define(
[
	'emberjs'
],
function (Ember) {
  // @TODO: Replace with ember-truth-helpers when ember-cli is available
  Ember.HTMLBars.helpers.registerHelper('eq', function(params) {
    return params[0] === params[1];
  });
});
