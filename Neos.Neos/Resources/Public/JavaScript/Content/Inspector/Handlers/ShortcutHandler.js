define(
  [
    'emberjs'
  ],
  function (Ember) {
    return Ember.Object.extend({
      handle: function (listeningEditor, newValue, propertyName, listenerName) {
        switch (propertyName) {
          case 'target':
            if (newValue !== null && newValue !== '') {
              listeningEditor.set('value', 'selectedTarget');
            }
            break;
          case 'targetMode':
            if (newValue !== 'selectedTarget') {
              listeningEditor.set('value', '');
            }
            break;
        }
      }
    });
  });
