<?php
/**
 * Plugin Name: WC Footer Logos (Woo + Elementor)
 * Description: Toon betaal/verzend/keurmerk logo's via shortcode en Elementor widget, incl. provider-indicator en handmatige logo's.
 * Version: 1.0.3
 * Author: Cees-Jan Slabbekoorn (Positie1)
 * Text Domain: wc-footer-logos
 */

if (!defined('ABSPATH')) exit;

define('WCFL_PATH', plugin_dir_path(__FILE__));
define('WCFL_URL', plugin_dir_url(__FILE__));
define('WCFL_VERSION', '1.0.3');

require_once WCFL_PATH . 'includes/class-wcfl-plugin.php';

add_action('plugins_loaded', function () {
    WCFL_Plugin::instance();
});
