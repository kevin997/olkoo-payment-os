<?php
/**
 * Payment Logger Class
 *
 * Handles logging for payment gateway operations
 *
 * @package OlkooPaymentOS
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

/**
 * Class Olkoo_Payment_Logger
 */
class Olkoo_Payment_Logger {
    /**
     * Gateway ID for logging context
     *
     * @var string
     */
    private $gateway_id;

    /**
     * WooCommerce logger instance
     *
     * @var WC_Logger
     */
    private $logger;

    /**
     * Constructor
     *
     * @param string $gateway_id Gateway identifier
     */
    public function __construct($gateway_id) {
        $this->gateway_id = $gateway_id;
        $this->logger = wc_get_logger();
    }

    /**
     * Log debug message
     *
     * @param string $message Log message
     * @param array $context Additional context data
     */
    public function debug($message, $context = array()) {
        $this->log('debug', $message, $context);
    }

    /**
     * Log info message
     *
     * @param string $message Log message
     * @param array $context Additional context data
     */
    public function info($message, $context = array()) {
        $this->log('info', $message, $context);
    }

    /**
     * Log warning message
     *
     * @param string $message Log message
     * @param array $context Additional context data
     */
    public function warning($message, $context = array()) {
        $this->log('warning', $message, $context);
    }

    /**
     * Log error message
     *
     * @param string $message Log message
     * @param array $context Additional context data
     */
    public function error($message, $context = array()) {
        $this->log('error', $message, $context);
    }

    /**
     * Log critical message
     *
     * @param string $message Log message
     * @param array $context Additional context data
     */
    public function critical($message, $context = array()) {
        $this->log('critical', $message, $context);
    }

    /**
     * Generic log method
     *
     * @param string $level Log level
     * @param string $message Log message
     * @param array $context Additional context data
     */
    private function log($level, $message, $context = array()) {
        $log_entry = sprintf(
            '[%s] %s',
            strtoupper($this->gateway_id),
            $message
        );

        if (!empty($context)) {
            $log_entry .= ' | Context: ' . wp_json_encode($context);
        }

        $this->logger->log($level, $log_entry, array(
            'source' => 'olkoo-payment-os-' . $this->gateway_id,
        ));
    }
}
