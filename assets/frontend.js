(function () {
	'use strict';

	function init() {
		var cfg = window.sbCookieConsent || {};
		var cookieName = cfg.cookieName || 'sb_cookie_consent';
		var days = parseInt(cfg.days, 10) || 365;
		var banner = document.getElementById('sb-cookie-consent');

		if (!banner) {
			return;
		}

		function readCookie(name) {
			var match = document.cookie.match(
				new RegExp('(?:^|; )' + name.replace(/([.$?*|{}()[\]\\/+^])/g, '\\$1') + '=([^;]*)')
			);
			return match ? decodeURIComponent(match[1]) : '';
		}

		function writeCookie(name, value, expireDays) {
			var maxAge = Math.max(1, expireDays) * 24 * 60 * 60;
			var secure = window.location.protocol === 'https:' ? '; Secure' : '';
			document.cookie =
				name +
				'=' +
				encodeURIComponent(value) +
				'; Path=/; Max-Age=' +
				maxAge +
				'; SameSite=Lax' +
				secure;
		}

		function hide() {
			banner.setAttribute('hidden', 'hidden');
		}

		var existing = readCookie(cookieName);
		if (existing === 'accepted' || existing === 'declined') {
			hide();
			return;
		}

		banner.addEventListener('click', function (event) {
			var target = event.target.closest
				? event.target.closest('[data-sb-cc]')
				: event.target;
			if (!target || !target.getAttribute) {
				return;
			}

			var action = target.getAttribute('data-sb-cc');
			if (action !== 'accept' && action !== 'decline') {
				return;
			}

			writeCookie(cookieName, action === 'accept' ? 'accepted' : 'declined', days);
			hide();

			try {
				document.dispatchEvent(
					new CustomEvent('sbCookieConsent', {
						detail: { status: action === 'accept' ? 'accepted' : 'declined' },
					})
				);
			} catch (e) {
				/* older browsers */
			}
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
