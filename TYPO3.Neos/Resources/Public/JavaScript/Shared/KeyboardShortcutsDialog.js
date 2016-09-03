define(
  [
    'emberjs',
    'Library/jquery-with-dependencies',
    'text!./KeyboardShortcutsDialog.html'
  ],
  function (Ember, $, template) {
    var KeyboardShortcutsDialog = Ember.Object.create({
      _view: null,
      ctrlCmd: 'ctrl',

      show: function () {
        var that = this;
        if (this.get('_view')) {
          return;
        }

        if (navigator.appVersion.indexOf("Mac") != -1) {
          ctrlCmd = 'cmd';
        }

        Mousetrap.bind('esc', function() {
          that.hide();
          return false;
        });

        this.set(
          '_view',
          Ember.View.create({
            template: Ember.Handlebars.compile(template),
            classNames: ['neos-overlay-component'],
            hide: function () {
              KeyboardShortcutsDialog.hide();
            }
          }).appendTo('#neos-application')
        );
      },

      hide: function () {
        if (this.get('_view')) {
          this.get('_view').remove();
          this.set('_view', null);
          Mousetrap.unbind('esc');
        }
      }
    });

    return KeyboardShortcutsDialog;
  }
);
