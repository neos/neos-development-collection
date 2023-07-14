@fixtures @contentrepository
  # Note: For the routing tests to work we rely on Configuration/Testing/Behat/NodeTypes.Test.Routing.yaml
Feature: Low level tests covering the inner behavior of the routing projection

  Background:
    Given I have no content dimensions
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                         | Value                    |
      | contentStreamId             | "cs-identifier"          |
      | nodeAggregateId             | "lady-eleonode-rootford" |
      | nodeTypeName                | "Neos.Neos:Sites"        |
    And the graph projection is fully up to date

    And I am in content stream "cs-identifier" and dimension space point {}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | parentNodeAggregateId  | nodeTypeName                | initialPropertyValues           | nodeName |
      | shernode-homes  | lady-eleonode-rootford | Neos.Neos:Test.Routing.Page | {"uriPathSegment": "ignore-me"} | site     |
      | a               | shernode-homes         | Neos.Neos:Test.Routing.Page | {"uriPathSegment": "a"}         | a        |
      | b               | shernode-homes         | Neos.Neos:Test.Routing.Page | {"uriPathSegment": "b"}         | b        |
      | c               | shernode-homes         | Neos.Neos:Test.Routing.Page | {"uriPathSegment": "c"}         | c        |
    And A site exists for node name "site"
    And The documenturipath projection is up to date

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
      | Key                                 | Value           |
      | contentStreamId                     | "cs-identifier" |
      | nodeAggregateId                     | "b"             |
      | dimensionSpacePoint                 | {}              |
      | newParentNodeAggregateId            | null            |
      | newSucceedingSiblingNodeAggregateId | null            |
    And The documenturipath projection is up to date
    Then I expect the documenturipath table to contain exactly:
      | uripath | nodeaggregateidpath                       | nodeaggregateid          | parentnodeaggregateid    | precedingnodeaggregateid | succeedingnodeaggregateid | nodetypename                  |
      | ""      | "lady-eleonode-rootford"                  | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"             |
      | ""      | "lady-eleonode-rootford/shernode-homes"   | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Test.Routing.Page" |
      | "a"     | "lady-eleonode-rootford/shernode-homes/a" | "a"                      | "shernode-homes"         | null                     | "c"                       | "Neos.Neos:Test.Routing.Page" |
      | "b"     | "lady-eleonode-rootford/shernode-homes/b" | "b"                      | "shernode-homes"         | "c"                      | null                      | "Neos.Neos:Test.Routing.Page" |
      | "c"     | "lady-eleonode-rootford/shernode-homes/c" | "c"                      | "shernode-homes"         | "a"                      | "b"                       | "Neos.Neos:Test.Routing.Page" |

  Scenario: abc => acb (moving c)
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value           |
      | contentStreamId                     | "cs-identifier" |
      | nodeAggregateId                     | "c"             |
      | dimensionSpacePoint                 | {}              |
      | newParentNodeAggregateId            | null            |
      | newSucceedingSiblingNodeAggregateId | "b"             |
    And The documenturipath projection is up to date
    Then I expect the documenturipath table to contain exactly:
      | uripath | nodeaggregateidpath                       | nodeaggregateid          | parentnodeaggregateid    | precedingnodeaggregateid | succeedingnodeaggregateid | nodetypename                  |
      | ""      | "lady-eleonode-rootford"                  | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"             |
      | ""      | "lady-eleonode-rootford/shernode-homes"   | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Test.Routing.Page" |
      | "a"     | "lady-eleonode-rootford/shernode-homes/a" | "a"                      | "shernode-homes"         | null                     | "c"                       | "Neos.Neos:Test.Routing.Page" |
      | "b"     | "lady-eleonode-rootford/shernode-homes/b" | "b"                      | "shernode-homes"         | "c"                      | null                      | "Neos.Neos:Test.Routing.Page" |
      | "c"     | "lady-eleonode-rootford/shernode-homes/c" | "c"                      | "shernode-homes"         | "a"                      | "b"                       | "Neos.Neos:Test.Routing.Page" |

  Scenario: abc => bac (moving b)
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value           |
      | contentStreamId                     | "cs-identifier" |
      | nodeAggregateId                     | "b"             |
      | dimensionSpacePoint                 | {}              |
      | newParentNodeAggregateId            | null            |
      | newSucceedingSiblingNodeAggregateId | "a"             |
    And The documenturipath projection is up to date
    Then I expect the documenturipath table to contain exactly:
      | uripath | nodeaggregateidpath                       | nodeaggregateid          | parentnodeaggregateid    | precedingnodeaggregateid | succeedingnodeaggregateid | nodetypename                  |
      | ""      | "lady-eleonode-rootford"                  | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"             |
      | ""      | "lady-eleonode-rootford/shernode-homes"   | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Test.Routing.Page" |
      | "a"     | "lady-eleonode-rootford/shernode-homes/a" | "a"                      | "shernode-homes"         | "b"                      | "c"                       | "Neos.Neos:Test.Routing.Page" |
      | "b"     | "lady-eleonode-rootford/shernode-homes/b" | "b"                      | "shernode-homes"         | null                     | "a"                       | "Neos.Neos:Test.Routing.Page" |
      | "c"     | "lady-eleonode-rootford/shernode-homes/c" | "c"                      | "shernode-homes"         | "a"                      | null                      | "Neos.Neos:Test.Routing.Page" |

  Scenario: abc => bac (moving a)
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value           |
      | contentStreamId                     | "cs-identifier" |
      | nodeAggregateId                     | "a"             |
      | dimensionSpacePoint                 | {}              |
      | newParentNodeAggregateId            | null            |
      | newSucceedingSiblingNodeAggregateId | "c"             |
    And The documenturipath projection is up to date
    Then I expect the documenturipath table to contain exactly:
      | uripath | nodeaggregateidpath                       | nodeaggregateid          | parentnodeaggregateid    | precedingnodeaggregateid | succeedingnodeaggregateid | nodetypename                  |
      | ""      | "lady-eleonode-rootford"                  | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"             |
      | ""      | "lady-eleonode-rootford/shernode-homes"   | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Test.Routing.Page" |
      | "a"     | "lady-eleonode-rootford/shernode-homes/a" | "a"                      | "shernode-homes"         | "b"                      | "c"                       | "Neos.Neos:Test.Routing.Page" |
      | "b"     | "lady-eleonode-rootford/shernode-homes/b" | "b"                      | "shernode-homes"         | null                     | "a"                       | "Neos.Neos:Test.Routing.Page" |
      | "c"     | "lady-eleonode-rootford/shernode-homes/c" | "c"                      | "shernode-homes"         | "a"                      | null                      | "Neos.Neos:Test.Routing.Page" |

  Scenario: abc => bca (moving a)
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value           |
      | contentStreamId                     | "cs-identifier" |
      | nodeAggregateId                     | "a"             |
      | dimensionSpacePoint                 | {}              |
      | newParentNodeAggregateId            | null            |
      | newSucceedingSiblingNodeAggregateId | null            |
    And The documenturipath projection is up to date
    Then I expect the documenturipath table to contain exactly:
      | uripath | nodeaggregateidpath                       | nodeaggregateid          | parentnodeaggregateid    | precedingnodeaggregateid | succeedingnodeaggregateid | nodetypename                  |
      | ""      | "lady-eleonode-rootford"                  | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"             |
      | ""      | "lady-eleonode-rootford/shernode-homes"   | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Test.Routing.Page" |
      | "a"     | "lady-eleonode-rootford/shernode-homes/a" | "a"                      | "shernode-homes"         | "c"                      | null                      | "Neos.Neos:Test.Routing.Page" |
      | "b"     | "lady-eleonode-rootford/shernode-homes/b" | "b"                      | "shernode-homes"         | null                     | "c"                       | "Neos.Neos:Test.Routing.Page" |
      | "c"     | "lady-eleonode-rootford/shernode-homes/c" | "c"                      | "shernode-homes"         | "b"                      | "a"                       | "Neos.Neos:Test.Routing.Page" |

  Scenario: abc => bca (moving b and c)
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value           |
      | contentStreamId                     | "cs-identifier" |
      | nodeAggregateId                     | "b"             |
      | dimensionSpacePoint                 | {}              |
      | newParentNodeAggregateId            | null            |
      | newSucceedingSiblingNodeAggregateId | "a"             |
    And the graph projection is fully up to date
    And the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value           |
      | contentStreamId                     | "cs-identifier" |
      | nodeAggregateId                     | "c"             |
      | dimensionSpacePoint                 | {}              |
      | newParentNodeAggregateId            | null            |
      | newSucceedingSiblingNodeAggregateId | "a"             |
    And The documenturipath projection is up to date
    Then I expect the documenturipath table to contain exactly:
      | uripath | nodeaggregateidpath                       | nodeaggregateid          | parentnodeaggregateid    | precedingnodeaggregateid | succeedingnodeaggregateid | nodetypename                  |
      | ""      | "lady-eleonode-rootford"                  | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"             |
      | ""      | "lady-eleonode-rootford/shernode-homes"   | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Test.Routing.Page" |
      | "a"     | "lady-eleonode-rootford/shernode-homes/a" | "a"                      | "shernode-homes"         | "c"                      | null                      | "Neos.Neos:Test.Routing.Page" |
      | "b"     | "lady-eleonode-rootford/shernode-homes/b" | "b"                      | "shernode-homes"         | null                     | "c"                       | "Neos.Neos:Test.Routing.Page" |
      | "c"     | "lady-eleonode-rootford/shernode-homes/c" | "c"                      | "shernode-homes"         | "b"                      | "a"                       | "Neos.Neos:Test.Routing.Page" |

  Scenario: abc => a(> b)c (moving b below a)
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value           |
      | contentStreamId                     | "cs-identifier" |
      | nodeAggregateId                     | "b"             |
      | dimensionSpacePoint                 | {}              |
      | newParentNodeAggregateId            | "a"             |
      | newSucceedingSiblingNodeAggregateId | null            |
    And The documenturipath projection is up to date
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
      | contentStreamId                  | "cs-identifier"               |
      | nodeAggregateId                  | "b1"                          |
      | nodeTypeName                     | "Neos.Neos:Test.Routing.Page" |
      | originDimensionSpacePoint        | {}                            |
      | parentNodeAggregateId            | "b"                           |
      | initialPropertyValues            | {"uriPathSegment": "b1"}      |
      | succeedingSiblingNodeAggregateId | null                          |
    And the graph projection is fully up to date
    And the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value           |
      | contentStreamId                     | "cs-identifier" |
      | nodeAggregateId                     | "b"             |
      | dimensionSpacePoint                 | {}              |
      | newParentNodeAggregateId            | "a"             |
      | newSucceedingSiblingNodeAggregateId | null            |
    And The documenturipath projection is up to date
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
      | contentStreamId                  | "cs-identifier"               |
      | nodeAggregateId                  | "b1"                          |
      | nodeTypeName                     | "Neos.Neos:Test.Routing.Page" |
      | originDimensionSpacePoint        | {}                            |
      | parentNodeAggregateId            | "b"                           |
      | initialPropertyValues            | {"uriPathSegment": "b1"}      |
      | succeedingSiblingNodeAggregateId | null                          |
    And the graph projection is fully up to date
    And the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value           |
      | contentStreamId                     | "cs-identifier" |
      | nodeAggregateId                     | "b1"            |
      | dimensionSpacePoint                 | {}              |
      | newParentNodeAggregateId            | "a"             |
      | newSucceedingSiblingNodeAggregateId | null            |
    And The documenturipath projection is up to date
    Then I expect the documenturipath table to contain exactly:
      | uripath | nodeaggregateidpath                          | nodeaggregateid          | parentnodeaggregateid    | precedingnodeaggregateid | succeedingnodeaggregateid | nodetypename                  |
      | ""      | "lady-eleonode-rootford"                     | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"             |
      | ""      | "lady-eleonode-rootford/shernode-homes"      | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Test.Routing.Page" |
      | "a"     | "lady-eleonode-rootford/shernode-homes/a"    | "a"                      | "shernode-homes"         | null                     | "b"                       | "Neos.Neos:Test.Routing.Page" |
      | "a/b1"  | "lady-eleonode-rootford/shernode-homes/a/b1" | "b1"                     | "a"                      | null                     | null                      | "Neos.Neos:Test.Routing.Page" |
      | "b"     | "lady-eleonode-rootford/shernode-homes/b"    | "b"                      | "shernode-homes"         | "a"                      | "c"                       | "Neos.Neos:Test.Routing.Page" |
      | "c"     | "lady-eleonode-rootford/shernode-homes/c"    | "c"                      | "shernode-homes"         | "b"                      | null                      | "Neos.Neos:Test.Routing.Page" |

  Scenario: ab(> b1, b2 > b2a)c => a(> b2 > b2a)b(> b1)c (moving b1 below a)
    And I am in content stream "cs-identifier" and dimension space point {}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | parentNodeAggregateId | nodeTypeName                | initialPropertyValues     | nodeName |
      | b1              | b                     | Neos.Neos:Test.Routing.Page | {"uriPathSegment": "b1"}  | b1       |
      | b2              | b                     | Neos.Neos:Test.Routing.Page | {"uriPathSegment": "b2"}  | b2       |
      | b2a             | b2                    | Neos.Neos:Test.Routing.Page | {"uriPathSegment": "b2a"} | b2a      |
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value           |
      | contentStreamId                     | "cs-identifier" |
      | nodeAggregateId                     | "b2"            |
      | dimensionSpacePoint                 | {}              |
      | newParentNodeAggregateId            | "a"             |
      | newSucceedingSiblingNodeAggregateId | null            |
    And The documenturipath projection is up to date
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
    And I am in content stream "cs-identifier" and dimension space point {}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | parentNodeAggregateId | nodeTypeName                | initialPropertyValues     | nodeName |
      | b1              | b                     | Neos.Neos:Test.Routing.Page | {"uriPathSegment": "b1"}  | b1       |
      | b2              | b                     | Neos.Neos:Test.Routing.Page | {"uriPathSegment": "b2"}  | b2       |
      | b2a             | b2                    | Neos.Neos:Test.Routing.Page | {"uriPathSegment": "b2a"} | b2a      |
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value           |
      | contentStreamId                     | "cs-identifier" |
      | nodeAggregateId                     | "a"             |
      | dimensionSpacePoint                 | {}              |
      | newParentNodeAggregateId            | "b"             |
      | newSucceedingSiblingNodeAggregateId | "b2"            |
    And The documenturipath projection is up to date
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
    When the command ChangeNodeAggregateType was published with payload:
      | Key             | Value                                  |
      | contentStreamId | "cs-identifier"                        |
      | nodeAggregateId | "c"                                    |
      | newNodeTypeName | "Neos.Neos:Test.Routing.SomeOtherPage" |
      | strategy        | "happypath"                            |
    And The documenturipath projection is up to date
    Then I expect the documenturipath table to contain exactly:
      | uripath | nodeaggregateidpath                       | nodeaggregateid          | parentnodeaggregateid    | precedingnodeaggregateid | succeedingnodeaggregateid | nodetypename                           |
      | ""      | "lady-eleonode-rootford"                  | "lady-eleonode-rootford" | null                     | null                     | null                      | "Neos.Neos:Sites"                      |
      | ""      | "lady-eleonode-rootford/shernode-homes"   | "shernode-homes"         | "lady-eleonode-rootford" | null                     | null                      | "Neos.Neos:Test.Routing.Page"          |
      | "a"     | "lady-eleonode-rootford/shernode-homes/a" | "a"                      | "shernode-homes"         | null                     | "b"                       | "Neos.Neos:Test.Routing.Page"          |
      | "b"     | "lady-eleonode-rootford/shernode-homes/b" | "b"                      | "shernode-homes"         | "a"                      | "c"                       | "Neos.Neos:Test.Routing.Page"          |
      | "c"     | "lady-eleonode-rootford/shernode-homes/c" | "c"                      | "shernode-homes"         | "b"                      | null                      | "Neos.Neos:Test.Routing.SomeOtherPage" |