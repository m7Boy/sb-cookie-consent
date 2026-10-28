(function ($) {
	'use strict';

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
	});
})(jQuery);
