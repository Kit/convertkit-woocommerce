/**
 * Initializes Select2 for <select> dropdowns which have the ckwc-select2 class.
 *
 * @since   1.4.3
 *
 * @author ConvertKit
 */

/**
 * Initializes Select2 for <select> dropdowns.
 *
 * @since 	1.4.3
 */
function ckwcSelect2Init() {
	(function ($) {
		$('.ckwc-select2').select2();
	})(jQuery);
}

jQuery(document).ready(function () {
	ckwcSelect2Init();
});
