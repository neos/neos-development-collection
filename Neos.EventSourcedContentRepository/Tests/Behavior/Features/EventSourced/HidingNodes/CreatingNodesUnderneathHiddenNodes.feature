@fixtures
Feature: Creation of nodes underneath hidden nodes

  If we create new nodes underneath of hidden nodes, they must be marked as "hidden" as well; i.e. they
  must have the proper restriction edges as well.

  Background:
    Given I have no content dimensions
    And the command CreateWorkspace is executed with payload:
      | Key                     | Value         | Type |
      | workspaceName           | live          |      |
      | contentStreamIdentifier | cs-identifier | Uuid |
      | rootNodeIdentifier      | rn-identifier | Uuid |
    And I have the following NodeTypes configuration:
    """
    Neos.ContentRepository:Root: {}
    'Neos.ContentRepository.Testing:Content':
      properties:
        text:
          type: string
    """
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                     | Value                                  | Type |
      | contentStreamIdentifier | cs-identifier                          | Uuid |
      | nodeAggregateIdentifier | na-identifier                          | Uuid |
      | nodeTypeName            | Neos.ContentRepository.Testing:Content |      |
      | nodeIdentifier          | node-identifier                        | Uuid |
      | parentNodeIdentifier    | rn-identifier                          | Uuid |
      | nodeName                | text1                                  |      |

    And the command "HideNode" is executed with payload:
      | Key                          | Value         | Type |
      | contentStreamIdentifier      | cs-identifier | Uuid |
      | nodeAggregateIdentifier      | na-identifier | Uuid |
      | affectedDimensionSpacePoints | [{}]          | json |

  Scenario: When a new node is created underneath a hidden node, this one should be hidden as well
    When the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                     | Value                                  | Type |
      | contentStreamIdentifier | cs-identifier                          | Uuid |
      | nodeAggregateIdentifier | cna-identifier                         | Uuid |
      | nodeTypeName            | Neos.ContentRepository.Testing:Content |      |
      | nodeIdentifier          | cnode-identifier                       | Uuid |
      | parentNodeIdentifier    | node-identifier                        | Uuid |
      | nodeName                | text2                                  |      |

    And the graph projection is fully up to date

    When I am in content stream "[cs-identifier]" and Dimension Space Point {}
    Then I expect a node identified by aggregate identifier "[cna-identifier]" not to exist in the subgraph
