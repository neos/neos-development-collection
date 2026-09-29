Feature: Sibling positions are properly resolved

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

  Scenario: Trigger rebalancing after several moves into a tiny gap
    # Reduce the gap between i (a1) and x to a minimum, so that a few inserts exceed NodeSortPath::MAX_KEY_LENGTH (12)
    Given I set the following sortPath:
      | Key                  | Value                      |
      | contentStreamId      | "cs-identifier"            |
      | dimensionSpacePoint  | {"example": "general"}     |
      | childNodeAggregateId | "lady-nodette-nodington-x" |
      | newSortPath          | "a0/a1000000000000000000000000000000001"              |
    And I am in workspace "live" and dimension space point {"example": "general"}
    # ii: a1000000000V (length 12)
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                       |
      | nodeAggregateId                     | "lady-nodette-nodington-ii" |
      | newSucceedingSiblingNodeAggregateId | "lady-nodette-nodington-x"  |
    # iii: a1000000000l (length 12)
    And the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                        |
      | nodeAggregateId                     | "lady-nodette-nodington-iii" |
      | newSucceedingSiblingNodeAggregateId | "lady-nodette-nodington-x"   |
    # iv: a1000000000t (length 12)
    And the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                       |
      | nodeAggregateId                     | "lady-nodette-nodington-iv" |
      | newSucceedingSiblingNodeAggregateId | "lady-nodette-nodington-x"  |
    # v: a1000000000x (length 12)
    And the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                      |
      | nodeAggregateId                     | "lady-nodette-nodington-v" |
      | newSucceedingSiblingNodeAggregateId | "lady-nodette-nodington-x" |
    # vi: a1000000000z (length 12, exactly at the limit -> no rebalancing)
    And the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                       |
      | nodeAggregateId                     | "lady-nodette-nodington-vi" |
      | newSucceedingSiblingNodeAggregateId | "lady-nodette-nodington-x"  |
    # vii: a1000000000zV (length 13, exceeds the limit -> rebalancing)
    And the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                        |
      | nodeAggregateId                     | "lady-nodette-nodington-vii" |
      | newSucceedingSiblingNodeAggregateId | "lady-nodette-nodington-x"   |
    # viii: inserted into the rebalanced sibling set
    And the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                         |
      | nodeAggregateId                     | "lady-nodette-nodington-viii" |
      | newSucceedingSiblingNodeAggregateId | "lady-nodette-nodington-x"    |
    # ix: inserted into the rebalanced sibling set
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

  Scenario: Trigger rebalancing in two workspaces and publish them one after the other
    # Reduce the gap between i (a1) and x to a minimum in live, so both workspaces inherit it
    Given I set the following sortPath:
      | Key                  | Value                      |
      | contentStreamId      | "cs-identifier"            |
      | dimensionSpacePoint  | {"example": "general"}     |
      | childNodeAggregateId | "lady-nodette-nodington-x" |
      | newSortPath          | "a0/a1000000000000000000000000000000001"                |
    And the command CreateWorkspace is executed with payload:
      | Key                | Value       |
      | workspaceName      | "user-a"    |
      | baseWorkspaceName  | "live"      |
      | newContentStreamId | "user-a-cs" |
    And the command CreateWorkspace is executed with payload:
      | Key                | Value       |
      | workspaceName      | "user-b"    |
      | baseWorkspaceName  | "live"      |
      | newContentStreamId | "user-b-cs" |

    # User A: one insert and five moves into the tiny gap before x
    When I am in workspace "user-a" and dimension space point {"example": "general"}
    # user-a: a1000000000V (length 12)
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | nodeTypeName                            | parentNodeAggregateId  | nodeName | succeedingSiblingNodeAggregateId |
      | nody-mc-user-a  | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | user-a   | lady-nodette-nodington-x         |
    # ii: a1000000000l (length 12)
    And the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                       |
      | nodeAggregateId                     | "lady-nodette-nodington-ii" |
      | newSucceedingSiblingNodeAggregateId | "lady-nodette-nodington-x"  |
    # iii: a1000000000t (length 12)
    And the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                        |
      | nodeAggregateId                     | "lady-nodette-nodington-iii" |
      | newSucceedingSiblingNodeAggregateId | "lady-nodette-nodington-x"   |
    # iv: a1000000000x (length 12)
    And the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                       |
      | nodeAggregateId                     | "lady-nodette-nodington-iv" |
      | newSucceedingSiblingNodeAggregateId | "lady-nodette-nodington-x"  |
    # v: a1000000000z (length 12, exactly at the limit -> no rebalancing)
    And the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                      |
      | nodeAggregateId                     | "lady-nodette-nodington-v" |
      | newSucceedingSiblingNodeAggregateId | "lady-nodette-nodington-x" |
    # vi: a1000000000zV (length 13, exceeds the limit -> rebalancing)
    And the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                       |
      | nodeAggregateId                     | "lady-nodette-nodington-vi" |
      | newSucceedingSiblingNodeAggregateId | "lady-nodette-nodington-x"  |

    # User B: four inserts and two moves into the tiny gap before x
    When I am in workspace "user-b" and dimension space point {"example": "general"}
    # user-b-i: a1000000000V, user-b-ii: a1000000000l, user-b-iii: a1000000000t, user-b-iv: a1000000000x (length 12)
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId    | nodeTypeName                            | parentNodeAggregateId  | nodeName   | succeedingSiblingNodeAggregateId |
      | nody-mc-user-b-i   | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | user-b-i   | lady-nodette-nodington-x         |
      | nody-mc-user-b-ii  | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | user-b-ii  | lady-nodette-nodington-x         |
      | nody-mc-user-b-iii | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | user-b-iii | lady-nodette-nodington-x         |
      | nody-mc-user-b-iv  | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | user-b-iv  | lady-nodette-nodington-x         |
    # ix: a1000000000z (length 12, exactly at the limit -> no rebalancing)
    And the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                       |
      | nodeAggregateId                     | "lady-nodette-nodington-ix" |
      | newSucceedingSiblingNodeAggregateId | "lady-nodette-nodington-x"  |
    # viii: a1000000000zV (length 13, exceeds the limit -> rebalancing)
    And the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                         |
      | nodeAggregateId                     | "lady-nodette-nodington-viii" |
      | newSucceedingSiblingNodeAggregateId | "lady-nodette-nodington-x"    |

    Then I am in workspace "user-a" and dimension space point {"example": "general"}
    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to node user-a-cs;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name           | NodeDiscriminator                                            |
      | esquire        | user-a-cs;sir-nodeward-nodington-iii;{"example": "general"}  |
      | nodington-i    | user-a-cs;lady-nodette-nodington-i;{"example": "general"}    |
      | user-a         | user-a-cs;nody-mc-user-a;{"example": "general"}              |
      | nodington-ii   | user-a-cs;lady-nodette-nodington-ii;{"example": "general"}   |
      | nodington-iii  | user-a-cs;lady-nodette-nodington-iii;{"example": "general"}  |
      | nodington-iv   | user-a-cs;lady-nodette-nodington-iv;{"example": "general"}   |
      | nodington-v    | user-a-cs;lady-nodette-nodington-v;{"example": "general"}    |
      | nodington-vi   | user-a-cs;lady-nodette-nodington-vi;{"example": "general"}   |
      | nodington-x    | user-a-cs;lady-nodette-nodington-x;{"example": "general"}    |
      | nodington-ix   | user-a-cs;lady-nodette-nodington-ix;{"example": "general"}   |
      | nodington-viii | user-a-cs;lady-nodette-nodington-viii;{"example": "general"} |
      | nodington-vii  | user-a-cs;lady-nodette-nodington-vii;{"example": "general"}  |

    And I am in workspace "user-b" and dimension space point {"example": "general"}
    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to node user-b-cs;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name           | NodeDiscriminator                                            |
      | esquire        | user-b-cs;sir-nodeward-nodington-iii;{"example": "general"}  |
      | nodington-i    | user-b-cs;lady-nodette-nodington-i;{"example": "general"}    |
      | user-b-i       | user-b-cs;nody-mc-user-b-i;{"example": "general"}            |
      | user-b-ii      | user-b-cs;nody-mc-user-b-ii;{"example": "general"}           |
      | user-b-iii     | user-b-cs;nody-mc-user-b-iii;{"example": "general"}          |
      | user-b-iv      | user-b-cs;nody-mc-user-b-iv;{"example": "general"}           |
      | nodington-ix   | user-b-cs;lady-nodette-nodington-ix;{"example": "general"}   |
      | nodington-viii | user-b-cs;lady-nodette-nodington-viii;{"example": "general"} |
      | nodington-x    | user-b-cs;lady-nodette-nodington-x;{"example": "general"}    |
      | nodington-vii  | user-b-cs;lady-nodette-nodington-vii;{"example": "general"}  |
      | nodington-vi   | user-b-cs;lady-nodette-nodington-vi;{"example": "general"}   |
      | nodington-v    | user-b-cs;lady-nodette-nodington-v;{"example": "general"}    |
      | nodington-iv   | user-b-cs;lady-nodette-nodington-iv;{"example": "general"}   |
      | nodington-iii  | user-b-cs;lady-nodette-nodington-iii;{"example": "general"}  |
      | nodington-ii   | user-b-cs;lady-nodette-nodington-ii;{"example": "general"}   |

    # Publish user A first
    When the command PublishWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "user-a"        |
      | newContentStreamId | "user-a-cs-new" |
    Then I am in workspace "live" and dimension space point {"example": "general"}
    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name           | NodeDiscriminator                                                |
      | esquire        | cs-identifier;sir-nodeward-nodington-iii;{"example": "general"}  |
      | nodington-i    | cs-identifier;lady-nodette-nodington-i;{"example": "general"}    |
      | user-a         | cs-identifier;nody-mc-user-a;{"example": "general"}              |
      | nodington-ii   | cs-identifier;lady-nodette-nodington-ii;{"example": "general"}   |
      | nodington-iii  | cs-identifier;lady-nodette-nodington-iii;{"example": "general"}  |
      | nodington-iv   | cs-identifier;lady-nodette-nodington-iv;{"example": "general"}   |
      | nodington-v    | cs-identifier;lady-nodette-nodington-v;{"example": "general"}    |
      | nodington-vi   | cs-identifier;lady-nodette-nodington-vi;{"example": "general"}   |
      | nodington-x    | cs-identifier;lady-nodette-nodington-x;{"example": "general"}    |
      | nodington-ix   | cs-identifier;lady-nodette-nodington-ix;{"example": "general"}   |
      | nodington-viii | cs-identifier;lady-nodette-nodington-viii;{"example": "general"} |
      | nodington-vii  | cs-identifier;lady-nodette-nodington-vii;{"example": "general"}  |

    # Publish user B afterwards, its changes are rebased onto user A's published changes
    When the command PublishWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "user-b"        |
      | newContentStreamId | "user-b-cs-new" |

    Then I am in workspace "live" and dimension space point {"example": "general"}
    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name           | NodeDiscriminator                                                |
      | esquire        | cs-identifier;sir-nodeward-nodington-iii;{"example": "general"}  |
      | nodington-i    | cs-identifier;lady-nodette-nodington-i;{"example": "general"}    |
      | user-a         | cs-identifier;nody-mc-user-a;{"example": "general"}              |
      | nodington-ii   | cs-identifier;lady-nodette-nodington-ii;{"example": "general"}   |
      | nodington-iii  | cs-identifier;lady-nodette-nodington-iii;{"example": "general"}  |
      | nodington-iv   | cs-identifier;lady-nodette-nodington-iv;{"example": "general"}   |
      | nodington-v    | cs-identifier;lady-nodette-nodington-v;{"example": "general"}    |
      | nodington-vi   | cs-identifier;lady-nodette-nodington-vi;{"example": "general"}   |
      | user-b-i       | cs-identifier;nody-mc-user-b-i;{"example": "general"}            |
      | user-b-ii      | cs-identifier;nody-mc-user-b-ii;{"example": "general"}           |
      | user-b-iii     | cs-identifier;nody-mc-user-b-iii;{"example": "general"}          |
      | user-b-iv      | cs-identifier;nody-mc-user-b-iv;{"example": "general"}           |
      | nodington-ix   | cs-identifier;lady-nodette-nodington-ix;{"example": "general"}   |
      | nodington-viii | cs-identifier;lady-nodette-nodington-viii;{"example": "general"} |
      | nodington-x    | cs-identifier;lady-nodette-nodington-x;{"example": "general"}    |
      | nodington-vii  | cs-identifier;lady-nodette-nodington-vii;{"example": "general"}  |

  Scenario: Move a node to its grandparent's level into a tiny gap, which rebalances its old parent, in a workspace
    Given the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId  | nodeTypeName                            | parentNodeAggregateId    | nodeName |
      | nody-mc-nodeface | Neos.ContentRepository.Testing:Document | lady-nodette-nodington-v | child    |
     # Tiny gap between i (a0/a1) and x: the first insert gets a10000000000V (length 13) -> rebalancing right away
    And I set the following sortPath:
      | Key                  | Value                      |
      | contentStreamId      | "cs-identifier"            |
      | dimensionSpacePoint  | {"example": "general"}     |
      | childNodeAggregateId | "lady-nodette-nodington-x" |
      | newSortPath          | "a0/a10000000000000000000000000000000001"          |
    And the command CreateWorkspace is executed with payload:
      | Key                | Value     |
      | workspaceName      | "user"    |
      | baseWorkspaceName  | "live"    |
      | newContentStreamId | "user-cs" |
    And I am in workspace "user" and dimension space point {"example": "general"}

     # Rebalancing re-keys v a0/a7 -> a0/a8 and repaths its descendants, i.e. the moved node's own relation
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                      |
      | nodeAggregateId                     | "nody-mc-nodeface"         |
      | newParentNodeAggregateId            | "lady-eleonode-rootford"   |
      | newSucceedingSiblingNodeAggregateId | "lady-nodette-nodington-x" |

    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node user-cs;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name           | NodeDiscriminator                                          |
      | esquire        | user-cs;sir-nodeward-nodington-iii;{"example": "general"}  |
      | nodington-i    | user-cs;lady-nodette-nodington-i;{"example": "general"}    |
      | child          | user-cs;nody-mc-nodeface;{"example": "general"}            |
      | nodington-x    | user-cs;lady-nodette-nodington-x;{"example": "general"}    |
      | nodington-ix   | user-cs;lady-nodette-nodington-ix;{"example": "general"}   |
      | nodington-viii | user-cs;lady-nodette-nodington-viii;{"example": "general"} |
      | nodington-vii  | user-cs;lady-nodette-nodington-vii;{"example": "general"}  |
      | nodington-vi   | user-cs;lady-nodette-nodington-vi;{"example": "general"}   |
      | nodington-v    | user-cs;lady-nodette-nodington-v;{"example": "general"}    |
      | nodington-iv   | user-cs;lady-nodette-nodington-iv;{"example": "general"}   |
      | nodington-iii  | user-cs;lady-nodette-nodington-iii;{"example": "general"}  |
      | nodington-ii   | user-cs;lady-nodette-nodington-ii;{"example": "general"}   |
    And I expect node aggregate identifier "lady-nodette-nodington-v" to lead to node user-cs;lady-nodette-nodington-v;{"example": "general"}
    And I expect this node to have no child nodes