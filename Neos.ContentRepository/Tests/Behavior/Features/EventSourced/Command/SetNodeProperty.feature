@fixtures
Feature: Set node property

  As a user of the CR I want to set a property of a node.

  Background:
    Given I have no content dimensions
    And the command "CreateRootWorkspace" is executed with payload:
      | Key                      | Value                                | Type |
      | workspaceName            | live                                 |      |
      | workspaceTitle           | Live                                 |      |
      | workspaceDescription     | The live workspace                   |      |
      | initiatingUserIdentifier | 00000000-0000-0000-0000-000000000000 |      |
      | contentStreamIdentifier  | c75ae6a2-7254-4d42-a31b-a629e264069d |      |
      | rootNodeIdentifier       | 5387cb08-2aaf-44dc-a8a1-483497aa0a03 |      |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository.Testing:Content':
      properties:
        text:
          type: string
    """

  Scenario: Set property of a node
    Given the Event "Neos.ContentRepository:NodeAggregateWithNodeWasCreated" was published to stream "Neos.ContentRepository:ContentStream:c75ae6a2-7254-4d42-a31b-a629e264069d" with payload:
      | Key                           | Value                                  | Type                   |
      | contentStreamIdentifier       | c75ae6a2-7254-4d42-a31b-a629e264069d   |                        |
      | nodeAggregateIdentifier       | 35411439-94d1-4bd4-8fac-0646856c6a1f   |                        |
      | nodeTypeName                  | Neos.ContentRepository.Testing:Content |                        |
      | dimensionSpacePoint           | {"coordinates":[]}                     | json                   |
      | visibleDimensionSpacePoints   | {"points":[{"coordinates":[]}]}        | DimensionSpacePointSet |
      | nodeIdentifier                | 75106e9a-7dfb-4b48-8b7a-3c4ab2546b81   |                        |
      | parentNodeIdentifier          | 5387cb08-2aaf-44dc-a8a1-483497aa0a03   |                        |
      | nodeName                      | text1                                  |                        |
      | propertyDefaultValuesAndTypes | {}                                     | json                   |

    When the command "SetNodeProperty" is executed with payload:
      | Key                     | Value                                | Type |
      | contentStreamIdentifier | c75ae6a2-7254-4d42-a31b-a629e264069d |      |
      | nodeIdentifier          | 75106e9a-7dfb-4b48-8b7a-3c4ab2546b81 |      |
      | propertyName            | text                                 |      |
      | value                   | {"value":"Hello","type":"string"}    | json |


    Then I expect exactly 4 events to be published on stream "Neos.ContentRepository:ContentStream:c75ae6a2-7254-4d42-a31b-a629e264069d"
    And event at index 3 is of type "Neos.ContentRepository:NodePropertyWasSet" with payload:
      | Key                     | Expected                             |
      | contentStreamIdentifier | c75ae6a2-7254-4d42-a31b-a629e264069d |
      | nodeIdentifier          | 75106e9a-7dfb-4b48-8b7a-3c4ab2546b81 |
      | propertyName            | text                                 |
      | value.value             | Hello                                |
