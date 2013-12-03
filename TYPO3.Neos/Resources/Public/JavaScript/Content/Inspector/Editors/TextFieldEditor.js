define(
[
	'emberjs'
],
function(Ember) {
	Ember.TextSupport.reopen({
		attributeBindings: ['name', 'required', 'pattern', 'step', 'min', 'max']
	});

	return Ember.TextField.extend({
		classNames: ['neos-input-small'],
		validators: {}
	});
});