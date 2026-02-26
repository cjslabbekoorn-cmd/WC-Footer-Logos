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

        // ✅ Elementor-native responsive control (1 veld met device switch)
        $this->add_responsive_control('columns', [
            'label' => __('Columns', 'wc-footer-logos'),
            'type' => Controls_Manager::NUMBER,
            'min' => 1,
            'max' => 12,
            'step' => 1,
            'default' => 6,
            'tablet_default' => 4,
            'mobile_default' => 3,
            'selectors' => [
                '{{WRAPPER}} .wcfl' => 'grid-template-columns: repeat({{VALUE}}, minmax(0,1fr)); --wcfl-cols: {{VALUE}};',
            ],
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
            'default' => 'manual',
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
            'default' => 'manual',
        ]);

        $this->add_control('lazy', [
            'label' => __('Lazy-load manual logos', 'wc-footer-logos'),
            'type' => Controls_Manager::SWITCHER,
            'label_on' => 'Yes',
            'label_off' => 'No',
            'return_value' => 'yes',
            'default' => 'yes',
        ]);

        $repeater = new Repeater();

        $repeater->add_control('label', [
            'label' => __('Label', 'wc-footer-logos'),
            'type' => Controls_Manager::TEXT,
            'default' => '',
        ]);

        // ✅ Media library selector
        $repeater->add_control('image', [
            'label' => __('Image', 'wc-footer-logos'),
            'type' => Controls_Manager::MEDIA,
            'default' => [
                'url' => '',
            ],
        ]);

        // Backward compat: oude URL veld (hidden)
        $repeater->add_control('url', [
            'label' => __('(legacy) Image URL', 'wc-footer-logos'),
            'type' => Controls_Manager::HIDDEN,
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

        
        $this->add_control('debug', [
            'label' => __('Debug (admin only)', 'wc-footer-logos'),
            'type' => Controls_Manager::SWITCHER,
            'label_on' => 'Yes',
            'label_off' => 'No',
            'return_value' => 'yes',
            'default' => '',
        ]);
$this->end_controls_section();

        // ----------------
        // Style
        // ----------------
        $this->start_controls_section('section_style', [
            'label' => __('Style', 'wc-footer-logos'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_responsive_control('align', [
            'label' => __('Alignment', 'wc-footer-logos'),
            'type' => Controls_Manager::CHOOSE,
            'options' => [
                'flex-start' => [
                    'title' => __('Left', 'wc-footer-logos'),
                    'icon'  => 'eicon-text-align-left',
                ],
                'center' => [
                    'title' => __('Center', 'wc-footer-logos'),
                    'icon'  => 'eicon-text-align-center',
                ],
                'flex-end' => [
                    'title' => __('Right', 'wc-footer-logos'),
                    'icon'  => 'eicon-text-align-right',
                ],
            ],
            'default' => 'center',
            'selectors' => [
                '{{WRAPPER}} .wcfl' => '--wcfl-align: {{VALUE}};',
            ],
        ]);

        $this->add_responsive_control('logo_width', [
            'label' => __('Width', 'wc-footer-logos'),
            'type' => Controls_Manager::SLIDER,
            'size_units' => ['%', 'px'],
            'range' => [
                '%' => ['min' => 1, 'max' => 100],
                'px' => ['min' => 10, 'max' => 400],
            ],
            'selectors' => [
                '{{WRAPPER}} .wcfl' => '--wcfl-logo-width: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_responsive_control('logo_max_width', [
            'label' => __('Max width', 'wc-footer-logos'),
            'type' => Controls_Manager::SLIDER,
            'size_units' => ['%', 'px'],
            'range' => [
                '%' => ['min' => 1, 'max' => 100],
                'px' => ['min' => 10, 'max' => 600],
            ],
            'selectors' => [
                '{{WRAPPER}} .wcfl' => '--wcfl-logo-max-width: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_responsive_control('logo_height', [
            'label' => __('Height', 'wc-footer-logos'),
            'type' => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range' => [
                'px' => ['min' => 10, 'max' => 200],
            ],
            'selectors' => [
                '{{WRAPPER}} .wcfl' => '--wcfl-logo-max-height: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_responsive_control('gap', [
            'label' => __('Gap', 'wc-footer-logos'),
            'type' => Controls_Manager::SLIDER,
            'size_units' => ['px', 'rem'],
            'range' => [
                'px' => ['min' => 0, 'max' => 60],
                'rem' => ['min' => 0, 'max' => 4],
            ],
            'selectors' => [
                '{{WRAPPER}} .wcfl' => '--wcfl-gap: {{SIZE}}{{UNIT}};',
            ],
        ]);

        
        $this->add_control('equal_height', [
            'label' => __('Equal logo height', 'wc-footer-logos'),
            'type' => Controls_Manager::SWITCHER,
            'label_on' => 'Yes',
            'label_off' => 'No',
            'return_value' => 'yes',
            'default' => '',
            'selectors' => [
                '{{WRAPPER}} .wcfl' => '--wcfl-equal-height: 1;',
            ],
        ]);

        $this->add_control('grayscale', [
            'label' => __('Grayscale logos', 'wc-footer-logos'),
            'type' => Controls_Manager::SWITCHER,
            'label_on' => 'Yes',
            'label_off' => 'No',
            'return_value' => 'yes',
            'default' => '',
            'selectors' => [
                '{{WRAPPER}} .wcfl' => '--wcfl-grayscale: 1;',
            ],
        ]);

        $this->add_control('grayscale_hover', [
            'label' => __('Remove grayscale on hover', 'wc-footer-logos'),
            'type' => Controls_Manager::SWITCHER,
            'label_on' => 'Yes',
            'label_off' => 'No',
            'return_value' => 'yes',
            'default' => 'yes',
            'condition' => [
                'grayscale' => 'yes',
            ],
            'selectors' => [
                '{{WRAPPER}} .wcfl' => '--wcfl-grayscale-hover: 1;',
            ],
        ]);
$this->end_controls_section();

    }

    private function is_elementor_editor(): bool {
        if (!class_exists('\Elementor\Plugin')) return false;
        $plugin = \Elementor\Plugin::$instance;

        // editor panel OR preview iframe
        if (isset($plugin->editor) && method_exists($plugin->editor, 'is_edit_mode') && $plugin->editor->is_edit_mode()) {
            return true;
        }
        if (isset($plugin->preview) && method_exists($plugin->preview, 'is_preview_mode') && $plugin->preview->is_preview_mode()) {
            return true;
        }
        return false;
    }

    private function get_manual_logo_url(array $row): string {
        if (!empty($row['image']) && is_array($row['image'])) {
            $id  = !empty($row['image']['id']) ? (int)$row['image']['id'] : 0;
            $url = !empty($row['image']['url']) ? (string)$row['image']['url'] : '';

            if ($id > 0) {
                $img = wp_get_attachment_image_url($id, 'full');
                if ($img) return (string)$img;
            }
            if (!empty($url)) return $url;
        }

        if (!empty($row['url'])) return (string)$row['url'];

        return '';
    }

    protected function render() {
        $s = $this->get_settings_for_display();

        if (class_exists('WCFL_Plugin')) {
            WCFL_Plugin::enqueue_frontend_assets(false);
        }

        $in_editor = $this->is_elementor_editor();

        // In Elementor preview/editor: nooit lazy via data-src (anders zie je broken previews)
        $lazy = ($in_editor) ? 'no' : (($s['lazy'] === 'yes') ? 'yes' : 'no');

        $atts = [
            'class'            => '',
            'layout'           => $s['layout'] ?: 'row',
            // Kolommen worden via Elementor selectors naar CSS var geschreven.
            'columns'          => (int)($s['columns'] ?: 6),

            'show_provider'    => ($s['show_provider'] === 'yes') ? 'yes' : 'no',
            'provider_mode'    => $s['provider_mode'] ?: 'auto',
            'provider_label'   => $s['provider_label'] ?? '',
            'provider_filter'  => $s['provider_filter'] ?: 'manual',

            'show_all_enabled' => 'yes',
            'debug'           => ($s['debug'] === 'yes') ? 'yes' : 'no',
        ];

        $items  = [];

        if (($atts['debug'] ?? 'no') === 'yes' && current_user_can('manage_options')) {
            $gateways = wcfl_get_gateways(['show_all_enabled' => 'yes'] + $atts);
            $total = is_array($gateways) ? count($gateways) : 0;
            $enabled = 0; $icons = 0;
            $gateway_details = [];
            if (is_array($gateways)) {
                foreach ($gateways as $gid => $gobj) {
                    $prop = (isset($gobj->enabled) && is_string($gobj->enabled)) ? $gobj->enabled : '';
                    $opt  = (method_exists($gobj,'get_option')) ? (string)$gobj->get_option('enabled','') : '';
                    $gateway_details[] = $gid . ':prop=' . $prop . ',opt=' . $opt;
                    if (wcfl_is_gateway_enabled($gobj)) {
                        $enabled++;
                        $ih = wcfl_get_gateway_icon_html($gid, $gobj);
                        if (trim((string)$ih) !== '') $icons++;
                    }
                }
            }
            $msg = sprintf('WCFL DEBUG: gateways=%d enabled=%d icons=%d provider_filter=%s | %s', $total, $enabled, $icons, (string)($atts['provider_filter'] ?? ''), implode(' ; ', array_slice($gateway_details, 0, 12)));
            $items[] = '<span class="wcfl__debug" style="display:none" data-wcfl-debug="' . esc_attr($msg) . '"></span>';
        }

        if ($atts['show_provider'] === 'yes') {
            $badge = wcfl_provider_badge_html($atts);
            if ($badge) {
                $items[] = '<div class="wcfl__item wcfl__item--provider">' . $badge . '</div>';
            }
        }

        $items = array_merge($items, wcfl_collect_payment_logo_items($atts));

        if (!empty($s['manual_logos'])) {
            foreach ($s['manual_logos'] as $row) {
                $row = is_array($row) ? $row : [];
                $url = $this->get_manual_logo_url($row);
                if (empty($url)) continue;

                $url   = esc_url($url);
                $label = !empty($row['label']) ? (string)$row['label'] : 'Logo';
                $size  = !empty($row['size']) ? (string)$row['size'] : ''; // optional per-logo size override

                // Per-item CSS vars override (works with checkout overrides and Style tab)
                $item_vars = '';
                if (trim($size) !== '') {
                    $item_vars = ' style="--wcfl-logo-max-height:' . esc_attr($size) . ';--wcfl-logo-max-width:' . esc_attr($size) . ';"';
                }

                // ✅ In editor altijd src (preview fix). Frontend kan lazy blijven gebruiken.
                if ($lazy === 'yes') {
                    $img = '<img data-src="' . $url . '" alt="' . esc_attr($label) . '" class="wcfl__img wcfl__img--lazy" />';
                } else {
                    $img = '<img src="' . $url . '" alt="' . esc_attr($label) . '" class="wcfl__img" loading="lazy" decoding="async" />';
                }

                $items[] = '<div class="wcfl__item wcfl__item--manual" aria-label="' . esc_attr($label) . '" title="' . esc_attr($label) . '"' . $item_vars . '>' . $img . '</div>';
            }
        }

        if ($lazy === 'yes') {
            wp_enqueue_script('wcfl-lazy');
        }

        // Geen inline responsive vars meer nodig; Elementor schrijft --wcfl-cols via selectors.
        $classes = ['wcfl', 'wcfl--' . (($atts['layout'] === 'inline') ? 'inline' : 'row')];
        if (!empty($atts['class'])) { $classes[] = sanitize_html_class($atts['class']); }
        echo '<div class="' . esc_attr(implode(' ', $classes)) . '">';
        echo implode('', $items);
        echo '</div>';
    }
}
