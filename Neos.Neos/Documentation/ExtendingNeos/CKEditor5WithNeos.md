=========================
Using CKEditor 5 with neos
=========================
CKEditor 5 is delivered with neos-ui 1.3. Until neos-ui 2.0,
CKEditor 4 will stay as the default editor. That means, CKEditor 5 must be activated
manually. Tho activate CKEditor 5, default inline editor must be set up
in settings.yaml


    Neos:
      Neos:
        Ui:
          frontendConfiguration:
            defaultInlineEditor: 'ckeditor5'

CKEditor 5 itself is just a container for different modules you can
seperatly activate or deactivate. To use the different modules
(plugins), you must add these settings to your node type definition:


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


Creating a Custom CKEditor 5 Plugin
===================================

First of all, you will need a dedicated flow package. To learn, how to kickstart a new flow package, please
visit `Flow Docs
<https://flowframework.readthedocs.io/en/stable/TheDefinitiveGuide/PartII/Kickstart.html>`.


To extend CKEditor 5, which is used for Neos inline editors, you will be needing CKEditor 5 Plugins.
Please install yarn (which is actually a prerequirement for Neos.UI Development Requirement.)

``npm install -g yarn``


Also you require Neos-ui extensibility. This should be added to your package.json and installed via yarn.




    "devDependencies": {
        "@neos-project/neos-ui-extensibility": "^1.3.0"
    }


Getting started
---------------

The most basic starter plugin has these components:

-  **index.js**: has only one purpose: deliver manifest.js via
   ``require('./manifest');``
-  **manifest.js**: modifies the ckeditor5. Here, you should add the
   button to CKEditor 5 Toolbar & the plugin to CKEditor config.
-  **Plugin.js**: is the heart of your plugin. Here you define different
   commands and also the initialize your plugin.
-  **Button.js**: defines the button, which appears in the toolbar. This one is of course optional,
   since you can always create Plugins without the need of a button.
   You can create a new pop-up in Neos, or just simply call a command which you defined.

An already working example can be found on `Neos-ui-extensiblity-examples Github
<https://github.com/neos/neos-ui-extensibility-examples/tree/master/Resources/Private/CustomStylingForCkEditor>`.
You can download this example and modify this to build your own example.


You would also need to tell the Neos UI to load the Plugin.js file of your package. Please add this to your Settings.yaml:


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

Building the Plugin
-------------------

Your changes must be built first. Please edit the values in package.json
and set up a proper name and buildTargetDirectory for your plugin. Get
the dependencies via ``yarn`` or ``yarn install``. When finished, you
can use ``yarn build`` for building your package once or
``yarn watch`` for the watcher.

Now your changes can be seen in the Neos UI.

Working with CKEditor
---------------------
You can access to CKEditor 5 API via using ``this.editor`` in your ``Plugin.js``. After that, you can use all the tools available in
`CKEditor 5 Documentation
<https://ckeditor.com/docs/ckeditor5/latest/api/>`__.
