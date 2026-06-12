<?php
/**
 * Payment API Client Class
 *
 * Handles HTTP requests to payment gateway APIs
 *
 * @package OlkooPaymentOS
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

/**
 * Class Olkoo_Payment_API_Client
 */
class Olkoo_Payment_API_Client {
    /**
     * Base API URL
     *
     * @var string
     */
    private $base_url;

    /**
     * API headers
     *
     * @var array
     */
    private $headers;

    /**
     * Logger instance
     *
     * @var Olkoo_Payment_Logger
     */
    private $logger;

    /**
     * Request timeout in seconds
     *
     * @var int
     */
    private $timeout;

    /**
     * Constructor
     *
     * @param string $base_url Base API URL
     * @param array $headers HTTP headers
     * @param Olkoo_Payment_Logger|null $logger Logger instance
     * @param int $timeout Request timeout in seconds
     */
    public function __construct($base_url, $headers = array(), $logger = null, $timeout = 30) {
        $this->base_url = trailingslashit($base_url);
        $this->headers = $headers;
        $this->logger = $logger;
        $this->timeout = $timeout;
    }

    /**
     * Make a GET request
     *
     * @param string $endpoint API endpoint
     * @param array $params Query parameters
     * @return array|WP_Error Response array or WP_Error on failure
     */
    public function get($endpoint, $params = array()) {
        $url = $this->build_url($endpoint, $params);

        return $this->request('GET', $url);
    }

    /**
     * Make a POST request
     *
     * @param string $endpoint API endpoint
     * @param array $data Request body data
     * @return array|WP_Error Response array or WP_Error on failure
     */
    public function post($endpoint, $data = array()) {
        $url = $this->build_url($endpoint);

        return $this->request('POST', $url, $data);
    }

    /**
     * Make a PUT request
     *
     * @param string $endpoint API endpoint
     * @param array $data Request body data
     * @return array|WP_Error Response array or WP_Error on failure
     */
    public function put($endpoint, $data = array()) {
        $url = $this->build_url($endpoint);

        return $this->request('PUT', $url, $data);
    }

    /**
     * Make a DELETE request
     *
     * @param string $endpoint API endpoint
     * @return array|WP_Error Response array or WP_Error on failure
     */
    public function delete($endpoint) {
        $url = $this->build_url($endpoint);

        return $this->request('DELETE', $url);
    }

    /**
     * Make HTTP request
     *
     * @param string $method HTTP method
     * @param string $url Request URL
     * @param array $body Request body
     * @return array|WP_Error Response array or WP_Error on failure
     */
    private function request($method, $url, $body = array()) {
        $args = array(
            'method' => $method,
            'headers' => array_merge(
                array(
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ),
                $this->headers
            ),
            'timeout' => $this->timeout,
            'sslverify' => true,
        );

        if (!empty($body)) {
            $args['body'] = wp_json_encode($body);
        }

        $this->log_request($method, $url, $body);

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            $this->log_error('Request failed', array(
                'url' => $url,
                'error' => $response->get_error_message(),
            ));
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $decoded_body = json_decode($response_body, true);

        $this->log_response($status_code, $decoded_body);

        if ($status_code < 200 || $status_code >= 300) {
            return new WP_Error(
                'api_error',
                sprintf('API request failed with status %d', $status_code),
                array(
                    'status_code' => $status_code,
                    'response_body' => $decoded_body,
                )
            );
        }

        return array(
            'success' => true,
            'status_code' => $status_code,
            'data' => $decoded_body,
        );
    }

    /**
     * Build full URL from endpoint and parameters
     *
     * @param string $endpoint API endpoint
     * @param array $params Query parameters
     * @return string Full URL
     */
    private function build_url($endpoint, $params = array()) {
        $url = $this->base_url . ltrim($endpoint, '/');

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        return $url;
    }

    /**
     * Log API request
     *
     * @param string $method HTTP method
     * @param string $url Request URL
     * @param array $body Request body
     */
    private function log_request($method, $url, $body) {
        if ($this->logger) {
            $this->logger->debug('API Request', array(
                'method' => $method,
                'url' => $url,
                'body' => $this->sanitize_log_data($body),
            ));
        }
    }

    /**
     * Log API response
     *
     * @param int $status_code HTTP status code
     * @param mixed $body Response body
     */
    private function log_response($status_code, $body) {
        if ($this->logger) {
            $this->logger->debug('API Response', array(
                'status_code' => $status_code,
                'body' => $this->sanitize_log_data($body),
            ));
        }
    }

    /**
     * Log API error
     *
     * @param string $message Error message
     * @param array $context Error context
     */
    private function log_error($message, $context) {
        if ($this->logger) {
            $this->logger->error($message, $context);
        }
    }

    /**
     * Sanitize sensitive data before logging
     *
     * @param mixed $data Data to sanitize
     * @return mixed Sanitized data
     */
    private function sanitize_log_data($data) {
        if (!is_array($data)) {
            return $data;
        }

        $sensitive_keys = array('apiKey', 'api_key', 'secret', 'password', 'token');

        foreach ($sensitive_keys as $key) {
            if (isset($data[$key])) {
                $data[$key] = substr($data[$key], 0, 4) . '...';
            }
        }

        return $data;
    }
}
