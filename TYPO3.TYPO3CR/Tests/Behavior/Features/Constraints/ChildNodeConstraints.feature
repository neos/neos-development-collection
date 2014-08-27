Feature: ChildNode Constraints
  In order to have content with a defined structure
  As an API user of the content repository
  I need to know which child-nodes can be created at a certain point in the hierarchy.

  Basic Principles:

  - Constraints are ONLY enforced for non-auto-created child nodes.
  - For auto-created child nodes, constraints can be specified for *their children* as well:

  - We currently only support the *Child Node Type* Constraint (abbreviated "Node Type Constraint"), which has the following format::

    nodeTypes:
      [NodeTypePattern]: (TRUE|FALSE|NULL)

  - NodeTypePattern is usually a *Node Type*, or `*` marks *the fallback* node type.
    - setting the value to `TRUE` is an explicit *ALLOW*
    - setting the value to `FALSE` is an explicit *DENY*
    - setting the value to `NULL` (i.e. using `~` in YAML) is an *ABSTAIN*, so that means the fallback of `*` is used.

  - the node types must be listed *explicitly*, so if allowing/disallowing "Foo", the subtypes of "Foo" are NOT automatically allowed/disallowed.
  - The default is to *ALWAYS DENY* (in case "*" is not specified).

  Background:
    Given I have the following NodeTypes configuration:
    """
    'unstructured':
      constraints:
        nodeTypes:
          '*': TRUE

    'TYPO3.Neos:Document':
      constraints:
        nodeTypes:
          "*": TRUE

    'TYPO3.Neos.NodeTypes:Page':
      superTypes: ['TYPO3.Neos:Document']

    'TYPO3.NeosDemoTypo3Org:Chapter':
      superTypes: ['TYPO3.Neos:Document']

    'TYPO3.Neos:Content': []

    'TYPO3.Neos:ContentCollection':
      superTypes: ['TYPO3.Neos:Content']
      constraints:
        nodeTypes:
          "*": TRUE

    'TYPO3.Neos.NodeTypes:Text':
      superTypes: ['TYPO3.Neos:Content']

    'TYPO3.Neos.NodeTypes:Image':
      superTypes: ['TYPO3.Neos:Content']

    'TYPO3.Neos.NodeTypes:TextWithImage':
      superTypes: ['TYPO3.Neos.NodeTypes:Text', 'TYPO3.Neos.NodeTypes:Image']
    """
    And I have the following nodes:
      | Identifier                           | Path                      | Node Type                    |
      | ecf40ad1-3119-0a43-d02e-55f8b5aa3c70 | /sites                    | unstructured                 |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/neosdemotypo3      | TYPO3.Neos.NodeTypes:Page    |
      | 52540602-b417-11e3-9358-14109fd7a2dd | /sites/neosdemotypo3/main | TYPO3.Neos:ContentCollection |

  @fixtures
  Scenario: Allow node types for direct child nodes
    When I get a node by path "/sites/neosdemotypo3" with the following context:
      | Workspace |
      | live      |
    Then I should be able to create a child node of type "TYPO3.Neos.NodeTypes:Page"

  @fixtures
  Scenario: Disallow node types for direct child nodes
    Given I have the following additional NodeTypes configuration:
    """
    'TYPO3.Neos.NodeTypes:Page':
      constraints:
        nodeTypes:
          'TYPO3.Neos.NodeTypes:Page': FALSE
    """
    When I get a node by path "/sites/neosdemotypo3" with the following context:
      | Workspace |
      | live      |
    Then I should not be able to create a child node of type "TYPO3.Neos.NodeTypes:Page"
    And  I should be able to create a child node of type "TYPO3.NeosDemoTypo3Org:Chapter"

  @fixtures
  Scenario: Allow node types for auto-created child nodes
    Given I have the following additional NodeTypes configuration:
    """
    'TYPO3.Neos.NodeTypes:Page':
      childNodes:
        main:
          type: 'TYPO3.Neos:ContentCollection'
          constraints:
            nodeTypes:
              'TYPO3.Neos.NodeTypes:Text': TRUE
              '*': FALSE
    """
    When I get a node by path "/sites/neosdemotypo3/main" with the following context:
      | Workspace |
      | live      |
    Then I should be able to create a child node of type "TYPO3.Neos.NodeTypes:Text"
    And I should not be able to create a child node of type "TYPO3.Neos.NodeTypes:Image"
    And I should not be able to create a child node of type "TYPO3.Neos.NodeTypes:TextWithImage"

  @fixtures
  Scenario: Disallow node types for auto-created child nodes, taking child node type constraints into account
    Given I have the following additional NodeTypes configuration:
    """
    'TYPO3.Neos.NodeTypes:Page':
      childNodes:
        main:
          type: 'TYPO3.Neos:ContentCollection'
          constraints:
            nodeTypes:
              'TYPO3.Neos.NodeTypes:Text': FALSE
    """
    When I get a node by path "/sites/neosdemotypo3/main" with the following context:
      | Workspace |
      | live      |
    Then I should not be able to create a child node of type "TYPO3.Neos.NodeTypes:Text"
    And I should be able to create a child node of type "TYPO3.Neos.NodeTypes:Image"
    And I should be able to create a child node of type "TYPO3.Neos.NodeTypes:TextWithImage"

  @fixtures
  Scenario: Inherit constraints from super-types
    Given I have the following additional NodeTypes configuration:
    """
    'TYPO3.Neos.NodeTypes:Page':
      childNodes:
        main:
          type: 'TYPO3.Neos:ContentCollection'
          constraints:
            nodeTypes:
              'TYPO3.Neos.NodeTypes:Text': FALSE
    """
    When I get a node by path "/sites/neosdemotypo3/main" with the following context:
      | Workspace |
      | live      |
    Then I should not be able to create a child node of type "TYPO3.Neos.NodeTypes:Text"
    And I should be able to create a child node of type "TYPO3.Neos.NodeTypes:Image"
    And I should be able to create a child node of type "TYPO3.Neos.NodeTypes:TextWithImage"

  @fixtures
  Scenario: Reset constraints from super-types
    Given I have the following additional NodeTypes configuration:
    """
    'TYPO3.Neos:Document':
      constraints:
        childNodes:
          'TYPO3.NeosDemoTypo3Org:Chapter': FALSE

    'TYPO3.Neos.NodeTypes:Page':
      constraints:
        childNodes:
          'TYPO3.NeosDemoTypo3Org:Chapter': ~
    """
    When I get a node by path "/sites/neosdemotypo3" with the following context:
      | Workspace |
      | live      |
    And I should be able to create a child node of type "TYPO3.NeosDemoTypo3Org:Chapter"

  @fixtures
  Scenario: Constraints for auto created childnodes are ignored on node create
    Given I have the following additional NodeTypes configuration:
    """
    'TYPO3.Neos.NodeTypes:Page':
      childNodes:
        main:
          type: 'TYPO3.Neos:ContentCollection'
      constraints:
        nodeTypes:
          'TYPO3.Neos:ContentCollection': FALSE
    """
    And I have the following nodes:
      | Identifier                           | Path                                | Node Type                     | Properties
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/neosdemotypo3/create-page    | TYPO3.Neos.NodeTypes:Page     | {"title": "page"}
    And I get a node by path "/sites/neosdemotypo3/create-page/main" with the following context:
      | Workspace |
      | live      |
    Then I should have one node

  @fixtures
  Scenario: Constraints for auto created childnodes are ignored on node copy
    Given I have the following additional NodeTypes configuration:
    """
    'TYPO3.Neos.NodeTypes:Page':
      childNodes:
        main:
          type: 'TYPO3.Neos:ContentCollection'
      constraints:
        nodeTypes:
          'TYPO3.Neos:ContentCollection': FALSE
    """
    And I have the following nodes:
      | Identifier                           | Path                              | Node Type                     | Properties
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/neosdemotypo3/copy-page    | TYPO3.Neos.NodeTypes:Page     | {"title": "page"}
    And I get a node by path "/sites/neosdemotypo3/copy-page" with the following context:
      | Workspace |
      | live      |
    When I copy the node into path "/sites/neosdemotypo3" with the following context:
      | Workspace |
      | live      |
    And I get a node by path "/sites/neosdemotypo3/copy-page-1" with the following context:
      | Workspace |
      | live      |
    Then I should have one node

  @fixtures
  Scenario: Nodes with auto created childnodes with constraints on nodetype can be moved
    Given I have the following additional NodeTypes configuration:
    """
    'TYPO3.Neos.NodeTypes:Page':
      childNodes:
        main:
          type: 'TYPO3.Neos:ContentCollection'
      constraints:
        nodeTypes:
          'TYPO3.Neos:ContentCollection': FALSE
    """
    And I have the following nodes:
      | Identifier                           | Path                               | Node Type                     | Properties
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/neosdemotypo3/move-page1    | TYPO3.Neos.NodeTypes:Page     | {"title": "page"}
      | ad5ba6e1-4313-b145-1004-dad2f1173a36 | /sites/neosdemotypo3/move-page2    | TYPO3.Neos.NodeTypes:Page     | {"title": "page 2"}
    And I get a node by path "/sites/neosdemotypo3/move-page1" with the following context:
      | Workspace |
      | live      |
    When I move the node into path "/sites/neosdemotypo3/move-page2" with the following context:
      | Workspace |
      | live      |
    And I get a node by path "/sites/neosdemotypo3/move-page2/move-page1" with the following context:
      | Workspace |
      | live      |
    Then I should have one node
