@fixtures
Feature: Creation of nodes underneath hidden nodes

  If we create new nodes underneath of hidden nodes, they must be marked as "hidden" as well; i.e. they
  must have the proper restriction edges as well.

  Background:
    Given I have no content dimensions
    And the command CreateWorkspace is executed with payload:
      | Key                     | Value           |
      | workspaceName           | "live"          |
      | contentStreamIdentifier | "cs-identifier" |
      | rootNodeIdentifier      | "rn-identifier" |
    And I have the following NodeTypes configuration:
    """
    Neos.ContentRepository:Root: {}
    'Neos.ContentRepository.Testing:Content':
      properties:
        text:
          type: string
    """
    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                     | Value                                    |
      | contentStreamIdentifier | "cs-identifier"                          |
      | nodeAggregateIdentifier | "na-identifier"                          |
      | nodeTypeName            | "Neos.ContentRepository.Testing:Content" |
      | nodeIdentifier          | "node-identifier"                        |
      | parentNodeIdentifier    | "rn-identifier"                          |
      | nodeName                | "text1"                                  |
    And the graph projection is fully up to date
    And the command "HideNode" is executed with payload:
      | Key                          | Value           |
      | contentStreamIdentifier      | "cs-identifier" |
      | nodeAggregateIdentifier      | "na-identifier" |
      | affectedDimensionSpacePoints | [{}]            |
    And the graph projection is fully up to date

  Scenario: When a new node is created underneath a hidden node, this one should be hidden as well
    When the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                     | Value                                    |
      | contentStreamIdentifier | "cs-identifier"                          |
      | nodeAggregateIdentifier | "cna-identifier"                         |
      | nodeTypeName            | "Neos.ContentRepository.Testing:Content" |
      | nodeIdentifier          | "cnode-identifier"                       |
      | parentNodeIdentifier    | "node-identifier"                        |
      | nodeName                | "text2"                                  |

    And the graph projection is fully up to date

    When I am in content stream "cs-identifier" and Dimension Space Point {}
    Then I expect a node identified by aggregate identifier "cna-identifier" not to exist in the subgraph
