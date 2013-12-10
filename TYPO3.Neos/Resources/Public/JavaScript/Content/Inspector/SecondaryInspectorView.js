define(
[
	'emberjs',
	'./SecondaryInspectorController',
	'text!./SecondaryInspectorView.html'
],
function(Ember, SecondaryInspectorController, template) {

	/**
	 * This class is a container for the "secondary inspector", showing e.g the image
	 * cropping, media browser or HTML editor. It is controlled through SecondaryInspectorController.
	 */
	var SecondaryInspectorView = Ember.View.extend({
		template: Ember.Handlebars.compile(template),
		classNames: ['neos-secondary-inspector'],
		classNameBindings: ['controller._visible:neos-secondary-inspector-open'],
		controller: SecondaryInspectorController,

		// *Warning*: the contentView MUST be a (computed) property of the VIEW, and should NOT
		// be created inside the controller. This was done before, and was the source
		// of a hard-to-track error which prevented the UI from reacting to URL changes
		// (f.e. when using forward- and back buttons). When the contentView is a property
		// of the *view*, it is part of the normal view lifecycle and is created and destructed
		// automatically.
		contentView: function() {
			return this.get('controller._viewClass').create();
		}.property('controller._viewClass')
	});

	return SecondaryInspectorView;
});