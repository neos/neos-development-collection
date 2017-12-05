define(
[
	'Library/mousetrap'
],
function(Mousetrap) {
	return (function(Mousetrap) {
		Mousetrap.stopCallback = function(e, element, combo, sequence) {
			return false;
		};

		return Mousetrap;
	})(Mousetrap);
});