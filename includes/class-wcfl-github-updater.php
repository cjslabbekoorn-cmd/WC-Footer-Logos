<?php
if (!defined('ABSPATH')) exit;

class WCFL_GitHub_Updater {

  private string $owner;
  private string $repo;
  private string $plugin_file;
  private string $plugin_basename;
  private string $plugin_slug;
  private string $api_base;

  // Optioneel: als je een private repo gebruikt, voeg token toe via constant of filter
  private ?string $token;

  public function __construct(array $args = []) {
    $this->owner           = $args['owner'] ?? '';
    $this->repo            = $args['repo'] ?? '';
    $this->plugin_file     = $args['plugin_file'] ?? '';
    $this->plugin_basename = $args['plugin_basename'] ?? '';
    $this->plugin_slug     = $args['plugin_slug'] ?? '';
    $this->api_base        = $args['api_base'] ?? 'https://api.github.com';
    $this->token           = $args['token'] ?? null;

    add_filter('pre_set_site_transient_update_plugins', [$this, 'inject_update']);
    add_filter('plugins_api', [$this, 'plugins_api'], 10, 3);
    add_filter('upgrader_pre_download', [$this, 'maybe_add_auth_header'], 10, 4);
  }

  /**
   * Main update hook.
   */
  public function inject_update($transient) {
    if (!is_object($transient) || empty($transient->checked)) return $transient;
    if (empty($transient->checked[$this->plugin_basename])) return $transient;

    $current_version = $transient->checked[$this->plugin_basename];

    $release = $this->get_latest_release();
    if (!$release) return $transient;

    $remote_version = $this->normalize_version($release['tag_name'] ?? '');
    if (!$remote_version) return $transient;

    if (version_compare($remote_version, $current_version, '<=')) {
      return $transient; // up-to-date
    }

    $zip_url = $this->pick_release_asset_zip($release);
    if (!$zip_url) return $transient;

    $package = $this->maybe_add_token_to_url($zip_url);

    $item = (object) [
      'slug'        => $this->plugin_slug,
      'plugin'      => $this->plugin_basename,
      'new_version' => $remote_version,
      'url'         => $release['html_url'] ?? '',
      'package'     => $package,
      'tested'      => '', // optioneel invullen als je wilt
      'requires'    => '', // optioneel
    ];

    $transient->response[$this->plugin_basename] = $item;
    return $transient;
  }

  /**
   * Plugin info popup (“View details”)
   */
  public function plugins_api($result, $action, $args) {
    if ($action !== 'plugin_information') return $result;
    if (empty($args->slug) || $args->slug !== $this->plugin_slug) return $result;

    $release = $this->get_latest_release();
    if (!$release) return $result;

    $remote_version = $this->normalize_version($release['tag_name'] ?? '');
    $zip_url        = $this->pick_release_asset_zip($release);

    $sections = [
      'description' => $release['body'] ?? '',
      'changelog'   => $release['body'] ?? '',
    ];

    return (object) [
      'name'          => $this->repo,
      'slug'          => $this->plugin_slug,
      'version'       => $remote_version ?: '',
      'author'        => 'Positie1 / Cees-Jan Slabbekoorn',
      'homepage'      => $release['html_url'] ?? '',
      'download_link' => $zip_url ? $this->maybe_add_token_to_url($zip_url) : '',
      'sections'      => $sections,
    ];
  }

  /**
   * If private repo: WordPress downloads without headers → we inject Authorization header.
   * For public repos: not needed, harmless.
   */
  public function maybe_add_auth_header($reply, $package, $upgrader, $hook_extra) {
    if (empty($this->token)) return $reply;
    if (strpos($package, 'github.com') === false && strpos($package, 'api.github.com') === false) return $reply;

    add_filter('http_request_args', function($args, $url) {
      if (strpos($url, 'github.com') === false && strpos($url, 'api.github.com') === false) return $args;
      $args['headers']['Authorization'] = 'token ' . $this->token;
      $args['headers']['User-Agent'] = $this->repo . '-updater';
      return $args;
    }, 10, 2);

    return $reply;
  }

  /**
   * --- GitHub fetch + caching ---
   */
  private function get_latest_release(): ?array {
    $cache_key = 'wcfl_gh_release_' . md5($this->owner . '/' . $this->repo);
    $cached = get_transient($cache_key);
    if (is_array($cached)) return $cached;

    $url = trailingslashit($this->api_base) . "repos/{$this->owner}/{$this->repo}/releases/latest";

    $args = [
      'timeout' => 15,
      'headers' => [
        'Accept'     => 'application/vnd.github+json',
        'User-Agent' => $this->repo . '-updater',
      ],
    ];

    if (!empty($this->token)) {
      $args['headers']['Authorization'] = 'token ' . $this->token;
    }

    $res = wp_remote_get($url, $args);
    if (is_wp_error($res)) return null;

    $code = wp_remote_retrieve_response_code($res);
    if ($code !== 200) return null;

    $body = wp_remote_retrieve_body($res);
    $json = json_decode($body, true);
    if (!is_array($json)) return null;

    // cache 15 min (GitHub rate limits)
    set_transient($cache_key, $json, 15 * MINUTE_IN_SECONDS);
    return $json;
  }

  private function pick_release_asset_zip(array $release): ?string {
    if (empty($release['assets']) || !is_array($release['assets'])) return null;

    // voorkeur: asset die begint met plugin slug en eindigt op .zip
    foreach ($release['assets'] as $asset) {
      $name = $asset['name'] ?? '';
      $url  = $asset['browser_download_url'] ?? '';
      if (!$name || !$url) continue;

      if (preg_match('/\.zip$/i', $name) && stripos($name, $this->plugin_slug . '-') === 0) {
        return $url;
      }
    }

    // fallback: eerste zip asset
    foreach ($release['assets'] as $asset) {
      $name = $asset['name'] ?? '';
      $url  = $asset['browser_download_url'] ?? '';
      if (!$name || !$url) continue;

      if (preg_match('/\.zip$/i', $name)) {
        return $url;
      }
    }

    return null;
  }

  private function normalize_version(string $tag): string {
    $tag = trim($tag);
    // support "v1.4.7" or "1.4.7"
    $tag = ltrim($tag, "vV");
    // only semver-ish
    if (!preg_match('/^\d+\.\d+\.\d+/', $tag)) return '';
    return $tag;
  }

  private function maybe_add_token_to_url(string $url): string {
    // Voor public repo: token niet nodig.
    // Voor private repo: WordPress kan browser_download_url soms zonder headers niet ophalen.
    // Als je tóch via URL wilt: niet aanbevolen. We doen header-injectie hierboven.
    return $url;
  }
}
