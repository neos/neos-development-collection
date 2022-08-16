@fixtures @contentrepository
  # Note: For the routing tests to work we rely on Configuration/Testing/Behat/NodeTypes.Test.Routing.yaml
Feature: Low level tests covering the inner behavior of the routing projection

  Background:
    Given I have no content dimensions
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value           |
      | workspaceName              | "live"          |
      | newContentStreamIdentifier | "cs-identifier" |
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                        |
      | contentStreamIdentifier     | "cs-identifier"              |
      | nodeAggregateIdentifier     | "lady-eleonode-rootford"     |
      | nodeTypeName                | "Neos.Neos:Sites"            |
      | coveredDimensionSpacePoints | [{}]                         |
      | initiatingUserIdentifier    | "initiating-user-identifier" |
      | nodeAggregateClassification | "root"                       |
    And the graph projection is fully up to date

    And I am in content stream "cs-identifier" and dimension space point {}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateIdentifier | parentNodeAggregateIdentifier | nodeTypeName                                       | initialPropertyValues           | nodeName |
      | shernode-homes          | lady-eleonode-rootford        | Neos.EventSourcedNeosAdjustments:Test.Routing.Page | {"uriPathSegment": "ignore-me"} | site     |
      | a                       | shernode-homes                | Neos.EventSourcedNeosAdjustments:Test.Routing.Page | {"uriPathSegment": "a"}         | a        |
      | b                       | shernode-homes                | Neos.EventSourcedNeosAdjustments:Test.Routing.Page | {"uriPathSegment": "b"}         | b        |
      | c                       | shernode-homes                | Neos.EventSourcedNeosAdjustments:Test.Routing.Page | {"uriPathSegment": "c"}         | c        |
    And A site exists for node name "site"
    And The documenturipath projection is up to date

  Scenario: initial state
    Then I expect the documenturipath table to contain exactly:
      | uripath | nodeaggregateidentifierpath               | nodeaggregateidentifier  | parentnodeaggregateidentifier | precedingnodeaggregateidentifier | succeedingnodeaggregateidentifier |
      | ""      | "lady-eleonode-rootford"                  | "lady-eleonode-rootford" | null                          | null                             | null                              |
      | ""      | "lady-eleonode-rootford/shernode-homes"   | "shernode-homes"         | "lady-eleonode-rootford"      | null                             | null                              |
      | "a"     | "lady-eleonode-rootford/shernode-homes/a" | "a"                      | "shernode-homes"              | null                             | "b"                               |
      | "b"     | "lady-eleonode-rootford/shernode-homes/b" | "b"                      | "shernode-homes"              | "a"                              | "c"                               |
      | "c"     | "lady-eleonode-rootford/shernode-homes/c" | "c"                      | "shernode-homes"              | "b"                              | null                              |

  Scenario: abc => acb (moving b)
    When the command MoveNodeAggregate is executed with payload:
      | Key                                         | Value           |
      | contentStreamIdentifier                     | "cs-identifier" |
      | nodeAggregateIdentifier                     | "b"             |
      | dimensionSpacePoint                         | {}              |
      | newParentNodeAggregateIdentifier            | null            |
      | newSucceedingSiblingNodeAggregateIdentifier | null            |
    And The documenturipath projection is up to date
    Then I expect the documenturipath table to contain exactly:
      | uripath | nodeaggregateidentifierpath               | nodeaggregateidentifier  | parentnodeaggregateidentifier | precedingnodeaggregateidentifier | succeedingnodeaggregateidentifier |
      | ""      | "lady-eleonode-rootford"                  | "lady-eleonode-rootford" | null                          | null                             | null                              |
      | ""      | "lady-eleonode-rootford/shernode-homes"   | "shernode-homes"         | "lady-eleonode-rootford"      | null                             | null                              |
      | "a"     | "lady-eleonode-rootford/shernode-homes/a" | "a"                      | "shernode-homes"              | null                             | "c"                               |
      | "b"     | "lady-eleonode-rootford/shernode-homes/b" | "b"                      | "shernode-homes"              | "c"                              | null                              |
      | "c"     | "lady-eleonode-rootford/shernode-homes/c" | "c"                      | "shernode-homes"              | "a"                              | "b"                               |

  Scenario: abc => acb (moving c)
    When the command MoveNodeAggregate is executed with payload:
      | Key                                         | Value           |
      | contentStreamIdentifier                     | "cs-identifier" |
      | nodeAggregateIdentifier                     | "c"             |
      | dimensionSpacePoint                         | {}              |
      | newParentNodeAggregateIdentifier            | null            |
      | newSucceedingSiblingNodeAggregateIdentifier | "b"             |
    And The documenturipath projection is up to date
    Then I expect the documenturipath table to contain exactly:
      | uripath | nodeaggregateidentifierpath               | nodeaggregateidentifier  | parentnodeaggregateidentifier | precedingnodeaggregateidentifier | succeedingnodeaggregateidentifier |
      | ""      | "lady-eleonode-rootford"                  | "lady-eleonode-rootford" | null                          | null                             | null                              |
      | ""      | "lady-eleonode-rootford/shernode-homes"   | "shernode-homes"         | "lady-eleonode-rootford"      | null                             | null                              |
      | "a"     | "lady-eleonode-rootford/shernode-homes/a" | "a"                      | "shernode-homes"              | null                             | "c"                               |
      | "b"     | "lady-eleonode-rootford/shernode-homes/b" | "b"                      | "shernode-homes"              | "c"                              | null                              |
      | "c"     | "lady-eleonode-rootford/shernode-homes/c" | "c"                      | "shernode-homes"              | "a"                              | "b"                               |

  Scenario: abc => bac (moving b)
    When the command MoveNodeAggregate is executed with payload:
      | Key                                         | Value           |
      | contentStreamIdentifier                     | "cs-identifier" |
      | nodeAggregateIdentifier                     | "b"             |
      | dimensionSpacePoint                         | {}              |
      | newParentNodeAggregateIdentifier            | null            |
      | newSucceedingSiblingNodeAggregateIdentifier | "a"             |
    And The documenturipath projection is up to date
    Then I expect the documenturipath table to contain exactly:
      | uripath | nodeaggregateidentifierpath               | nodeaggregateidentifier  | parentnodeaggregateidentifier | precedingnodeaggregateidentifier | succeedingnodeaggregateidentifier |
      | ""      | "lady-eleonode-rootford"                  | "lady-eleonode-rootford" | null                          | null                             | null                              |
      | ""      | "lady-eleonode-rootford/shernode-homes"   | "shernode-homes"         | "lady-eleonode-rootford"      | null                             | null                              |
      | "a"     | "lady-eleonode-rootford/shernode-homes/a" | "a"                      | "shernode-homes"              | "b"                              | "c"                               |
      | "b"     | "lady-eleonode-rootford/shernode-homes/b" | "b"                      | "shernode-homes"              | null                             | "a"                               |
      | "c"     | "lady-eleonode-rootford/shernode-homes/c" | "c"                      | "shernode-homes"              | "a"                              | null                              |

  Scenario: abc => bac (moving a)
    When the command MoveNodeAggregate is executed with payload:
      | Key                                         | Value           |
      | contentStreamIdentifier                     | "cs-identifier" |
      | nodeAggregateIdentifier                     | "a"             |
      | dimensionSpacePoint                         | {}              |
      | newParentNodeAggregateIdentifier            | null            |
      | newSucceedingSiblingNodeAggregateIdentifier | "c"             |
    And The documenturipath projection is up to date
    Then I expect the documenturipath table to contain exactly:
      | uripath | nodeaggregateidentifierpath               | nodeaggregateidentifier  | parentnodeaggregateidentifier | precedingnodeaggregateidentifier | succeedingnodeaggregateidentifier |
      | ""      | "lady-eleonode-rootford"                  | "lady-eleonode-rootford" | null                          | null                             | null                              |
      | ""      | "lady-eleonode-rootford/shernode-homes"   | "shernode-homes"         | "lady-eleonode-rootford"      | null                             | null                              |
      | "a"     | "lady-eleonode-rootford/shernode-homes/a" | "a"                      | "shernode-homes"              | "b"                              | "c"                               |
      | "b"     | "lady-eleonode-rootford/shernode-homes/b" | "b"                      | "shernode-homes"              | null                             | "a"                               |
      | "c"     | "lady-eleonode-rootford/shernode-homes/c" | "c"                      | "shernode-homes"              | "a"                              | null                              |

  Scenario: abc => bca (moving a)
    When the command MoveNodeAggregate is executed with payload:
      | Key                                         | Value           |
      | contentStreamIdentifier                     | "cs-identifier" |
      | nodeAggregateIdentifier                     | "a"             |
      | dimensionSpacePoint                         | {}              |
      | newParentNodeAggregateIdentifier            | null            |
      | newSucceedingSiblingNodeAggregateIdentifier | null            |
    And The documenturipath projection is up to date
    Then I expect the documenturipath table to contain exactly:
      | uripath | nodeaggregateidentifierpath               | nodeaggregateidentifier  | parentnodeaggregateidentifier | precedingnodeaggregateidentifier | succeedingnodeaggregateidentifier |
      | ""      | "lady-eleonode-rootford"                  | "lady-eleonode-rootford" | null                          | null                             | null                              |
      | ""      | "lady-eleonode-rootford/shernode-homes"   | "shernode-homes"         | "lady-eleonode-rootford"      | null                             | null                              |
      | "a"     | "lady-eleonode-rootford/shernode-homes/a" | "a"                      | "shernode-homes"              | "c"                              | null                              |
      | "b"     | "lady-eleonode-rootford/shernode-homes/b" | "b"                      | "shernode-homes"              | null                             | "c"                               |
      | "c"     | "lady-eleonode-rootford/shernode-homes/c" | "c"                      | "shernode-homes"              | "b"                              | "a"                               |

  Scenario: abc => bca (moving b and c)
    When the command MoveNodeAggregate is executed with payload:
      | Key                                         | Value           |
      | contentStreamIdentifier                     | "cs-identifier" |
      | nodeAggregateIdentifier                     | "b"             |
      | dimensionSpacePoint                         | {}              |
      | newParentNodeAggregateIdentifier            | null            |
      | newSucceedingSiblingNodeAggregateIdentifier | "a"             |
    And the graph projection is fully up to date
    And the command MoveNodeAggregate is executed with payload:
      | Key                                         | Value           |
      | contentStreamIdentifier                     | "cs-identifier" |
      | nodeAggregateIdentifier                     | "c"             |
      | dimensionSpacePoint                         | {}              |
      | newParentNodeAggregateIdentifier            | null            |
      | newSucceedingSiblingNodeAggregateIdentifier | "a"             |
    And The documenturipath projection is up to date
    Then I expect the documenturipath table to contain exactly:
      | uripath | nodeaggregateidentifierpath               | nodeaggregateidentifier  | parentnodeaggregateidentifier | precedingnodeaggregateidentifier | succeedingnodeaggregateidentifier |
      | ""      | "lady-eleonode-rootford"                  | "lady-eleonode-rootford" | null                          | null                             | null                              |
      | ""      | "lady-eleonode-rootford/shernode-homes"   | "shernode-homes"         | "lady-eleonode-rootford"      | null                             | null                              |
      | "a"     | "lady-eleonode-rootford/shernode-homes/a" | "a"                      | "shernode-homes"              | "c"                              | null                              |
      | "b"     | "lady-eleonode-rootford/shernode-homes/b" | "b"                      | "shernode-homes"              | null                             | "c"                               |
      | "c"     | "lady-eleonode-rootford/shernode-homes/c" | "c"                      | "shernode-homes"              | "b"                              | "a"                               |

  Scenario: abc => a(> b)c (moving b below a)
    When the command MoveNodeAggregate is executed with payload:
      | Key                                         | Value           |
      | contentStreamIdentifier                     | "cs-identifier" |
      | nodeAggregateIdentifier                     | "b"             |
      | dimensionSpacePoint                         | {}              |
      | newParentNodeAggregateIdentifier            | "a"             |
      | newSucceedingSiblingNodeAggregateIdentifier | null            |
    And The documenturipath projection is up to date
    Then I expect the documenturipath table to contain exactly:
      | uripath | nodeaggregateidentifierpath                 | nodeaggregateidentifier  | parentnodeaggregateidentifier | precedingnodeaggregateidentifier | succeedingnodeaggregateidentifier |
      | ""      | "lady-eleonode-rootford"                    | "lady-eleonode-rootford" | null                          | null                             | null                              |
      | ""      | "lady-eleonode-rootford/shernode-homes"     | "shernode-homes"         | "lady-eleonode-rootford"      | null                             | null                              |
      | "a"     | "lady-eleonode-rootford/shernode-homes/a"   | "a"                      | "shernode-homes"              | null                             | "c"                               |
      | "a/b"   | "lady-eleonode-rootford/shernode-homes/a/b" | "b"                      | "a"                           | null                             | null                              |
      | "c"     | "lady-eleonode-rootford/shernode-homes/c"   | "c"                      | "shernode-homes"              | "a"                              | null                              |

  Scenario: ab(> b1)c => a(> b > b1)c (moving b & b1 below a)
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                                      | Value                                                |
      | contentStreamIdentifier                  | "cs-identifier"                                      |
      | nodeAggregateIdentifier                  | "b1"                                                 |
      | nodeTypeName                             | "Neos.EventSourcedNeosAdjustments:Test.Routing.Page" |
      | originDimensionSpacePoint                | {}                                                   |
      | parentNodeAggregateIdentifier            | "b"                                                  |
      | initialPropertyValues                    | {"uriPathSegment": "b1"}                             |
      | succeedingSiblingNodeAggregateIdentifier | null                                                 |
    And the graph projection is fully up to date
    And the command MoveNodeAggregate is executed with payload:
      | Key                                         | Value           |
      | contentStreamIdentifier                     | "cs-identifier" |
      | nodeAggregateIdentifier                     | "b"             |
      | dimensionSpacePoint                         | {}              |
      | newParentNodeAggregateIdentifier            | "a"             |
      | newSucceedingSiblingNodeAggregateIdentifier | null            |
    And The documenturipath projection is up to date
    Then I expect the documenturipath table to contain exactly:
      | uripath  | nodeaggregateidentifierpath                    | nodeaggregateidentifier  | parentnodeaggregateidentifier | precedingnodeaggregateidentifier | succeedingnodeaggregateidentifier |
      | ""       | "lady-eleonode-rootford"                       | "lady-eleonode-rootford" | null                          | null                             | null                              |
      | ""       | "lady-eleonode-rootford/shernode-homes"        | "shernode-homes"         | "lady-eleonode-rootford"      | null                             | null                              |
      | "a"      | "lady-eleonode-rootford/shernode-homes/a"      | "a"                      | "shernode-homes"              | null                             | "c"                               |
      | "a/b"    | "lady-eleonode-rootford/shernode-homes/a/b"    | "b"                      | "a"                           | null                             | null                              |
      | "a/b/b1" | "lady-eleonode-rootford/shernode-homes/a/b/b1" | "b1"                     | "b"                           | null                             | null                              |
      | "c"      | "lady-eleonode-rootford/shernode-homes/c"      | "c"                      | "shernode-homes"              | "a"                              | null                              |

  Scenario: ab(> b1)c => a(> b1)bc (moving b1 below a)
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                                      | Value                                                |
      | contentStreamIdentifier                  | "cs-identifier"                                      |
      | nodeAggregateIdentifier                  | "b1"                                                 |
      | nodeTypeName                             | "Neos.EventSourcedNeosAdjustments:Test.Routing.Page" |
      | originDimensionSpacePoint                | {}                                                   |
      | parentNodeAggregateIdentifier            | "b"                                                  |
      | initialPropertyValues                    | {"uriPathSegment": "b1"}                             |
      | succeedingSiblingNodeAggregateIdentifier | null                                                 |
    And the graph projection is fully up to date
    And the command MoveNodeAggregate is executed with payload:
      | Key                                         | Value           |
      | contentStreamIdentifier                     | "cs-identifier" |
      | nodeAggregateIdentifier                     | "b1"            |
      | dimensionSpacePoint                         | {}              |
      | newParentNodeAggregateIdentifier            | "a"             |
      | newSucceedingSiblingNodeAggregateIdentifier | null            |
    And The documenturipath projection is up to date
    Then I expect the documenturipath table to contain exactly:
      | uripath | nodeaggregateidentifierpath                  | nodeaggregateidentifier  | parentnodeaggregateidentifier | precedingnodeaggregateidentifier | succeedingnodeaggregateidentifier |
      | ""      | "lady-eleonode-rootford"                     | "lady-eleonode-rootford" | null                          | null                             | null                              |
      | ""      | "lady-eleonode-rootford/shernode-homes"      | "shernode-homes"         | "lady-eleonode-rootford"      | null                             | null                              |
      | "a"     | "lady-eleonode-rootford/shernode-homes/a"    | "a"                      | "shernode-homes"              | null                             | "b"                               |
      | "a/b1"  | "lady-eleonode-rootford/shernode-homes/a/b1" | "b1"                     | "a"                           | null                             | null                              |
      | "b"     | "lady-eleonode-rootford/shernode-homes/b"    | "b"                      | "shernode-homes"              | "a"                              | "c"                               |
      | "c"     | "lady-eleonode-rootford/shernode-homes/c"    | "c"                      | "shernode-homes"              | "b"                              | null                              |

  Scenario: ab(> b1, b2 > b2a)c => a(> b2 > b2a)b(> b1)c (moving b1 below a)
    And I am in content stream "cs-identifier" and dimension space point {}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateIdentifier | parentNodeAggregateIdentifier | nodeTypeName                                       | initialPropertyValues     | nodeName |
      | b1                      | b                             | Neos.EventSourcedNeosAdjustments:Test.Routing.Page | {"uriPathSegment": "b1"}  | b1       |
      | b2                      | b                             | Neos.EventSourcedNeosAdjustments:Test.Routing.Page | {"uriPathSegment": "b2"}  | b2       |
      | b2a                     | b2                            | Neos.EventSourcedNeosAdjustments:Test.Routing.Page | {"uriPathSegment": "b2a"} | b2a      |
    When the command MoveNodeAggregate is executed with payload:
      | Key                                         | Value           |
      | contentStreamIdentifier                     | "cs-identifier" |
      | nodeAggregateIdentifier                     | "b2"            |
      | dimensionSpacePoint                         | {}              |
      | newParentNodeAggregateIdentifier            | "a"             |
      | newSucceedingSiblingNodeAggregateIdentifier | null            |
    And The documenturipath projection is up to date
    Then I expect the documenturipath table to contain exactly:
      | uripath    | nodeaggregateidentifierpath                      | nodeaggregateidentifier  | parentnodeaggregateidentifier | precedingnodeaggregateidentifier | succeedingnodeaggregateidentifier |
      | ""         | "lady-eleonode-rootford"                         | "lady-eleonode-rootford" | null                          | null                             | null                              |
      | ""         | "lady-eleonode-rootford/shernode-homes"          | "shernode-homes"         | "lady-eleonode-rootford"      | null                             | null                              |
      | "a"        | "lady-eleonode-rootford/shernode-homes/a"        | "a"                      | "shernode-homes"              | null                             | "b"                               |
      | "a/b2"     | "lady-eleonode-rootford/shernode-homes/a/b2"     | "b2"                     | "a"                           | null                             | null                              |
      | "a/b2/b2a" | "lady-eleonode-rootford/shernode-homes/a/b2/b2a" | "b2a"                    | "b2"                          | null                             | null                              |
      | "b"        | "lady-eleonode-rootford/shernode-homes/b"        | "b"                      | "shernode-homes"              | "a"                              | "c"                               |
      | "b/b1"     | "lady-eleonode-rootford/shernode-homes/b/b1"     | "b1"                     | "b"                           | null                             | null                              |
      | "c"        | "lady-eleonode-rootford/shernode-homes/c"        | "c"                      | "shernode-homes"              | "b"                              | null                              |

  Scenario: ab(> b1, b2 > b2a)c => b(> b1, a, b2 > b2a)c (moving a below b)
    And I am in content stream "cs-identifier" and dimension space point {}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateIdentifier | parentNodeAggregateIdentifier | nodeTypeName                                       | initialPropertyValues     | nodeName |
      | b1                      | b                             | Neos.EventSourcedNeosAdjustments:Test.Routing.Page | {"uriPathSegment": "b1"}  | b1       |
      | b2                      | b                             | Neos.EventSourcedNeosAdjustments:Test.Routing.Page | {"uriPathSegment": "b2"}  | b2       |
      | b2a                     | b2                            | Neos.EventSourcedNeosAdjustments:Test.Routing.Page | {"uriPathSegment": "b2a"} | b2a      |
    When the command MoveNodeAggregate is executed with payload:
      | Key                                         | Value           |
      | contentStreamIdentifier                     | "cs-identifier" |
      | nodeAggregateIdentifier                     | "a"             |
      | dimensionSpacePoint                         | {}              |
      | newParentNodeAggregateIdentifier            | "b"             |
      | newSucceedingSiblingNodeAggregateIdentifier | "b2"            |
    And The documenturipath projection is up to date
    Then I expect the documenturipath table to contain exactly:
      | uripath    | nodeaggregateidentifierpath                      | nodeaggregateidentifier  | parentnodeaggregateidentifier | precedingnodeaggregateidentifier | succeedingnodeaggregateidentifier |
      | ""         | "lady-eleonode-rootford"                         | "lady-eleonode-rootford" | null                          | null                             | null                              |
      | ""         | "lady-eleonode-rootford/shernode-homes"          | "shernode-homes"         | "lady-eleonode-rootford"      | null                             | null                              |
      | "b"        | "lady-eleonode-rootford/shernode-homes/b"        | "b"                      | "shernode-homes"              | null                             | "c"                               |
      | "b/a"      | "lady-eleonode-rootford/shernode-homes/b/a"      | "a"                      | "b"                           | "b1"                             | "b2"                              |
      | "b/b1"     | "lady-eleonode-rootford/shernode-homes/b/b1"     | "b1"                     | "b"                           | null                             | "a"                               |
      | "b/b2"     | "lady-eleonode-rootford/shernode-homes/b/b2"     | "b2"                     | "b"                           | "a"                              | null                              |
      | "b/b2/b2a" | "lady-eleonode-rootford/shernode-homes/b/b2/b2a" | "b2a"                    | "b2"                          | null                             | null                              |
      | "c"        | "lady-eleonode-rootford/shernode-homes/c"        | "c"                      | "shernode-homes"              | "b"                              | null                              |
