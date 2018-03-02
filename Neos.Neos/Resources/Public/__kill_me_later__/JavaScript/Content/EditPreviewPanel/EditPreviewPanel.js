define(
[
	'emberjs',
	'Library/jquery-with-dependencies',
	'Library/underscore',
	'./EditPreviewPanelController',
	'Shared/EventDispatcher',
	'text!./EditPreviewPanel.html'
], function(
	Ember,
	$,
	_,
	EditPreviewPanelController,
	EventDispatcher,
	template
) {
	return Ember.View.extend({
		elementId: 'neos-edit-preview-panel',
		template: Ember.Handlebars.compile(template),
		isVisibleBinding: 'controller.visible',

		controller: EditPreviewPanelController,

		onEditPreviewPanelModeChanged: function() {
			if (this.$()) {
				var that = this;
				this.$().one('webkitTransitionEnd transitionend msTransitionEnd oTransitionEnd', function() {
					if (that.get('controller.editPreviewPanelMode') === true) {
						EventDispatcher.triggerExternalEvent('Neos.EditPreviewPanelOpened');
					} else {
						EventDispatcher.triggerExternalEvent('Neos.EditPreviewPanelClosed');
					}
					EventDispatcher.triggerExternalEvent('Neos.LayoutChanged');
				});
			}
			if (this.get('controller.editPreviewPanelMode') === true) {
				$('body').addClass('neos-edit-preview-panel-open');
			} else {
				$('body').removeClass('neos-edit-preview-panel-open');
			}
		}.observes('controller.editPreviewPanelMode').on('init'),

		didInsertElement: function() {
			var editModes = this.get('controller.editingModes'),
				previewModes = this.get('controller.previewModes'),
				activeItems = [
					Math.max(0, editModes.indexOf(editModes.findProperty('active'))),
					Math.max(0, previewModes.indexOf(previewModes.findProperty('active')))
				];
			this.$('.neos-scroll-container > .neos-frame').each(function(index) {
				$(this).sly({
					horizontal: 1,

					itemNav: 'centered',
					itemSelector: 'li',
					smart: 1,

					scrollBy: 1,
					easing: 'swing',
					speed: 200,
					mouseDragging: 1,
					touchDragging: 1,

					draggedClass: 'neos-dragged',
					activeClass: 'neos-active',
					disabledClass: 'neos-disabled',

					prevPage: $('.neos-scroll-previous', this),
					nextPage:  $('.neos-scroll-next', this),

					startAt: activeItems[index]
				});
			});
			var that = this;
			this.$().get(0).addEventListener('transitionend', function() {
				that._reloadSliders();
			}, false);
			window.addEventListener('resize', _.debounce(function() {
				that._reloadSliders();
			}, 200), false);
		},

		_reloadSliders: function() {
			if (this.get('controller.editingModes').length > 0 || this.get('controller.previewModes').length > 0) {
				this.$('.neos-scroll-container > .neos-frame').sly('reload');
			}
		}
	});
});