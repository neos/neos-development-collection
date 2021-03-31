@fixtures
Feature: Disable a node aggregate

  As a user of the CR I want to disable a node aggregate and expect its descendants to also be disabled.

  These are the test cases without dimensions being involved

  Background:
    Given I have no content dimensions
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:Document': []
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
      | nodeAggregateIdentifier     | "lady-eleonode-rootford"               |
      | nodeTypeName                | "Neos.ContentRepository:Root"          |
      | coveredDimensionSpacePoints | [{}]                                   |
      | initiatingUserIdentifier    | "00000000-0000-0000-0000-000000000000" |
      | nodeAggregateClassification | "root"                                 |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                     |
      | contentStreamIdentifier       | "cs-identifier"                           |
      | nodeAggregateIdentifier       | "preceding-nodenborough"                  |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {}                                        |
      | coveredDimensionSpacePoints   | [{}]                                      |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                  |
      | nodeName                      | "preceding-document"                      |
      | nodeAggregateClassification   | "regular"                                 |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                     |
      | contentStreamIdentifier       | "cs-identifier"                           |
      | nodeAggregateIdentifier       | "sir-david-nodenborough"                  |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {}                                        |
      | coveredDimensionSpacePoints   | [{}]                                      |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                  |
      | nodeName                      | "document"                                |
      | nodeAggregateClassification   | "regular"                                 |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                     |
      | contentStreamIdentifier       | "cs-identifier"                           |
      | nodeAggregateIdentifier       | "succeeding-nodenborough"                 |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {}                                        |
      | coveredDimensionSpacePoints   | [{}]                                      |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                  |
      | nodeName                      | "succeeding-document"                     |
      | nodeAggregateClassification   | "regular"                                 |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                     |
      | contentStreamIdentifier       | "cs-identifier"                           |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                        |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {}                                        |
      | coveredDimensionSpacePoints   | [{}]                                      |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                  |
      | nodeName                      | "child-document"                          |
      | nodeAggregateClassification   | "regular"                                 |
    And the event NodeReferencesWereSet was published with payload:
      | Key                                 | Value                      |
      | contentStreamIdentifier             | "cs-identifier"            |
      | sourceNodeAggregateIdentifier       | "preceding-nodenborough"   |
      | sourceOriginDimensionSpacePoint     | {}                         |
      | destinationNodeAggregateIdentifiers | ["sir-david-nodenborough"] |
      | referenceName                       | "references"               |
    And the graph projection is fully up to date

  Scenario: Disable node with arbitrary strategy since dimensions are not involved
    When the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | contentStreamIdentifier      | "cs-identifier"          |
      | nodeAggregateIdentifier      | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {}                       |
      | nodeVariantSelectionStrategy | "allVariants"            |
      | initiatingUserIdentifier     | "initiating-user-identifier" |

    Then I expect exactly 8 events to be published on stream with prefix "Neos.ContentRepository:ContentStream:cs-identifier"
    And event at index 7 is of type "Neos.EventSourcedContentRepository:NodeAggregateWasDisabled" with payload:
      | Key                          | Expected                 |
      | contentStreamIdentifier      | "cs-identifier"          |
      | nodeAggregateIdentifier      | "sir-david-nodenborough" |
      | affectedDimensionSpacePoints | [[]]                     |
      | initiatingUserIdentifier     | "initiating-user-identifier" |

    When the graph projection is fully up to date
    And I am in content stream "cs-identifier"
    Then I expect the graph projection to consist of exactly 5 nodes
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"preceding-nodenborough", "originDimensionSpacePoint": {}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"succeeding-nodenborough", "originDimensionSpacePoint": {}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nody-mc-nodeface", "originDimensionSpacePoint": {}} to exist in the content graph

    And I expect the node aggregate "sir-david-nodenborough" to exist
    And I expect this node aggregate to disable dimension space points [{}]

    When I am in content stream "cs-identifier" and Dimension Space Point {}
    And VisibilityConstraints are set to "withoutRestrictions"
    Then I expect the node aggregate "lady-eleonode-rootford" to have the following child nodes:
      | Name                | NodeDiscriminator                                                                                                                 |
      | preceding-document  | {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"preceding-nodenborough", "originDimensionSpacePoint": {}}  |
      | document            | {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {}}  |
      | succeeding-document | {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"succeeding-nodenborough", "originDimensionSpacePoint": {}} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | preceding-nodenborough  |
      | 1     | sir-david-nodenborough  |
      | 2     | nody-mc-nodeface        |
      | 1     | succeeding-nodenborough |
    And I expect node aggregate identifier "preceding-nodenborough" and path "preceding-document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"preceding-nodenborough", "originDimensionSpacePoint": {}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect this node to have the preceding siblings []
    And I expect this node to have the succeeding siblings ["sir-david-nodenborough", "succeeding-nodenborough"]
    And I expect the node aggregate "preceding-nodenborough" to have the references:
      | Key        | Value                      |
      | references | ["sir-david-nodenborough"] |
    And I expect node aggregate identifier "sir-david-nodenborough" and path "document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect this node to have the preceding siblings ["preceding-nodenborough"]
    And I expect this node to have the succeeding siblings ["succeeding-nodenborough"]
    And I expect the node aggregate "sir-david-nodenborough" to be referenced by:
      | Key        | Value                      |
      | references | ["preceding-nodenborough"] |
    And I expect node aggregate identifier "succeeding-nodenborough" and path "succeeding-document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"succeeding-nodenborough", "originDimensionSpacePoint": {}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect this node to have the preceding siblings ["sir-david-nodenborough", "preceding-nodenborough"]
    And I expect this node to have the succeeding siblings []
    And I expect node aggregate identifier "nody-mc-nodeface" and path "document/child-document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nody-mc-nodeface", "originDimensionSpacePoint": {}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {}}

    When VisibilityConstraints are set to "frontend"
    Then I expect the node aggregate "lady-eleonode-rootford" to have the following child nodes:
      | Name                | NodeDiscriminator                                                                                                                 |
      | preceding-document  | {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"preceding-nodenborough", "originDimensionSpacePoint": {}}  |
      | succeeding-document | {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"succeeding-nodenborough", "originDimensionSpacePoint": {}} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | preceding-nodenborough  |
      | 1     | succeeding-nodenborough |
    And I expect node aggregate identifier "preceding-nodenborough" and path "preceding-document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"preceding-nodenborough", "originDimensionSpacePoint": {}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect this node to have the preceding siblings []
    And I expect this node to have the succeeding siblings ["succeeding-nodenborough"]
    And I expect the node aggregate "preceding-nodenborough" to have the references:
      | Key        | Value |
      | references | []    |
    And I expect node aggregate identifier "sir-david-nodenborough" and path "document" to lead to no node
    And I expect node aggregate identifier "succeeding-nodenborough" and path "succeeding-document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"succeeding-nodenborough", "originDimensionSpacePoint": {}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect this node to have the preceding siblings ["preceding-nodenborough"]
    And I expect node aggregate identifier "nody-mc-nodeface" and path "document/child-document" to lead to no node

  Scenario: Restore a hidden node by removing and recreating it
    Given the event NodeAggregateWasDisabled was published with payload:
      | Key                          | Value              |
      | contentStreamIdentifier      | "cs-identifier"    |
      | nodeAggregateIdentifier      | "nody-mc-nodeface" |
      | affectedDimensionSpacePoints | [{}]               |
    And the event NodeAggregateWasRemoved was published with payload:
      | Key                                  | Value              |
      | contentStreamIdentifier              | "cs-identifier"    |
      | nodeAggregateIdentifier              | "nody-mc-nodeface" |
      | affectedOccupiedDimensionSpacePoints | [{}]               |
      | affectedCoveredDimensionSpacePoints  | [{}]               |
    And the graph projection is fully up to date

    When the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                           | Value                                     |
      | contentStreamIdentifier       | "cs-identifier"                           |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                        |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {}                                        |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                  |
      | nodeName                      | "child-document"                          |

    When the graph projection is fully up to date
    And I am in content stream "cs-identifier"
    Then I expect the graph projection to consist of exactly 5 nodes
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"preceding-nodenborough", "originDimensionSpacePoint": {}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"succeeding-nodenborough", "originDimensionSpacePoint": {}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nody-mc-nodeface", "originDimensionSpacePoint": {}} to exist in the content graph

    And I expect the node aggregate "sir-david-nodenborough" to exist
    And I expect this node aggregate to disable dimension space points []

    When I am in content stream "cs-identifier" and Dimension Space Point {}
    And VisibilityConstraints are set to "frontend"
    Then the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | preceding-nodenborough  |
      | 1     | sir-david-nodenborough  |
      | 2     | nody-mc-nodeface        |
      | 1     | succeeding-nodenborough |
    And I expect node aggregate identifier "sir-david-nodenborough" and path "document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {}}
    And the subtree for node aggregate "sir-david-nodenborough" with node types "" and 1 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | sir-david-nodenborough  |
      | 1     | nody-mc-nodeface        |
    And I expect node aggregate identifier "nody-mc-nodeface" and path "document/child-document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nody-mc-nodeface", "originDimensionSpacePoint": {}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {}}
