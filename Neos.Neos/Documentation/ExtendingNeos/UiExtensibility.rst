.. _ui-extensibility:

=====================================
Neos User Interface Extensibility API
=====================================

At the heart of the Neos UI lies the system of registries – key-value stores that contain system components. The registries are populated through the `manifest` API command that is exposed through the neos-ui-extensibility package.

Inspector-specific Registries
=============================

Editors
-------

Way to retrieve::

  globalRegistry.get('inspector').get('editors')

Contains all inspector editors. The key is an editor name (such as
`Neos.Neos/Inspector/Editors/SelectBoxEditor`), and the values
are objects of the following form:

.. code-block:: javascript

  {
    component: TextInput // the React editor component to use. Required
    hasOwnLabel: true|false // whether the component renders the label internally or not
  }

Component Wiring
~~~~~~~~~~~~~~~~

Every component gets the following properties (see `EditorEnvelope/index.js`)

- `identifier`: an identifier which can be used for HTML ID generation
- `label`: the label
- `value`: the value to display
- `propertyName`: name of the node property to edit
- `options`: additional editor options
- `commit`: a callback function when the content changes.

  - 1st argument: the new value
  - 2nd argument (optional): an object whose keys are *saveHooks* to be triggered, the
    values are hook-specific options. Example:
    ``{'Neos.UI:Hook.BeforeSave.CreateImageVariant': nextImage}``
- `renderSecondaryInspector`:

  - 1st argument: a string identifier of the second inspector; used to implement toggling
    of the inspector when calling this method twice.
  - 2nd argument: a callback function which can be used to render the secondary inspector.
    The callback function should return the secondary inspector content itself; or "undefined/null"
    to close the secondary inspector.
    Example usage: ``props.renderSecondaryInspector('IMAGE_CROPPING', () => <MySecondaryInspectorContent />)``

Secondary Editors
-----------------

Way to retrieve::

  globalRegistry.get('inspector').get('editors')

Contains all secondary inspector editors, which can be used to provide additional, more complex
functionality that needs more space of the UI than the inspector panel can provide itself.

Use it like the registry for editors.

Views
-----

Way to retrieve::

  globalRegistry.get('inspector').get('views')

Contains all inspector views.

Use it like the registry for editors.

Save Hooks
----------

Way to retrieve::

  globalRegistry.get('inspector').get('saveHooks')

Sometimes, it is needed to run code when the user presses "Apply" inside the Inspector.

Example: When the user cropped a new image, on "Apply", a new `imageVariant` must be created on
the server, and then the identity of the new `imageVariant` must be stored inside the value of
the image.

The process is as follows:

- When an editor wants its value to be post-processed, it calls ``props.commit(newValue, {hookName: hookOptions})``
- Then, when pressing "Apply" in the UI, the hookNames are resolved inside this `saveHooks` registry.

Hook Definitions
~~~~~~~~~~~~~~~~

Every entry inside this registry is a function of the following signature:

.. code-block:: javascript

  (valueSoFar, hookOptions) => {
    return new value; // can also return a new Promise.
  }

Validators
==========

Way to retrieve::

  globalRegistry.get('validators')

Contains all server feedback handlers.

The key is the server-feedback-handler-type, and the value is a function with the following signature:

.. code-block:: javascript

  (feedback, store) => {
    // do whatever you like here
  }

Frontend Configuration
======================

Any settings under `Neos.Neos.Ui.frontendConfiguration` would be available here.

Might be used also for third-party packages to deliver own settings to the UI, but this is still experimental.

Settings from each package should be prefixed to avoid collisions (unprefixed settings are reserved for the core UI itself), e.g.:

.. code-block:: yaml

  Neos:
    Neos:
      Ui:
        frontendConfiguration:
          'Your.Own:Package':
            someKey: someValue

Then it may be accessed as::

  globalRegistry.get('frontendConfiguration').get('Your.Own:Package').someKey

Inline Editors
==============

Way to retrieve::

  globalRegistry.get('inlineEditors')

Each key in this registry should be a unique identifier for an inline editor, that can be referenced in a node type configuration.

Each entry in this registry is supposed to consist of an object with the following structure:

.. code-block:: javascript

  {
    bootstrap: myBootstrapFunction,
    createInlineEditor: myInlineEditorFactoryFunction
  }

`bootstrap` is called only once during the global initialization of the guest frame. It is not required
to do anything in this function, but it is possible to prepare the guest frame environment, if any
global variables must be defined or other initialization routines must be run in order for the inline
editor to work.

`bootstrap` will receive an API Object as its first parameter, with the following methods:

- `setFormattingUnderCursor`: Will dispatch the respective action from the `@neos-project/neos-ui-redux-store`
  package (`actions.UI.ContentCanvas.setFormattingUnderCursor`)
- `setCurrentlyEditedPropertyName`: Will dispatch the respective action from the `@neos-project/neos-ui-redux-store`
  package (`actions.UI.ContentCanvas.setCurrentlyEditedPropertyName`)

`createInlineEditor` is called on every DOM node in the guest frame that represents an editable property.
It is supposed to handle the initialization and display of an inline editor.

`createInlineEditor` will receive an object as its first parameter, with the following properties:

- `propertyDomNode`: The DOM node associated with the editable property
- `propertyName`: The name of the editable property
- `contextPath`: The contextPath of the associated node
- `nodeType`: The nodeType of the associated node
- `editorOptions`: The configuration for this inline editor
- `globalRegistry`: The global registry
- `persistChange`: Will dispatch the respective action from `@neos-project/neos-ui-redux-store` package
  (`actions.Changes.persistChanges`)

CKEditor5-specific registries
=============================

The integration of CKeditor5 is dead simple and tries to introduce a minimal amount of abstractions
on top of CKeditor5. There are only two registries involved in configuring it: `config` and
`richtextToolbar`

Configuration of CKeditor5
--------------------------

Way to retrieve::

  globalRegistry.get('ckEditor5').get('config')

In CKE all things are configured via a single configuration object: plugins, custom configs, etc (@see https://docs.ckeditor.com/ckeditor5/latest/builds/guides/integration/configuration.html)

This registry allows to register a custom configuration processor that takes a configuration object, modifies it and returns a new one. Example::

  config.set('doSomethingWithConfig' (ckeConfig, editorOptions) => {
    ckeConfig.mySetting = true;
    return ckeConfig;
  })

That is all you need to know about configuring CKE in Neos,
Refer to CKeditor5 documentation for more details on what you can do with it: https://docs.ckeditor.com/ckeditor5/latest/index.html

Richtext Toolbar
~~~~~~~~~~~~~~~~

Way to retrieve::

  globalRegistry.get('ckEditor5').get('richtextToolbar')

Contains the Rich Text Editing Toolbar components.

Buttons in the Rich Text Editing Toolbar are just plain React components.

The only way for these components to communicate with CKE is via its commands mechanism
(@see https://docs.ckeditor.com/ckeditor5/latest/framework/guides/architecture/core-editor-architecture.html#commands)

Some commands may take arguments. Commands also contain state that is serialized into
`formattingUnderCursor` redux state. Commands are provided and handled by CKE plugins, which may be
registered via the configuration registry explained above.

The values are objects of the following form::

    {
        commandName: 'bold' // A CKE command that gets dispatched
        commandArgs: [arg1, arg2] // Additional arguments passed together with a command
        component: Button // the React component being used for rendering
        isVisible: (editorOptions, formattingUnderCursor) => true // A function that decides is the button should be visible or not
        isActive: (formattingUnderCursor, editorOptions) => true // A function that decides is the button should be active or not
        callbackPropName: 'onClick' // Name of the callback prop of the Component which is
                                    fired when the component's value changes.

        // all other properties are directly passed on to the component.
    }

CKEditor4-specific registries
=============================

Formatting rules
----------------

Way to retrieve::

  globalRegistry.get('ckEditor').get('formattingRules')

Contains the possible styles for CKEditor.

Enabled Styles
~~~~~~~~~~~~~~

The actual *enabled* styles are determined by the NodeTypes configuration of the property. This means,
that if the node is configured in `NodeTypes.yaml` using:

.. code-block:: yaml

  properties:
    [propertyName]:
      ui:
        inline:
          editorOptions:
            formatting:
              strong: true

then the "strong" key inside this registry is actually enabled for the editor.

For backwards compatibility reasons, the formatting-and-styling-registry *KEYS* must match the "pre-React"
UI, if they existed beforehand.


Configuration of CKEditor
~~~~~~~~~~~~~~~~~~~~~~~~~

With this config, CKEditor itself is controlled:

- the Advanced Content Filter (ACF) is configured, thus determining which markup is allowed in the editors
- which effect a button action actually has.

Currently, there exist three possible effects:

- triggering a command
- setting a style
- executing arbitrary code

Configuration Format
~~~~~~~~~~~~~~~~~~~~

NOTE: one of "command" or "style" must be specified in all cases.

- `command` (string, optional). If specified, this CKEditor command is triggered; so the command string
  is known by CKEditor in the "commands" section: http://docs.ckeditor.com/#!/api/CKEDITOR.editor-method-getCommand
- `style` (object, optional). If specified, this CKEditor style is applied. Expects a style description
  adhering to CKEDITOR.style(...), so for example: `{ style: {element: 'h1'}`
- `config` (function, optional): This function needs to adjust the CKEditor config to e.g. configure ACF
  correctly. The function gets passed in the config so-far, AND the configuration from the node type
  underneath `ui.inline.editorOptions.formatting.[formatingRuleName]` and needs to return the modified
  config. See "CKEditor Configuration Helpers" below for helper functions.
- `extractCurrentFormatFn` (function, optional): If specified, this function will extract the current
  format. The function gets passed the currend "editor" and "CKEDITOR".
- `applyStyleFn` (function, optional): This function applies a style to CKEditor.
  Arguments: formattingOptions, editor, CKEDITOR.

CKEditor Configuration Helpers
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

- `config: registry.ckEditor.formattingRules.config.addToFormatTags('h1')`: adds the passed-in tag to the
  `format_tags` configuration option of CKEditor.
- `registry.ckEditor.formattingRules.config.add('Strong')`: adds the passed-in *Button Definition Name*
  to the ACF configuration (automatic mode). This means the button names are standard CKEditor config
  buttons, like "Cut,Copy,Paste,Undo,Redo,Anchor".

Richtext Toolbar
----------------

Contains the Rich Text Editing Toolbar components.

The values are objects of the following form::

  {
    formattingRule: 'h1' // References a key inside "formattingRules"
    component: Button // the React component being used for rendering
    callbackPropName: 'onClick' // Name of the callback prop of the Component which is fired when the component's value changes.

    // all other properties are directly passed on to the component.
  }

Component wiring
~~~~~~~~~~~~~~~~

- Each toolbar component receives all properties except "formattingRule" and "component" directly as props.
- Furthermore, the "isActive" property is bound, which is a boolean flag defining whether the text style
  referenced by "formatting" is currently active or not.
- Furthermore, the callback specified in "callbackPropName" is wired, which toggles the value.

For advanced use-cases; also the "formattingRule" is bound to the component; containing a formatting-rule identifier (string).
If you need this, you'll most likely need to listen to `selectors.UI.ContentCanvas.formattingUnderCursor` and extract your relevant information manually.

Plugins
-------

Way to retrieve::

  globalRegistry.get('ckEditor').get('plugins')

Contains custom plugins for CkEditor.

.. code-block:: javascript

  plugins.set('plugin_key', {
      initFn: pluginInitFunction
  });

`pluginInitFunction` is passed from CKEDITOR as the first argument.
In that function you may register your plugin with CKEditor via its API (`CKEDITOR.plugins.add`).
Take custom plugins as examples.

Data Loaders
============

Way to retrieve::

  globalRegistry.get('dataLoaders')

A "Data Loader" controls asynchronous loading of secondary data, which is used in all kinds of Select / List boxes in the backend.

Example of data which is loaded through a data loader:

- Link Labels (in the inline link editor)
- Reference / References editor
- Data Sources in the Select Editor

Each Data Loader can have a slightly different API, so check the "description" field of each data loader when using it. It is up to the data loaders to implement caching internally.

Normally, each data loader exposes the following methods:

.. code-block:: javascript

  resolveValue(options, identifier) {
    // "options" is a DataLoader-specific object.
    // returns Promise with [{identifier, label}, ...] list; where "identifier" was resolved to the actual object represented by "identifier".
  }

  search(options, searchTerm) {
    // "options" is a DataLoader-specific object.
    // returns Promise with [{identifier, label}, ...] list; these are the objects displayed in the selection dropdown.
  }

Containers
==========

Way to retrieve::

  globalRegistry.get('containers')

The whole user interface is built around container components. They are registered through the containers registry. Below you will find an example on how to replace the PageTree container with your custom container:

.. code-block:: javascript

  manifest('Example', {}, globalRegistry => {
    const containerRegistry = globalRegistry.get('containers');
    containerRegistry.set('LeftSideBar/Top/PageTreeToolbar', () => null);
    containerRegistry.set('LeftSideBar/Top/PageTreeSearchbar', () => null);
    containerRegistry.set('LeftSideBar/Top/PageTree', FlatNavContainer);
  });

Server Feedback Handlers
========================

Way to retrieve::

  globalRegistry.get('serverFeedbackHandlers')

Contains all server feedback handlers.

The key is the server-feedback-handler-type, and the value is a function with the following signature:

.. code-block:: javascript

  (feedback, store) => {
    // do whatever you like here :-)
  }

Reducers
========

Way to retrieve::

  globalRegistry.get('reducers')

Allows to register custom reducers for your plugin.
It is probably a bad idea to override any of the existing reducers.

Sagas
=====

Way to retrieve::

  globalRegistry.get('sagas')

Allows to register custom sagas for your plugin.
It is probably a bad idea to override any of the existing reducers.

Example:

.. code-block:: javascript

  function* watchNodeFocus() {
    yield takeLatest(actionTypes.CR.Nodes.FOCUS, function* (action) {
      yield put(actions.UI.FlashMessages.add(
        'testMessage',
        'Focused: ' + action.payload.contextPath,
        'success'
      ));
    });
  }
  manifest('The.Demo:Focus', {}, globalRegistry => {
    const sagasRegistry = globalRegistry.get('sagas');
    sagasRegistry.set('The.Demo/watchNodeFocus', {saga: watchNodeFocus});
  });

 
