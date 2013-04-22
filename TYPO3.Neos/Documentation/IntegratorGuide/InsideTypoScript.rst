=================
Inside TypoScript
=================

In this chapter, TypoScript will be explained in a step-by-step fashion, focussing on the different
internal parts, the syntax of these and the semantics.

TypoScript is fundamentally a *hierarchical, prototype based processing language*:

* It is *hierarchical* because the content it should render is also hierarchically structured.

* It is *prototype based* because it allows to define properties for *all instances* of a certain
  TypoScript object type. It is also possible to define properties not for all instances, but only
  for *instances inside a certain hierarchy*. Thus, the prototype definitions are hierarchically-scoped
  as well.

* It is a *processing language* because it processes the values in the *context* into a *single output
  value*.

In the first part of this chapter, we will explain the syntactic and semantic features of the TypoScript,
Eel and FlowQuery languages. Then, we will focus on the design decisions and goals of TypoScript, such that
the reader can get a better understanding of the main objectives we had in mind designing the language.

Goals of TypoScript
===================

- both for planned and unplanned extensibility
- also used for standalone, extensible applications (though that is not relevant
  in this guide)
- out-of-band rendering easily possible
- multiple renderings of the same content
-
- …
- inspiration sources (see issue) http://forge.typo3.org/issues/31638
-- css, jQuery (flowQuery, eel, ...), xpath, JS

TypoScript Objects
==================

TypoScript is a language to describe *TypoScript objects*. A TypoScript object has some *properties*
which are used to configure the object. Additionally, a TypoScript object has access to a *context*,
which is a list of variables. The goal of a TypoScript object is to take the variables from the
context, and transform them to the desired *output*, using its properties for configuration as needed.

Thus, TypoScript objects take some *input* which is given through the context and the properties, and
produce a single *output value*. Internally, they can modify the context, and trigger rendering of
nested TypoScript objects: This way, a big task (like rendering a whole web page) can be split into
many smaller tasks (render a single image, render a text, ...): The results of the small tasks are then
again put together, forming the final end result.

Because it is a fundamental principle that TypoScript objects call nested TypoScript objects, the rendering
process forms a *tree* of TypoScript objects, which can also be inspected using a TypoScript debugger.

TypoScript objects are implemented by a PHP class, which is instanciated at runtime. A single PHP class
is the basis for many TypoScript objects. We will highlight the exact connection between TypoScript
objects and their PHP implementations at a later chapter.

A TypoScript object can be instanciated by assigning it to a TypoScript path, such as::

	foo = Page
	# or:
	my.object = Text
	# or:
	my.image = TYPO3.Neos.ContentTypes:Image

You see that the name of the to-be-instanciated TypoScript prototype is listed without quotes.

By convention, TypoScript paths (such as `my.object`) are written in `lowerCamelCase`, while
TypoScript prototypes (such as `TYPO3.Neos.ContentTypes:Image`) are written in `UpperCamelCase`.

Now, we are able to set *properties* on the newly created TypoScript objects::

	foo.myProperty1 = 'Some Property which Page can access'
	my.object.myProperty1 = "Some other property"
	my.image.width = ${q(node).property('foo')}

You see that properties have to be quoted (with either single or double quotes), or can be an
*Eel expression* (which will be explained in a separate section lateron).

In order to reduce typing overhead, curly braces can be used to "abbreviate" long TypoScript paths,
as the following example demonstrates::

	my {
	  image = Image
	  image.width = 200

	  object {
	    myProperty1 = 'some property'
	  }
	}

Furthermore, you can also instanciate a TypoScript object and set properties on it in a single
pass, as shown in the third example below::

	# all three examples mean exactly the same.

	someImage = Image
	someImage.foo = 'bar'

	# Instanciate object, set property one after each other
	someImage = Image
	someImage {
	  foo = 'bar'
	}

	# Instanciate an object and setting properties directly
	someImage = Image {
	  foo = 'bar'
	}

In the next section, we will learn what is exactly done on object creation, i.e. when you type
`someImage = Image`.

.. admonition:: TypoScript Objects are Side-Effect Free

	When TypoScript objects are rendered, they are allowed to modify the TypoScript context
	(i.e. they can add, or override variables); and can invoke other TypoScript objects.
	After that, however, the parent TypoScript object must make sure to clean up the context,
	such that it contains exactly the state before its rendering.

	The API helps to enforce that, as the TypoScript context is a *stack*: The only thing the
	developer of a TypoScript object needs to make sure is that if he adds some variable to
	the stack, effectively creating a new stack frame, he needs to remove exactly this stack
	frame after rendering again.

	This means that a TypoScript object can only manipulate TypoScript objects *below it*,
	but not following or preceeding it.

	In order to enforce this, TypoScript objects are furthermore only allowed to communicate
	through the TypoScript Context; and they are never allowed to be invoked directly: Instead,
	all invocations need to be done through the *TypoScript Runtime*.

	All these constraints make sure that a TypoScript object is *side-effect free*, leading
	to an important benefit: If somebody knows the exact path towards a TypoScript object together
	with its context, it can be rendered in a stand-alone manner, exactly as if it was embedded
	in a bigger element. This enables f.e. to render parts of pages with different cache life-
	times, or the effective implementation of AJAX or ESI handlers reloading only parts of a
	website.


TypoScript Prototypes
=====================

When a TypoScript object is instanciated, the *TypoScript Prototype* for this object is *copied*
and is taken as a basis. The prototype is defined using the following syntax::

	# we prefer this syntax:
	prototype(MyImage) {
		width = '500px'
		height = '600px'
	}

	# could also be written as:
	prototype(MyImage).width = '500px'
	prototype(MyImage).height = '500px'

Now, when the above prototype is instanciated, the instanciated object will have all the properties
of the prototype copied. This is illustrated through the following example::

	someImage = MyImage
	# now, someImage will have a width of 500px and a height of 600px

	someImage.width = '100px'
	# now, we have overridden the height of "someImage" to be 100px.

.. admonition:: Prototype- vs class-based languages

	There are generally two major "flavours" of object-oriented languages. Most languages
	(such as PHP, Ruby, Perl, Java, C++) are *class-based*, meaning that they explicitely
	distinguish between the place where behavior for a given object is defined (the "class")
	and the runtime representation which contains the data (the "instance").

	Other languages such as JavaScript are prototype-based, meaning that there is no distinction
	between classes and instances: At object creation time, all properties and methods of
	the object's *prototype* (which roughly corresponds to a "class") are copied (or otherwise
	referenced) to the *instance*.

	TypoScript is a *prototype-based language* because it *copies* the TypoScript Prototype
	to the instance when an object is evaluated.


Prototypes in TypoScript are *mutable*, which means that they can easily be modified::

	prototype(MyYouTube) {
		width = '100px'
		height = '500px'
	}

	# you can easily change the width/height, or define new properties:
	prototype(MyYouTube).width = '400px'
	prototype(MyYouTube).showFullScreen = ${true}

So far, we have seen how to define and instanciate prototypes from scratch. However, often
you will want to use an *existing TypoScript prototype* as basis for a new one. This can be
currently done by *subclassing* a TypoScript prototype using the `<` operator::

	prototype(MyImage) < prototype(Template)

	# now, the MyImage prototype contains all properties of the Template
	# prototype, and can be further customized.

We implement *prototype inheritance*, meaning that the "subclass" (`MyImage` in the example
above) and the "parent class (`Template`) are still attached to each other: If a property
is added to the parent class, this also applies to the subclass, as the following example
demonstrates::

	prototype(Template).fruit = 'apple'
	prototype(Template).meal = 'dinner'

	prototype(MyImage) < prototype(Template)
	# now, MyImage also has the properties "fruit = apple" and "meal = dinner"

	prototype(Template).fruit = 'Banana'
	# because MyImage *extends* Template, MyImage.fruit equals 'Banana' as well.

	prototype(MyImage).meal = 'breakfast'
	prototype(Template).meal = 'supper'
	# because MyImage now has an *overridden* property "meal", the change of
	# the parent class' property is not reflected in the MyImage class


.. admonition:: Prototype Inheritance is only allowed at top level

	Currently, prototype inerhitance can only be defined *globally*, i.e. with
	a statement of the following form::

		prototype(Foo) < prototype(Bar)

	It is not allowed to nest prototypes when defining prototype inheritance,
	so the following examples are **not valid TypoScript** and will result in
	an exception::

		prototype(Foo) < some.prototype(Bar)
		other.prototype(Foo) < prototype(Bar)
		prototype(Foo).prototype(Bar) < prototype(Baz)

	While it would be theoretically possible to support this, we have chosen
	not to do so in order to reduce complexity and to keep the rendering process
	more understandable. We have not yet seen a TypoScript example where a construct
	such as the above would be needed.

Namespaces of TypoScript objects
--------------------------------

.. TODO Robert: explain namespacing of TypoScript prototypes


Hierarchical TypoScript Prototypes
----------------------------------

One way to flexibly adjust the rendering of a TypoScript object is done through
modifying its *Prototype* in certain parts of the rendering tree. This is possible
because TypoScript prototypes are *hierarchical*, meaning that `prototype(...)`
can be part of any TypoScript path in an assignment; even multiple times::

	# the following are all valid TypoScript assignments, all with different
	# semantics
	prototype(Foo).bar = 'baz'
	prototype(Foo).some.thing = 'baz2'
	some.path.prototype(Foo).some = 'baz2'
	prototype(Foo).prototype(Bar).some = 'baz2'
	prototype(Foo).left.prototype(Bar).some = 'baz2'

Let's dissect these examples one by one:

* `prototype(Foo).bar` is a simple, top-level prototype property assignment. It means:
  *For all objects of type `Foo`, set property `bar`*. The second example is another variant
  of this pattern, just with more nesting levels inside the property assignment.

* `some.path.prototype(Foo).some` is a prototype property assignment *inside `some.path`*.
  It means: *For all objects of type `Foo` which occur inside the TypoScript path `some.path`,
  the property `some` is set.*

* `prototype(Foo).prototype(Bar).some` is a prototype property assignment *inside another
  prototype*. It means: *For all objects of type `Bar` which occur somewhere inside an
  object of type `Foo`, the property `some` is set.*

* This can both be combined, as in the last example inside `prottoype(Foo).left.prototype(Bar).some`.

.. admonition:: Internals of hierarchical prototypes

	We stated before that a TypoScript object is side-effect free, meaning that it can be
	rendered deterministically just knowing its *TypoScript path* and the *context*. In order
	to make this work with hierarchical prototypes, we need to encode the types of all TypoScript
	objects above the current one into the current path. This is done using angular brackets::

		a1/a2<Foo>/a3/a4<Bar>

	when this path is rendered, we know that at `a1/a2`, a TypoScript object of type `Foo` has
	been rendered -- which is needed to apply the prototype inheritance rules correctly.

Bottom line: You do not need to know exactly how the *TypoScript path* towards the currently
rendered TypoScript object is constructed, you just need to pass it on without modification
if you want to render a single element out-of-band.

Setting Properties On a TypoScript Object
=========================================

Now, we have dissected the main building principles of TypoScript objects, and we're turning
towards smaller -- but nevertheless important -- building blocks inside TypoScript. We will now
focus on how exactly properties are set in a TypoScript object.

Besides simple assignments such as `myObject.foo = 'bar'` (which are a bit boring), one can write
*expressions* using the *Eel language* such as `myObject.foo = ${q(node).property('bar')}`.

Although the TypoScript object can read its context directly, it is a better practice to
instead use *properties* for configuration::

	# imagine that there is a property "foo=bar" inside the TypoScript context at this point
	myObject = MyObject

	# we explicitely take the "foo" variable's value from the context and pass it into the "foo"
	# property of myObject. This way, the flow of data is better visible.
	myObject.foo = ${foo}

While myObject could rely on the assumption that there is a "foo" variable inside the TypoScript
context, it has no way (besides written documentation) to communicate this to the outside world.

Thus, we encourage that a TypoScript object's implementation should *only use properties* of itself
to determine its output, and be independent of what is stored in the context.

However, in the prototype of this TypoScript object it is perfectly legal to store the mapping
between the context variables and TypoScript properties, such as in the following example::

	# this way, an explicit default mapping between a context variable and a property of the
	# TypoScript object is created.
	prototype(MyObject).foo = ${foo}


To sum it up: If you implement a TypoScript object, it should not access its context variables
directly, but instead use a property. In the TypoScript object's prototype, a default mapping
between a context variable and the prototype can be made.


Manipulating the TypoScript Context
-----------------------------------

Now that we have seen how the properties of a TypoScript object are evaluated, we're now turning
our focus to changing the TypoScript context.

This is possible through the use of the `@override` meta-property::

	myObject = MyObject
	myObject.@override.foo = ${bar * 2}

In the above example, there is now an additional context variable `foo` with twice the value
of `bar`.

This functionality is especially helpful if there are strong conventions regarding the TypoScript
context variables; which is often the case in standalone TypoScript applications.

For Neos, this functionality is hardly ever used.

.. TODO: is @override final in regard to the naming?

Processors
----------

.. TODO: Processors and eel should be able to work together
.. TODO: processor ordering should adhere to @override notation


Important TypoScript objects and patterns
=========================================

- page, template, section, menu, value (TODO ChristianM)


Planned Extension Points using Case and Collection
--------------------------------------------------

TBD

TypoScript Internals
====================

- @class, backed by PHP class
- DOs and DONT's when implementing custom TypoScript objects
- implementing custom FlowQuery operations

Standalone Usage of TypoScript
-> eigene Dokumentation
Standalone Usage of Eel & FlowQuery
-> eigene Dokumentation


Eel -- Embedded Expression Language
===================================

The Embedded Expression Language *Eel* is a building block for creating Domain Specific Languages.
It provides a rich *syntax* for arbitrary expressions, such that the author of the DSL can focus
on its Semantics.

In this section, we will focus on the use of Eel inside TypoScript.

Syntax
------

Every Eel expression in TypoScript is surrounded by `${...}`, which is the delimiter for Eel
expressions. Basically, the Eel syntax and semantics is like a condensed version of JavaScript::

* Most things you can write as a single JavaScript expression (that is, without a `;`) can also
  be written as Eel expression.

* Eel does not throw an error if `null` values are dereferenced, i.e. inside `${foo.bar}`
  with `foo` being `null`. Instead, `null` is returned. This also works for calling undefined
  functions.

* We do not support control structures or variable declarations.

* We support the common JavaScript arithmetic and comparison operators, such as `+-*/%` for
  arithmetic and `== != > >= < <=` for comparison operators. Operator precedence is as expected,
  with multiplication binding higher than addition. This can be adjusted by using brackets. Boolean
  operators `&&` and `||` are supported.

* We support the ternary operator to allow for conditions `<condition> ? <ifTrue> : <ifFalse>`.

* When object access is done (such as `foo.bar.baz`) on PHP objects, getters are called automatically.

* Object access with the offset notation is supported: `foo['bar']`

This means the following expressions are all valid Eel expressions::

	${foo}
	${foo.bar}
	${f()}
	${f().g()}
	${f() ? g : h + i * 5}


Semantics inside TypoScript
---------------------------

Eel does not define any functions or variables by itself. Instead, it exposes the *Eel context
array*, such that functions and objects which should be accessible can be defined there.

Because of that, Eel is perfectly usable as a "domain-specific language construction kit", which
provides the syntax, but not the semantics of a given language.

*For Eel inside TypoScript, we have defined a semantics which is outlined below:*

* All variables of the TypoScript context are made available inside the Eel context.

* Additionally, the function `q()` is available, which wraps its argument into a FlowQuery
  object. FlowQuery is explained below.

* Last, the special variable `this` always points to the current TypoScript object implementation.

Here follows an example usage in the context of TypoScript::

	${node}
	${myContextVariable}
	${node.getProperty('foo')} # discouraged. You should use FlowQuery instead.
	${q(node).property('foo')}

.. TODO: Eel Standard Library

FlowQuery and Fizzle
====================

- flowquery (syntax, examples on nodes)
- fizzle (TODO: check if syntax is final)
