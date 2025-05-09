@contentrepository @adapters=DoctrineDBAL
@flowEntities
Feature: Move node aggregate with dimensions

  Background:
    Given using the following content dimensions:
      | Identifier | Values    | Generalizations |
      | language   | de,gsw,fr | gsw->de, fr     |
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Node':
      properties:
        text:
          type: string

    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | workspaceTitle       | "Live"               |
      | workspaceDescription | "The live workspace" |
      | newContentStreamId   | "cs-identifier"      |

    And I am in workspace "live"
    And I am in dimension space point {"language": "de"}

    And I am user identified by "initiating-user-identifier"
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |

    Then the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId           | nodeName   | parentNodeAggregateId  | nodeTypeName                        | initialPropertyValues                                      |
      | sir-david-nodenborough    | node       | lady-eleonode-rootford | Neos.ContentRepository.Testing:Node | {}                                                         |
      | nody-mc-nodeface          | child-node | sir-david-nodenborough | Neos.ContentRepository.Testing:Node | {"text": "This is a text about Nody Mc Nodeface"}          |
      | sir-nodeward-nodington-iv | bakura     | lady-eleonode-rootford | Neos.ContentRepository.Testing:Node | {"text": "This is a text about Sir Nodeward Nodington IV"} |
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value              |
      | nodeAggregateId | "nody-mc-nodeface" |
      | sourceOrigin    | {"language":"de"}  |
      | targetOrigin    | {"language":"gsw"} |
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "sir-david-nodenborough" |
      | sourceOrigin    | {"language":"de"}        |
      | targetOrigin    | {"language":"fr"}        |
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value              |
      | nodeAggregateId | "nody-mc-nodeface" |
      | sourceOrigin    | {"language":"de"}  |
      | targetOrigin    | {"language":"fr"}  |
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                       |
      | nodeAggregateId | "sir-nodeward-nodington-iv" |
      | sourceOrigin    | {"language":"de"}           |
      | targetOrigin    | {"language":"fr"}           |




    Then the command CreateWorkspace is executed with payload:
      | Key                | Value            |
      | workspaceName      | "user-workspace" |
      | baseWorkspaceName  | "live"           |
      | newContentStreamId | "user-cs-id"     |

    And I am in workspace "user-workspace"

  Scenario: Move nodeAggregate (variant) into new parent with scatter
    When I am in dimension space point {"language": "fr"}
    And  the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                       |
      | nodeAggregateId              | "sir-nodeward-nodington-iv" |
      | newParentNodeAggregateId     | "nody-mc-nodeface"          |
      | relationDistributionStrategy | "scatter"                   |

    Then I expect to have the following changes in workspace "user-workspace":
      | nodeAggregateId           | created | changed | moved | deleted | originDimensionSpacePoint |
      | sir-nodeward-nodington-iv | 0       | 0       | 1     | 0       | {"language": "fr"}        |
    And I expect to have no changes in workspace "live"

  Scenario: Move nodeAggregate (generalization) into new parent with scatter
    When I am in dimension space point {"language": "de"}
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                       |
      | nodeAggregateId              | "nody-mc-nodeface"          |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iv" |
      | relationDistributionStrategy | "scatter"                   |

    Then I expect to have the following changes in workspace "user-workspace":
      | nodeAggregateId  | created | changed | moved | deleted | originDimensionSpacePoint |
      | nody-mc-nodeface | 0       | 0       | 1     | 0       | {"language": "de"}        |
    And I expect to have no changes in workspace "live"

  Scenario: Move nodeAggregate (specialization) into new parent with scatter
    When I am in dimension space point {"language": "gsw"}
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                       |
      | nodeAggregateId              | "nody-mc-nodeface"          |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iv" |
      | relationDistributionStrategy | "scatter"                   |

    Then I expect to have the following changes in workspace "user-workspace":
      | nodeAggregateId  | created | changed | moved | deleted | originDimensionSpacePoint |
      | nody-mc-nodeface | 0       | 0       | 1     | 0       | {"language": "gsw"}       |
    And I expect to have no changes in workspace "live"

  Scenario: Move nodeAggregate (variant) into new parent with gatherAll
    When I am in dimension space point {"language": "fr"}
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                       |
      | nodeAggregateId              | "nody-mc-nodeface"          |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iv" |
      | relationDistributionStrategy | "gatherAll"                 |

    Then I expect to have the following changes in workspace "user-workspace":
      | nodeAggregateId  | created | changed | moved | deleted | originDimensionSpacePoint |
      | nody-mc-nodeface | 0       | 0       | 1     | 0       | {"language": "fr"}        |
      | nody-mc-nodeface | 0       | 0       | 1     | 0       | {"language": "de"}        |
      | nody-mc-nodeface | 0       | 0       | 1     | 0       | {"language": "gsw"}       |
    And I expect to have no changes in workspace "live"

  Scenario: Move nodeAggregate (specialization) into new parent with gatherAll
    When I am in dimension space point {"language": "gsw"}
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                       |
      | nodeAggregateId              | "nody-mc-nodeface"          |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iv" |
      | relationDistributionStrategy | "gatherAll"                 |

    Then I expect to have the following changes in workspace "user-workspace":
      | nodeAggregateId  | created | changed | moved | deleted | originDimensionSpacePoint |
      | nody-mc-nodeface | 0       | 0       | 1     | 0       | {"language": "de"}        |
      | nody-mc-nodeface | 0       | 0       | 1     | 0       | {"language": "fr"}        |
      | nody-mc-nodeface | 0       | 0       | 1     | 0       | {"language": "gsw"}       |
    And I expect to have no changes in workspace "live"

  Scenario: Move nodeAggregate (generalization) into new parent with gatherAll
    When I am in dimension space point {"language": "de"}
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                       |
      | nodeAggregateId              | "nody-mc-nodeface"          |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iv" |
      | relationDistributionStrategy | "gatherAll"                 |

    Then I expect to have the following changes in workspace "user-workspace":
      | nodeAggregateId  | created | changed | moved | deleted | originDimensionSpacePoint |
      | nody-mc-nodeface | 0       | 0       | 1     | 0       | {"language": "de"}        |
      | nody-mc-nodeface | 0       | 0       | 1     | 0       | {"language": "fr"}        |
      | nody-mc-nodeface | 0       | 0       | 1     | 0       | {"language": "gsw"}       |
    And I expect to have no changes in workspace "live"

  Scenario: Move nodeAggregate (variant) into new parent with gatherSpecializations
    When I am in dimension space point {"language": "fr"}
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                       |
      | nodeAggregateId              | "nody-mc-nodeface"          |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iv" |
      | relationDistributionStrategy | "gatherSpecializations"     |

    Then I expect to have the following changes in workspace "user-workspace":
      | nodeAggregateId  | created | changed | moved | deleted | originDimensionSpacePoint |
      | nody-mc-nodeface | 0       | 0       | 1     | 0       | {"language": "fr"}        |
    And I expect to have no changes in workspace "live"

  Scenario: Move nodeAggregate (specialization) into new parent with gatherSpecializations
    When I am in dimension space point {"language": "gsw"}
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                       |
      | nodeAggregateId              | "nody-mc-nodeface"          |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iv" |
      | relationDistributionStrategy | "gatherSpecializations"     |

    Then I expect to have the following changes in workspace "user-workspace":
      | nodeAggregateId  | created | changed | moved | deleted | originDimensionSpacePoint |
      | nody-mc-nodeface | 0       | 0       | 1     | 0       | {"language": "gsw"}       |
    And I expect to have no changes in workspace "live"

  Scenario: Move nodeAggregate (generalization) into new parent with gatherSpecializations
    When I am in dimension space point {"language": "de"}
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                       |
      | nodeAggregateId              | "nody-mc-nodeface"          |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iv" |
      | relationDistributionStrategy | "gatherSpecializations"     |

    Then I expect to have the following changes in workspace "user-workspace":
      | nodeAggregateId  | created | changed | moved | deleted | originDimensionSpacePoint |
      | nody-mc-nodeface | 0       | 0       | 1     | 0       | {"language": "de"}        |
      | nody-mc-nodeface | 0       | 0       | 1     | 0       | {"language": "gsw"}       |
    And I expect to have no changes in workspace "live"

  Scenario: Move nodeAggregate with children into new parent
    When I am in dimension space point {"language": "de"}
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                       |
      | nodeAggregateId              | "sir-nodeward-nodington-iv" |
      | newParentNodeAggregateId     | "nody-mc-nodeface"          |
      | relationDistributionStrategy | "scatter"                   |

    Then I expect to have the following changes in workspace "user-workspace":
      | nodeAggregateId           | created | changed | moved | deleted | originDimensionSpacePoint |
      | sir-nodeward-nodington-iv | 0       | 0       | 1     | 0       | {"language": "de"}        |
    And I expect to have no changes in workspace "live"

