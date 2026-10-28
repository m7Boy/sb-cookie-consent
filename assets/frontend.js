(function () {
	'use strict';

	var cfg = window.sbCookieConsent || {};
	var cookieName = cfg.cookieName || 'sb_cookie_consent';
	var days = parseInt(cfg.days, 10) || 365;
	var purgeMap = cfg.purge || {};

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

	function expireCookie(name) {
		var host = window.location.hostname;
		var parts = [
			name + '=; Path=/; Max-Age=0; SameSite=Lax',
			name + '=; Path=/; Max-Age=0; Domain=' + host + '; SameSite=Lax',
			name + '=; Path=/; Max-Age=0; Domain=.' + host + '; SameSite=Lax',
		];
		parts.forEach(function (c) {
			document.cookie = c;
		});
	}

	function patternToRegex(pattern) {
		return new RegExp('^' + pattern.replace(/[.+?^${}()|[\]\\]/g, '\\$&').replace(/\*/g, '.*') + '$');
	}

	function purgeCategory(category) {
		var patterns = purgeMap[category] || [];
		if (!patterns.length) {
			return;
		}
		var all = document.cookie ? document.cookie.split('; ') : [];
		all.forEach(function (pair) {
			var name = pair.split('=')[0];
			if (!name || name === cookieName) {
				return;
			}
			patterns.forEach(function (pattern) {
				if (patternToRegex(pattern).test(name)) {
					expireCookie(name);
				}
			});
		});
	}

	function applyPurge(categories) {
		['preferences', 'analytics', 'marketing'].forEach(function (cat) {
			if (!categories[cat]) {
				purgeCategory(cat);
			}
		});
	}

	function activateScripts(categories) {
		var nodes = document.querySelectorAll('script[type="text/plain"][data-sb-cc-category]');
		nodes.forEach(function (node) {
			var cat = node.getAttribute('data-sb-cc-category');
			if (!cat || cat === 'necessary') {
				return;
			}
			if (!categories[cat]) {
				return;
			}
			var script = document.createElement('script');
			Array.prototype.forEach.call(node.attributes, function (attr) {
				if (attr.name === 'type' || attr.name.indexOf('data-sb-cc') === 0) {
					return;
				}
				script.setAttribute(attr.name, attr.value);
			});
			if (node.src) {
				script.src = node.src;
			} else {
				script.text = node.textContent || '';
			}
			node.parentNode.insertBefore(script, node);
			node.parentNode.removeChild(node);
		});
	}

	function syncBootstrap(categories) {
		window.sbCC = window.sbCC || {};
		window.sbCC.hasChoice = true;
		window.sbCC.categories = categories;
		window.sbCC.has = function (c) {
			c = String(c || '');
			if (c === 'necessary') {
				return true;
			}
			return !!(window.sbCC.categories && window.sbCC.categories[c]);
		};
	}

	function emit(categories) {
		try {
			document.dispatchEvent(
				new CustomEvent('sbCookieConsent', {
					detail: { categories: categories },
				})
			);
		} catch (e) {
			/* ignore */
		}
	}

	function collectFromUi(banner) {
		var categories = {
			necessary: true,
			preferences: false,
			analytics: false,
			marketing: false,
		};
		banner.querySelectorAll('input[data-sb-cc-category]').forEach(function (input) {
			var cat = input.getAttribute('data-sb-cc-category');
			if (!cat || cat === 'necessary') {
				return;
			}
			categories[cat] = !!input.checked;
		});
		return categories;
	}

	function setUi(banner, categories) {
		banner.querySelectorAll('input[data-sb-cc-category]').forEach(function (input) {
			var cat = input.getAttribute('data-sb-cc-category');
			if (!cat) {
				return;
			}
			if (cat === 'necessary') {
				input.checked = true;
				input.disabled = true;
				return;
			}
			input.checked = !!categories[cat];
		});
	}

	function save(categories) {
		writeCookie(cookieName, JSON.stringify(categories), days);
		syncBootstrap(categories);
		applyPurge(categories);
		activateScripts(categories);
		emit(categories);
	}

	function init() {
		var banner = document.getElementById('sb-cookie-consent');
		var reopen = document.getElementById('sb-cookie-consent-reopen');

		if (!banner) {
			return;
		}

		var details = document.getElementById('sb-cookie-consent-details');
		var settingsBtn = banner.querySelector('[data-sb-cc="settings"]');
		var saveBtn = banner.querySelector('[data-sb-cc="save"]');

		function setDetailsOpen(open) {
			if (details) {
				if (open) {
					details.removeAttribute('hidden');
				} else {
					details.setAttribute('hidden', 'hidden');
				}
			}
			if (settingsBtn) {
				settingsBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
				settingsBtn.hidden = !!open;
			}
			if (saveBtn) {
				if (open) {
					saveBtn.removeAttribute('hidden');
				} else {
					saveBtn.setAttribute('hidden', 'hidden');
				}
			}
		}

		function showBanner(openSettings) {
			banner.removeAttribute('hidden');
			setDetailsOpen(!!openSettings);
			if (reopen) {
				reopen.setAttribute('hidden', 'hidden');
			}
		}

		function hideBanner() {
			banner.setAttribute('hidden', 'hidden');
			setDetailsOpen(false);
			if (reopen) {
				reopen.removeAttribute('hidden');
			}
		}

		var existing = readCookie(cookieName);
		if (existing) {
			var parsed = null;
			try {
				parsed = JSON.parse(existing);
			} catch (e) {
				if (existing === 'accepted') {
					parsed = { necessary: true, preferences: true, analytics: true, marketing: true };
				} else if (existing === 'declined') {
					parsed = { necessary: true, preferences: false, analytics: false, marketing: false };
				}
			}
			if (parsed) {
				parsed.necessary = true;
				setUi(banner, parsed);
				syncBootstrap(parsed);
				activateScripts(parsed);
			}
		}

		document.addEventListener('click', function (event) {
			var target = event.target.closest ? event.target.closest('[data-sb-cc]') : null;
			if (!target) {
				return;
			}
			var action = target.getAttribute('data-sb-cc');
			if (!action) {
				return;
			}

			if (action === 'reopen') {
				event.preventDefault();
				showBanner(true);
				return;
			}

			if (!banner.contains(target)) {
				return;
			}

			if (action === 'settings') {
				event.preventDefault();
				setDetailsOpen(true);
				return;
			}

			var categories;
			if (action === 'accept') {
				categories = {
					necessary: true,
					preferences: true,
					analytics: true,
					marketing: true,
				};
				setUi(banner, categories);
			} else if (action === 'decline') {
				categories = {
					necessary: true,
					preferences: false,
					analytics: false,
					marketing: false,
				};
				setUi(banner, categories);
			} else if (action === 'save') {
				categories = collectFromUi(banner);
			} else {
				return;
			}

			save(categories);
			hideBanner();
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
