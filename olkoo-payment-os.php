<?php
/**
 * Plugin Name: Olkoo Payment OS
 * Plugin URI: https://okenlysolutions.com/olkoo-payment-os
 * Description: Extensible payment gateway plugin for WooCommerce supporting TaraMoney and other payment providers
 * Version: 1.2.0
 * Author: Okenly Solutions
 * Author URI: https://okenlysolutions.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: olkoo-payment-os
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.5
 *
 * @package OlkooPaymentOS
 */

defined('ABSPATH') || exit;

// Define plugin constants
define('OLKOO_PAYMENT_OS_VERSION', '1.2.0');
define('OLKOO_PAYMENT_OS_PLUGIN_FILE', __FILE__);
define('OLKOO_PAYMENT_OS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('OLKOO_PAYMENT_OS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('OLKOO_PAYMENT_OS_PLUGIN_BASENAME', plugin_basename(__FILE__));

require_once OLKOO_PAYMENT_OS_PLUGIN_DIR . 'includes/class-olkoo-payment-updater.php';
new Olkoo_Payment_OS_Updater();

/**
 * Main plugin class
 */
class Olkoo_Payment_OS {
    /**
     * The single instance of the class
     *
     * @var Olkoo_Payment_OS
     */
    protected static $_instance = null;

    /**
     * Main Olkoo Payment OS Instance
     *
     * @static
     * @return Olkoo_Payment_OS - Main instance
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Hook into actions and filters
     */
    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'init'), 0);
        add_filter('woocommerce_payment_gateways', array($this, 'add_gateways'));
        add_filter('plugin_action_links_' . OLKOO_PAYMENT_OS_PLUGIN_BASENAME, array($this, 'plugin_action_links'));
    }

    /**
     * Initialize the plugin
     */
    public function init() {
        // Check if WooCommerce is active
        if (!$this->is_woocommerce_active()) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }

        // Load plugin textdomain
        load_plugin_textdomain('olkoo-payment-os', false, dirname(OLKOO_PAYMENT_OS_PLUGIN_BASENAME) . '/languages');

        // Include required files
        $this->includes();

        // Initialize admin if in admin area
        if (is_admin()) {
            $this->admin_includes();
        }

        do_action('olkoo_payment_os_init');
    }

    /**
     * Include required core files
     */
    private function includes() {
        // Core abstracts and interfaces (interface must load before the abstract that implements it)
        require_once OLKOO_PAYMENT_OS_PLUGIN_DIR . 'includes/interfaces/interface-olkoo-payment-gateway.php';
        require_once OLKOO_PAYMENT_OS_PLUGIN_DIR . 'includes/abstracts/abstract-olkoo-payment-gateway.php';

        // Gateway factory
        require_once OLKOO_PAYMENT_OS_PLUGIN_DIR . 'includes/class-olkoo-payment-gateway-factory.php';

        // Utilities
        require_once OLKOO_PAYMENT_OS_PLUGIN_DIR . 'includes/class-olkoo-payment-logger.php';
        require_once OLKOO_PAYMENT_OS_PLUGIN_DIR . 'includes/class-olkoo-payment-api-client.php';

        // Gateway implementations
        require_once OLKOO_PAYMENT_OS_PLUGIN_DIR . 'includes/gateways/class-olkoo-gateway-taramoney.php';

        // Webhook handler
        require_once OLKOO_PAYMENT_OS_PLUGIN_DIR . 'includes/class-olkoo-payment-webhook-handler.php';
    }

    /**
     * Include required admin files
     */
    private function admin_includes() {
        require_once OLKOO_PAYMENT_OS_PLUGIN_DIR . 'includes/admin/class-olkoo-payment-admin.php';
    }

    /**
     * Add payment gateways to WooCommerce
     *
     * @param array $gateways
     * @return array
     */
    public function add_gateways($gateways) {
        $olkoo_gateways = apply_filters('olkoo_payment_os_gateways', array(
            'Olkoo_Gateway_TaraMoney',
        ));

        return array_merge($gateways, $olkoo_gateways);
    }

    /**
     * Check if WooCommerce is active
     *
     * @return bool
     */
    private function is_woocommerce_active() {
        return class_exists('WooCommerce');
    }

    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="error">
            <p>
                <?php
                echo sprintf(
                    /* translators: %s: WooCommerce plugin link */
                    esc_html__('Olkoo Payment OS requires WooCommerce to be installed and active. You can download %s here.', 'olkoo-payment-os'),
                    '<a href="https://wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce</a>'
                );
                ?>
            </p>
        </div>
        <?php
    }

    /**
     * Show action links on the plugin screen
     *
     * @param array $links
     * @return array
     */
    public function plugin_action_links($links) {
        $action_links = array(
            'settings' => '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout') . '">' . __('Settings', 'olkoo-payment-os') . '</a>',
            'docs' => '<a href="https://okenlysolutions.com/docs/olkoo-payment-os" target="_blank">' . __('Documentation', 'olkoo-payment-os') . '</a>',
        );

        return array_merge($action_links, $links);
    }

    /**
     * Get the plugin url
     *
     * @return string
     */
    public function plugin_url() {
        return OLKOO_PAYMENT_OS_PLUGIN_URL;
    }

    /**
     * Get the plugin path
     *
     * @return string
     */
    public function plugin_path() {
        return OLKOO_PAYMENT_OS_PLUGIN_DIR;
    }
}

/**
 * Main instance of Olkoo Payment OS
 *
 * @return Olkoo_Payment_OS
 */
function Olkoo_Payment_OS() {
    return Olkoo_Payment_OS::instance();
}

// Initialize the plugin
Olkoo_Payment_OS();
