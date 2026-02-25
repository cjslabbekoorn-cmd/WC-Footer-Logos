<?php
if (!defined('ABSPATH')) exit;

final class WCFL_Plugin {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        require_once WCFL_PATH . 'includes/class-wcfl-provider.php';
        require_once WCFL_PATH . 'includes/shortcode.php';
        require_once WCFL_PATH . 'includes/class-wcfl-updater.php';

        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        // Elementor widget laden (als Elementor actief is)
        add_action('elementor/widgets/register', [$this, 'register_elementor_widget']);
    

        // GitHub updater (pas owner/repo aan naar jouw repo)
        if (is_admin()) {
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

    public function enqueue_assets() {
        wp_enqueue_style('wcfl');
        // Script wordt conditioneel ge-enqueued door shortcode/widget wanneer lazy = yes
    }

    public function register_elementor_widget($widgets_manager) {
        if (!did_action('elementor/loaded')) return;

        require_once WCFL_PATH . 'includes/elementor/class-wcfl-elementor-widget.php';
        $widgets_manager->register(new \WCFL_Elementor_Widget());
    }
}
