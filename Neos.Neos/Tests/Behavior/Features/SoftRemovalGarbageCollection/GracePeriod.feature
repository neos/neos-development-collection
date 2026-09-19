Feature: Tests that the garbage collector respects the configured grace time when deciding whether to delete nodes

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
    'Neos.Neos:Document': {}
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
    And I am in workspace "live" and dimension space point {"example": "general"}
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

  Scenario: Garbage collection does not remove content modified within the grace period but removes content modified before it
    When I am in workspace "user-workspace"
    And the current date and time is "2026-04-14T16:00:00+00:00"
    When the command TagSubtree is executed with payload:
      | Key                          | Value                 |
      | workspaceName                | "user-workspace"      |
      | nodeAggregateId              | "nodingers-cat"       |
      | coveredDimensionSpacePoint   | {"example": "source"} |
      | nodeVariantSelectionStrategy | "allSpecializations"  |
      | tag                          | "removed"             |
    And the current date and time is "2026-04-14T18:00:00+00:00"
    When the command TagSubtree is executed with payload:
      | Key                          | Value                 |
      | workspaceName                | "user-workspace"      |
      | nodeAggregateId              | "nodingers-cat"       |
      | coveredDimensionSpacePoint   | {"example": "peer"}   |
      | nodeVariantSelectionStrategy | "allSpecializations"  |
      | tag                          | "removed"             |
    And the command PublishWorkspace is executed with payload:
      | Key           | Value            |
      | workspaceName | "user-workspace" |
    And the current date and time is "2026-04-15T17:00:00+00:00"
    When soft removal garbage collection is run for content repository default with grace period P1D
    Then I expect exactly 8 events to be published on stream "ContentStream:cs-identifier"
    And event at index 7 is of type "NodeAggregateWasRemoved" with payload:
      | Key                                  | Expected                                        |
      | workspaceName                        | "live"                                          |
      | contentStreamId                      | "cs-identifier"                                 |
      | nodeAggregateId                      | "nodingers-cat"                                 |
      | affectedCoveredDimensionSpacePoints  | [{"example": "source"}, {"example": "special"}] |
