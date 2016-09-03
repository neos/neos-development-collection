define(
  [
    'Library/jquery-with-dependencies',
    'emberjs',
    'LibraryExtensions/Mousetrap',
    'InlineEditing/ContentCommands',
    '../EditPreviewPanel/EditPreviewPanelController',
    'Shared/KeyboardShortcutsDialog',
    '../FullScreenController',
    'Shared/Configuration'
  ],
  function ($, Ember, Mousetrap, ContentCommands, EditPreviewPanelController, KeyboardShortcutsDialog, FullScreenController, Configuration) {
    return Ember.Object.create({
      initializeContentModuleEvents: function () {
        var that = this;

        Mousetrap.bind(['mod+shift+a'], function () {
          ContentCommands.create('after');
          return false;
        });

        Mousetrap.bind(['mod+shift+v'], function () {
          ContentCommands.paste();
          return false;
        });

        Mousetrap.bind(['mod+shift+c'], function () {
          ContentCommands.copy();
          return false;
        });

        Mousetrap.bind(['mod+shift+x'], function () {
          ContentCommands.cut();
          return false;
        });

        Mousetrap.bind(['mod+shift+d'], function () {
          ContentCommands.remove();
          return false;
        });

        $('.neos-open-shortcuts').on('click', function (e) {
          e.preventDefault();
          $(this).parents('.neos-user-menu').toggleClass('neos-open');
          KeyboardShortcutsDialog.show();
        });

        Mousetrap.bind('?', function () {
          KeyboardShortcutsDialog.show();
          return false;
        });

        Mousetrap.bind(['mod+e'], function () {
          EditPreviewPanelController.toggleEditPreviewPanelMode();
          return false;
        });

        Mousetrap.bind(['mod+h'], function () {
          FullScreenController.toggleFullScreen();
          return false;
        });

        this.waitForSchema(function () {
          that.waitForAloha(that.initAlohaEventListener);
        });
      },
      initAlohaEventListener: function (Aloha) {
        Aloha.ready(function () {
          Aloha.bind('aloha-editable-activated', function (event) {
            Mousetrap.unbind('?');
          });

          Aloha.bind('aloha-editable-deactivated', function (event) {
            Mousetrap.bind('?', function () {
              KeyboardShortcutsDialog.show();
            });
          });
        });
      },
      waitForSchema: function (callback) {
        if (Configuration.get('Schema') === undefined) {
          Configuration.addObserver('Schema', callback);
        } else {
          callback();
        }
      },
      waitForAloha: function (callback) {
        if (window.Aloha === undefined || window.Aloha.__shouldInit) {
          require({
            context: 'aloha'
          }, [
            'aloha'
          ], callback);
        } else {
          callback(Aloha);
        }
      }
    });
  });
