.. _node-constraints:

=====================
Node Type Constraints
=====================

In a typical Neos project, you will create lots of custom node types. However, many node types should only be
used in a specific context and not everywhere. Neos allows you to define node type constraints, which restrict
the possible node types that can be added as children of a specific node type. There are two ways to do this:

- Regular node type constraints are defined per node type. They apply in any context the node type appears in.
- Additionally, when a node type has auto-created child nodes (see :ref:`node-type-definition`), you can
  define additional constraints that only apply for these child nodes. This allows you to restrict node type
  usage depending on the context that the node types are placed in.

.. note:: Node type constraints are cached in the browser's session storage.
          During development, it's a good idea to run ``sessionStorage.clear()`` in the browser console to remove
          the old configuration after you make changes. Alternatively, you can use an anonymous browser tab to
          avoid storing outdated node type constraints.


Regular Node Type Constraints
=============================
Let's assume that, inside the "Chapter" node type of the Neos Demo Site (which is a document node), one should only be
able to create nested chapters, and not pages or shortcuts. Using node type constraints, this can be enforced::

  'Neos.Demo:Chapter':
    constraints:
      nodeTypes:
        'Neos.Neos:Document': false
        'Neos.Demo:Chapter': true

In the above example, we disable all document node types using ``'Neos.Neos:Document': false``, and then enable the
``Neos.Demo:Chapter`` node type as well as any node type that inherits from it. The reason why we use
``'Neos.Neos:Document': false`` instead of ``'*': false`` here is that by default, only document node types are
allowed as children of other document node types anyway (see further down for more information regarding the defaults).

You might now wonder why it is still possible to create content inside the chapter (because everything except Chapter
is disabled with the above configuration): The reason is that node type constraints are only enforced for nodes which
are *not auto-created*. Because ``Neos.Demo:Chapter`` has an auto-created ``main ContentCollection``, it is still possible
to add content inside. In the following example, we see the node type definition which is shipped with the demo website::

  'Neos.Demo:Chapter':
    superTypes:
      'Neos.Neos:Document': true
    childNodes:
      'main':
        type: 'Neos.Neos:ContentCollection'

The ``main`` ContentCollection is still added, even though you cannot add any more because ContentCollections are not allowed
according to the node type constraints.

Auto-Created Child Node Constraints
===================================
Let's assume that our chapter node type should only contain text within its main ContentCollection. This is possible using
additional constraints for each *auto-created child node*. These constraints will only be applied for the configured
auto-created child nodes - not for any others, even if they are of the same type. ::

  'Neos.Demo:Chapter':
    childNodes:
      'main':
        type: 'Neos.Neos:ContentCollection'
        constraints:
          nodeTypes:
            '*': false
            'Neos.NodeTypes:Text': true


Override Logic and Default Values
=================================
The following logic applies for node type constraints:

- Constraints are only enforced for child nodes which are not auto-created.
- You can specify node types explicitly or use '*' to allow/deny all node types.
- Setting the value to `true` is an explicit *allow*
- Setting the value to `false` is an explicit *deny*
- The default is to *always deny* (in case '*' is not specified).
- More specific constraints override less specific constraints. Specificity is deduced from the inheritance
  hierarchy of the node types. This means that e.g. setting `'*': false` will only apply if no more specific
  constraint has been set, such as `'Neos.Neos:Document': true`.
- Node type constraints are inherited from parent node types. If your node type has listed `Neos.Neos:Document`
  as a superType, its constraints will apply for your node type as well.

The last rule is especially important, since most node types you define will have either ``Neos.NodeTypes:Page``
(which, in turn, inherits from ``Neos.Neos:Document`) or ``Neos.Neos:Content`` as superTypes. You should know which
constraints are defined per default in order to effectively override them. These are the current defaults for these
two node types - this is taken from ``NodeTypes.yaml`` in the Neos.Neos package. ::

  'Neos.Neos:Document':
    constraints:
      nodeTypes:
        '*': false
        'Neos.Neos:Document': true

The document node type, by default, allows any other document node type below it. This means that if you want to
disable all document node types under your custom one, setting ``'*': false`` will have no effect on anything inheriting from
``Neos.Neos:Document`` - the more specific constraint ``'Neos.Neos:Document': true`` will override it. You will need to set
``'Neos.Neos:Document': false`` instead.

The default content node type, on the other hand, only has the catch-all constraint. If you want to enable any child nodes,
you can simply allow them. ::

  'Neos.Neos:Content':
    constraints:
      nodeTypes:
        '*': false

Examples
========
You can use YAML references (with the ``&xyz`` and ``*xyz`` syntax) to re-use constraints. Here's how to
disallow nested Two/Three/FourColumn inside a multi column element::

  'Neos.NodeTypes:Column':
    childNodes:
      column0:
        constraints: &columnConstraints
          nodeTypes:
            'Neos.NodeTypes:TwoColumn': false
            'Neos.NodeTypes:ThreeColumn': false
            'Neos.NodeTypes:FourColumn': false
      column1:
        constraints: *columnConstraints
      column2:
        constraints: *columnConstraints
      column3:
        constraints: *columnConstraints



