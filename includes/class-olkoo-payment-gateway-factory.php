<?php
/**
 * Payment Gateway Factory Class
 *
 * Factory class for creating payment gateway instances
 *
 * @package OlkooPaymentOS
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

/**
 * Class Olkoo_Payment_Gateway_Factory
 */
class Olkoo_Payment_Gateway_Factory {
    /**
     * Registered gateway classes
     *
     * @var array
     */
    private static $gateways = array();

    /**
     * Register a gateway class
     *
     * @param string $gateway_id Gateway identifier
     * @param string $class_name Gateway class name
     */
    public static function register_gateway($gateway_id, $class_name) {
        if (class_exists($class_name)) {
            self::$gateways[$gateway_id] = $class_name;
        }
    }

    /**
     * Create gateway instance
     *
     * @param string $gateway_id Gateway identifier
     * @return Abstract_Olkoo_Payment_Gateway|null Gateway instance or null if not found
     */
    public static function create_gateway($gateway_id) {
        if (!isset(self::$gateways[$gateway_id])) {
            return null;
        }

        $class_name = self::$gateways[$gateway_id];

        if (!class_exists($class_name)) {
            return null;
        }

        return new $class_name();
    }

    /**
     * Get all registered gateways
     *
     * @return array Array of gateway IDs and class names
     */
    public static function get_registered_gateways() {
        return self::$gateways;
    }

    /**
     * Check if gateway is registered
     *
     * @param string $gateway_id Gateway identifier
     * @return bool True if registered, false otherwise
     */
    public static function is_gateway_registered($gateway_id) {
        return isset(self::$gateways[$gateway_id]);
    }

    /**
     * Unregister a gateway
     *
     * @param string $gateway_id Gateway identifier
     */
    public static function unregister_gateway($gateway_id) {
        if (isset(self::$gateways[$gateway_id])) {
            unset(self::$gateways[$gateway_id]);
        }
    }
}

// Register default gateways
Olkoo_Payment_Gateway_Factory::register_gateway('taramoney', 'Olkoo_Gateway_TaraMoney');
