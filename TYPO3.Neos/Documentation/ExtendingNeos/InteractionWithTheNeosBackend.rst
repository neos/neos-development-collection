.. _interaction-with-the-neos-backend:

=================================
Interaction with the Neos backend
=================================

JavaScript events
=================

Some sites will rely on JavaScript initialization when the page is rendered,
typically on DocumentReady, and typically via jQuery or similar framework.
The Neos backend will however often reload the page via Ajax whenever a node
property is changed, and this might break functionality on sites relying on
custom JavaScript being executed on DocumentReady.

To fix this, the Neos backend will dispatch an event when the page is reloaded
via ajax, and site specific JavaScript can listen on this event to trigger
whatever code is needed to render the content correctly.

.. code-block:: javascript

  if (typeof document.addEventListener === 'function') {
  	document.addEventListener('Neos.PageLoaded', function(event) {
  		// Do stuff
  	}, false);
  }

The event object given, will always have the message and time set on
event.detail. Some events might have more attributes set.

Note that this only works in IE9+, Safari, Firefox and Chrome. For earlier IE
versions the events are not triggered.

The Neos backend will dispatch events that can be listened on when the following
events occur:

* **Neos.PageLoaded** Whenever the page reloads by Ajax.
* **Neos.PreviewModeActivated** When the backend switches from edit to preview mode.
* **Neos.PreviewModeDeactivated** When the backend switches from preview to edit mode.
* **Neos.ContentModuleLoaded** When the content module is loaded (i.e. when a user is logged in).
* **Neos.NodeCreated** When a new node was added to the document. The event has a reference to the DOM element in ``event.detail.element``. Additional information can be fetched through the element's attributes.
* **Neos.NodeRemoved** When a new node was removed from the document. The event has a reference to the DOM element in ``event.detail.element``. Additional information can be fetched through the element's attributes.
* **Neos.NodeSelected** When a node existing on the page is selected. The event has a reference to the DOM element in ``event.detail.element`` and the node model object in ``event.detail.node``. Additional information can be fetched through the node model.
* **Neos.LayoutChanged** When the content window layout changes (when panels that alter the body margin are opened/closed).
* **Neos.NavigatePanelOpened** When the navigate panel is opened.
* **Neos.NavigatePanelClosed** When the inspector panel is closed.
* **Neos.InspectorPanelOpened** When the navigate panel is opened.
* **Neos.InspectorPanelClosed** When the inspector panel is closed.
* **Neos.EditPreviewPanelOpened** When the edit/preview panel is opened.
* **Neos.EditPreviewPanelClosed** When the edit/preview panel is closed.
* **Neos.MenuPanelOpened** When the menu panel is opened.
* **Neos.MenuPanelClosed** When the menu panel is closed.
* **Neos.FullScreenModeActivated** When the backend switches to fullscreen mode.
* **Neos.FullScreenModeDeactivated** When the backend leaves the fullscreen mode.

Example of interacting with the selected node element using the ``NodeSelected`` event.

.. code-block:: javascript

  document.addEventListener('Neos.NodeSelected', function(event) {
  	var node = event.detail.node;
  	if (event.detail.node.get('nodeType') === 'Acme:Demo') {
  		console.log(node.getAttribute('title'), node.get('attributes.title'), node.$element);
  	}
  }, false);

Example of listening for the ``LayoutChanged`` event.

.. code-block:: javascript

  document.addEventListener('Neos.LayoutChanged', function(event) {
  	// Do stuff
  }, false);

.. tip::
  As an alternative to using the ``LayoutChanged`` event, listening to transition events on the body can be done.

  Example (using jQuery)::

    $('body').on('webkitTransitionEnd transitionend msTransitionEnd oTransitionEnd', function() {
    	// Do stuff
    });


Backend API
===========

The Neos backend exposes certain functions in a JavaScript API. These can be helpful to
customize the editing experience for special elements.

* **Typo3Neos.Content.reloadPage()** Reload the current page in the content module.
