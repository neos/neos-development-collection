Feature: Tests for soft removal garbage collection without impending conflicts

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
      | nodingers-cat          | sir-david-nodenborough | Neos.Neos:Document |
    And the command CreateWorkspace is executed with payload:
      | Key                | Value            |
      | workspaceName      | "user-workspace" |
      | baseWorkspaceName  | "live"           |
      | newContentStreamId | "user-cs-id"     |

  Scenario: Garbage collection will ignore a soft removal if the node exists unremoved in another workspace
    When the command TagSubtree is executed with payload:
      | Key                          | Value                 |
      | workspaceName                | "live"                |
      | nodeAggregateId              | "nodingers-cat"       |
      | coveredDimensionSpacePoint   | {"example": "source"} |
      | nodeVariantSelectionStrategy | "allSpecializations"  |
      | tag                          | "removed"             |
    And soft removal garbage collection is run for content repository default

    Then I expect exactly 5 events to be published on stream "ContentStream:cs-identifier"
    And event at index 4 is of type "SubtreeWasTagged" with payload:
      | Key                          | Expected                                        |
      | workspaceName                | "live"                                          |
      | contentStreamId              | "cs-identifier"                                 |
      | nodeAggregateId              | "nodingers-cat"                                 |
      | affectedDimensionSpacePoints | [{"example": "source"}, {"example": "special"}] |
      | tag                          | "removed"                                       |

  Scenario: Garbage collection will transform a soft removal of a node that only exists in a root workspace
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                   | Value                    |
      | workspaceName         | "live"                   |
      | nodeAggregateId       | "nonly-lively"           |
      | nodeTypeName          | "Neos.Neos:Document"     |
      | parentNodeAggregateId | "sir-david-nodenborough" |

    When the command TagSubtree is executed with payload:
      | Key                          | Value                 |
      | workspaceName                | "live"                |
      | nodeAggregateId              | "nonly-lively"        |
      | coveredDimensionSpacePoint   | {"example": "source"} |
      | nodeVariantSelectionStrategy | "allSpecializations"  |
      | tag                          | "removed"             |
    And soft removal garbage collection is run for content repository default

    Then I expect exactly 7 events to be published on stream "ContentStream:cs-identifier"
    And event at index 6 is of type "NodeAggregateWasRemoved" with payload:
      | Key                                  | Expected                                        |
      | workspaceName                        | "live"                                          |
      | contentStreamId                      | "cs-identifier"                                 |
      | nodeAggregateId                      | "nonly-lively"                                  |
      | affectedCoveredDimensionSpacePoints  | [{"example": "source"}, {"example": "special"}] |

  Scenario: Garbage collection will transform a soft removal of a node which is published to live from the only other workspace
    And I am in workspace "user-workspace"
    When the command TagSubtree is executed with payload:
      | Key                          | Value                 |
      | nodeAggregateId              | "nodingers-cat"       |
      | coveredDimensionSpacePoint   | {"example": "source"} |
      | nodeVariantSelectionStrategy | "allSpecializations"  |
      | tag                          | "removed"             |

    When the command PublishWorkspace is executed with payload:
      | Key                | Value                      |
      | workspaceName      | "user-workspace"           |
      | newContentStreamId | "new-user-workspace-cs-id" |

    When soft removal garbage collection is run for content repository default
    Then I expect exactly 6 events to be published on stream "ContentStream:cs-identifier"
    And event at index 5 is of type "NodeAggregateWasRemoved" with payload:
      | Key                                  | Expected                                        |
      | workspaceName                        | "live"                                          |
      | contentStreamId                      | "cs-identifier"                                 |
      | nodeAggregateId                      | "nodingers-cat"                                 |
      | affectedCoveredDimensionSpacePoints  | [{"example": "source"}, {"example": "special"}] |

    When the command RebaseWorkspace is executed with payload:
      | Key           | Value            |
      | workspaceName | "user-workspace" |
    # no exceptions must be thrown

  Scenario: Garbage collection will transform nested soft removals of a node that only exists in a root workspace
    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId             | parentNodeAggregateId | nodeTypeName       |
      | nodingers-kitten            | nodingers-cat         | Neos.Neos:Document |
      | nodingers-kittens-plaything | nodingers-kitten      | Neos.Neos:Document |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                         |
      | workspaceName                | "live"                        |
      | nodeAggregateId              | "nodingers-kittens-plaything" |
      | coveredDimensionSpacePoint   | {"example": "source"}         |
      | nodeVariantSelectionStrategy | "allSpecializations"          |
      | tag                          | "removed"                     |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                 |
      | workspaceName                | "live"                |
      | nodeAggregateId              | "nodingers-kitten"    |
      | coveredDimensionSpacePoint   | {"example": "source"} |
      | nodeVariantSelectionStrategy | "allSpecializations"  |
      | tag                          | "removed"             |
    Then I expect the following hard removal conflicts to be impending:
      | nodeAggregateId | dimensionSpacePoints |

    When soft removal garbage collection is run for content repository default
    Then I expect exactly 10 events to be published on stream "ContentStream:cs-identifier"
    And event at index 8 is of type "NodeAggregateWasRemoved" with payload:
      | Key                                  | Expected                                        |
      | workspaceName                        | "live"                                          |
      | contentStreamId                      | "cs-identifier"                                 |
      | nodeAggregateId                      | "nodingers-kittens-plaything"                   |
      | affectedCoveredDimensionSpacePoints  | [{"example": "source"}, {"example": "special"}] |
    And event at index 9 is of type "NodeAggregateWasRemoved" with payload:
      | Key                                  | Expected                                        |
      | workspaceName                        | "live"                                          |
      | contentStreamId                      | "cs-identifier"                                 |
      | nodeAggregateId                      | "nodingers-kitten"                              |
      | affectedCoveredDimensionSpacePoints  | [{"example": "source"}, {"example": "special"}] |

    When the command RebaseWorkspace is executed with payload:
      | Key           | Value            |
      | workspaceName | "user-workspace" |
    # no exceptions must be thrown

  Scenario: Garbage collection will transform nested soft removals of a node that only exists in a root workspace
  by also considering that the parent node might be removed first and skipping the removal of the child

    # we need to have movements in the tree which will lead to the node returned by creation order does
    # not match the inversed hierarchy and we run into the case of deleting the parent first
    When the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId             | parentNodeAggregateId  | nodeTypeName       |
      | nodingers-kittens-plaything | sir-david-nodenborough | Neos.Neos:Document |
      | nodingers-kitten            | nodingers-cat          | Neos.Neos:Document |
    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                         |
      | nodeAggregateId              | "nodingers-kittens-plaything" |
      | dimensionSpacePoint          | {"example": "source"}         |
      | newParentNodeAggregateId     | "nodingers-kitten"            |
      | relationDistributionStrategy | "gatherAll"                   |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                         |
      | workspaceName                | "live"                        |
      | nodeAggregateId              | "nodingers-kittens-plaything" |
      | coveredDimensionSpacePoint   | {"example": "source"}         |
      | nodeVariantSelectionStrategy | "allSpecializations"          |
      | tag                          | "removed"                     |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                 |
      | workspaceName                | "live"                |
      | nodeAggregateId              | "nodingers-kitten"    |
      | coveredDimensionSpacePoint   | {"example": "source"} |
      | nodeVariantSelectionStrategy | "allSpecializations"  |
      | tag                          | "removed"             |
    Then I expect the following hard removal conflicts to be impending:
      | nodeAggregateId | dimensionSpacePoints |

    When soft removal garbage collection is run for content repository default
    Then I expect exactly 10 events to be published on stream "ContentStream:cs-identifier"
    And event at index 9 is of type "NodeAggregateWasRemoved" with payload:
      | Key                                  | Expected                                        |
      | workspaceName                        | "live"                                          |
      | contentStreamId                      | "cs-identifier"                                 |
      | nodeAggregateId                      | "nodingers-kitten"                              |
      | affectedCoveredDimensionSpacePoints  | [{"example": "source"}, {"example": "special"}] |

    # the event for the nested removal is never explicitly emitted; the corresponding command fails and gets caught for now

    When the command RebaseWorkspace is executed with payload:
      | Key           | Value            |
      | workspaceName | "user-workspace" |
    # no exceptions must be thrown

  Scenario: Garbage collection will transform a soft removal of a partially covering node aggregate

    When the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                 |
      | workspaceName                | "live"                |
      | nodeAggregateId              | "nodingers-cat"       |
      | coveredDimensionSpacePoint   | {"example":"special"} |
      | nodeVariantSelectionStrategy | "allSpecializations"  |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                 |
      | workspaceName                | "live"                |
      | nodeAggregateId              | "nodingers-cat"       |
      | coveredDimensionSpacePoint   | {"example": "source"} |
      | nodeVariantSelectionStrategy | "allSpecializations"  |
      | tag                          | "removed"             |
    And the command RebaseWorkspace is executed with payload:
      | Key           | Value            |
      | workspaceName | "user-workspace" |
    Then I expect the following hard removal conflicts to be impending:
      | nodeAggregateId | dimensionSpacePoints |

    When soft removal garbage collection is run for content repository default
    Then I expect exactly 7 events to be published on stream "ContentStream:cs-identifier"
    And event at index 6 is of type "NodeAggregateWasRemoved" with payload:
      | Key                                  | Expected                |
      | workspaceName                        | "live"                  |
      | contentStreamId                      | "cs-identifier"         |
      | nodeAggregateId                      | "nodingers-cat"         |
      | affectedCoveredDimensionSpacePoints  | [{"example": "source"}] |

    When the command RebaseWorkspace is executed with payload:
      | Key           | Value            |
      | workspaceName | "user-workspace" |
    # no exceptions must be thrown
