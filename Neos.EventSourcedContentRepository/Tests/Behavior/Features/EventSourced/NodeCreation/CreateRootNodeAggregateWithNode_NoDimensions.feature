@fixtures
Feature: Create a root node aggregate

  As a user of the CR I want to create a new root node aggregate with an initial node.

  This is the tale of venerable root node aggregate Sir David Nodenborough already persistent in the content graph for quite some time
  and Nody McNodeface, a new root node aggregate to be added.

  Background:
    Given I have no content dimensions
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:NonRoot': []
    """
    And the event RootWorkspaceWasCreated was published with payload:
      | Key                            | Value                                  |
      | workspaceName                  | "live"                                 |
      | workspaceTitle                 | "Live"                                 |
      | workspaceDescription           | "The live workspace"                   |
      | initiatingUserIdentifier       | "00000000-0000-0000-0000-000000000000" |
      | newContentStreamIdentifier     | "cs-identifier"                        |
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                  |
      | contentStreamIdentifier     | "cs-identifier"                        |
      | nodeAggregateIdentifier     | "sir-david-nodenborough"               |
      | nodeTypeName                | "Neos.ContentRepository:Root"          |
      | coveredDimensionSpacePoints | [{}]                                   |
      | initiatingUserIdentifier    | "00000000-0000-0000-0000-000000000000" |
      | nodeAggregateClassification | "root"                                 |
    And the graph projection is fully up to date

  Scenario: Try to create a root node aggregate in a content stream that currently does not exist:
    When the command CreateRootNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                      | Value                                  |
      | contentStreamIdentifier  | "00000000-0000-0000-0000-000000000000" |
      | nodeAggregateIdentifier  | "nody-mc-nodeface"                     |
      | nodeTypeName             | "Neos.ContentRepository:Root"          |
      | initiatingUserIdentifier | "00000000-0000-0000-0000-000000000000" |

    Then the last command should have thrown an exception of type "ContentStreamDoesNotExistYet"

  Scenario: Try to create a root node aggregate in a content stream where it is already present:
    When the command CreateRootNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                      | Value                                  |
      | contentStreamIdentifier  | "cs-identifier"                        |
      | nodeAggregateIdentifier  | "sir-david-nodenborough"               |
      | nodeTypeName             | "Neos.ContentRepository:Root"          |
      | initiatingUserIdentifier | "00000000-0000-0000-0000-000000000000" |

    Then the last command should have thrown an exception of type "NodeAggregateCurrentlyExists"

  Scenario: Try to create a root node aggregate of a non-root node type:
    When the command CreateRootNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                      | Value                                    |
      | contentStreamIdentifier  | "cs-identifier"                          |
      | nodeAggregateIdentifier  | "nody-mc-nodeface"                       |
      | nodeTypeName             | "Neos.ContentRepository.Testing:NonRoot" |
      | initiatingUserIdentifier | "00000000-0000-0000-0000-000000000000"   |

    Then the last command should have thrown an exception of type "NodeTypeIsNotOfTypeRoot"

  Scenario: Create a root node aggregate using valid payload without dimensions
    When the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                      | Value                                  |
      | contentStreamIdentifier  | "cs-identifier"                        |
      | nodeAggregateIdentifier  | "nody-mc-nodeface"                     |
      | nodeTypeName             | "Neos.ContentRepository:Root"          |
      | initiatingUserIdentifier | "00000000-0000-0000-0000-000000000000" |

    Then I expect exactly 3 events to be published on stream "Neos.ContentRepository:ContentStream:cs-identifier"
    And event at index 2 is of type "Neos.EventSourcedContentRepository:RootNodeAggregateWithNodeWasCreated" with payload:
      | Key                         | Expected                               |
      | contentStreamIdentifier     | "cs-identifier"                        |
      | nodeAggregateIdentifier     | "nody-mc-nodeface"                     |
      | nodeTypeName                | "Neos.ContentRepository:Root"          |
      | coveredDimensionSpacePoints | [[]]                                   |
      | initiatingUserIdentifier    | "00000000-0000-0000-0000-000000000000" |
      | nodeAggregateClassification | "root"                                 |

    When the graph projection is fully up to date
    Then I expect the graph projection to consist of exactly 2 nodes
    And I expect a node with identifier {"nodeAggregateIdentifier": "sir-david-nodenborough", "contentStreamIdentifier": "cs-identifier", "originDimensionSpacePoint": {}} to exist in the content graph
    And I expect a node with identifier {"nodeAggregateIdentifier": "nody-mc-nodeface", "contentStreamIdentifier": "cs-identifier", "originDimensionSpacePoint": {}} to exist in the content graph

    When I am in content stream "cs-identifier" and Dimension Space Point {}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node {"nodeAggregateIdentifier": "sir-david-nodenborough", "contentStreamIdentifier": "cs-identifier", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "nody-mc-nodeface" to lead to node {"nodeAggregateIdentifier": "nody-mc-nodeface", "contentStreamIdentifier": "cs-identifier", "originDimensionSpacePoint": {}}
