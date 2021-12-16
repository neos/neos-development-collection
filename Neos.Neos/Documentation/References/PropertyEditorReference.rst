.. _property-editor-reference:

Property Editor Reference
=========================

For each property which is defined in ``NodeTypes.yaml``, the editor inside the Neos inspector can be customized
using various options. Here follows the reference for each property type.

.. note:: All NodeType inspector configuration values are dynamically evaluated on the client-side, see
   :ref:`dynamic-configuration-processing` for more details.

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
	HTML ``disabled`` property. If ``true``, disable this checkbox.

Property Type: string ``TextFieldEditor`` -- Single-line Text Editor (default)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Example::

    subtitle:
      type: string
      ui:
        label: 'Subtitle'
        help:
          message: 'Enter some help text for the editors here. The text will be shown via click.'
        inspector:
          group: 'document'
          editorOptions:
            placeholder: 'Enter subtitle here'
            maxlength: 20

Options Reference:

``placeholder`` (string)
	HTML5 ``placeholder`` property, which is shown if the text field is empty.

``disabled`` (boolean)
	HTML ``disabled`` property. If ``true``, disable this textfield.

``maxlength`` (integer)
	HTML ``maxlength`` property. Maximum number of characters allowed to be entered.

``readonly`` (boolean)
	HTML ``readonly`` property. If ``true``, this field is cannot be written to.

``form`` (optional)
	HTML5 ``form`` property.

``selectionDirection`` (optional)
	HTML5 ``selectionDirection`` property.

``spellcheck`` (optional)
	HTML5 ``spellcheck`` property.

``required`` (boolean)
	HTML5 ``required`` property. If ``true``, input is required.

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

Property Type: string ``RichTextEditor`` -- Full-Screen Rich Text Editor
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

In case a large block of text has to be edited with support for rich text editing, a ``RichTextEditor`` can be used.

It takes all the same configuration options as the inline rich text editor under ``editorOptions``::

    'source':
        type: 'string'
        ui:
          label: 'Toggle the editor'
          inspector:
            editor: 'Neos.Neos/Inspector/Editors/RichTextEditor'
            editorOptions:
              placeholder: '<p>placeholder</p>'
              autoparagraph: true
              linking:
                anchor: true
                title: true
                relNofollow: true
                targetBlank: true
              formatting:
                strong: true
                em: true
                u: true
                sub: true
                sup: true
                del: true
                p: true
                h1: true
                h2: true
                h3: true
                h4: true
                h5: true
                h6: true
                pre: true
                underline: true
                strikethrough: true
                removeFormat: true
                left: true
                right: true
                center: true
                justify: true
                table: true
                ol: true
                ul: true
                a: true

Property Type: string ``CodeEditor`` -- Full-Screen Code Editor
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

In case HTML source code or any other plain text has to be edited, a ``CodeEditor`` can be used::

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

``disabled`` (boolean)
	If ``true``, disables the CodeEditor.

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

Furthermore, multiple selection is also possible, by setting ``multiple`` to ``true``, which is automatically set
for properties of type ``array``. If an empty value is allowed as well, ``allowEmpty`` should be set to ``true`` and
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
            # multiple: true

            allowEmpty: true
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
	if true, it is allowed to choose an empty value.

``placeholder`` (string)
	placeholder text which is shown if nothing is selected. Only works if
	``allowEmpty`` is ``true``. Default ``Choose``.

``multiple`` (boolean)
	If ``true``, multi-selection is allowed. Default ``FALSE``.

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

``disabled`` (boolean)
	If ``true``, disables the SelectBoxEditor.


Property Type: string ``LinkEditor`` -- Link Editor for internal, external and asset links
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

If internal links to other nodes, external links or asset links shall be editable at some point, the
``LinkEditor`` can be used to edit a link::

    myLink:
      type: string
      ui:
        inspector:
          editor: 'Neos.Neos/Inspector/Editors/LinkEditor'

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
          editor: 'Neos.Neos/Inspector/Editors/LinkEditor'
          editorOptions:
            assets: FALSE
            nodeTypes: ['Neos.Neos:Shortcut']
            placeholder: 'Paste a link, or type to search for nodes'

Options Reference:

``disabled`` (boolean)
	If ``true``, disables the LinkEditor.

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

Property Type: string / integer ``RangeEditor`` -- Range Editor for selecting numeric values
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The minimum, maximum and step size can be defined. Additionally a unit label as well as a special label for the minimum and maximum value can be defined.

If a certain value should be entered the current value can also be clicked to enter the desired value directly.

::

    opacity:
      type: integer
      ui:
        inspector:
          editor: 'Neos.Neos/Inspector/Editors/RangeEditor'
          editorOptions:
            minLabel: Invisible
            maxLabel: Opaque
            min: 0
            max: 100
            step: 5
            unit: px


Options Reference:

``min`` (integer)
	The lowest value in the range of permitted values. This value must be less than or equal to the value of the max attribute.
  
``max`` (integer)
	The greatest value in the range of permitted values. This value must be greater than or equal to the value of the min attribute.
  
``step`` (integer)
	The step attribute is a number that specifies the granularity that the value must adhere to.
  
``unit`` (string)
  The value gets displayed beside the current value, as well after the minimal value (only if ``minLabel`` is not set) and after the maximal value (only if ``maxLabel`` is not set).

``minLabel`` (string)
	If set, this value is displayed instead of the minimum value 
  
``maxLabel`` (string)
	If set, this value is displayed instead of the maximum value
 
``disabled`` (boolean)
	If set to ``true``, the range editor gets disabled.

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
	Minimum amount of characters which trigger a search. Default is set to 2.

``createNew`` (array)
    It is also possible to create new selectable nodes directly from the reference editor.
    This can come in handy for example if you reference tag nodes and want to add new tags on the fly.

    The given string is passed to the title property of the new node.

    ``path`` (string)
        The path to the node in which the new nodes should be created.

    ``type`` (string)
        The type of the nodes to be created.

    .. code-block:: yaml

        tags:
          type: references
          ui:
            label: 'Tags'
            inspector:
              group: document
              editorOptions:
                nodeTypes: ['My.Website:Tag']
                createNew:
                  path: /sites/yoursite/tags
                  type: 'My.Website:Tag'

``disabled`` (boolean)
	If ``true``, disables the Reference(s)Editor.




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

``disabled`` (boolean)
	If ``true``, disables the DateTimeEditor.



Property Type: image (Neos\\Media\\Domain\\Model\\ImageInterface) ``ImageEditor`` -- Image Selection/Upload Editor
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

For properties of type ``Neos\Media\Domain\Model\ImageInterface``, an image editor is rendered. If you want cropping
and resizing functionality, you need to set ``features.crop`` and ``features.resize`` to ``true``, as in the following
example::

    'teaserImage'
      type: 'Neos\Media\Domain\Model\ImageInterface'
      ui:
        label: 'Teaser Image'
        inspector:
          group: 'document'
          editorOptions:
            features:
              crop: true
              resize: true

If cropping is enabled, you might want to enforce a certain aspect ratio, which can be done by setting
``crop.aspectRatio.locked.width`` and ``crop.aspectRatio.locked.height``. To show the crop dialog automatically on image upload, configure the ``crop.aspectRatio.forceCrop`` option. In the following example, the
image format must be ``16:9``::

    'teaserImage'
      type: 'Neos\Media\Domain\Model\ImageInterface'
      ui:
        label: 'Teaser Image'
        inspector:
          group: 'document'
          editorOptions:
            features:
              crop: true
            constraints:
              mediaTypes: ['image/png']
            crop:
              aspectRatio:
                forceCrop: true
                locked:
                  width: 16
                  height: 9

If not locking the cropping to a specific ratio, a set of predefined ratios can be chosen by the user. Elements can be
added or removed from this list underneath ``crop.aspectRatio.options``. If the aspect ratio of the original image
shall be added to the list, ``crop.aspectRatio.enableOriginal`` must be set to ``true``. If the user should be allowed
to choose a custom aspect ratio, set ``crop.aspectRatio.allowCustom`` to ``true``::

    'teaserImage'
      type: 'Neos\Media\Domain\Model\ImageInterface'
      ui:
        label: 'Teaser Image'
        inspector:
          group: 'document'
          editorOptions:
            constraints:
              mediaTypes: ['image/png']
            features:
              crop: true
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
                enableOriginal: true
                allowCustom: true

Options Reference:

``maximumFileSize`` (string)
	Set the maximum allowed file size to be uploaded.
	Accepts numeric or formatted string values, e.g. "204800" or "204800b" or "2kb".
	Defaults to the maximum allowed upload size configured in php.ini

``accept`` (string)
  DEPRECATED. Use ``constraints.mediaTypes`` instead

``constraints``

	``mediaTypes`` (array)
		If set, the media browser and file upload will be limited to assets with the specified media type. Default ``['image/*']``
		Example: ``['image/png', 'image/jpeg']``
		Note: Due to technical limitations the media browser currently ignores the media sub type, so ``image/png`` has the same effect as ``image/*``

	``assetSources`` (array)
		If set, the media browser will be limited to assets of the specified asset source. Default: ``[]`` (all asset sources)
		Example: ``['neos', 'custom_asset_source]``

``features``

	``crop`` (boolean)
		If ``true``, enable image cropping. Default ``true``.

	``upload`` (boolean)
		If ``true``, enable Upload button, allowing new files to be uploaded directly in the editor. Default ``true``.

	``mediaBrowser`` (boolean)
		If ``true``, enable Media Browser button. Default ``true``.

	``resize`` (boolean)
		If ``true``, enable image resizing. Default ``FALSE``.

``crop``
	crop-related options. Only relevant if ``features.crop`` is enabled.

		``aspectRatio``

      ``forceCrop``
        Show the crop dialog on image upload

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
				If ``true``, the image ratio of the original image can be chosen in the selector.
				Only effective if ``locked`` is not set. Default ``true``.

			``allowCustom`` (boolean)
				If ``true``, a completely custom image ratio can be chosen. Only effective if ``locked``
				is not set. Default ``true``.

			``defaultOption`` (string)
				default aspect ratio option to be chosen if no cropping has been applied already.

``disabled`` (boolean)
	If ``true``, disables the ImageEditor.

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

``accept`` (string)
  DEPRECATED. Use ``constraints.mediaTypes`` instead

``constraints``

	``mediaTypes`` (array)
		If set, the media browser, file search and file upload will be limited to assets with the specified media type. Default ``[]`` (all media types)
		Example: ``['application/msword', 'application/pdf']``
		Note: Due to technical limitations the media browser currently ignores the media sub type, so ``application/pdf`` has the same effect as ``application/*``.

	``assetSources`` (array)
		If set, the media browser and file search will be limited to assets of the specified asset source. Default: ``[]`` (all asset sources)
		Example: ``['neos', 'custom_asset_source]``

``features``

	``upload`` (boolean)
		If ``true``, enable Upload button, allowing new files to be uploaded directly in the editor. Default ``true``.

	``mediaBrowser`` (boolean)
		If ``true``, enable Media Browser button. Default ``true``.

``disabled`` (boolean)
	If ``true``, disables the AssetEditor.

Property Validation
-------------------

The validators that can be assigned to properties in the node type configuration are used on properties
that are edited via the inspector or inline. They are applied on the client-side only. The available validators can
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
