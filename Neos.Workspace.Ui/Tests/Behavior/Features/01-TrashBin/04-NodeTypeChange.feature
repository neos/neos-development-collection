Feature: Tests for the trash bin with node type changes

  Changing the node type of a node with the "mark_with_tag:removed" strategy tags all newly disallowed children
  as removed - even if they already are. The trash bin must not fail on this and keep the original trash item.

  Background:
    Given using the following content dimensions:
      | Identifier | Values | Generalizations |
      | example    | source |                 |
    And using the following node types:
    """yaml
    'Neos.ContentRepository:Root': {}
    'Neos.Neos:Sites':
      superTypes:
        'Neos.ContentRepository:Root': true
    'Neos.Neos:Document':
      label: ${node.properties.title}
      properties:
        title:
          type: string
        uriPathSegment:
          type: string
    'Neos.Neos:RestrictiveDocument':
      superTypes:
        'Neos.Neos:Document': true
      constraints:
        nodeTypes:
          '*': false
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
      | nodeAggregateId        | parentNodeAggregateId  | nodeTypeName       | initialPropertyValues |
      | sir-david-nodenborough | lady-eleonode-rootford | Neos.Neos:Site     | {}                    |
      | nodingers-cat          | sir-david-nodenborough | Neos.Neos:Document | {"title": "Cat"}      |
      | nodingers-kitten       | nodingers-cat          | Neos.Neos:Document | {"title": "Kitten"}   |

    And the command CreateWorkspace is executed with payload:
      | Key                | Value              |
      | workspaceName      | "review-workspace" |
      | baseWorkspaceName  | "live"             |
      | newContentStreamId | "review-cs-id"     |
    And the command CreateWorkspace is executed with payload:
      | Key                | Value              |
      | workspaceName      | "user-workspace"   |
      | baseWorkspaceName  | "review-workspace" |
      | newContentStreamId | "user-cs-id"       |

  Scenario: Tagging an already soft removed child again via node type change keeps the original trash item

    When the current date and time is "2025-06-24T17:56:25+02:00"
    And the command TagSubtree is executed with payload:
      | Key                          | Value                |
      | workspaceName                | "user-workspace"     |
      | nodeAggregateId              | "nodingers-kitten"   |
      | coveredDimensionSpacePoint   | {"example":"source"} |
      | nodeVariantSelectionStrategy | "allSpecializations" |
      | tag                          | "removed"            |

    # nodingers-kitten is not allowed below Neos.Neos:RestrictiveDocument and is tagged "removed" a second time
    When the current date and time is "2025-06-26T09:10:15+02:00"
    And the command ChangeNodeAggregateType is executed with payload:
      | Key             | Value                           |
      | workspaceName   | "user-workspace"                |
      | nodeAggregateId | "nodingers-cat"                 |
      | newNodeTypeName | "Neos.Neos:RestrictiveDocument" |
      | strategy        | "mark_with_tag:removed"         |

    Then I expect the trash bin for workspace "user-workspace" to contain exactly the following items:
      | nodeAggregateId  | userId                     | deleteTime                | affectedDimensionSpacePoints |
      | nodingers-kitten | initiating-user-identifier | 2025-06-24T15:56:25+00:00 | [{"example":"source"}]       |

    # publishing applies both tagging events to the base workspace as well
    When the command PublishWorkspace is executed with payload:
      | Key                | Value            |
      | workspaceName      | "user-workspace" |
      | newContentStreamId | "new-user-cs-id" |

    Then I expect the trash bin for workspace "user-workspace" to contain exactly the following items:
      | nodeAggregateId  | userId                     | deleteTime                | affectedDimensionSpacePoints |
      | nodingers-kitten | initiating-user-identifier | 2025-06-24T15:56:25+00:00 | [{"example":"source"}]       |

    And I expect the trash bin for workspace "review-workspace" to contain exactly the following items:
      | nodeAggregateId  | userId                     | deleteTime                | affectedDimensionSpacePoints |
      | nodingers-kitten | initiating-user-identifier | 2025-06-24T15:56:25+00:00 | [{"example":"source"}]       |
