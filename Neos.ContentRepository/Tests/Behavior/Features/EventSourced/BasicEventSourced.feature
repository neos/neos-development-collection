@fixtures
Feature: Basic evetn source
  Create

  Scenario:


  Scenario: Root Node is created
    When the command "CreateRootNode" is executed with payload:
      | Key                      | Value                                |
      | contentStreamIdentifier  | c75ae6a2-7254-4d42-a31b-a629e264069d |
      | nodeIdentifier           | 5387cb08-2aaf-44dc-a8a1-483497aa0a0  |
      | initiatingUserIdentifier | 00000000-0000-0000-0000-000000000000 |
    Then I expect exactly 1 event to be published on stream "Neos.ContentRepository:ContentStream:c75ae6a2-7254-4d42-a31b-a629e264069d"
    And event number 1 is of type "Neos.ContentRepository:RootNodeWasCreated" with payload:
      | Key                      | Value                                |
      | contentStreamIdentifier  | c75ae6a2-7254-4d42-a31b-a629e264069d |
      | nodeIdentifier           | 5387cb08-2aaf-44dc-a8a1-483497aa0a0  |
      | initiatingUserIdentifier | 00000000-0000-0000-0000-000000000000 |


  Scenario: CreateChildNodeWithVariant
    Given I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository.Testing:NodeWithoutAutoCreatedChildNodes':
      properties:
        text:
          defaultValue: 'my default'
          type: string
    """
    Given the Event "Neos.ContentRepository:RootNodeWasCreated" was published to stream "Neos.ContentRepository:ContentStream:c75ae6a2-7254-4d42-a31b-a629e264069d" with payload:
      | Key                      | Value                                |
      | contentStreamIdentifier  | c75ae6a2-7254-4d42-a31b-a629e264069d |
      | nodeIdentifier           | 5387cb08-2aaf-44dc-a8a1-483497aa0a0  |
      | initiatingUserIdentifier | 00000000-0000-0000-0000-000000000000 |

    When the command "CreateChildNodeWithVariant" is executed with payload:
      | Key                     | Value                                                           | Type            |
      | contentStreamIdentifier | c75ae6a2-7254-4d42-a31b-a629e264069d                            |                 |
      | parentNodeIdentifier    | 5387cb08-2aaf-44dc-a8a1-483497aa0a0                             |                 |
      | nodeIdentifier          | 35411439-94d1-4bd4-8fac-0646856c6a1f                            |                 |
      | nodeName                | foo                                                             |                 |
      | nodeTypeName            | Neos.ContentRepository.Testing:NodeWithoutAutoCreatedChildNodes |                 |
      | dimensionValues         | {}                                                              | DimensionValues |


    # event 1 is the one from the "Given" part
    Then I expect exactly 2 events to be published on stream "Neos.ContentRepository:ContentStream:c75ae6a2-7254-4d42-a31b-a629e264069d"
    And event number 2 is of type "Neos.ContentRepository:ChildNodeWithVariantWasCreated" with payload:
      | Key                                      | Value                                                           | Type |
      | contentStreamIdentifier                  | c75ae6a2-7254-4d42-a31b-a629e264069d                            |      |
      | parentNodeIdentifier                     | 5387cb08-2aaf-44dc-a8a1-483497aa0a0                             |      |
      | nodeIdentifier                           | 35411439-94d1-4bd4-8fac-0646856c6a1f                            |      |
      | nodeName                                 | foo                                                             |      |
      | nodeTypeName                             | Neos.ContentRepository.Testing:NodeWithoutAutoCreatedChildNodes |      |
      #| dimensionValues                          | {"values": []}                                                  | json |
      | propertyDefaultValuesAndTypes.text.value | my default                                                      |      |
      | propertyDefaultValuesAndTypes.text.type  | string                                                          |      |

