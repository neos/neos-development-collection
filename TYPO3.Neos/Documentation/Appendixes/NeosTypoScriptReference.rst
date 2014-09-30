.. _neos-typoscript-reference:

=========================
Neos TypoScript Reference
=========================

TYPO3.TypoScript
================

This package contains general-purpose TypoScript Objects, which are usable both within
Neos and standalone. Often, these TypoScript objects are subclassed and enhanced
by TYPO3.Neos, to provide tailored CMS functionality.


.. _TYPO3_TypoScript__Array:

TYPO3.TypoScript:Array
----------------------

Render the *nested TypoScript objects* and concatenate their results.

:[any]: (Any) the nested TypoScript objects

The order in which nested TypoScript objects are evaluated are specified using their
``@position`` argument. For this argument, the following sort order applies:

* ``start [priority]`` positions. The higher the priority, the earlier
  the object is added. If no priority is given, the element is sorted after all
  ``start`` elements with a priority.
* ``[numeric ordering]`` positions, ordered ascending.
* ``end [priority]`` positions. The higher the priority, the later the object is
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

	# in this example, we would not need to use any @position statement;
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

If no ``@position`` argument is given, the array key is used. However, we suggest
to use ``@position`` and meaningful keys in your application, and not numeric ones.

Example of numeric keys (discouraged)::

	myArray = TYPO3.TypoScript:Array {
		10 = TYPO3.Neos.NodeTypes:Text
		20 = TYPO3.Neos.NodeTypes:Text
	}


.. _TYPO3_TypoScript__Collection:

TYPO3.TypoScript:Collection
---------------------------

Loop through the array-like inside ``collection`` and render each element using ``itemRenderer``.

:collection: (array/Iterable, **required**) the array or iterable to iterate over
:itemName: (String, **required**) the variable name as which each collection element is made available inside the TypoScript context
:iterationName: (String) if set, a variable name under which some collection information is made available inside the TypoScript context: ``index`` (zero-based), ``cycle`` (1-based), ``isFirst``, ``isLast``.
:itemRenderer: (nested TypoScript object). This TypoScript object will be called once for every collection element, and its results will be concatenated.

Example::

	myCollection = TYPO3.TypoScript:Collection {
		collection = ${[1, 2, 3]}
		itemName = 'element'
		itemRenderer = TYPO3.TypoScript:Template
		itemRenderer.templatePath = '...'
		itemRenderer.element = ${element}
	}

.. _TYPO3_TypoScript__Case:

TYPO3.TypoScript:Case
---------------------

Evaluate all nested conditions in order until the first ``condition`` is TRUE. For this one,
continue rendering the specified type.

Simple Example::

	myCase = TYPO3.TypoScript:Case
	myCase {
		someCondition {
			condition = ${... some eel expression evaluating to TRUE or FALSE ... }
			type = 'MyNamespace:My.Special.Type'
		}

		fallback {
			condition = ${true}
			type = 'MyNamespace:My.Default.Type'
		}
	}

The order of conditions is specified with the ``@position`` syntax defined in
:ref:`TYPO3_TypoScript__Array`. Thus, each condition can be deterministically
ordered independently from the order it is defined inside TypoScript.

.. note:: Internally, a single branch inside the conditions is implemented using
   ``TYPO3.TypoScript:Matcher``, which is, hoverver, not yet public API.


.. _TYPO3_TypoScript__Template:

TYPO3.TypoScript:Template
-------------------------

Render a *Fluid Template* specified by ``templatePath``.

:templatePath: (String, **required**) the path towards the template to be rendered, often a ``resource://`` URI
:partialRootPath: (String) path where partials are found on the file system
:layoutRootPath: (String) path where layouts are found on the file system
:sectionName: (String) the Fluid ``<f:section>`` to be rendered, if any.
:[remaining]: (Any) all remaining variables are directly passed through into the Fluid template

Example::

	myTemplate = TYPO3.TypoScript:Template {
		templatePath = 'resource://My.Package/Private/path/to/Template.html'
		someDataAvailableInsideFluid = 'my data'
	}

.. _TYPO3_TypoScript__Value:

TYPO3.TypoScript:Value
----------------------

A TypoScript object wrapper for an arbitrary (simple) value.

:value: (mixed, **required**) the value itself

Example::

	myValue = Value {
		myValue.value = 'Hello World'
	}

.. note:: Most of the time this can be simplified by directly assigning the value instead of using the ``Value`` object.

.. _TYPO3_TypoScript__Tag:

TYPO3.TypoScript:Tag
--------------------

A TypoScript object to render an HTML tag with attributes and optional content.

:tagName: (String) The tag name of the HTML element, defaults to ``div``
:omitClosingTag: (boolean) Whether to render the element ``content`` and the closing tag, defaults to ``FALSE``
:selfClosingTag: (boolean) Whether the tag is a self-closing tag with no closing tag. Will be resolved from ``tagName`` by default, so default HTML tags are treated correctly.
:content: (String) The inner content of the element, will only be rendered if the tag is not self-closing and the closing tag is not omitted
:attributes: (:ref:`TYPO3__TypoScript__Attributes`) Tag attributes

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

.. _TYPO3__TypoScript__Attributes:

TYPO3.TypoScript:Attributes
---------------------------

A TypoScript object to render HTML tag attributes. This object is used by the :ref:`TYPO3_TypoScript__Tag` object to
render the attributes of a tag. But it's also useful standalone to render extensible attributes in a Fluid template.

:*: (String) A single attribute, array values are joined with whitespace. Boolean values will be rendered as an empty or absent attribute.
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
:[any]: (Any) the nested TypoScript objects

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
:headers.*: (String) An HTTP header that should be set on the response, the property name (e.g. ``headers.Content-Type``) will be used for the header name

TYPO3.Neos TypoScript Objects
=============================

The TypoScript objects defined in TYPO3 Neos contain all TypoScript objects which
are needed to integrate a simple site. Often, it contains generic TypoScript objects
which do not need a particular node type to work on.

As TYPO3.Neos is the default namespace, the TypoScript objects do not need to be
prefixed with TYPO3.Neos.

.. _TYPO3_Neos__Template:

Template
--------

Subclass of :ref:`TYPO3_TypoScript__Template`, only making the current ``node``
available inside the template because it is used very often.

For a reference of all properties, see :ref:`TYPO3_TypoScript__Template`.

Example::

	// While this example demonstrates Template, it overrides all Neos default
	// templates. That's why in production, you should rather start with the
	// TYPO3.Neos:Page TypoScript object.
	page = Template
	page.templatePath = ...
	// inside the template, you could access "Node"

.. _TYPO3_Neos__Page:

Page
----
Subclass of :ref:`TYPO3_TypoScript__Http_Message`, which is based on :ref:`TYPO3_TypoScript__Array`. Main entry point
into rendering a page; responsible for rendering the ``<html>`` tag and everything inside.

:doctype: (String) Defaults to ``<!DOCTYPE html>``
:htmlTag: (:ref:`TYPO3_TypoScript__Tag`) The opening ``<html>`` tag
:htmlTag.attributes.*: (array of String) attributes to be added to the outermost ``<html>`` tag
:headTag: (:ref:`TYPO3_TypoScript__Tag`) The opening ``<head>`` tag
:head: (:ref:`TYPO3_TypoScript__Array`) HTML markup to be added to the ``<head>`` of the website
:head.titleTag: (:ref:`TYPO3_TypoScript__Tag`) The ``<title>`` tag of the website
:head.javascripts: (:ref:`TYPO3_TypoScript__Array`) Script includes in the head should go here
:head.stylesheets: (:ref:`TYPO3_TypoScript__Array`) Link tags for stylesheets in the head should go here
:body.templatePath: (String) path to a fluid template to be used in the page body
:bodyTag: (:ref:`TYPO3_TypoScript__Tag`) The opening ``<body>`` tag
:bodyTag.attributes.*: (array of String) attributes to be added to be ``<body>`` tag of the website.
:body: (:ref:`TYPO3_TypoScript__Template`) HTML markup of the ``<body>`` of the website
:body.javascripts: (:ref:`TYPO3_TypoScript__Array`) Script includes before the closing body tag should go here
:body.*: ``body`` defaults to a :ref:`TYPO3_TypoScript__Template`, so you can set all properties on it as well (like ``sectionName``)

Examples
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

.. TODO: continue here

ContentCollection
-----------------

Subclass of :ref:`TYPO3_TypoScript__Case` with a nested :ref:`TYPO3_TypoScript__Collection`,
which in turn contains the ContentCase for rendering single elements.

PrimaryContentCollection
------------------------

Subclass of ContentCollection, to indicate the *primary area* of a website. Only
to be used by the integrator who writes the Page template. Is a marker to
indicate the primary content area of the website.

ContentCase
-----------

Render a single Node. Used inside ContentCollection.


Plugin
------

Generic extension point for custom code inside the page rendering (what we call a "plugin").

Menu
----

Breadcrumb
----------

NodeUri
-------

Create links to nodes easily by using this TypoScript object. Accepts the same arguments as the
node link/uri view helpers.

:node: (string/object/null) A node object or a string node path or NULL to resolve the current document node.
:arguments: (array) Additional arguments to be passed to the UriBuilder (for example pagination parameters).
:format: (string) The requested format, for example "html".
:section: (string) The anchor to be appended to the URL.
:additionalParams: (array) Additional query parameters that won't be prefixed like $arguments (overrule $arguments).
:argumentsToBeExcludedFromQueryString: (array) Arguments to be removed from the URI. Only active if addQueryString = TRUE.
:addQueryString: (boolean, default **false**) If TRUE, the current query parameters will be kept in the URI.
:absolute: (boolean, default **false**) If TRUE, an absolute URI is rendered.
:baseNodeName: (string, default **documentNode**) The name of the base node inside the TypoScript context to use for resolving relative paths.

Example::

	nodeLink = TYPO3.Neos:NodeUri {
		node = ${q(node).parent().get(0)}
	}

TYPO3.Neos.NodeTypes
====================

The TYPO3.Neos.NodeTypes package contains most node types inheriting from *content*,
like Text, HTML, Image, TextWithImage, TwoColumn. It contains the TYPO3CR Node Type
Definition and the corresponding TypoScript objects.

If wanted, this package could be removed to completely start from scratch with custom
node types.

.. note:: A few node types like Plugin or ContentCollection are not defined inside
	this package, but inside TYPO3.Neos. This is because these are *core types*:
	Neos itself depends on them at various places in the code, and Neos would not
	be of much use if any of these types was removed. That's why Plugin (a generic
	extension point towards custom code) and ContentCollection (a generic list of
	content) is implemented inside Neos.

TYPO3.Neos.NodeTypes:Html
=========================
TYPO3.Neos.NodeTypes:Headline
=============================
TYPO3.Neos.NodeTypes:Image
==========================
TYPO3.Neos.NodeTypes:Text
=========================
TYPO3.Neos.NodeTypes:TextWithImage
==================================
TYPO3.Neos.NodeTypes:Menu
=========================
TYPO3.Neos.NodeTypes:MultiColumn
================================
TYPO3.Neos.NodeTypes:TwoColumn
==============================