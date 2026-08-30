Feature: Move a node aggregate into and out of a tagged parent - with workspaces

  Background:
    Given using the following content dimensions:
      | Identifier | Values                      | Generalizations                      |
      | example    | general, source, peer, spec | spec->source->general, peer->general |
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Document': []
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the current date and time is "2026-05-11T14:19:00+01:00"
    And I am user identified by "initiating-user-identifier"
    And I set up the edge case workspace tree and the following additional commands:
      | shortName                       | payload                                                                                                                                                                                                                                                                  |
      | CreateRootNodeAggregateWithNode | {"workspaceName": "live", "originDimensionSpacePoint": {"example": "general"}, "nodeAggregateId": "lady-eleonode-rootford", "nodeTypeName": "Neos.ContentRepository:Root"}                                                                                               |
      | CreateNodeAggregateWithNode     | {"workspaceName": "live", "originDimensionSpacePoint": {"example": "general"}, "nodeAggregateId": "sir-david-nodenborough", "parentNodeAggregateId": "lady-eleonode-rootford", "nodeTypeName": "Neos.ContentRepository.Testing:Document", "nodeName": "parent-document"} |
      | CreateNodeAggregateWithNode     | {"workspaceName": "live", "originDimensionSpacePoint": {"example": "general"}, "nodeAggregateId": "nody-mc-nodeface", "parentNodeAggregateId": "sir-david-nodenborough", "nodeTypeName": "Neos.ContentRepository.Testing:Document", "nodeName": "document"}              |
      | CreateNodeAggregateWithNode     | {"workspaceName": "live", "originDimensionSpacePoint": {"example": "general"}, "nodeAggregateId": "nodimus-mediocre", "parentNodeAggregateId": "nody-mc-nodeface", "nodeTypeName": "Neos.ContentRepository.Testing:Document", "nodeName": "child-document"}              |
      | CreateNodeAggregateWithNode     | {"workspaceName": "live", "originDimensionSpacePoint": {"example": "general"}, "nodeAggregateId": "sir-nodeward-nodington-iii", "parentNodeAggregateId": "lady-eleonode-rootford", "nodeTypeName": "Neos.ContentRepository.Testing:Document", "nodeName": "esquire"}     |
      | CreateNodeAggregateWithNode     | {"workspaceName": "live", "originDimensionSpacePoint": {"example": "general"}, "nodeAggregateId": "nodimus-prime", "parentNodeAggregateId": "sir-nodeward-nodington-iii", "nodeTypeName": "Neos.ContentRepository.Testing:Document", "nodeName": "esquire-child"}        |

  Scenario: Publish moving an untagged node to a new parent that tags itself
    Given the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | workspaceName                | "local"                      |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "source"}        |
      | nodeVariantSelectionStrategy | "allSpecializations"         |
      | tag                          | "tag1"                       |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | workspaceName                | "local-3"                    |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "source"}        |
      | nodeVariantSelectionStrategy | "allSpecializations"         |
      | tag                          | "tag1"                       |
    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | workspaceName                | "local"                      |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | dimensionSpacePoint          | {"example": "source"}        |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
      | relationDistributionStrategy | "gatherSpecializations"      |
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | workspaceName                | "local-3"                    |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | dimensionSpacePoint          | {"example": "source"}        |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
      | relationDistributionStrategy | "gatherSpecializations"      |
    And I memorise the global graph state
    And the command PublishWorkspace is executed with payload:
      | Key                | Value             |
      | workspaceName      | "local"           |
      | newContentStreamId | "new-local-cs-id" |
    Then I expect the graph state for workspace "local" to be unchanged
    And I expect the graph state for workspace "live" to be unchanged
    And I expect the graph state for workspace "local-2" to be unchanged
    And I expect the graph state for workspace "local-3" to be unchanged
    And I expect the graph state for workspace "intermediate" to equal that of workspace "local"

  Scenario: Partially publish moving an untagged node to a new parent that tags itself
    Given the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | workspaceName                | "local"                      |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "source"}        |
      | nodeVariantSelectionStrategy | "allSpecializations"         |
      | tag                          | "tag1"                       |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | workspaceName                | "local"                  |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allSpecializations"     |
      | tag                          | "tag1"                   |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | workspaceName                | "local-3"                    |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "source"}        |
      | nodeVariantSelectionStrategy | "allSpecializations"         |
      | tag                          | "tag1"                       |
    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | workspaceName                | "local"                      |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | dimensionSpacePoint          | {"example": "source"}        |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
      | relationDistributionStrategy | "gatherSpecializations"      |
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | workspaceName                | "local-3"                    |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | dimensionSpacePoint          | {"example": "source"}        |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
      | relationDistributionStrategy | "gatherSpecializations"      |
    And I memorise the global graph state
    And the command PublishIndividualNodesFromWorkspace is executed with payload:
      | Key                             | Value                                              |
      | workspaceName                   | "local"                                            |
      | nodesToPublish                  | ["sir-nodeward-nodington-iii", "nody-mc-nodeface"] |
      | contentStreamIdForRemainingPart | "remaining-local-cs-id"                            |
    And I expect the graph state for workspace "local" to be unchanged
    Then I expect the graph state for workspace "live" to be unchanged
    And I expect the graph state for workspace "local-2" to be unchanged
    And I expect the graph state for workspace "local-3" to be unchanged
    And I expect the graph state for workspace "intermediate" to have changed as declared in the snapshot
