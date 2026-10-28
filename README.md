# SB Cookie Consent

A WordPress plugin that shows a cookie consent banner with admin-managed text, buttons, and colors. Theme palette colors are used by default. Developed by [Software Brewery](https://softwarebrewery.net).

## Requirements

- WordPress 6.0+
- PHP 8.0+

## Features

- Consent banner on the front end (“Съгласявам се” / optional decline)
- Editable message, button labels, privacy policy link, position, and cookie lifetime
- Colors from the active theme (`theme.json` / global styles) by default
- Manual color overrides with the WordPress color picker
- Choice stored in a first-party cookie (`sb_cookie_consent`)
- `sbCookieConsent` custom event for other scripts after accept/decline
- Settings under **Settings → SB Cookie Consent**
- Clean uninstall (removes plugin options)

## Installation

1. Copy the `sb-cookie-consent` folder into `wp-content/plugins/`.
2. Activate **SB Cookie Consent** from Plugins.
3. Open **Settings → SB Cookie Consent** and adjust the message, buttons, and colors.

## Usage

Visitors without a stored choice see the banner. Accept or decline sets `sb_cookie_consent` to `accepted` or `declined` and hides the banner for the configured number of days.

Listen for the choice in other scripts:

```js
document.addEventListener('sbCookieConsent', function (event) {
  console.log(event.detail.status); // 'accepted' | 'declined'
});
```

Filter theme-derived colors:

```php
add_filter( 'sb_cookie_consent_theme_colors', function ( $colors ) {
	$colors['primary'] = '#0a7';
	return $colors;
} );
```

## Security

- Capability checks on the settings screen
- Settings API with sanitized input
- Escaped output on admin and front end
- Cookie uses `SameSite=Lax` (and `Secure` on HTTPS)

## Structure

```
sb-cookie-consent/
├── sb-cookie-consent.php
├── uninstall.php
├── includes/
│   ├── class-settings.php
│   ├── class-admin.php
│   └── class-frontend.php
└── assets/
    ├── frontend.css
    ├── frontend.js
    ├── admin.css
    └── admin.js
```

## Version

1.0.1 — Banner renders before footer scripts so it is visible without relying on JS to unhide it.

## License

GPL-2.0-or-later
