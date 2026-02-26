<?php
if (!defined('ABSPATH')) exit;

/**
 * Shortcode: [wc_footer_logos]
 * Toont WooCommerce payment gateway icons + optioneel provider label.
 *
 * Attribs:
 * - class
 * - layout: row|inline
 * - columns: aantal kolommen desktop (1-12)
 * - columns_tablet: aantal kolommen <=768px (optioneel)
 * - columns_mobile: aantal kolommen <=480px (optioneel)
 * - show_provider: yes|no
 * - provider_mode: auto|manual
 * - provider_label: tekst als manual
 * - provider_filter: auto|multisafepay|mollie|stripe|paypal|adyen|manual|''
 * - show_all_enabled: yes|no  (default: yes) -> toon alle ingeschakelde gateways, ook buiten checkout
 */

function wcfl_enqueue_assets(array $atts): void {
    // Enqueue CSS altijd wanneer shortcode/widget rendert
    if (class_exists('WCFL_Plugin')) {
        $needs_lazy = false; // payment gateway icons gebruiken direct <img src>, geen lazy script nodig
        WCFL_Plugin::enqueue_frontend_assets($needs_lazy);
    }
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

    $cols        = max(1, (int)$atts['columns']);
    $cols_tablet = isset($atts['columns_tablet']) && $atts['columns_tablet'] !== '' ? max(1, (int)$atts['columns_tablet']) : '';
    $cols_mobile = isset($atts['columns_mobile']) && $atts['columns_mobile'] !== '' ? max(1, (int)$atts['columns_mobile']) : '';

    // Unique id per instance (voor responsive kolommen via inline <style>)
    $instance_id = 'wcfl-' . wp_generate_password(8, false, false);

    // Desktop kolommen altijd via CSS var
    $style = '--wcfl-cols:' . $cols . ';';

    $html  = '<div id="' . esc_attr($instance_id) . '" class="' . esc_attr(implode(' ', $classes)) . '" style="' . esc_attr($style) . '">';
    $html .= implode('', $items);
    $html .= '</div>';

    // Tablet/Mobile via instance-scoped media queries (zodat shortcode ook responsive kan zijn)
    if ($cols_tablet !== '' || $cols_mobile !== '') {
        $css = '';
        if ($cols_tablet !== '') {
            $css .= '@media (max-width:768px){#' . $instance_id . '{--wcfl-cols:' . $cols_tablet . ';}}';
        }
        if ($cols_mobile !== '') {
            $css .= '@media (max-width:480px){#' . $instance_id . '{--wcfl-cols:' . $cols_mobile . ';}}';
        }
        $html .= '<style>' . $css . '</style>';
    }

    return $html;
}

function wcfl_get_gateways(array $atts) {
    static $req_cache = [];
    $mode = (($atts['show_all_enabled'] ?? 'yes') === 'no') ? 'available' : 'all';
    if (isset($req_cache[$mode]) && is_array($req_cache[$mode])) return $req_cache[$mode];

    if (!class_exists('WooCommerce') || !function_exists('WC') || !WC()->payment_gateways()) return [];

    $pg = WC()->payment_gateways();

    // Extra robuust (builders/caching): force init/load gateways
    if (method_exists($pg, 'init')) {
        $pg->init();
    }

    if (($atts['show_all_enabled'] ?? 'yes') === 'no') {
        $gateways = $pg->get_available_payment_gateways();
    } else {
        $gateways = $pg->payment_gateways();
    }

    $req_cache[$mode] = (is_array($gateways)) ? $gateways : [];
    return $req_cache[$mode];
}

function wcfl_get_gateway_icon_html($gateway_id, $gateway_obj) {
    $icon_html = '';

    if (method_exists($gateway_obj, 'get_icon')) {
        $icon_html = (string)$gateway_obj->get_icon();
    }

    // Fallback 1: icon property
    if (trim($icon_html) === '') {
        if (isset($gateway_obj->icon) && is_string($gateway_obj->icon) && trim($gateway_obj->icon) !== '') {
            $url = esc_url($gateway_obj->icon);
            $alt = esc_attr(isset($gateway_obj->title) ? $gateway_obj->title : $gateway_id);
            $icon_html = '<img src="' . $url . '" alt="' . $alt . '" loading="lazy" decoding="async" />';
        }
    }

    // Fallback 2: option 'icon'
    if (trim($icon_html) === '' && method_exists($gateway_obj, 'get_option')) {
        $opt = (string)$gateway_obj->get_option('icon', '');
        if (trim($opt) !== '') {
            $url = esc_url($opt);
            $alt = esc_attr(isset($gateway_obj->title) ? $gateway_obj->title : $gateway_id);
            $icon_html = '<img src="' . $url . '" alt="' . $alt . '" loading="lazy" decoding="async" />';
        }
    }

    // Laat site/theme override doen
    $icon_html = apply_filters('wcfl_gateway_icon_html', $icon_html, $gateway_id, $gateway_obj);

    return $icon_html;
}


function wcfl_is_gateway_enabled($gateway_obj): bool {
    // Most gateways expose ->enabled (string yes/no)
    if (isset($gateway_obj->enabled) && is_string($gateway_obj->enabled) && $gateway_obj->enabled === 'yes') {
        return true;
    }

    // Fallback: read from options
    if (method_exists($gateway_obj, 'get_option')) {
        $opt = (string)$gateway_obj->get_option('enabled', '');
        if ($opt === 'yes') return true;
    }

    // Fallback: some gateways store settings array
    if (isset($gateway_obj->settings) && is_array($gateway_obj->settings) && isset($gateway_obj->settings['enabled']) && $gateway_obj->settings['enabled'] === 'yes') {
        return true;
    }

    return false;
}

function wcfl_collect_payment_logo_items($atts) {
    $gateways = wcfl_get_gateways($atts);
    if (empty($gateways)) return [];

    // Transient cache (6h) op basis van enabled gateways + provider filter + cache bust
    $bust   = (int) get_option('wcfl_cache_bust', 1);
    $locale = function_exists('determine_locale') ? determine_locale() : get_locale();
    $enabled_fingerprint = [];
    foreach ($gateways as $gid => $gobj) {
        $prop = (isset($gobj->enabled) && is_string($gobj->enabled)) ? $gobj->enabled : '';
        $opt  = (method_exists($gobj,'get_option')) ? (string)$gobj->get_option('enabled','') : '';
        $icon = (method_exists($gobj,'get_option')) ? (string)$gobj->get_option('icon','') : '';
        $enabled_fingerprint[] = $gid . ':' . $prop . ':' . $opt . ':' . md5($icon);
    }
    $cache_seed = implode('|', $enabled_fingerprint) . '|' . (string)($atts['provider_filter'] ?? '') . '|' . $bust . '|' . $locale . '|' . WCFL_VERSION;
    $cache_key  = 'wcfl_pay_items_' . md5($cache_seed);
    $cached = get_transient($cache_key);
    if (is_array($cached)) {
        return $cached;
    }

    wcfl_enqueue_assets($atts);

    $items = [];

    foreach ($gateways as $gateway_id => $gateway_obj) {
        if (!wcfl_is_gateway_enabled($gateway_obj)) continue;

        if (!WCFL_Provider::gateway_belongs_to_provider($gateway_id, $gateway_obj, $atts['provider_filter'])) {
            continue;
        }

        $icon_html = wcfl_get_gateway_icon_html($gateway_id, $gateway_obj);
        if (!$icon_html || trim($icon_html) === '') continue;

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

    set_transient($cache_key, $items, 6 * HOUR_IN_SECONDS);
    return $items;
}

function wcfl_provider_badge_html($atts) {
    if (($atts['show_provider'] ?? 'no') !== 'yes') return '';

    $provider_mode   = $atts['provider_mode'] ?? 'auto';
    $manual_label    = $atts['provider_label'] ?? '';
    $provider_filter = $atts['provider_filter'] ?? 'manual';

    // If user explicitly selected a provider filter, use that label (most predictable)
    if ($provider_filter && $provider_filter !== 'manual' && $provider_filter !== 'auto') {
        $label = WCFL_Provider::get_provider_label($provider_filter, $manual_label);
        return '<div class="wcfl__provider" aria-label="' . esc_attr($label) . '">' . esc_html($label) . '</div>';
    }

    if ($provider_mode === 'manual') {
        $label = WCFL_Provider::get_provider_label('manual', $manual_label);
        return '<div class="wcfl__provider" aria-label="' . esc_attr($label) . '">' . esc_html($label) . '</div>';
    }

    // Auto detect op basis van enabled gateways
    $gateways = wcfl_get_gateways(['show_all_enabled' => 'yes'] + $atts);

    $provider_key = WCFL_Provider::detect_active_provider_key_from_gateways($gateways);

    // Als autodetect faalt, toon geen generiek label maar niks (tenzij manual_label gevuld is)
    if (!$provider_key) {
        if (trim($manual_label) === '') return '';
        $provider_key = 'manual';
    }

    $label = WCFL_Provider::get_provider_label($provider_key, $manual_label);

    return '<div class="wcfl__provider" aria-label="' . esc_attr($label) . '">' . esc_html($label) . '</div>';
}

function wcfl_logos_shortcode($atts) {
    $atts = shortcode_atts(
        [
            'class'            => '',
            'layout'           => 'row',
            'columns'          => 6,
            'columns_tablet'   => '',
            'columns_mobile'   => '',

            'show_provider'    => 'no',
            'provider_mode'    => 'auto',
            'provider_label'   => '',

            'provider_filter'  => 'manual',

            'show_all_enabled' => 'yes',
            'debug'           => 'no',
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

    $html = wcfl_build_wrapper_html($items, $atts);
    if (($atts['debug'] ?? 'no') === 'yes' && current_user_can('manage_options')) {
        $gateways = wcfl_get_gateways(['show_all_enabled' => 'yes'] + $atts);
        $total = is_array($gateways) ? count($gateways) : 0;
        $enabled = 0;
        $icons = 0;
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
        $debug_span = '<span class="wcfl__debug" style="display:none" data-wcfl-debug="' . esc_attr($msg) . '"></span>';
        // Inject just after opening wrapper div
        $html = preg_replace('/(<div[^>]*class="[^"]*wcfl[^"]*"[^>]*>)/', '$1' . $debug_span, $html, 1);
    }
    return $html;
}

add_shortcode('wc_footer_logos', 'wcfl_logos_shortcode');
