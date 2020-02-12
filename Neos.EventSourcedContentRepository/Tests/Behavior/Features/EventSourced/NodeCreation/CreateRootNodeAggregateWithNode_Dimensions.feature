@fixtures
Feature: Create a root node aggregate

  As a user of the CR I want to create a new root node aggregate with an initial node.

  This is the tale of venerable root node aggregate Sir David Nodenborough already persistent in the content graph for quite some time
  and Nody McNodeface, a new root node aggregate to be added.

  Background:
    Given I have the following content dimensions:
      | Identifier | Default | Values          | Generalizations      |
      | language   | mul     | mul, de, en, ch | ch->de->mul, en->mul |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    """
    And the event RootWorkspaceWasCreated was published with payload:
      | Key                            | Value                                  |
      | workspaceName                  | "live"                                 |
      | workspaceTitle                 | "Live"                                 |
      | workspaceDescription           | "The live workspace"                   |
      | initiatingUserIdentifier       | "00000000-0000-0000-0000-000000000000" |
      | newContentStreamIdentifier     | "cs-identifier"                        |
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                                                             |
      | contentStreamIdentifier     | "cs-identifier"                                                                   |
      | nodeAggregateIdentifier     | "sir-david-nodenborough"                                                          |
      | nodeTypeName                | "Neos.ContentRepository:Root"                                                     |
      | coveredDimensionSpacePoints | [{"language": "mul"}, {"language": "de"}, {"language": "en"}, {"language": "ch"}] |
      | initiatingUserIdentifier    | "00000000-0000-0000-0000-000000000000"                                            |
      | nodeAggregateClassification | "root"                                                                            |

  Scenario: Create a root node aggregate using valid payload with dimensions
    When the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                      | Value                                  |
      | contentStreamIdentifier  | "cs-identifier"                        |
      | nodeAggregateIdentifier  | "nody-mc-nodeface"                     |
      | nodeTypeName             | "Neos.ContentRepository:Root"          |
      | initiatingUserIdentifier | "00000000-0000-0000-0000-000000000000" |

    Then I expect exactly 3 events to be published on stream "Neos.ContentRepository:ContentStream:cs-identifier"
    And event at index 2 is of type "Neos.EventSourcedContentRepository:RootNodeAggregateWithNodeWasCreated" with payload:
      | Key                         | Expected                                                                          |
      | contentStreamIdentifier     | "cs-identifier"                                                                   |
      | nodeAggregateIdentifier     | "nody-mc-nodeface"                                                                |
      | nodeTypeName                | "Neos.ContentRepository:Root"                                                     |
      | coveredDimensionSpacePoints | [{"language": "mul"}, {"language": "de"}, {"language": "en"}, {"language": "ch"}] |
      | initiatingUserIdentifier    | "00000000-0000-0000-0000-000000000000"                                            |
      | nodeAggregateClassification | "root"                                                                            |

    When the graph projection is fully up to date
    Then I expect the graph projection to consist of exactly 2 nodes
    And I expect a node with identifier {"nodeAggregateIdentifier": "sir-david-nodenborough", "contentStreamIdentifier": "cs-identifier", "originDimensionSpacePoint": {}} to exist in the content graph
    And I expect a node with identifier {"nodeAggregateIdentifier": "nody-mc-nodeface", "contentStreamIdentifier": "cs-identifier", "originDimensionSpacePoint": {}} to exist in the content graph

    When I am in content stream "cs-identifier" and Dimension Space Point {"language": "mul"}
    Then I expect the subgraph projection to consist of exactly 2 nodes
    And I expect node aggregate identifier "sir-david-nodenborough" to lead to node {"nodeAggregateIdentifier": "sir-david-nodenborough", "contentStreamIdentifier": "cs-identifier", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "nody-mc-nodeface" to lead to node {"nodeAggregateIdentifier": "nody-mc-nodeface", "contentStreamIdentifier": "cs-identifier", "originDimensionSpacePoint": {}}

    When I am in content stream "cs-identifier" and Dimension Space Point {"language": "de"}
    Then I expect the subgraph projection to consist of exactly 2 nodes
    And I expect node aggregate identifier "sir-david-nodenborough" to lead to node {"nodeAggregateIdentifier": "sir-david-nodenborough", "contentStreamIdentifier": "cs-identifier", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "nody-mc-nodeface" to lead to node {"nodeAggregateIdentifier": "nody-mc-nodeface", "contentStreamIdentifier": "cs-identifier", "originDimensionSpacePoint": {}}

    When I am in content stream "cs-identifier" and Dimension Space Point {"language": "en"}
    Then I expect the subgraph projection to consist of exactly 2 nodes
    And I expect node aggregate identifier "sir-david-nodenborough" to lead to node {"nodeAggregateIdentifier": "sir-david-nodenborough", "contentStreamIdentifier": "cs-identifier", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "nody-mc-nodeface" to lead to node {"nodeAggregateIdentifier": "nody-mc-nodeface", "contentStreamIdentifier": "cs-identifier", "originDimensionSpacePoint": {}}

    When I am in content stream "cs-identifier" and Dimension Space Point {"language": "ch"}
    Then I expect the subgraph projection to consist of exactly 2 nodes
    And I expect node aggregate identifier "sir-david-nodenborough" to lead to node {"nodeAggregateIdentifier": "sir-david-nodenborough", "contentStreamIdentifier": "cs-identifier", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "nody-mc-nodeface" to lead to node {"nodeAggregateIdentifier": "nody-mc-nodeface", "contentStreamIdentifier": "cs-identifier", "originDimensionSpacePoint": {}}
