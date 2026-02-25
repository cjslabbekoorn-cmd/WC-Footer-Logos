# WC Footer Logos

WooCommerce betaalmethoden (payment gateways) logo's via shortcode + Elementor widget.

## Features (v1.0.3)
- Shortcode: `[wc_footer_logos]` (payment icons + optioneel provider label)
- Elementor widget: **WC Footer Logos**
- Optioneel: provider label (auto/manual)
- Optioneel: handmatige extra logo's (Elementor repeater)
- Instelbaar aantal kolommen via CSS grid
- Lazy-load (data-src) voor handmatige logo's
- **GitHub Releases updater** (WordPress ziet updates vanuit GitHub)

## Belangrijk voor updates via GitHub
1. Maak een release met tag `v1.0.3` (of hoger).
2. Upload de plugin-zip als **release asset**, bij voorkeur met naam `wc-footer-logos-<versie>.zip`.
3. In WP → Plugins zie je dan een update melding zodra er een hogere versie is.

Repo configuratie staat in `includes/class-wcfl-plugin.php` (owner/repo).

## Shortcode voorbeelden
Payment icons:
`[wc_footer_logos]`

Payment + provider label:
`[wc_footer_logos show_provider="yes" provider_mode="auto" columns="6"]`

Filter payment gateways op provider:
`[wc_footer_logos provider_filter="mollie"]`
