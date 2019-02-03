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
      | Key                            | Value                                | Type                    |
      | workspaceName                  | live                                 | WorkspaceName           |
      | workspaceTitle                 | Live                                 | WorkspaceTitle          |
      | workspaceDescription           | The live workspace                   | WorkspaceDescription    |
      | initiatingUserIdentifier       | 00000000-0000-0000-0000-000000000000 | UserIdentifier          |
      | currentContentStreamIdentifier | c75ae6a2-7254-4d42-a31b-a629e264069d | ContentStreamIdentifier |
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                | Type                    |
      | contentStreamIdentifier       | c75ae6a2-7254-4d42-a31b-a629e264069d | ContentStreamIdentifier |
      | nodeAggregateIdentifier       | sir-david-nodenborough               | NodeAggregateIdentifier |
      | nodeTypeName                  | Neos.ContentRepository:Root          | NodeTypeName            |
      | visibleInDimensionSpacePoints | [{}]                                 | DimensionSpacePointSet  |
      | initiatingUserIdentifier      | 00000000-0000-0000-0000-000000000000 | UserIdentifier          |

  Scenario: Try to create a root node aggregate in a content stream that currently does not exist:
    When the command CreateRootNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                      | Value                                | Type                    |
      | contentStreamIdentifier  | 00000000-0000-0000-0000-000000000000 | ContentStreamIdentifier |
      | nodeAggregateIdentifier  | nody-mc-nodeface                     | NodeAggregateIdentifier |
      | nodeTypeName             | Neos.ContentRepository:Root          | NodeTypeName            |
      | initiatingUserIdentifier | 00000000-0000-0000-0000-000000000000 | UserIdentifier          |

    Then the last command should have thrown an exception of type "ContentStreamDoesNotExistYet"

  Scenario: Try to create a root node aggregate in a content stream where it is already present:
    When the command CreateRootNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                      | Value                                | Type                    |
      | contentStreamIdentifier  | c75ae6a2-7254-4d42-a31b-a629e264069d | ContentStreamIdentifier |
      | nodeAggregateIdentifier  | sir-david-nodenborough               | NodeAggregateIdentifier |
      | nodeTypeName             | Neos.ContentRepository:Root          | NodeTypeName            |
      | initiatingUserIdentifier | 00000000-0000-0000-0000-000000000000 | UserIdentifier          |

    Then the last command should have thrown an exception of type "NodeAggregateCurrentlyExists"

  Scenario: Try to create a root node aggregate of a non-root node type:
    When the command CreateRootNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                      | Value                                  | Type                    |
      | contentStreamIdentifier  | c75ae6a2-7254-4d42-a31b-a629e264069d   | ContentStreamIdentifier |
      | nodeAggregateIdentifier  | nody-mc-nodeface                       | NodeAggregateIdentifier |
      | nodeTypeName             | Neos.ContentRepository.Testing:NonRoot | NodeTypeName            |
      | initiatingUserIdentifier | 00000000-0000-0000-0000-000000000000   | UserIdentifier          |

    Then the last command should have thrown an exception of type "NodeTypeIsNotOfTypeRoot"

  Scenario: Create a root node aggregate using valid payload without dimensions
    When the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                      | Value                                | Type                    |
      | contentStreamIdentifier  | c75ae6a2-7254-4d42-a31b-a629e264069d | ContentStreamIdentifier |
      | nodeAggregateIdentifier  | nody-mc-nodeface                     | NodeAggregateIdentifier |
      | nodeTypeName             | Neos.ContentRepository:Root          | NodeTypeName            |
      | initiatingUserIdentifier | 00000000-0000-0000-0000-000000000000 | UserIdentifier          |

    Then I expect exactly 1 event to be published on stream "Neos.ContentRepository:ContentStream:c75ae6a2-7254-4d42-a31b-a629e264069d:NodeAggregate:nody-mc-nodeface"
    And event at index 0 is of type "Neos.EventSourcedContentRepository:RootNodeAggregateWithNodeWasCreated" with payload:
      | Key                           | Expected                             | Type                    | AssertionType |
      | contentStreamIdentifier       | c75ae6a2-7254-4d42-a31b-a629e264069d | ContentStreamIdentifier |               |
      | nodeAggregateIdentifier       | nody-mc-nodeface                     | NodeAggregateIdentifier |               |
      | nodeTypeName                  | Neos.ContentRepository:Root          | NodeTypeName            |               |
      | visibleInDimensionSpacePoints | [[]]                                 |                         | json          |
      | initiatingUserIdentifier      | 00000000-0000-0000-0000-000000000000 | UserIdentifier          |               |

    When the graph projection is fully up to date
    Then I expect a node with identifier {"nodeAggregateIdentifier": "nody-mc-nodeface", contentStreamIdentifier: "c75ae6a2-7254-4d42-a31b-a629e264069d", "originDimensionSpacePoint": {}} to exist in the content graph
    And I expect a node with identifier {"nodeAggregateIdentifier": "sir-david-nodenborough", contentStreamIdentifier: "c75ae6a2-7254-4d42-a31b-a629e264069d", "originDimensionSpacePoint": {}} to exist in the content graph

    When I am in content stream "c75ae6a2-7254-4d42-a31b-a629e264069d" and Dimension Space Point {}
    Then I expect a node identified by aggregate identifier "nody-mc-nodeface" to exist in the subgraph
    And I expect a node identified by aggregate identifier "sir-david-nodenborough" to exist in the subgraph
