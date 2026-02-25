<?php
if (!defined('ABSPATH')) exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;

class WCFL_Elementor_Widget extends Widget_Base {

    public function get_name() { return 'wcfl_footer_logos'; }
    public function get_title() { return __('WC Footer Logos', 'wc-footer-logos'); }
    public function get_icon() { return 'eicon-payment'; }
    public function get_categories() { return ['general']; }

    protected function register_controls() {

        $this->start_controls_section('section_content', [
            'label' => __('Content', 'wc-footer-logos'),
        ]);

        $this->add_control('layout', [
            'label' => __('Layout', 'wc-footer-logos'),
            'type' => Controls_Manager::SELECT,
            'options' => [
                'row' => __('Row (wrap)', 'wc-footer-logos'),
                'inline' => __('Inline', 'wc-footer-logos'),
            ],
            'default' => 'row',
        ]);

        $this->add_control('columns', [
            'label' => __('Columns', 'wc-footer-logos'),
            'type' => Controls_Manager::NUMBER,
            'min' => 1,
            'max' => 12,
            'step' => 1,
            'default' => 6,
        ]);

        $this->add_control('lazy', [
            'label' => __('Lazy-load manual logos', 'wc-footer-logos'),
            'type' => Controls_Manager::SWITCHER,
            'label_on' => 'Yes',
            'label_off' => 'No',
            'return_value' => 'yes',
            'default' => 'yes',
        ]);

        $this->add_control('show_provider', [
            'label' => __('Show provider label', 'wc-footer-logos'),
            'type' => Controls_Manager::SWITCHER,
            'label_on' => 'Yes',
            'label_off' => 'No',
            'return_value' => 'yes',
            'default' => '',
        ]);

        $this->add_control('provider_mode', [
            'label' => __('Provider mode', 'wc-footer-logos'),
            'type' => Controls_Manager::SELECT,
            'options' => [
                'auto' => __('Auto-detect from enabled gateways', 'wc-footer-logos'),
                'manual' => __('Manual label', 'wc-footer-logos'),
            ],
            'default' => 'auto',
            'condition' => [
                'show_provider' => 'yes',
            ],
        ]);

        $this->add_control('provider_label', [
            'label' => __('Manual provider label', 'wc-footer-logos'),
            'type' => Controls_Manager::TEXT,
            'default' => '',
            'condition' => [
                'show_provider' => 'yes',
                'provider_mode' => 'manual',
            ],
        ]);

        $provider_options = [];
        foreach (WCFL_Provider::provider_map() as $key => $data) {
            $provider_options[$key] = $data['label'];
        }
        $provider_options['manual'] = __('(No filter)', 'wc-footer-logos');

        $this->add_control('provider_filter', [
            'label' => __('Filter payment methods by provider (optional)', 'wc-footer-logos'),
            'type' => Controls_Manager::SELECT,
            'options' => $provider_options,
            'default' => 'auto',
        ]);

        // Manual logos repeater (alleen extra icons; shipping/trust zitten niet meer in plugin)
        $repeater = new Repeater();

        $repeater->add_control('label', [
            'label' => __('Label', 'wc-footer-logos'),
            'type' => Controls_Manager::TEXT,
            'default' => '',
        ]);

        $repeater->add_control('url', [
            'label' => __('Image URL', 'wc-footer-logos'),
            'type' => Controls_Manager::TEXT,
            'default' => '',
        ]);

        $repeater->add_control('size', [
            'label' => __('Size (optional)', 'wc-footer-logos'),
            'type' => Controls_Manager::TEXT,
            'default' => '40px',
        ]);

        $this->add_control('manual_logos', [
            'label' => __('Manual logos', 'wc-footer-logos'),
            'type' => Controls_Manager::REPEATER,
            'fields' => $repeater->get_controls(),
            'default' => [],
            'title_field' => '{{{ label }}}',
        ]);

        $this->end_controls_section();
    }

    protected function render() {
        $s = $this->get_settings_for_display();

        $atts = [
            'class'           => '',
            'layout'          => $s['layout'] ?: 'row',
            'columns'         => (int)($s['columns'] ?: 6),
            'lazy'            => ($s['lazy'] === 'yes') ? 'yes' : 'no',
            'provider_mode'   => $s['provider_mode'] ?: 'auto',
            'provider_label'  => $s['provider_label'] ?? '',
            'provider_filter' => $s['provider_filter'] ?: 'auto',
            'show_provider'   => ($s['show_provider'] === 'yes') ? 'yes' : 'no',
        ];

        $items  = [];

        if ($atts['show_provider'] === 'yes') {
            $badge = wcfl_provider_badge_html($atts);
            if ($badge) {
                $items[] = '<div class="wcfl__item wcfl__item--provider">' . $badge . '</div>';
            }
        }

        // Always payment gateways
        $items = array_merge($items, wcfl_collect_payment_logo_items($atts));

        // Manual logos (extra icons)
        if (!empty($s['manual_logos'])) {
            foreach ($s['manual_logos'] as $i => $row) {
                if (empty($row['url'])) continue;

                $url   = esc_url($row['url']);
                $label = !empty($row['label']) ? $row['label'] : 'Logo';
                $size  = !empty($row['size']) ? $row['size'] : '40px';

                if ($atts['lazy'] === 'yes') {
                    $img = '<img data-src="' . $url . '" alt="' . esc_attr($label) . '" style="max-height:' . esc_attr($size) . ';max-width:' . esc_attr($size) . ';" class="wcfl__img wcfl__img--lazy" />';
                } else {
                    $img = '<img src="' . $url . '" alt="' . esc_attr($label) . '" style="max-height:' . esc_attr($size) . ';max-width:' . esc_attr($size) . ';" class="wcfl__img" loading="lazy" decoding="async" />';
                }

                $items[] = '<div class="wcfl__item wcfl__item--manual" aria-label="' . esc_attr($label) . '" title="' . esc_attr($label) . '">' . $img . '</div>';
            }
        }

        if ($atts['lazy'] === 'yes') {
            wp_enqueue_script('wcfl-lazy');
        }

        echo wcfl_build_wrapper_html($items, $atts);
    }
}
