.. _custom-editors:

Custom Editors
==============

.. note:: For documentation on how to create inspector editors for the legacy Ember version of the user interface, refer to the older versions of the documentation.


Every dataType has its default editor set, which can have options applied like::

.. code-block:: yaml

  Neos:
    Neos:
      userInterface:
        inspector:
          dataTypes:
            'string':
              editor: 'Neos.Neos/Inspector/Editors/TextFieldEditor'
              editorOptions:
                placeholder: 'This is a placeholder'

On a property level this can be overridden like:

.. code-block:: yaml

  Neos:
    Neos:
      userInterface:
        inspector:
          properties:
            'string':
              editor: 'My.Package/Inspector/Editors/TextFieldEditor'
              editorOptions:
                placeholder: 'This is my custom placeholder'

In order to implement a custom inspector editor one has to use the UI extensibility layer exposed through the `@neos-project/neos-ui-extensibility` package.
See :ref:`ui-extensibility` for the detailed information on the topic.

Let's create a simple colour picker editor.

Use the following `package.json` file:

.. code-block:: json

  {
    "scripts": {
      "build": "neos-react-scripts build",
      "watch": "neos-react-scripts watch"
    },
    "neos": {
      "buildTargetDirectory": "../../Public/ColorPickerEditor"
    },
    "devDependencies": {
      "@neos-project/neos-ui-extensibility": "^1.0.0"
    },
    "dependencies": {
      "react-color": "^2.11.1"
    }
  }

To build the editor you need to run the following commands (after the code is in place):

```
yarn
yarn build # or yarn watch
```

This will put the compiled Plugin.js asset into Public/ColorPickerEditor folder.
Now this file has to be loaded into the host UI.
Put the following configuration into Settings.yaml to do it:

.. code-block:: yaml

  Neos:
    Neos:
      Ui:
        resources:
          javascript:
            'Neos.Neos.Ui.ExtensibilityExamples:ColorPickerEditor':
              resource: resource://Neos.Neos.Ui.ExtensibilityExamples/Public/ColorPickerEditor/Plugin.js


Now it is time to write the actual source code of the editor.

From `index.js` we just require manifest.js file:

.. code-block:: javascript

  require('./manifest');


In `manifest.js` we use the `manifest` API to get access to the globalRegistry, then we get the `editors` registry out of it and register our custom editor into it:

.. code-block:: javascript

  import manifest from '@neos-project/neos-ui-extensibility';
  import ColorPickerEditor from './ColorPickerEditor';
  manifest('Neos.Neos.Ui.ExtensibilityExamples:ColorPickerEditor', {}, globalRegistry => {
    const editorsRegistry = globalRegistry.get('inspector').get('editors');
    editorsRegistry.set('Neos.Neos.Ui.ExtensibilityExamples/ColorPickerEditor', {
        component: ColorPickerEditor
    });
  });


And finally the editor component itself (`ColorPickerEditor.js`):

.. code-block:: javascript

  import React, {PureComponent} from 'react';
  import PropTypes from 'prop-types';
  import {SketchPicker} from 'react-color';
  export default class ColorPickerEditor extends PureComponent {
    static propTypes = {
      value: PropTypes.string,
      commit: PropTypes.func.isRequired,
    };
    handleChangeColor = newColor => {
      this.props.commit(newColor.hex);
    };
    render() {
      return <SketchPicker color={this.props.value} onChange={this.handleChangeColor}/>;
    }
  }

Each editor component gets a few API props passed, including the current value of the editor and the `commit` callback which the editor should use to commit the new value.

That is it! Now it is time to use our brand new editor!

.. code-block:: javascript
  'Neos.NodeTypes:TextMixin':
    properties:
      color:
        ui:
          label: 'Color picker'
          inspector:
            editor: 'Neos.Neos.Ui.ExtensibilityExamples/ColorPickerEditor'
