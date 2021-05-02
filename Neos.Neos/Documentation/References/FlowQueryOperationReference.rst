.. _`FlowQuery Operation Reference`:

FlowQuery Operation Reference
=============================

This reference was automatically generated from code on 2021-05-02


.. _`FlowQuery Operation Reference: add`:

add
---

Adds the given items to the current context.
The operation accepts one argument that may be an Array, a FlowQuery
or an Object.

:Implementation: Neos\\Eel\\FlowQuery\\Operations\\AddOperation
:Priority: 1
:Final: No
:Returns: void





.. _`FlowQuery Operation Reference: cacheLifetime`:

cacheLifetime
-------------

"cacheLifetime" operation working on ContentRepository nodes. Will get the minimum of all allowed cache lifetimes for the
nodes in the current FlowQuery context. This means it will evaluate to the nearest future value of the
hiddenBeforeDateTime or hiddenAfterDateTime properties of all nodes in the context. If none are set or all values
are in the past it will evaluate to NULL.

To include already hidden nodes (with a hiddenBeforeDateTime value in the future) in the result, also invisible nodes
have to be included in the context. This can be achieved using the "context" operation before fetching child nodes.

Example:

	q(node).context({'invisibleContentShown': true}).children().cacheLifetime()

:Implementation: Neos\\ContentRepository\\Eel\\FlowQueryOperations\\CacheLifetimeOperation
:Priority: 1
:Final: Yes
:Returns: integer The cache lifetime in seconds or NULL if either no content collection was given or no child node had a "hiddenBeforeDateTime" or "hiddenAfterDateTime" property set





.. _`FlowQuery Operation Reference: children`:

children
--------

"children" operation working on generic objects. It iterates over all
context elements and returns the values of the properties given in the
filter expression that has to be specified as argument or in a following
filter operation.

:Implementation: Neos\\Eel\\FlowQuery\\Operations\\Object\\ChildrenOperation
:Priority: 1
:Final: No
:Returns: void





.. _`FlowQuery Operation Reference: children`:

children
--------

"children" operation working on ContentRepository nodes. It iterates over all
context elements and returns all child nodes or only those matching
the filter expression specified as optional argument.

:Implementation: Neos\\ContentRepository\\Eel\\FlowQueryOperations\\ChildrenOperation
:Priority: 100
:Final: No
:Returns: void





.. _`FlowQuery Operation Reference: closest`:

closest
-------

"closest" operation working on ContentRepository nodes. For each node in the context,
get the first node that matches the selector by testing the node itself and
traversing up through its ancestors.

:Implementation: Neos\\ContentRepository\\Eel\\FlowQueryOperations\\ClosestOperation
:Priority: 100
:Final: No
:Returns: void





.. _`FlowQuery Operation Reference: context`:

context
-------

"context" operation working on ContentRepository nodes. Modifies the ContentRepository Context of each
node in the current FlowQuery context by the given properties and returns the same
nodes by identifier if they can be accessed in the new Context (otherwise they
will be skipped).

Example:

	q(node).context({'invisibleContentShown': true}).children()

:Implementation: Neos\\ContentRepository\\Eel\\FlowQueryOperations\\ContextOperation
:Priority: 1
:Final: No
:Returns: void





.. _`FlowQuery Operation Reference: count`:

count
-----

Count the number of elements in the context.

If arguments are given, these are used to filter the elements before counting.

:Implementation: Neos\\Eel\\FlowQuery\\Operations\\CountOperation
:Priority: 1
:Final: Yes
:Returns: void|integer with the number of elements





.. _`FlowQuery Operation Reference: filter`:

filter
------

Filter operation, limiting the set of objects. The filter expression is
expected as string argument and used to reduce the context to matching
elements by checking each value against the filter.

A filter expression is written in Fizzle, a grammar inspired by CSS selectors.
It has the form `"[" [<value>] <operator> <operand> "]"` and supports the
following operators:

=
  Strict equality of value and operand
!=
  Strict inequality of value and operand
<
  Value is less than operand
<=
  Value is less than or equal to operand
>
  Value is greater than operand
>=
  Value is greater than or equal to operand
$=
  Value ends with operand (string-based) or value's last element is equal to operand (array-based)
^=
  Value starts with operand (string-based) or value's first element is equal to operand (array-based)
*=
  Value contains operand (string-based) or value contains an element that is equal to operand (array based)
instanceof
  Checks if the value is an instance of the operand
!instanceof
  Checks if the value is not an instance of the operand


For the latter the behavior is as follows: if the operand is one of the strings
object, array, int(eger), float, double, bool(ean) or string the value is checked
for being of the specified type. For any other strings the value is used as
classname with the PHP instanceof operation to check if the value matches.

:Implementation: Neos\\Eel\\FlowQuery\\Operations\\Object\\FilterOperation
:Priority: 1
:Final: No
:Returns: void





.. _`FlowQuery Operation Reference: filter`:

filter
------

This filter implementation contains specific behavior for use on ContentRepository
nodes. It will not evaluate any elements that are not instances of the
`NodeInterface`.

The implementation changes the behavior of the `instanceof` operator to
work on node types instead of PHP object types, so that::

	[instanceof Acme.Com:Page]

will in fact use `isOfType()` on the `NodeType` of context elements to
filter. This filter allow also to filter the current context by a given
node. Anything else remains unchanged.

:Implementation: Neos\\ContentRepository\\Eel\\FlowQueryOperations\\FilterOperation
:Priority: 100
:Final: No
:Returns: void





.. _`FlowQuery Operation Reference: find`:

find
----

"find" operation working on ContentRepository nodes. This operation allows for retrieval
of nodes specified by a path, identifier or node type (recursive).

Example (node name):

	q(node).find('main')

Example (relative path):

	q(node).find('main/text1')

Example (absolute path):

	q(node).find('/sites/my-site/home')

Example (identifier):

	q(node).find('#30e893c1-caef-0ca5-b53d-e5699bb8e506')

Example (node type):

	q(node).find('[instanceof Acme.Com:Text]')

Example (multiple node types):

	q(node).find('[instanceof Acme.Com:Text],[instanceof Acme.Com:Image]')

Example (node type with filter):

	q(node).find('[instanceof Acme.Com:Text][text*="Neos"]')

This operation operates rather on the given Context object than on the given node
and thus may work with the legacy node interface until subgraphs are available
{@inheritdoc}

:Implementation: Neos\\ContentRepository\\Eel\\FlowQueryOperations\\FindOperation
:Priority: 100
:Final: No
:Returns: void





.. _`FlowQuery Operation Reference: first`:

first
-----

Get the first element inside the context.

:Implementation: Neos\\Eel\\FlowQuery\\Operations\\FirstOperation
:Priority: 1
:Final: No
:Returns: void





.. _`FlowQuery Operation Reference: get`:

get
---

Get a (non-wrapped) element from the context.

If FlowQuery is used, the result is always another FlowQuery. In case you
need to pass a FlowQuery result (and lazy evaluation does not work out) you
can use get() to unwrap the result from the "FlowQuery envelope".

If no arguments are given, the full context is returned. Otherwise the
value contained in the context at the index given as argument is
returned. If no such index exists, NULL is returned.

:Implementation: Neos\\Eel\\FlowQuery\\Operations\\GetOperation
:Priority: 1
:Final: Yes
:Returns: mixed





.. _`FlowQuery Operation Reference: has`:

has
---

"has" operation working on NodeInterface. Reduce the set of matched elements
to those that have a child node that matches the selector or given subject.

Accepts a selector, an array, an object, a traversable object & a FlowQuery
object as argument.

:Implementation: Neos\\ContentRepository\\Eel\\FlowQueryOperations\\HasOperation
:Priority: 100
:Final: No
:Returns: void





.. _`FlowQuery Operation Reference: is`:

is
--

Check whether the at least one of the context elements match the given filter.

Without arguments is evaluates to true if the context is not empty. If arguments
are given, they are used to filter the context before evaluation.

:Implementation: Neos\\Eel\\FlowQuery\\Operations\\IsOperation
:Priority: 1
:Final: Yes
:Returns: void|boolean





.. _`FlowQuery Operation Reference: last`:

last
----

Get the last element inside the context.

:Implementation: Neos\\Eel\\FlowQuery\\Operations\\LastOperation
:Priority: 1
:Final: No
:Returns: void





.. _`FlowQuery Operation Reference: neosUiDefaultNodes`:

neosUiDefaultNodes
------------------

Fetches all nodes needed for the given state of the UI

:Implementation: Neos\\Neos\\Ui\\FlowQueryOperations\\NeosUiDefaultNodesOperation
:Priority: 100
:Final: No
:Returns: void





.. _`FlowQuery Operation Reference: neosUiFilteredChildren`:

neosUiFilteredChildren
----------------------

"children" operation working on ContentRepository nodes. It iterates over all
context elements and returns all child nodes or only those matching
the filter expression specified as optional argument.

:Implementation: Neos\\Neos\\Ui\\FlowQueryOperations\\NeosUiFilteredChildrenOperation
:Priority: 100
:Final: No
:Returns: void





.. _`FlowQuery Operation Reference: next`:

next
----

"next" operation working on ContentRepository nodes. It iterates over all
context elements and returns the immediately following sibling.
If an optional filter expression is provided, it only returns the node
if it matches the given expression.

:Implementation: Neos\\ContentRepository\\Eel\\FlowQueryOperations\\NextOperation
:Priority: 100
:Final: No
:Returns: void





.. _`FlowQuery Operation Reference: nextAll`:

nextAll
-------

"nextAll" operation working on ContentRepository nodes. It iterates over all
context elements and returns each following sibling or only those matching
the filter expression specified as optional argument.

:Implementation: Neos\\ContentRepository\\Eel\\FlowQueryOperations\\NextAllOperation
:Priority: 0
:Final: No
:Returns: void





.. _`FlowQuery Operation Reference: nextUntil`:

nextUntil
---------

"nextUntil" operation working on ContentRepository nodes. It iterates over all context elements
and returns each following sibling until the matching sibling is found.
If an optional filter expression is provided as a second argument,
it only returns the nodes matching the given expression.

:Implementation: Neos\\ContentRepository\\Eel\\FlowQueryOperations\\NextUntilOperation
:Priority: 0
:Final: No
:Returns: void





.. _`FlowQuery Operation Reference: parent`:

parent
------

"parent" operation working on ContentRepository nodes. It iterates over all
context elements and returns each direct parent nodes or only those matching
the filter expression specified as optional argument.

:Implementation: Neos\\ContentRepository\\Eel\\FlowQueryOperations\\ParentOperation
:Priority: 100
:Final: No
:Returns: void





.. _`FlowQuery Operation Reference: parents`:

parents
-------

"parents" operation working on ContentRepository nodes. It iterates over all
context elements and returns the parent nodes or only those matching
the filter expression specified as optional argument.

:Implementation: Neos\\ContentRepository\\Eel\\FlowQueryOperations\\ParentsOperation
:Priority: 0
:Final: No
:Returns: void





.. _`FlowQuery Operation Reference: parents`:

parents
-------

"parents" operation working on ContentRepository nodes. It iterates over all
context elements and returns the parent nodes or only those matching
the filter expression specified as optional argument.

:Implementation: Neos\\Neos\\Eel\\FlowQueryOperations\\ParentsOperation
:Priority: 100
:Final: No
:Returns: void





.. _`FlowQuery Operation Reference: parentsUntil`:

parentsUntil
------------

"parentsUntil" operation working on ContentRepository nodes. It iterates over all
context elements and returns the parent nodes until the matching parent is found.
If an optional filter expression is provided as a second argument,
it only returns the nodes matching the given expression.

:Implementation: Neos\\Neos\\Eel\\FlowQueryOperations\\ParentsUntilOperation
:Priority: 100
:Final: No
:Returns: void





.. _`FlowQuery Operation Reference: parentsUntil`:

parentsUntil
------------

"parentsUntil" operation working on ContentRepository nodes. It iterates over all
context elements and returns the parent nodes until the matching parent is found.
If an optional filter expression is provided as a second argument,
it only returns the nodes matching the given expression.

:Implementation: Neos\\ContentRepository\\Eel\\FlowQueryOperations\\ParentsUntilOperation
:Priority: 0
:Final: No
:Returns: void





.. _`FlowQuery Operation Reference: prev`:

prev
----

"prev" operation working on ContentRepository nodes. It iterates over all
context elements and returns the immediately preceding sibling.
If an optional filter expression is provided, it only returns the node
if it matches the given expression.

:Implementation: Neos\\ContentRepository\\Eel\\FlowQueryOperations\\PrevOperation
:Priority: 100
:Final: No
:Returns: void





.. _`FlowQuery Operation Reference: prevAll`:

prevAll
-------

"prevAll" operation working on ContentRepository nodes. It iterates over all
context elements and returns each preceding sibling or only those matching
the filter expression specified as optional argument

:Implementation: Neos\\ContentRepository\\Eel\\FlowQueryOperations\\PrevAllOperation
:Priority: 0
:Final: No
:Returns: void





.. _`FlowQuery Operation Reference: prevUntil`:

prevUntil
---------

"prevUntil" operation working on ContentRepository nodes. It iterates over all context elements
and returns each preceding sibling until the matching sibling is found.
If an optional filter expression is provided as a second argument,
it only returns the nodes matching the given expression.

:Implementation: Neos\\ContentRepository\\Eel\\FlowQueryOperations\\PrevUntilOperation
:Priority: 0
:Final: No
:Returns: void





.. _`FlowQuery Operation Reference: property`:

property
--------

Used to access properties of a ContentRepository Node. If the property mame is
prefixed with _, internal node properties like start time, end time,
hidden are accessed.

:Implementation: Neos\\ContentRepository\\Eel\\FlowQueryOperations\\PropertyOperation
:Priority: 100
:Final: Yes
:Returns: mixed





.. _`FlowQuery Operation Reference: property`:

property
--------

Access properties of an object using ObjectAccess.

Expects the name of a property as argument. If the context is empty, NULL
is returned. Otherwise the value of the property on the first context
element is returned.

:Implementation: Neos\\Eel\\FlowQuery\\Operations\\Object\\PropertyOperation
:Priority: 1
:Final: Yes
:Returns: mixed





.. _`FlowQuery Operation Reference: remove`:

remove
------

Removes the given items from the current context.
The operation accepts one argument that may be an Array, a FlowQuery
or an Object.

:Implementation: Neos\\Eel\\FlowQuery\\Operations\\RemoveOperation
:Priority: 1
:Final: No
:Returns: void





.. _`FlowQuery Operation Reference: search`:

search
------



:Implementation: Neos\\Neos\\Ui\\FlowQueryOperations\\SearchOperation
:Priority: 100
:Final: No
:Returns: void





.. _`FlowQuery Operation Reference: siblings`:

siblings
--------

"siblings" operation working on ContentRepository nodes. It iterates over all
context elements and returns all sibling nodes or only those matching
the filter expression specified as optional argument.

:Implementation: Neos\\ContentRepository\\Eel\\FlowQueryOperations\\SiblingsOperation
:Priority: 100
:Final: No
:Returns: void





.. _`FlowQuery Operation Reference: slice`:

slice
-----

Slice the current context

If no arguments are given, the full context is returned. Otherwise the
value contained in the context are sliced with offset and length.

:Implementation: Neos\\Eel\\FlowQuery\\Operations\\SliceOperation
:Priority: 1
:Final: No
:Returns: void





.. _`FlowQuery Operation Reference: sort`:

sort
----

"sort" operation working on ContentRepository nodes.
Sorts nodes by specified node properties.

{@inheritdoc}

First argument is the node property to sort by. Works with internal arguments (_xyz) as well.
Second argument is the sort direction (ASC or DESC).

:Implementation: Neos\\Neos\\Eel\\FlowQueryOperations\\SortOperation
:Priority: 1
:Final: No
:Returns: void




