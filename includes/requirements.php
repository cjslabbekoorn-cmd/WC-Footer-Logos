<?php
if (!defined('ABSPATH')) exit;

/**
 * Requirements checks + safe bootstrap.
 * Keeps frontend stable even if WooCommerce/Elementor are missing or outdated.
 */

function wcfl_requirements_bootstrap() {

    // PHP check (mostly relevant for activation, but keep as runtime guard too)
    if (version_compare(PHP_VERSION, WCFL_MIN_PHP, '<')) {
        add_action('admin_notices', function () {
            if (!current_user_can('activate_plugins')) return;
            echo '<div class="notice notice-error"><p><strong>WC Footer Logos</strong>: PHP ' . esc_html(WCFL_MIN_PHP) . '+ required.</p></div>';
        });
        return;
    }

    // WordPress check
    global $wp_version;
    if (isset($wp_version) && version_compare($wp_version, WCFL_MIN_WP, '<')) {
        add_action('admin_notices', function () use ($wp_version) {
            if (!current_user_can('activate_plugins')) return;
            echo '<div class="notice notice-error"><p><strong>WC Footer Logos</strong>: WordPress ' . esc_html(WCFL_MIN_WP) . '+ required. You are running ' . esc_html($wp_version) . '.</p></div>';
        });
        return;
    }

    // WooCommerce is required for gateway icons
    if (!class_exists('WooCommerce') || !function_exists('WC')) {
        add_action('admin_notices', function () {
            if (!current_user_can('activate_plugins')) return;
            echo '<div class="notice notice-warning"><p><strong>WC Footer Logos</strong>: WooCommerce is not active. Payment icons will not render until WooCommerce is activated.</p></div>';
        });
        // Still load shortcode/widget files: they already hard-fail gracefully (output empty).
        // But we can return here to avoid unnecessary includes if your main file doesn't guard.
        // We do NOT return; we still allow manual logos + provider label/manual.
    }

    // Load plugin core (existing includes)
    // Note: main plugin file should already include these; this is an extra safety net.
}

/**
 * Activation guard (wp_die if unsupported).
 */
function wcfl_activation_check() {
    global $wp_version;

    if (version_compare(PHP_VERSION, WCFL_MIN_PHP, '<')) {
        wp_die(
            esc_html('WC Footer Logos requires PHP ' . WCFL_MIN_PHP . ' or higher.'),
            esc_html('Plugin activation failed'),
            ['back_link' => true]
        );
    }

    if (isset($wp_version) && version_compare($wp_version, WCFL_MIN_WP, '<')) {
        wp_die(
            esc_html('WC Footer Logos requires WordPress ' . WCFL_MIN_WP . ' or higher.'),
            esc_html('Plugin activation failed'),
            ['back_link' => true]
        );
    }
}
