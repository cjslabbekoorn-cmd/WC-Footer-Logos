# WC Footer Logos

WooCommerce betaalmethoden (payment gateways) logo's via shortcode + Elementor widget.

## Requirements
- WordPress: 
- PHP: 7.4+
- WooCommerce: recommended (required for payment gateway icons)
- Elementor: optional (required only for the Elementor widget)

## License
GPL-2.0-or-later. See `LICENSE`.

## Disclaimer
This plugin is provided “as is”, without warranty of any kind. You use it at your own risk.
Positie1 / Cees-Jan Slabbekoorn is not liable for any damages arising from the use of this plugin.

## Changelog
- 1.4.2: CI/CD toegevoegd (GitHub Actions matrix + PHPCS) + automatische ZIP build & release asset op tags.
- 1.4.1: Admin tool toegevoegd om cache te legen (nonce + capability) + uninstall cleanup (transients/options).
- 1.4.0: Foundation hardening: license + security docs; minimum version guards; safer bootstrapping.

## Development
- `composer install`
- `composer lint`
- `composer phpcs`
- Tag een release (`1.4.2`) om automatisch een ZIP asset te laten bouwen en attachen.

## Admin tools
Ga naar **Tools → WC Footer Logos** om de WCFL cache te legen.

## Debug
(alleen admins) toggle **Debug (admin only)** en zoek in de DOM naar `data-wcfl-debug`.
