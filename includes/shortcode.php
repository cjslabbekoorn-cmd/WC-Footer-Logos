<?php
if (!defined('ABSPATH')) exit;

/**
 * Shortcode: [wc_footer_logos]
 * Toont WooCommerce payment gateway icons + optioneel provider label.
 *
 * Attribs:
 * - class
 * - layout: row|inline
 * - columns: 1-12 (CSS grid)
 * - show_provider: yes|no
 * - provider_mode: auto|manual
 * - provider_label: tekst als manual
 * - provider_filter: auto|multisafepay|mollie|stripe|paypal|adyen|manual|''
 * - lazy: yes|no (alleen relevant voor manual images; payment icons krijgen loading= toegevoegd waar mogelijk)
 */

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
            'class'           => '',
            'layout'          => 'row', // row|inline
            'columns'         => 6,

            // provider badge
            'show_provider'   => 'no',   // yes|no
            'provider_mode'   => 'auto', // auto|manual
            'provider_label'  => '',

            // payment filter by provider (optional)
            'provider_filter' => 'auto', // auto|multisafepay|mollie|stripe|paypal|adyen|manual|''

            // lazy loading (script blijft bestaan; widget gebruikt dit ook)
            'lazy'            => 'yes', // yes|no
        ],
        $atts,
        'wc_footer_logos'
    );

    $items = [];

    if ($atts['show_provider'] === 'yes') {
        $badge = wcfl_provider_badge_html($atts);
        if ($badge) {
            $items[] = '<div class="wcfl__item wcfl__item--provider">' . $badge . '</div>';
        }
    }

    $items = array_merge($items, wcfl_collect_payment_logo_items($atts));

    // Lazy script kan nodig zijn voor manual images (alleen widget), maar kortom: conditioneel laden op attribute.
    if ($atts['lazy'] === 'yes') {
        wp_enqueue_script('wcfl-lazy');
    }

    return wcfl_build_wrapper_html($items, $atts);
}

add_shortcode('wc_footer_logos', 'wcfl_logos_shortcode');
