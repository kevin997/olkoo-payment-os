<?php
/**
 * Payment Gateway Interface
 *
 * Defines the contract that all payment gateway implementations must follow
 *
 * @package OlkooPaymentOS
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

/**
 * Interface Olkoo_Payment_Gateway_Interface
 */
interface Olkoo_Payment_Gateway_Interface {
    /**
     * Process the payment
     *
     * @param int $order_id WooCommerce order ID
     * @return array Result array with success status and redirect URL or error message
     */
    public function process_payment($order_id);

    /**
     * Process webhook callback from payment gateway
     *
     * @param array $data Webhook payload data
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function process_webhook($data);

    /**
     * Verify payment status with gateway API
     *
     * @param string $transaction_id Transaction ID from gateway
     * @param WC_Order $order WooCommerce order object
     * @return array Payment status data
     */
    public function verify_payment($transaction_id, $order);

    /**
     * Process refund
     *
     * @param int $order_id WooCommerce order ID
     * @param float|null $amount Amount to refund
     * @param string $reason Reason for refund
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function process_refund($order_id, $amount = null, $reason = '');

    /**
     * Validate webhook signature for security
     *
     * @param array $data Webhook payload
     * @param string $signature Signature from webhook header
     * @return bool True if signature is valid, false otherwise
     */
    public function validate_webhook_signature($data, $signature);

    /**
     * Get gateway configuration array
     *
     * @return array Gateway configuration
     */
    public function get_gateway_config();

    /**
     * Check if gateway supports specific feature
     *
     * @param string $feature Feature name
     * @return bool
     */
    public function supports_feature($feature);
}
