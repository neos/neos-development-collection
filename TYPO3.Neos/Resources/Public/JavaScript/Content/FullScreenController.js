/**
 * Controller for Full Screen Mode
 *
 * Singleton
 */
define(
[
	'Library/jquery-with-dependencies',
	'emberjs',
	'Shared/LocalStorage'
], function(
	$,
	Ember,
	LocalStorage
) {
	return Ember.Controller.extend({
		fullScreenActive: false,

		init: function() {
			if (LocalStorage.getItem('fullScreenActive') === true) {
				this.toggleFullScreen();
			}
		},

		toggleFullScreen: function() {
			this.toggleProperty('fullScreenActive');

			var that = this,
				isFullScreenActive = this.get('fullScreenActive'),
				fullScreenCloseClass = 'neos-full-screen-close';
			if (isFullScreenActive) {
				$('body')
					.append($('<div class="neos" />').addClass(fullScreenCloseClass).append($('<button class="neos-button neos-pressed"><i class="icon-resize-small"></i></button>'))
						.on('click', function() {
							that.toggleFullScreen();
						}));
				$(document).on('keyup.wireframe', function(e) {
					// TODO: check this stuff...
					if (e.keyCode === 27) {
						that.set('fullScreenActive', false);
					}
				});
			} else {
				$('body > .' + fullScreenCloseClass).remove();
				//$(document).off('keyup.wireframe');
			}
			$('body').toggleClass('neos-full-screen neos-controls');
		},

		_storeFullScreenMode: function() {
			LocalStorage.setItem('fullScreenActive', this.get('fullScreenActive'));
		}.observes('fullScreenActive')
	}).create();
});