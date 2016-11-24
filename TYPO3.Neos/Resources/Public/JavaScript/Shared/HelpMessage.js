/**
 * Prepare help messages
 */
define([
	'Library/marked',
	'Shared/I18n'
],
	function(Marked, I18n) {
	/**
	 * @param {array} help message configuration
	 * @param {string} alt text for thumbnail
	 * @return {string}
	 */
	return function(helpConfiguration, altText) {
		helpMessage = '';
		if (helpConfiguration.message) {
			helpMessage = Marked(I18n.translate(helpConfiguration.message)).replace(/<a\s(.*?)>/g, "<a $1 target='_blank'>");
		}
		if (helpConfiguration.thumbnail) {
			helpMessage = '<img alt=' + altText + ' src="' + helpConfiguration.thumbnail + '" />' + helpMessage;
		}
		return helpMessage;
	};
});
