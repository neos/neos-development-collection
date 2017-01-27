.. _property-editor-reference:

Property Editor Reference
=========================

For each property which is defined in ``NodeTypes.yaml``, the editor inside the Neos inspector can be customized
using various options. Here follows the reference for each property type.

Property Type: boolean ``BooleanEditor`` -- Checkbox editor
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

A ``boolean`` value is rendered using a checkbox in the inspector::

    'isActive'
      type: boolean
      ui:
        label: 'is active'
        inspector:
          group: 'document'

Options Reference:

``disabled`` (boolean)
	HTML ``disabled`` property. If ``TRUE``, disable this checkbox.

Property Type: string ``TextFieldEditor`` -- Single-line Text Editor (default)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Example::

    subtitle:
      type: string
      ui:
        label: 'Subtitle'
        inspector:
          group: 'document'
          editorOptions:
            placeholder: 'Enter subtitle here'
            maxlength: 20

Options Reference:

``placeholder`` (string)
	HTML5 ``placeholder`` property, which is shown if the text field is empty.

``disabled`` (boolean)
	HTML ``disabled`` property. If ``TRUE``, disable this textfield.

``maxlength`` (integer)
	HTML ``maxlength`` property. Maximum number of characters allowed to be entered.

``readonly`` (boolean)
	HTML ``readonly`` property. If ``TRUE``, this field is cannot be written to.

``form`` (optional)
	HTML5 ``form`` property.

``selectionDirection`` (optional)
	HTML5 ``selectionDirection`` property.

``spellcheck`` (optional)
	HTML5 ``spellcheck`` property.

``required`` (boolean)
	HTML5 ``required`` property. If ``TRUE``, input is required.

``title`` (boolean)
	HTML ``title`` property.

``autocapitalize`` (boolean)
	Custom HTML ``autocapitalize`` property.

``autocorrect`` (boolean)
	Custom HTML ``autocorrect`` property.


Property Type: string ``TextAreaEditor`` -- Multi-line Text Editor
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

In case the text input should span multiple lines, a ``TextAreaEditor`` should be used as follows::

    'description':
        type: 'string'
        ui:
          label: 'Description'
          inspector:
            group: 'document'
            editor: 'Neos.Neos/Inspector/Editors/TextAreaEditor'
            editorOptions:
              rows: 7

Options Reference:

``rows`` (integer)
	Number of lines this textarea should have; Default ``5``.

** and all options from Text Field Editor -- see above**


Property Type: string ``CodeEditor`` -- Full-Screen Code Editor
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

In case a lot of space is needed for the text (f.e. for HTML source code), a ``CodeEditor`` can be used::

    'source':
        type: 'string'
        ui:
          label: 'Source'
          inspector:
            group: 'document'
            editor: 'Neos.Neos/Inspector/Editors/CodeEditor'

Furthermore, the button label can be adjusted by specifying ``buttonLabel``. Furthermore, the highlighting mode
can be customized, which is helpful for editing markdown and similar contents::

    'markdown':
        type: 'string'
        ui:
          label: 'Markdown'
          inspector:
            group: 'document'
            editor: 'Neos.Neos/Inspector/Editors/CodeEditor'
            editorOptions:
              buttonLabel: 'Edit Markdown'
              highlightingMode: 'text/plain'

Options Reference:

``buttonLabel`` (string)
	label of the button which is used to open the full-screen editor. Default ``Edit code``.

``highlightingMode`` (string)
	CodeMirror highlighting mode to use. These formats are support by default:
	``text/plain``, ``text/xml``, ``text/html``, ``text/css``, ``text/javascript``. If other highlighting modes shall be
	used, they must be loaded beforehand using custom JS code. Default ``text/html``.

.. _property-editor-reference-selectboxeditor:

Property Type: string / array<string> ``SelectBoxEditor`` -- Dropdown Select Editor
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

In case only fixed entries are allowed to be chosen a select box can be used - multiple selection is supported as well.
The data for populating the select box can be fetched from a fixed set of entries defined in YAML or a datasource.
The most important option is called ``values``, containing the choices which can be made. If wanted, an icon can be displayed for each choice by setting the ``icon`` class appropriately.

Basic Example -- simple select box::

    targetMode:
      type: string
      defaultValue: 'firstChildNode'
      ui:
        label: 'Target mode'
        inspector:
          group: 'document'
          editor: 'Neos.Neos/Inspector/Editors/SelectBoxEditor'
          editorOptions:
            values:
              firstChildNode:
                label: 'First child node'
                icon: 'icon-legal'
              parentNode:
                label: 'Parent node'
                icon: 'icon-fire'
              selectedTarget:
                label: 'Selected target'

If the selection list should be grouped, this can be done by setting the ``group`` key of each individual value::

    country:
      type: string
      ui:
        label: 'Country'
        inspector:
          group: 'document'
          editor: 'Neos.Neos/Inspector/Editors/SelectBoxEditor'
          editorOptions:
            values:
              italy:
                label: 'Italy'
                group: 'Southern Europe'
              austria:
                label: 'Austria'
                group: 'Central Europe'
              germany:
                label: 'Germany'
                group: 'Central Europe'

Furthermore, multiple selection is also possible, by setting ``multiple`` to ``TRUE``, which is automatically set
for properties of type ``array``. If an empty value is allowed as well, ``allowEmpty`` should be set to ``TRUE`` and
``placeholder`` should be set to a helpful text::

    styleOptions:
      type: array
      ui:
        label: 'Styling Options'
        inspector:
          group: 'document'
          editor: 'Neos.Neos/Inspector/Editors/SelectBoxEditor'
          editorOptions:

            # The next line is set automatically for type array
            # multiple: TRUE

            allowEmpty: TRUE
            placeholder: 'Select Styling Options'

            values:
              leftColumn:
                label: 'Show Left Column'
              rightColumn:
                label: 'Show Right Column'

Because selection options shall be fetched from server-side code frequently, the Select Box Editor contains
support for so-called *data sources*, by setting a ``dataSourceIdentifier``, or optionally a ``dataSourceUri``.
This helps to provide data to the editing interface without having to define routes, policies or a controller.
You can provide an array of ``dataSourceAdditionalData`` that will be sent to the data source with each request,
the key/value pairs can be accessed in the ``$arguments`` array passed to ``getData()``.

.. code-block:: yaml

    questions:
      ui:
        inspector:
          editor: 'Content/Inspector/Editors/SelectBoxEditor'
          editorOptions:
            dataSourceIdentifier: 'questions'
            # alternatively using a custom uri:
            # dataSourceUri: 'custom-route/end-point'
            dataSourceAdditionalData:
              apiKey: 'foo-bar-baz'

See :ref:`data-sources` for more details on implementing a *data source* based on Neos conventions. If you are using a
data source to populate SelectBoxEditor instances it has to be matching the ``values`` option. Make sure you sort by
group first, if using the grouping option.

Example for returning compatible data:

.. code-block:: php

  return array(
      array('value' => 'key', 'label' => 'Foo', 'group' => 'A', 'icon' => 'icon-key'),
      array('value' => 'fire', 'label' => 'Fire', 'group' => 'A', 'icon' => 'icon-fire'),
      array('value' => 'legal', 'label' => 'Legal', 'group' => 'B', 'icon' => 'icon-legal')
  );

If you use the ``dataSourceUri`` option to connect to an arbitrary service, make sure the output of the data source
is a JSON formatted array matching the following structure. Make sure you sort by group first, if using the grouping
option.

Example for compatible data:

.. code-block:: json

  [{
    "value": "key",
    "label": "Key",
    "group": "A",
    "icon": "icon-key"
  },
  {
    "value": "fire",
    "label": "Fire",
    "group": "A",
    "icon": "icon-fire"
  },
  {
    "value": "legal",
    "label": "Legal",
    "group": "B",
    "icon": "icon-legal"
  }]

Options Reference:

``values`` (required array)
	the list of values which can be chosen from

	``[valueKey]``

		``label`` (required string)
			label of this value.

		``group`` (string)
			group of this value.

		``icon`` (string)
			CSS icon class for this value.

``allowEmpty`` (boolean)
	if TRUE, it is allowed to choose an empty value.

``placeholder`` (string)
	placeholder text which is shown if nothing is selected. Only works if
	``allowEmpty`` is ``TRUE``. Default ``Choose``.

``multiple`` (boolean)
	If ``TRUE``, multi-selection is allowed. Default ``FALSE``.

``minimumResultsForSearch`` (integer)
	The minimum amount of items in the select before showing a search box,
	if set to ``-1`` the search box will never be shown.

``dataSourceUri`` (string)
	If set, this URI will be called for loading the options of the select field.

``dataSourceIdentifier`` (string)
	If set, a server-side data source will be called for loading the
	possible options of the select field.

``dataSourceAdditionalData`` (array)
	Key/value pairs that will be sent to the server-side data source with every request.

Property Type: string ``LinkEditor`` -- Link Editor for internal, external and asset links
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

If internal links to other nodes, external links or asset links shall be editable at some point, the
``LinkEditor`` can be used to edit a link::

    myLink:
      type: string
      ui:
        inspector:
          editor: 'TYPO3.Neos/Inspector/Editors/LinkEditor'

The searchbox will accept:

* node document titles
* asset titles and tags
* valid URLs
* valid email addresses

By default, links to generic ``Neos.Neos:Document`` nodes are allowed; but by setting the ``nodeTypes`` option,
this can be further restricted (like with the ``reference`` editor). Additionally, links to assets can be disabled
by setting ``assets`` to ``FALSE``. Links to external URLs are always possible. If you need a reference towards
only an asset, use the ``asset`` property type; for a reference to another node, use the ``reference`` node type.
Furthermore, the placeholder text can be customized by setting the ``placeholder`` option::


    myExternalLink:
      type: string
      ui:
        inspector:
          group: 'document'
          editor: 'TYPO3.Neos/Inspector/Editors/LinkEditor'
          editorOptions:
            assets: FALSE
            nodeTypes: ['TYPO3.Neos:Shortcut']
            placeholder: 'Paste a link, or type to search for nodes'

Property Type: integer ``TextFieldEditor``
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Example::

    cropAfterCharacters:
      type: integer
      ui:
        label: 'Crop after characters'
        inspector:
          group: 'document'

Options Reference:

**all TextFieldEditor options apply**

Property Type: reference / references ``ReferenceEditor`` / ``ReferencesEditor`` -- Reference Selection Editors
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The most important option for the property type ``reference`` and ``references`` is ``nodeTypes``, which allows to
restrict the type of the target nodes which can be selected in the editor.

Example::

    authors:
      type: references
      ui:
        label: 'Article Authors'
        inspector:
          group: 'document'
          editorOptions:
            nodeTypes: ['My.Website:Author']

Options Reference:

``nodeTypes`` (array of strings)
	List of node types which are allowed to be selected. By default, is set
	to ``Neos.Neos:Document``, allowing only to choose other document nodes.

``placeholder`` (string)
	Placeholder text to be shown if nothing is selected

``startingPoint`` (string)
	The starting point (node path) for finding possible nodes to create a reference.
	This allows to search for nodes outside the current site. If not given, nodes
	will be searched for in the current site. For all nodes outside the current site
	the node path is shown instead of the url path.

``threshold`` (number)
	Minimum amount of characters which trigger a search

Property Type: DateTime ``DateTimeEditor`` -- Date & Time Selection Editor
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The most important option for ``DateTime`` properties is the ``format``, which is configured like in PHP, as the following
examples show:

* ``d-m-Y``: ``05-12-2014`` -- allows to set only the date
* ``d-m-Y H:i``: ``05-12-2014 17:07`` -- allows to set date and time
* ``H:i``: ``17:07`` -- allows to set only the time

Example::

    publishingDate:
      type: DateTime
      defaultValue: 'today midnight'
      ui:
        label: 'Publishing Date'
        inspector:
          group: 'document'
          position: 10
          editorOptions:
            format: 'd.m.Y'

Options Reference:

``format`` (required string)
	The date format, a combination of y, Y, F, m, M, n, t, d, D, j, l, N,
	S, w, a, A, g, G, h, H, i, s. Default ``d-m-Y``.

``defaultValue`` (string)
  Sets property value, when the node is created. Accepted values are whatever
  ``strtotime()`` can parse, but it works best with relative formats like
  ``tomorrow 09:00`` etc. Use ``now`` to set current date and time.

``placeholder`` (string)
	The placeholder shown when no date is selected

``minuteStep`` (integer)
	The granularity on which a time can be selected. Example: If set to ``30``, only half-hour
	increments of time can be chosen. Default ``5`` minutes.

For the date format, these are the available placeholders:

* year
	* ``y``: A two digit representation of a year - Examples: 99 or 03
	* ``Y``: A full numeric representation of a year, 4 digits - Examples: 1999 or 2003
* month
	* ``F``: A full textual representation of a month, such as January or March - January through December
	* ``m``: Numeric representation of a month, with leading zeros - 01 through 12
	* ``M``: A short textual representation of a month, three letters - Jan through Dec
	* ``n``: Numeric representation of a month, without leading zeros - 1 through 12
	* ``t``: Number of days in the given month - 28 through 31
* day
	* ``d``: Day of the month, 2 digits with leading zeros - 01 to 31
	* ``D``: A textual representation of a day, three letters - Mon through Sun
	* ``j``: Day of the month without leading zeros - 1 to 31
	* ``l``: A full textual representation of the day of the week - Sunday through Saturday
	* ``N``: ISO-8601 numeric representation of the day of the week - 1 (for Monday) through 7 (for Sunday)
	* ``S``: English ordinal suffix for the day of the month, 2 characters - st, nd, rd or th.
	* ``w``: Numeric representation of the day of the week - 0 (for Sunday) through 6 (for Saturday)
* hour
	* ``a``: Lowercase Ante meridiem and Post meridiem - am or pm
	* ``A``: Uppercase Ante meridiem and Post meridiem - AM or PM
	* ``g``: hour without leading zeros - 12-hour format - 1 through 12
	* ``G``: hour without leading zeros - 24-hour format - 0 through 23
	* ``h``: 12-hour format of an hour with leading zeros - 01 through 12
	* ``H``: 24-hour format of an hour with leading zeros - 00 through 23
* minute
	* ``i``: minutes, 2 digits with leading zeros - 00 to 59
* second
	* ``s``: seconds, 2 digits with leading zeros - 00 through 59


Property Type: image (Neos\\Media\\Domain\\Model\\ImageInterface) ``ImageEditor`` -- Image Selection/Upload Editor
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

For properties of type ``Neos\Media\Domain\Model\ImageInterface``, an image editor is rendered. If you want cropping
and resizing functionality, you need to set ``features.crop`` and ``features.resize`` to ``TRUE``, as in the following
example::

    'teaserImage'
      type: 'Neos\Media\Domain\Model\ImageInterface'
      ui:
        label: 'Teaser Image'
        inspector:
          group: 'document'
          editorOptions:
            features:
              crop: TRUE
              resize: TRUE

If cropping is enabled, you might want to enforce a certain aspect ratio, which can be done by setting
``crop.aspectRatio.locked.width`` and ``crop.aspectRatio.locked.height``. In the following example, the
image format must be ``16:9``::

    'teaserImage'
      type: 'Neos\Media\Domain\Model\ImageInterface'
      ui:
        label: 'Teaser Image'
        inspector:
          group: 'document'
          editorOptions:
            features:
              crop: TRUE
            crop:
              aspectRatio:
                locked:
                  width: 16
                  height: 9

If not locking the cropping to a specific ratio, a set of predefined ratios can be chosen by the user. Elements can be
added or removed from this list underneath ``crop.aspectRatio.options``. If the aspect ratio of the original image
shall be added to the list, ``crop.aspectRatio.enableOriginal`` must be set to ``TRUE``. If the user should be allowed
to choose a custom aspect ratio, set ``crop.aspectRatio.allowCustom`` to ``TRUE``::

    'teaserImage'
      type: 'Neos\Media\Domain\Model\ImageInterface'
      ui:
        label: 'Teaser Image'
        inspector:
          group: 'document'
          editorOptions:
            features:
              crop: TRUE
            crop:
              aspectRatio:
                options:
                  square:
                    width: 1
                    height: 1
                    label: 'Square'
                  fourFive:
                    width: 4
                    height: 5
                  # disable this ratio (if it was defined in a supertype)
                  fiveSeven: ~
                enableOriginal: TRUE
                allowCustom: TRUE

Options Reference:

``maximumFileSize`` (string)
	Set the maximum allowed file size to be uploaded.
	Accepts numeric or formatted string values, e.g. "204800" or "204800b" or "2kb".
	Defaults to the maximum allowed upload size configured in php.ini

``features``

	``crop`` (boolean)
		If ``TRUE``, enable image cropping. Default ``TRUE``.

	``resize`` (boolean)
		If ``TRUE``, enable image resizing. Default ``FALSE``.

``crop``
	crop-related options. Only relevant if ``features.crop`` is enabled.

		``aspectRatio``

			``locked``
				Locks the aspect ratio to a specific width/height ratio

				``width`` (integer)
					width of the aspect ratio which shall be enforced

				``height`` (integer)
					height of the aspect ratio which shall be enforced

			``options``
				aspect-ratio presets. Only effective if ``locked`` is not set.

				``[presetIdentifier]``

					``width`` (required integer)
						the width of the aspect ratio preset

					``height`` (required integer)
						the height of the aspect ratio preset

					``label`` (string)
						a human-readable name of the aspect ratio preset

			``enableOriginal`` (boolean)
				If ``TRUE``, the image ratio of the original image can be chosen in the selector.
				Only effective if ``locked`` is not set. Default ``TRUE``.

			``allowCustom`` (boolean)
				If ``TRUE``, a completely custom image ratio can be chosen. Only effective if ``locked``
				is not set. Default ``TRUE``.

			``defaultOption`` (string)
				default aspect ratio option to be chosen if no cropping has been applied already.

Property Type: asset (Neos\\Media\\Domain\\Model\\Asset / array<Neos\\Media\\Domain\\Model\\Asset>) ``AssetEditor`` -- File Selection Editor
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

If an asset, i.e. ``Neos\Media\Domain\Model\Asset``, shall be uploaded or selected, the following configuration
is an example::

    'caseStudyPdf'
      type: 'Neos\Media\Domain\Model\Asset'
      ui:
        label: 'Case Study PDF'
        inspector:
          group: 'document'

Conversely, if multiple assets shall be uploaded, use ``array<Neos\Media\Domain\Model\Asset>`` as type::

    'caseStudies'
      type: 'array<Neos\Media\Domain\Model\Asset>'
      ui:
        label: 'Case Study PDF'
        inspector:
          group: 'document'

Options Reference:

(no options)

Property Validation
-------------------

The validators that can be assigned to properties in the node type configuration are used on properties
that are edited via the inspector and are applied on the client-side only. The available validators can
be found in the Neos package in ``Resources/Public/JavaScript/Shared/Validation``:

* AlphanumericValidator
* CountValidator
* DateTimeRangeValidator
* DateTimeValidator
* EmailAddressValidator
* FloatValidator
* IntegerValidator
* LabelValidator
* NotEmptyValidator
* NumberRangeValidator
* RegularExpressionValidator
* StringLengthValidator
* StringValidator
* TextValidator
* UuidValidator

The options are in sync with the Flow validators, so feel free to check the Flow documentation for details.

To apply options, just specify them like this::

	someProperty:
	  validation:
	    'Neos.Neos/Validation/StringLengthValidator':
	      minimum: 1
	      maximum: 255

Extensibility
-------------

It is also possible to add :ref:`custom-editors` and use :ref:`custom-validators`.
