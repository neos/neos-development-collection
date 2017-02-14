.. _translate-nodetypes:

Translate NodeTypes
===================

To use the translations for NodeType labels or help messages you have to enable it for each label
or message by setting the value to the predefined value "i18n".

*NodeTypes.yaml*

.. code-block:: yaml

  Vendor.Site:YourContentElementName:
    ui:
      help:
        message: 'i18n'
      inspector:
        tabs:
          yourTab:
            label: 'i18n'
        groups:
          yourGroup:
            label: 'i18n'
    properties:
      yourProperty:
        type: string
          ui:
            label: 'i18n'
            help:
              message: 'i18n'

That will instruct Neos to look for translations of these labels. To register an xliff file
for this NodeTypes you have to add the following configuration to the Settings.yaml of your package:

.. code-block:: yaml

  Neos:
    Neos:
      userInterface:
        translation:
          autoInclude:
            'Vendor.Site': ['NodeTypes/*']


Inside of the xliff file **Resources/Private/Translations/en/NodeTypes/YourContentElementName.xlf** the
translated labels for ``help``, ``properties``, ``groups``, ``tabs`` and ``views`` are defined in the xliff
as follows:

.. code-block:: xml

	<?xml version="1.0" encoding="UTF-8"?>
	<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
		<file original="" product-name="Vendor.Site" source-language="en" datatype="plaintext">
			<body>
				<trans-unit id="ui.help.message" xml:space="preserve">
					<source>Your help message here</source>
				</trans-unit>
				<trans-unit id="tabs.myTab" xml:space="preserve">
					<source>Your Tab Title</source>
				</trans-unit>
				<trans-unit id="groups.myTab" xml:space="preserve">
					<source>Your Group Title</source>
				</trans-unit>
				<trans-unit id="properties.myProperty" xml:space="preserve">
					<source>Your Property Title</source>
				</trans-unit>
				<trans-unit id="properties.myProperty.ui.help.message" xml:space="preserve">
					<source>Your help message here</source>
				</trans-unit>
			</body>
		</file>
	</xliff>

Add properties to existing NodeTypes
------------------------------------

For adding properties to existing NodeTypes the use of mixins is encouraged.

*NodeTypes.yaml*

.. code-block:: yaml

  Vendor.Site:YourNodetypeMixin:
    abstract: true
    properties:
      demoProperty:
        type: string
          ui:
            label: 'i18n'

  Neos.Neos:Page:
    superTypes:
      'Vendor.Site:YourNodetypeMixin': true

That way you can add the translations for the added properties to the file
**Resources/Private/Translations/en/NodeTypes/YourNodetypeMixin.xlf**.

Override Translations
---------------------

To override translations entirely or to use custom id's the label property can also
contain a path of the format ``Vendor.Package:Xliff.Path.And.Filename:labelType.identifier``.
The string consists of three parts delimited by ``:``:

* First, the *Package Key*
* Second, the path towards the xliff file, replacing slashes by dots (relative to ``Resources/Private/Translation/<language>``).
* Third, the key inside the xliff file.

For the example above that would be ``Vendor.Site:NodeTypes.YourContentElementName:properties.title``:

.. code-block:: yaml

    properties:
      title:
        type: string
          ui:
            label: 'Vendor.Site:NodeTypes.YourContentElementName:properties.title'

If you e.g. want to *relabel* an existing node property of a different package (like the ``Neos.NodeTypes:Page``),
you always have to specify the full translation key (pointing to your package's XLIFF files then).

Validate Translations
---------------------

To validate that all labels are translated Neos has the following setting in *Settings.yaml*::


  Neos:
    Neos:
      UserInterface:
        scrambleTranslatedLabels: true

If that setting is enabled all already translated labels are replaced with ###### -- that way you can easily identify the labels that still lack translations.

.. note:: Make sure to flush the browser caches after working with the translation to make sure that the browser always
          shows the latest translations.
