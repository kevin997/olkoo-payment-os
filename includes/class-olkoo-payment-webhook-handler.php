<?php
/**
 * Payment Webhook Handler Class
 *
 * Centralized webhook handler for all payment gateways
 *
 * @package OlkooPaymentOS
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

/**
 * Class Olkoo_Payment_Webhook_Handler
 */
class Olkoo_Payment_Webhook_Handler {
    /**
     * Constructor
     */
    public function __construct() {
        // Register webhook endpoints for each gateway
        add_action('woocommerce_api_olkoo_webhook_taramoney', array($this, 'handle_taramoney_webhook'));

        // Allow other plugins to register their webhook handlers
        do_action('olkoo_payment_os_register_webhooks', $this);
    }

    /**
     * Handle TaraMoney webhook
     */
    public function handle_taramoney_webhook() {
        $gateway = Olkoo_Payment_Gateway_Factory::create_gateway('taramoney');

        if ($gateway) {
            $gateway->handle_webhook();
        } else {
            status_header(404);
            exit;
        }
    }

    /**
     * Register webhook handler for a gateway
     *
     * @param string $gateway_id Gateway identifier
     * @param callable $callback Callback function to handle webhook
     */
    public function register_webhook_handler($gateway_id, $callback) {
        $hook_name = 'woocommerce_api_olkoo_webhook_' . $gateway_id;
        add_action($hook_name, $callback);
    }

    /**
     * Get webhook URL for a gateway
     *
     * @param string $gateway_id Gateway identifier
     * @return string Webhook URL
     */
    public static function get_webhook_url($gateway_id) {
        return WC()->api_request_url('olkoo_webhook_' . $gateway_id);
    }
}

// Initialize webhook handler
new Olkoo_Payment_Webhook_Handler();
