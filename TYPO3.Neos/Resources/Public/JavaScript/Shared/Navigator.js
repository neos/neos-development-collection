define(
  [
    'emberjs'
  ],
  function (Ember) {
    return Ember.Object.create({
      isMac: function() {
        return navigator.appVersion.indexOf("Mac") != -1;
      },
      modKey: function() {
        return this.isMac() ? 'cmd' : 'ctrl';
      }
    });
  }
);
