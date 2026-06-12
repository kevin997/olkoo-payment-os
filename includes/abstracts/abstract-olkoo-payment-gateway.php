<?php
/**
 * Abstract Payment Gateway Class
 *
 * Extends WC_Payment_Gateway and implements Olkoo_Payment_Gateway_Interface
 * Provides common functionality for all gateway implementations
 *
 * @package OlkooPaymentOS
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

/**
 * Abstract class Abstract_Olkoo_Payment_Gateway
 */
abstract class Abstract_Olkoo_Payment_Gateway extends WC_Payment_Gateway implements Olkoo_Payment_Gateway_Interface {
    /**
     * Gateway test mode
     *
     * @var bool
     */
    protected $test_mode;

    /**
     * Gateway API key
     *
     * @var string
     */
    protected $api_key;

    /**
     * Gateway secret key
     *
     * @var string
     */
    protected $secret_key;

    /**
     * Gateway webhook secret
     *
     * @var string
     */
    protected $webhook_secret;

    /**
     * Logger instance
     *
     * @var Olkoo_Payment_Logger
     */
    protected $logger;

    /**
     * API client instance
     *
     * @var Olkoo_Payment_API_Client
     */
    protected $api_client;

    /**
     * Constructor
     */
    public function __construct() {
        // Load the settings
        $this->init_form_fields();
        $this->init_settings();

        // Get setting values
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->test_mode = 'yes' === $this->get_option('test_mode', 'no');

        // Initialize logger
        $this->logger = new Olkoo_Payment_Logger($this->id);

        // Hooks
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_api_' . $this->id, array($this, 'handle_webhook'));
    }

    /**
     * Initialize gateway form fields
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'olkoo-payment-os'),
                'type' => 'checkbox',
                'label' => __('Enable this payment gateway', 'olkoo-payment-os'),
                'default' => 'no',
            ),
            'title' => array(
                'title' => __('Title', 'olkoo-payment-os'),
                'type' => 'text',
                'description' => __('Payment method title that users see during checkout', 'olkoo-payment-os'),
                'default' => $this->method_title,
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'olkoo-payment-os'),
                'type' => 'textarea',
                'description' => __('Payment method description that users see during checkout', 'olkoo-payment-os'),
                'default' => $this->method_description,
                'desc_tip' => true,
            ),
            'test_mode' => array(
                'title' => __('Test Mode', 'olkoo-payment-os'),
                'type' => 'checkbox',
                'label' => __('Enable test mode', 'olkoo-payment-os'),
                'description' => __('Use test API credentials for testing', 'olkoo-payment-os'),
                'default' => 'yes',
                'desc_tip' => true,
            ),
        );

        // Allow child classes to add their own fields
        $this->form_fields = array_merge($this->form_fields, $this->get_gateway_form_fields());
    }

    /**
     * Get gateway-specific form fields
     * Should be overridden by child classes
     *
     * @return array
     */
    abstract protected function get_gateway_form_fields();

    /**
     * Process payment
     * Must be implemented by child classes
     *
     * @param int $order_id
     * @return array
     */
    abstract public function process_payment($order_id);

    /**
     * Process webhook
     * Must be implemented by child classes
     *
     * @param array $data
     * @return bool|WP_Error
     */
    abstract public function process_webhook($data);

    /**
     * Handle webhook callback
     */
    public function handle_webhook() {
        $this->logger->info('Webhook received');

        try {
            // Get raw POST data
            $raw_data = file_get_contents('php://input');
            $data = json_decode($raw_data, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('Invalid JSON in webhook payload');
                status_header(400);
                exit;
            }

            // Get signature from headers if available
            $signature = isset($_SERVER['HTTP_X_WEBHOOK_SIGNATURE']) ? $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] : '';

            // Validate webhook signature
            if ($this->webhook_secret && !$this->validate_webhook_signature($data, $signature)) {
                $this->logger->error('Invalid webhook signature');
                status_header(401);
                exit;
            }

            // Process the webhook
            $result = $this->process_webhook($data);

            if (is_wp_error($result)) {
                $this->logger->error('Webhook processing failed: ' . $result->get_error_message());
                status_header(500);
                exit;
            }

            $this->logger->info('Webhook processed successfully');
            status_header(200);
            exit;
        } catch (Exception $e) {
            $this->logger->error('Webhook exception: ' . $e->getMessage());
            status_header(500);
            exit;
        }
    }

    /**
     * Verify payment with gateway
     *
     * @param string $transaction_id
     * @param WC_Order $order
     * @return array
     */
    public function verify_payment($transaction_id, $order) {
        $this->logger->info('Verifying payment', array(
            'transaction_id' => $transaction_id,
            'order_id' => $order->get_id(),
        ));

        // Default implementation - should be overridden by child classes
        return array(
            'success' => false,
            'message' => __('Payment verification not implemented', 'olkoo-payment-os'),
        );
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
            return new WP_Error('invalid_order', __('Invalid order ID', 'olkoo-payment-os'));
        }

        $this->logger->info('Processing refund', array(
            'order_id' => $order_id,
            'amount' => $amount,
            'reason' => $reason,
        ));

        // Default implementation - refunds not supported
        return new WP_Error('refund_not_supported', __('Refunds are not supported by this gateway', 'olkoo-payment-os'));
    }

    /**
     * Validate webhook signature
     *
     * @param array $data
     * @param string $signature
     * @return bool
     */
    public function validate_webhook_signature($data, $signature) {
        // Default implementation - no validation
        // Should be overridden by child classes that support signature verification
        return true;
    }

    /**
     * Get gateway configuration
     *
     * @return array
     */
    public function get_gateway_config() {
        return array(
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'enabled' => $this->enabled === 'yes',
            'test_mode' => $this->test_mode,
            'supports' => $this->supports,
        );
    }

    /**
     * Check if gateway supports feature
     *
     * @param string $feature
     * @return bool
     */
    public function supports_feature($feature) {
        return $this->supports($feature);
    }

    /**
     * Get webhook URL for this gateway
     *
     * @return string
     */
    public function get_webhook_url() {
        return WC()->api_request_url($this->id);
    }

    /**
     * Log debug message
     *
     * @param string $message
     * @param array $context
     */
    protected function log_debug($message, $context = array()) {
        $this->logger->debug($message, $context);
    }

    /**
     * Log info message
     *
     * @param string $message
     * @param array $context
     */
    protected function log_info($message, $context = array()) {
        $this->logger->info($message, $context);
    }

    /**
     * Log error message
     *
     * @param string $message
     * @param array $context
     */
    protected function log_error($message, $context = array()) {
        $this->logger->error($message, $context);
    }

    /**
     * Mark order as failed
     *
     * @param WC_Order $order
     * @param string $message
     */
    protected function mark_order_as_failed($order, $message) {
        $order->update_status('failed', $message);
        $this->log_error('Order marked as failed', array(
            'order_id' => $order->get_id(),
            'message' => $message,
        ));
    }

    /**
     * Mark order as processing
     *
     * @param WC_Order $order
     * @param string $transaction_id
     * @param string $message
     */
    protected function mark_order_as_processing($order, $transaction_id, $message = '') {
        $order->payment_complete($transaction_id);

        if ($message) {
            $order->add_order_note($message);
        }

        $this->log_info('Order marked as processing', array(
            'order_id' => $order->get_id(),
            'transaction_id' => $transaction_id,
        ));
    }
}
