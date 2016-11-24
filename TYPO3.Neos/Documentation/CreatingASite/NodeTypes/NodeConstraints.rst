.. _node-constraints:

=====================
Node Type Constraints
=====================

In a typical Neos project, you will create lots of custom node types. However, many node types should only be
used in a specific context and not everywhere.

For instance, inside the "Chapter" node type of the Neos Demo Site (which is a document node), one should only be
able to create nested chapters, and not pages or shortcuts. Using node type constraints, this can be enforced::

  'Neos.Demo:Chapter':
    constraints:
      nodeTypes:
        'Neos.Demo:Chapter': true
        '*': false

In the above example, we disable all node types using ``*: false``, and then enable the ``Chapter`` node type as well
as any node type that super types it. The closest matching constraint of a super type is used to determine the constraint.

You might now wonder why it is still possible to create content inside the chapter (because everything except Chapter
is disabled with the above configuration): The reason is that node type constraints are *only enforced* for nodes
which are *not auto-created*. Because "Chapter" has an auto-created ``main ContentCollection``, it is still possible
to add content inside. In the following example, we see the NodeType definition which is shipped with the demo website::

  'Neos.Demo:Chapter':
    superTypes:
      'Neos.Neos:Document': true
    childNodes:
      'main':
        type: 'Neos.Neos:ContentCollection'

Now, it might additionally be useful to only allow text and images inside the chapter contents. This is possible using
additional constraints for each *auto-created child node*::

  'Neos.Demo:Chapter':
    childNodes:
      'main':
        constraints:
          nodeTypes:
            'Neos.Neos.NodeTypes:Text': true
            '*': false


Examples
========

Disallow nested Two/Three/FourColumn inside a multi column element::

  'Neos.Neos.NodeTypes:Column':
    childNodes:
      column0:
        constraints: &columnConstraints
          nodeTypes:
            'Neos.Neos.NodeTypes:TwoColumn': false
            'Neos.Neos.NodeTypes:ThreeColumn': false
            'Neos.Neos.NodeTypes:FourColumn': false
      column1:
        constraints: *columnConstraints
      column2:
        constraints: *columnConstraints
      column3:
        constraints: *columnConstraints


Constraint Specification
========================

To sum it up, the following rules apply:

- Constraints are only enforced for non-auto-created child nodes.
- For auto-created child nodes, constraints can be specified for *their children* as well.
- NodeTypePattern is usually a *Node Type*, or `*` marks *the fallback* node type.
	- setting the value to `true` is an explicit *allow*
	- setting the value to `false` is an explicit *deny*
	- setting the value to `null` (i.e. using `~` in YAML) is an *abstain*, so that means the fallback of `*` is used.
- Inheritance is taking into account, so if allowing/disallowing "Foo", the subtypes of "Foo" are automatically
  allowed/disallowed. To constraint subtypes you must be more specific for those types.
- The default is to *always deny* (in case "*" is not specified).

.. note:: Node type constraints are cached in the browser's session storage. During development, it's a good idea
          to run `sessionStorage.clear();` in the browser console to remove the old configuration after you make
          changes.
