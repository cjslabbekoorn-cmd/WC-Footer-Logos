# WC Footer Logos

WooCommerce footer logo's via shortcode + Elementor widget.

## Features (v1.0.0)
- Shortcode: `[wc_footer_logos]`
- Elementor widget: **WC Footer Logos**
- Groepen: payment / shipping / trust + optioneel provider-label + manual logos
- Instelbaar aantal kolommen via CSS grid
- Lazy-load (data-src) voor shipping/trust/manual logo's

## Shortcode voorbeelden
Toon alles:
`[wc_footer_logos]`

Payment + provider badge (auto):
`[wc_footer_logos groups="payment" show_provider="yes" provider_mode="auto" columns="6"]`

Filter payment gateways op provider:
`[wc_footer_logos groups="payment" provider_filter="mollie"]`

## GitHub release ZIP
Maak een tag zoals `v1.0.0` en push die:
- `git tag v1.0.0`
- `git push origin v1.0.0`

De workflow bouwt een ZIP artifact: `wc-footer-logos-v1.0.0.zip`
