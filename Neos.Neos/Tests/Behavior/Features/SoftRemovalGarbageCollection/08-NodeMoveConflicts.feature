Feature: Tests for soft removal garbage collection with impending conflicts caused by move

  Background:
    Given using the following content dimensions:
      | Identifier | Values                         | Generalizations                         |
      | example    | general, source, special, peer | special->source->general, peer->general |
    And using the following node types:
    """yaml
    'Neos.ContentRepository:Root': {}
    'Neos.Neos:Sites':
      superTypes:
        'Neos.ContentRepository:Root': true
    'Neos.Neos:Document':
      properties:
        title:
          type: string
        uriPathSegment:
          type: string
      references:
        myReference:
          constraints:
            nodeTypes:
              'Neos.Neos:Document': true
    'Neos.Neos:OtherDocument':
      superTypes:
        'Neos.Neos:Document': true
    'Neos.Neos:Site':
      superTypes:
        'Neos.Neos:Document': true
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"

    When the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live" and dimension space point {"example": "source"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "lady-eleonode-rootford" |
      | nodeTypeName    | "Neos.Neos:Sites"        |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId        | parentNodeAggregateId  | nodeTypeName       |
      | sir-david-nodenborough | lady-eleonode-rootford | Neos.Neos:Site     |
      | nody-mc-nodeface       | sir-david-nodenborough | Neos.Neos:Document |
      | nodingers-cat          | sir-david-nodenborough | Neos.Neos:Document |
      | nodingers-kitten       | nodingers-cat          | Neos.Neos:Document |
    And the command CreateWorkspace is executed with payload:
      | Key                | Value            |
      | workspaceName      | "user-workspace" |
      | baseWorkspaceName  | "live"           |
      | newContentStreamId | "user-cs-id"     |

  Scenario: Garbage collection will ignore a soft removal if the node has an unpublished outbound move in another workspace
    When the command TagSubtree is executed with payload:
      | Key                          | Value                 |
      | workspaceName                | "live"                |
      | nodeAggregateId              | "nodingers-cat"       |
      | coveredDimensionSpacePoint   | {"example": "source"} |
      | nodeVariantSelectionStrategy | "allSpecializations"  |
      | tag                          | "removed"             |

    And I am in workspace "user-workspace"
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | dimensionSpacePoint          | {"example": "source"}    |
      | relationDistributionStrategy | "gatherSpecializations"  |
      | nodeAggregateId              | "nodingers-cat"          |
      | newParentNodeAggregateId     | "lady-eleonode-rootford" |
    And the command RebaseWorkspace is executed with payload:
      | Key           | Value            |
      | workspaceName | "user-workspace" |
    Then I expect the following hard removal conflicts to be impending:
      | nodeAggregateId | dimensionSpacePoints                            |
      | nodingers-cat   | [{"example": "source"}, {"example": "special"}] |

    When soft removal garbage collection is run for content repository default
    Then I expect exactly 7 events to be published on stream "ContentStream:cs-identifier"
    And event at index 6 is of type "SubtreeWasTagged" with payload:
      | Key                          | Expected                                        |
      | workspaceName                | "live"                                          |
      | contentStreamId              | "cs-identifier"                                 |
      | nodeAggregateId              | "nodingers-cat"                                 |
      | affectedDimensionSpacePoints | [{"example": "source"}, {"example": "special"}] |
      | tag                          | "removed"                                       |

  Scenario: Garbage collection will ignore a soft removal if the node has a descendant with an unpublished outbound move in another workspace
    When the command TagSubtree is executed with payload:
      | Key                          | Value                 |
      | workspaceName                | "live"                |
      | nodeAggregateId              | "nodingers-cat"       |
      | coveredDimensionSpacePoint   | {"example": "source"} |
      | nodeVariantSelectionStrategy | "allSpecializations"  |
      | tag                          | "removed"             |

    And I am in workspace "user-workspace"
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | dimensionSpacePoint          | {"example": "source"}    |
      | relationDistributionStrategy | "gatherSpecializations"  |
      | nodeAggregateId              | "nodingers-kitten"       |
      | newParentNodeAggregateId     | "lady-eleonode-rootford" |
    And the command RebaseWorkspace is executed with payload:
      | Key           | Value            |
      | workspaceName | "user-workspace" |
    Then I expect the following hard removal conflicts to be impending:
      | nodeAggregateId | dimensionSpacePoints                            |
      | nodingers-cat   | [{"example": "source"}, {"example": "special"}] |

    When soft removal garbage collection is run for content repository default
    Then I expect exactly 7 events to be published on stream "ContentStream:cs-identifier"
    And event at index 6 is of type "SubtreeWasTagged" with payload:
      | Key                          | Expected                                        |
      | workspaceName                | "live"                                          |
      | contentStreamId              | "cs-identifier"                                 |
      | nodeAggregateId              | "nodingers-cat"                                 |
      | affectedDimensionSpacePoints | [{"example": "source"}, {"example": "special"}] |
      | tag                          | "removed"                                       |

  Scenario: Garbage collection will incrementally transform soft removals in the specialisation first
    if the node has a descendant with an unpublished move only in source dimension
    After cleaning the impending conflict via publish the node in the source dimension is removed

    When the command TagSubtree is executed with payload:
      | Key                          | Value                 |
      | workspaceName                | "live"                |
      | nodeAggregateId              | "nodingers-cat"       |
      | coveredDimensionSpacePoint   | {"example": "source"} |
      | nodeVariantSelectionStrategy | "allSpecializations"  |
      | tag                          | "removed"             |

    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | workspaceName                | "user-workspace"         |
      | dimensionSpacePoint          | {"example": "source"}    |
      | relationDistributionStrategy | "scatter"                |
      | nodeAggregateId              | "nodingers-kitten"       |
      | newParentNodeAggregateId     | "lady-eleonode-rootford" |
    And the command RebaseWorkspace is executed with payload:
      | Key           | Value            |
      | workspaceName | "user-workspace" |
    Then I expect the following hard removal conflicts to be impending:
      | nodeAggregateId | dimensionSpacePoints    |
      | nodingers-cat   | [{"example": "source"}] |

    When soft removal garbage collection is run for content repository default
    Then I expect exactly 8 events to be published on stream "ContentStream:cs-identifier"
    And event at index 7 is of type "NodeAggregateWasRemoved" with payload:
      | Key                                  | Expected                 |
      | workspaceName                        | "live"                   |
      | contentStreamId                      | "cs-identifier"          |
      | nodeAggregateId                      | "nodingers-cat"          |
      | affectedCoveredDimensionSpacePoints  | [{"example": "special"}] |

    When the command PublishWorkspace is executed with payload:
      | Key           | Value            |
      | workspaceName | "user-workspace" |
    Then I expect the following hard removal conflicts to be impending:
      | nodeAggregateId | dimensionSpacePoints |

    When soft removal garbage collection is run for content repository default
    Then I expect exactly 10 events to be published on stream "ContentStream:cs-identifier"
    And event at index 9 is of type "NodeAggregateWasRemoved" with payload:
      | Key                                  | Expected                |
      | workspaceName                        | "live"                  |
      | contentStreamId                      | "cs-identifier"         |
      | nodeAggregateId                      | "nodingers-cat"         |
      | affectedCoveredDimensionSpacePoints  | [{"example": "source"}] |

  Scenario: Garbage collection will ignore a soft removal if the node affects an unpublished inbound move in another workspace
    When the command TagSubtree is executed with payload:
      | Key                          | Value                 |
      | workspaceName                | "live"                |
      | nodeAggregateId              | "nodingers-cat"       |
      | coveredDimensionSpacePoint   | {"example": "source"} |
      | nodeVariantSelectionStrategy | "allSpecializations"  |
      | tag                          | "removed"             |

    And I am in workspace "user-workspace"
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                   |
      | dimensionSpacePoint          | {"example": "source"}   |
      | relationDistributionStrategy | "gatherSpecializations" |
      | nodeAggregateId              | "nody-mc-nodeface"      |
      | newParentNodeAggregateId     | "nodingers-cat"         |
    And the command RebaseWorkspace is executed with payload:
      | Key           | Value            |
      | workspaceName | "user-workspace" |
    Then I expect the following hard removal conflicts to be impending:
      | nodeAggregateId | dimensionSpacePoints                            |
      | nodingers-cat   | [{"example": "source"}, {"example": "special"}] |

    When soft removal garbage collection is run for content repository default
    Then I expect exactly 7 events to be published on stream "ContentStream:cs-identifier"
    And event at index 6 is of type "SubtreeWasTagged" with payload:
      | Key                          | Expected                                        |
      | workspaceName                | "live"                                          |
      | contentStreamId              | "cs-identifier"                                 |
      | nodeAggregateId              | "nodingers-cat"                                 |
      | affectedDimensionSpacePoints | [{"example": "source"}, {"example": "special"}] |
      | tag                          | "removed"                                       |

  Scenario: Garbage collection will ignore a soft removal if one of the node's descendants affects an unpublished inbound move in another workspace
    When the command TagSubtree is executed with payload:
      | Key                          | Value                 |
      | workspaceName                | "live"                |
      | nodeAggregateId              | "nodingers-cat"       |
      | coveredDimensionSpacePoint   | {"example": "source"} |
      | nodeVariantSelectionStrategy | "allSpecializations"  |
      | tag                          | "removed"             |

    And I am in workspace "user-workspace"
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                   |
      | dimensionSpacePoint          | {"example": "source"}   |
      | relationDistributionStrategy | "gatherSpecializations" |
      | nodeAggregateId              | "nody-mc-nodeface"      |
      | newParentNodeAggregateId     | "nodingers-kitten"      |
    And the command RebaseWorkspace is executed with payload:
      | Key           | Value            |
      | workspaceName | "user-workspace" |
    Then I expect the following hard removal conflicts to be impending:
      | nodeAggregateId | dimensionSpacePoints                            |
      | nodingers-cat   | [{"example": "source"}, {"example": "special"}] |

    When soft removal garbage collection is run for content repository default
    Then I expect exactly 7 events to be published on stream "ContentStream:cs-identifier"
    And event at index 6 is of type "SubtreeWasTagged" with payload:
      | Key                          | Expected                                        |
      | workspaceName                | "live"                                          |
      | contentStreamId              | "cs-identifier"                                 |
      | nodeAggregateId              | "nodingers-cat"                                 |
      | affectedDimensionSpacePoints | [{"example": "source"}, {"example": "special"}] |
      | tag                          | "removed"                                       |

  Scenario: Garbage collection will transform a soft removal if only an unrelated move exists in another workspace
    When the command TagSubtree is executed with payload:
      | Key                          | Value                 |
      | workspaceName                | "live"                |
      | nodeAggregateId              | "nodingers-cat"       |
      | coveredDimensionSpacePoint   | {"example": "source"} |
      | nodeVariantSelectionStrategy | "allSpecializations"  |
      | tag                          | "removed"             |

    And I am in workspace "user-workspace"
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | dimensionSpacePoint          | {"example": "source"}    |
      | relationDistributionStrategy | "gatherSpecializations"  |
      | nodeAggregateId              | "nody-mc-nodeface"       |
      | newParentNodeAggregateId     | "lady-eleonode-rootford" |
    And the command RebaseWorkspace is executed with payload:
      | Key           | Value            |
      | workspaceName | "user-workspace" |
    Then I expect the following hard removal conflicts to be impending:
      | nodeAggregateId | dimensionSpacePoints |

    When soft removal garbage collection is run for content repository default
    Then I expect exactly 8 events to be published on stream "ContentStream:cs-identifier"
    And event at index 7 is of type "NodeAggregateWasRemoved" with payload:
      | Key                                  | Expected                                        |
      | workspaceName                        | "live"                                          |
      | contentStreamId                      | "cs-identifier"                                 |
      | nodeAggregateId                      | "nodingers-cat"                                 |
      | affectedCoveredDimensionSpacePoints  | [{"example": "source"}, {"example": "special"}] |

    When the command RebaseWorkspace is executed with payload:
      | Key           | Value            |
      | workspaceName | "user-workspace" |
    # no exceptions must be thrown

  Scenario: Garbage collection will transform a soft removal if only a move in an unrelated dimension space point exists in another workspace
    When the command CreateNodeVariant is executed with payload:
      | Key             | Value                    |
      | workspaceName   | "live"                   |
      | nodeAggregateId | "sir-david-nodenborough" |
      | sourceOrigin    | {"example": "source"}    |
      | targetOrigin    | {"example": "peer"}      |
    When the command CreateNodeVariant is executed with payload:
      | Key             | Value                 |
      | workspaceName   | "live"                |
      | nodeAggregateId | "nody-mc-nodeface"    |
      | sourceOrigin    | {"example": "source"} |
      | targetOrigin    | {"example": "peer"}   |
    When the command CreateNodeVariant is executed with payload:
      | Key             | Value                 |
      | workspaceName   | "live"                |
      | nodeAggregateId | "nodingers-cat"       |
      | sourceOrigin    | {"example": "source"} |
      | targetOrigin    | {"example": "peer"}   |
    When the command CreateNodeVariant is executed with payload:
      | Key             | Value                 |
      | workspaceName   | "live"                |
      | nodeAggregateId | "nodingers-kitten"    |
      | sourceOrigin    | {"example": "source"} |
      | targetOrigin    | {"example": "peer"}   |
    And the command RebaseWorkspace is executed with payload:
      | Key           | Value            |
      | workspaceName | "user-workspace" |
    When the command TagSubtree is executed with payload:
      | Key                          | Value                 |
      | workspaceName                | "live"                |
      | nodeAggregateId              | "nodingers-cat"       |
      | coveredDimensionSpacePoint   | {"example": "source"} |
      | nodeVariantSelectionStrategy | "allSpecializations"  |
      | tag                          | "removed"             |

    And I am in workspace "user-workspace"
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                   |
      | dimensionSpacePoint          | {"example": "peer"}     |
      | relationDistributionStrategy | "gatherSpecializations" |
      | nodeAggregateId              | "nody-mc-nodeface"      |
      | newParentNodeAggregateId     | "nodingers-cat"         |
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | dimensionSpacePoint          | {"example": "peer"}      |
      | relationDistributionStrategy | "gatherSpecializations"  |
      | nodeAggregateId              | "nodingers-kitten"       |
      | newParentNodeAggregateId     | "sir-david-nodenborough" |
    And the command RebaseWorkspace is executed with payload:
      | Key           | Value            |
      | workspaceName | "user-workspace" |
    Then I expect the following hard removal conflicts to be impending:
      | nodeAggregateId | dimensionSpacePoints |

    When soft removal garbage collection is run for content repository default
    Then I expect exactly 12 events to be published on stream "ContentStream:cs-identifier"
    And event at index 11 is of type "NodeAggregateWasRemoved" with payload:
      | Key                                  | Expected                                        |
      | workspaceName                        | "live"                                          |
      | contentStreamId                      | "cs-identifier"                                 |
      | nodeAggregateId                      | "nodingers-cat"                                 |
      | affectedCoveredDimensionSpacePoints  | [{"example": "source"}, {"example": "special"}] |

    When the command RebaseWorkspace is executed with payload:
      | Key           | Value            |
      | workspaceName | "user-workspace" |
    # no exceptions must be thrown
