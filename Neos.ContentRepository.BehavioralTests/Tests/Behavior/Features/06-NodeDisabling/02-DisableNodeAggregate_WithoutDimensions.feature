@contentrepository @adapters=DoctrineDBAL,Postgres
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
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | workspaceTitle       | "Live"               |
      | workspaceDescription | "The live workspace" |
      | newContentStreamId   | "cs-identifier"      |
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId         | nodeTypeName                            | parentNodeAggregateId  | nodeName            |
      | preceding-nodenborough  | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | preceding-document  |
      | sir-david-nodenborough  | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | document            |
      | succeeding-nodenborough | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | succeeding-document |
      | nody-mc-nodeface        | Neos.ContentRepository.Testing:Document | sir-david-nodenborough | child-document      |
    And the command SetNodeReferences is executed with payload:
      | Key                   | Value                                  |
      | sourceNodeAggregateId | "preceding-nodenborough"               |
      | referenceName         | "references"                           |
      | references            | [{"target": "sir-david-nodenborough"}] |
    And the graph projection is fully up to date

  Scenario: Disable node with arbitrary strategy since dimensions are not involved
    When the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | nodeVariantSelectionStrategy | "allVariants"            |

    Then I expect exactly 8 events to be published on stream with prefix "ContentStream:cs-identifier"
    And event at index 7 is of type "NodeAggregateWasDisabled" with payload:
      | Key                          | Expected                 |
      | contentStreamId              | "cs-identifier"          |
      | nodeAggregateId              | "sir-david-nodenborough" |
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
    And I expect this node aggregate to disable dimension space points [{}]

    When I am in content stream "cs-identifier" and dimension space point {}
    And VisibilityConstraints are set to "withoutRestrictions"
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name                | NodeDiscriminator                        |
      | preceding-document  | cs-identifier;preceding-nodenborough;{}  |
      | document            | cs-identifier;sir-david-nodenborough;{}  |
      | succeeding-document | cs-identifier;succeeding-nodenborough;{} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | nodeAggregateId         |
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
      | Name       | Node                                    | Properties |
      | references | cs-identifier;sir-david-nodenborough;{} | null       |
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                       |
      | cs-identifier;preceding-nodenborough;{} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                        |
      | cs-identifier;succeeding-nodenborough;{} |
    And I expect this node to be referenced by:
      | Name       | Node                                    | Properties |
      | references | cs-identifier;preceding-nodenborough;{} | null       |
    And I expect node aggregate identifier "succeeding-nodenborough" and node path "succeeding-document" to lead to node cs-identifier;succeeding-nodenborough;{}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                       |
      | cs-identifier;sir-david-nodenborough;{} |
      | cs-identifier;preceding-nodenborough;{} |
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{}

    When VisibilityConstraints are set to "frontend"
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name                | NodeDiscriminator                        |
      | preceding-document  | cs-identifier;preceding-nodenborough;{}  |
      | succeeding-document | cs-identifier;succeeding-nodenborough;{} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | nodeAggregateId         |
      | 0     | lady-eleonode-rootford  |
      | 1     | preceding-nodenborough  |
      | 1     | succeeding-nodenborough |
    And I expect node aggregate identifier "preceding-nodenborough" and node path "preceding-document" to lead to node cs-identifier;preceding-nodenborough;{}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                        |
      | cs-identifier;succeeding-nodenborough;{} |
    And I expect this node to have no references
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to no node
    And I expect node aggregate identifier "succeeding-nodenborough" and node path "succeeding-document" to lead to node cs-identifier;succeeding-nodenborough;{}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                       |
      | cs-identifier;preceding-nodenborough;{} |
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to no node
