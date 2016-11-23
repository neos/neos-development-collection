.. _eel-flowquery:

=========================
Eel, FlowQuery and Fizzle
=========================

Eel - Embedded Expression Language
==================================

Besides simple TypoScript assignments such as ``myObject.foo = 'bar'``, it is possible to write
*expressions* using the *Eel* language such as ``myObject.foo = ${q(node).property('bar')}``.

The *Embedded Expression Language* (Eel) is a building block for creating Domain Specific Languages.
It provides a rich *syntax* for arbitrary expressions, such that the author of the DSL can focus
on its Semantics.

In this section, the focus lies on the use of Eel inside TypoScript.

Syntax
------

Every Eel expression in TypoScript is surrounded by ``${...}``, which is the delimiter for Eel
expressions. Basically, the Eel syntax and semantics is like a condensed version of JavaScript:

* Most things you can write as a single JavaScript expression (that is, without a ``;``) can also
  be written as Eel expression.

* Eel does not throw an error if ``null`` values are dereferenced, i.e. inside ``${foo.bar}``
  with ``foo`` being ``null``. Instead, ``null`` is returned. This also works for calling undefined
  functions.

* Eel does not support control structures or variable declarations.

* Eel supports the common JavaScript arithmetic and comparison operators, such as ``+-*/%`` for
  arithmetic and ``== != > >= < <=`` for comparison operators. Operator precedence is as expected,
  with multiplication binding higher than addition. This can be adjusted by using brackets. Boolean
  operators ``&&`` and ``||`` are supported.

* Eel supports the ternary operator to allow for conditions ``<condition> ? <ifTrue> : <ifFalse>``.

* When object access is done (such as ``foo.bar.baz``) on PHP objects, getters are called automatically.

* Object access with the offset notation is supported as well: ``foo['bar']``

This means the following expressions are all valid Eel expressions:

.. code-block:: text

	${foo.bar}         // Traversal
	${foo.bar()}       // Method call
	${foo.bar().baz()} // Chained method call

	${foo.bar("arg1", true, 42)} // Method call with arguments

	${12 + 18.5}         // Calculations are possible
	${foo == bar}      // ... and comparisons

	${foo.bar(12+18.5, foo == bar)} // and of course also use it inside arguments

	${[foo, bar]}           // Array Literal
	${{foo: bar, baz: test}} // Object Literal

Semantics inside TypoScript
---------------------------

Eel does not define any functions or variables by itself. Instead, it exposes the *Eel context
array*, meaning that functions and objects which should be accessible can be defined there.

Because of that, Eel is perfectly usable as a "domain-specific language construction kit", which
provides the syntax, but not the semantics of a given language.

For Eel inside TypoScript, the semantics are as follows:

* All variables of the TypoScript context are made available inside the Eel context.

* The special variable ``this`` always points to the current TypoScript object implementation.

* The function ``q()`` is available, which wraps its argument into a FlowQuery
  object. `FlowQuery`_ is explained below.

By default the following Eel helpers are available in the default context for Eel expressions:

* ``String``, exposing ``TYPO3\Eel\Helper\StringHelper``
* ``Array``, exposing ``TYPO3\Eel\Helper\ArrayHelper``
* ``Date``, exposing ``TYPO3\Eel\Helper\DateHelper``
* ``Configuration``, exposing ``TYPO3\Eel\Helper\ConfigurationHelper``
* ``Math``, exposing ``TYPO3\Eel\Helper\MathHelper``
* ``Json``, exposing ``TYPO3\Eel\Helper\JsonHelper``
* ``Security``, exposing ``TYPO3\Eel\Helper\SecurityHelper``

* ``Translation``, exposing ``TYPO3\Flow\I18n\EelHelper\TranslationHelper``

* ``Neos.Node``, exposing ``TYPO3\Neos\TypoScript\Helper\NodeHelper``
* ``Neos.Link``, exposing ``TYPO3\Neos\TypoScript\Helper\LinkHelper``
* ``Neos.Array``, exposing ``TYPO3\Neos\TypoScript\Helper\ArrayHelper``
* ``Neos.Rendering``, exposing ``TYPO3\Neos\TypoScript\Helper\RenderingHelper``

See: :ref:`Eel Helpers Reference`

This is configured via the setting ``TYPO3.TypoScript.defaultContext``.

Additionally, the defaultContext contains the ``request`` object,
where you have also access to Arguments. e.g.
``${request.httpRequest.arguments.nameOfYourGetArgument}``

FlowQuery
=========

FlowQuery, as the name might suggest, *is like jQuery for Flow*. It's syntax
has been heavily influenced by jQuery.

FlowQuery is a way to process the content (being a TYPO3CR node within Neos) of the Eel
context. FlowQuery operations are implemented in PHP classes. For any FlowQuery operation
to be available, the package containing the operation must be installed. Any package can
add their own FlowQuery operations. A set of basic operations is always available as part
of the TYPO3.Eel package itself.

In TYPO3.Neos, the following FlowQuery operations are defined:

``property``
  Adjusted to access properties of a TYPO3CR node. If property names are prefixed with an
  underscore, internal node properties like start time, end time, and hidden are accessed.

``filter``
  Used to check a value against a given constraint. The filters expressions are
  given in `Fizzle`_, a language inspired by CSS selectors. The Neos-specific
  filter changes ``instanceof`` to work on node types instead of PHP classes.

``children``
  Returns the children of a TYPO3CR node. They are optionally filtered with a
  ``filter`` operation to limit the returned result set.

``parents``
  Returns the parents of a TYPO3CR node. They are optionally filtered with a
  ``filter`` operation to limit the returned result set.

A reference of all FlowQuery operations defined in TYPO3.Eel and TYPO3.Neos can be
found in the :ref:`FlowQuery Operation Reference`.

Operation Resolving
-------------------

When multiple packages define an operation with the same short name, they are
resolved using the priority each implementation defines, higher priorities have
higher precedence when operations are resolved.

The ``OperationResolver`` loops over the implementations sorted by order and asks
them if they can evaluate the current context. The first operation that answers this
check positively is used.

FlowQuery by Example
--------------------

Any context variable can be accessed directly:

.. code-block:: text

	${myContextVariable}

and the current node is available as well:

.. code-block:: text

	${node}

There are various ways to access its properties. Direct access is possible, but should
be avoided. It is better to use FlowQuery instead:

.. code-block:: text

	${q(node).getProperty('foo')} // Possible, but discouraged
	${q(node).property('foo')} // Better: use FlowQuery instead

Through this a node property can be fetched and assigned to a variable:

.. code-block:: text

	text = ${q(node).property('text')}

Fetching all parent nodes of the current node:

.. code-block:: text

	${q(node).parents()}

Here are two equivalent ways to fetch the first node below the ``left`` child node:

.. code-block:: text

	${q(node).children('left').first()}
	${q(node).children().filter('left').first()}

Fetch all parent nodes and add the current node to the selected set:

.. code-block:: text

	${node.parents().add(node)}

The next example combines multiple operations. First it fetches all children of the
current node that have the name ``comments``. Then it fetches all children of those
nodes that have a property ``spam`` with a value of false. The result of that is then
passed to the ``count()`` method and the count of found nodes is assigned to the
variable 'numberOfComments':

.. code-block:: text

	numberOfComments = ${q(node).children('comments').children("[spam = false]").count()}

The following expands a little more on that. It assigns a set of nodes to the ``collection``
property of the comments object. This set of nodes is either fetched from different places,
depending on whether the current node is a ``ContentCollection`` node or not. If it is, the
children of the current node are used directly. If not, the result of ``this.getNodePath()``
is used to fetch a node below the current node and those children are used. In both cases
the nodes are again filtered by a check for their property ``spam`` being false.

.. code-block:: text

	comments.collection = ${q(node).is('[instanceof TYPO3.Neos:ContentCollection]') ?
		q(node).children("[spam = false]") : q(node).children(this.getNodePath()).children("[spam = false]")}

Querying for nodes of two or more different node types

.. code-block:: text

	elements = ${q(node).filter('[instanceof TYPO3.Neos.NodeTypes:Text],[instanceof TYPO3.Neos.NodeTypes:TextWithImage]').get()}


Fizzle
======

Filter operations as already shown are written in *Fizzle*. It has been inspired by
the selector syntax known from CSS.

Property Name Filters
---------------------

The first component of a filter query can be a ``Property Name`` filter. It is given
as a simple string. Checks against property paths are not currently possible::

	foo          //works
	foo.bar      //does not work
	foo.bar.baz  //does not work

In the context of Neos the property name is rarely used, as FlowQuery operates on
TYPO3CR nodes and the ``children`` operation has a clear scope. If generic PHP objects are
used, the property name filter is essential to define which property actually contains
the ``children``.

Attribute Filters
-----------------

The next component are ``Attribute`` filters. They can check for the presence and against
the values of attributes of context elements:

.. code-block:: text

	baz[foo]
	baz[answer = 42]
	baz[foo = "Bar"]
	baz[foo = 'Bar']
	baz[foo != "Bar"]
	baz[foo ^= "Bar"]
	baz[foo $= "Bar"]
	baz[foo *= "Bar"]

As the above examples show, string values can be quoted using double or single quotes.

Available Operators
~~~~~~~~~~~~~~~~~~~

The operators for checking against attribute are as follows:

``=``
  Strict equality of value and operand
``!=``
  Strict inequality of value and operand
``$=``
  Value ends with operand (string-based)
``^=``
  Value starts with operand (string-based)
``*=``
  Value contains operand (string-based)
``instanceof``
  Checks if the value is an instance of the operand

For the latter the behavior is as follows: if the operand is one of the strings
object, array, int(eger), float, double, bool(ean) or string the value is checked
for being of the specified type. For any other strings the value is used as
class name with the PHP instanceof operation to check if the value matches.

Using Multiple Filters
----------------------

It is possible to combine multiple filters:

``[foo][bar][baz]``
  All filters have to match (AND)
``[foo],[bar],[baz]``
  Only one filter has to match (OR)
