Feature: Disable a node aggregate

  As a user of the CR I want to disable a node aggregate and expect its descendants to also be disabled.

  These are the test cases without dimensions being involved

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Document':
      properties:
        references:
          type: references
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the current date and time is "2026-05-11T14:19:00+01:00"
    And I am user identified by "initiating-user-identifier"
    And I set up the edge case workspace tree and the following additional commands:
      | shortName                       | payload                                                                                                                                                                                                                                                   |
      | CreateRootNodeAggregateWithNode | {"workspaceName": "live", "nodeAggregateId": "lady-eleonode-rootford", "nodeTypeName": "Neos.ContentRepository:Root"}                                                                                                                                     |
      | CreateNodeAggregateWithNode     | {"workspaceName": "live", "nodeAggregateId": "preceding-nodenborough", "originDimensionSpacePoint": {}, "nodeTypeName": "Neos.ContentRepository.Testing:Document", "parentNodeAggregateId": "lady-eleonode-rootford", "nodeName": "preceding-document"}   |
      | CreateNodeAggregateWithNode     | {"workspaceName": "live", "nodeAggregateId": "sir-david-nodenborough", "originDimensionSpacePoint": {}, "nodeTypeName": "Neos.ContentRepository.Testing:Document", "parentNodeAggregateId": "lady-eleonode-rootford", "nodeName": "document"}             |
      | CreateNodeAggregateWithNode     | {"workspaceName": "live", "nodeAggregateId": "succeeding-nodenborough", "originDimensionSpacePoint": {}, "nodeTypeName": "Neos.ContentRepository.Testing:Document", "parentNodeAggregateId": "lady-eleonode-rootford", "nodeName": "succeeding-document"} |
      | CreateNodeAggregateWithNode     | {"workspaceName": "live", "nodeAggregateId": "nody-mc-nodeface", "originDimensionSpacePoint": {}, "nodeTypeName": "Neos.ContentRepository.Testing:Document", "parentNodeAggregateId": "sir-david-nodenborough", "nodeName": "child-document"}             |
      | SetNodeReferences               | {"workspaceName": "live", "sourceNodeAggregateId": "preceding-nodenborough", "sourceOriginDimensionSpacePoint": {}, "references": [{"referenceName": "references", "references": [{"target": "sir-david-nodenborough"}]}]}                                      |

  Scenario: Publish subtree tagging
    When the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | workspaceName                | "local"                  |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {}                       |
      | nodeVariantSelectionStrategy | "allVariants"            |
      | tag                          | "my-tag"                 |
    When the command TagSubtree is executed with payload:
      | Key                          | Value              |
      | workspaceName                | "local-3"          |
      | nodeAggregateId              | "nody-mc-nodeface" |
      | coveredDimensionSpacePoint   | {}                 |
      | nodeVariantSelectionStrategy | "allVariants"      |
      | tag                          | "my-tag"           |

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

  Scenario: Partially publish subtree tagging
    When the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | workspaceName                | "local"                  |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {}                       |
      | nodeVariantSelectionStrategy | "allVariants"            |
      | tag                          | "my-tag"                 |
    When the command TagSubtree is executed with payload:
      | Key                          | Value              |
      | workspaceName                | "local-3"          |
      | nodeAggregateId              | "nody-mc-nodeface" |
      | coveredDimensionSpacePoint   | {}                 |
      | nodeVariantSelectionStrategy | "allVariants"      |
      | tag                          | "my-tag"           |

    And I memorise the global graph state
    And the command PublishIndividualNodesFromWorkspace is executed with payload:
      | Key                             | Value                      |
      | workspaceName                   | "local"                    |
      | nodesToPublish                  | ["sir-david-nodenborough"] |
      | contentStreamIdForRemainingPart | "remaining-local-cs-id"    |
    And I expect the graph state for workspace "local" to be unchanged
    Then I expect the graph state for workspace "live" to be unchanged
    And I expect the graph state for workspace "local-2" to be unchanged
    And I expect the graph state for workspace "local-3" to be unchanged
    And I expect the graph state for workspace "intermediate" to have changed as declared in the snapshot
