=========================
Customizing the Inspector
=========================

When you add a new node type, you can customize the rendering of the inspector.
Based on the first node that we created in the "CreatingContentElement" cookbook,
we can add some properties in the inspector.

Add a simple checkbox element
=============================

This first example adds a checkbox, in a dedicated inspector section, to define if we need to hide
the Subheadline property.

You can just add the following configuration to your NodesType.yaml, based on the previous cookbook example:

Yaml (Sites/Vendor.Site/Configuration/NodeTypes.yaml) ::

  'Vendor.Site:YourContentElementName':
    ui:
      inspector:
        groups:
          advanced:
            label: 'Advanced'
            icon: 'icon-fort-awesome'
            position: 2
    properties:
      hideSubheadline:
        type: boolean
        defaultValue: TRUE
        ui:
          label: 'Hide Subheadline ?'
          reloadIfChanged: TRUE
          inspector:
            group: 'advanced'

You can add this property to your Fusion:

Fusion (Sites/Vendor.Site/Resources/Private/Fusion/Root.fusion) ::

  prototype(Vendor.Site:YourContentElementName) < prototype(Neos.Neos:Content) {
    templatePath = 'resource://Vendor.Site/Private/Templates/FusionObjects/YourContentElementName.html'
    headline = ${q(node).property('headline')}
    subheadline = ${q(node).property('subheadline')}
    hideSubheadline = ${q(node).property('hideSubheadline')}
    text = ${q(node).property('text')}
    image = ${q(node).property('image')}
  }

And you can use it in your Fluid template:

HTML (Vendor.Site/Private/Templates/FusionObjects/YourContentElementName.html) ::

  {namespace neos=Neos\Neos\ViewHelpers}
  <neos:contentElement node="{node}">
    <article>
      <header>
        <h2><neos:contentElement.editable property="headline">{headline -> f:format.raw()}</neos:contentElement></h2>
        <f:if condition="{hideSubheadline}">
          <f:else>
            <h3><neos:contentElement.editable property="subheadline">{subheadline -> f:format.raw()}</neos:contentElement></h3>
          </f:else>
        </f:if>
      </header>
      ...
    </article>
  </neos:contentElement>

Add a simple selectbox element
==============================

The second example is about adding a selector to change the class of the article element:

Yaml (Sites/Vendor.Site/Configuration/NodeTypes.yaml) ::

  'Vendor.Site:YourContentElementName':
    ui:
      inspector:
        groups:
          advanced:
            label: 'Advanced'
            position: 2
            icon: 'icon-fort-awesome'
    properties:
      articleType:
        type: string
        defaultValue: ''
        ui:
          label: 'Article Type'
          reloadIfChanged: TRUE
          inspector:
            group: 'advanced'
            editor: Content/Inspector/Editors/SelectBoxEditor
            editorOptions:
              placeholder: 'What kind of article ...'
              values:
                '':
                  label: ''
                announcement:
                  label: 'Announcement'
                casestudy:
                  label: 'Case Study'
                event:
                  label: 'Event'

Fusion (Sites/Vendor.Site/Resources/Private/Fusion/Root.fusion) ::

  prototype(Vendor.Site:YourContentElementName) < prototype(Neos.Fusion:Template) {
    templatePath = 'resource://Vendor.Site/Private/Templates/FusionObjects/YourContentElementName.html'
    headline = ${q(node).property('headline')}
    subheadline = ${q(node).property('subheadline')}
    articleType = ${q(node).property('articleType')}
    text = ${q(node).property('text')}
    image = ${q(node).property('image')}
  }

HTML (Vendor.Site/Private/Templates/FusionObjects/YourContentElementName.html) ::

  {namespace neos=Neos\Neos\ViewHelpers}
  <neos:contentElement node="{node}">
    <article{f:if(condition:articleType,then:' class="{articleType}"')}>
      ...
    </article>
  </neos:contentElement>

Select multiple options in a selectbox element
==============================================

For selecting more than one item with a slect box the type of the property has to be set to ``array``.

Yaml (Sites/Vendor.Site/Configuration/NodeTypes.yaml):

.. code-block:: yaml

  'Vendor.Site:YourContentElementName':
    properties:
      tags:
        type: array
        ...
        ui:
          inspector:
            ...
            editor: Content/Inspector/Editors/SelectBoxEditor
            editorOptions:
              multiple: TRUE
              allowEmpty: FALSE
              values:
                ...


Use custom DataSources for a selectbox element
==============================================

To add custom selectbox-options, Neos uses *data sources* for the inspector that can be implemented in PHP.
See :ref:`data-sources` for more details.

Yaml (Sites/Vendor.Site/Configuration/NodeTypes.yaml):

.. code-block:: yaml

  'Vendor.Site:YourContentElementName':
    properties:
      articleType:
        ui:
          inspector:
            editor: Content/Inspector/Editors/SelectBoxEditor
            editorOptions:
              dataSourceIdentifier: 'acme-yourpackage-test'

Remove fields from an existing Node Type
========================================

Yaml (Sites/Vendor.Site/Configuration/NodeTypes.yaml):

.. code-block:: yaml

  'Neos.Neos:Plugin':
    properties:
      package:    [ ]
      subpackage: [ ]
      controller: [ ]
      action:     [ ]

Remove a selectbox option from an existing Node Type
====================================================

Removing a selectbox option, can be done by simply edition your NodeTypes.yaml.

Yaml (Sites/Vendor.Site/Configuration/NodeTypes.yaml):

.. code-block:: yaml

  'Neos.Neos:Shortcut':
    properties:
      targetMode:
        ui:
          inspector:
            editorOptions:
              values:
                parentNode: [ ]

It is also possible to add :ref:`custom-editors` and use :ref:`custom-validators`.
