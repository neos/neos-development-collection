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
		controller: SecondaryInspectorController
	});

	return SecondaryInspectorView;
});
