Feature: Tests for soft removal garbage collection with impending conflicts caused by node referencing

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
      | nodingers-cat          | sir-david-nodenborough | Neos.Neos:Document |
      | nodingers-kitten       | nodingers-cat          | Neos.Neos:Document |
    And the command CreateWorkspace is executed with payload:
      | Key                | Value            |
      | workspaceName      | "user-workspace" |
      | baseWorkspaceName  | "live"           |
      | newContentStreamId | "user-cs-id"     |

  Scenario: Garbage collection will ignore a soft removal in dimension if the node has unpublished reference changes by source in another workspace
    When the command TagSubtree is executed with payload:
      | Key                          | Value                 |
      | workspaceName                | "live"                |
      | nodeAggregateId              | "nodingers-cat"       |
      | coveredDimensionSpacePoint   | {"example": "source"} |
      | nodeVariantSelectionStrategy | "allSpecializations"  |
      | tag                          | "removed"             |
    And I am in workspace "user-workspace"
    And the command SetNodeReferences is executed with payload:
      | Key                       | Value                                                                                    |
      | sourceNodeAggregateId     | "nodingers-cat"                                                                          |
      | originDimensionSpacePoint | {"example": "source"}                                                                    |
      | references                | [{"referenceName": "myReference", "references": [{"target": "sir-david-nodenborough"}]}] |
    And the command RebaseWorkspace is executed with payload:
      | Key           | Value            |
      | workspaceName | "user-workspace" |
    Then I expect the following hard removal conflicts to be impending:
      | nodeAggregateId | dimensionSpacePoints                            |
      | nodingers-cat   | [{"example": "source"}, {"example": "special"}] |

    When soft removal garbage collection is run for content repository default
    Then I expect exactly 6 events to be published on stream "ContentStream:cs-identifier"
    And event at index 5 is of type "SubtreeWasTagged" with payload:
      | Key                          | Expected                                        |
      | workspaceName                | "live"                                          |
      | contentStreamId              | "cs-identifier"                                 |
      | nodeAggregateId              | "nodingers-cat"                                 |
      | affectedDimensionSpacePoints | [{"example": "source"}, {"example": "special"}] |
      | tag                          | "removed"                                       |

  Scenario: Garbage collection will ignore a soft removal in dimension if one of the node's descendants has unpublished reference changes by source in another workspace
    When the command TagSubtree is executed with payload:
      | Key                          | Value                 |
      | workspaceName                | "live"                |
      | nodeAggregateId              | "nodingers-cat"       |
      | coveredDimensionSpacePoint   | {"example": "source"} |
      | nodeVariantSelectionStrategy | "allSpecializations"  |
      | tag                          | "removed"             |

    And I am in workspace "user-workspace"
    And the command SetNodeReferences is executed with payload:
      | Key                             | Value                                                                                    |
      | workspaceName                   | "user-workspace"                                                                         |
      | sourceNodeAggregateId           | "nodingers-kitten"                                                                       |
      | sourceOriginDimensionSpacePoint | {"example": "source"}                                                                    |
      | references                      | [{"referenceName": "myReference", "references": [{"target": "sir-david-nodenborough"}]}] |
    And the command RebaseWorkspace is executed with payload:
      | Key           | Value            |
      | workspaceName | "user-workspace" |
    Then I expect the following hard removal conflicts to be impending:
      | nodeAggregateId | dimensionSpacePoints                            |
      | nodingers-cat   | [{"example": "source"}, {"example": "special"}] |

    When soft removal garbage collection is run for content repository default
    Then I expect exactly 6 events to be published on stream "ContentStream:cs-identifier"
    And event at index 5 is of type "SubtreeWasTagged" with payload:
      | Key                          | Expected                                        |
      | workspaceName                | "live"                                          |
      | contentStreamId              | "cs-identifier"                                 |
      | nodeAggregateId              | "nodingers-cat"                                 |
      | affectedDimensionSpacePoints | [{"example": "source"}, {"example": "special"}] |
      | tag                          | "removed"                                       |

  Scenario: Garbage collection will ignore a soft removal in dimension if the node has unpublished reference changes by target in another workspace
    When the command TagSubtree is executed with payload:
      | Key                          | Value                 |
      | workspaceName                | "live"                |
      | nodeAggregateId              | "nodingers-cat"       |
      | coveredDimensionSpacePoint   | {"example": "source"} |
      | nodeVariantSelectionStrategy | "allSpecializations"  |
      | tag                          | "removed"             |
    And I am in workspace "user-workspace"
    And the command SetNodeReferences is executed with payload:
      | Key                       | Value                                                                           |
      | sourceNodeAggregateId     | "sir-david-nodenborough"                                                        |
      | originDimensionSpacePoint | {"example": "source"}                                                           |
      | references                | [{"referenceName": "myReference", "references": [{"target": "nodingers-cat"}]}] |
    And the command RebaseWorkspace is executed with payload:
      | Key           | Value            |
      | workspaceName | "user-workspace" |
    Then I expect the following hard removal conflicts to be impending:
      | nodeAggregateId | dimensionSpacePoints                            |
      | nodingers-cat   | [{"example": "source"}, {"example": "special"}] |

    When soft removal garbage collection is run for content repository default
    Then I expect exactly 6 events to be published on stream "ContentStream:cs-identifier"
    And event at index 5 is of type "SubtreeWasTagged" with payload:
      | Key                          | Expected                                        |
      | workspaceName                | "live"                                          |
      | contentStreamId              | "cs-identifier"                                 |
      | nodeAggregateId              | "nodingers-cat"                                 |
      | affectedDimensionSpacePoints | [{"example": "source"}, {"example": "special"}] |
      | tag                          | "removed"                                       |

  Scenario: Garbage collection will ignore a soft removal in dimension if one of the node's descendants has unpublished reference changes by target in another workspace
    When the command TagSubtree is executed with payload:
      | Key                          | Value                 |
      | workspaceName                | "live"                |
      | nodeAggregateId              | "nodingers-cat"       |
      | coveredDimensionSpacePoint   | {"example": "source"} |
      | nodeVariantSelectionStrategy | "allSpecializations"  |
      | tag                          | "removed"             |

    And I am in workspace "user-workspace"
    And the command SetNodeReferences is executed with payload:
      | Key                             | Value                                                                              |
      | workspaceName                   | "user-workspace"                                                                   |
      | sourceNodeAggregateId           | "sir-david-nodenborough"                                                           |
      | sourceOriginDimensionSpacePoint | {"example": "source"}                                                              |
      | references                      | [{"referenceName": "myReference", "references": [{"target": "nodingers-kitten"}]}] |
    And the command RebaseWorkspace is executed with payload:
      | Key           | Value            |
      | workspaceName | "user-workspace" |
    Then I expect the following hard removal conflicts to be impending:
      | nodeAggregateId | dimensionSpacePoints                            |
      | nodingers-cat   | [{"example": "source"}, {"example": "special"}] |

    When soft removal garbage collection is run for content repository default
    Then I expect exactly 6 events to be published on stream "ContentStream:cs-identifier"
    And event at index 5 is of type "SubtreeWasTagged" with payload:
      | Key                          | Expected                                        |
      | workspaceName                | "live"                                          |
      | contentStreamId              | "cs-identifier"                                 |
      | nodeAggregateId              | "nodingers-cat"                                 |
      | affectedDimensionSpacePoints | [{"example": "source"}, {"example": "special"}] |
      | tag                          | "removed"                                       |

  Scenario: Garbage collection will ignore a soft removal if there are reference changes in an affected dimension space point
    When the command TagSubtree is executed with payload:
      | Key                          | Value                  |
      | workspaceName                | "live"                 |
      | nodeAggregateId              | "nodingers-cat"        |
      | coveredDimensionSpacePoint   | {"example": "special"} |
      | nodeVariantSelectionStrategy | "allSpecializations"   |
      | tag                          | "removed"              |

    And I am in workspace "user-workspace"
    And the command SetNodeReferences is executed with payload:
      | Key                             | Value                                                                                    |
      | workspaceName                   | "user-workspace"                                                                         |
      | sourceNodeAggregateId           | "nodingers-cat"                                                                          |
      | sourceOriginDimensionSpacePoint | {"example": "source"}                                                                    |
      | references                      | [{"referenceName": "myReference", "references": [{"target": "sir-david-nodenborough"}]}] |
    And the command RebaseWorkspace is executed with payload:
      | Key           | Value            |
      | workspaceName | "user-workspace" |
    Then I expect the following hard removal conflicts to be impending:
      | nodeAggregateId | dimensionSpacePoints     |
      | nodingers-cat   | [{"example": "special"}] |

    When soft removal garbage collection is run for content repository default
    Then I expect exactly 6 events to be published on stream "ContentStream:cs-identifier"
    And event at index 5 is of type "SubtreeWasTagged" with payload:
      | Key                          | Expected                 |
      | workspaceName                | "live"                   |
      | contentStreamId              | "cs-identifier"          |
      | nodeAggregateId              | "nodingers-cat"          |
      | affectedDimensionSpacePoints | [{"example": "special"}] |
      | tag                          | "removed"                |

  Scenario: Garbage collection will transform a soft removal if there are unrelated reference changes in another workspace
    When the command TagSubtree is executed with payload:
      | Key                          | Value                 |
      | workspaceName                | "live"                |
      | nodeAggregateId              | "nodingers-cat"       |
      | coveredDimensionSpacePoint   | {"example": "source"} |
      | nodeVariantSelectionStrategy | "allSpecializations"  |
      | tag                          | "removed"             |

    And I am in workspace "user-workspace"
    And the command SetNodeReferences is executed with payload:
      | Key                             | Value                                                                                    |
      | workspaceName                   | "user-workspace"                                                                         |
      | sourceNodeAggregateId           | "sir-david-nodenborough"                                                                 |
      | sourceOriginDimensionSpacePoint | {"example": "source"}                                                                    |
      # explicit self-reference to be unrelated, also tests both directions
      | references                      | [{"referenceName": "myReference", "references": [{"target": "sir-david-nodenborough"}]}] |
    And the command RebaseWorkspace is executed with payload:
      | Key           | Value            |
      | workspaceName | "user-workspace" |
    Then I expect the following hard removal conflicts to be impending:
      | nodeAggregateId | dimensionSpacePoints |

    When soft removal garbage collection is run for content repository default
    Then I expect exactly 7 events to be published on stream "ContentStream:cs-identifier"
    And event at index 6 is of type "NodeAggregateWasRemoved" with payload:
      | Key                                  | Expected                                        |
      | workspaceName                        | "live"                                          |
      | contentStreamId                      | "cs-identifier"                                 |
      | nodeAggregateId                      | "nodingers-cat"                                 |
      | affectedCoveredDimensionSpacePoints  | [{"example": "source"}, {"example": "special"}] |

    When the command RebaseWorkspace is executed with payload:
      | Key           | Value            |
      | workspaceName | "user-workspace" |
    # no exceptions must be thrown

  Scenario: Garbage collection will transform a soft removal if there are reference changes in an unrelated dimension space point (both directions, directly or via descendants)
    When the command CreateNodeVariant is executed with payload:
      | Key             | Value                    |
      | workspaceName   | "live"                   |
      | nodeAggregateId | "sir-david-nodenborough" |
      | sourceOrigin    | {"example": "source"}    |
      | targetOrigin    | {"example": "general"}   |
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                  |
      | workspaceName   | "live"                 |
      | nodeAggregateId | "nodingers-cat"        |
      | sourceOrigin    | {"example": "source"}  |
      | targetOrigin    | {"example": "general"} |
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                  |
      | workspaceName   | "live"                 |
      | nodeAggregateId | "nodingers-kitten"     |
      | sourceOrigin    | {"example": "source"}  |
      | targetOrigin    | {"example": "general"} |
    And the command RebaseWorkspace is executed with payload:
      | Key           | Value            |
      | workspaceName | "user-workspace" |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                  |
      | workspaceName                | "live"                 |
      | nodeAggregateId              | "nodingers-cat"        |
      | coveredDimensionSpacePoint   | {"example": "special"} |
      | nodeVariantSelectionStrategy | "allSpecializations"   |
      | tag                          | "removed"              |

    And I am in workspace "user-workspace"
    And the command SetNodeReferences is executed with payload:
      | Key                             | Value                                                                                    |
      | workspaceName                   | "user-workspace"                                                                         |
      | sourceNodeAggregateId           | "nodingers-cat"                                                                          |
      | sourceOriginDimensionSpacePoint | {"example": "general"}                                                                   |
      | references                      | [{"referenceName": "myReference", "references": [{"target": "sir-david-nodenborough"}]}] |
    And the command SetNodeReferences is executed with payload:
      | Key                             | Value                                                                           |
      | workspaceName                   | "user-workspace"                                                                |
      | sourceNodeAggregateId           | "sir-david-nodenborough"                                                        |
      | sourceOriginDimensionSpacePoint | {"example": "general"}                                                          |
      | references                      | [{"referenceName": "myReference", "references": [{"target": "nodingers-cat"}]}] |
    And the command SetNodeReferences is executed with payload:
      | Key                             | Value                                                                                    |
      | workspaceName                   | "user-workspace"                                                                         |
      | sourceNodeAggregateId           | "nodingers-kitten"                                                                       |
      | sourceOriginDimensionSpacePoint | {"example": "general"}                                                                   |
      | references                      | [{"referenceName": "myReference", "references": [{"target": "sir-david-nodenborough"}]}] |
    And the command SetNodeReferences is executed with payload:
      | Key                             | Value                                                                              |
      | workspaceName                   | "user-workspace"                                                                   |
      | sourceNodeAggregateId           | "sir-david-nodenborough"                                                           |
      | sourceOriginDimensionSpacePoint | {"example": "general"}                                                             |
      | references                      | [{"referenceName": "myReference", "references": [{"target": "nodingers-kitten"}]}] |
    And the command RebaseWorkspace is executed with payload:
      | Key           | Value            |
      | workspaceName | "user-workspace" |
    Then I expect the following hard removal conflicts to be impending:
      | nodeAggregateId | dimensionSpacePoints |

    When soft removal garbage collection is run for content repository default
    Then I expect exactly 10 events to be published on stream "ContentStream:cs-identifier"
    And event at index 9 is of type "NodeAggregateWasRemoved" with payload:
      | Key                                  | Expected                 |
      | workspaceName                        | "live"                   |
      | contentStreamId                      | "cs-identifier"          |
      | nodeAggregateId                      | "nodingers-cat"          |
      | affectedCoveredDimensionSpacePoints  | [{"example": "special"}] |

    When the command RebaseWorkspace is executed with payload:
      | Key           | Value            |
      | workspaceName | "user-workspace" |
    # no exceptions must be thrown
