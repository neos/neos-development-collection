define(
[
	'emberjs'
],
function(Ember) {
	Ember.TextSupport.reopen({
		attributeBindings: ['placeholder', 'disabled', 'maxlength', 'tabindex', 'readonly', 'autofocus', 'form', 'selectionDirection', 'spellcheck', 'required', 'title', 'autocapitalize', 'autocorrect']
	});

	return Ember.TextField.extend({
		classNames: ['neos-input-small'],
		validators: {}
	});
});