Feature: ChildNode Constraints
  In order to have content with a defined structure
  As an API user of the content repository
  I need to know which child-nodes can be created at a certain point in the hierarchy.

  Basic Principles:

  - Constraints are ONLY enforced for non-auto-created child nodes.
  - For auto-created child nodes, constraints can be specified for *their children* as well:

  - We currently only support the *Child Node Type* Constraint (abbreviated "Node Type Constraint"), which has the following format::

    nodeTypes:
      [NodeTypePattern]: (true|false|null)

  - NodeTypePattern is usually a *Node Type*, or `*` marks *the fallback* node type.
    - setting the value to `true` is an explicit *ALLOW*
    - setting the value to `false` is an explicit *DENY*
    - setting the value to `null` (i.e. using `~` in YAML) is an *ABSTAIN*, so that means the fallback of `*` is used.

  - The node type inheritance is taken into account, so if allowing/disallowing "Foo", the subtypes of "Foo" are automatically allowed/disallowed.
  - The default is to *ALWAYS DENY* (in case "*" is not specified).

  Background:
    Given I have the following NodeTypes configuration:
    """
    'unstructured':
      constraints:
        nodeTypes:
          '*': true

    'Neos.ContentRepository.Testing:Document':
      constraints:
        nodeTypes:
          "*": true

    'Neos.ContentRepository.Testing:Page':
      superTypes:
        'Neos.ContentRepository.Testing:Document': true

    'Neos.ContentRepository.Testing:Chapter':
      superTypes:
        'Neos.ContentRepository.Testing:Document': true

    'Neos.ContentRepository.Testing:Content': []

    'Neos.ContentRepository.Testing:ContentCollection':
      superTypes:
        'Neos.ContentRepository.Testing:Content': true
      constraints:
        nodeTypes:
          "*": true

    'Neos.ContentRepository.Testing:Text':
      superTypes:
        'Neos.ContentRepository.Testing:Content': true

    'Neos.ContentRepository.Testing:Image':
      superTypes:
        'Neos.ContentRepository.Testing:Content': true

    'Neos.ContentRepository.Testing:TextWithImage':
      superTypes:
        'Neos.ContentRepository.Testing:Text': true
        'Neos.ContentRepository.Testing:Image': true
    """
    And I have the following nodes:
      | Identifier                           | Path                | Node Type                               |
      | ecf40ad1-3119-0a43-d02e-55f8b5aa3c70 | /sites              | unstructured                            |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/content-repository      | Neos.ContentRepository.Testing:Page              |
      | 52540602-b417-11e3-9358-14109fd7a2dd | /sites/content-repository/main | Neos.ContentRepository.Testing:ContentCollection |
    And I am authenticated with role "Neos.Neos:Editor"

  @fixtures
  Scenario: Allow node types for direct child nodes
    When I get a node by path "/sites/content-repository" with the following context:
      | Workspace |
      | live      |
    Then I should be able to create a child node of type "Neos.ContentRepository.Testing:Page"

  @fixtures
  Scenario: Disallow node types for direct child nodes
    Given I have the following additional NodeTypes configuration:
    """
    'Neos.ContentRepository.Testing:Page':
      constraints:
        nodeTypes:
          'Neos.ContentRepository.Testing:Page': false
    """
    When I get a node by path "/sites/content-repository" with the following context:
      | Workspace |
      | live      |
    Then I should not be able to create a child node of type "Neos.ContentRepository.Testing:Page"
    And  I should be able to create a child node of type "Neos.ContentRepository.Testing:Chapter"

  @fixtures
  Scenario: Allow node types for auto-created child nodes
    Given I have the following additional NodeTypes configuration:
    """
    'Neos.ContentRepository.Testing:Page':
      childNodes:
        main:
          type: 'Neos.ContentRepository.Testing:ContentCollection'
          constraints:
            nodeTypes:
              'Neos.ContentRepository.Testing:Text': true
              '*': false
    """
    When I get a node by path "/sites/content-repository/main" with the following context:
      | Workspace |
      | live      |
    Then I should be able to create a child node of type "Neos.ContentRepository.Testing:Text"
    And I should be able to create a child node of type "Neos.ContentRepository.Testing:TextWithImage"
    And I should not be able to create a child node of type "Neos.ContentRepository.Testing:Image"

  @fixtures
  Scenario: Disallow node types for auto-created child nodes, taking child node type constraints into account
    Given I have the following additional NodeTypes configuration:
    """
    'Neos.ContentRepository.Testing:Page':
      childNodes:
        main:
          type: 'Neos.ContentRepository.Testing:ContentCollection'
          constraints:
            nodeTypes:
              'Neos.ContentRepository.Testing:Text': false
    """
    When I get a node by path "/sites/content-repository/main" with the following context:
      | Workspace |
      | live      |
    Then I should not be able to create a child node of type "Neos.ContentRepository.Testing:Text"
    And I should not be able to create a child node of type "Neos.ContentRepository.Testing:TextWithImage"
    And I should be able to create a child node of type "Neos.ContentRepository.Testing:Image"

  @fixtures
  Scenario: Inherit constraints from super-types
    Given I have the following additional NodeTypes configuration:
    """
    'Neos.ContentRepository.Testing:Page':
      childNodes:
        main:
          type: 'Neos.ContentRepository.Testing:ContentCollection'
          constraints:
            nodeTypes:
              'Neos.ContentRepository.Testing:Text': false
    """
    When I get a node by path "/sites/content-repository/main" with the following context:
      | Workspace |
      | live      |
    Then I should not be able to create a child node of type "Neos.ContentRepository.Testing:Text"
    And I should not be able to create a child node of type "Neos.ContentRepository.Testing:TextWithImage"
    And I should be able to create a child node of type "Neos.ContentRepository.Testing:Image"

  @fixtures
  Scenario: Reset constraints from super-types
    Given I have the following additional NodeTypes configuration:
    """
    'Neos.ContentRepository.Testing:Document':
      constraints:
        childNodes:
          'Neos.ContentRepository.Testing:Chapter': false

    'Neos.ContentRepository.Testing:Page':
      constraints:
        childNodes:
          'Neos.ContentRepository.Testing:Chapter': ~
    """
    When I get a node by path "/sites/content-repository" with the following context:
      | Workspace |
      | live      |
    Then I should be able to create a child node of type "Neos.ContentRepository.Testing:Chapter"

  @fixtures
  Scenario: Constraints for auto created childnodes are ignored on node create
    Given I have the following additional NodeTypes configuration:
    """
    'Neos.ContentRepository.Testing:Page':
      childNodes:
        main:
          type: 'Neos.ContentRepository.Testing:ContentCollection'
      constraints:
        nodeTypes:
          'Neos.ContentRepository.Testing:ContentCollection': false
    """
    When I have the following nodes:
      | Identifier                           | Path                                     | Node Type                               | Properties        |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/content-repository/create-page    | Neos.ContentRepository.Testing:Page     | {"title": "page"} |
    And I get a node by path "/sites/content-repository/create-page/main" with the following context:
      | Workspace |
      | live      |
    Then I should have one node

  @fixtures
  Scenario: Constraints for auto created childnodes are ignored on node copy
    Given I have the following additional NodeTypes configuration:
    """
    'Neos.ContentRepository.Testing:Page':
      childNodes:
        main:
          type: 'Neos.ContentRepository.Testing:ContentCollection'
      constraints:
        nodeTypes:
          'Neos.ContentRepository.Testing:ContentCollection': false
    """
    And I have the following nodes:
      | Identifier                           | Path                                   | Node Type                               | Properties        |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/content-repository/copy-page    | Neos.ContentRepository.Testing:Page     | {"title": "page"} |
    And I get a node by path "/sites/content-repository/copy-page" with the following context:
      | Workspace |
      | live      |
    When I copy the node into path "/sites/content-repository" with the following context:
      | Workspace |
      | live      |
    And I get a node by path "/sites/content-repository/copy-page-1" with the following context:
      | Workspace |
      | live      |
    Then I should have one node

  @fixtures
  Scenario: Nodes with auto created childnodes with constraints on nodetype can be moved
    Given I have the following additional NodeTypes configuration:
    """
    'Neos.ContentRepository.Testing:Page':
      childNodes:
        main:
          type: 'Neos.ContentRepository.Testing:ContentCollection'
      constraints:
        nodeTypes:
          'Neos.ContentRepository.Testing:ContentCollection': false
    """
    And I have the following nodes:
      | Identifier                           | Path                                    | Node Type                               | Properties          |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/content-repository/move-page1    | Neos.ContentRepository.Testing:Page     | {"title": "page"}   |
      | ad5ba6e1-4313-b145-1004-dad2f1173a36 | /sites/content-repository/move-page2    | Neos.ContentRepository.Testing:Page     | {"title": "page 2"} |
    And I get a node by path "/sites/content-repository/move-page1" with the following context:
      | Workspace |
      | live      |
    When I move the node into path "/sites/content-repository/move-page2" with the following context:
      | Workspace |
      | live      |
    And I get a node by path "/sites/content-repository/move-page2/move-page1" with the following context:
      | Workspace |
      | live      |
    Then I should have one node

  @fixtures
  Scenario: Allow node types for auto-created child nodes and disallow inherited subtype
    Given I have the following additional NodeTypes configuration:
    """
    'Neos.ContentRepository.Testing:Page':
      childNodes:
        main:
          type: 'Neos.ContentRepository.Testing:ContentCollection'
          constraints:
            nodeTypes:
              'Neos.ContentRepository.Testing:Text': true
              'Neos.ContentRepository.Testing:TextWithImage': false
              '*': false
    """
    When I get a node by path "/sites/content-repository/main" with the following context:
      | Workspace |
      | live      |
    Then I should be able to create a child node of type "Neos.ContentRepository.Testing:Text"
    And I should not be able to create a child node of type "Neos.ContentRepository.Testing:TextWithImage"
    And I should not be able to create a child node of type "Neos.ContentRepository.Testing:Image"
