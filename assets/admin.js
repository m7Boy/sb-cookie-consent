(function ($) {
	'use strict';

	var cfg = window.sbCookieConsentAdmin || {};

	function toggleColorRows() {
		var useTheme = $('#sb-cc-use-theme').is(':checked');
		$('.sb-cc-color-row').toggleClass('is-disabled', useTheme);
		$('.sb-cc-color-row input').prop('disabled', useTheme);
	}

	function nextIndex() {
		var max = -1;
		$('#sb-cc-cookies tbody .sb-cc-cookie-row').each(function () {
			$(this)
				.find('input, select')
				.each(function () {
					var name = $(this).attr('name') || '';
					var match = name.match(/\[cookies\]\[(\d+)\]/);
					if (match) {
						max = Math.max(max, parseInt(match[1], 10));
					}
				});
		});
		return max + 1;
	}

	function collectExisting() {
		var rows = [];
		$('#sb-cc-cookies tbody .sb-cc-cookie-row').each(function () {
			var $row = $(this);
			var name = $.trim($row.find('.sb-cc-col-name input').val() || '');
			if (!name) {
				return;
			}
			rows.push({
				name: name,
				category: $row.find('.sb-cc-col-cat select').val() || '',
			});
		});
		return rows;
	}

	function appendCookieRow(cookie) {
		var tpl = document.getElementById('sb-cc-cookie-row-tpl');
		if (!tpl) {
			return;
		}

		// Drop placeholder empty first row.
		var $rows = $('#sb-cc-cookies tbody .sb-cc-cookie-row');
		if ($rows.length === 1) {
			var onlyName = $.trim($rows.first().find('.sb-cc-col-name input').val() || '');
			if (!onlyName) {
				$rows.first().remove();
			}
		}

		var index = nextIndex();
		var html = tpl.innerHTML.replace(/__i__/g, String(index));
		var $row = $(html);
		$row.find('.sb-cc-col-name input').val(cookie.name || '');
		$row.find('.sb-cc-col-cat select').val(cookie.category || 'analytics');
		$row.find('.sb-cc-col-provider input').val(cookie.provider || '');
		$row.find('.sb-cc-col-purpose input').val(cookie.purpose || '');
		$row.find('.sb-cc-col-duration input').val(cookie.duration || '');
		$row.find('.sb-cc-col-handles input').val(cookie.script_handles || '');
		$('#sb-cc-cookies tbody').append($row);
	}

	$(function () {
		$('.sb-cc-color').each(function () {
			var $input = $(this);
			var defaultColor = $input.data('default-color');
			var options = {
				change: function () {},
				clear: function () {},
			};

			if (defaultColor === 'transparent') {
				options.palettes = false;
			}

			$input.wpColorPicker(options);
		});

		$('#sb-cc-use-theme').on('change', toggleColorRows);
		toggleColorRows();

		$('#sb-cc-add-cookie').on('click', function () {
			var tpl = document.getElementById('sb-cc-cookie-row-tpl');
			if (!tpl) {
				return;
			}
			var html = tpl.innerHTML.replace(/__i__/g, String(nextIndex()));
			$('#sb-cc-cookies tbody').append(html);
		});

		$('#sb-cc-cookies').on('click', '.sb-cc-remove-cookie', function () {
			var $rows = $('#sb-cc-cookies tbody .sb-cc-cookie-row');
			if ($rows.length < 2) {
				$(this).closest('tr').find('input').val('');
				return;
			}
			$(this).closest('tr').remove();
		});

		$('#sb-cc-scan-cookies').on('click', function () {
			var $btn = $(this);
			var $status = $('#sb-cc-scan-status');
			$btn.prop('disabled', true);
			$status.text(cfg.scanning || '…');

			$.post(cfg.ajaxUrl, {
				action: 'sb_cookie_consent_scan',
				nonce: cfg.nonce,
				existing: collectExisting(),
			})
				.done(function (response) {
					if (!response || !response.success || !response.data) {
						$status.text(cfg.scanError || 'Error');
						return;
					}
					var cookies = response.data.cookies || [];
					cookies.forEach(appendCookieRow);
					$status.text(response.data.message || '');
				})
				.fail(function () {
					$status.text(cfg.scanError || 'Error');
				})
				.always(function () {
					$btn.prop('disabled', false);
				});
		});
	});
})(jQuery);
