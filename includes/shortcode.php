<?php
if (!defined('ABSPATH')) exit;

function wcfl_get_shipping_logos() {
    return apply_filters('wcfl_default_shipping_logos', []);
}

function wcfl_get_trust_logos() {
    return apply_filters('wcfl_default_trust_logos', []);
}

function wcfl_build_wrapper_html(array $items, $atts) {
    if (empty($items)) return '';

    $classes = [
        'wcfl',
        'wcfl--' . ($atts['layout'] === 'inline' ? 'inline' : 'row'),
    ];

    if (!empty($atts['class'])) {
        $classes[] = sanitize_html_class($atts['class']);
    }

    $cols = max(1, (int)$atts['columns']);
    $style = '--wcfl-cols:' . $cols . ';';

    $html  = '<div class="' . esc_attr(implode(' ', $classes)) . '" style="' . esc_attr($style) . '">';
    $html .= implode('', $items);
    $html .= '</div>';

    return $html;
}

function wcfl_build_logo_items_from_array($logos, $atts, $group_type = 'shipping') {
    if (empty($logos) || !is_array($logos)) return [];

    $items = [];
    foreach ($logos as $key => $data) {
        if (empty($data['url'])) continue;

        $url   = esc_url($data['url']);
        $label = isset($data['label']) ? $data['label'] : $key;

        $size_value = (!empty($data['size'])) ? $data['size'] : $atts['size'];
        $size_css   = esc_attr($size_value);

        $style_attr = 'style="max-height:' . $size_css . ';max-width:' . $size_css . ';"';
        $key_sanitized = sanitize_html_class($key);

        // Lazy: data-src of direct src
        if ($atts['lazy'] === 'yes') {
            $img = sprintf(
                '<img data-src="%1$s" alt="%2$s" %3$s class="wcfl__img wcfl__img--lazy" />',
                $url,
                esc_attr($label),
                $style_attr
            );
        } else {
            $img = sprintf(
                '<img src="%1$s" alt="%2$s" %3$s class="wcfl__img" loading="lazy" decoding="async" />',
                $url,
                esc_attr($label),
                $style_attr
            );
        }

        $items[] = sprintf(
            '<div class="wcfl__item wcfl__item--%1$s wcfl__item--%1$s-%2$s" aria-label="%3$s" title="%3$s">%4$s</div>',
            esc_attr($group_type),
            esc_attr($key_sanitized),
            esc_attr($label),
            $img
        );
    }

    return $items;
}

function wcfl_collect_payment_logo_items($atts) {
    if (!class_exists('WooCommerce') || !function_exists('WC') || !WC()->payment_gateways()) return [];

    $gateways = WC()->payment_gateways()->get_available_payment_gateways();
    if (empty($gateways) || !is_array($gateways)) return [];

    $items = [];

    foreach ($gateways as $gateway_id => $gateway_obj) {
        if (empty($gateway_obj->enabled) || $gateway_obj->enabled !== 'yes') continue;
        if (!method_exists($gateway_obj, 'get_icon')) continue;

        // Provider filter (optioneel)
        if (!WCFL_Provider::gateway_belongs_to_provider($gateway_id, $gateway_obj, $atts['provider_filter'])) {
            continue;
        }

        $icon_html = $gateway_obj->get_icon();
        if (!$icon_html || trim($icon_html) === '') continue;

        // Lazy/async toevoegen als er <img> is en nog geen loading=
        if (stripos($icon_html, '<img') !== false && stripos($icon_html, ' loading=') === false) {
            $icon_html = str_ireplace('<img ', '<img loading="lazy" decoding="async" ', $icon_html);
        }

        $title = isset($gateway_obj->title) ? $gateway_obj->title : $gateway_id;

        $items[] = sprintf(
            '<div class="wcfl__item wcfl__item--payment wcfl__item--%1$s" aria-label="%2$s" title="%2$s">%3$s</div>',
            esc_attr(sanitize_html_class($gateway_id)),
            esc_attr($title),
            $icon_html
        );
    }

    return $items;
}

function wcfl_provider_badge_html($atts) {
    if ($atts['show_provider'] !== 'yes') return '';

    $provider_mode = $atts['provider_mode']; // auto|manual
    $manual_label  = $atts['provider_label'];

    if ($provider_mode === 'manual') {
        $label = WCFL_Provider::get_provider_label('manual', $manual_label);
        return '<div class="wcfl__provider" aria-label="' . esc_attr($label) . '">' . esc_html($label) . '</div>';
    }

    // auto detect
    $gateways = (class_exists('WooCommerce') && function_exists('WC') && WC()->payment_gateways())
        ? WC()->payment_gateways()->get_available_payment_gateways()
        : [];

    $provider_key = WCFL_Provider::detect_active_provider_key_from_gateways($gateways);
    $label = $provider_key ? WCFL_Provider::get_provider_label($provider_key) : WCFL_Provider::get_provider_label('manual', $manual_label);

    return '<div class="wcfl__provider" aria-label="' . esc_attr($label) . '">' . esc_html($label) . '</div>';
}

function wcfl_logos_shortcode($atts) {
    $atts = shortcode_atts(
        [
            'size'            => '40px',
            'class'           => '',
            'layout'          => 'row', // row|inline
            'groups'          => 'payment,shipping,trust',
            'columns'         => 6,

            // provider badge
            'show_provider'   => 'no',   // yes|no
            'provider_mode'   => 'auto', // auto|manual
            'provider_label'  => '',

            // payment filter by provider (optional)
            'provider_filter' => 'auto', // auto|multisafepay|mollie|stripe|paypal|adyen|manual|''

            // lazy loading for shipping/trust images
            'lazy'            => 'yes', // yes|no
        ],
        $atts,
        'wc_footer_logos'
    );

    // Groups normaliseren
    $groups = array_filter(array_map('trim', explode(',', strtolower($atts['groups']))));
    if (empty($groups)) $groups = ['payment','shipping','trust'];
    $groups = array_unique($groups);

    $items = [];

    if ($atts['show_provider'] === 'yes') {
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

    if ($atts['lazy'] === 'yes') {
        wp_enqueue_script('wcfl-lazy');
    }

    return wcfl_build_wrapper_html($items, $atts);
}

add_shortcode('wc_footer_logos', 'wcfl_logos_shortcode');
