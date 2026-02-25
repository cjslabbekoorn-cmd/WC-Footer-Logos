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

        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        // Elementor widget laden (als Elementor actief is)
        add_action('elementor/widgets/register', [$this, 'register_elementor_widget']);

        // Defaults uitbreidbaar maken via filters
        add_filter('wcfl_default_shipping_logos', [$this, 'default_shipping_logos']);
        add_filter('wcfl_default_trust_logos', [$this, 'default_trust_logos']);
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

    public function default_shipping_logos($logos) {
        return [
            'postnl' => [
                'url'   => 'https://autishop.eu/wp-content/uploads/2025/06/postnl-2-1.svg',
                'label' => 'PostNL',
                'size'  => '40px',
            ],
            'dhl' => [
                'url'   => 'https://autishop.eu/wp-content/uploads/2022/01/dhl-logo.svg',
                'label' => 'DHL',
                'size'  => '40px',
            ],
        ];
    }

    public function default_trust_logos($logos) {
        return [
            'webwinkelkeur' => [
                'url'   => 'https://autishop.eu/wp-content/uploads/2025/11/logo-webwinkelkeur-groot.webp',
                'label' => 'WebwinkelKeur',
                'size'  => '40px',
            ],
        ];
    }
}
