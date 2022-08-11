@contentrepository @adapters=DoctrineDBAL,Postgres
Feature: Remove NodeAggregate

  As a user of the CR I want to be able to remove a NodeAggregate or parts of it.

  These are the test cases without dimensions being involved (so no partial removal)

  Background:
    Given I have no content dimensions
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': {}
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
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                     | Value                         |
      | nodeAggregateIdentifier | "lady-eleonode-rootford"      |
      | nodeTypeName            | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateIdentifier | nodeTypeName                            | parentNodeAggregateIdentifier | nodeName |
      | sir-david-nodenborough  | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford        | document |
      | nodingers-cat           | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford        | pet      |
      | nodingers-kitten        | Neos.ContentRepository.Testing:Document | nodingers-cat                 | kitten   |
    And the command SetNodeReferences is executed with payload:
      | Key                           | Value                                  |
      | sourceNodeAggregateIdentifier | "nodingers-cat"                        |
      | referenceName                 | "references"                           |
      | references                    | [{"target": "sir-david-nodenborough"}] |

  Scenario: Remove a node aggregate
    When the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value           |
      | nodeAggregateIdentifier      | "nodingers-cat" |
      | nodeVariantSelectionStrategy | "allVariants"   |
    Then I expect exactly 7 events to be published on stream with prefix "Neos.ContentRepository:ContentStream:cs-identifier"
    And event at index 6 is of type "NodeAggregateWasRemoved" with payload:
      | Key                                  | Expected                     |
      | contentStreamIdentifier              | "cs-identifier"              |
      | nodeAggregateIdentifier              | "nodingers-cat"              |
      | affectedOccupiedDimensionSpacePoints | [[]]                         |
      | affectedCoveredDimensionSpacePoints  | [[]]                         |
      | initiatingUserIdentifier             | "initiating-user-identifier" |
      | removalAttachmentPoint               | null                         |
    When the graph projection is fully up to date
    Then I expect the graph projection to consist of exactly 2 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{} to exist in the content graph
    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                       |
      | document | cs-identifier;sir-david-nodenborough;{} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 1 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | sir-david-nodenborough  |
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no references
    And I expect node aggregate identifier "nodingers-cat" and node path "pet" to lead to no node
    And I expect node aggregate identifier "nodingers-kitten" and node path "pet/kitten" to lead to no node

  Scenario: Disable a node aggregate, remove it, recreate it and expect it to be enabled
    When the command DisableNodeAggregate is executed with payload:
      | Key                          | Value           |
      | nodeAggregateIdentifier      | "nodingers-cat" |
      | nodeVariantSelectionStrategy | "allVariants"   |
    And the graph projection is fully up to date
    And the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value           |
      | nodeAggregateIdentifier      | "nodingers-cat" |
      | nodeVariantSelectionStrategy | "allVariants"   |
    And the graph projection is fully up to date
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                           | Value                                     |
      | nodeAggregateIdentifier       | "nodingers-cat"                           |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                  |
      | nodeName                      | "pet"                                     |
    And the graph projection is fully up to date

    Then I expect the node aggregate "nodingers-cat" to exist
    And I expect this node aggregate to disable dimension space points []
    And I expect the graph projection to consist of exactly 3 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{} to exist in the content graph
    And I expect a node identified by cs-identifier;nodingers-cat;{} to exist in the content graph
    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                       |
      | document | cs-identifier;sir-david-nodenborough;{} |
      | pet      | cs-identifier;nodingers-cat;{}          |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 1 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | sir-david-nodenborough  |
      | 1     | nodingers-cat           |
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "nodingers-cat" and node path "pet" to lead to node cs-identifier;nodingers-cat;{}
    And I expect node aggregate identifier "nodingers-kitten" and node path "pet/kitten" to lead to no node

  Scenario: Remove a node aggregate, recreate it and expect it to have no references
    When the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value           |
      | nodeAggregateIdentifier      | "nodingers-cat" |
      | nodeVariantSelectionStrategy | "allVariants"   |
    And the graph projection is fully up to date
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                           | Value                                     |
      | nodeAggregateIdentifier       | "nodingers-cat"                           |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                  |
      | nodeName                      | "pet"                                     |
    And the graph projection is fully up to date

    Then I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to not be referenced
    And I expect node aggregate identifier "nodingers-cat" and node path "pet" to lead to node cs-identifier;nodingers-cat;{}
    And I expect this node to have no references
    And I expect node aggregate identifier "nodingers-kitten" and node path "pet/kitten" to lead to no node
