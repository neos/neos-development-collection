Feature: Additional constraint checks for the soft "removed" tag

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
    'Neos.Neos:ContentCollection': {}
    'Neos.Neos:Document':
      properties:
        title:
          type: string
        uriPathSegment:
          type: string
    'Neos.Neos:Site':
      superTypes:
        'Neos.Neos:Document': true
    'Neos.Neos.Testing:Document':
      superTypes:
        'Neos.Neos:Document': true
      childNodes:
        main:
          type: 'Neos.Neos:ContentCollection'
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
      | nodeAggregateId        | parentNodeAggregateId  | nodeTypeName               | tetheredDescendantNodeAggregateIds   |
      | sir-david-nodenborough | lady-eleonode-rootford | Neos.Neos:Site             |                                      |
      | nodingers-cat          | sir-david-nodenborough | Neos.Neos.Testing:Document | {"main": "nodingers-leashed-kitten"} |
    And the command CreateWorkspace is executed with payload:
      | Key                | Value            |
      | workspaceName      | "user-workspace" |
      | baseWorkspaceName  | "live"           |
      | newContentStreamId | "user-cs-id"     |

  Scenario: Soft removing a tethered node aggregate is forbidden
    When the command TagSubtree is executed with payload and exceptions are caught:
      | Key                          | Value                      |
      | nodeAggregateId              | "nodingers-leashed-kitten" |
      | nodeVariantSelectionStrategy | "allSpecializations"       |
      | tag                          | "removed"                  |
    Then the last command should have thrown an exception of type "NodeAggregateIsTethered"

  Scenario: Tethered soft removed node aggregate's are ignored
    # forced soft removal by circumventing the Neos' constraints - should not be possible ...
    And the event SubtreeWasTagged was published with payload:
      | Key                          | Value                                        |
      | workspaceName                | "live"                                       |
      | contentStreamId              | "cs-identifier"                              |
      | nodeAggregateId              | "nodingers-leashed-kitten"                   |
      | affectedDimensionSpacePoints | [{"example":"source"},{"example":"special"}] |
      | tag                          | "removed"                                    |
    When the command RebaseWorkspace is executed with payload:
      | Key           | Value            |
      | workspaceName | "user-workspace" |
    # We still must not remove node as this cause an exception: The node aggregate "nodingers-leashed-kitten" is tethered, and thus cannot be removed.
    When soft removal garbage collection is run for content repository default
    Then I expect exactly 6 events to be published on stream "ContentStream:cs-identifier"
    And event at index 5 is of type "SubtreeWasTagged" with payload:
      | Key                          | Expected                                     |
      | workspaceName                | "live"                                       |
      | contentStreamId              | "cs-identifier"                              |
      | nodeAggregateId              | "nodingers-leashed-kitten"                   |
      | affectedDimensionSpacePoints | [{"example":"source"},{"example":"special"}] |
      | tag                          | "removed"                                    |

  Scenario: Soft removing a root node aggregate is forbidden
    When the command TagSubtree is executed with payload and exceptions are caught:
      | Key                          | Value                    |
      | workspaceName                | "live"                   |
      | nodeAggregateId              | "lady-eleonode-rootford" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allSpecializations"     |
      | tag                          | "removed"                |
    Then the last command should have thrown an exception of type "NodeAggregateIsRoot"
