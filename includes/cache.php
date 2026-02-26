<?php
if (!defined('ABSPATH')) exit;

/**
 * Cache utilities (transients + options).
 */

function wcfl_clear_all_cache(): int {
    global $wpdb;

    $deleted = 0;

    // Bust option
    delete_option('wcfl_cache_bust');

    // Delete transients by prefix
    $prefix = 'wcfl_pay_items_';

    // Normal transients
    $like = $wpdb->esc_like('_transient_' . $prefix) . '%';
    $sql = $wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like);
    $deleted += (int) $wpdb->query($sql);

    // Timeout rows
    $like_t = $wpdb->esc_like('_transient_timeout_' . $prefix) . '%';
    $sql_t = $wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like_t);
    $deleted += (int) $wpdb->query($sql_t);

    // Site transients (multisite)
    if (is_multisite()) {
        $like_s = $wpdb->esc_like('_site_transient_' . $prefix) . '%';
        $sql_s = $wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like_s);
        $deleted += (int) $wpdb->query($sql_s);

        $like_st = $wpdb->esc_like('_site_transient_timeout_' . $prefix) . '%';
        $sql_st = $wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like_st);
        $deleted += (int) $wpdb->query($sql_st);
    }

    return $deleted;
}
