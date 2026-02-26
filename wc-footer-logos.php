<?php
/**
 * Plugin Name: WC Footer Logos (Woo + Elementor)
 * Description: Toon betaal/verzend/keurmerk logo's via shortcode en Elementor widget, incl. provider-indicator en handmatige logo's.
 * Requires at least: 6.0
 * Tested up to: 6.5
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Author: Positie1 / Cees-Jan Slabbekoorn
 * Author URI: https://positie1.nl
 * Version: 1.4.7
 * Text Domain: wc-footer-logos
 */

if (!defined('ABSPATH')) {
  exit;
}

/**
 * Plugin constants
 */
define('WCFL_PLUGIN_FILE', __FILE__);
define('WCFL_PATH', plugin_dir_path(__FILE__));
define('WCFL_URL', plugin_dir_url(__FILE__));

/**
 * IMPORTANT: keep this in sync with the plugin header Version.
 * (Updater + cache + debug uses this)
 */
define('WCFL_VERSION', '1.4.7');

/**
 * Compatibility minimums
 */
define('WCFL_MIN_WP', '6.0');
define('WCFL_MIN_PHP', '7.4');
define('WCFL_MIN_WC', '6.0');
define('WCFL_MIN_ELEMENTOR', '3.0.0');

/**
 * GitHub updater config
 * Repo should be public for easiest setup.
 */
define('WCFL_GH_OWNER', 'cjslabbekoorn-cmd');
define('WCFL_GH_REPO', 'WC-Footer-Logos');
define('WCFL_PLUGIN_SLUG', 'wc-footer-logos'); // folder slug in wp-content/plugins/
define('WCFL_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('WCFL_GH_API_BASE', 'https://api.github.com');
// Optional: for private repos
// define('WCFL_GH_TOKEN', 'ghp_xxx');

/**
 * Requirements / activation checks
 */
require_once WCFL_PATH . 'includes/requirements.php';
register_activation_hook(__FILE__, 'wcfl_activation_check');

// Requirements bootstrap early (so plugin doesn't run if deps missing)
add_action('plugins_loaded', 'wcfl_requirements_bootstrap', 1);

/**
 * GitHub auto-updater
 * Loads regardless of Elementor usage.
 */
add_action('plugins_loaded', function () {
  $updater_file = WCFL_PATH . 'includes/class-wcfl-github-updater.php';
  if (file_exists($updater_file)) {
    require_once $updater_file;

    $token = defined('WCFL_GH_TOKEN') ? WCFL_GH_TOKEN : null;

    new WCFL_GitHub_Updater([
      'owner'           => WCFL_GH_OWNER,
      'repo'            => WCFL_GH_REPO,
      'plugin_file'     => WCFL_PLUGIN_FILE,
      'plugin_basename' => WCFL_PLUGIN_BASENAME,
      'plugin_slug'     => WCFL_PLUGIN_SLUG,
      'api_base'        => WCFL_GH_API_BASE,
      'token'           => $token,
    ]);
  }
}, 2);

/**
 * Main plugin bootstrap (only after requirements)
 */
require_once WCFL_PATH . 'includes/class-wcfl-plugin.php';

add_action('plugins_loaded', function () {
  // If your requirements.php sets a flag/constant when requirements fail,
  // you can optionally early-return here. If not needed, keep as-is.
  if (class_exists('WCFL_Plugin')) {
    WCFL_Plugin::instance();
  }
}, 20);
