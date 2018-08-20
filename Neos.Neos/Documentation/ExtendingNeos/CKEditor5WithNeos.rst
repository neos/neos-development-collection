=========================
Using ckeditor5 with neos
=========================
CKEditor5 is delivered with neos 4.0. Until next major release,
ckeditor4 is the default editor. That means, CKEditor5 must be activated
manually. Tho activate CKEditor5, default inline editor must be set up
in settings.yaml

::

    Neos:
      Neos:
        Ui:
          frontendConfiguration:
            defaultInlineEditor: 'ckeditor5'

CKEditor5 itself is just a container for different modules you can
seperatly activate or deactivate. To use the different modules
(plugins), you must add these settings to your node type definition:

::

    'Example.Package:Text':
      properties:
        text:
          ui:
            inlineEditable: true
            inline:
              editorOptions:
                placeholder: 'Text'
                autoparagraph: true
                formatting:
                    'strong': true          // bold
                    'em': true              // italic
                    'u': true               // underline
                    'sub': false            // subscript
                    'sup': false            // superscript
                    'del': false            // strike-through
                    'p': true
                    'h1': true
                    'h2': true
                    'h3': true
                    'h4': false
                    'h5': false
                    'h6': false
                    'code': false           // code formatting
                    'removeFormat': true    // remove format
                    'table': true           // table editor
                    'a': true               // link editor
                    'ul': true              // unordered list
                    'ol': true              // ordered list
                    'indent': false         // indent
                    'outdent': false        // outdent
                    'left': false
                    'center': false
                    'right': false
                    'justify': false

In formatting part, you can define, which plugins for CKEditor5 should
be loaded. This way, you can also load different CKEditor5 plugins for
different NodeTypes. For more information about this, you might check
the file ``manifest.richtextToolbar.js``.

Creating own CKEditor5 Plugins
==============================

To extend Neos inline editors, you will be needing CKEditor5 Plugins.
Please install yarn (which is actually a prerequirement for Neos.UI
Development Requirement.)

``npm install -g yarn``

Getting started
---------------

The most basic starter plugin has these components:

-  **index.js**: has only one purpose: deliver manifest.js via
   ``require('./manifest');``
-  **manifest.js**: modifies the ckeditor5. Here, you should add the
   button to CKEditor5 Toolbar & the plugin to CKEditor config.
-  **Button.js**: defines the button, which appears in the toolbar. You
   can create a new pop-up in Neos, or just simply call a command which
   you defined.
-  **Plugin.js**: is the heart of your plugin. Here you define different
   commands and also the initialize your plugin.

An already working example can be found on `Neos-ui-extensiblity-examples Github
<https://github.com/neos/neos-ui-extensibility-examples/tree/master/Resources/Private/CustomStylingForCkEditor>`__.
You can download this example and modify this to build your own example.


You also have to inform neos about the existence of your package. Please
add this to your settings.yaml

::

    Neos:
      Neos:
        Ui:
          resources:
            javascript:
              'Your.Package:YourModule':
                resource: resource://Your.Package/Public/YourModule/Plugin.js

The example above brings his own settings. You don't have to add any
additional configuration for that. Please check
``Configuration/Settings.yaml`` for further information.

Apply changes
-------------

Your changes must be built first. Please edit the values in package.json
and set up a proper name and buildTargetDirectory for your plugin. Get
the dependencies via ``yarn``\ or ``yarn install``. When finished, you
can use ``yarn build``\ for buildingyour package once or
``yarn watch``\ for the watcher.

Now your changes can be seen in frontend.

Working with CKEditor
---------------------
You can access to CKEditor5 API via using ``this.editor``\ in your ``Plugin.js``. After that, you can use all the tools available in
`CKEditor5 Documentation
<https://ckeditor.com/docs/ckeditor5/latest/api/>`__.
