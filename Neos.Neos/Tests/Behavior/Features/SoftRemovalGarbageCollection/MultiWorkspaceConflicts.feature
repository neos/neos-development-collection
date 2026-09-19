Feature: Tests for soft removal garbage collection with conflicts across workspaces

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

  Scenario: Impending conflicts: Changes in multiple workspace will avoid garbage collection
    And the command CreateWorkspace is executed with payload:
      | Key                | Value                |
      | workspaceName      | "user-workspace-two" |
      | baseWorkspaceName  | "live"               |
      | newContentStreamId | "user-two-cs-id"     |

    When the command TagSubtree is executed with payload:
      | Key                          | Value                  |
      | workspaceName                | "live"                 |
      | nodeAggregateId              | "nodingers-cat"        |
      | coveredDimensionSpacePoint   | {"example": "special"} |
      | nodeVariantSelectionStrategy | "allSpecializations"   |
      | tag                          | "removed"              |

    And I am in workspace "user-workspace"
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                     |
      | workspaceName             | "user-workspace"          |
      | nodeAggregateId           | "nodingers-cat"           |
      | originDimensionSpacePoint | {"example": "source"}     |
      | propertyValues            | {"title": "First change"} |
    And the command RebaseWorkspace is executed with payload:
      | Key           | Value            |
      | workspaceName | "user-workspace" |
    Then I expect the following hard removal conflicts to be impending:
      | nodeAggregateId | dimensionSpacePoints     |
      | nodingers-cat   | [{"example": "special"}] |

    When the command TagSubtree is executed with payload:
      | Key                          | Value                 |
      | workspaceName                | "live"                |
      | nodeAggregateId              | "nodingers-cat"       |
      | coveredDimensionSpacePoint   | {"example": "source"} |
      | nodeVariantSelectionStrategy | "allSpecializations"  |
      | tag                          | "removed"             |

    And I am in workspace "user-workspace-two"
    When the command CreateNodeVariant is executed with payload:
      | Key             | Value                    |
      | workspaceName   | "user-workspace-two"     |
      | nodeAggregateId | "sir-david-nodenborough" |
      | sourceOrigin    | {"example": "source"}    |
      | targetOrigin    | {"example": "general"}   |
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                  |
      | workspaceName   | "user-workspace-two"   |
      | nodeAggregateId | "nodingers-cat"        |
      | sourceOrigin    | {"example": "source"}  |
      | targetOrigin    | {"example": "general"} |
    And the command RebaseWorkspace is executed with payload:
      | Key           | Value                |
      | workspaceName | "user-workspace-two" |

    # both impending conflicts are joined
    Then I expect the following hard removal conflicts to be impending:
      | nodeAggregateId  | dimensionSpacePoints                                                   |
      | nodingers-cat    | [{"example":"general"}, {"example": "source"}, {"example": "special"}] |

    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                      |
      | workspaceName             | "user-workspace-two"       |
      | nodeAggregateId           | "nodingers-kitten"         |
      | originDimensionSpacePoint | {"example": "source"}      |
      | propertyValues            | {"title": "Nested change"} |

    When soft removal garbage collection is run for content repository default
    Then I expect exactly 7 events to be published on stream "ContentStream:cs-identifier"
    And event at index 6 is of type "SubtreeWasTagged" with payload:
      | Key                          | Expected                                        |
      | workspaceName                | "live"                                          |
      | contentStreamId              | "cs-identifier"                                 |
      | nodeAggregateId              | "nodingers-cat"                                 |
      | affectedDimensionSpacePoints | [{"example": "source"}, {"example": "special"}] |
      | tag                          | "removed"                                       |

  Scenario: Visibility conflict: Node not soft removed in nested dependent workspace will avoid garbage collection
    And the command CreateWorkspace is executed with payload:
      | Key                | Value              |
      | workspaceName      | "shared-workspace" |
      | baseWorkspaceName  | "live"             |
      | newContentStreamId | "shared-cs-id"     |
    And the command CreateWorkspace is executed with payload:
      | Key                | Value                |
      | workspaceName      | "user-workspace-two" |
      | baseWorkspaceName  | "shared-workspace"   |
      | newContentStreamId | "user-two-cs-id"     |

    When the command TagSubtree is executed with payload:
      | Key                          | Value                 |
      | workspaceName                | "live"                |
      | nodeAggregateId              | "nodingers-cat"       |
      | coveredDimensionSpacePoint   | {"example": "source"} |
      | nodeVariantSelectionStrategy | "allSpecializations"  |
      | tag                          | "removed"             |
    # rebase only user one
    And the command RebaseWorkspace is executed with payload:
      | Key           | Value            |
      | workspaceName | "user-workspace" |

    Then I expect the following hard removal conflicts to be impending:
      | nodeAggregateId | dimensionSpacePoints     |

    # no garbage collection
    When soft removal garbage collection is run for content repository default
    Then I expect exactly 6 events to be published on stream "ContentStream:cs-identifier"
    And event at index 5 is of type "SubtreeWasTagged" with payload:
      | Key                          | Expected                                        |
      | workspaceName                | "live"                                          |
      | contentStreamId              | "cs-identifier"                                 |
      | nodeAggregateId              | "nodingers-cat"                                 |
      | affectedDimensionSpacePoints | [{"example": "source"}, {"example": "special"}] |
      | tag                          | "removed"                                       |

    # rebase only shared
    And the command RebaseWorkspace is executed with payload:
      | Key           | Value              |
      | workspaceName | "shared-workspace" |

      # no garbage collection
    When soft removal garbage collection is run for content repository default
    Then I expect exactly 6 events to be published on stream "ContentStream:cs-identifier"

    # rebase finally user two
    And the command RebaseWorkspace is executed with payload:
      | Key           | Value                |
      | workspaceName | "user-workspace-two" |

    # garbage collection removes
    When soft removal garbage collection is run for content repository default
    Then I expect exactly 7 events to be published on stream "ContentStream:cs-identifier"
    And event at index 6 is of type "NodeAggregateWasRemoved" with payload:
      | Key                                  | Expected                                        |
      | workspaceName                        | "live"                                          |
      | contentStreamId                      | "cs-identifier"                                 |
      | nodeAggregateId                      | "nodingers-cat"                                 |
      | affectedCoveredDimensionSpacePoints  | [{"example": "source"}, {"example": "special"}] |
