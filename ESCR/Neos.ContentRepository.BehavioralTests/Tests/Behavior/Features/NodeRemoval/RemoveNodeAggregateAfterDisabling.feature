@fixtures @adapters=DoctrineDBAL
Feature: Disable a node aggregate

  As a user of the CR I want to disable a node aggregate and expect its descendants to also be disabled.

  These are the test cases without dimensions being involved

  Background:
    Given I have no content dimensions
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:Document':
      properties:
        references:
          type: references
    """
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value                |
      | workspaceName              | "live"               |
      | workspaceTitle             | "Live"               |
      | workspaceDescription       | "The live workspace" |
      | newContentStreamIdentifier | "cs-identifier"      |
    And I am in content stream "cs-identifier" and dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                     | Value                         |
      | nodeAggregateIdentifier | "lady-eleonode-rootford"      |
      | nodeTypeName            | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateIdentifier | nodeTypeName                            | parentNodeAggregateIdentifier | nodeName            |
      | preceding-nodenborough  | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford        | preceding-document  |
      | sir-david-nodenborough  | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford        | document            |
      | succeeding-nodenborough | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford        | succeeding-document |
      | nody-mc-nodeface        | Neos.ContentRepository.Testing:Document | sir-david-nodenborough        | child-document      |
    And the command SetNodeReferences is executed with payload:
      | Key                                 | Value                      |
      | sourceNodeAggregateIdentifier       | "preceding-nodenborough"   |
      | destinationNodeAggregateIdentifiers | ["sir-david-nodenborough"] |
      | referenceName                       | "references"               |
    And the graph projection is fully up to date

  Scenario: Restore a hidden node by removing and recreating it
    Given the command DisableNodeAggregate is executed with payload:
      | Key                          | Value              |
      | nodeAggregateIdentifier      | "nody-mc-nodeface" |
      | nodeVariantSelectionStrategy | "allVariants"      |
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
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;preceding-nodenborough;{} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{} to exist in the content graph
    And I expect a node identified by cs-identifier;succeeding-nodenborough;{} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{} to exist in the content graph

    And I expect the node aggregate "sir-david-nodenborough" to exist
    And I expect this node aggregate to disable dimension space points []

    When I am in content stream "cs-identifier" and dimension space point {}
    And VisibilityConstraints are set to "frontend"
    Then the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | preceding-nodenborough  |
      | 1     | sir-david-nodenborough  |
      | 2     | nody-mc-nodeface        |
      | 1     | succeeding-nodenborough |
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{}
    And the subtree for node aggregate "sir-david-nodenborough" with node types "" and 1 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | sir-david-nodenborough  |
      | 1     | nody-mc-nodeface        |
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{}
