.. _depending-properties:

Depending Properties
====================

.. note:: This API is still experimental, we might change details about the handler
   signature and implementation to reduce the amount of exposed internal code, also
   the UI code is undergoing major changes right now which also might make
   adjustments necessary.

Sometimes it might be necessary to depend one property editor on another,
such as two select boxes where one selection is not meaningful without the other.
For that you can setup listeners that get triggered each time a property changes.

The general configuration would look like this::

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

Now this would setup a listener named ``activeWithNonEmptyValue``, a name which
you can decide on. This allows you to override specific listeners in other packages by
refering to that name.
The ``property`` setting defines the name of the property on the same Node that will be
observed. That means any change to this property will trigger the configured ``handler``.
Configuring the ``handler`` means defining a require path to the handler object just like
with custom property editors :ref:`custom-editors`.

Namespaces can be registered like this, as with validators::

	TYPO3:
	  Neos:
	    userInterface:
	      requireJsPathMapping:
	        'Some.Package/Handlers': 'resource://Some.Package/Public/Scripts/Inspector/Handlers'

The handler should be compatible to RequireJS and be an Ember.Object that has a ``handle`` function.
The ``handlerOptions`` configured for the listener in the NdoeType configuration will be given to the
handler and available in the handle method.

A code example for a handler::

	define(
	[
	    'emberjs',
	],
	function (Ember) {
	    return Ember.Object.extend({
	        handle: function(listeningEditor, newValue, property, listenerName) {
	            listeningEditor.set('disabled', (newValue === null || newValue === ''));
	        }
	    });
	});

The handle function receives the following arguments:

 - listeningEditor - The property editor this listener is configured for, in the above example it will
   be the ``border-color`` editor.
 - newValue will be the value of the observed property, which is the ``border-width`` probpery in the
   above example.
 - property is the name of the observed property, literally ``border-width`` in the above example.
 - listenerName is the configured name of the listener in question, literally ``activeWithNonEmptyValue``
   in the example above.