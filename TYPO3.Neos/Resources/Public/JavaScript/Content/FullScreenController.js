/**
 * Controller for Full Screen Mode
 *
 * Singleton
 */
define(
[
	'Library/jquery-with-dependencies',
	'emberjs',
	'Shared/LocalStorage',
	'LibraryExtensions/Mousetrap'
], function(
	$,
	Ember,
	LocalStorage,
	Mousetrap
) {
	return Ember.Controller.extend({
		fullScreenMode: false,

		init: function() {
			if (LocalStorage.getItem('fullScreenMode') === true) {
				this.toggleFullScreen();
			}
		},

		toggleFullScreen: function() {
			this.toggleProperty('fullScreenMode');

			var fullScreenCloseClass = 'neos-full-screen-close';
			if (this.get('fullScreenMode')) {
				var that = this;
				$('body')
					.append($('<div class="neos" />').addClass(fullScreenCloseClass).append($('<button class="neos-button neos-pressed"><i class="icon-resize-small"></i></button>'))
					.on('click', function() {
						that.toggleFullScreen();
					}));
				Mousetrap.bind('esc', function() {
					that.toggleFullScreen();
				});
			} else {
				$('body > .' + fullScreenCloseClass).remove();
				Mousetrap.unbind('esc');
			}
		},

		onFullScreenModeChanged: function() {
			LocalStorage.setItem('fullScreenMode', this.get('fullScreenMode'));
			var fullScreenClassName = 'neos-full-screen',
				controlClassName = 'neos-controls';
			if (this.get('fullScreenMode') === true) {
				$('body').removeClass(controlClassName).addClass(fullScreenClassName);
			} else {
				$('body').addClass(controlClassName).removeClass(fullScreenClassName);
			}
		}.observes('fullScreenMode').on('init')
	}).create();
});