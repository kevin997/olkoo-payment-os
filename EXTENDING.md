# Extending Olkoo Payment OS

This guide explains how to extend Olkoo Payment OS to add support for new payment gateways.

## Architecture Overview

Olkoo Payment OS uses an extensible architecture with:

- **Interface**: `Olkoo_Payment_Gateway_Interface` - Defines the contract
- **Abstract Class**: `Abstract_Olkoo_Payment_Gateway` - Provides common functionality
- **Factory**: `Olkoo_Payment_Gateway_Factory` - Creates gateway instances
- **Utilities**: Logger and API client for common operations

## Creating a Custom Gateway

### Step 1: Create Gateway Class

Create a new file in `includes/gateways/`:

```php
<?php
/**
 * Custom Payment Gateway
 *
 * @package OlkooPaymentOS
 */

defined('ABSPATH') || exit;

class Olkoo_Gateway_Custom extends Abstract_Olkoo_Payment_Gateway {

    /**
     * Constructor
     */
    public function __construct() {
        // Gateway identification
        $this->id = 'custom_gateway';
        $this->icon = OLKOO_PAYMENT_OS_PLUGIN_URL . 'assets/images/custom-logo.png';
        $this->has_fields = false; // Set true if you need custom checkout fields
        $this->method_title = __('Custom Gateway', 'olkoo-payment-os');
        $this->method_description = __('Accept payments via Custom Gateway', 'olkoo-payment-os');

        // Supported features
        $this->supports = array(
            'products',
            'refunds', // Remove if refunds not supported
        );

        // Call parent constructor
        parent::__construct();

        // Load gateway settings
        $this->api_key = $this->test_mode
            ? $this->get_option('test_api_key')
            : $this->get_option('api_key');

        $this->secret_key = $this->test_mode
            ? $this->get_option('test_secret_key')
            : $this->get_option('secret_key');

        // Initialize API client
        $this->api_client = new Olkoo_Payment_API_Client(
            'https://api.customgateway.com',
            array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ),
            $this->logger
        );
    }

    /**
     * Get gateway-specific form fields
     *
     * @return array
     */
    protected function get_gateway_form_fields() {
        return array(
            'api_credentials' => array(
                'title' => __('API Credentials', 'olkoo-payment-os'),
                'type' => 'title',
                'description' => __('Enter your Custom Gateway API credentials', 'olkoo-payment-os'),
            ),
            'api_key' => array(
                'title' => __('Live API Key', 'olkoo-payment-os'),
                'type' => 'text',
                'description' => __('Your production API key', 'olkoo-payment-os'),
                'default' => '',
                'desc_tip' => true,
            ),
            'secret_key' => array(
                'title' => __('Live Secret Key', 'olkoo-payment-os'),
                'type' => 'password',
                'description' => __('Your production secret key', 'olkoo-payment-os'),
                'default' => '',
                'desc_tip' => true,
            ),
            'test_api_key' => array(
                'title' => __('Test API Key', 'olkoo-payment-os'),
                'type' => 'text',
                'description' => __('Your sandbox API key', 'olkoo-payment-os'),
                'default' => '',
                'desc_tip' => true,
            ),
            'test_secret_key' => array(
                'title' => __('Test Secret Key', 'olkoo-payment-os'),
                'type' => 'password',
                'description' => __('Your sandbox secret key', 'olkoo-payment-os'),
                'default' => '',
                'desc_tip' => true,
            ),
            'webhook_url' => array(
                'title' => __('Webhook URL', 'olkoo-payment-os'),
                'type' => 'title',
                'description' => sprintf(
                    __('Configure this URL in your gateway dashboard: %s', 'olkoo-payment-os'),
                    '<br><code>' . $this->get_webhook_url() . '</code>'
                ),
            ),
        );
    }

    /**
     * Process payment
     *
     * @param int $order_id
     * @return array
     */
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);

        if (!$order) {
            return array(
                'result' => 'error',
                'message' => __('Invalid order', 'olkoo-payment-os'),
            );
        }

        $this->log_info('Processing payment', array('order_id' => $order_id));

        // Prepare payment data
        $payment_data = array(
            'amount' => $order->get_total(),
            'currency' => $order->get_currency(),
            'order_id' => $order->get_id(),
            'description' => sprintf(__('Order #%s', 'olkoo-payment-os'), $order->get_order_number()),
            'customer' => array(
                'name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                'email' => $order->get_billing_email(),
                'phone' => $order->get_billing_phone(),
            ),
            'return_url' => $this->get_return_url($order),
            'webhook_url' => $this->get_webhook_url(),
        );

        // Make API request
        $response = $this->api_client->post('payments', $payment_data);

        if (is_wp_error($response)) {
            $this->log_error('Payment creation failed', array(
                'error' => $response->get_error_message(),
            ));

            wc_add_notice(__('Payment initialization failed. Please try again.', 'olkoo-payment-os'), 'error');

            return array(
                'result' => 'error',
                'message' => $response->get_error_message(),
            );
        }

        $data = $response['data'];

        // Check if payment was successful
        if (!isset($data['payment_url'])) {
            $error_message = $data['error'] ?? __('Failed to create payment', 'olkoo-payment-os');
            $this->log_error('Payment creation failed', array('response' => $data));

            wc_add_notice($error_message, 'error');

            return array(
                'result' => 'error',
                'message' => $error_message,
            );
        }

        // Save payment info to order
        $order->update_meta_data('_custom_gateway_payment_id', $data['payment_id']);
        $order->update_meta_data('_custom_gateway_payment_url', $data['payment_url']);
        $order->save();

        // Mark order as pending
        $order->update_status('pending', __('Awaiting payment', 'olkoo-payment-os'));

        $this->log_info('Payment created successfully', array(
            'order_id' => $order->get_id(),
            'payment_id' => $data['payment_id'],
        ));

        // Redirect to payment page
        return array(
            'result' => 'success',
            'redirect' => $data['payment_url'],
        );
    }

    /**
     * Process webhook
     *
     * @param array $data
     * @return bool|WP_Error
     */
    public function process_webhook($data) {
        $this->log_info('Processing webhook', array('data' => $data));

        // Extract payment information
        $payment_id = $data['payment_id'] ?? '';
        $status = $data['status'] ?? '';
        $order_id = $data['order_id'] ?? '';

        if (empty($payment_id) || empty($order_id)) {
            return new WP_Error('invalid_webhook', 'Missing required webhook data');
        }

        $order = wc_get_order($order_id);

        if (!$order) {
            return new WP_Error('order_not_found', 'Order not found: ' . $order_id);
        }

        // Process based on status
        switch ($status) {
            case 'completed':
            case 'success':
                $this->process_successful_payment($order, $data);
                break;

            case 'failed':
            case 'cancelled':
                $this->process_failed_payment($order, $data);
                break;

            case 'pending':
                $order->add_order_note(__('Payment is pending', 'olkoo-payment-os'));
                break;

            default:
                return new WP_Error('unknown_status', 'Unknown payment status: ' . $status);
        }

        return true;
    }

    /**
     * Process successful payment
     *
     * @param WC_Order $order
     * @param array $data
     */
    private function process_successful_payment($order, $data) {
        if ($order->has_status(array('processing', 'completed'))) {
            $this->log_info('Order already processed', array('order_id' => $order->get_id()));
            return;
        }

        $transaction_id = $data['transaction_id'] ?? $data['payment_id'];

        $order->add_order_note(
            sprintf(__('Payment completed. Transaction ID: %s', 'olkoo-payment-os'), $transaction_id)
        );

        $this->mark_order_as_processing($order, $transaction_id);

        $this->log_info('Payment completed successfully', array(
            'order_id' => $order->get_id(),
            'transaction_id' => $transaction_id,
        ));
    }

    /**
     * Process failed payment
     *
     * @param WC_Order $order
     * @param array $data
     */
    private function process_failed_payment($order, $data) {
        $reason = $data['failure_reason'] ?? 'Payment failed';
        $message = sprintf(__('Payment failed: %s', 'olkoo-payment-os'), $reason);

        $this->mark_order_as_failed($order, $message);

        $this->log_error('Payment failed', array(
            'order_id' => $order->get_id(),
            'reason' => $reason,
        ));
    }

    /**
     * Process refund
     *
     * @param int $order_id
     * @param float|null $amount
     * @param string $reason
     * @return bool|WP_Error
     */
    public function process_refund($order_id, $amount = null, $reason = '') {
        $order = wc_get_order($order_id);

        if (!$order) {
            return new WP_Error('invalid_order', __('Invalid order', 'olkoo-payment-os'));
        }

        $payment_id = $order->get_meta('_custom_gateway_payment_id');

        if (empty($payment_id)) {
            return new WP_Error('no_payment_id', __('Payment ID not found', 'olkoo-payment-os'));
        }

        $this->log_info('Processing refund', array(
            'order_id' => $order_id,
            'amount' => $amount,
            'reason' => $reason,
        ));

        $refund_data = array(
            'payment_id' => $payment_id,
            'amount' => $amount ?? $order->get_total(),
            'reason' => $reason,
        );

        $response = $this->api_client->post('refunds', $refund_data);

        if (is_wp_error($response)) {
            $this->log_error('Refund failed', array('error' => $response->get_error_message()));
            return $response;
        }

        $data = $response['data'];

        if (!isset($data['refund_id'])) {
            return new WP_Error('refund_failed', __('Refund request failed', 'olkoo-payment-os'));
        }

        $order->add_order_note(
            sprintf(__('Refund processed. Refund ID: %s', 'olkoo-payment-os'), $data['refund_id'])
        );

        $this->log_info('Refund processed successfully', array(
            'order_id' => $order_id,
            'refund_id' => $data['refund_id'],
        ));

        return true;
    }

    /**
     * Validate webhook signature
     *
     * @param array $data
     * @param string $signature
     * @return bool
     */
    public function validate_webhook_signature($data, $signature) {
        if (empty($this->secret_key)) {
            $this->log_warning('No secret key configured for signature validation');
            return true;
        }

        // Implement your gateway's signature validation logic
        $expected_signature = hash_hmac('sha256', json_encode($data), $this->secret_key);

        return hash_equals($expected_signature, $signature);
    }
}
```

### Step 2: Register the Gateway

In your plugin file or a separate initialization file:

```php
// Register with factory
Olkoo_Payment_Gateway_Factory::register_gateway('custom_gateway', 'Olkoo_Gateway_Custom');

// Add to gateway list
add_filter('olkoo_payment_os_gateways', function($gateways) {
    $gateways[] = 'Olkoo_Gateway_Custom';
    return $gateways;
});
```

### Step 3: Include the Gateway File

In the main plugin file's `includes()` method:

```php
require_once OLKOO_PAYMENT_OS_PLUGIN_DIR . 'includes/gateways/class-olkoo-gateway-custom.php';
```

## Advanced Features

### Custom Checkout Fields

If your gateway needs custom fields at checkout:

```php
public function __construct() {
    // ... existing code ...
    $this->has_fields = true;
}

public function payment_fields() {
    if ($this->description) {
        echo wpautop(wp_kses_post($this->description));
    }
    ?>
    <fieldset>
        <p class="form-row form-row-wide">
            <label for="custom_field"><?php _e('Custom Field', 'olkoo-payment-os'); ?></label>
            <input type="text" name="custom_field" id="custom_field" />
        </p>
    </fieldset>
    <?php
}

public function validate_fields() {
    if (empty($_POST['custom_field'])) {
        wc_add_notice(__('Custom field is required', 'olkoo-payment-os'), 'error');
        return false;
    }
    return true;
}
```

### Payment Verification

Implement payment verification:

```php
public function verify_payment($transaction_id, $order) {
    $response = $this->api_client->get('payments/' . $transaction_id);

    if (is_wp_error($response)) {
        return array(
            'success' => false,
            'message' => $response->get_error_message(),
        );
    }

    $data = $response['data'];

    return array(
        'success' => true,
        'status' => $data['status'],
        'amount' => $data['amount'],
        'currency' => $data['currency'],
    );
}
```

### Custom Webhook Handler

Register a custom webhook endpoint:

```php
add_action('olkoo_payment_os_register_webhooks', function($webhook_handler) {
    $webhook_handler->register_webhook_handler('custom_gateway', array($this, 'handle_custom_webhook'));
});

public function handle_custom_webhook() {
    // Custom webhook handling logic
}
```

## Testing Your Gateway

### Unit Testing

Create tests for your gateway:

```php
class Test_Olkoo_Gateway_Custom extends WP_UnitTestCase {
    public function test_gateway_initialization() {
        $gateway = new Olkoo_Gateway_Custom();
        $this->assertEquals('custom_gateway', $gateway->id);
    }

    public function test_payment_processing() {
        // Test payment processing logic
    }

    public function test_webhook_processing() {
        // Test webhook handling
    }
}
```

### Manual Testing

1. Enable test mode
2. Configure test API credentials
3. Create test order
4. Complete payment with test card/account
5. Verify webhook receives callback
6. Check order status updates correctly

## Best Practices

1. **Error Handling**: Always handle API errors gracefully
2. **Logging**: Use the logger for all operations
3. **Security**: Validate all inputs and webhook signatures
4. **Idempotency**: Handle duplicate webhooks safely
5. **Testing**: Test thoroughly before production
6. **Documentation**: Document gateway-specific features

## Example: Stripe Gateway

For a complete example, see how to implement Stripe:

```php
// Coming soon: Full Stripe gateway implementation example
```

## Support

For help extending the plugin:

- Review existing gateway implementations
- Check the architecture documentation
- Post questions in GitHub Discussions
- Contact support@okenlysolutions.com

---

**Happy coding! 🚀**
