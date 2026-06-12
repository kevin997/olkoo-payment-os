<?php
/**
 * TaraMoney Payment Gateway
 *
 * Implements TaraMoney payment gateway integration for WooCommerce
 * Supports Payment Links (WhatsApp, Telegram, SMS, Dikalo)
 *
 * API Documentation: https://www.dklo.co/api/tara/paymentlinks
 *
 * @package OlkooPaymentOS
 * @since 1.0.0
 * @updated 1.1.0 - Updated to new Tara Payment Links API
 */

defined('ABSPATH') || exit;

/**
 * Class Olkoo_Gateway_TaraMoney
 */
class Olkoo_Gateway_TaraMoney extends Abstract_Olkoo_Payment_Gateway {
    /**
     * TaraMoney API base URL
     */
    const API_BASE_URL = 'https://www.dklo.co/api/tara';

    /**
     * Payment Links endpoint
     */
    const ENDPOINT_PAYMENT_LINKS = 'paymentlinks';

    /**
     * Mobile Money endpoint (deprecated)
     * @deprecated 1.1.0 Mobile money is now handled via collects/webhooks
     */
    const ENDPOINT_MOBILE_MONEY = 'cmmobile';

    /**
     * Business ID
     *
     * @var string
     */
    private $business_id;

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'taramoney';
        $this->icon = apply_filters('olkoo_taramoney_icon', OLKOO_PAYMENT_OS_PLUGIN_URL . 'assets/images/taramoney-logo.jpg');
        $this->has_fields = false;
        $this->method_title = __('TaraMoney', 'olkoo-payment-os');
        $this->method_description = __('Accept payments via TaraMoney Payment Links (WhatsApp, Telegram, SMS, Dikalo). Supports Mobile Money and Card payments via webhooks.', 'olkoo-payment-os');
        $this->supports = array(
            'products',
            'refunds',
        );

        parent::__construct();

        // Get setting values
        $this->api_key = $this->test_mode
            ? $this->get_option('test_api_key')
            : $this->get_option('api_key');

        $this->business_id = $this->test_mode
            ? $this->get_option('test_business_id')
            : $this->get_option('business_id');

        $this->webhook_secret = $this->get_option('webhook_secret');

        // Initialize API client
        $this->api_client = new Olkoo_Payment_API_Client(
            self::API_BASE_URL,
            array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
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
            'api_credentials_title' => array(
                'title' => __('API Credentials', 'olkoo-payment-os'),
                'type' => 'title',
                'description' => __('Enter your TaraMoney API credentials. Get them from your TaraMoney dashboard.', 'olkoo-payment-os'),
            ),
            'api_key' => array(
                'title' => __('Live API Key', 'olkoo-payment-os'),
                'type' => 'password',
                'description' => __('Your TaraMoney live API key', 'olkoo-payment-os'),
                'default' => '',
                'desc_tip' => true,
            ),
            'business_id' => array(
                'title' => __('Live Business ID', 'olkoo-payment-os'),
                'type' => 'password',
                'description' => __('Your TaraMoney live business ID', 'olkoo-payment-os'),
                'default' => '',
                'desc_tip' => true,
            ),
            'test_api_key' => array(
                'title' => __('Test API Key', 'olkoo-payment-os'),
                'type' => 'password',
                'description' => __('Your TaraMoney sandbox API key', 'olkoo-payment-os'),
                'default' => '',
                'desc_tip' => true,
            ),
            'test_business_id' => array(
                'title' => __('Test Business ID', 'olkoo-payment-os'),
                'type' => 'password',
                'description' => __('Your TaraMoney sandbox business ID', 'olkoo-payment-os'),
                'default' => '',
                'desc_tip' => true,
            ),
            'webhook_secret' => array(
                'title' => __('Webhook Secret', 'olkoo-payment-os'),
                'type' => 'password',
                'description' => __('Shared secret appended as a token query parameter to the webhook URL and verified on every notification. TaraMoney does not sign webhook payloads.', 'olkoo-payment-os'),
                'default' => '',
                'desc_tip' => true,
            ),
            'payment_options_title' => array(
                'title' => __('Payment Options', 'olkoo-payment-os'),
                'type' => 'title',
                'description' => __('Payment links are generated for customers to pay via their preferred method.', 'olkoo-payment-os'),
            ),
            'enable_order_links' => array(
                'title' => __('Enable Payment Links', 'olkoo-payment-os'),
                'type' => 'checkbox',
                'label' => __('Allow payments via WhatsApp, Telegram, SMS, and Dikalo', 'olkoo-payment-os'),
                'default' => 'yes',
            ),
            'webhook_info' => array(
                'title' => __('Webhook Configuration', 'olkoo-payment-os'),
                'type' => 'title',
                'description' => sprintf(
                    __('Configure this webhook URL in your TaraMoney dashboard to receive payment notifications:%s%sThis URL handles both Mobile Money (collects) and Card payment webhooks automatically.', 'olkoo-payment-os'),
                    '<br><code>' . $this->get_webhook_url() . '</code><br><br>',
                    ''
                ),
            ),
            'supported_payments_info' => array(
                'title' => __('Supported Payment Methods', 'olkoo-payment-os'),
                'type' => 'title',
                'description' => __('<strong>Payment Links:</strong> WhatsApp, Telegram, SMS, Dikalo<br><strong>Webhook Payments:</strong> Mobile Money (Orange Money, MTN Mobile Money), Card Payments<br><br><em>Note: Mobile Money and Card payments are processed through Tara\'s unified payment interface and confirmed via webhooks.</em>', 'olkoo-payment-os'),
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

        // Check if API credentials are configured
        if (empty($this->api_key) || empty($this->business_id)) {
            wc_add_notice(__('Payment gateway is not properly configured. Please contact the store administrator.', 'olkoo-payment-os'), 'error');
            return array(
                'result' => 'error',
                'message' => __('Gateway not configured', 'olkoo-payment-os'),
            );
        }

        $this->log_info('Processing payment', array('order_id' => $order_id));

        // Process payment using Payment Links API
        // Note: Mobile Money is now deprecated and handled via collects/webhooks
        return $this->process_order_link_payment($order);
    }

    /**
     * Process payment link generation
     *
     * Uses the new Payment Links API endpoint: POST /api/tara/paymentlinks
     *
     * @param WC_Order $order
     * @return array
     */
    private function process_order_link_payment($order) {
        $this->log_info('Creating TaraMoney payment link', array('order_id' => $order->get_id()));

        $product_name = $this->get_order_product_name($order);
        $product_description = $this->get_order_description($order);

        $request_data = array(
            'apiKey' => $this->api_key,
            'businessId' => $this->business_id,
            'productId' => (string) $order->get_id(),
            'productName' => $product_name,
            'productPrice' => (int) $order->get_total(),
            'productDescription' => $product_description,
            'productPictureUrl' => $this->get_order_product_image($order),
            'returnUrl' => $this->get_return_url($order),
            'webHookUrl' => $this->get_order_webhook_url($order),
        );

        // Use new paymentlinks endpoint
        $response = $this->api_client->post(self::ENDPOINT_PAYMENT_LINKS, $request_data);

        if (is_wp_error($response)) {
            $this->log_error('Payment link creation failed', array(
                'error' => $response->get_error_message(),
            ));

            wc_add_notice(__('Payment initialization failed. Please try again.', 'olkoo-payment-os'), 'error');

            return array(
                'result' => 'error',
                'message' => $response->get_error_message(),
            );
        }

        $data = $response['data'];

        // Check if payment link was created successfully
        // New API returns status: "success" (lowercase)
        // Also maintain backwards compatibility with old response format
        $is_success = (isset($data['status']) && strtolower($data['status']) === 'success') ||
                      (isset($data['message']) && stripos($data['message'], 'successfully') !== false);

        if (!$is_success) {
            $error_message = isset($data['message']) ? $data['message'] : __('Failed to create payment link', 'olkoo-payment-os');
            $this->log_error('Payment link creation failed', array('response' => $data));

            wc_add_notice($error_message, 'error');

            return array(
                'result' => 'error',
                'message' => $error_message,
            );
        }

        // Save payment links to order meta
        $order->update_meta_data('_taramoney_general_link', $data['generalLink'] ?? '');
        $order->update_meta_data('_taramoney_card_link', $data['cardLink'] ?? '');
        $order->update_meta_data('_taramoney_whatsapp_link', $data['whatsappLink'] ?? '');
        $order->update_meta_data('_taramoney_telegram_link', $data['telegramLink'] ?? '');
        $order->update_meta_data('_taramoney_dikalo_link', $data['dikaloLink'] ?? '');
        $order->update_meta_data('_taramoney_sms_link', $data['smsLink'] ?? '');
        $order->update_meta_data('_taramoney_payment_type', 'order_link');
        $order->save();

        // Mark order as pending payment
        $order->update_status('pending', __('Awaiting TaraMoney payment', 'olkoo-payment-os'));

        $this->log_info('Order link created successfully', array(
            'order_id' => $order->get_id(),
            'links' => array_keys(array_filter(array(
                'general' => $data['generalLink'] ?? '',
                'card' => $data['cardLink'] ?? '',
                'whatsapp' => $data['whatsappLink'] ?? '',
                'telegram' => $data['telegramLink'] ?? '',
                'dikalo' => $data['dikaloLink'] ?? '',
                'sms' => $data['smsLink'] ?? '',
            ))),
        ));

        // Prefer the unified payment page (generalLink), then card, then channel-specific links
        $link_candidates = array_filter(array(
            $data['generalLink'] ?? '',
            $data['cardLink'] ?? '',
            $data['dikaloLink'] ?? '',
            $data['whatsappLink'] ?? '',
            $data['telegramLink'] ?? '',
            $data['smsLink'] ?? '',
        ));
        $redirect_url = !empty($link_candidates) ? reset($link_candidates) : $this->get_return_url($order);

        return array(
            'result' => 'success',
            'redirect' => $redirect_url,
        );
    }

    /**
     * Process mobile money payment
     *
     * @deprecated 1.1.0 Mobile money payments are now handled via Tara collects/webhooks.
     *             Use the Payment Links API instead, which redirects users to complete
     *             payment through Tara's unified payment interface.
     *
     * @param WC_Order $order
     * @param string $phone_number
     * @return array
     */
    private function process_mobile_money_payment($order, $phone_number) {
        // Log deprecation warning
        $this->log_info('DEPRECATED: Direct mobile money payment called', array(
            'order_id' => $order->get_id(),
            'phone_number' => $phone_number,
            'message' => 'Mobile money is now handled via Tara collects/webhooks. Falling back to payment links.',
        ));

        // Fall back to payment links
        return $this->process_order_link_payment($order);
    }

    /**
     * Process webhook
     *
     * Handles webhooks for both Collects (Mobile Money) and Card Payments.
     *
     * Collects Webhook Payload:
     * - businessId, paymentId, amount, mobileOperator, customerName
     * - collectionId, transactionCode, customerId, phoneNumber
     * - creationDate, changeDate, type (DEPOSIT/TRANSFER), status (SUCCESS/FAILURE)
     *
     * Card Payment Webhook Payload:
     * - businessId, status (SUCCESS/FAILURE), paymentId
     * - collectionId, creationDate, changeDate
     *
     * @param array $data
     * @return bool|WP_Error
     */
    public function process_webhook($data) {
        // Determine webhook type
        $webhook_type = $this->detect_webhook_type($data);

        $this->log_info('Processing webhook', array(
            'type' => $webhook_type,
            'data' => $data,
        ));

        // Extract payment information
        $payment_id = $data['paymentId'] ?? '';
        $status = $data['status'] ?? '';
        $business_id = $data['businessId'] ?? '';

        // Verify business ID
        if ($business_id !== $this->business_id) {
            return new WP_Error('invalid_business', 'Invalid business ID in webhook');
        }

        // Find order by payment ID or collection ID
        $order_id = $this->find_order_by_payment_id($payment_id, $data);

        if (!$order_id) {
            return new WP_Error('order_not_found', 'Order not found for payment ID: ' . $payment_id);
        }

        $order = wc_get_order($order_id);

        if (!$order) {
            return new WP_Error('invalid_order', 'Invalid order ID: ' . $order_id);
        }

        // Process payment based on status
        if ($status === 'SUCCESS') {
            $this->process_successful_payment($order, $data, $webhook_type);
        } else {
            $this->process_failed_payment($order, $data, $webhook_type);
        }

        return true;
    }

    /**
     * Build the webhook URL for a specific order
     *
     * TaraMoney does not sign webhook payloads, so the webhook secret is
     * passed as a token query parameter and verified on receipt. The order ID
     * is also appended because card payment webhooks do not include productId.
     *
     * @param WC_Order $order
     * @return string
     */
    private function get_order_webhook_url($order) {
        $args = array('order_id' => $order->get_id());

        if (!empty($this->webhook_secret)) {
            $args['token'] = $this->webhook_secret;
        }

        return add_query_arg($args, $this->get_webhook_url());
    }

    /**
     * Validate webhook authenticity
     *
     * TaraMoney sends no signature header; the shared secret travels as a
     * token query parameter on the webhook URL we registered.
     *
     * @param array $data
     * @param string $signature
     * @return bool
     */
    public function validate_webhook_signature($data, $signature) {
        if (empty($this->webhook_secret)) {
            return true;
        }

        $token = isset($_GET['token']) ? sanitize_text_field(wp_unslash($_GET['token'])) : '';

        return hash_equals($this->webhook_secret, $token);
    }

    /**
     * Detect the type of webhook from payload
     *
     * @param array $data Webhook payload
     * @return string 'collects' for mobile money, 'card' for card payments
     */
    private function detect_webhook_type($data) {
        // Collects (mobile money) webhooks have mobileOperator and type fields
        if (isset($data['mobileOperator']) || isset($data['type'])) {
            return 'collects';
        }

        // Card payment webhooks are simpler with just status and IDs
        return 'card';
    }

    /**
     * Find order by payment ID
     *
     * @param string $payment_id
     * @param array $data Webhook data
     * @return int|null Order ID or null if not found
     */
    private function find_order_by_payment_id($payment_id, $data) {
        // Preferred: order ID passed back as a query parameter on the webhook URL
        // (card webhooks carry no productId, so this is the only reliable mapping)
        if (isset($_GET['order_id'])) {
            $order_id = absint(wp_unslash($_GET['order_id']));
            if ($order_id > 0 && wc_get_order($order_id)) {
                return $order_id;
            }
        }

        // First try to find by collection ID (for mobile money)
        if (isset($data['collectionId'])) {
            $orders = wc_get_orders(array(
                'meta_key' => '_taramoney_payment_id',
                'meta_value' => $payment_id,
                'limit' => 1,
            ));

            if (!empty($orders)) {
                return $orders[0]->get_id();
            }
        }

        // Try to extract order ID from productId
        if (isset($data['productId'])) {
            $order_id = (int) $data['productId'];
            if ($order_id > 0 && wc_get_order($order_id)) {
                return $order_id;
            }
        }

        return null;
    }

    /**
     * Process successful payment
     *
     * @param WC_Order $order
     * @param array $data
     * @param string $webhook_type 'collects' or 'card'
     */
    private function process_successful_payment($order, $data, $webhook_type = 'collects') {
        if ($order->has_status(array('processing', 'completed'))) {
            $this->log_info('Order already processed', array('order_id' => $order->get_id()));
            return;
        }

        $payment_id = $data['paymentId'] ?? '';
        $transaction_code = $data['transactionCode'] ?? $payment_id;
        $collection_id = $data['collectionId'] ?? '';

        // Store webhook data as order meta
        $order->update_meta_data('_taramoney_payment_id', $payment_id);
        $order->update_meta_data('_taramoney_collection_id', $collection_id);
        $order->update_meta_data('_taramoney_webhook_type', $webhook_type);
        $order->update_meta_data('_taramoney_creation_date', $data['creationDate'] ?? '');
        $order->update_meta_data('_taramoney_change_date', $data['changeDate'] ?? '');

        // Build order note based on webhook type
        if ($webhook_type === 'collects') {
            // Store mobile money specific data
            $mobile_operator = $data['mobileOperator'] ?? '';
            $phone_number = $data['phoneNumber'] ?? '';
            $amount = $data['amount'] ?? '';
            $payment_type = $data['type'] ?? 'DEPOSIT';

            $order->update_meta_data('_taramoney_mobile_operator', $mobile_operator);
            $order->update_meta_data('_taramoney_phone_number', $phone_number);
            $order->update_meta_data('_taramoney_amount', $amount);
            $order->update_meta_data('_taramoney_payment_method', 'mobile_money');
            $order->update_meta_data('_taramoney_transaction_type', $payment_type);

            $order->add_order_note(
                sprintf(
                    __('TaraMoney mobile money payment completed.%sTransaction ID: %s%sOperator: %s%sPhone: %s%sAmount: %s FCFA%sType: %s', 'olkoo-payment-os'),
                    "\n",
                    $transaction_code,
                    "\n",
                    $mobile_operator,
                    "\n",
                    $phone_number,
                    "\n",
                    $amount,
                    "\n",
                    $payment_type
                )
            );
        } else {
            // Card payment
            $order->update_meta_data('_taramoney_payment_method', 'card');

            $order->add_order_note(
                sprintf(
                    __('TaraMoney card payment completed.%sPayment ID: %s%sCollection ID: %s', 'olkoo-payment-os'),
                    "\n",
                    $payment_id,
                    "\n",
                    $collection_id
                )
            );
        }

        $order->save();

        $this->mark_order_as_processing($order, $transaction_code);

        $this->log_info('Payment completed successfully', array(
            'order_id' => $order->get_id(),
            'transaction_id' => $transaction_code,
            'webhook_type' => $webhook_type,
        ));
    }

    /**
     * Process failed payment
     *
     * @param WC_Order $order
     * @param array $data
     * @param string $webhook_type 'collects' or 'card'
     */
    private function process_failed_payment($order, $data, $webhook_type = 'collects') {
        $status = $data['status'] ?? 'FAILURE';
        $payment_id = $data['paymentId'] ?? '';

        // Store failure data
        $order->update_meta_data('_taramoney_payment_id', $payment_id);
        $order->update_meta_data('_taramoney_webhook_type', $webhook_type);
        $order->update_meta_data('_taramoney_failure_status', $status);

        if ($webhook_type === 'collects') {
            $mobile_operator = $data['mobileOperator'] ?? '';
            $phone_number = $data['phoneNumber'] ?? '';

            $message = sprintf(
                __('TaraMoney mobile money payment failed.%sStatus: %s%sOperator: %s%sPhone: %s', 'olkoo-payment-os'),
                "\n",
                $status,
                "\n",
                $mobile_operator,
                "\n",
                $phone_number
            );
        } else {
            $message = sprintf(
                __('TaraMoney card payment failed.%sStatus: %s%sPayment ID: %s', 'olkoo-payment-os'),
                "\n",
                $status,
                "\n",
                $payment_id
            );
        }

        $order->save();

        $this->mark_order_as_failed($order, $message);

        $this->log_error('Payment failed', array(
            'order_id' => $order->get_id(),
            'status' => $status,
            'webhook_type' => $webhook_type,
        ));
    }

    /**
     * Get order product name
     *
     * @param WC_Order $order
     * @return string
     */
    private function get_order_product_name($order) {
        $items = $order->get_items();

        if (count($items) === 1) {
            $item = reset($items);
            return $item->get_name();
        }

        return sprintf(__('Order #%s', 'olkoo-payment-os'), $order->get_order_number());
    }

    /**
     * Get order description
     *
     * @param WC_Order $order
     * @return string
     */
    private function get_order_description($order) {
        $items = $order->get_items();
        $item_names = array();

        foreach ($items as $item) {
            $item_names[] = $item->get_name() . ' x ' . $item->get_quantity();
        }

        return implode(', ', $item_names);
    }

    /**
     * Get order product image
     *
     * @param WC_Order $order
     * @return string
     */
    private function get_order_product_image($order) {
        $items = $order->get_items();

        if (empty($items)) {
            return '';
        }

        $item = reset($items);
        $product = $item->get_product();

        if (!$product) {
            return '';
        }

        $image_id = $product->get_image_id();

        if (!$image_id) {
            return '';
        }

        $image_url = wp_get_attachment_image_url($image_id, 'medium');

        return $image_url ? $image_url : '';
    }
}
