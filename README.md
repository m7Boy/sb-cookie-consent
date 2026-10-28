# SB Cookie Consent

WordPress cookie consent with categories, a cookie inventory, a cookie-policy shortcode, a permanent policy link, a reopen control, and real script/cookie gating. Developed by [Software Brewery](https://softwarebrewery.net).

## Requirements

- WordPress 6.0+
- PHP 8.0+

## Features

- Banner with **Necessary / Preferences / Analytics / Marketing**
- Per-category toggles and cookie lists (empty categories still shown)
- **Accept all**, **Necessary only**, **Save choice**
- Floating icon to change consent after accept/decline
- Permanent footer link to the **cookie policy** (not privacy)
- Shortcode `[sb_cookie_policy]` for the full inventory on a policy page
- Admin cookie registry (name/mask, provider, purpose, duration, WP script handles)
- Blocks mapped scripts until the category is allowed (`type="text/plain"` → activated on consent)
- Deletes matching first-party cookies when a category is turned off
- Theme colors by default; manual overrides via color picker
- Helpers: `sb_cookie_consent_allows( 'analytics' )`, `window.sbCC.has('analytics')`

## Setup

1. Activate **SB Cookie Consent**.
2. Create a page (e.g. “Политика за бисквитки”) and add `[sb_cookie_policy]`.
3. Open **Settings → SB Cookie Consent**, set the cookie policy URL, and fill the cookie registry.
4. For analytics/marketing scripts registered in WordPress, put their **script handles** on the matching cookie row so they stay blocked without consent.

## Front-end API

```js
document.addEventListener('sbCookieConsent', function (event) {
  console.log(event.detail.categories);
});

if (window.sbCC && window.sbCC.has('analytics')) {
  // load analytics
}
```

```php
if ( sb_cookie_consent_allows( 'marketing' ) ) {
	// output marketing tag
}
```

## Filters

- `sb_cookie_consent_theme_colors` — default banner colors from the theme
- `sb_cookie_consent_gated_handles` — map of `script_handle => category`

## Version

1.1.0 — Categories, inventory, cookie policy shortcode, permanent policy link, reopen icon, script gating and cookie purge.

## License

GPL-2.0-or-later
