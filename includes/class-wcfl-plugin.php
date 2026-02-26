<?php
if (!defined('ABSPATH')) exit;

final class WCFL_Plugin {
    private static $instance = null;
    private static $did_enqueue = false;
    private static $did_enqueue_lazy = false;

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        require_once WCFL_PATH . 'includes/class-wcfl-provider.php';
        require_once WCFL_PATH . 'includes/cache.php';
        require_once WCFL_PATH . 'includes/shortcode.php';
        require_once WCFL_PATH . 'includes/class-wcfl-updater.php';

        add_action('wp_enqueue_scripts', [$this, 'register_assets']);

        // Cache bust wanneer WC payment instellingen wijzigen
        add_action('updated_option', [$this, 'maybe_bust_cache'], 10, 3);

        // Elementor widget laden (als Elementor actief is)
        add_action('elementor/widgets/register', [$this, 'register_elementor_widget']);
    

        // GitHub updater (pas owner/repo aan naar jouw repo)
        if (is_admin()) {
            require_once WCFL_PATH . 'includes/admin.php';
            new WCFL_GitHub_Updater([
                'plugin_file'     => WCFL_PATH . 'wc-footer-logos.php',
                'plugin_slug'     => 'wc-footer-logos',
                'repo_owner'      => 'cjslabbekoorn-cmd',
                'repo_name'       => 'WC-Footer-Logos',
                'current_version' => WCFL_VERSION,
                // 'token'        => 'GITHUB_TOKEN_HIER', // alleen nodig bij private repo
            ]);
        }
    }


    public function register_assets() {
        wp_register_style('wcfl', WCFL_URL . 'assets/css/wcfl.css', [], WCFL_VERSION);
        wp_register_script('wcfl-lazy', WCFL_URL . 'assets/js/wcfl-lazy.js', [], WCFL_VERSION, true);
    }


    /**
     * Enqueue assets alleen wanneer shortcode/widget daadwerkelijk rendert.
     */
    public static function enqueue_frontend_assets(bool $needs_lazy = false): void {
        if (!self::$did_enqueue) {
            wp_enqueue_style('wcfl');
            self::$did_enqueue = true;
        }
        if ($needs_lazy && !self::$did_enqueue_lazy) {
            wp_enqueue_script('wcfl-lazy');
            self::$did_enqueue_lazy = true;
        }
    }

    /**
     * Bust cache bij relevante WooCommerce gateway settings updates.
     */
    public function maybe_bust_cache($option_name, $old_value, $value): void {
        if (!is_string($option_name)) return;
        // WooCommerce gateway settings options volgen vaak: woocommerce_{gateway_id}_settings
        if (strpos($option_name, 'woocommerce_') === 0 && substr($option_name, -9) === '_settings') {
            $bust = (int) get_option('wcfl_cache_bust', 1);
            update_option('wcfl_cache_bust', $bust + 1, false);
        }
    }

    public function register_elementor_widget($widgets_manager) {
        if (!did_action('elementor/loaded')) return;

        require_once WCFL_PATH . 'includes/elementor/class-wcfl-elementor-widget.php';
        $widgets_manager->register(new \WCFL_Elementor_Widget());
    }
}
