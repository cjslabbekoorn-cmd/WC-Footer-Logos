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

        $this->add_control('groups', [
            'label' => __('Groups', 'wc-footer-logos'),
            'type' => Controls_Manager::SELECT2,
            'multiple' => true,
            'options' => [
                'provider' => __('Provider label', 'wc-footer-logos'),
                'payment'  => __('Payment', 'wc-footer-logos'),
                'shipping' => __('Shipping', 'wc-footer-logos'),
                'trust'    => __('Trust', 'wc-footer-logos'),
                'manual'   => __('Manual logos', 'wc-footer-logos'),
            ],
            'default' => ['payment','shipping','trust'],
        ]);

        $this->add_control('size', [
            'label' => __('Default logo size', 'wc-footer-logos'),
            'type' => Controls_Manager::TEXT,
            'default' => '40px',
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
            'label' => __('Lazy-load (shipping/trust/manual)', 'wc-footer-logos'),
            'type' => Controls_Manager::SWITCHER,
            'label_on' => 'Yes',
            'label_off' => 'No',
            'return_value' => 'yes',
            'default' => 'yes',
        ]);

        // Provider label controls
        $this->add_control('provider_mode', [
            'label' => __('Provider mode', 'wc-footer-logos'),
            'type' => Controls_Manager::SELECT,
            'options' => [
                'auto' => __('Auto-detect from enabled gateways', 'wc-footer-logos'),
                'manual' => __('Manual label', 'wc-footer-logos'),
            ],
            'default' => 'auto',
        ]);

        $this->add_control('provider_label', [
            'label' => __('Manual provider label', 'wc-footer-logos'),
            'type' => Controls_Manager::TEXT,
            'default' => '',
            'condition' => [
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

        // Manual logos repeater
        $repeater = new Repeater();

        $repeater->add_control('type', [
            'label' => __('Type', 'wc-footer-logos'),
            'type' => Controls_Manager::SELECT,
            'options' => [
                'manual' => __('Manual', 'wc-footer-logos'),
                'shipping' => __('Shipping', 'wc-footer-logos'),
                'trust' => __('Trust', 'wc-footer-logos'),
            ],
            'default' => 'manual',
        ]);

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
            'label' => __('Size override (optional)', 'wc-footer-logos'),
            'type' => Controls_Manager::TEXT,
            'default' => '',
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
            'size'            => $s['size'] ?: '40px',
            'class'           => '',
            'layout'          => $s['layout'] ?: 'row',
            'columns'         => (int)($s['columns'] ?: 6),
            'lazy'            => ($s['lazy'] === 'yes') ? 'yes' : 'no',
            'provider_mode'   => $s['provider_mode'] ?: 'auto',
            'provider_label'  => $s['provider_label'] ?? '',
            'provider_filter' => $s['provider_filter'] ?: 'auto',
            'show_provider'   => 'no',
        ];

        $groups = is_array($s['groups']) ? $s['groups'] : [];
        $items  = [];

        if (in_array('provider', $groups, true)) {
            $atts['show_provider'] = 'yes';
            $badge = wcfl_provider_badge_html($atts);
            if ($badge) {
                $items[] = '<div class="wcfl__item wcfl__item--provider">' . $badge . '</div>';
            }
        }

        if (in_array('payment', $groups, true)) {
            $items = array_merge($items, wcfl_collect_payment_logo_items($atts));
        }

        if (in_array('shipping', $groups, true)) {
            $items = array_merge($items, wcfl_build_logo_items_from_array(wcfl_get_shipping_logos(), $atts, 'shipping'));
        }

        if (in_array('trust', $groups, true)) {
            $items = array_merge($items, wcfl_build_logo_items_from_array(wcfl_get_trust_logos(), $atts, 'trust'));
        }

        if (in_array('manual', $groups, true) && !empty($s['manual_logos'])) {
            $logos_by_type = [
                'manual' => [],
                'shipping' => [],
                'trust' => [],
            ];

            foreach ($s['manual_logos'] as $i => $row) {
                if (empty($row['url'])) continue;

                $type = !empty($row['type']) ? $row['type'] : 'manual';
                if (!isset($logos_by_type[$type])) $type = 'manual';

                $logos_by_type[$type]['manual_' . $i] = [
                    'url'   => $row['url'],
                    'label' => $row['label'] ?: 'Logo',
                    'size'  => $row['size'] ?: '',
                ];
            }

            foreach ($logos_by_type as $type => $logos) {
                if (!empty($logos)) {
                    $items = array_merge($items, wcfl_build_logo_items_from_array($logos, $atts, $type));
                }
            }
        }

        if ($atts['lazy'] === 'yes') {
            wp_enqueue_script('wcfl-lazy');
        }

        echo wcfl_build_wrapper_html($items, $atts);
    }
}
