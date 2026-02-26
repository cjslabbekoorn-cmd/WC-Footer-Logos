<?php
/**
 * Plugin Name: WC Footer Logos (Woo + Elementor)
 * Description: Toon betaal/verzend/keurmerk logo's via shortcode en Elementor widget, incl. provider-indicator en handmatige logo's.
 * Requires at least: 6.0
 * Tested up to: 6.5
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Author URI: https://positie1.nl
 * Version: 1.4.7
 * Author: Positie1 / Cees-Jan Slabbekoorn
 * Text Domain: wc-footer-logos
 */

define('WCFL_GH_OWNER', 'cjslabbekoorn-cmd');
define('WCFL_GH_REPO',  'WC-Footer-Logos');
define('WCFL_GH_BRANCH', 'main'); // niet essentieel, maar handig
define('WCFL_PLUGIN_FILE', __FILE__);
define('WCFL_PLUGIN_SLUG', 'wc-footer-logos'); // folder slug
define('WCFL_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('WCFL_GH_API_BASE', 'https://api.github.com');

if (!defined('ABSPATH')) exit;

define('WCFL_PATH', plugin_dir_path(__FILE__));
define('WCFL_URL', plugin_dir_url(__FILE__));
define('WCFL_VERSION', '1.4.2');

define('WCFL_MIN_WP', '6.0');
define('WCFL_MIN_PHP', '7.4');
define('WCFL_MIN_WC', '6.0');
define('WCFL_MIN_ELEMENTOR', '3.0.0');

require_once __DIR__ . '/includes/requirements.php';

require_once WCFL_PATH . 'includes/class-wcfl-plugin.php';

add_action('plugins_loaded', function () {
    WCFL_Plugin::instance();
});


// Requirements & bootstrap
add_action('plugins_loaded', 'wcfl_requirements_bootstrap', 1);

register_activation_hook(__FILE__, 'wcfl_activation_check');
