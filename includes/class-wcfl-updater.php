<?php
if (!defined('ABSPATH')) exit;

/**
 * Minimal GitHub Releases updater (public repos).
 *
 * Werkt zonder externe libraries: gebruikt GitHub Releases API om de nieuwste release te vinden
 * en biedt daarna een update aan in WordPress.
 *
 * Vereisten:
 * - Repo is public OF je gebruikt een token (optioneel).
 * - Je uploadt een ZIP asset naar de GitHub release (bijv. wc-footer-logos-1.0.3.zip)
 * - Tagnaam mag v1.0.3 zijn; versie wordt uit de release 'tag_name' gehaald.
 */
final class WCFL_GitHub_Updater {

    private string $plugin_file;
    private string $plugin_slug;     // directory/slug, bv wc-footer-logos
    private string $repo_owner;
    private string $repo_name;
    private string $current_version;
    private ?string $token;

    private string $cache_key;

    public function __construct(array $args) {
        $this->plugin_file     = $args['plugin_file'];
        $this->plugin_slug     = $args['plugin_slug'];
        $this->repo_owner      = $args['repo_owner'];
        $this->repo_name       = $args['repo_name'];
        $this->current_version = $args['current_version'];
        $this->token           = $args['token'] ?? null;

        $this->cache_key = 'wcfl_gh_release_' . md5($this->repo_owner . '/' . $this->repo_name);

        add_filter('pre_set_site_transient_update_plugins', [$this, 'inject_update']);
        add_filter('plugins_api', [$this, 'plugins_api'], 20, 3);
        add_filter('upgrader_pre_download', [$this, 'maybe_add_auth_header'], 10, 3);
    }

    private function api_url_latest(): string {
        return sprintf('https://api.github.com/repos/%s/%s/releases/latest', $this->repo_owner, $this->repo_name);
    }

    private function request_latest_release() {
        $cached = get_site_transient($this->cache_key);
        if ($cached) return $cached;

        $headers = [
            'Accept'     => 'application/vnd.github+json',
            'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url('/'),
        ];

        if (!empty($this->token)) {
            $headers['Authorization'] = 'Bearer ' . $this->token;
        }

        $res = wp_remote_get($this->api_url_latest(), [
            'timeout' => 12,
            'headers' => $headers,
        ]);

        if (is_wp_error($res)) return null;

        $code = wp_remote_retrieve_response_code($res);
        if ($code < 200 || $code >= 300) return null;

        $body = wp_remote_retrieve_body($res);
        $data = json_decode($body, true);

        if (!is_array($data) || empty($data['tag_name'])) return null;

        // cache 6 uur
        set_site_transient($this->cache_key, $data, 6 * HOUR_IN_SECONDS);
        return $data;
    }

    private function normalize_version(string $tag): string {
        // v1.0.3 -> 1.0.3
        return ltrim(trim($tag), "vV");
    }

    private function find_zip_asset_url(array $release): ?string {
        if (empty($release['assets']) || !is_array($release['assets'])) return null;

        // Voorkeur: asset met "wc-footer-logos" en ".zip"
        foreach ($release['assets'] as $asset) {
            if (empty($asset['browser_download_url']) || empty($asset['name'])) continue;
            $name = strtolower($asset['name']);
            if (str_contains($name, $this->plugin_slug) && str_ends_with($name, '.zip')) {
                return $asset['browser_download_url'];
            }
        }

        // Fallback: eerste .zip asset
        foreach ($release['assets'] as $asset) {
            if (empty($asset['browser_download_url']) || empty($asset['name'])) continue;
            $name = strtolower($asset['name']);
            if (str_ends_with($name, '.zip')) {
                return $asset['browser_download_url'];
            }
        }

        return null;
    }

    public function inject_update($transient) {
        if (!is_object($transient)) $transient = new stdClass();
        if (empty($transient->checked) || !is_array($transient->checked)) return $transient;

        $release = $this->request_latest_release();
        if (!$release) return $transient;

        $new_version = $this->normalize_version((string)$release['tag_name']);
        if (version_compare($new_version, $this->current_version, '<=')) return $transient;

        $package = $this->find_zip_asset_url($release);
        if (!$package) return $transient; // geen zip asset -> geen update aanbieden

        $plugin_basename = plugin_basename($this->plugin_file);

        $update = (object)[
            'slug'        => $this->plugin_slug,
            'plugin'      => $plugin_basename,
            'new_version' => $new_version,
            'url'         => $release['html_url'] ?? '',
            'package'     => $package,
        ];

        $transient->response[$plugin_basename] = $update;
        return $transient;
    }

    public function plugins_api($result, $action, $args) {
        if ($action !== 'plugin_information') return $result;
        if (empty($args->slug) || $args->slug !== $this->plugin_slug) return $result;

        $release = $this->request_latest_release();
        if (!$release) return $result;

        $new_version = $this->normalize_version((string)$release['tag_name']);

        return (object)[
            'name'          => 'WC Footer Logos',
            'slug'          => $this->plugin_slug,
            'version'       => $new_version,
            'author'        => 'Cees-Jan Slabbekoorn (Positie1)',
            'homepage'      => $release['html_url'] ?? '',
            'download_link' => $this->find_zip_asset_url($release) ?? '',
            'sections'      => [
                'description' => !empty($release['body']) ? wp_kses_post($release['body']) : 'WooCommerce betaalmethoden logo\'s via shortcode + Elementor widget.',
            ],
        ];
    }

    public function maybe_add_auth_header($reply, $package, $upgrader) {
        // Voor private repos kun je hier token-header aan download toevoegen (optioneel).
        // Voor public repos is dit niet nodig.
        return $reply;
    }
}
