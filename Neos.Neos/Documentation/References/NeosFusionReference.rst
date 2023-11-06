.. _neos-Fusion-reference:

================
Fusion Reference
================

Neos.Fusion
===========

This package contains general-purpose Fusion objects, which are usable both within Neos and standalone.

.. _Neos_Fusion__Join:

Neos.Fusion:Join
----------------

Render multiple nested definitions and concatenate the results.

:[key]: (string) A nested definition (simple value, expression or object) that evaluates to a string
:[key].@ignoreProperties: (array) A list of properties to ignore from being "rendered" during evaluation
:[key].@position: (string/integer) Define the ordering of the nested definition
:@glue: (string) The glue used to join the items together (default = '').

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

	myString = Neos.Fusion:Join {
		o1 = Neos.NodeTypes:Text
		o1.@position = 'start 12'
		o2 = Neos.NodeTypes:Text
		o2.@position = 'start 5'
		o2 = Neos.NodeTypes:Text
		o2.@position = 'start'

		o3 = Neos.NodeTypes:Text
		o3.@position = '10'
		o4 = Neos.NodeTypes:Text
		o4.@position = '20'

		o5 = Neos.NodeTypes:Text
		o5.@position = 'before o6'

		o6 = Neos.NodeTypes:Text
		o6.@position = 'end'
		o7 = Neos.NodeTypes:Text
		o7.@position = 'end 20'
		o8 = Neos.NodeTypes:Text
		o8.@position = 'end 30'

		o9 = Neos.NodeTypes:Text
		o9.@position = 'after o8'
	}

If no ``@position`` property is defined, the array key is used. However, we suggest
to use ``@position`` and meaningful keys in your application, and not numeric ones.

Example of numeric keys (discouraged)::

	myString = Neos.Fusion:Join {
		10 = Neos.NodeTypes:Text
		20 = Neos.NodeTypes:Text
	}

.. _Neos_Fusion__Loop:

Neos.Fusion:Loop
----------------

Render each item in ``items`` using ``itemRenderer``.

:items: (array/Iterable, **required**) The array or iterable to iterate over (to calculate ``iterator.isLast`` items have to be ``countable``)
:itemName: (string, defaults to ``item``) Context variable name for each item
:itemKey: (string, defaults to ``itemKey``) Context variable name for each item key, when working with array
:iterationName: (string, defaults to ``iterator``) A context variable with iteration information will be available under the given name: ``index`` (zero-based), ``cycle`` (1-based), ``isFirst``, ``isLast``
:itemRenderer: (string, **required**) The renderer definition (simple value, expression or object) will be called once for every collection element, and its results will be concatenated (if ``itemRenderer`` cannot be rendered the path ``content`` is used as fallback for convenience in afx)
:@glue: (string) The glue used to join the items together (default = '').

Example using an object ``itemRenderer``::

	myLoop = Neos.Fusion:Loop {
		items = ${[1, 2, 3]}
		itemName = 'element'
		itemRenderer = Neos.Fusion:Template {
			templatePath = 'resource://...'
			element = ${element}
		}
	}


Example using an expression ``itemRenderer``::

	myLoop = Neos.Fusion:Loop {
		items = ${[1, 2, 3]}
		itemName = 'element'
		itemRenderer = ${element * 2}
	}

.. _Neos_Fusion__Map:

Neos.Fusion:Map
---------------

Render each item in ``items`` using ``itemRenderer`` and return the result as an array (opposed to *string* for :ref:`Neos_Fusion__Join`)

:items: (array/Iterable, **required**) The array or iterable to iterate over (to calculate ``iterator.isLast`` items have to be ``countable``)
:itemName: (string, defaults to ``item``) Context variable name for each item
:itemKey: (string, defaults to ``itemKey``) Context variable name for each item key, when working with array
:iterationName: (string, defaults to ``iterator``) A context variable with iteration information will be available under the given name: ``index`` (zero-based), ``cycle`` (1-based), ``isFirst``, ``isLast``
:itemRenderer: (mixed, **required**) The renderer definition (simple value, expression or object) will be called once for every collection element to render the item (if ``itemRenderer`` cannot be rendered the path ``content`` is used as fallback for convenience in afx)
:keyRenderer: (mixed, **optional**) The renderer definition (simple value, expression or object) will be called once for every collection element to render the key in the result collection.

.. _Neos_Fusion__Reduce:

Neos.Fusion:Reduce
------------------

Reduce the given items to a single value by using ``itemRenderer``.

:items: (array/Iterable, **required**) The array or iterable to iterate over (to calculate ``iterator.isLast`` items have to be ``countable``)
:itemName: (string, defaults to ``item``) Context variable name for each item
:itemKey: (string, defaults to ``itemKey``) Context variable name for each item key, when working with array
:carryName: (string, defaults to ``carry``) Context variable that contains the result of the last iteration
:iterationName: (string, defaults to ``iterator``) A context variable with iteration information will be available under the given name: ``index`` (zero-based), ``cycle`` (1-based), ``isFirst``, ``isLast``
:itemReducer: (mixed, **required**) The reducer definition (simple value, expression or object) that will be applied for every item.
:initialValue: (mixed, defaults to ``null``) The value that is passed to the first iteration or returned if the items are empty

.. _Neos_Fusion__Case:

Neos.Fusion:Case
----------------

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

	myCase = Neos.Fusion:Case {
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

The ordering of matcher definitions can be specified with the ``@position`` property (see :ref:`Neos_Fusion__Join`).
Thus, the priority of existing matchers (e.g. the default Neos document rendering) can be changed by setting or
overriding the ``@position`` property.

.. note:: The internal ``Neos.Fusion:Matcher`` object type is used to evaluate the matcher definitions which
   is based on the ``Neos.Fusion:Renderer``.

.. _Neos_Fusion__Renderer:

Neos.Fusion:Renderer
--------------------

The Renderer object will evaluate to a result using either ``renderer``, ``renderPath`` or ``type`` from the configuration.

:type: (string) Object type to render (as string)
:element.*: (mixed) Properties for the rendered object (when using ``type``)
:renderPath: (string) Relative or absolute path to render, overrules ``type``
:renderer: (mixed) Rendering definition (simple value, expression or object), overrules ``renderPath`` and ``type``

Simple Example::

	myCase = Neos.Fusion:Renderer {
		type = 'Neos.Fusion:Value'
		element.value = 'hello World'
	}

.. note:: This is especially handy if the prototype that should be rendered is determined via eel or passed via @context.

.. _Neos_Fusion__Debug:

Neos.Fusion:Debug
-----------------

Shows the result of Fusion Expressions directly.

:title: (optional) Title for the debug output
:plaintext: (boolean) If set true, the result will be shown as plaintext
:[key]: (mixed) A nested definition (simple value, expression or object), ``[key]`` will be used as key for the resulting output

Example::

  valueToDebug = "hello neos world"
  valueToDebug.@process.debug = Neos.Fusion:Debug {
        title = 'Debug of hello world'

        # Additional values for debugging
        documentTitle = ${q(documentNode).property('title')}
        documentPath = ${documentNode.path}
  }

  # the initial value is not changed, so you can define the Debug prototype anywhere in your Fusion code

.. _Neos_Fusion__DebugConsole:

Neos.Fusion:DebugConsole
-----------------

Wraps the given value with a script tag to print it to the browser console.
When used as process the script tag is appended to the processed value.

:title: (optional) Title for the debug output
:value: (mixed) The value to print to the console
:method: (string, optional) The method to call on the browser console object
:[key]: (mixed) Other arguments to pass to the console method

Example::

  renderer.@process.debug = Neos.Fusion:Debug.Console {
    title = 'My props'
    value = ${props}
    method = 'table'
  }

Multiple values::

  renderer.@process.debug = Neos.Fusion:Debug.Console {
    value = ${props.foo}
    otherValue = ${props.other}
    thirdValue = ${props.third}
  }

Color usage::

  renderer.@process.debug = Neos.Fusion:Debug.Console {
    value = ${'%c' + node.identifier}
    color = 'color: red'
  }

.. _Neos_Fusion__Component:

Neos.Fusion:Component
---------------------

Create a component

:[key]: (mixed) The public API of your component: Lazy evaluated props that will be available inside the current component's scope under the context ``props`` (is iterable)
:@private.[key]: (mixed) Can only be set inside the root component declaration: Lazy evaluated private props that will be available inside the current component's scope under the context ``private`` (is not iterable / is only a proxy)
:renderer: (mixed, **required**) The value which gets rendered

.. note:: The context ``props`` and ``private`` is only available in the components scope
   The component's scope will be available inside the `renderer` and `@private` and will extend inwards until inside another component's renderer
   That means inside `@private` it's even allowed to reference another private prop (be carefully of circular references, though!)
   But normal props are not inside the component's scope and thus cannot reference each other or ``private``

Example::

  prototype(Vendor.Site:Component) < prototype(Neos.Fusion:Component) {
      title = 'Hello World'
      titleTagName = 'h1'
      bold = false

      @private {
          computedTitle = ${String.toLowercase(props.title)}
          funnyTitle = Neos.Fusion:Value {
              value = ${props.titleTagName + " " + private.computedTitle}
          }
      }

      renderer = Neos.Fusion:Tag {
          attributes.class {
              component = 'component'
              bold = ${props.bold && 'component--bold'}
          }
          content = Neos.Fusion:Join {
              headline = Neos.Fusion:Tag {
                  tagName = ${props.titleTagName}
                  content = ${private.funnyTitle}
              }
              // nestedComponentScope = Neos.Fusion:Component {
              //   prop1 = ${props.title} // works
              //   renderer = ${props.title} // doest work!
              // }
  			}
      }
  }

.. _Neos_Fusion__Fragment:

Neos.Fusion:Fragment
--------------------

A fragment is a component that renders the given `content` without additional markup.
That way conditions can be defined for bigger chunks of afx instead of single tags.

:content: (string) The value which gets rendered

Example::

	renderer = afx`
		<Neos.Fusion:Fragment @if.isEnabled={props.enable}>
			<h1>Example</h1>
			<h2>Content</h2>
		</Neos.Fusion:Fragment>
	`

.. _Neos_Fusion__Augmenter:

Neos.Fusion:Augmenter
---------------------

Modify given html content and add attributes. The augmenter can be used as processor or as a standalone prototype

:content: (string) The content that shall be augmented
:fallbackTagName: (string, defaults to ``div``) If no single tag that can be augmented is found the content is wrapped into the fallback-tag before augmentation
:[key]: All other fusion properties are added to the html content as html attributes

Example as a standalone augmenter::

	augmentedContent = Neos.Fusion:Augmenter {

		content = Neos.Fusion:Join {
			title = Neos.Fusion:Tag {
				@if.hasContent = ${this.content}
				tagName = 'h2'
				content = ${q(node).property('title')}
			}
			text = Neos.Fusion:Tag {
				@if.hasContent = ${this.content}
				tagName = 'p'
				content = ${q(node).property('text')}
			}
		}

		fallbackTagName = 'header'

		class = 'header'
		data-foo = 'bar'
	}

Example as a processor augmenter::

	augmentedContent = Neos.Fusion:Tag {
		tagName = 'h2'
		content = 'Hello World'
		@process.augment = Neos.Fusion:Augmenter {
				class = 'header'
				data-foo = 'bar'
		}
	}

.. _Neos_Fusion__Template:

Neos.Fusion:Template
--------------------

Render a *Fluid template* specified by ``templatePath``.

:templatePath: (string, **required**) Path and filename for the template to be rendered, often a ``resource://`` URI
:partialRootPath: (string) Path where partials are found on the file system
:layoutRootPath: (string) Path where layouts are found on the file system
:sectionName: (string) The Fluid ``<f:section>`` to be rendered, if given
:[key]: (mixed) All remaining properties are directly passed into the Fluid template as template variables

Example::

	myTemplate = Neos.Fusion:Template {
		templatePath = 'resource://My.Package/Private/Templates/FusionObjects/MyTemplate.html'
		someDataAvailableInsideFluid = 'my data'
	}

	<div class="hero">
		{someDataAvailableInsideFluid}
	</div>

.. _Neos_Fusion__Value:

Neos.Fusion:Value
-----------------

Evaluate any value as a Fusion object

:value: (mixed, **required**) The value to evaluate

Example::

	myValue = Neos.Fusion:Value {
		value = 'Hello World'
	}

.. note:: Most of the time this can be simplified by directly assigning the value instead of using the ``Value`` object.

.. _Neos_Fusion__Match:

Neos.Fusion:Match
-----------------

Matches the given subject to a value

:@subject: (string, **required**) The subject to match
:@default: (mixed) The default to return when no match was found
:[key]: (mixed) Definition list, the keys will be matched to the subject and their value returned.

Example::

	myValue = Neos.Fusion:Match {
		@subject = 'hello'
		@default = 'World?'
		hello = 'Hello World'
		bye = 'Goodbye world'
	}

.. note:: This can be used to simplify many usages of :ref:`Neos_Fusion__Case` when the subject is a string.

.. _Neos_Fusion__Memo:

Neos.Fusion:Memo
-----------------

Returns the result of previous calls with the same "discriminator"

:discriminator: (string, **required**) Cache identifier
:value: (mixed) The value to evaluate and store for future calls during rendering

Example::

  prototype(My.Vendor:Expensive.Calculation) < prototype(Neos.Fusion:Memo) {
    discriminator = 'expensive-calculation'
    value = ${1+2}
  }

.. _Neos_Fusion__DataStructure:


Neos.Fusion:DataStructure
--------------------

Evaluate nested definitions as an array (opposed to *string* for :ref:`Neos_Fusion__Join`)

:[key]: (mixed) A nested definition (simple value, expression or object), ``[key]`` will be used for the resulting array key
:[key].@position: (string/integer) Define the ordering of the nested definition

.. tip:: For simple cases an expression with an array literal ``${[1, 2, 3]}`` might be easier to read

.. _Neos_Fusion__Tag:

Neos.Fusion:Tag
---------------

Render an HTML tag with attributes and optional body

:tagName: (string) Tag name of the HTML element, defaults to ``div``
:omitClosingTag: (boolean) Whether to render the element ``content`` and the closing tag, defaults to ``FALSE``
:selfClosingTag: (boolean) Whether the tag is a self-closing tag with no closing tag. Will be resolved from ``tagName`` by default, so default HTML tags are treated correctly.
:content: (string) The inner content of the element, will only be rendered if the tag is not self-closing and the closing tag is not omitted
:attributes: (iterable) Tag attributes as key-value pairs. Default is ``Neos.Fusion:DataStructure``. If a non iterable is returned the value is casted to string.
:allowEmptyAttributes: (boolean) Whether empty attributes (HTML5 syntax) should be used for empty, false or null attribute values. By default this is ``true``

Example:
^^^^^^^^

::

	htmlTag = Neos.Fusion:Tag {
		tagName = 'html'
		omitClosingTag = TRUE

		attributes {
			version = 'HTML+RDFa 1.1'
			xmlns = 'http://www.w3.org/1999/xhtml'
		}
	}

Evaluates to::

	<html version="HTML+RDFa 1.1" xmlns="http://www.w3.org/1999/xhtml">

.. _Neos_Fusion__Http_Message:

Neos.Fusion:Http.Message
------------------------

A prototype based on :ref:`Neos_Fusion__Join` for rendering an HTTP message (response). It should be used to
render documents since it generates a full HTTP response and allows to override the HTTP status code and headers.

:httpResponseHead: (:ref:`Neos_Fusion__Http_ResponseHead`) An HTTP response head with properties to adjust the status and headers, the position in the ``Join`` defaults to the very beginning
:[key]: (string) A nested definition (see :ref:`Neos_Fusion__Join`)

Example:
^^^^^^^^

::

	// Page extends from Http.Message
	//
	// prototype(Neos.Neos:Page) < prototype(Neos.Fusion:Http.Message)
	//
	page = Neos.Neos:Page {
		httpResponseHead.headers.Content-Type = 'application/json'
	}

.. _Neos_Fusion__Http_ResponseHead:

Neos.Fusion:Http.ResponseHead
-----------------------------

A helper object to render the head of an HTTP response

:statusCode: (integer) The HTTP status code for the response, defaults to ``200``
:headers.*: (string) An HTTP header that should be set on the response, the property name (e.g. ``headers.Content-Type``) will be used for the header name

.. _Neos_Fusion__ActionUri:

Neos.Fusion:ActionUri
---------------------

Built a URI to a controller action

:request: (ActionRequest, defaults to the the current ``request``) The action request the uri is build from.
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

	uri = Neos.Fusion:ActionUri {
		package = 'My.Package'
		controller = 'Registration'
		action = 'new'
	}

A special case is generating URIs for links to Neos modules. In this case often the option `request = ${request.mainRequest}` is needed
when linking to a controller outside of the context of the current subrequest.

Link to the content module::

	uri = Neos.Fusion:ActionUri {
		request = ${request.mainRequest}
		package="Neos.Neos.Ui"
		controller="Backend"
		action = 'index'
		arguments.node = ${documentNode}
	}

Link to backend modules (other than `content`)::

	uri = Neos.Fusion:ActionUri {
		request = ${request.mainRequest}
		action = "index"
		package = "Neos.Neos"
		controller = "Backend\\Module"
		arguments {
			module = 'administration/sites'
			moduleArguments {
				@action = 'edit'
				site = ${site}
			}
		}
	}

.. _Neos_Fusion__ResourceUri:

Neos.Fusion:ResourceUri
-----------------------

Build a URI to a static or persisted resource

:path: (string) Path to resource, either a path relative to ``Public`` and ``package`` or a ``resource://`` URI
:package: (string) The package key (e.g. ``'My.Package'``)
:resource: (Resource) A ``Resource`` object instead of ``path`` and ``package``
:localize: (boolean) Whether resource localization should be used, defaults to ``true``

Example::

	scriptInclude = Neos.Fusion:Tag {
		tagName = 'script'
		attributes {
			src = Neos.Fusion:ResourceUri {
				path = 'resource://My.Package/Public/Scripts/App.js'
			}
		}
	}

.. _Neos_Fusion__Link_Action:

Neos.Fusion:Link.Action
-----------------------

Renders a link pointing to a controller/action

:content: (string) content of the link tag
:href: (string, default :ref:`Neos_Fusion__ActionUri`) The href for the link tag
:[key]: (string) Other attributes for the link tag

Example::

	link = Neos.Fusion:Link.Action {
		content = "register"
		class="action-link"
		href.package = 'My.Package'
		href.controller = 'Registration'
		href.action = 'new'
	}

	link = afx`
		<Neos.Fusion:Link.Action class="action-link" href.package="My.Package" href.controller="Registration" href.action="new">
			register
		</Neos.Fusion:Link.Action>
	`

Link to the content-module in afx::

 <Neos.Fusion:Link.Action
		href.request={request.mainRequest}
		href.action="index"
		href.package="Neos.Neos.Ui"
		href.controller="Backend"
		href.arguments.node={node}
	>
		to content module
	</Neos.Fusion:Link.Action>

Link to backend-modules other than the content-module::

	<Neos.Fusion:Link.Action
		href.request={request.mainRequest}
		href.action="index"
		href.package="Neos.Neos"
		href.controller="Backend\\Module"
		href.arguments.module='administration/sites'
		href.arguments.moduleArguments.@action='index'
	>
		to site module
	</Neos.Fusion:Link.Action>

.. _Neos_Fusion__Link_Resource:

Neos.Fusion:Link.Resource
-------------------------

Renders a link pointing to a resource

:content: (string) content of the link tag
:href: (string,  default :ref:`Neos_Fusion__ResourceUri`) The href for the link tag
:[key]: (string) Other attributes for the link tag

Example::

	link = afx`
		<Neos.Fusion:Link.Resource class="resource-link" href.path="resource://Some.Package/Public/Images/SomeImage.png">
			Some Link
		</Neos.Fusion:Link.Resource>
	`

Neos.Fusion:CanRender
---------------------

Check whether a Fusion prototype can be rendered. For being renderable a prototype must exist and have an implementation class, or inherit from an existing renderable prototype. The implementation class can be defined indirectly via base prototypes.

:type: (string) The prototype name that is checked
:path: (string) The fusion path name that is checked

Example::

	canRender = Neos.Fusion:CanRender {
		type = 'My.Package:Prototype'
	}

Neos.Neos Fusion Objects
=============================

The Fusion objects defined in the Neos package contain all Fusion objects which
are needed to integrate a site. Often, it contains generic Fusion objects
which do not need a particular node type to work on.

.. _Neos_Neos__Page:

Neos.Neos:Page
--------------
Subclass of :ref:`Neos_Fusion__Http_Message`, which is based on :ref:`Neos_Fusion__Join`. Main entry point
into rendering a page; responsible for rendering the ``<html>`` tag and everything inside.

:doctype: (string) Defaults to ``<!DOCTYPE html>``
:htmlTag: (:ref:`Neos_Fusion__Tag`) The opening ``<html>`` tag
:htmlTag.attributes: (:ref:`Neos_Fusion__DataStructure`) Attributes for the ``<html>`` tag
:headTag: (:ref:`Neos_Fusion__Tag`) The opening ``<head>`` tag
:head: (:ref:`Neos_Fusion__Join`) HTML markup for the ``<head>`` tag
:head.titleTag: (:ref:`Neos_Fusion__Tag`) The ``<title>`` tag
:head.javascripts: (:ref:`Neos_Fusion__Join`) Script includes in the head should go here
:head.stylesheets: (:ref:`Neos_Fusion__Join`) Link tags for stylesheets in the head should go here
:bodyTag: (:ref:`Neos_Fusion__Tag`) The opening ``<body>`` tag
:bodyTag.attributes: (:ref:`Neos_Fusion__DataStructure`) Attributes for the ``<body>`` tag
:body: (:ref:`Neos_Fusion__Join`) HTML markup for the ``<body>`` tag.
:body.javascripts: (:ref:`Neos_Fusion__Join`) Body footer JavaScript includes

Examples:
^^^^^^^^^

Rendering a simple page:
""""""""""""""""""""""""

Fusion::

	page = Neos.Neos:Page
	page.body {
    header = Vendor.Site:Fragment.Header
    content = afx`
      <div class="container">
        <Neos.Neos:ContentCollection nodePath="main"/>
      </div>
    `
    footer = Vendor.Site:Fragment.Header
  }

Including stylesheets from a template section in the head:
""""""""""""""""""""""""""""""""""""""""""""""""""""""""""

Fusion::

	page.head.stylesheets {
	  mySite = afx`
	    <link rel="stylesheet" href={StaticResource.uri('Vendor.Site', 'Public/Styles/Main.css')} />
	  `
	}

Adding body attributes with ``bodyTag.attributes``:
"""""""""""""""""""""""""""""""""""""""""""""""""""

::

	page.bodyTag.attributes.class = 'body-css-class1 body-css-class2'


.. _Neos_Neos__ContentCollection:

Neos.Neos:ContentCollection
---------------------------

Render nested content from a ``ContentCollection`` node. Individual nodes are rendered using the
:ref:`Neos_Neos__ContentCase` object.

:nodePath: (string, **required**) The relative node path of the ``ContentCollection`` (e.g. ``'main'``)
:@context.node: (Node) The content collection node, resolved from ``nodePath`` by default
:tagName: (string) Tag name for the wrapper element
:attributes: (:ref:`Neos_Fusion__DataStructure`) Tag attributes for the wrapper element

Example::

	page.body {
		content {
			main = Neos.Neos:ContentCollection {
				nodePath = 'main'
			}
			footer = Neos.Neos:ContentCollection {
				nodePath = 'footer'
			}
		}
	}

.. _Neos_Neos__ContentCase:

Neos.Neos:ContentCase
---------------------

Render a content node, extends :ref:`Neos_Fusion__Case`. This is a prototype that is used by the default content
rendering (:ref:`Neos_Neos__ContentCollection`) and can be extended to add custom matchers.

:default: Default matcher that renders a prototype of the same name as the node type name
:[key]: Additional matchers (see :ref:`Neos_Fusion__Case`)

.. _Neos_Neos__Content:

Neos.Neos:Content
-----------------

Base type to render content nodes, extends :ref:`Neos_Fusion__Template`. This prototype is extended by the
auto-generated Fusion to define prototypes for each node type extending ``Neos.Neos:Content``.

:templatePath: (string) The template path and filename, defaults to ``'resource://[packageKey]/Private/Templates/NodeTypes/[nodeType].html'`` (for auto-generated prototypes)
:[key]: (mixed) Template variables, all node type properties are available by default (for auto-generated prototypes)
:attributes: (:ref:`Neos_Fusion__DataStructure`) Extensible attributes, used in the default templates

Example::

	prototype(My.Package:MyContent) < prototype(Neos.Neos:Content) {
		templatePath = 'resource://My.Package/Private/Templates/NodeTypes/MyContent.html'
		# Auto-generated for all node type properties
		# title = ${q(node).property('title')}
	}


.. _Neos_Neos__ContentComponent:

Neos.Neos:ContentComponent
--------------------------

Base type to render component based content-nodes, extends :ref:`Neos_Fusion__Component`.
Features the same API as :ref:`Neos_Fusion__Component`, but it adds content element wrapping, so the node is correctly detected by the Neos.Ui


.. _Neos_Neos__Editable:

Neos.Neos:Editable
------------------

Create an editable tag for a property. In the frontend, only the content of the property gets rendered.

:node: (node) A node instance that should be used to read the property. Default to `${node}`
:property: (string) The name of the property which should be accessed
:block: (boolean) Decides if the editable tag should be a block element (`div`) or an inline element (`span`). Default to `true`


Example::

	title = Neos.Neos:Editable {
		property = 'title'
		block = false
	}


.. _Neos_Neos__Plugin:

Neos.Neos:Plugin
----------------

Base type to render plugin content nodes or static plugins. A *plugin* is a Flow controller that can implement
arbitrary logic.

:package: (string, **required**) The package key (e.g. `'My.Package'`)
:subpackage: (string) The subpackage, defaults to empty
:controller: (array) The controller name (e.g. 'Registration')
:action: (string) The action name, defaults to `'index'`
:argumentNamespace: (string) Namespace for action arguments, will be resolved from node type by default
:[key]: (mixed) Pass an internal argument to the controller action (access with argument name ``__key``)

Example::

	prototype(My.Site:Registration) < prototype(Neos.Neos:Plugin) {
		package = 'My.Site'
		controller = 'Registration'
	}

Example with argument passed to controller action::

  prototype(My.Site:Registration) < prototype(Neos.Neos:Plugin) {
    package = 'My.Site'
    controller = 'Registration'
    action = 'register'
    additionalArgument = 'foo'
  }

Get argument in controller action::

  public function registerAction()
  {
    $additionalArgument = $this->request->getInternalArgument('__additionalArgument');
    [...]
  }

.. _Neos_Neos__Menu:

Neos.Neos:Menu
--------------

Render a menu with items for nodes.

:attributes: (:ref:`Neos_Fusion__DataStructure`) attributes for the whole menu

The following properties are passed over to :ref:`Neos_Neos__MenuItems` internally:

:node: (Node) The current node used to calculate the itemStates, and ``startingPoint`` (if not defined explicitly). Defaults to ``node`` from the fusion context
:entryLevel: (integer) Define the startingPoint of the menu relatively. Non negative values specify this as n levels below root. Negative values are n steps up from ``node`` or ``startingPoint`` if defined. Defaults to ``1`` if no ``startingPoint`` is set otherwise ``0``
:lastLevel: (optional, integer) Restrict the depth of the menu relatively. Positive values specify this as n levels below root. Negative values specify this as n steps up from ``node``. Defaults to ``null``
:maximumLevels: (integer) Restrict the maximum depth of items in the menu (relative to ``entryLevel``)
:startingPoint: (optional, Node) The node where the menu hierarchy starts. If not specified explicitly the startingPoint is calculated from (``node`` and ``entryLevel``), defaults to ``null``
:filter: (string) Filter items by node type (e.g. ``'!My.Site:News,Neos.Neos:Document'``), defaults to ``'Neos.Neos:Document'``. The filter is only used for fetching subItems and is ignored for determining the ``startingPoint``
:renderHiddenInIndex: (boolean) Whether nodes with ``hiddenInIndex`` should be rendered, defaults to ``false``
:calculateItemStates: (boolean) activate the *expensive* calculation of item states defaults to ``false``.
:itemCollection: (optional, array of Nodes) Explicitly set the Node items for the menu (taking precedence over ``startingPoints`` and ``entryLevel`` and ``lastLevel``). The children for each ``Node`` will be fetched taking the ``maximumLevels`` property into account.

Example::

	menu = Neos.Neos:Menu {
	  attributes.class = 'menu'
	  maximumLevels = 3
	}

.. note:: The ``items`` of the ``Menu`` are internally calculated with the prototype :ref:`Neos_Neos__MenuItems` which
   you can use directly aswell.

.. note:: The ``rendering`` of the ``Menu`` is performed with the prototype :ref:`Neos_Neos__MenuItemListRenderer`.
   If the rendering does not suit your useCase it we recommended to create your own variants of the menu and renderer prototype.

.. _Neos_Neos__BreadcrumbMenu:

Neos.Neos:BreadcrumbMenu
------------------------

Render a breadcrumb (ancestor documents).

:attributes: (:ref:`Neos_Fusion__DataStructure`) html attributes for the rendered list

The following properties are passed over to :ref:`Neos_Neos__BreadcrumbMenuItems` internally:

:node: (Node) The current node to render the menu for. Defaults to ``documentNode`` from the fusion context
:maximumLevels: (integer) Restrict the maximum depth of items in the menu, defaults to ``0``
:renderHiddenInIndex: (boolean) Whether nodes with ``hiddenInIndex`` should be rendered (the current documentNode is always included), defaults to ``false``.
:calculateItemStates: (boolean) activate the *expensive* calculation of item states defaults to ``false``

Example::

	breadcrumb = Neos.Neos:BreadcrumbMenu

.. note:: The ``items`` of the ``BreadcrumbMenu`` are internally calculated with the prototype :ref:`Neos_Neos__MenuItems` which
   you can use directly aswell.

.. note:: The ``rendering`` of the ``BreadcrumbMenu`` is performed with the prototype :ref:`Neos_Neos__MenuItemListRenderer`.
   If the rendering does not suit your useCase it we recommended to create your own variants of the menu and renderer prototype.

.. _Neos_Neos__DimensionMenu:
.. _Neos_Neos__DimensionsMenu:

Neos.Neos:DimensionsMenu
------------------------

Create links to other node variants (e.g. variants of the current node in other dimensions).

:attributes: (:ref:`Neos_Fusion__DataStructure`) attributes for the whole menu

The following fusion properties are passed over to :ref:`Neos_Neos__DimensionsMenuItems` internally:

:node: (Node) The current node used to calculate the Menu. Defaults to ``documentNode`` from the fusion context
:dimension: (optional, string): name of the dimension which this menu should be based on. Example: "language".
:presets: (optional, array): If set, the presets rendered will be taken from this list of preset identifiers
:includeAllPresets: (boolean, default **false**) If TRUE, include all presets, not only allowed combinations
:renderHiddenInIndex: (boolean, default **true**) If TRUE, render nodes which are marked as "hidded-in-index"
:calculateItemStates: (boolean) activate the *expensive* calculation of item states defaults to ``false``

.. note:: The ``items`` of the ``DimensionsMenu`` are internally calculated with the prototype :ref:`Neos_Neos__DimensionsMenuItems` which
   you can use directly aswell.

.. note:: The ``rendering`` of the ``DimensionsMenu`` is performed with the prototype :ref:`Neos_Neos__MenuItemListRenderer`.
   If the rendering does not suit your useCase it we recommended to create your own variants of the menu and renderer prototype.

.. _Neos_Neos__MenuItemListRenderer:

Neos.Neos:MenuItemListRenderer
-------------------------------

A very basic renderer that takes a list of MenuItems and renders the result as unordered list. If item states were calculated
they are applied as classnames to the list items.

:items: (array): The MenuItems as generated by :ref:`Neos_Neos__MenuItems`, :ref:`Neos_Neos__DimensionsMenuItems`, :ref:`Neos_Neos__BreadcrumbMenuItems`
:attributes: (optional, array): The attributes to apply on the outer list

Neos.Neos:MenuItems
-------------------

Create a list of menu-items items for nodes.

:node: (Node) The current node used to calculate the itemStates, and ``startingPoint`` (if not defined explicitly). Defaults to ``node`` from the fusion context
:entryLevel: (integer) Define the startingPoint of the menu relatively. Non negative values specify this as n levels below root. Negative values are n steps up from ``node`` or ``startingPoint`` if defined. Defaults to ``1`` if no ``startingPoint`` is set otherwise ``0``
:lastLevel: (optional, integer) Restrict the depth of the menu relatively. Positive values specify this as n levels below root. Negative values specify this as n steps up from ``node``. Defaults to ``null``
:maximumLevels: (integer) Restrict the maximum depth of items in the menu (relative to ``entryLevel``)
:startingPoint: (optional, Node) The node where the menu hierarchy starts. If not specified explicitly the startingPoint is calculated from (``node`` and ``entryLevel``), defaults to ``null``
:filter: (string) Filter items by node type (e.g. ``'!My.Site:News,Neos.Neos:Document'``), defaults to ``'Neos.Neos:Document'``. The filter is only used for fetching subItems and is ignored for determining the ``startingPoint``
:renderHiddenInIndex: (boolean) Whether nodes with ``hiddenInIndex`` should be rendered, defaults to ``false``
:calculateItemStates: (boolean) activate the *expensive* calculation of item states defaults to ``false``.
:itemCollection: (optional, array of Nodes) Explicitly set the Node items for the menu (taking precedence over ``startingPoints`` and ``entryLevel`` and ``lastLevel``). The children for each ``Node`` will be fetched taking the ``maximumLevels`` property into account.

Note::

MenuItems item properties:
^^^^^^^^^^^^^^^^^^^^^^^^^

:node: (Node) A node instance (with resolved shortcuts) that should be used to link to the item
:originalNode: (Node) Original node for the item
:state: (string) Menu state of the item: ``'normal'``, ``'current'`` (the current node) or ``'active'`` (ancestor of current node)
:label: (string) Full label of the node
:menuLevel: (integer) Menu level the item is rendered on
:uri: (string) Frontend URI of the node
:children: (array) array of ``MenuItem`` instances

Examples:
^^^^^^^^^

::

	menuItems = Neos.Neos:MenuItems {
		entryLevel = 1
		maximumLevels = 3
	}

MenuItems including site node:
""""""""""""""""""""""""""""""

::

	menuItems = Neos.Neos:MenuItems {
		itemCollection = ${q(site).add(q(site).children('[instanceof Neos.Neos:Document]')).get()}
	}

Menu with custom starting point:
""""""""""""""""""""""""""""""""

::

	menuItems = Neos.Neos:MenuItems {
		entryLevel = 2
		maximumLevels = 1
		startingPoint = ${q(site).children('[uriPathSegment="metamenu"]').get(0)}
	}

Menu with absolute uris:
""""""""""""""""""""""""

::

	menuItems = Neos.Neos:MenuItems {
		itemUriRenderer {
			absolute = true
		}
	}

.. _Neos_Neos__BreadcrumbMenuItems:

Neos.Neos:BreadcrumbMenuItems
-----------------------------

Create a list of of menu-items for the breadcrumb (ancestor documents).

:node: (Node) The current node to render the menu for. Defaults to ``documentNode`` from the fusion context
:maximumLevels: (integer) Restrict the maximum depth of items in the menu, defaults to ``0``
:renderHiddenInIndex: (boolean) Whether nodes with ``hiddenInIndex`` should be rendered (the current documentNode is always included), defaults to ``false``.
:calculateItemStates: (boolean) activate the *expensive* calculation of item states defaults to ``false``

Example::

	breadcrumbItems = Neos.Neos:BreadcrumbMenuItems

.. _Neos_Neos__DimensionsMenuItems:

Neos.Neos:DimensionsMenuItems
-----------------------------

Create a list of menu-items for other node variants (e.g. variants of the current node in other dimensions) by using this Fusion object.

If the ``dimension`` setting is given, the menu will only include items for this dimension, with all other configured
dimension being set to the value(s) of the current node. Without any ``dimension`` being configured, all possible
variants will be included.

If no node variant exists for the preset combination, a ``NULL`` node will be included in the item with a state ``absent``.

:dimension: (optional, string): name of the dimension which this menu should be based on. Example: "language".
:presets: (optional, array): If set, the presets rendered will be taken from this list of preset identifiers
:includeAllPresets: (boolean, default **false**) If TRUE, include all presets, not only allowed combinations
:renderHiddenInIndex: (boolean, default **true**) If TRUE, render nodes which are marked as "hidded-in-index"
:calculateItemStates: (boolean) activate the *expensive* calculation of item states defaults to ``false``

Each ``item`` has the following properties:

:node: (Node) The current node used to calculate the Menu. Defaults to ``documentNode`` from the fusion context
:state: (string) Menu state of the item: ``normal``, ``current`` (the current node), ``absent``
:label: (string) Label of the item (the dimension preset label)
:menuLevel: (integer) Menu level the item is rendered on
:dimensions: (array) Dimension values of the node, indexed by dimension name
:targetDimensions: (array) The target dimensions, indexed by dimension name and values being arrays with ``value``, ``label`` and ``isPinnedDimension``

Examples
^^^^^^^^

Minimal Example, outputting a menu with all configured dimension combinations::

	variantMenuItems = Neos.Neos:DimensionsMenuItems

This example will create two menus, one for the 'language' and one for the 'country' dimension::

	languageMenuItems = Neos.Neos:DimensionsMenuItems {
		dimension = 'language'
	}
	countryMenuItems = Neos.Neos:DimensionsMenuItems {
		dimension = 'country'
	}

If you only want to render a subset of the available presets or manually define a specific order for a menu,
you can override the "presets"::

	languageMenuItems = Neos.Neos:DimensionsMenuItems {
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

	languageMenuItems = Neos.Neos:DimensionsMenuItems {
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

.. _Neos_Neos__NodeUri:

Neos.Neos:NodeUri
-----------------

Build a URI to a node. Accepts the same arguments as the node link/uri view helpers.

:node: (string/Node) A node object or a node path (relative or absolute) or empty to resolve the current document node
:format: (string) An optional request format (e.g. ``'html'``)
:section: (string) An optional fragment (hash) for the URI
:additionalParams: (array) Additional URI query parameters.
:argumentsToBeExcludedFromQueryString: (array) Query parameters to exclude for ``addQueryString``
:addQueryString: (boolean) Whether to keep current query parameters, defaults to ``FALSE``
:absolute: (boolean) Whether to create an absolute URI, defaults to ``FALSE``
:baseNodeName: (string) Base node context variable name (for relative paths), defaults to ``'documentNode'``

Example::

	nodeLink = Neos.Neos:NodeUri {
		node = ${q(node).parent().get(0)}
	}


.. _Neos_Neos__NodeLink:

Neos.Neos:NodeLink
-----------------

Renders an anchor tag pointing to the node given via the argument. Based on :ref:`Neos_Neos__NodeUri`.
The link text is the node label, unless overridden.

:\*: All :ref:`Neos_Neos__NodeUri` properties
:attributes: (:ref:`Neos_Fusion__DataStructure`) Link tag attributes
:content: (string) The label of the link, defaults to ``node.label``.

Example::

	nodeLink = Neos.Neos:NodeLink {
		node = ${q(node).parent().get(0)}
	}

.. note::
   By default no ``title`` is generated. By setting ``attributes.title = ${node.label}`` the label is rendered as title.

.. _Neos_Neos__ImageUri:

Neos.Neos:ImageUri
------------------

Get a URI to a (thumbnail) image for an asset.

:asset: (Asset) An asset object (``Image``, ``ImageInterface`` or other ``AssetInterface``)
:width: (integer) Desired width of the image
:maximumWidth: (integer) Desired maximum height of the image
:height: (integer) Desired height of the image
:maximumHeight: (integer) Desired maximum width of the image
:allowCropping: (boolean) Whether the image should be cropped if the given sizes would hurt the aspect ratio, defaults to ``FALSE``
:allowUpScaling: (boolean) Whether the resulting image size might exceed the size of the original image, defaults to ``FALSE``
:async: (boolean) Return asynchronous image URI in case the requested image does not exist already, defaults to ``FALSE``
:quality: (integer) Image quality, from 0 to 100
:format: (string) Format for the image, jpg, jpeg, gif, png, wbmp, xbm, webp and bmp are supported
:preset: (string) Preset used to determine image configuration, if set all other resize attributes will be ignored

Example::

	logoUri = Neos.Neos:ImageUri {
		asset = ${q(node).property('image')}
		width = 100
		height = 100
		allowCropping = TRUE
		allowUpScaling = TRUE
	}

.. _Neos_Neos__ImageTag:

Neos.Neos:ImageTag
------------------

Render an image tag for an asset.

:\*: All :ref:`Neos_Neos__ImageUri` properties
:attributes: (:ref:`Neos_Fusion__DataStructure`) Image tag attributes

Per default, the attribute loading is set to ``'lazy'``. To fetch a resource immediately, you can set ``attributes.loading``
to ``null``, ``false`` or ``'eager'``.

Example::

	logoImage = Neos.Neos:ImageTag {
		asset = ${q(node).property('image')}
		maximumWidth = 400
		attributes.alt = 'A company logo'
	}

.. _Neos_Neos__ConvertUris:

Neos.Neos:ConvertUris
---------------------

Convert internal node and asset URIs (``node://...`` or ``asset://...``) in a string to public URIs and allows for
overriding the target attribute for external links and resource links.

:value: (string) The string value, defaults to the ``value`` context variable to work as a processor by default
:node: (Node) The current node as a reference, defaults to the ``node`` context variable
:externalLinkTarget: (string) Override the target attribute for external links, defaults to ``_blank``. Can be disabled with an empty value.
:resourceLinkTarget: (string) Override the target attribute for resource links, defaults to ``_blank``. Can be disabled with an empty value.
:forceConversion: (boolean) Whether to convert URIs in a non-live workspace, defaults to ``FALSE``
:absolute: (boolean) Can be used to convert node URIs to absolute links, defaults to ``FALSE``
:setNoOpener: (boolean) Sets the rel="noopener" attribute to external links, which is good practice, defaults to ``TRUE``
:setExternal: (boolean) Sets the rel="external" attribute to external links. Defaults to ``TRUE``

Example::

	prototype(My.Site:Special.Type) {
		title.@process.convertUris = Neos.Neos:ConvertUris
	}

.. _TYPO3_Neos__ContentElementWrapping:

Neos.Neos:ContentElementWrapping
--------------------------------

Processor to augment rendered HTML code with node metadata that allows the Neos UI to select the node and show
node properties in the inspector. This is especially useful if your renderer prototype is not derived from ``Neos.Neos:Content``.

The processor expects being applied on HTML code with a single container tag that is augmented.

:node: (Node) The node of the content element. Optional, will use the Fusion context variable ``node`` by default.

Example::

	prototype(Vendor.Site:ExampleContent) {
		value = '<div>Example</div>'

		# The following line must not be removed as it adds required meta data
		# to edit content elements in the backend
		@process.contentElementWrapping = Neos.Neos:ContentElementWrapping {
			@position = 'end'
		}
	}


.. _TYPO3_Neos__ContentElementEditable:

Neos.Neos:ContentElementEditable
--------------------------------

Processor to augment an HTML tag with metadata for inline editing to make a rendered representation of a property editable.

The processor expects beeing applied to an HTML tag with the content of the edited property.

:node: (Node) The node of the content element. Optional, will use the Fusion context variable ``node`` by default.
:property: (string) Node property that should be editable

Example::

	renderer = Neos.Fusion:Tag {
		tagName = 'h1'
		content = ${q(node).property('title')}
		@process.contentElementEditableWrapping = Neos.Neos:ContentElementEditable {
			property = 'title'
		}
	}


Deprecated Fusion Prototypes
----------------------------

The following prototypes are deprecated and will be removed in future versions of Neos!

.. _Neos_Fusion__UriBuilder:

Neos.Fusion:UriBuilder
~~~~~~~~~~~~~~~~~~~~~~

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

.. note:: The use of ``Neos.Fusion:UriBuilder`` is deprecated. Use :ref:`Neos_Fusion__ActionUri` instead.

Example::

	uri = Neos.Fusion:UriBuilder {
		package = 'My.Package'
		controller = 'Registration'
		action = 'new'
	}

Removed Fusion Prototypes
-------------------------

The following Fusion Prototypes have been removed:

.. _Neos_Fusion__Array:
* `Neos.Fusion:Array` replaced with :ref:`Neos_Fusion__Join`
.. _Neos_Fusion__RawArray:
* `Neos.Fusion:RawArray` replaced with :ref:`Neos_Fusion__DataStructure`
.. _Neos_Fusion__Collection:
* `Neos.Fusion:Collection` replaced with :ref:`Neos_Fusion__Loop`
.. _Neos_Fusion__RawCollection:
* `Neos.Fusion:RawCollection` replaced with :ref:`Neos_Fusion__Map`
.. _Neos_Fusion__Attributes:
* `Neos.Fusion:Attributes` use property `attributes` in :ref:`Neos_Fusion__Tag`



