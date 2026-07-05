Feature: Tag and untag nodes after moving their children in or out

  As a user of the CR I want to
  - move child nodes were out
  - move nodes in as new children
  using the
  - scatter
  - gatherSpecializations
  - gatherAll
  strategy and then tag the parent in
  - allSpecializations
  - allVariants
  Idea for testing TAGGING ("... then tag the parent ..."):
  - lady-eleonode-rootford
  -- sir-david-nodenborough  <-- (2) Tag
  --- nody-mc-nodeface       <-- (1) Move Source
  ---- nodimus-mediocre
  -- sir-nodeward-nodington-iii
  --- nodimus-prime
  --- .....................  <-- (1) Move Target

  Idea for testing UNTAGGING ("... then untag the parent ..."):
  - lady-eleonode-rootford
  -- sir-david-nodenborough  <-- (1) Tag         (3) untag
  --- nody-mc-nodeface       <-- (1) Move Source
  ---- nodimus-mediocre
  -- sir-nodeward-nodington-iii <-- (1) Tag
  --- nodimus-prime
  --- .....................  <-- (1) Move Target

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
      | CreateNodeAggregateWithNode     | {"workspaceName": "live", "originDimensionSpacePoint": {"example": "general"}, "nodeAggregateId": "nody-o-nodeface", "parentNodeAggregateId": "sir-david-nodenborough", "nodeTypeName": "Neos.ContentRepository.Testing:Document", "nodeName": "other-document"}         |

  Scenario: Publish moving a child node out via scatter strategy, and tagging the parent and all its specializations
    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | workspaceName                | "local"                      |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "scatter"                    |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | workspaceName                | "local-3"                    |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "scatter"                    |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | workspaceName                | "local"                  |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allSpecializations"     |
      | tag                          | "my-tag"                 |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | workspaceName                | "local-3"                |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allSpecializations"     |
      | tag                          | "my-tag"                 |
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

  Scenario: Partially publish moving a child node out via scatter strategy and tagging the parent and all its specializations
    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | workspaceName                | "local"                      |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "scatter"                    |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | workspaceName                | "local-3"                    |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "scatter"                    |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | workspaceName                | "local"                      |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-o-nodeface"            |
      | relationDistributionStrategy | "scatter"                    |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | workspaceName                | "local"                  |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allSpecializations"     |
      | tag                          | "my-tag"                 |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | workspaceName                | "local-3"                |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allSpecializations"     |
      | tag                          | "my-tag"                 |
    And I memorise the global graph state
    And the command PublishIndividualNodesFromWorkspace is executed with payload:
      | Key                             | Value                                          |
      | workspaceName                   | "local"                                        |
      | nodesToPublish                  | ["sir-david-nodenborough", "nody-mc-nodeface"] |
      | contentStreamIdForRemainingPart | "remaining-local-cs-id"                        |
    And I expect the graph state for workspace "local" to be unchanged
    Then I expect the graph state for workspace "live" to be unchanged
    And I expect the graph state for workspace "local-2" to be unchanged
    And I expect the graph state for workspace "local-3" to be unchanged
    And I expect the graph state for workspace "intermediate" to have changed as declared in the snapshot

  Scenario: Publish moving a child node out via scatter strategy and tagging the parent and all its variants
    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | workspaceName                | "local"                      |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "scatter"                    |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | workspaceName                | "local-3"                    |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "scatter"                    |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | workspaceName                | "local"                  |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allVariants"            |
      | tag                          | "my-tag"                 |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | workspaceName                | "local-3"                |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allVariants"            |
      | tag                          | "my-tag"                 |
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

  Scenario: Partially publish moving a child node out via scatter strategy and tagging the parent and all its variants
    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | workspaceName                | "local"                      |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "scatter"                    |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | workspaceName                | "local-3"                    |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "scatter"                    |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | workspaceName                | "local"                      |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-o-nodeface"            |
      | relationDistributionStrategy | "scatter"                    |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | workspaceName                | "local"                  |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allVariants"            |
      | tag                          | "my-tag"                 |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | workspaceName                | "local-3"                |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allVariants"            |
      | tag                          | "my-tag"                 |
    And I memorise the global graph state
    And the command PublishIndividualNodesFromWorkspace is executed with payload:
      | Key                             | Value                                          |
      | workspaceName                   | "local"                                        |
      | nodesToPublish                  | ["sir-david-nodenborough", "nody-mc-nodeface"] |
      | contentStreamIdForRemainingPart | "remaining-local-cs-id"                        |
    And I expect the graph state for workspace "local" to be unchanged
    Then I expect the graph state for workspace "live" to be unchanged
    And I expect the graph state for workspace "local-2" to be unchanged
    And I expect the graph state for workspace "local-3" to be unchanged
    And I expect the graph state for workspace "intermediate" to have changed as declared in the snapshot

  Scenario: Publish moving a child node out via gatherSpecializations strategy, and tagging the parent and all its specializations
    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | workspaceName                | "local"                      |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "gatherSpecializations"                    |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | workspaceName                | "local-3"                    |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "gatherSpecializations"                    |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | workspaceName                | "local"                  |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allSpecializations"     |
      | tag                          | "my-tag"                 |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | workspaceName                | "local-3"                |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allSpecializations"     |
      | tag                          | "my-tag"                 |
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

  Scenario: Partially publish moving a child node out via gatherSpecializations strategy and tagging the parent and all its specializations
    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | workspaceName                | "local"                      |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "gatherSpecializations"                    |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | workspaceName                | "local-3"                    |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "gatherSpecializations"                    |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | workspaceName                | "local"                      |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-o-nodeface"            |
      | relationDistributionStrategy | "gatherSpecializations"                    |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | workspaceName                | "local"                  |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allSpecializations"     |
      | tag                          | "my-tag"                 |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | workspaceName                | "local-3"                |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allSpecializations"     |
      | tag                          | "my-tag"                 |
    And I memorise the global graph state
    And the command PublishIndividualNodesFromWorkspace is executed with payload:
      | Key                             | Value                                          |
      | workspaceName                   | "local"                                        |
      | nodesToPublish                  | ["sir-david-nodenborough", "nody-mc-nodeface"] |
      | contentStreamIdForRemainingPart | "remaining-local-cs-id"                        |
    And I expect the graph state for workspace "local" to be unchanged
    Then I expect the graph state for workspace "live" to be unchanged
    And I expect the graph state for workspace "local-2" to be unchanged
    And I expect the graph state for workspace "local-3" to be unchanged
    And I expect the graph state for workspace "intermediate" to have changed as declared in the snapshot

  Scenario: Publish moving a child node out via gatherSpecializations strategy and tagging the parent and all its variants
    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | workspaceName                | "local"                      |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "gatherSpecializations"                    |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | workspaceName                | "local-3"                    |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "gatherSpecializations"                    |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | workspaceName                | "local"                  |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allVariants"            |
      | tag                          | "my-tag"                 |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | workspaceName                | "local-3"                |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allVariants"            |
      | tag                          | "my-tag"                 |
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

  Scenario: Partially publish moving a child node out via gatherSpecializations strategy and tagging the parent and all its variants
    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | workspaceName                | "local"                      |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "gatherSpecializations"                    |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | workspaceName                | "local-3"                    |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "gatherSpecializations"                    |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | workspaceName                | "local"                      |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-o-nodeface"            |
      | relationDistributionStrategy | "gatherSpecializations"                    |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | workspaceName                | "local"                  |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allVariants"            |
      | tag                          | "my-tag"                 |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | workspaceName                | "local-3"                |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allVariants"            |
      | tag                          | "my-tag"                 |
    And I memorise the global graph state
    And the command PublishIndividualNodesFromWorkspace is executed with payload:
      | Key                             | Value                                          |
      | workspaceName                   | "local"                                        |
      | nodesToPublish                  | ["sir-david-nodenborough", "nody-mc-nodeface"] |
      | contentStreamIdForRemainingPart | "remaining-local-cs-id"                        |
    And I expect the graph state for workspace "local" to be unchanged
    Then I expect the graph state for workspace "live" to be unchanged
    And I expect the graph state for workspace "local-2" to be unchanged
    And I expect the graph state for workspace "local-3" to be unchanged
    And I expect the graph state for workspace "intermediate" to have changed as declared in the snapshot

  Scenario: Publish moving a child node out via gatherAll strategy, and tagging the parent and all its specializations
    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | workspaceName                | "local"                      |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "gatherAll"                    |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | workspaceName                | "local-3"                    |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "gatherAll"                    |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | workspaceName                | "local"                  |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allSpecializations"     |
      | tag                          | "my-tag"                 |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | workspaceName                | "local-3"                |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allSpecializations"     |
      | tag                          | "my-tag"                 |
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

  Scenario: Partially publish moving a child node out via gatherAll strategy and tagging the parent and all its specializations
    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | workspaceName                | "local"                      |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "gatherAll"                    |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | workspaceName                | "local-3"                    |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "gatherAll"                    |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | workspaceName                | "local"                      |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-o-nodeface"            |
      | relationDistributionStrategy | "gatherAll"                    |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | workspaceName                | "local"                  |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allSpecializations"     |
      | tag                          | "my-tag"                 |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | workspaceName                | "local-3"                |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allSpecializations"     |
      | tag                          | "my-tag"                 |
    And I memorise the global graph state
    And the command PublishIndividualNodesFromWorkspace is executed with payload:
      | Key                             | Value                                          |
      | workspaceName                   | "local"                                        |
      | nodesToPublish                  | ["sir-david-nodenborough", "nody-mc-nodeface"] |
      | contentStreamIdForRemainingPart | "remaining-local-cs-id"                        |
    And I expect the graph state for workspace "local" to be unchanged
    Then I expect the graph state for workspace "live" to be unchanged
    And I expect the graph state for workspace "local-2" to be unchanged
    And I expect the graph state for workspace "local-3" to be unchanged
    And I expect the graph state for workspace "intermediate" to have changed as declared in the snapshot

  Scenario: Publish moving a child node out via gatherAll strategy and tagging the parent and all its variants
    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | workspaceName                | "local"                      |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "gatherAll"                    |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | workspaceName                | "local-3"                    |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "gatherAll"                    |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | workspaceName                | "local"                  |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allVariants"            |
      | tag                          | "my-tag"                 |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | workspaceName                | "local-3"                |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allVariants"            |
      | tag                          | "my-tag"                 |
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

  Scenario: Partially publish moving a child node out via gatherAll strategy and tagging the parent and all its variants
    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | workspaceName                | "local"                      |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "gatherAll"                    |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | workspaceName                | "local-3"                    |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "gatherAll"                    |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | workspaceName                | "local"                      |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-o-nodeface"            |
      | relationDistributionStrategy | "gatherAll"                    |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | workspaceName                | "local"                  |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allVariants"            |
      | tag                          | "my-tag"                 |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | workspaceName                | "local-3"                |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allVariants"            |
      | tag                          | "my-tag"                 |
    And I memorise the global graph state
    And the command PublishIndividualNodesFromWorkspace is executed with payload:
      | Key                             | Value                                          |
      | workspaceName                   | "local"                                        |
      | nodesToPublish                  | ["sir-david-nodenborough", "nody-mc-nodeface"] |
      | contentStreamIdForRemainingPart | "remaining-local-cs-id"                        |
    And I expect the graph state for workspace "local" to be unchanged
    Then I expect the graph state for workspace "live" to be unchanged
    And I expect the graph state for workspace "local-2" to be unchanged
    And I expect the graph state for workspace "local-3" to be unchanged
    And I expect the graph state for workspace "intermediate" to have changed as declared in the snapshot
