@fixtures
Feature: On forking a content stream, hidden nodes should be correctly copied as well.

  Because we store hidden node information in an extra DB table, this needs to be copied correctly on ForkContentStream
  as well.

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

    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                     | Value                                    |
      | contentStreamIdentifier | "cs-identifier"                          |
      | nodeAggregateIdentifier | "cna-identifier"                         |
      | nodeTypeName            | "Neos.ContentRepository.Testing:Content" |
      | nodeIdentifier          | "cnode-identifier"                       |
      | parentNodeIdentifier    | "node-identifier"                        |
      | nodeName                | "text2"                                  |

    And the graph projection is fully up to date

    And the command "HideNode" is executed with payload:
      | Key                          | Value           |
      | contentStreamIdentifier      | "cs-identifier" |
      | nodeAggregateIdentifier      | "na-identifier" |
      | affectedDimensionSpacePoints | [{}]            |

  Scenario: on ForkContentStream, the hidden nodes in the target content stream should still be invisible.
    When the command "ForkContentStream" is executed with payload:
      | Key                           | Value                |
      | sourceContentStreamIdentifier | "cs-identifier"      |
      | contentStreamIdentifier       | "user-cs-identifier" |
    And the graph projection is fully up to date

    When I am in content stream "user-cs-identifier" and Dimension Space Point {}

    Then I expect a node identified by aggregate identifier "na-identifier" not to exist in the subgraph
    Then I expect a node identified by aggregate identifier "cna-identifier" not to exist in the subgraph
