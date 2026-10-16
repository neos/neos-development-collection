Feature: Moving a node repaths its whole subtree

  In the general DBAL adapter, every hierarchy relation stores its materialised sort path and its depth. Moving a node
  rewrites both for all of its descendants: the old sort path prefix is replaced by the new one, and the depth is
  shifted by the depth difference between the old and the new position (depthDelta). findSubtree derives the levels
  from the depth and applies maximumLevels on it, findDescendantNodes scans the sort path range.
  Moves on the same level (depthDelta 0) still rewrite the prefix but must keep the depth.

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
      | nodeAggregateId | nodeTypeName                            | parentNodeAggregateId  |
      | a               | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford |
      | a1              | Neos.ContentRepository.Testing:Document | a                      |
      | a1a             | Neos.ContentRepository.Testing:Document | a1                     |
      | a1a1            | Neos.ContentRepository.Testing:Document | a1a                    |
      | b               | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford |
      | b1              | Neos.ContentRepository.Testing:Document | b                      |

  Scenario: Move a node with descendants to another parent at the same depth
    # a1 moves from below a to below b, both at depth 1, so its descendants get depthDelta 0 but a new prefix
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value |
      | nodeAggregateId                     | "a1"  |
      | newParentNodeAggregateId            | "b"   |
      | newSucceedingSiblingNodeAggregateId | "b1"  |

    Then I execute the findSubtree query for entry node aggregate id "lady-eleonode-rootford" I expect the following tree:
    """
    lady-eleonode-rootford
     a
     b
      a1
       a1a
        a1a1
      b1
    """
    And I execute the findSubtree query for entry node aggregate id "b" and filter '{"maximumLevels": 1}' I expect the following tree:
    """
    b
     a1
     b1
    """
    And I execute the findSubtree query for entry node aggregate id "a1" and filter '{"maximumLevels": 1}' I expect the following tree:
    """
    a1
     a1a
    """
    And I execute the findDescendantNodes query for entry node aggregate id "b" I expect the nodes "a1,a1a,a1a1,b1" to be returned
    And I execute the findDescendantNodes query for entry node aggregate id "a" I expect no nodes to be returned

  Scenario: Move a node with descendants among its siblings
    # b moves before a without changing its parent, so its descendants get depthDelta 0 but a new prefix
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value |
      | nodeAggregateId                     | "b"   |
      | newSucceedingSiblingNodeAggregateId | "a"   |

    Then I execute the findSubtree query for entry node aggregate id "lady-eleonode-rootford" I expect the following tree:
    """
    lady-eleonode-rootford
     b
      b1
     a
      a1
       a1a
        a1a1
    """
    And I execute the findSubtree query for entry node aggregate id "lady-eleonode-rootford" and filter '{"maximumLevels": 2}' I expect the following tree:
    """
    lady-eleonode-rootford
     b
      b1
     a
      a1
    """
    And I execute the findDescendantNodes query for entry node aggregate id "b" I expect the nodes "b1" to be returned

  Scenario: Move a node with descendants deeper
    # a1 moves from depth 2 to depth 3, so its descendants get depthDelta +1
    When the command MoveNodeAggregate is executed with payload:
      | Key                      | Value |
      | nodeAggregateId          | "a1"  |
      | newParentNodeAggregateId | "b1"  |

    Then I execute the findSubtree query for entry node aggregate id "lady-eleonode-rootford" I expect the following tree:
    """
    lady-eleonode-rootford
     a
     b
      b1
       a1
        a1a
         a1a1
    """
    And I execute the findSubtree query for entry node aggregate id "b" and filter '{"maximumLevels": 2}' I expect the following tree:
    """
    b
     b1
      a1
    """
    And I execute the findSubtree query for entry node aggregate id "a1" and filter '{"maximumLevels": 1}' I expect the following tree:
    """
    a1
     a1a
    """
    And I execute the findDescendantNodes query for entry node aggregate id "b" I expect the nodes "b1,a1,a1a,a1a1" to be returned
    And I execute the findDescendantNodes query for entry node aggregate id "a" I expect no nodes to be returned

  Scenario: Move a node with descendants shallower
    # a1 moves from depth 2 to depth 1, so its descendants get depthDelta -1
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                    |
      | nodeAggregateId                     | "a1"                     |
      | newParentNodeAggregateId            | "lady-eleonode-rootford" |
      | newSucceedingSiblingNodeAggregateId | "b"                      |

    Then I execute the findSubtree query for entry node aggregate id "lady-eleonode-rootford" I expect the following tree:
    """
    lady-eleonode-rootford
     a
     a1
      a1a
       a1a1
     b
      b1
    """
    And I execute the findSubtree query for entry node aggregate id "lady-eleonode-rootford" and filter '{"maximumLevels": 2}' I expect the following tree:
    """
    lady-eleonode-rootford
     a
     a1
      a1a
     b
      b1
    """
    And I execute the findSubtree query for entry node aggregate id "a1" and filter '{"maximumLevels": 1}' I expect the following tree:
    """
    a1
     a1a
    """
    And I execute the findDescendantNodes query for entry node aggregate id "a1" I expect the nodes "a1a,a1a1" to be returned
    And I execute the findDescendantNodes query for entry node aggregate id "a" I expect no nodes to be returned

  Scenario: Move a node with descendants shallower in a workspace
    # The repathed descendants are copied to the user layer, live keeps the old sort paths and depths
    When the command CreateWorkspace is executed with payload:
      | Key                | Value     |
      | workspaceName      | "user"    |
      | baseWorkspaceName  | "live"    |
      | newContentStreamId | "user-cs" |
    And I am in workspace "user" and dimension space point {"example": "general"}
    And the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                    |
      | nodeAggregateId                     | "a1"                     |
      | newParentNodeAggregateId            | "lady-eleonode-rootford" |
      | newSucceedingSiblingNodeAggregateId | "b"                      |

    Then I execute the findSubtree query for entry node aggregate id "lady-eleonode-rootford" I expect the following tree:
    """
    lady-eleonode-rootford
     a
     a1
      a1a
       a1a1
     b
      b1
    """
    And I execute the findSubtree query for entry node aggregate id "a1" and filter '{"maximumLevels": 1}' I expect the following tree:
    """
    a1
     a1a
    """
    And I execute the findDescendantNodes query for entry node aggregate id "a1" I expect the nodes "a1a,a1a1" to be returned
    And I execute the findDescendantNodes query for entry node aggregate id "a" I expect no nodes to be returned

    When I am in workspace "live" and dimension space point {"example": "general"}
    Then I execute the findSubtree query for entry node aggregate id "lady-eleonode-rootford" I expect the following tree:
    """
    lady-eleonode-rootford
     a
      a1
       a1a
        a1a1
     b
      b1
    """
    And I execute the findSubtree query for entry node aggregate id "a" and filter '{"maximumLevels": 2}' I expect the following tree:
    """
    a
     a1
      a1a
    """
    And I execute the findDescendantNodes query for entry node aggregate id "a" I expect the nodes "a1,a1a,a1a1" to be returned
