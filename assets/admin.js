(function ($) {
	'use strict';

	function toggleColorRows() {
		var useTheme = $('#sb-cc-use-theme').is(':checked');
		$('.sb-cc-color-row').toggleClass('is-disabled', useTheme);
		$('.sb-cc-color-row input').prop('disabled', useTheme);
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
	});
})(jQuery);
