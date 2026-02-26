<?php
if (!defined('ABSPATH')) exit;

require_once WCFL_PATH . 'includes/cache.php';

function wcfl_register_admin_page(): void {
    add_management_page(
        __('WC Footer Logos', 'wc-footer-logos'),
        __('WC Footer Logos', 'wc-footer-logos'),
        'manage_options',
        'wcfl-tools',
        'wcfl_render_tools_page'
    );
}
add_action('admin_menu', 'wcfl_register_admin_page');

function wcfl_render_tools_page(): void {
    if (!current_user_can('manage_options')) return;

    $cleared = isset($_GET['wcfl_cleared']) ? (int) $_GET['wcfl_cleared'] : 0;
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('WC Footer Logos', 'wc-footer-logos'); ?></h1>

        <?php if ($cleared === 1): ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo esc_html__('Cache cleared.', 'wc-footer-logos'); ?></p>
            </div>
        <?php endif; ?>

        <p><?php echo esc_html__('Use this tool to clear cached payment icon HTML.', 'wc-footer-logos'); ?></p>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('wcfl_clear_cache'); ?>
            <input type="hidden" name="action" value="wcfl_clear_cache" />
            <?php submit_button(__('Clear WCFL cache', 'wc-footer-logos'), 'primary'); ?>
        </form>
    </div>
    <?php
}

function wcfl_handle_clear_cache(): void {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Insufficient permissions.', 'wc-footer-logos'));
    }
    check_admin_referer('wcfl_clear_cache');

    wcfl_clear_all_cache();

    wp_safe_redirect(add_query_arg(['page' => 'wcfl-tools', 'wcfl_cleared' => 1], admin_url('tools.php')));
    exit;
}
add_action('admin_post_wcfl_clear_cache', 'wcfl_handle_clear_cache');
