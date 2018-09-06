.. _inside-fusion:

=================
Inside Fusion
=================

In this chapter, Fusion will be explained in a step-by-step fashion, focusing on the different
internal parts, the syntax of these and the semantics.

Fusion is fundamentally a *hierarchical, prototype based processing language*:

* It is *hierarchical* because the content it should render is also hierarchically structured.

* It is *prototype based* because it allows to define properties for *all instances* of a certain
  Fusion object type. It is also possible to define properties not for all instances, but only
  for *instances inside a certain hierarchy*. Thus, the prototype definitions are hierarchically-scoped
  as well.

* It is a *processing language* because it processes the values in the *context* into a *single output
  value*.

In the first part of this chapter, the syntactic and semantic features of the Fusion, Eel and FlowQuery
languages are explained. Then, the focus will be on the design decisions and goals of Fusion, to provide
a better understanding of the main objectives while designing the language.

Goals of Fusion
===================

Fusion should **cater to both planned and unplanned extensibility**. This means it should provide
ways to adjust and extend its behavior in places where this is to be expected. At the same time it
should also be possible to adjust and extend in any other place without having to apply dirty hacks.

Fusion should be **usable in standalone, extensible applications** outside of Neos. The use of a
flexible language for configuration of (rendering) behavior is beneficial for most complex applications.

Fusion should make **out-of-band rendering** easy to do. This should ease content generation for
technologies like AJAX or edge-side includes (ESI).

Fusion should make **multiple renderings of the same content** possible. It should allow placement
of the same content (but possibly in different representations) on the same page multiple times.

Fusion's **syntax should be familiar to the user**, so that existing knowledge can be leveraged.
To achieve this, Fusion takes inspiration from CSS selectors, jQuery and other technologies that
are in widespread use in modern frontend development.

.. TODO there is probably more to say here...

Fusion files
================

Fusion is read from files. In the context of Neos, some of these files are loaded automatically,
and Fusion files can be split into parts to organize things as needed.

Automatic Fusion file inclusion
-----------------------------------

All Fusion files are expected to be in the package subfolder *Resources/Private/Fusion*. Neos will
automatically include the file *Root.fusion* for the current site package (package which resides in
Packages/Sites and has the type "neos-site" in its composer manifest).

To automatically include *Root.fusion* files from other packages, you will need to add those packages to
the configuration setting ``Neos.Neos.fusion.autoInclude``::

  # Settings.yaml

  Neos:
    Neos:
      fusion:
        autoInclude:
          Your.Package: true

Neos will then autoinclude *Root.fusion* files from these packages in the order defined by package management.
Files with a name other than *Root.fusion* **will never be auto-included** even with that setting. You
will need to include them manually in your *Root.fusion*.

Manual Fusion file inclusion
--------------------------------

In any Fusion file further files can be included using the ``include`` statement. The path is either
relative to the current file or can be given with the ``resource`` wrapper::

	include: NodeTypes/CustomElements.fusion
	include: resource://Acme.Demo/Private/Fusion/Quux.fusion

In addition to giving exact filenames, globbing is possible in two variants::

	# Include all .fusion files in NodeTypes
	include: NodeTypes/*

	# Include all .fusion files in NodeTypes and it's subfolders recursively
	include: NodeTypes/**/*

The first includes all Fusion files in the *NodeTypes* folder, the latter will recursively include all
Fusion files in NodeTypes and any folders below.

The globbing can be combined with the ``resource`` wrapper::

	include: resource://Acme.Demo/Private/Fusion/NodeTypes/*
	include: resource://Acme.Demo/Private/Fusion/**/*

Fusion Objects
==================

Fusion is a language to describe *Fusion objects*. A Fusion object has some *properties*
which are used to configure the object. Additionally, a Fusion object has access to a *context*,
which is a list of variables. The goal of a Fusion object is to take the variables from the
context, and transform them to the desired *output*, using its properties for configuration as needed.

Thus, Fusion objects take some *input* which is given through the context and the properties, and
produce a single *output value*. Internally, they can modify the context, and trigger rendering of
nested Fusion objects: This way, a big task (like rendering a whole web page) can be split into
many smaller tasks (render a single image, render some text, ...): The results of the small tasks are
then put together again, forming the final end result.

Fusion object nesting is a fundamental principle of Fusion. As Fusion objects call nested
Fusion objects, the rendering process forms a *tree* of Fusion objects.

Fusion objects are implemented by a PHP class, which is instantiated at runtime. A single PHP class
is the basis for many Fusion objects. We will highlight the exact connection between Fusion
objects and their PHP implementations later.

A Fusion object can be instantiated by assigning it to a Fusion path, such as::

	foo = Page
	# or:
	my.object = Text
	# or:
	my.image = Neos.Neos.ContentTypes:Image

The name of the to-be-instantiated Fusion prototype is listed without quotes.

By convention, Fusion paths (such as ``my.object``) are written in ``lowerCamelCase``, while
Fusion prototypes (such as ``Neos.Neos.ContentTypes:Image``) are written in ``UpperCamelCase``.

It is possible to set *properties* on the newly created Fusion objects::

	foo.myProperty1 = 'Some Property which Page can access'
	my.object.myProperty1 = "Some other property"
	my.image.width = ${q(node).property('foo')}

Property values that are strings have to be quoted (with either single or double quotes). A property
can also be an *Eel expression* (which are explained in :ref:`eel-flowquery`.)

To reduce typing overhead, curly braces can be used to "abbreviate" long Fusion paths::

	my {
	  image = Image
	  image.width = 200

	  object {
	    myProperty1 = 'some property'
	  }
	}

Instantiating a Fusion object and setting properties on it in a single pass is also possible.
All three examples mean exactly the same::

	someImage = Image
	someImage.foo = 'bar'

	# Instantiate object, set property one after each other
	someImage = Image
	someImage {
	  foo = 'bar'
	}

	# Instantiate an object and set properties directly
	someImage = Image {
	  foo = 'bar'
	}

Fusion Objects are Side-Effect Free
---------------------------------------

When Fusion objects are rendered, they are allowed to modify the Fusion context
(they can add or override variables); and can invoke other Fusion objects.
After rendering, however, the parent Fusion object must make sure to clean up the context,
so that it contains exactly the state it had before the rendering.

The API helps to enforce this, as the Fusion context is a *stack*: The only thing the
developer of a Fusion object needs to make sure is that if he adds some variable to
the stack, effectively creating a new stack frame, he needs to remove exactly this stack
frame after rendering again.

This means that a Fusion object can only manipulate Fusion objects *below it*,
but not following or preceding it.

In order to enforce this, Fusion objects are furthermore only allowed to communicate
through the Fusion Context; and they are never allowed to be invoked directly: Instead,
all invocations need to be done through the *Fusion Runtime*.

All these constraints make sure that a Fusion object is *side-effect free*, leading
to an important benefit: If somebody knows the exact path towards a Fusion object together
with its context, it can be rendered in a stand-alone manner, exactly as if it was embedded
in a bigger element. This enables, for example, rendering parts of pages with different cache life-
times, or the effective implementation of AJAX or ESI handlers reloading only parts of a
website.

Fusion Prototypes
=====================

When a Fusion object is instantiated (i.e. when you type ``someImage = Image``) the
*Fusion Prototype* for this object is *copied* and is used as a basis for the new object.
The prototype is defined using the following syntax::

	prototype(MyImage) {
		width = '500px'
		height = '600px'
	}

When the above prototype is instantiated, the instantiated object will have all the properties
of the copied prototype. This is illustrated through the following example::

	someImage = MyImage
	# now, someImage will have a width of 500px and a height of 600px

	someImage.width = '100px'
	# now, we have overridden the height of "someImage" to be 100px.

.. admonition:: Prototype- vs. class-based languages

	There are generally two major "flavours" of object-oriented languages. Most languages
	(such as PHP, Ruby, Perl, Java, C++) are *class-based*, meaning that they explicitly
	distinguish between the place where behavior for a given object is defined (the "class")
	and the runtime representation which contains the data (the "instance").

	Other languages such as JavaScript are prototype-based, meaning that there is no distinction
	between classes and instances: At object creation time, all properties and methods of
	the object's *prototype* (which roughly corresponds to a "class") are copied (or otherwise
	referenced) to the *instance*.

	Fusion is a *prototype-based language* because it *copies* the Fusion Prototype
	to the instance when an object is evaluated.

Prototypes in Fusion are *mutable*, which means that they can easily be modified::

	prototype(MyYouTube) {
		width = '100px'
		height = '500px'
	}

	# you can change the width/height
	prototype(MyYouTube).width = '400px'
	# or define new properties:
	prototype(MyYouTube).showFullScreen = ${true}

Defining and instantiating a prototype from scratch is not the only way to define and
instantiate them. You can also use an *existing Fusion prototype* as basis
for a new one when needed. This can be done by *inheriting* from a Fusion prototype
using the ``<`` operator::

	prototype(MyImage) < prototype(Neos.Neos:Content)

	# now, the MyImage prototype contains all properties of the Template
	# prototype, and can be further customized.

This implements *prototype inheritance*, meaning that the "subclass" (``MyImage`` in the example
above) and the "parent class (``Content``) are still attached to each other: If a property
is added to the parent class, this also applies to the subclass, as in the following example::

	prototype(Neos.Neos:Content).fruit = 'apple'
	prototype(Neos.Neos:Content).meal = 'dinner'

	prototype(MyImage) < prototype(Neos.Neos:Content)
	# now, MyImage also has the properties "fruit = apple" and "meal = dinner"

	prototype(Neos.Neos:Content).fruit = 'Banana'
	# because MyImage *extends* Content, MyImage.fruit equals 'Banana' as well.

	prototype(MyImage).meal = 'breakfast'
	prototype(Neos.Fusion:Content).meal = 'supper'
	# because MyImage now has an *overridden* property "meal", the change of
	# the parent class' property is not reflected in the MyImage class

Prototype inheritance can only be defined *globally*, i.e. with a statement of the
following form::

	prototype(Foo) < prototype(Bar)

It is not allowed to nest prototypes when defining prototype inheritance, so the
following examples are **not valid Fusion** and will result in an exception::

	prototype(Foo) < some.prototype(Bar)
	other.prototype(Foo) < prototype(Bar)
	prototype(Foo).prototype(Bar) < prototype(Baz)

While it would be theoretically possible to support this, we have chosen not to do
so in order to reduce complexity and to keep the rendering process more understandable.
We have not yet seen a Fusion example where a construct such as the above would be
needed.

Hierarchical Fusion Prototypes
----------------------------------

One way to flexibly adjust the rendering of a Fusion object is done through
modifying its *Prototype* in certain parts of the rendering tree. This is possible
because Fusion prototypes are *hierarchical*, meaning that ``prototype(...)``
can be part of any Fusion path in an assignment; even multiple times::

	prototype(Foo).bar = 'baz'
	prototype(Foo).some.thing = 'baz2'

	some.path.prototype(Foo).some = 'baz2'

	prototype(Foo).prototype(Bar).some = 'baz2'
	prototype(Foo).left.prototype(Bar).some = 'baz2'

* ``prototype(Foo).bar`` is a simple, top-level prototype property assignment. It means:
  *For all objects of type Foo, set property bar*. The second example is another variant
  of this pattern, just with more nesting levels inside the property assignment.

* ``some.path.prototype(Foo).some`` is a prototype property assignment *inside some.path*.
  It means: *For all objects of type Foo which occur inside the Fusion path some.path,
  the property some is set.*

* ``prototype(Foo).prototype(Bar).some`` is a prototype property assignment *inside another
  prototype*. It means: *For all objects of type Bar which occur somewhere inside an
  object of type Foo, the property some is set.*

* This can both be combined, as in the last example inside ``prototype(Foo).left.prototype(Bar).some``.

.. admonition:: Internals of hierarchical prototypes

	A Fusion object is side-effect free, which means that it can be rendered deterministically
	knowing only its *Fusion path* and the *context*. In order to make this work with hierarchical
	prototypes, we need to encode the types of all Fusion objects above the current one into the
	current path. This is done using angular brackets::

		a1/a2<Foo>/a3/a4<Bar>

	When this path is rendered, ``a1/a2`` is rendered as a Fusion object of type ``Foo`` -- which is needed
	to apply the prototype inheritance rules correctly.

	Those paths are rarely visible on the "outside" of the rendering process, but might at times
	appear in exception messages if rendering fails. For those cases it is helpful to know their
	semantics.

	Bottom line: It is not important to know exactly how the a rendering Fusion object's *Fusion path*
	is constructed. Just pass it on, without modification to render a single element out of band.

Namespaces of Fusion objects
================================

The benefits of namespacing apply just as well to Fusion objects as they apply to other languages.
Namespacing helps to organize the code and avoid name clashes.

In Fusion the namespace of a prototype is given when the prototype is declared. The
following declares a ``YouTube`` prototype in the ``Acme.Demo`` namespace::

	prototype(Acme.Demo:YouTube) {
		width = '100px'
		height = '500px'
	}

The namespace is, by convention, the package key of the package in which the Fusion
resides.

Fully qualified identifiers can be used everywhere an identifier is used::

	prototype(Neos.Neos:ContentCollection) < prototype(Neos.Neos:Collection)

In Fusion a ``default`` namespace of ``Neos.Fusion`` is set. So whenever ``Value`` is used in
Fusion, it is a shortcut for ``Neos.Fusion:Value``.

Custom namespace aliases can be defined using the following syntax::

	namespace: Foo = Acme.Demo

	# the following two lines are equivalent now
	video = Acme.Demo:YouTube
	video = Foo:YouTube

.. warning:: These declarations are scoped to the file they are in and have to be declared in every fusion file where they shall be used.

Setting Properties On a Fusion Object
=========================================

Although the Fusion object can read its context directly, it is good practice to
instead use *properties* for configuration::

	# imagine there is a property "foo=bar" inside the Fusion context at this point
	myObject = MyObject

	# explicitly take the "foo" variable's value from the context and pass it into the "foo"
	# property of myObject. This way, the flow of data is more visible.
	myObject.foo = ${foo}

While ``myObject`` could rely on the assumption that there is a ``foo`` variable inside the Fusion
context, it has no way (besides written documentation) to communicate this to the outside world.

Therefore, a Fusion object's implementation should *only use properties* of itself to determine
its output, and be independent of what is stored in the context.

However, in the prototype of a Fusion object it is perfectly legal to store the mapping
between the context variables and Fusion properties, such as in the following example::

	# this way, an explicit default mapping between a context variable and a property of the
	# Fusion object is created.
	prototype(MyObject).foo = ${foo}

To sum it up: When implementing a Fusion object, it should not access its context variables
directly, but instead use a property. In the Fusion object's prototype, a default mapping
between a context variable and the prototype can be set up.

Default Context Variables
=========================

Neos exposes some default variables to the Fusion context that can be used to control page rendering
in a more granular way.

* ``node`` can be used to get access to the current node in the node tree and read its properties.
  It is of type ``NodeInterface`` and can be used to work with node data, such as::

    # Make the node available in the template
    node = ${node}

    # Expose the "backgroundImage" property to the rendering using FlowQuery
    backgroundImage = ${q(node).property('backgroundImage')}

  To see what data is available on the node, you can expose it to the template as above and wrap it in a debug view helper::

    {node -> f:debug()}

* ``documentNode`` contains the closest parent document node - broadly speaking, it is the page the current node is on.
  Just like ``node``, it is a ``NodeInterface`` and can be provided to the rendering in the same way::

    # Expose the document node to the template
    documentNode = ${documentNode}

    # Display the document node path
    nodePath = ${documentNode.path}

  ``documentNode`` is in the end just a shorthand to get the current document node faster. It could be replaced with::

    # Expose the document node to the template using FlowQuery and a Fizzle operator
    documentNode = ${q(node).closest('[instanceof Neos.Neos:Document]').get(0)}

* ``request`` is an instance of ``Neos\Flow\Mvc\ActionRequest`` and allows you to access the current request from within Fusion.
  Use it to provide request variables to the template::

    # This would provide the value sent by an input field with name="username".
    userName = ${request.arguments.username}

    # request.format contains the format string of the request, such as "html" or "json"
    requestFormat = ${request.format}

  Another use case is to trigger an action, e.g. a search, via a custom Eel helper::

    searchResults = ${Search.query(site).fulltext(request.arguments.searchword).execute()}

  A word of caution: You should never trigger write operations from Fusion, since it can be called multiple times (or not at all, because of caching)
  during a single page render. If you want a request to trigger a persistent change on your site, it's better to use a Plugin.


Manipulating the Fusion Context
-----------------------------------

The Fusion context can be manipulated directly through the use of the ``@context``
meta-property::

	myObject = MyObject
	myObject.@context.bar = ${foo * 2}

In the above example, there is now an additional context variable ``bar`` with twice the value
of ``foo``.

This functionality is especially helpful if there are strong conventions regarding the Fusion
context variables. This is often the case in standalone Fusion applications, but for Neos, this
functionality is hardly ever used.

.. warning:: In order to prevent unwanted side effects, it is not possible to access context variables from within ``@context`` on the same level. This means that the following will never return the string ``Hello World!``

	@context.contextOne = 'World!'
	@context.contextTwo = ${'Hello ' + contextOne}
	output = ${contextTwo}

Processors
==========

Processors allow the manipulation of values in Fusion properties. A processor is applied to
a property using the ``@process`` meta-property::

	myObject = MyObject {
		property = 'some value'
		property.@process.1 = ${'before ' + value + ' after'}
	}
	# results in 'before some value after'

Multiple processors can be used, their execution order is defined by the numeric position given
in the Fusion after ``@process``. In the example above a ``@process.2`` would run on the results of ``@process.1``.

Additionally, an extended syntax can be used as well::

	myObject = MyObject {
		property = 'some value'
		property.@process.someWrap {
			expression = ${'before ' + value + ' after'}
			@position = 'start'
		}
	}

This allows to use string keys for the processor name, and support ``@position`` arguments as explained for Arrays.

Processors are Eel Expressions or Fusion objects operating on the ``value`` property of the context. Additionally,
they can access the current Fusion object they are operating on as ``this``.

Conditions
==========

Conditions can be added to all values to prevent evaluation of the value. A condition is applied to
a property using the ``@if`` meta-property::

	myObject = Menu {
		@if.1 = ${q(node).property('showMenu') == true}
	}
	# results in the menu object only being evaluated if the node's showMenu property is not ``false``
	# the php rules for mapping values to boolean are used internally so following values are
	# considered beeing false: ``null, false, '', 0, []``

Multiple conditions can be used, and if one of them doesn't return ``true`` the condition stops evaluation.

Debugging
=========

To show the result of Fusion Expressions directly you can use the Neos.Fusion:Debug Fusion-Object::

	debugObject = Neos.Fusion:Debug {
		# optional: set title for the debug output
		# title = 'Debug'

		# optional: show result as plaintext
		# plaintext = TRUE

		# If only the "value"-key is given it is debugged directly,
		# otherwise all keys except "title" and "plaintext" are debugged.
		value = "hello neos world"

		# Additional values for debugging
		documentTitle = ${q(documentNode).property('title')}
		documentPath = ${documentNode.path}
	}
	# the value of this object is the formatted debug output of all keys given to the object


Domain-specific languages in Fusion
===================================

Fusion allows the implementation of domain-specific sublanguages. Those DSLs can take a piece of code, that
is optimized to express a specific class of problems, and return the equivalent fusion-code that is cached and executed
by the Fusion-runtime afterwards.

Fusion-DSLs use the syntax of tagged template literals from ES6 and can be used in all value assignments::

	value = dslIdentifier`... the code that is passed to the dsl ...`

If such a syntax-block is detected fusion will:

* Lookup the key ``dslIdentifier`` in the Setting ``Neos.Fusion.dsl`` to find the matching dsl-implementation.
* Instantiate the dsl-implementation class that was found registered.
* Check that the dsl-implementation satisfies the interface ``\Neos\Fusion\Core\DslInterface``
* Pass the code between the backticks to the dsl-implementation.
* Finally parse the returned Fusion-code

Fusion DSLs cannot extend the fusion-language and -runtime itself, they are meant to enable a more efficient syntax
for specific problems.

.. Important Fusion objects and patterns
.. =========================================
.. - page, template, content collection, menu, value (TODO ChristianM)

.. Planned Extension Points using Case and Collection
.. --------------------------------------------------
.. TBD

.. Fusion Internals
.. ====================
..
.. - @class, backed by PHP class
.. - DOs and DONT's when implementing custom Fusion objects
.. - implementing custom FlowQuery operations
