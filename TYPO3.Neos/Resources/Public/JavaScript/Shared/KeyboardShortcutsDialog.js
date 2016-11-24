define(
  [
    'emberjs',
    'Library/jquery-with-dependencies',
    'text!./KeyboardShortcutsDialog.html',
    'Shared/Navigator'
  ],
  function (Ember, $, template, Navigator) {
    var KeyboardShortcutsDialog = Ember.Object.create({
      _view: null,
      modKey: Navigator.modKey(),

      show: function () {
        var that = this;
        if (this.get('_view')) {
          return;
        }

        modKey = Navigator.modKey();

        Mousetrap.bind('esc', function() {
          that.hide();
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
