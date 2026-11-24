@graphState
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
    Given the current date and time is "2026-02-07T06:00:00+00:00"
    And I am user identified by "initiating-user-identifier"
    And I set up the edge case workspace tree and the following additional commands:
      | shortName                       | payload                                                                                                                                                                                                                                                               |
      | CreateRootNodeAggregateWithNode | {"workspaceName": "live", "originDimensionSpacePoint": {"example": "general"}, "nodeAggregateId": "lady-eleonode-rootford", "nodeTypeName": "Neos.ContentRepository:Root"}                                                                                            |
      | CreateNodeAggregateWithNode     | {"workspaceName": "live", "originDimensionSpacePoint": {"example": "general"}, "nodeAggregateId": "nodingers-cat", "parentNodeAggregateId": "lady-eleonode-rootford", "nodeTypeName": "Neos.ContentRepository.Testing:Document", "nodeName": "parent-document"}       |
      | CreateNodeAggregateWithNode     | {"workspaceName": "live", "originDimensionSpacePoint": {"example": "general"}, "nodeAggregateId": "nodingers-kitten", "parentNodeAggregateId": "nodingers-cat", "nodeTypeName": "Neos.ContentRepository.Testing:Document", "nodeName": "child-document"}              |
      | CreateNodeAggregateWithNode     | {"workspaceName": "live", "originDimensionSpacePoint": {"example": "general"}, "nodeAggregateId": "nodingers-grandkitten", "parentNodeAggregateId": "nodingers-kitten", "nodeTypeName": "Neos.ContentRepository.Testing:Document", "nodeName": "grandchild-document"} |
      | CreateNodeAggregateWithNode     | {"workspaceName": "live", "originDimensionSpacePoint": {"example": "general"}, "nodeAggregateId": "nodingers-other-kitten", "parentNodeAggregateId": "nodingers-cat", "nodeTypeName": "Neos.ContentRepository.Testing:Document", "nodeName": "other-child-document"}  |
      | CreateNodeAggregateWithNode     | {"workspaceName": "live", "originDimensionSpacePoint": {"example": "general"}, "nodeAggregateId": "sir-nodeward-nodington-iii", "parentNodeAggregateId": "lady-eleonode-rootford", "nodeTypeName": "Neos.ContentRepository.Testing:Document", "nodeName": "esquire"}  |
      | CreateNodeAggregateWithNode     | {"workspaceName": "live", "originDimensionSpacePoint": {"example": "general"}, "nodeAggregateId": "nodimus-prime", "parentNodeAggregateId": "sir-nodeward-nodington-iii", "nodeTypeName": "Neos.ContentRepository.Testing:Document", "nodeName": "esquire-child"}     |

  Scenario: Publish removing a node aggregate with strategy allSpecializations
    When the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | workspaceName                | "local"              |
      | nodeAggregateId              | "nodingers-cat"      |
      | coveredDimensionSpacePoint   | {"example":"source"} |
      | nodeVariantSelectionStrategy | "allSpecializations" |
    And the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | workspaceName                | "local-3"            |
      | nodeAggregateId              | "nodingers-cat"      |
      | coveredDimensionSpacePoint   | {"example":"source"} |
      | nodeVariantSelectionStrategy | "allSpecializations" |
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

  Scenario: Partially publish removing a node aggregate with strategy allSpecializations
    When the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | workspaceName                | "local"              |
      | nodeAggregateId              | "nodingers-cat"      |
      | coveredDimensionSpacePoint   | {"example":"source"} |
      | nodeVariantSelectionStrategy | "allSpecializations" |
    And the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | workspaceName                | "local-3"            |
      | nodeAggregateId              | "nodingers-cat"      |
      | coveredDimensionSpacePoint   | {"example":"source"} |
      | nodeVariantSelectionStrategy | "allSpecializations" |
    When the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | workspaceName                | "local"                      |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example":"source"}         |
      | nodeVariantSelectionStrategy | "allSpecializations"         |
    And the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | workspaceName                | "local-3"                    |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example":"source"}         |
      | nodeVariantSelectionStrategy | "allSpecializations"         |
    And I memorise the global graph state
    And the command PublishIndividualNodesFromWorkspace is executed with payload:
      | Key                             | Value                   |
      | workspaceName                   | "local"                 |
      | nodesToPublish                  | ["nodingers-cat"]       |
      | contentStreamIdForRemainingPart | "remaining-local-cs-id" |
    Then I expect the graph state for workspace "local" to be unchanged
    And I expect the graph state for workspace "live" to be unchanged
    And I expect the graph state for workspace "local-2" to be unchanged
    And I expect the graph state for workspace "local-3" to be unchanged
    And I expect the graph state for workspace "intermediate" to have changed as declared in the snapshot

  Scenario: Publish removing a node aggregate with strategy allVariants
    When the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | workspaceName                | "local"              |
      | nodeAggregateId              | "nodingers-cat"      |
      | coveredDimensionSpacePoint   | {"example":"source"} |
      | nodeVariantSelectionStrategy | "allVariants" |
    And the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | workspaceName                | "local-3"            |
      | nodeAggregateId              | "nodingers-cat"      |
      | coveredDimensionSpacePoint   | {"example":"source"} |
      | nodeVariantSelectionStrategy | "allVariants" |
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

  Scenario: Partially publish removing a node aggregate with strategy allVariants
    When the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | workspaceName                | "local"              |
      | nodeAggregateId              | "nodingers-cat"      |
      | coveredDimensionSpacePoint   | {"example":"source"} |
      | nodeVariantSelectionStrategy | "allVariants" |
    And the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | workspaceName                | "local-3"            |
      | nodeAggregateId              | "nodingers-cat"      |
      | coveredDimensionSpacePoint   | {"example":"source"} |
      | nodeVariantSelectionStrategy | "allVariants" |
    When the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | workspaceName                | "local"                      |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example":"source"}         |
      | nodeVariantSelectionStrategy | "allVariants"         |
    And the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | workspaceName                | "local-3"                    |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example":"source"}         |
      | nodeVariantSelectionStrategy | "allVariants"         |
    And I memorise the global graph state
    And the command PublishIndividualNodesFromWorkspace is executed with payload:
      | Key                             | Value                   |
      | workspaceName                   | "local"                 |
      | nodesToPublish                  | ["nodingers-cat"]       |
      | contentStreamIdForRemainingPart | "remaining-local-cs-id" |
    Then I expect the graph state for workspace "local" to be unchanged
    And I expect the graph state for workspace "live" to be unchanged
    And I expect the graph state for workspace "local-2" to be unchanged
    And I expect the graph state for workspace "local-3" to be unchanged
    And I expect the graph state for workspace "intermediate" to have changed as declared in the snapshot
