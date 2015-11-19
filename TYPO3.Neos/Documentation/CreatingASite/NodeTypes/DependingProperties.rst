.. _depending-properties:

Depending Properties
====================

.. note:: This API is still experimental, we might change details about the handler
   signature and implementation to reduce the amount of exposed internal code. The
   UI code is undergoing major changes right now which also might make adjustments
   necessary.

Sometimes it might be necessary to depend one property editor on another,
such as two select boxes where one selection is not meaningful without the other.
For that you can setup listeners that get triggered each time a property changes.

Here is an example of the configuration:

.. code-block:: yaml

  'Some.Package:NodeType':
    properties:
      border-width:
        type: integer
      border-color:
        type: string
        ui:
          label: i18n
          inspector:
            editorListeners:
              activeWithNonEmptyValue:
                property: 'border-width'
                handler: 'Some.Package/Handlers/BorderHandler'
                handlerOptions:
                  something: true

This sets up a listener named ``activeWithNonEmptyValue``. The name can be freely chosen.
This allows to override specific listeners in other packages by refering to that name.
The ``property`` setting defines the name of the property on the same Node that will be
observed. That means any change to this property will trigger the configured ``handler``.

Configuring the ``handler`` means defining a require path to the handler object just like
with :ref:`custom-editors` for properties. Namespaces can be registered like this:

.. code-block:: yaml

  TYPO3:
    Neos:
      userInterface:
        requireJsPathMapping:
          'Some.Package/Handlers': 'resource://Some.Package/Public/Scripts/Inspector/Handlers'

The handler should be compatible to RequireJS and be an ``Ember.Object`` that has a ``handle`` function.
The ``handlerOptions`` configured for the listener in the NodeType configuration will be given to the
handler upon creation and are available in the ``handle`` method.

A code example for a handler:

.. code-block:: js

  define(
  [
      'emberjs'
  ],
  function (Ember) {
      return Ember.Object.extend({
          handle: function(listeningEditor, newValue, property, listenerName) {
              if (this.get('something') === true) {
                  listeningEditor.set('disabled', (newValue === null || newValue === ''));
              }
          }
      });
  });

The handle function receives the following arguments:

- ``listeningEditor`` - The property editor this listener is configured for, in the above example it will
  be the ``border-color`` editor.
- ``newValue`` will be the value of the observed property, which is the ``border-width`` probpery in the
  above example.
- ``property`` is the name of the observed property, literally ``border-width`` in the above example.
- ``listenerName`` is the configured name of the listener in question, literally ``activeWithNonEmptyValue``
  in the example above.
