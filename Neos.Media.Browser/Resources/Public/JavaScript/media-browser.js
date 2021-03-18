window.addEventListener("DOMContentLoaded", (event) => {
	window.jQuery(function () {
		jQuery('[data-neos-toggle="popover"]').popover();
		jQuery('[data-neos-toggle="tooltip"]').tooltip();
	});
});
