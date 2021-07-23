@fixtures
Feature: Enable a node aggregate

  As a user of the CR I want to enable a node aggregate and expect its descendants to also be enabled unless otherwise disabled.

  These are the test cases without dimensions being involved

  Background:
    Given I have no content dimensions
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:Document': []
    """
    And the event RootWorkspaceWasCreated was published with payload:
      | Key                        | Value                                  |
      | workspaceName              | "live"                                 |
      | workspaceTitle             | "Live"                                 |
      | workspaceDescription       | "The live workspace"                   |
      | initiatingUserIdentifier   | "00000000-0000-0000-0000-000000000000" |
      | newContentStreamIdentifier | "cs-identifier"                        |
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

  Scenario: Enable a previously disabled node with arbitrary strategy since dimensions are not involved
    Given the event NodeAggregateWasDisabled was published with payload:
      | Key                          | Value                    |
      | contentStreamIdentifier      | "cs-identifier"          |
      | nodeAggregateIdentifier      | "sir-david-nodenborough" |
      | affectedDimensionSpacePoints | [{}]                     |
    And the graph projection is fully up to date

    When the command EnableNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | contentStreamIdentifier      | "cs-identifier"          |
      | nodeAggregateIdentifier      | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {}                       |
      | nodeVariantSelectionStrategy | "allVariants"            |

    Then I expect exactly 9 events to be published on stream with prefix "Neos.ContentRepository:ContentStream:cs-identifier"
    And event at index 8 is of type "Neos.EventSourcedContentRepository:NodeAggregateWasEnabled" with payload:
      | Key                          | Expected                 |
      | contentStreamIdentifier      | "cs-identifier"          |
      | nodeAggregateIdentifier      | "sir-david-nodenborough" |
      | affectedDimensionSpacePoints | [[]]                     |

    When the graph projection is fully up to date
    And I am in content stream "cs-identifier"
    Then I expect the graph projection to consist of exactly 5 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;preceding-nodenborough;{} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{} to exist in the content graph
    And I expect a node identified by cs-identifier;succeeding-nodenborough;{} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{} to exist in the content graph

    And I expect the node aggregate "sir-david-nodenborough" to exist
    And I expect this node aggregate to disable dimension space points []

    When I am in content stream "cs-identifier" and dimension space point {}
    And VisibilityConstraints are set to "frontend"
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name                | NodeDiscriminator                        |
      | preceding-document  | cs-identifier;preceding-nodenborough;{}  |
      | document            | cs-identifier;sir-david-nodenborough;{}  |
      | succeeding-document | cs-identifier;succeeding-nodenborough;{} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | preceding-nodenborough  |
      | 1     | sir-david-nodenborough  |
      | 2     | nody-mc-nodeface        |
      | 1     | succeeding-nodenborough |
    And I expect node aggregate identifier "preceding-nodenborough" and node path "preceding-document" to lead to node cs-identifier;preceding-nodenborough;{}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                        |
      | cs-identifier;sir-david-nodenborough;{}  |
      | cs-identifier;succeeding-nodenborough;{} |
    And I expect this node to have the following references:
      | Key        | Value                                       |
      | references | ["cs-identifier;sir-david-nodenborough;{}"] |
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                       |
      | cs-identifier;preceding-nodenborough;{} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                        |
      | cs-identifier;succeeding-nodenborough;{} |
    And I expect this node to be referenced by:
      | Key        | Value                      |
      | references | ["cs-identifier;preceding-nodenborough;{}"] |
    And I expect node aggregate identifier "succeeding-nodenborough" and node path "succeeding-document" to lead to node cs-identifier;succeeding-nodenborough;{}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                       |
      | cs-identifier;sir-david-nodenborough;{} |
      | cs-identifier;preceding-nodenborough;{} |
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{}

  Scenario: Enable a previously disabled node with explicitly disabled child nodes with arbitrary strategy since dimensions are not involved
    Given the event NodeAggregateWasDisabled was published with payload:
      | Key                          | Value                    |
      | contentStreamIdentifier      | "cs-identifier"          |
      | nodeAggregateIdentifier      | "sir-david-nodenborough" |
      | affectedDimensionSpacePoints | [{}]                     |
    And the event NodeAggregateWasDisabled was published with payload:
      | Key                          | Value              |
      | contentStreamIdentifier      | "cs-identifier"    |
      | nodeAggregateIdentifier      | "nody-mc-nodeface" |
      | affectedDimensionSpacePoints | [{}]               |
    And the graph projection is fully up to date

    When the command EnableNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | contentStreamIdentifier      | "cs-identifier"          |
      | nodeAggregateIdentifier      | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {}                       |
      | nodeVariantSelectionStrategy | "allVariants"            |
    Then I expect exactly 10 events to be published on stream with prefix "Neos.ContentRepository:ContentStream:cs-identifier"
    And event at index 9 is of type "Neos.EventSourcedContentRepository:NodeAggregateWasEnabled" with payload:
      | Key                          | Expected                 |
      | contentStreamIdentifier      | "cs-identifier"          |
      | nodeAggregateIdentifier      | "sir-david-nodenborough" |
      | affectedDimensionSpacePoints | [[]]                     |

    When the graph projection is fully up to date
    And I am in content stream "cs-identifier"

    Then I expect the node aggregate "sir-david-nodenborough" to exist
    And I expect this node aggregate to disable dimension space points []
    And I expect the node aggregate "nody-mc-nodeface" to exist
    And I expect this node aggregate to disable dimension space points [{}]

    When I am in content stream "cs-identifier" and dimension space point {}
    And VisibilityConstraints are set to "frontend"
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name                | NodeDiscriminator                        |
      | preceding-document  | cs-identifier;preceding-nodenborough;{}  |
      | document            | cs-identifier;sir-david-nodenborough;{}  |
      | succeeding-document | cs-identifier;succeeding-nodenborough;{} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | preceding-nodenborough  |
      | 1     | sir-david-nodenborough  |
      | 1     | succeeding-nodenborough |
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{}
    And I expect this node to have no child nodes
    And I expect node aggregate identifier "preceding-nodenborough" and node path "preceding-document" to lead to node cs-identifier;preceding-nodenborough;{}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                        |
      | cs-identifier;sir-david-nodenborough;{}  |
      | cs-identifier;succeeding-nodenborough;{} |
    And I expect this node to have the following references:
      | Key        | Value                      |
      | references | ["cs-identifier;sir-david-nodenborough;{}"] |
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                       |
      | cs-identifier;preceding-nodenborough;{} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                        |
      | cs-identifier;succeeding-nodenborough;{} |
    And I expect this node to be referenced by:
      | Key        | Value                      |
      | references | ["cs-identifier;preceding-nodenborough;{}"] |
    And I expect node aggregate identifier "succeeding-nodenborough" and node path "succeeding-document" to lead to node cs-identifier;succeeding-nodenborough;{}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                       |
      | cs-identifier;sir-david-nodenborough;{} |
      | cs-identifier;preceding-nodenborough;{} |
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to no node

  Scenario: Enable a previously disabled node with explicitly disabled parent node with arbitrary strategy since dimensions are not involved
    Given the event NodeAggregateWasDisabled was published with payload:
      | Key                          | Value                    |
      | contentStreamIdentifier      | "cs-identifier"          |
      | nodeAggregateIdentifier      | "sir-david-nodenborough" |
      | affectedDimensionSpacePoints | [{}]                     |
      | affectedDimensionSpacePoints | [{}]                     |
    And the event NodeAggregateWasDisabled was published with payload:
      | Key                          | Value              |
      | contentStreamIdentifier      | "cs-identifier"    |
      | nodeAggregateIdentifier      | "nody-mc-nodeface" |
      | affectedDimensionSpacePoints | [{}]               |
    And the graph projection is fully up to date

    When the command EnableNodeAggregate is executed with payload:
      | Key                          | Value              |
      | contentStreamIdentifier      | "cs-identifier"    |
      | nodeAggregateIdentifier      | "nody-mc-nodeface" |
      | coveredDimensionSpacePoint   | {}                 |
      | nodeVariantSelectionStrategy | "allVariants"      |
    Then I expect exactly 10 events to be published on stream with prefix "Neos.ContentRepository:ContentStream:cs-identifier"
    And event at index 9 is of type "Neos.EventSourcedContentRepository:NodeAggregateWasEnabled" with payload:
      | Key                          | Expected           |
      | contentStreamIdentifier      | "cs-identifier"    |
      | nodeAggregateIdentifier      | "nody-mc-nodeface" |
      | affectedDimensionSpacePoints | [[]]               |

    When the graph projection is fully up to date
    And I am in content stream "cs-identifier"

    Then I expect the node aggregate "sir-david-nodenborough" to exist
    And I expect this node aggregate to disable dimension space points [{}]
    And I expect the node aggregate "nody-mc-nodeface" to exist
    And I expect this node aggregate to disable dimension space points []

    When I am in content stream "cs-identifier" and dimension space point {}
    And VisibilityConstraints are set to "frontend"
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name                | NodeDiscriminator                        |
      | preceding-document  | cs-identifier;preceding-nodenborough;{}  |
      | succeeding-document | cs-identifier;succeeding-nodenborough;{} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | preceding-nodenborough  |
      | 1     | succeeding-nodenborough |
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to no node
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to no node
