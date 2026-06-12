<?php
/**
 * TaraMoney Checkout Block integration.
 *
 * @package OlkooPaymentOS
 * @since 1.2.1
 */

defined('ABSPATH') || exit;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;

if (!class_exists(AbstractPaymentMethodType::class)) {
    return;
}

/**
 * Class Olkoo_TaraMoney_Blocks_Payment_Method
 */
final class Olkoo_TaraMoney_Blocks_Payment_Method extends AbstractPaymentMethodType {
    /**
     * Payment method name. Must match the WooCommerce gateway ID and JS registration name.
     *
     * @var string
     */
    protected $name = 'taramoney';

    /**
     * Gateway settings.
     *
     * @var array
     */
    protected $settings = array();

    /**
     * Initialize settings.
     */
    public function initialize() {
        $this->settings = get_option('woocommerce_taramoney_settings', array());
    }

    /**
     * Check whether the payment method should be available in Checkout Block.
     *
     * @return bool
     */
    public function is_active() {
        return isset($this->settings['enabled']) && 'yes' === $this->settings['enabled'];
    }

    /**
     * Register frontend script handles.
     *
     * @return array
     */
    public function get_payment_method_script_handles() {
        wp_register_script(
            'olkoo-taramoney-blocks',
            OLKOO_PAYMENT_OS_PLUGIN_URL . 'assets/js/taramoney-blocks.js',
            array('wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-html-entities'),
            OLKOO_PAYMENT_OS_VERSION,
            true
        );

        return array('olkoo-taramoney-blocks');
    }

    /**
     * Register editor script handles.
     *
     * @return array
     */
    public function get_payment_method_script_handles_for_admin() {
        return $this->get_payment_method_script_handles();
    }

    /**
     * Provide data to the Checkout Block JavaScript registration.
     *
     * @return array
     */
    public function get_payment_method_data() {
        return array(
            'title'       => isset($this->settings['title']) && $this->settings['title'] ? $this->settings['title'] : __('TaraMoney', 'olkoo-payment-os'),
            'description' => isset($this->settings['description']) ? $this->settings['description'] : '',
            'icon'        => OLKOO_PAYMENT_OS_PLUGIN_URL . 'assets/images/taramoney-logo.jpg',
            'supports'    => array('products'),
        );
    }
}

add_action(
    'woocommerce_blocks_payment_method_type_registration',
    function (PaymentMethodRegistry $payment_method_registry) {
        $payment_method_registry->register(new Olkoo_TaraMoney_Blocks_Payment_Method());
    }
);
