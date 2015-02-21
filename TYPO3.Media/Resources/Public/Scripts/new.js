(function($) {
	$(function() {
		$('#resource').change(function() {
			$('label[for="resource"]').text($(this).val().split('\\').pop());
		});
	});
})(jQuery);