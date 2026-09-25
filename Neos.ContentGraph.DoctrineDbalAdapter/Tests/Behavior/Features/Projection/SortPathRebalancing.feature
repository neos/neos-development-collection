Feature: When sortPath segments get to long a rebalancing is triggered

  In the general DBAL adapter, hierarchy relations are sorted by a materialised sort path: one base 62 fractional
  index key per tree level, joined with "/" (e.g. a0/a0/b3/ZzV). Inserting between two siblings generates a key
  strictly between their two keys, so there is no fixed distance to run out of - the key simply grows, by roughly
  0.2 characters per insert into the same gap. Only once a key passes NodeSortPath::MAX_KEY_LENGTH is the whole
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
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
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

  Scenario: Rebalancing keeps the descendants below their own parents
      # vi (a0/a6) and iv (a0/a8) get a child each; vi's child gets the key a5, iv's child the key a0
    Given the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId  | nodeTypeName                            | parentNodeAggregateId     | nodeName |
      | nody-mc-vi-child | Neos.ContentRepository.Testing:Document | lady-nodette-nodington-vi | vi-child |
      | nody-mc-iv-child | Neos.ContentRepository.Testing:Document | lady-nodette-nodington-iv | iv-child |
    And I set the following sortPath:
      | Key                  | Value                  |
      | contentStreamId      | "cs-identifier"        |
      | dimensionSpacePoint  | {"example": "general"} |
      | childNodeAggregateId | "nody-mc-vi-child"     |
      | newSortPath          | "a0/a6/a5"             |
      # Tiny gap between i (a0/a1) and x: the first insert gets a10000000000V (length 13) -> rebalancing right away
    And I set the following sortPath:
      | Key                  | Value                      |
      | contentStreamId      | "cs-identifier"            |
      | dimensionSpacePoint  | {"example": "general"}     |
      | childNodeAggregateId | "lady-nodette-nodington-x" |
      | newSortPath          | "a0/a10000000001"          |
    And the command CreateWorkspace is executed with payload:
      | Key                | Value     |
      | workspaceName      | "user"    |
      | baseWorkspaceName  | "live"    |
      | newContentStreamId | "user-cs" |
    And I am in workspace "user" and dimension space point {"example": "general"}

      # Rebalancing: esquire a0, i a1, [reserved a2 -> ii], x a3, ix a4, viii a5, vii a6, vi a6 -> a7, v a7 -> a8, iv a8 -> a9, iii a9 -> aA, ii aA -> aB
      # All siblings move up, so they must be processed last to first. Otherwise vi's child is carried along to a0/aB/a5, behind iv's child (a0/aB/a0)
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                       |
      | nodeAggregateId                     | "lady-nodette-nodington-ii" |
      | newSucceedingSiblingNodeAggregateId | "lady-nodette-nodington-x"  |

      # findDescendantNodes orders by level and sortpath, so the level 2 nodes reveal whether their paths still start with their parents' paths
    Then I execute the findDescendantNodes query for entry node aggregate id "lady-eleonode-rootford" I expect the nodes "sir-nodeward-nodington-iii,lady-nodette-nodington-i,lady-nodette-nodington-ii,lady-nodette-nodington-x,lady-nodette-nodington-ix,lady-nodette-nodington-viii,lady-nodette-nodington-vii,lady-nodette-nodington-vi,lady-nodette-nodington-v,lady-nodette-nodington-iv,lady-nodette-nodington-iii,nody-mc-vi-child,nody-mc-iv-child" to be returned