.. _neos-typoscript-reference:

====================
TypoScript Reference
====================

TYPO3.TypoScript
================

This package contains general-purpose TypoScript objects, which are usable both within Neos and standalone.

.. _TYPO3_TypoScript__Array:

TYPO3.TypoScript:Array
----------------------

Render multiple nested definitions and concatenate the results.

:[key]: (string) A nested definition (simple value, expression or object) that evaluates to a string
:[key].@position: (string/integer) Define the ordering of the nested definition

The order in which nested definitions are evaluated are specified using their
``@position`` meta property. For this argument, the following sort order applies:

* ``start [priority]`` positions. The higher the priority, the earlier
  the object is added. If no priority is given, the element is sorted after all
  ``start`` elements with a priority.
* ``[numeric ordering]`` positions, ordered ascending.
* ``end [priority]`` positions. The higher the priority, the later the element is
  added. If no priority is given, the element is sorted before all ``end`` elements
  with a priority.

Furthermore, you can specify that an element should be inserted before or after a given
other named element, using ``before`` and ``after`` syntax as follows:

* ``before [namedElement] [optionalPriority]``: add this element before ``namedElement``;
  the higher the priority the more in front of ``namedElement`` we will add it if multiple
  ``before [namedElement]`` statements exist. Statements without ``[optionalPriority]``
  are added the farthest before the element.

  If ``[namedElement]`` does not exist, the element is added after all ``start`` positions.

* ``after [namedElement] [optionalPriority]``: add this element after ``namedElement``;
  the higher the priority the more closely after ``namedElement`` we will add it if multiple
  ``after [namedElement]`` statements exist. Statements without ``[optionalPriority]``
  are added farthest after the element.

  If ``[namedElement]`` does not exist, the element is added before all all ``end`` positions.

Example Ordering::

	# in this example, we would not need to use any @position property;
	# as the default (document order) would then be used. However, the
	# order (o1 ... o9) is *always* fixed, no matter in which order the
	# individual statements are defined.

	myArray = TYPO3.TypoScript:Array {
		o1 = TYPO3.Neos.NodeTypes:Text
		o1.@position = 'start 12'
		o2 = TYPO3.Neos.NodeTypes:Text
		o2.@position = 'start 5'
		o2 = TYPO3.Neos.NodeTypes:Text
		o2.@position = 'start'

		o3 = TYPO3.Neos.NodeTypes:Text
		o3.@position = '10'
		o4 = TYPO3.Neos.NodeTypes:Text
		o4.@position = '20'

		o5 = TYPO3.Neos.NodeTypes:Text
		o5.@position = 'before o6'

		o6 = TYPO3.Neos.NodeTypes:Text
		o6.@position = 'end'
		o7 = TYPO3.Neos.NodeTypes:Text
		o7.@position = 'end 20'
		o8 = TYPO3.Neos.NodeTypes:Text
		o8.@position = 'end 30'

		o9 = TYPO3.Neos.NodeTypes:Text
		o9.@position = 'after o8'
	}

If no ``@position`` property is defined, the array key is used. However, we suggest
to use ``@position`` and meaningful keys in your application, and not numeric ones.

Example of numeric keys (discouraged)::

	myArray = TYPO3.TypoScript:Array {
		10 = TYPO3.Neos.NodeTypes:Text
		20 = TYPO3.Neos.NodeTypes:Text
	}


.. _TYPO3_TypoScript__Collection:

TYPO3.TypoScript:Collection
---------------------------

Render each item in ``collection`` using ``itemRenderer``.

:collection: (array/Iterable, **required**) The array or iterable to iterate over
:itemName: (string, defaults to ``item``) Context variable name for each item
:itemKey: (string) Context variable name for each item key, when working with array
:iterationName: (string) If set, a context variable with iteration information will be availble under the given name: ``index`` (zero-based), ``cycle`` (1-based), ``isFirst``, ``isLast``
:itemRenderer: (string) The renderer definition (simple value, expression or object) will be called once for every collection element, and its results will be concatenated

Example using an object ``itemRenderer``::

	myCollection = TYPO3.TypoScript:Collection {
		collection = ${[1, 2, 3]}
		itemName = 'element'
		itemRenderer = TYPO3.TypoScript:Template {
			templatePath = 'resource://...'
			element = ${element}
		}
	}


Example using an expression ``itemRenderer``::

	myCollection = TYPO3.TypoScript:Collection {
		collection = ${[1, 2, 3]}
		itemName = 'element'
		itemRenderer = ${element * 2}
	}

.. _TYPO3_TypoScript__Case:

TYPO3.TypoScript:Case
---------------------

**Conditionally evaluate** nested definitions.

Evaluates all nested definitions until the first ``condition`` evaluates to ``TRUE``. The Case object will
evaluate to a result using either ``renderer``, ``renderPath`` or ``type`` on the matching definition.

:[key]: A matcher definition
:[key].condition: (boolean, **required**) A simple value, expression or object that will be used as a condition for this matcher
:[key].type: (string) Object type to render (as string)
:[key].element.*: (mixed) Properties for the rendered object (when using ``type``)
:[key].renderPath: (string) Relative or absolute path to render, overrules ``type``
:[key].renderer: (mixed) Rendering definition (simple value, expression or object), overrules ``renderPath`` and ``type``
:[key].@position: (string/integer) Define the ordering of the nested definition

Simple Example::

	myCase = TYPO3.TypoScript:Case {
		someCondition {
			condition = ${q(node).is('[instanceof MyNamespace:My.Special.SuperType]')}
			type = 'MyNamespace:My.Special.Type'
		}

		otherCondition {
			@position = 'start'
			condition = ${q(documentNode).property('layout') == 'special'}
			renderer = ${'<marquee>' + q(node).property('content') + '</marquee>'}
		}

		fallback {
			condition = ${true}
			renderPath = '/myPath'
		}
	}

The ordering of matcher definitions can be specified with the ``@position`` property (see :ref:`TYPO3_TypoScript__Array`).
Thus, the priority of existing matchers (e.g. the default Neos document rendering) can be changed by setting or
overriding the ``@position`` property.

.. note:: The internal ``TYPO3.TypoScript:Matcher`` object type is used to evaluate the matcher definitions.

.. _TYPO3_TypoScript__Debug:

TYPO3.TypoScript:Debug
-------------------------

Shows the result of TypoScript Expressions directly.

:title: (optional) Title for the debug output
:plaintext: (boolean) If set true, the result will be shown as plaintext
:[key]: (mixed) A nested definition (simple value, expression or object), ``[key]`` will be used as key for the resulting output

Example::

  debugObject = Debug {
        title = 'Debug of hello world'

        # If only the "value"-key is given it is debugged directly,
        # otherwise all keys except "title" an "plaintext" are debugged.
        value = "hello neos world"

        # Additional values for debugging
        documentTitle = ${q(documentNode).property('title')}
        documentPath = ${documentNode.path}
  }
  
  # the value of this object is the formated debug output of all keys given to the object


.. _TYPO3_TypoScript__Template:

TYPO3.TypoScript:Template
-------------------------

Render a *Fluid template* specified by ``templatePath``.

:templatePath: (string, **required**) Path and filename for the template to be rendered, often a ``resource://`` URI
:partialRootPath: (string) Path where partials are found on the file system
:layoutRootPath: (string) Path where layouts are found on the file system
:sectionName: (string) The Fluid ``<f:section>`` to be rendered, if given
:[key]: (mixed) All remaining properties are directly passed into the Fluid template as template variables

Example::

	myTemplate = TYPO3.TypoScript:Template {
		templatePath = 'resource://My.Package/Private/Templates/TypoScriptObjects/MyTemplate.html'
		someDataAvailableInsideFluid = 'my data'
	}

	<div class="hero">
		{someDataAvailableInsideFluid}
	</div>

.. _TYPO3_TypoScript__Value:

TYPO3.TypoScript:Value
----------------------

Evaluate any value as a TypoScript object

:value: (mixed, **required**) The value to evaluate

Example::

	myValue = TYPO3.TypoScript:Value {
		value = 'Hello World'
	}

.. note:: Most of the time this can be simplified by directly assigning the value instead of using the ``Value`` object.


.. _TYPO3_TypoScript__RawArray:

TYPO3.TypoScript:RawArray
-------------------------

Evaluate nested definitions as an array (opposed to *string* for :ref:`TYPO3_TypoScript__Array`)

:[key]: (mixed) A nested definition (simple value, expression or object), ``[key]`` will be used for the resulting array key
:[key].@position: (string/integer) Define the ordering of the nested definition

.. tip:: For simple cases an expression with an array literal ``${[1, 2, 3]}`` might be easier to read

.. _TYPO3_TypoScript__Tag:

TYPO3.TypoScript:Tag
--------------------

Render an HTML tag with attributes and optional body

:tagName: (string) Tag name of the HTML element, defaults to ``div``
:omitClosingTag: (boolean) Whether to render the element ``content`` and the closing tag, defaults to ``FALSE``
:selfClosingTag: (boolean) Whether the tag is a self-closing tag with no closing tag. Will be resolved from ``tagName`` by default, so default HTML tags are treated correctly.
:content: (string) The inner content of the element, will only be rendered if the tag is not self-closing and the closing tag is not omitted
:attributes: (:ref:`TYPO3_TypoScript__Attributes`) Tag attributes

Example:
^^^^^^^^

::

	htmlTag = TYPO3.TypoScript:Tag {
		tagName = 'html'
		omitClosingTag = TRUE

		attributes {
			version = 'HTML+RDFa 1.1'
			xmlns = 'http://www.w3.org/1999/xhtml'
		}
	}

Evaluates to::

	<html version="HTML+RDFa 1.1" xmlns="http://www.w3.org/1999/xhtml">

.. _TYPO3_TypoScript__Attributes:

TYPO3.TypoScript:Attributes
---------------------------

A TypoScript object to render HTML tag attributes. This object is used by the :ref:`TYPO3_TypoScript__Tag` object to
render the attributes of a tag. But it's also useful standalone to render extensible attributes in a Fluid template.

:[key]: (string) A single attribute, array values are joined with whitespace. Boolean values will be rendered as an empty or absent attribute.
:@allowEmpty: (boolean) Whether empty attributes (HTML5 syntax) should be used for empty, false or null attribute values

Example:
^^^^^^^^

::

	attributes = TYPO3.TypoScript:Attributes {
		foo = 'bar'
		class = TYPO3.TypoScript:RawArray {
			class1 = 'class1'
			class2 = 'class2'
		}
	}

Evaluates to::

	foo="bar" class="class1 class2"

Unsetting an attribute:
^^^^^^^^^^^^^^^^^^^^^^^

It's possible to unset an attribute by assigning ``false`` or ``${null}`` as a value. No attribute will be rendered for
this case.

.. _TYPO3_TypoScript__Http_Message:

TYPO3.TypoScript:Http.Message
-----------------------------

A prototype based on :ref:`TYPO3_TypoScript__Array` for rendering an HTTP message (response). It should be used to
render documents since it generates a full HTTP response and allows to override the HTTP status code and headers.

:httpResponseHead: (:ref:`TYPO3_TypoScript__Http_ResponseHead`) An HTTP response head with properties to adjust the status and headers, the position in the ``Array`` defaults to the very beginning
:[key]: (string) A nested definition (see :ref:`TYPO3_TypoScript__Array`)

Example:
^^^^^^^^

::

	// Page extends from Http.Message
	//
	// prototype(TYPO3.Neos:Page) < prototype(TYPO3.TypoScript:Http.Message)
	//
	page = TYPO3.Neos:Page {
		httpResponseHead.headers.Content-Type = 'application/json'
	}

.. _TYPO3_TypoScript__Http_ResponseHead:

TYPO3.TypoScript:Http.ResponseHead
----------------------------------

A helper object to render the head of an HTTP response

:statusCode: (integer) The HTTP status code for the response, defaults to ``200``
:headers.*: (string) An HTTP header that should be set on the response, the property name (e.g. ``headers.Content-Type``) will be used for the header name

.. _TYPO3_TypoScript__UriBuilder:

TYPO3.TypoScript:UriBuilder
---------------------------

Built a URI to a controller action

:package: (string) The package key (e.g. ``'My.Package'``)
:subpackage: (string) The subpackage, empty by default
:controller: (string) The controller name (e.g. ``'Registration'``)
:action: (string) The action name (e.g. ``'new'``)
:arguments: (array) Arguments to the action by named key
:format: (string) An optional request format (e.g. ``'html'``)
:section: (string) An optional fragment (hash) for the URI
:additionalParams: (array) Additional URI query parameters by named key
:addQueryString: (boolean) Whether to keep the query parameters of the current URI
:argumentsToBeExcludedFromQueryString: (array) Query parameters to exclude for ``addQueryString``
:absolute: (boolean) Whether to create an absolute URI

Example::

	uri = TYPO3.TypoScript:UriBuilder {
		package = 'My.Package'
		controller = 'Registration'
		action = 'new'
	}

.. _TYPO3_TypoScript__ResourceUri:

TYPO3.TypoScript:ResourceUri
----------------------------

Build a URI to a static or persisted resource

:path: (string) Path to resource, either a path relative to ``Public`` and ``package`` or a ``resource://`` URI
:package: (string) The package key (e.g. ``'My.Package'``)
:resource: (Resource) A ``Resource`` object instead of ``path`` and ``package``
:localize: (boolean) Whether resource localization should be used, defaults to ``true``

Example::

	scriptInclude = TYPO3.TypoScript:Tag {
		tagName = 'script'
		attributes {
			src = TYPO3.TypoScript:ResourceUri {
				path = 'resource://My.Package/Public/Scripts/App.js'
			}
		}
	}


TYPO3.Neos TypoScript Objects
=============================

The TypoScript objects defined in the Neos package contain all TypoScript objects which
are needed to integrate a site. Often, it contains generic TypoScript objects
which do not need a particular node type to work on.

As TYPO3.Neos is the default namespace, the TypoScript objects do not need to be
prefixed with TYPO3.Neos.

.. _TYPO3_Neos__Page:

Page
----
Subclass of :ref:`TYPO3_TypoScript__Http_Message`, which is based on :ref:`TYPO3_TypoScript__Array`. Main entry point
into rendering a page; responsible for rendering the ``<html>`` tag and everything inside.

:doctype: (string) Defaults to ``<!DOCTYPE html>``
:htmlTag: (:ref:`TYPO3_TypoScript__Tag`) The opening ``<html>`` tag
:htmlTag.attributes: (:ref:`TYPO3_TypoScript__Attributes`) Attributes for the ``<html>`` tag
:headTag: (:ref:`TYPO3_TypoScript__Tag`) The opening ``<head>`` tag
:head: (:ref:`TYPO3_TypoScript__Array`) HTML markup for the ``<head>`` tag
:head.titleTag: (:ref:`TYPO3_TypoScript__Tag`) The ``<title>`` tag
:head.javascripts: (:ref:`TYPO3_TypoScript__Array`) Script includes in the head should go here
:head.stylesheets: (:ref:`TYPO3_TypoScript__Array`) Link tags for stylesheets in the head should go here
:body.templatePath: (string) Path to a fluid template for the page body
:bodyTag: (:ref:`TYPO3_TypoScript__Tag`) The opening ``<body>`` tag
:bodyTag.attributes: (:ref:`TYPO3_TypoScript__Attributes`) Attributes for the ``<body>`` tag
:body: (:ref:`TYPO3_TypoScript__Template`) HTML markup for the ``<body>`` tag
:body.javascripts: (:ref:`TYPO3_TypoScript__Array`) Body footer JavaScript includes
:body.[key]: (mixed) Body template variables

Examples:
^^^^^^^^^

Rendering a simple page:
""""""""""""""""""""""""

::

	page = Page
	page.body.templatePath = 'resource://My.Package/Private/MyTemplate.html'
	// the following line is optional, but recommended for base CSS inclusions etc
	page.body.sectionName = 'main'

Rendering content in the body:
""""""""""""""""""""""""""""""

TypoScript::

	page.body {
		sectionName = 'body'
		content.main = PrimaryContent {
			nodePath = 'main'
		}
	}

Fluid::

	<html>
		<body>
			<f:section name="body">
				<div class="container">
					{content.main -> f:format.raw()}
				</div>
			</f:section>
		</body>
	</html

Including stylesheets from a template section in the head:
""""""""""""""""""""""""""""""""""""""""""""""""""""""""""

::

	page.head.stylesheets.mySite = TYPO3.TypoScript:Template {
		templatePath = 'resource://My.Package/Private/MyTemplate.html'
		sectionName = 'stylesheets'
	}


Adding body attributes with ``bodyTag.attributes``:
"""""""""""""""""""""""""""""""""""""""""""""""""""

::

	page.bodyTag.attributes.class = 'body-css-class1 body-css-class2'


.. _TYPO3_Neos__ContentCollection:

ContentCollection
-----------------

Render nested content from a ``ContentCollection`` node. Individual nodes are rendered using the
:ref:`TYPO3_Neos__ContentCase` object.

:nodePath: (string, **required**) The relative node path of the ``ContentCollection`` (e.g. ``'main'``)
:@context.contentCollectionNode: (Node) The content collection node, resolved from ``nodePath`` by default
:tagName: (string) Tag name for the wrapper element
:attributes: (:ref:`TYPO3_TypoScript__Attributes`) Tag attributes for the wrapper element

Example::

	page.body {
		content {
			main = PrimaryContent {
				nodePath = 'main'
			}
			footer = ContentCollection {
				nodePath = 'footer'
			}
		}
	}

.. _TYPO3_Neos__PrimaryContent:

PrimaryContent
--------------

Primary content rendering, extends :ref:`TYPO3_TypoScript__Case`. This is a prototype that can be used from packages
to extend the default content rendering (e.g. to handle specific document node types).

:nodePath: (string, **required**) The relative node path of the ``ContentCollection`` (e.g. ``'main'``)
:default: Default matcher that renders a ContentCollection
:[key]: Additional matchers (see :ref:`TYPO3_TypoScript__Case`)

Example for basic usage::

	page.body {
		content {
			main = PrimaryContent {
				nodePath = 'main'
			}
		}
	}

Example for custom matcher::

	prototype(TYPO3.Neos:PrimaryContent) {
		myArticle {
			condition = ${q(node).is('[instanceof My.Site:Article]')}
			renderer = My.Site:ArticleRenderer
		}
	}

.. _TYPO3_Neos__ContentCase:

ContentCase
-----------

Render a content node, extends :ref:`TYPO3_TypoScript__Case`. This is a prototype that is used by the default content
rendering (:ref:`TYPO3_Neos__ContentCollection`) and can be extended to add custom matchers.

:default: Default matcher that renders a prototype of the same name as the node type name
:[key]: Additional matchers (see :ref:`TYPO3_TypoScript__Case`)

.. _TYPO3_Neos__Content:

Content
-------

Base type to render content nodes, extends :ref:`TYPO3_TypoScript__Template`. This prototype is extended by the
auto-generated TypoScript to define prototypes for each node type extending ``TYPO3.Neos:Content``.

:templatePath: (string) The template path and filename, defaults to ``'resource://[packageKey]/Private/Templates/NodeTypes/[nodeType].html'`` (for auto-generated prototypes)
:[key]: (mixed) Template variables, all node type properties are available by default (for auto-generated prototypes)
:attributes: (:ref:`TYPO3_TypoScript__Attributes`) Extensible attributes, used in the default templates

Example::

	prototype(My.Package:MyContent) < prototype(TYPO3.Neos:Content) {
		templatePath = 'resource://My.Package/Private/Templates/NodeTypes/MyContent.html'
		# Auto-generated for all node type properties
		# title = ${q(node).property('title')}
	}

.. _TYPO3_Neos__Plugin:

Plugin
------

Base type to render plugin content nodes or static plugins. A *plugin* is a Flow controller that can implement
arbitrary logic.

:package: (string, **required**) The package key (e.g. `'My.Package'`)
:subpackage: (string) The subpackage, defaults to empty
:controller: (array) The controller name (e.g. 'Registration')
:action: (string) The action name, defaults to `'index'`
:argumentNamespace: (string) Namespace for action arguments, will be resolved from node type by default
:[key]: (mixed) Pass an internal argument to the controller action (access with argument name ``_key``)

Example::

	prototype(My.Site:Registration) < prototype(TYPO3.Neos:Plugin) {
		package = 'My.Site'
		controller = 'Registration'
	}

.. _TYPO3_Neos__Menu:

Menu
----

Render a menu with items for nodes. Extends :ref:`TYPO3_TypoScript__Template`.

:templatePath: (string) Override the template path
:entryLevel: (integer) Start the menu at the given depth
:maximumLevels: (integer) Restrict the maximum depth of items in the menu (relative to ``entryLevel``)
:startingPoint: (Node) The parent node of the first menu level (defaults to ``node`` context variable)
:lastLevel: (integer) Restrict the menu depth by node depth (relative to site node)
:filter: (string) Filter items by node type (e.g. ``'!My.Site:News,TYPO3.Neos:Document'``), defaults to ``'TYPO3.Neos:Document'``
:renderHiddenInIndex: (boolean) Whether nodes with ``hiddenInIndex`` should be rendered, defaults to ``false``
:itemCollection: (array) Explicitly set the Node items for the menu (alternative to ``startingPoints`` and levels)
:attributes: (:ref:`TYPO3_TypoScript__Attributes`) Extensible attributes for the whole menu
:normal.attributes: (:ref:`TYPO3_TypoScript__Attributes`) Attributes for normal state
:active.attributes: (:ref:`TYPO3_TypoScript__Attributes`) Attributes for active state
:current.attributes: (:ref:`TYPO3_TypoScript__Attributes`) Attributes for current state

Menu item properties:
^^^^^^^^^^^^^^^^^^^^^

:node: (Node) A node instance (with resolved shortcuts) that should be used to link to the item
:originalNode: (Node) Original node for the item
:state: (string) Menu state of the item: ``'normal'``, ``'current'`` (the current node) or ``'active'`` (ancestor of current node)
:label: (string) Full label of the node
:menuLevel: (integer) Menu level the item is rendered on

Examples:
^^^^^^^^^

Custom menu template:
"""""""""""""""""""""

::

	menu = Menu {
		entryLevel = 1
		maximumLevels = 3
		templatePath = 'resource://My.Site/Private/Templates/TypoScriptObjects/MyMenu.html'
	}

Menu including site node:
"""""""""""""""""""""""""

::

	menu = Menu {
		itemCollection = ${q(site).add(q(site).children('[instanceof TYPO3.Neos:Document]')).get()}
	}

Menu with custom starting point:
""""""""""""""""""""""""""""""""

::

	menu = Menu {
		entryLevel = 2
		maximumLevels = 1
		startingPoint = ${q(site).children('[uriPathSegment="metamenu"]').get(0)}
	}

.. _TYPO3_Neos__BreadcrumbMenu:

BreadcrumbMenu
--------------

Render a breadcrumb (ancestor documents), based on :ref:`TYPO3_Neos__Menu`.

Example::

	breadcrumb = BreadcrumbMenu

.. _TYPO3_Neos__DimensionMenu:
.. _TYPO3_Neos__DimensionsMenu:

DimensionsMenu
--------------

Create links to other node variants (e.g. variants of the current node in other dimensions) by using this TypoScript object.

If the ``dimension`` setting is given, the menu will only include items for this dimension, with all other configured
dimension being set to the value(s) of the current node. Without any ``dimension`` being configured, all possible
variants will be included.

If no node variant exists for the preset combination, a ``NULL`` node will be included in the item with a state ``absent``.

:dimension: (optional, string): name of the dimension which this menu should be based on. Example: "language".
:presets: (optional, array): If set, the presets rendered will be taken from this list of preset identifiers
:includeAllPresets: (boolean, default **false**) If TRUE, include all presets, not only allowed combinations
:renderHiddenInIndex: (boolean, default **true**) If TRUE, render nodes which are marked as "hidded-in-index"

In the template for the menu, each ``item`` has the following properties:

:node: (Node) A node instance (with resolved shortcuts) that should be used to link to the item
:state: (string) Menu state of the item: ``normal``, ``current`` (the current node), ``absent``
:label: (string) Label of the item (the dimension preset label)
:menuLevel: (integer) Menu level the item is rendered on
:dimensions: (array) Dimension values of the node, indexed by dimension name
:targetDimensions: (array) The target dimensions, indexed by dimension name and values being arrays with ``value``, ``label`` and ``isPinnedDimension``

.. note:: The ``DimensionMenu`` is an alias to ``DimensionsMenu``, available for compatibility reasons only.

Examples
^^^^^^^^

Minimal Example, outputting a menu with all configured dimension combinations::

	variantMenu = TYPO3.Neos:DimensionsMenu

This example will create two menus, one for the 'language' and one for the 'country' dimension::

	languageMenu = TYPO3.Neos:DimensionsMenu {
		dimension = 'language'
	}
	countryMenu = TYPO3.Neos:DimensionsMenu {
		dimension = 'country'
	}

If you only want to render a subset of the available presets or manually define a specific order for a menu,
you can override the "presets"::

	languageMenu = TYPO3.Neos:DimensionsMenu {
		dimension = 'language'
		presets = ${['en_US', 'de_DE']} # no matter how many languages are defined, only these two are displayed.
	}

In some cases, it can be good to ignore the availability of variants when rendering a dimensions menu. Consider a
situation with two independent menus for country and language, where the following variants of a node exist
(language / country):

- english / Germany
- german / Germany
- english / UK

If the user selects UK, only english will be linked in the language selector. German is only available again, if the
user switches back to Germany first. This can be changed by setting the ``includeAllPresets`` option::

	languageMenu = TYPO3.Neos:DimensionsMenu {
		dimension = 'language'
		includeAllPresets = true
	}

Now the language menu will try to find nodes for all languages, if needed the menu items will point to a different
country than currently selected. The menu tries to find a node to link to by using the current preset for the language
(in this example) and the default presets for any other dimensions. So if fallback rules are in place and a node can be
found, it is used.

.. note:: The ``item.targetDimensions`` will contain the "intended" dimensions, so that information can be used to
   inform the user about the potentially unexpected change of dimensions when following  such a link.

Only if the current node is not available at all (even after considering default presets with their fallback rules),
no node be assigned (so no link will be created and the items will have the ``absent`` state.)

.. _TYPO3_Neos__NodeUri:

NodeUri
-------

Build a URI to a node. Accepts the same arguments as the node link/uri view helpers.

:node: (string/Node) A node object or a node path (relative or absolute) or empty to resolve the current document node
:arguments: (array) Additional arguments to be passed to the UriBuilder (for example pagination parameters)
:format: (string) An optional request format (e.g. ``'html'``)
:section: (string) An optional fragment (hash) for the URI
:additionalParams: (array) Additional URI query parameters (overrule ``arguments``).
:argumentsToBeExcludedFromQueryString: (array) Query parameters to exclude for ``addQueryString``
:addQueryString: (boolean) Whether to keep current query parameters, defaults to ``FALSE``
:absolute: (boolean) Whether to create an absolute URI, defaults to ``FALSE``
:baseNodeName: (string) Base node context variable name (for relative paths), defaults to ``'documentNode'``

Example::

	nodeLink = TYPO3.Neos:NodeUri {
		node = ${q(node).parent().get(0)}
	}

.. _TYPO3_Neos__ImageUri:

ImageUri
--------

Get a URI to a (thumbnail) image for an asset.

:asset: (Asset) An asset object (``Image``, ``ImageInterface`` or other ``AssetInterface``)
:maximumWidth: (integer) Desired maximum height of the image
:maximumHeight: (integer) Desired maximum width of the image
:allowCropping: (boolean) Whether the image should be cropped if the given sizes would hurt the aspect ratio, defaults to ``FALSE``
:allowUpScaling: (boolean) Whether the resulting image size might exceed the size of the original image, defaults to ``FALSE``

Example::

	logoUri = TYPO3.Neos:ImageUri {
		asset = ${q(node).property('image')}
		maximumWidth = 100
		maximumHeight = 100
		allowCropping = TRUE
		allowUpScaling = TRUE
	}

.. _TYPO3_Neos__ImageTag:

ImageTag
--------

Render an image tag for an asset.

:\*: All :ref:`TYPO3_Neos__ImageUri` properties
:attributes: (:ref:`TYPO3_TypoScript__Attributes`) Image tag attributes

Example::

	logoImage = TYPO3.Neos:ImageTag {
		asset = ${q(node).property('image')}
		maximumWidth = 400
		attributes.alt = 'A company logo'
	}

.. _TYPO3_Neos__ConvertUris:

ConvertUris
-----------

Convert internal node and asset URIs (``node://...`` or ``asset://...``) in a string to public URIs and allows for
overriding the target attribute for external links and resource links.

:value: (string) The string value, defaults to the ``value`` context variable to work as a processor by default
:node: (Node) The current node as a reference, defaults to the ``node`` context variable
:externalLinkTarget: (string) Override the target attribute for external links, defaults to ``_blank``. Can be disabled with an empty value.
:resourceLinkTarget: (string) Override the target attribute for resource links, defaults to ``_blank``. Can be disabled with an empty value.
:forceConversion: (boolean) Whether to convert URIs in a non-live workspace, defaults to ``FALSE``
:absolute: (boolean) Can be used to convert node URIs to absolute links, defaults to ``FALSE``

Example::

	prototype(My.Site:Special.Type) {
		title.@process.convertUris = TYPO3.Neos:ConvertUris
	}
