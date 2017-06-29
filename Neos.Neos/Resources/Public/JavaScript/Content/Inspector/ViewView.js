/**
 * View view
 */
define(
[
	'emberjs',
	'Library/jquery-with-dependencies',
	'Shared/I18n'
], function(
	Ember,
	$,
    I18n
) {
	return Ember.ContainerView.extend({
		viewDefinition: null,
		classNameBindings: ['viewClassName'],
		viewClassName: '',

		init: function() {
			this._super();
			this._loadView();
		},

		_loadView: function() {
			var that = this,
				viewDefinition = this.get('viewDefinition'),
				view;

			var viewOptions = $.extend(true,
				{
					elementId: viewDefinition.elementId,
					key: viewDefinition.key,
					label: I18n.translate(viewDefinition.label),
					icon: viewDefinition.icon,
					inspectorBinding: this.inspectorBinding
				},
				Ember.get(viewDefinition, 'viewOptions') || {}
			);

			view = Ember.get(viewDefinition, 'view');
			Ember.assert('View for "' + viewDefinition.key + '" not defined!', !!view);

			// Convert last part of editor path into dashed class name
			var viewName = view.substring(view.lastIndexOf('/') + 1);
			this.set('viewClassName', viewName.replace(/([a-z\d])([A-Z])/g, '$1-$2').toLowerCase());

			require({context: 'neos'}, [view], function(viewClass) {
				Ember.run(function() {
					if (!that.isDestroyed) {
						// It might happen that the editor was deselected before the require() call completed; so we
						// need to check again whether the view has been destroyed in the meantime.
						var view = viewClass.create(viewOptions);
						that.set('currentView', view);
					}
				});
			});
		}
	});
});