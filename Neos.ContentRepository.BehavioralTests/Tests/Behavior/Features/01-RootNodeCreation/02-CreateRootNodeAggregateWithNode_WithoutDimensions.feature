Feature: Create a root node aggregate

  As a user of the CR I want to create a new root node aggregate with an initial node.

  This is the tale of venerable root node aggregate Lady Eleonode Rootford already persistent in the content graph for quite some time
  and Nody McNodeface, a new root node aggregate to be added.

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository:AnotherRoot':
      superTypes:
        'Neos.ContentRepository:Root': true
    'Neos.ContentRepository:YetAnotherRoot':
      superTypes:
        'Neos.ContentRepository:Root': true
    'Neos.ContentRepository.Testing:NonRoot': []
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live"

  Scenario: Create the initial root node aggregate using valid payload without dimensions
    When the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |

    Then I expect exactly 2 events to be published on stream "ContentStream:cs-identifier"
    And event at index 1 is of type "RootNodeAggregateWithNodeWasCreated" with payload:
      | Key                         | Expected                      |
      | contentStreamId             | "cs-identifier"               |
      | nodeAggregateId             | "lady-eleonode-rootford"      |
      | nodeTypeName                | "Neos.ContentRepository:Root" |
      | coveredDimensionSpacePoints | [[]]                          |
      | nodeAggregateClassification | "root"                        |

    Then I expect the node aggregate "lady-eleonode-rootford" to exist
    And I expect this node aggregate to be classified as "root"
    And I expect this node aggregate to be of type "Neos.ContentRepository:Root"
    And I expect this node aggregate to be unnamed
    And I expect this node aggregate to occupy dimension space points [[]]
    And I expect this node aggregate to cover dimension space points [[]]
    And I expect this node aggregate to disable dimension space points []
    And I expect this node aggregate to have no parent node aggregates
    And I expect this node aggregate to have no child node aggregates

    And I expect the graph projection to consist of exactly 1 node
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect this node to be classified as "root"
    And I expect this node to be of type "Neos.ContentRepository:Root"
    And I expect this node to be unnamed
    And I expect this node to have no properties

    When I am in dimension space point {}
    Then I expect the subgraph projection to consist of exactly 1 node
    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no parent node
    And I expect this node to have no child nodes
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings
    And I expect this node to have no references
    And I expect this node to not be referenced

  Scenario: Create a root node aggregate using valid payload without dimensions
    Given the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |

    When the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                                |
      | nodeAggregateId | "nody-mc-nodeface"                   |
      | nodeTypeName    | "Neos.ContentRepository:AnotherRoot" |

    Then I expect exactly 3 events to be published on stream "ContentStream:cs-identifier"
    And event at index 2 is of type "RootNodeAggregateWithNodeWasCreated" with payload:
      | Key                         | Expected                             |
      | contentStreamId             | "cs-identifier"                      |
      | nodeAggregateId             | "nody-mc-nodeface"                   |
      | nodeTypeName                | "Neos.ContentRepository:AnotherRoot" |
      | coveredDimensionSpacePoints | [[]]                                 |
      | nodeAggregateClassification | "root"                               |

    Then I expect the node aggregate "lady-eleonode-rootford" to exist
    And I expect this node aggregate to have no parent node aggregates
    And I expect this node aggregate to have no child node aggregates
    And I expect the node aggregate "nody-mc-nodeface" to exist
    And I expect this node aggregate to be classified as "root"
    And I expect this node aggregate to be of type "Neos.ContentRepository:AnotherRoot"
    And I expect this node aggregate to be unnamed
    And I expect this node aggregate to occupy dimension space points [[]]
    And I expect this node aggregate to cover dimension space points [[]]
    And I expect this node aggregate to disable dimension space points []
    And I expect this node aggregate to have no parent node aggregates
    And I expect this node aggregate to have no child node aggregates

    And I expect the graph projection to consist of exactly 2 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{} to exist in the content graph
    And I expect this node to be classified as "root"
    And I expect this node to be of type "Neos.ContentRepository:AnotherRoot"
    And I expect this node to be unnamed
    And I expect this node to have no properties

    When I am in dimension space point {}
    Then I expect the subgraph projection to consist of exactly 2 nodes
    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                 |
      | cs-identifier;nody-mc-nodeface;{} |
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{}
    And I expect this node to have no parent node
    And I expect this node to have no child nodes
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                       |
      | cs-identifier;lady-eleonode-rootford;{} |
    And I expect this node to have no succeeding siblings
    And I expect this node to have no references
    And I expect this node to not be referenced

  Scenario: Publish all of the above
    Given I set up the edge case workspace tree
    When the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | workspaceName   | "local"                       |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                                |
      | workspaceName   | "local"                              |
      | nodeAggregateId | "sire-frode-rootford"                |
      | nodeTypeName    | "Neos.ContentRepository:AnotherRoot" |
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                                   |
      | workspaceName   | "local-3"                               |
      | nodeAggregateId | "nody-mc-rootface"                      |
      | nodeTypeName    | "Neos.ContentRepository:YetAnotherRoot" |
    And I memorise the global graph state
    And the command PublishWorkspace is executed with payload:
      | Key                | Value             |
      | workspaceName      | "local"           |
      | newContentStreamId | "new-local-cs-id" |
    And I expect the graph state for workspace "local" to be unchanged
    Then I expect the graph state for workspace "live" to be unchanged
    And I expect the graph state for workspace "local-2" to be unchanged
    And I expect the graph state for workspace "local-3" to be unchanged
    And I expect the graph state for workspace "intermediate" to equal that of workspace "local"

  Scenario: Partial publishing
    Given the current date and time is "2026-05-11T14:19:00+00:00"
    Given I set up the edge case workspace tree
    When the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | workspaceName   | "local"                       |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                                |
      | workspaceName   | "local"                              |
      | nodeAggregateId | "sire-frode-rootford"                |
      | nodeTypeName    | "Neos.ContentRepository:AnotherRoot" |
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                                   |
      | workspaceName   | "local-3"                               |
      | nodeAggregateId | "nody-mc-rootface"                      |
      | nodeTypeName    | "Neos.ContentRepository:YetAnotherRoot" |
    And I memorise the global graph state
    And the command PublishIndividualNodesFromWorkspace is executed with payload:
      | Key                             | Value                          |
      | workspaceName                   | "local"                    |
      | nodesToPublish                  | ["lady-eleonode-rootford"]     |
      | contentStreamIdForRemainingPart | "remaining-local-cs-id" |
    And I expect the graph state for workspace "local" to be unchanged
    Then I expect the graph state for workspace "live" to be unchanged
    And I expect the graph state for workspace "local-2" to be unchanged
    And I expect the graph state for workspace "local-3" to be unchanged
    And I expect the graph state for workspace "intermediate" to have changed as follows:
      """json
      {
          "lady-eleonode-rootford": {
              "d751713988987e9331980363e24189ce": {
                  "node": {
                      "contentRepositoryId": "default",
                      "workspaceName": "intermediate",
                      "dimensionSpacePoint": [],
                      "aggregateId": "lady-eleonode-rootford",
                      "originDimensionSpacePoint": [],
                      "classification": "root",
                      "nodeTypeName": "Neos.ContentRepository:Root",
                      "properties": {},
                      "name": null,
                      "tags": [],
                      "timestamps": {
                          "created": "2026-05-11T14:19:00+00:00",
                          "originalCreated": "2026-05-11T14:19:00+00:00",
                          "lastModified": null,
                          "originalLastModified": null
                      }
                  },
                  "parent": null,
                  "children": [],
                  "precedingSiblings": [],
                  "succeedingSiblings": [],
                  "references": [],
                  "backReferences": []
              }
          }
      }
      """
