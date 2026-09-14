Feature: Sibling positions are properly resolved

  In the general DBAL adapter, hierarchy relations are sorted by a materialised sort path: one base 62 fractional
  index key per tree level, joined with "/" (e.g. a0/a0/b3/ZzV). Inserting between two siblings generates a key
  strictly between their two keys, so there is no fixed distance to run out of - the key simply grows, by roughly
  0.17 characters per insert into the same gap. Only once a key passes NodeSortPath::MAX_KEY_LENGTH is the whole
  sibling set rebalanced. These are the test cases for this behavior.

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
    # Every move below inserts into the same gap, directly before nodington-x. With the integer scheme the
    # distance halved each time (128, 64, 32 ... 1) and the siblings had to be renumbered at the end; with
    # fractional keys the key simply grows instead, and no renumbering happens at this scale.
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                       |
      | nodeAggregateId                     | "lady-nodette-nodington-ii" |
      | newSucceedingSiblingNodeAggregateId | "lady-nodette-nodington-x"  |
    And the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                        |
      | nodeAggregateId                     | "lady-nodette-nodington-iii" |
      | newSucceedingSiblingNodeAggregateId | "lady-nodette-nodington-x"   |
    And the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                       |
      | nodeAggregateId                     | "lady-nodette-nodington-iv" |
      | newSucceedingSiblingNodeAggregateId | "lady-nodette-nodington-x"  |
    And the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                      |
      | nodeAggregateId                     | "lady-nodette-nodington-v" |
      | newSucceedingSiblingNodeAggregateId | "lady-nodette-nodington-x" |
    And the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                       |
      | nodeAggregateId                     | "lady-nodette-nodington-vi" |
      | newSucceedingSiblingNodeAggregateId | "lady-nodette-nodington-x"  |
    And the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                        |
      | nodeAggregateId                     | "lady-nodette-nodington-vii" |
      | newSucceedingSiblingNodeAggregateId | "lady-nodette-nodington-x"   |
    # with the integer scheme this was the point the siblings had to be renumbered; fractional keys just grow
    And the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                         |
      | nodeAggregateId                     | "lady-nodette-nodington-viii" |
      | newSucceedingSiblingNodeAggregateId | "lady-nodette-nodington-x"    |
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
