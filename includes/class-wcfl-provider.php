<?php
if (!defined('ABSPATH')) exit;

class WCFL_Provider {

    public static function provider_map() {
        return [
            'auto' => [
                'label' => __('Auto-detect', 'wc-footer-logos'),
                'match' => [],
            ],
            'multisafepay' => [
                'label' => 'MultiSafepay',
                'match' => ['multisafepay', 'msp', 'multi_safe_pay'],
            ],
            'mollie' => [
                'label' => 'Mollie',
                'match' => ['mollie'],
            ],
            'stripe' => [
                'label' => 'Stripe',
                'match' => ['stripe'],
            ],
            'paypal' => [
                'label' => 'PayPal',
                'match' => ['paypal', 'ppec', 'ppcp'],
            ],
            'adyen' => [
                'label' => 'Adyen',
                'match' => ['adyen'],
            ],
        ];
    }

    public static function detect_active_provider_key_from_gateways($gateways) {
        if (empty($gateways) || !is_array($gateways)) return '';

        $map = self::provider_map();

        foreach ($gateways as $gateway_id => $gateway_obj) {
            if (empty($gateway_obj->enabled) || $gateway_obj->enabled !== 'yes') continue;

            $hay = strtolower($gateway_id . ' ' . get_class($gateway_obj));

            foreach ($map as $provider_key => $provider) {
                if ($provider_key === 'auto') continue;

                foreach ((array)$provider['match'] as $needle) {
                    if ($needle && strpos($hay, strtolower($needle)) !== false) {
                        return $provider_key;
                    }
                }
            }
        }

        return '';
    }

    public static function get_provider_label($provider_key, $manual_label = '') {
        $map = self::provider_map();

        if ($provider_key === 'manual') {
            return trim($manual_label) ?: __('Payment provider', 'wc-footer-logos');
        }

        if (isset($map[$provider_key]['label'])) {
            return $map[$provider_key]['label'];
        }

        return __('Payment provider', 'wc-footer-logos');
    }

    public static function gateway_belongs_to_provider($gateway_id, $gateway_obj, $provider_key) {
        if ($provider_key === 'auto' || $provider_key === '' || $provider_key === 'manual') return true;

        $map = self::provider_map();
        if (!isset($map[$provider_key])) return true;

        $hay = strtolower($gateway_id . ' ' . get_class($gateway_obj));
        foreach ((array)$map[$provider_key]['match'] as $needle) {
            if ($needle && strpos($hay, strtolower($needle)) !== false) {
                return true;
            }
        }
        return false;
    }
}
