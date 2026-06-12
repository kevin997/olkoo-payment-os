<?php
/**
 * GitHub release updater.
 *
 * Allows WordPress to discover plugin updates from the public GitHub release
 * assets instead of the WordPress.org plugin directory.
 *
 * @package OlkooPaymentOS
 * @since 1.2.0
 */

defined('ABSPATH') || exit;

/**
 * Class Olkoo_Payment_OS_Updater
 */
class Olkoo_Payment_OS_Updater {
    /**
     * GitHub repository owner.
     */
    const REPO_OWNER = 'kevin997';

    /**
     * GitHub repository name.
     */
    const REPO_NAME = 'olkoo-payment-os';

    /**
     * Release cache key.
     */
    const CACHE_KEY = 'olkoo_payment_os_github_release';

    /**
     * Constructor.
     */
    public function __construct() {
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_update'));
        add_filter('plugins_api', array($this, 'plugin_information'), 10, 3);
    }

    /**
     * Add update data to the WordPress plugin update transient.
     *
     * @param object $transient Plugin update transient.
     * @return object
     */
    public function check_for_update($transient) {
        if (empty($transient->checked) || !isset($transient->checked[OLKOO_PAYMENT_OS_PLUGIN_BASENAME])) {
            return $transient;
        }

        $release = $this->get_latest_release();

        if (!$release || !version_compare($release['version'], OLKOO_PAYMENT_OS_VERSION, '>')) {
            return $transient;
        }

        if (empty($transient->response) || !is_array($transient->response)) {
            $transient->response = array();
        }

        $transient->response[OLKOO_PAYMENT_OS_PLUGIN_BASENAME] = (object) array(
            'id'            => OLKOO_PAYMENT_OS_PLUGIN_BASENAME,
            'slug'          => self::REPO_NAME,
            'plugin'        => OLKOO_PAYMENT_OS_PLUGIN_BASENAME,
            'new_version'   => $release['version'],
            'url'           => $release['html_url'],
            'package'       => $release['download_url'],
            'requires'      => '5.8',
            'requires_php'  => '7.4',
            'tested'        => '8.5',
        );

        return $transient;
    }

    /**
     * Provide release details in the WordPress plugin modal.
     *
     * @param false|object|array $result Existing result.
     * @param string             $action API action.
     * @param object             $args Plugin information arguments.
     * @return false|object|array
     */
    public function plugin_information($result, $action, $args) {
        if ('plugin_information' !== $action || empty($args->slug) || self::REPO_NAME !== $args->slug) {
            return $result;
        }

        $release = $this->get_latest_release();

        if (!$release) {
            return $result;
        }

        return (object) array(
            'name'          => 'Olkoo Payment OS',
            'slug'          => self::REPO_NAME,
            'version'       => $release['version'],
            'author'        => '<a href="https://okenlysolutions.com">Okenly Solutions</a>',
            'homepage'      => $release['html_url'],
            'download_link' => $release['download_url'],
            'requires'      => '5.8',
            'requires_php'  => '7.4',
            'tested'        => '8.5',
            'last_updated'  => $release['published_at'],
            'sections'      => array(
                'description' => 'Extensible WooCommerce payment gateway plugin supporting TaraMoney and other payment providers.',
                'changelog'   => wpautop(esc_html($release['body'] ? $release['body'] : 'See the GitHub release notes for details.')),
            ),
        );
    }

    /**
     * Get the highest semver GitHub release with a plugin ZIP asset.
     *
     * @return array|false
     */
    private function get_latest_release() {
        $cached = get_site_transient(self::CACHE_KEY);

        if (false !== $cached) {
            return $cached;
        }

        $response = wp_remote_get(
            sprintf('https://api.github.com/repos/%s/%s/releases', self::REPO_OWNER, self::REPO_NAME),
            array(
                'headers' => array(
                    'Accept'     => 'application/vnd.github+json',
                    'User-Agent' => 'Olkoo-Payment-OS/' . OLKOO_PAYMENT_OS_VERSION,
                ),
                'timeout' => 10,
            )
        );

        if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
            set_site_transient(self::CACHE_KEY, false, HOUR_IN_SECONDS);
            return false;
        }

        $releases = json_decode(wp_remote_retrieve_body($response), true);

        if (!is_array($releases)) {
            set_site_transient(self::CACHE_KEY, false, HOUR_IN_SECONDS);
            return false;
        }

        $latest = false;

        foreach ($releases as $release) {
            if (!empty($release['draft']) || !empty($release['prerelease']) || empty($release['tag_name'])) {
                continue;
            }

            $version = ltrim($release['tag_name'], 'vV');

            if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
                continue;
            }

            $download_url = $this->get_plugin_zip_url($release, $version);

            if (!$download_url) {
                continue;
            }

            if ($latest && !version_compare($version, $latest['version'], '>')) {
                continue;
            }

            $latest = array(
                'version'      => $version,
                'download_url' => $download_url,
                'html_url'     => isset($release['html_url']) ? $release['html_url'] : '',
                'body'         => isset($release['body']) ? $release['body'] : '',
                'published_at' => isset($release['published_at']) ? $release['published_at'] : '',
            );
        }

        set_site_transient(self::CACHE_KEY, $latest, 6 * HOUR_IN_SECONDS);

        return $latest;
    }

    /**
     * Find the plugin ZIP asset URL for a release.
     *
     * @param array  $release GitHub release data.
     * @param string $version Release version.
     * @return string|false
     */
    private function get_plugin_zip_url($release, $version) {
        if (empty($release['assets']) || !is_array($release['assets'])) {
            return false;
        }

        $expected_name = sprintf('%s-%s.zip', self::REPO_NAME, $version);

        foreach ($release['assets'] as $asset) {
            if (empty($asset['name']) || empty($asset['browser_download_url'])) {
                continue;
            }

            if ($expected_name === $asset['name']) {
                return $asset['browser_download_url'];
            }
        }

        foreach ($release['assets'] as $asset) {
            if (empty($asset['name']) || empty($asset['browser_download_url'])) {
                continue;
            }

            if (preg_match('/\.zip$/', $asset['name']) && false !== strpos($asset['name'], self::REPO_NAME)) {
                return $asset['browser_download_url'];
            }
        }

        return false;
    }
}
