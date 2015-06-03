define(
[
	'emberjs',
	'Content/Components/Button'
],
function(Ember, Button) {

	/**
	 * Controller responsible for displaying a View to Secondary Inspector; and a
	 * helping Button which is pushed when the secondary inspector shows the view
	 * assigned to the button.
	 *
	 * Usage: inside the template, add
	 * {{view SecondaryInspectorController.SecondaryInspectorButton viewClassBinding="view.[yourViewClassNameForSecondaryInspector]"}}
	 *
	 * Optionally, "target" and "action" can be set on the SecondaryInspectorButton; then the target action is called
	 * directly before the UI is shown.
	 *
	 * Singleton.
	 */
	var SecondaryInspectorController = Ember.Object.extend({

		/**
		 * Internal. The view class which is displayed in SecondaryInspectorView
		 */
		_viewClass: Ember.View.extend(),

		/**
		 * if TRUE, the secondary inspector is currently visible.
		 */
		_visible: false,

		/**
		 * Check if the viewClass (which is passed as parameter) is currently visible
		 * and active or not. (returns boolean)
		 */
		_viewIsActive: function(viewClass) {
			if (!this.get('_visible')) {
				return false;
			}

			return this.get('_viewClass') === viewClass;
		},

		/**
		 * Show the given view class if it is not active; or hide it otherwise.
		 */
		toggle: function(viewClass) {
			if (this._viewIsActive(viewClass)) {
				this.hide();
			} else {
				this._show(viewClass);
			}
		},

		_show: function(viewClass) {
			this.set('_viewClass', viewClass);
			this.set('_visible', true);
		},

		/**
		 * Always hide the given view class
		 */
		hide: function() {
			this.set('_visible', false);
		}
	}).create();

	/**
	 * Helper button view which shows or hides a view (specified by viewClass) inside
	 * the secondary inspector.
	 */
	SecondaryInspectorController.SecondaryInspectorButton = Button.extend({

		/**
		 * API: the class name which is
		 */
		viewClass: null,

		classNameBindings: ['_currentViewIsActive:neos-pressed'],
		_secondaryInspectorController: SecondaryInspectorController,

		_currentViewIsActive: function() {
			return SecondaryInspectorController._viewIsActive(this.get('viewClass'));
		}.property('_secondaryInspectorController._viewClass', '_secondaryInspectorController._visible', 'viewClass'),

		triggerAction: function() {
			if (this.get('_currentViewIsActive')) {
				SecondaryInspectorController.hide();
			} else {
				this._super();
				SecondaryInspectorController._show(this.get('viewClass'));
			}
		}
	});

	return SecondaryInspectorController;
});