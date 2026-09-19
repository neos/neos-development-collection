Feature: Tests for reading the trash bin with search parameters

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
      label: ${node.properties.title}
      properties:
        title:
          type: string
        uriPathSegment:
          type: string
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
      | nodeAggregateId        | parentNodeAggregateId  | nodeTypeName       | initialPropertyValues        |
      | sir-david-nodenborough | lady-eleonode-rootford | Neos.Neos:Site     | {}                           |
      | nodingers-cat          | sir-david-nodenborough | Neos.Neos:Document | {"title": "Cat"}             |
      | nodingers-kitten       | nodingers-cat          | Neos.Neos:Document | {"title": "Kitten"}          |
      | nodingers-cat-2        | sir-david-nodenborough | Neos.Neos:Document | {"title": "Another Cat"}     |
      | nodingers-cat-3        | sir-david-nodenborough | Neos.Neos:Document | {"title": "Yet Another Cat"} |
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "sir-david-nodenborough" |
      | sourceOrigin    | {"example": "source"}    |
      | targetOrigin    | {"example": "peer"}      |
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                 |
      | nodeAggregateId | "nodingers-cat"       |
      | sourceOrigin    | {"example": "source"} |
      | targetOrigin    | {"example": "peer"}   |

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

  Scenario: The trash bin can properly be searched
    When the current date and time is "2025-06-24T17:56:25+02:00"
    And the command TagSubtree is executed with payload:
      | Key                          | Value                |
      | workspaceName                | "user-workspace"     |
      | nodeAggregateId              | "nodingers-cat-3"    |
      | coveredDimensionSpacePoint   | {"example":"source"} |
      | nodeVariantSelectionStrategy | "allSpecializations" |
      | tag                          | "removed"            |
    And the current date and time is "2025-06-25T17:56:25+02:00"
    And the command TagSubtree is executed with payload:
      | Key                          | Value                |
      | workspaceName                | "user-workspace"     |
      | nodeAggregateId              | "nodingers-cat"      |
      | coveredDimensionSpacePoint   | {"example":"source"} |
      | nodeVariantSelectionStrategy | "allSpecializations" |
      | tag                          | "removed"            |
    And the current date and time is "2025-06-26T17:56:25+02:00"
    And the command TagSubtree is executed with payload:
      | Key                          | Value                |
      | workspaceName                | "user-workspace"     |
      | nodeAggregateId              | "nodingers-cat-2"    |
      | coveredDimensionSpacePoint   | {"example":"source"} |
      | nodeVariantSelectionStrategy | "allSpecializations" |
      | tag                          | "removed"            |

    Then I expect the trash bin for workspace "user-workspace" and parameters to contain exactly the items:
    """json
    {
      "parameters": {
        "sorting": {
          "propertyName": "deleteTime",
          "direction": "asc"
        },
        "pagination": {
          "offset": 0,
          "limit": null
        },
        "searchTerm": null
      },
      "expectedItems": [
        {
          "nodeAggregateId": "nodingers-cat-3",
          "userId": "initiating-user-identifier",
          "deleteTime": "2025-06-24T15:56:25+00:00",
          "affectedDimensionSpacePoints": [{"example":"source"}, {"example": "special"}]
        },
        {
          "nodeAggregateId": "nodingers-cat",
          "userId": "initiating-user-identifier",
          "deleteTime": "2025-06-25T15:56:25+00:00",
          "affectedDimensionSpacePoints": [{"example":"source"}, {"example": "special"}]
        },
        {
          "nodeAggregateId": "nodingers-cat-2",
          "userId": "initiating-user-identifier",
          "deleteTime": "2025-06-26T15:56:25+00:00",
          "affectedDimensionSpacePoints": [{"example":"source"}, {"example": "special"}]
        }
      ]
    }
    """

    And I expect the trash bin for workspace "user-workspace" and parameters to contain exactly the items:
    """json
    {
      "parameters": {
        "sorting": {
          "propertyName": "deleteTime",
          "direction": "asc"
        },
        "pagination": {
          "offset": 0,
          "limit": null
        },
        "searchTerm": "Another"
      },
      "expectedItems": [
        {
          "nodeAggregateId": "nodingers-cat-3",
          "userId": "initiating-user-identifier",
          "deleteTime": "2025-06-24T15:56:25+00:00",
          "affectedDimensionSpacePoints": [{"example":"source"}, {"example": "special"}]
        },
        {
          "nodeAggregateId": "nodingers-cat-2",
          "userId": "initiating-user-identifier",
          "deleteTime": "2025-06-26T15:56:25+00:00",
          "affectedDimensionSpacePoints": [{"example":"source"}, {"example": "special"}]
        }
      ]
    }
    """

    And I expect the trash bin for workspace "user-workspace" and parameters to contain exactly the items:
    """json
    {
      "parameters": {
        "sorting": {
          "propertyName": "deleteTime",
          "direction": "desc"
        },
        "pagination": {
          "offset": 1,
          "limit": 1
        },
        "searchTerm": "Another"
      },
      "expectedItems": [
        {
          "nodeAggregateId": "nodingers-cat-3",
          "userId": "initiating-user-identifier",
          "deleteTime": "2025-06-24T15:56:25+00:00",
          "affectedDimensionSpacePoints": [{"example":"source"}, {"example": "special"}]
        }
      ]
    }
    """
