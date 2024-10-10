@contentrepository
Feature: Sibling positions are properly resolved

  In the general DBAL adapter, hierarchy relations are sorted by an integer field. It defaults to a distance of 128,
  which is reduced each time a node is inserted between two siblings. Once the number becomes uneven, the siblings positions are recalculated.
  These are the test cases for this behavior.

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
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | newContentStreamId   | "cs-identifier"      |
    And I am in workspace "live" and dimension space point {"example": "general"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId             | nodeTypeName                            | parentNodeAggregateId  | nodeName       |
      | sir-nodeward-nodington-iii  | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | esquire        |
      | lady-nodette-nodington-i    | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | nodington-i    |
      | lady-nodette-nodington-x    | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | nodington-x    |
      | lady-nodette-nodington-ix   | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | nodington-ix   |
      | lady-nodette-nodington-viii | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | nodington-viii |
      | lady-nodette-nodington-vii  | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | nodington-vii  |
      | lady-nodette-nodington-vi   | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | nodington-vi   |
      | lady-nodette-nodington-v    | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | nodington-v    |
      | lady-nodette-nodington-iv   | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | nodington-iv   |
      | lady-nodette-nodington-iii  | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | nodington-iii  |
      | lady-nodette-nodington-ii   | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | nodington-ii   |


  Scenario: Trigger position update in DBAL graph
    Given I am in workspace "live" and dimension space point {"example": "general"}
    # distance i to x: 128
    # distance ii to x: 64
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                       |
      | nodeAggregateId                     | "lady-nodette-nodington-ii" |
      | newSucceedingSiblingNodeAggregateId | "lady-nodette-nodington-x"  |
    # distance iii to x: 32
    And the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                        |
      | nodeAggregateId                     | "lady-nodette-nodington-iii" |
      | newSucceedingSiblingNodeAggregateId | "lady-nodette-nodington-x"   |
    # distance iv to x: 16
    And the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                       |
      | nodeAggregateId                     | "lady-nodette-nodington-iv" |
      | newSucceedingSiblingNodeAggregateId | "lady-nodette-nodington-x"  |
    # distance v to x: 8
    And the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                      |
      | nodeAggregateId                     | "lady-nodette-nodington-v" |
      | newSucceedingSiblingNodeAggregateId | "lady-nodette-nodington-x" |
    # distance vi to x: 4
    And the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                       |
      | nodeAggregateId                     | "lady-nodette-nodington-vi" |
      | newSucceedingSiblingNodeAggregateId | "lady-nodette-nodington-x"  |
    # distance vii to x: 2
    And the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                        |
      | nodeAggregateId                     | "lady-nodette-nodington-vii" |
      | newSucceedingSiblingNodeAggregateId | "lady-nodette-nodington-x"   |
    # distance viii to x: 1 -> reorder -> 128
    And the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                         |
      | nodeAggregateId                     | "lady-nodette-nodington-viii" |
      | newSucceedingSiblingNodeAggregateId | "lady-nodette-nodington-x"    |
    # distance ix to x: 64 after reorder
    And the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                       |
      | nodeAggregateId                     | "lady-nodette-nodington-ix" |
      | newSucceedingSiblingNodeAggregateId | "lady-nodette-nodington-x"  |

    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name           | NodeDiscriminator                                                |
      | esquire        | cs-identifier;sir-nodeward-nodington-iii;{"example": "general"}  |
      | nodington-i    | cs-identifier;lady-nodette-nodington-i;{"example": "general"}    |
      | nodington-ii   | cs-identifier;lady-nodette-nodington-ii;{"example": "general"}   |
      | nodington-iii  | cs-identifier;lady-nodette-nodington-iii;{"example": "general"}  |
      | nodington-iv   | cs-identifier;lady-nodette-nodington-iv;{"example": "general"}   |
      | nodington-v    | cs-identifier;lady-nodette-nodington-v;{"example": "general"}    |
      | nodington-vi   | cs-identifier;lady-nodette-nodington-vi;{"example": "general"}   |
      | nodington-vii  | cs-identifier;lady-nodette-nodington-vii;{"example": "general"}  |
      | nodington-viii | cs-identifier;lady-nodette-nodington-viii;{"example": "general"} |
      | nodington-ix   | cs-identifier;lady-nodette-nodington-ix;{"example": "general"}   |
      | nodington-x    | cs-identifier;lady-nodette-nodington-x;{"example": "general"}    |
