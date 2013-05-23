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

.. _TYPO3_TypoScript__Case

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



TYPO3.TypoScript:Value
----------------------

A TypoScript object wrapper for an arbitrary (simple) value.

:value: (mixed, **required**) the value itself

Example::

	myValue = Value {
		myValue.value = 'Hello World'
	}

.. note:: This TypoScript object will not be available after the TypoScript refactoring of #48359


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

Subclass of :ref:`TYPO3_Neos__Template`. Main entry point into rendering a page;
responsible for rendering the ``<html>`` tag and everything inside.

.. note:: The following properties are public API. There are more properties because
	``Page`` inherits from :ref:`TYPO3_Neos__Template`, which are, however, not public.

:headerData: (:ref:`TYPO3_TypoScript__Array`) HTML markup to be added to the ``<head>`` of the website
:htmlAttributes: (String) attributes to be added to the outermost ``<html>`` tag
:body.templatePath: (String) path to a fluid template to be used in the page body
:body.bodyAttributes.*: (array of String) attributes to be added to be ``<body>`` tag of the website.
:body.*: ``body`` is a :ref:`TYPO3_Neos__Template`, so you can set all properties on it as well (like ``sectionName``)

Small Example::

	page = Page
	page.body.templatePath = 'resource://My.Package/Private/MyTemplate.html'
	// the following line is optional, but recommended for base CSS inclusions etc
	page.body.sectionName = 'main'

Example with content rendering::

	page.body.content.main = PrimaryContentCollection {
		nodePath = 'main'
	}

Example for HeaderData::

	page.headerData.stylesheets = Template {
		templatePath = 'resource://My.Package/Private/MyTemplate.html'
		sectionName = 'stylesheets'
	}

Example for htmlAttributes::

	page.htmlAttributes = 'data-myProperty="42"'

Example for bodyAttributes::

	page.bodyAttributes.class = 'body-css-class1 body-css-class2'

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
TYPO3.Neos.NodeTypes:Menu (!!!!?!?!?)
=====================================
TYPO3.Neos.NodeTypes:MultiColumn
================================
TYPO3.Neos.NodeTypes:TwoColumn
==============================