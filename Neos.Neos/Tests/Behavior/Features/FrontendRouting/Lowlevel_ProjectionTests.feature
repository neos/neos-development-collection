@flowEntities @contentrepository
Feature: Low level tests covering the inner behavior of the routing projection

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.Neos:Sites':
      superTypes:
        'Neos.ContentRepository:Root': true
    'Neos.Neos:Document':
      properties:
        uriPathSegment:
          type: string
    'Neos.Neos:Content': []

    'Neos.Neos:Test.Routing.Page':
      superTypes:
        'Neos.Neos:Document': true
      constraints:
        nodeTypes:
          '*': true
          'Neos.Neos:Test.Routing.Page': true
          'Neos.Neos:Test.Routing.SomeOtherPage': true
          'Neos.Neos:Test.Routing.Content': true

    'Neos.Neos:Test.Routing.Content':
      superTypes:
        'Neos.Neos:Content': true

    'Neos.Neos:Test.Routing.SomeOtherPage':
      superTypes:
        'Neos.Neos:Test.Routing.Page': true
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live" and dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "lady-eleonode-rootford" |
      | nodeTypeName    | "Neos.Neos:Sites"        |

    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | parentNodeAggregateId  | nodeTypeName                | initialPropertyValues           | nodeName |
      | shernode-homes  | lady-eleonode-rootford | Neos.Neos:Test.Routing.Page | {"uriPathSegment": "ignore-me"} | site     |
      | a               | shernode-homes         | Neos.Neos:Test.Routing.Page | {"uriPathSegment": "a"}         | a        |
      | b               | shernode-homes         | Neos.Neos:Test.Routing.Page | {"uriPathSegment": "b"}         | b        |
      | c               | shernode-homes         | Neos.Neos:Test.Routing.Page | {"uriPathSegment": "c"}         | c        |
    And A site exists for node name "site"

  Scenario: initial state
    Then I expect the documenturipath table to contain exactly:
      | uripath | nodeaggregateidpath                       | nodeaggregateid          | parentnodeaggregateid    | precedingnodeaggregateid | succeedingnodeaggregateid | nodetypename                  |
      | ""      | "lady-eleonode-rootford"                  | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"             |
      | ""      | "lady-eleonode-rootford/shernode-homes"   | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Test.Routing.Page" |
      | "a"     | "lady-eleonode-rootford/shernode-homes/a" | "a"                      | "shernode-homes"         | null                     | "b"                       | "Neos.Neos:Test.Routing.Page" |
      | "b"     | "lady-eleonode-rootford/shernode-homes/b" | "b"                      | "shernode-homes"         | "a"                      | "c"                       | "Neos.Neos:Test.Routing.Page" |
      | "c"     | "lady-eleonode-rootford/shernode-homes/c" | "c"                      | "shernode-homes"         | "b"                      | null                      | "Neos.Neos:Test.Routing.Page" |

  Scenario: abc => acb (moving b)
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value |
      | nodeAggregateId                     | "b"   |
      | dimensionSpacePoint                 | {}    |
      | newParentNodeAggregateId            | null  |
      | newSucceedingSiblingNodeAggregateId | null  |
    Then I expect the documenturipath table to contain exactly:
      | uripath | nodeaggregateidpath                       | nodeaggregateid          | parentnodeaggregateid    | precedingnodeaggregateid | succeedingnodeaggregateid | nodetypename                  |
      | ""      | "lady-eleonode-rootford"                  | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"             |
      | ""      | "lady-eleonode-rootford/shernode-homes"   | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Test.Routing.Page" |
      | "a"     | "lady-eleonode-rootford/shernode-homes/a" | "a"                      | "shernode-homes"         | null                     | "c"                       | "Neos.Neos:Test.Routing.Page" |
      | "b"     | "lady-eleonode-rootford/shernode-homes/b" | "b"                      | "shernode-homes"         | "c"                      | null                      | "Neos.Neos:Test.Routing.Page" |
      | "c"     | "lady-eleonode-rootford/shernode-homes/c" | "c"                      | "shernode-homes"         | "a"                      | "b"                       | "Neos.Neos:Test.Routing.Page" |

  Scenario: abc => acb (moving c)
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value |
      | nodeAggregateId                     | "c"   |
      | dimensionSpacePoint                 | {}    |
      | newParentNodeAggregateId            | null  |
      | newSucceedingSiblingNodeAggregateId | "b"   |
    Then I expect the documenturipath table to contain exactly:
      | uripath | nodeaggregateidpath                       | nodeaggregateid          | parentnodeaggregateid    | precedingnodeaggregateid | succeedingnodeaggregateid | nodetypename                  |
      | ""      | "lady-eleonode-rootford"                  | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"             |
      | ""      | "lady-eleonode-rootford/shernode-homes"   | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Test.Routing.Page" |
      | "a"     | "lady-eleonode-rootford/shernode-homes/a" | "a"                      | "shernode-homes"         | null                     | "c"                       | "Neos.Neos:Test.Routing.Page" |
      | "b"     | "lady-eleonode-rootford/shernode-homes/b" | "b"                      | "shernode-homes"         | "c"                      | null                      | "Neos.Neos:Test.Routing.Page" |
      | "c"     | "lady-eleonode-rootford/shernode-homes/c" | "c"                      | "shernode-homes"         | "a"                      | "b"                       | "Neos.Neos:Test.Routing.Page" |

  Scenario: abc => bac (moving b)
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value |
      | nodeAggregateId                     | "b"   |
      | dimensionSpacePoint                 | {}    |
      | newParentNodeAggregateId            | null  |
      | newSucceedingSiblingNodeAggregateId | "a"   |
    Then I expect the documenturipath table to contain exactly:
      | uripath | nodeaggregateidpath                       | nodeaggregateid          | parentnodeaggregateid    | precedingnodeaggregateid | succeedingnodeaggregateid | nodetypename                  |
      | ""      | "lady-eleonode-rootford"                  | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"             |
      | ""      | "lady-eleonode-rootford/shernode-homes"   | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Test.Routing.Page" |
      | "a"     | "lady-eleonode-rootford/shernode-homes/a" | "a"                      | "shernode-homes"         | "b"                      | "c"                       | "Neos.Neos:Test.Routing.Page" |
      | "b"     | "lady-eleonode-rootford/shernode-homes/b" | "b"                      | "shernode-homes"         | null                     | "a"                       | "Neos.Neos:Test.Routing.Page" |
      | "c"     | "lady-eleonode-rootford/shernode-homes/c" | "c"                      | "shernode-homes"         | "a"                      | null                      | "Neos.Neos:Test.Routing.Page" |

  Scenario: abc => bac (moving a)
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value |
      | nodeAggregateId                     | "a"   |
      | dimensionSpacePoint                 | {}    |
      | newParentNodeAggregateId            | null  |
      | newSucceedingSiblingNodeAggregateId | "c"   |
    Then I expect the documenturipath table to contain exactly:
      | uripath | nodeaggregateidpath                       | nodeaggregateid          | parentnodeaggregateid    | precedingnodeaggregateid | succeedingnodeaggregateid | nodetypename                  |
      | ""      | "lady-eleonode-rootford"                  | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"             |
      | ""      | "lady-eleonode-rootford/shernode-homes"   | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Test.Routing.Page" |
      | "a"     | "lady-eleonode-rootford/shernode-homes/a" | "a"                      | "shernode-homes"         | "b"                      | "c"                       | "Neos.Neos:Test.Routing.Page" |
      | "b"     | "lady-eleonode-rootford/shernode-homes/b" | "b"                      | "shernode-homes"         | null                     | "a"                       | "Neos.Neos:Test.Routing.Page" |
      | "c"     | "lady-eleonode-rootford/shernode-homes/c" | "c"                      | "shernode-homes"         | "a"                      | null                      | "Neos.Neos:Test.Routing.Page" |

  Scenario: abc => bca (moving a)
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value |
      | nodeAggregateId                     | "a"   |
      | dimensionSpacePoint                 | {}    |
      | newParentNodeAggregateId            | null  |
      | newSucceedingSiblingNodeAggregateId | null  |
    Then I expect the documenturipath table to contain exactly:
      | uripath | nodeaggregateidpath                       | nodeaggregateid          | parentnodeaggregateid    | precedingnodeaggregateid | succeedingnodeaggregateid | nodetypename                  |
      | ""      | "lady-eleonode-rootford"                  | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"             |
      | ""      | "lady-eleonode-rootford/shernode-homes"   | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Test.Routing.Page" |
      | "a"     | "lady-eleonode-rootford/shernode-homes/a" | "a"                      | "shernode-homes"         | "c"                      | null                      | "Neos.Neos:Test.Routing.Page" |
      | "b"     | "lady-eleonode-rootford/shernode-homes/b" | "b"                      | "shernode-homes"         | null                     | "c"                       | "Neos.Neos:Test.Routing.Page" |
      | "c"     | "lady-eleonode-rootford/shernode-homes/c" | "c"                      | "shernode-homes"         | "b"                      | "a"                       | "Neos.Neos:Test.Routing.Page" |

  Scenario: abc => bca (moving b and c)
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value |
      | nodeAggregateId                     | "b"   |
      | dimensionSpacePoint                 | {}    |
      | newParentNodeAggregateId            | null  |
      | newSucceedingSiblingNodeAggregateId | "a"   |
    And the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value |
      | nodeAggregateId                     | "c"   |
      | dimensionSpacePoint                 | {}    |
      | newParentNodeAggregateId            | null  |
      | newSucceedingSiblingNodeAggregateId | "a"   |
    Then I expect the documenturipath table to contain exactly:
      | uripath | nodeaggregateidpath                       | nodeaggregateid          | parentnodeaggregateid    | precedingnodeaggregateid | succeedingnodeaggregateid | nodetypename                  |
      | ""      | "lady-eleonode-rootford"                  | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"             |
      | ""      | "lady-eleonode-rootford/shernode-homes"   | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Test.Routing.Page" |
      | "a"     | "lady-eleonode-rootford/shernode-homes/a" | "a"                      | "shernode-homes"         | "c"                      | null                      | "Neos.Neos:Test.Routing.Page" |
      | "b"     | "lady-eleonode-rootford/shernode-homes/b" | "b"                      | "shernode-homes"         | null                     | "c"                       | "Neos.Neos:Test.Routing.Page" |
      | "c"     | "lady-eleonode-rootford/shernode-homes/c" | "c"                      | "shernode-homes"         | "b"                      | "a"                       | "Neos.Neos:Test.Routing.Page" |

  Scenario: abc => a(> b)c (moving b below a)
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value |
      | nodeAggregateId                     | "b"   |
      | dimensionSpacePoint                 | {}    |
      | newParentNodeAggregateId            | "a"   |
      | newSucceedingSiblingNodeAggregateId | null  |
    Then I expect the documenturipath table to contain exactly:
      | uripath | nodeaggregateidpath                         | nodeaggregateid          | parentnodeaggregateid    | precedingnodeaggregateid | succeedingnodeaggregateid | nodetypename                  |
      | ""      | "lady-eleonode-rootford"                    | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"             |
      | ""      | "lady-eleonode-rootford/shernode-homes"     | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Test.Routing.Page" |
      | "a"     | "lady-eleonode-rootford/shernode-homes/a"   | "a"                      | "shernode-homes"         | null                     | "c"                       | "Neos.Neos:Test.Routing.Page" |
      | "a/b"   | "lady-eleonode-rootford/shernode-homes/a/b" | "b"                      | "a"                      | null                     | null                      | "Neos.Neos:Test.Routing.Page" |
      | "c"     | "lady-eleonode-rootford/shernode-homes/c"   | "c"                      | "shernode-homes"         | "a"                      | null                      | "Neos.Neos:Test.Routing.Page" |

  Scenario: ab(> b1)c => a(> b > b1)c (moving b & b1 below a)
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                              | Value                         |
      | nodeAggregateId                  | "b1"                          |
      | nodeTypeName                     | "Neos.Neos:Test.Routing.Page" |
      | originDimensionSpacePoint        | {}                            |
      | parentNodeAggregateId            | "b"                           |
      | initialPropertyValues            | {"uriPathSegment": "b1"}      |
      | succeedingSiblingNodeAggregateId | null                          |
    And the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value |
      | nodeAggregateId                     | "b"   |
      | dimensionSpacePoint                 | {}    |
      | newParentNodeAggregateId            | "a"   |
      | newSucceedingSiblingNodeAggregateId | null  |
    Then I expect the documenturipath table to contain exactly:
      | uripath  | nodeaggregateidpath                            | nodeaggregateid          | parentnodeaggregateid    | precedingnodeaggregateid | succeedingnodeaggregateid | nodetypename                  |
      | ""       | "lady-eleonode-rootford"                       | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"             |
      | ""       | "lady-eleonode-rootford/shernode-homes"        | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Test.Routing.Page" |
      | "a"      | "lady-eleonode-rootford/shernode-homes/a"      | "a"                      | "shernode-homes"         | null                     | "c"                       | "Neos.Neos:Test.Routing.Page" |
      | "a/b"    | "lady-eleonode-rootford/shernode-homes/a/b"    | "b"                      | "a"                      | null                     | null                      | "Neos.Neos:Test.Routing.Page" |
      | "a/b/b1" | "lady-eleonode-rootford/shernode-homes/a/b/b1" | "b1"                     | "b"                      | null                     | null                      | "Neos.Neos:Test.Routing.Page" |
      | "c"      | "lady-eleonode-rootford/shernode-homes/c"      | "c"                      | "shernode-homes"         | "a"                      | null                      | "Neos.Neos:Test.Routing.Page" |

  Scenario: ab(> b1)c => a(> b1)bc (moving b1 below a)
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                              | Value                         |
      | nodeAggregateId                  | "b1"                          |
      | nodeTypeName                     | "Neos.Neos:Test.Routing.Page" |
      | originDimensionSpacePoint        | {}                            |
      | parentNodeAggregateId            | "b"                           |
      | initialPropertyValues            | {"uriPathSegment": "b1"}      |
      | succeedingSiblingNodeAggregateId | null                          |
    And the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value |
      | nodeAggregateId                     | "b1"  |
      | dimensionSpacePoint                 | {}    |
      | newParentNodeAggregateId            | "a"   |
      | newSucceedingSiblingNodeAggregateId | null  |
    Then I expect the documenturipath table to contain exactly:
      | uripath | nodeaggregateidpath                          | nodeaggregateid          | parentnodeaggregateid    | precedingnodeaggregateid | succeedingnodeaggregateid | nodetypename                  |
      | ""      | "lady-eleonode-rootford"                     | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"             |
      | ""      | "lady-eleonode-rootford/shernode-homes"      | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Test.Routing.Page" |
      | "a"     | "lady-eleonode-rootford/shernode-homes/a"    | "a"                      | "shernode-homes"         | null                     | "b"                       | "Neos.Neos:Test.Routing.Page" |
      | "a/b1"  | "lady-eleonode-rootford/shernode-homes/a/b1" | "b1"                     | "a"                      | null                     | null                      | "Neos.Neos:Test.Routing.Page" |
      | "b"     | "lady-eleonode-rootford/shernode-homes/b"    | "b"                      | "shernode-homes"         | "a"                      | "c"                       | "Neos.Neos:Test.Routing.Page" |
      | "c"     | "lady-eleonode-rootford/shernode-homes/c"    | "c"                      | "shernode-homes"         | "b"                      | null                      | "Neos.Neos:Test.Routing.Page" |

  Scenario: ab(> b1, b2 > b2a)c => a(> b2 > b2a)b(> b1)c (moving b1 below a)
    And I am in workspace "live" and dimension space point {}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | parentNodeAggregateId | nodeTypeName                | initialPropertyValues     | nodeName |
      | b1              | b                     | Neos.Neos:Test.Routing.Page | {"uriPathSegment": "b1"}  | b1       |
      | b2              | b                     | Neos.Neos:Test.Routing.Page | {"uriPathSegment": "b2"}  | b2       |
      | b2a             | b2                    | Neos.Neos:Test.Routing.Page | {"uriPathSegment": "b2a"} | b2a      |
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value |
      | nodeAggregateId                     | "b2"  |
      | dimensionSpacePoint                 | {}    |
      | newParentNodeAggregateId            | "a"   |
      | newSucceedingSiblingNodeAggregateId | null  |
    Then I expect the documenturipath table to contain exactly:
      | uripath    | nodeaggregateidpath                              | nodeaggregateid          | parentnodeaggregateid    | precedingnodeaggregateid | succeedingnodeaggregateid | nodetypename                  |
      | ""         | "lady-eleonode-rootford"                         | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"             |
      | ""         | "lady-eleonode-rootford/shernode-homes"          | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Test.Routing.Page" |
      | "a"        | "lady-eleonode-rootford/shernode-homes/a"        | "a"                      | "shernode-homes"         | null                     | "b"                       | "Neos.Neos:Test.Routing.Page" |
      | "a/b2"     | "lady-eleonode-rootford/shernode-homes/a/b2"     | "b2"                     | "a"                      | null                     | null                      | "Neos.Neos:Test.Routing.Page" |
      | "a/b2/b2a" | "lady-eleonode-rootford/shernode-homes/a/b2/b2a" | "b2a"                    | "b2"                     | null                     | null                      | "Neos.Neos:Test.Routing.Page" |
      | "b"        | "lady-eleonode-rootford/shernode-homes/b"        | "b"                      | "shernode-homes"         | "a"                      | "c"                       | "Neos.Neos:Test.Routing.Page" |
      | "b/b1"     | "lady-eleonode-rootford/shernode-homes/b/b1"     | "b1"                     | "b"                      | null                     | null                      | "Neos.Neos:Test.Routing.Page" |
      | "c"        | "lady-eleonode-rootford/shernode-homes/c"        | "c"                      | "shernode-homes"         | "b"                      | null                      | "Neos.Neos:Test.Routing.Page" |

  Scenario: ab(> b1, b2 > b2a)c => b(> b1, a, b2 > b2a)c (moving a below b)
    And I am in workspace "live" and dimension space point {}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | parentNodeAggregateId | nodeTypeName                | initialPropertyValues     | nodeName |
      | b1              | b                     | Neos.Neos:Test.Routing.Page | {"uriPathSegment": "b1"}  | b1       |
      | b2              | b                     | Neos.Neos:Test.Routing.Page | {"uriPathSegment": "b2"}  | b2       |
      | b2a             | b2                    | Neos.Neos:Test.Routing.Page | {"uriPathSegment": "b2a"} | b2a      |
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value |
      | nodeAggregateId                     | "a"   |
      | dimensionSpacePoint                 | {}    |
      | newParentNodeAggregateId            | "b"   |
      | newSucceedingSiblingNodeAggregateId | "b2"  |
    Then I expect the documenturipath table to contain exactly:
      | uripath    | nodeaggregateidpath                              | nodeaggregateid          | parentnodeaggregateid    | precedingnodeaggregateid | succeedingnodeaggregateid | nodetypename                  |
      | ""         | "lady-eleonode-rootford"                         | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"             |
      | ""         | "lady-eleonode-rootford/shernode-homes"          | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Test.Routing.Page" |
      | "b"        | "lady-eleonode-rootford/shernode-homes/b"        | "b"                      | "shernode-homes"         | null                     | "c"                       | "Neos.Neos:Test.Routing.Page" |
      | "b/a"      | "lady-eleonode-rootford/shernode-homes/b/a"      | "a"                      | "b"                      | "b1"                     | "b2"                      | "Neos.Neos:Test.Routing.Page" |
      | "b/b1"     | "lady-eleonode-rootford/shernode-homes/b/b1"     | "b1"                     | "b"                      | null                     | "a"                       | "Neos.Neos:Test.Routing.Page" |
      | "b/b2"     | "lady-eleonode-rootford/shernode-homes/b/b2"     | "b2"                     | "b"                      | "a"                      | null                      | "Neos.Neos:Test.Routing.Page" |
      | "b/b2/b2a" | "lady-eleonode-rootford/shernode-homes/b/b2/b2a" | "b2a"                    | "b2"                     | null                     | null                      | "Neos.Neos:Test.Routing.Page" |
      | "c"        | "lady-eleonode-rootford/shernode-homes/c"        | "c"                      | "shernode-homes"         | "b"                      | null                      | "Neos.Neos:Test.Routing.Page" |

  Scenario: Changing the NodeTypeName of a NodeAggregate
    When the command ChangeNodeAggregateType is executed with payload:
      | Key             | Value                                  |
      | nodeAggregateId | "c"                                    |
      | newNodeTypeName | "Neos.Neos:Test.Routing.SomeOtherPage" |
      | strategy        | "happypath"                            |
    Then I expect the documenturipath table to contain exactly:
      | uripath | nodeaggregateidpath                       | nodeaggregateid          | parentnodeaggregateid    | precedingnodeaggregateid | succeedingnodeaggregateid | nodetypename                           |
      | ""      | "lady-eleonode-rootford"                  | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"                      |
      | ""      | "lady-eleonode-rootford/shernode-homes"   | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Test.Routing.Page"          |
      | "a"     | "lady-eleonode-rootford/shernode-homes/a" | "a"                      | "shernode-homes"         | null                     | "b"                       | "Neos.Neos:Test.Routing.Page"          |
      | "b"     | "lady-eleonode-rootford/shernode-homes/b" | "b"                      | "shernode-homes"         | "a"                      | "c"                       | "Neos.Neos:Test.Routing.Page"          |
      | "c"     | "lady-eleonode-rootford/shernode-homes/c" | "c"                      | "shernode-homes"         | "b"                      | null                      | "Neos.Neos:Test.Routing.SomeOtherPage" |
