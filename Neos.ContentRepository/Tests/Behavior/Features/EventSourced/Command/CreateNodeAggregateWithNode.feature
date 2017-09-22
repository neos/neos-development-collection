@fixtures
Feature: Create node aggregate with node

  As a user of the CR I want to create a new externally referenceable node aggregate of a specific type with a node
  in a specific dimension space point.

  Background:
    Given I have no content dimensions

  Scenario: Create root node
    When the command "CreateRootNode" is executed with payload:
      | Key                      | Value                                |
      | contentStreamIdentifier  | c75ae6a2-7254-4d42-a31b-a629e264069d |
      | nodeIdentifier           | 5387cb08-2aaf-44dc-a8a1-483497aa0a03 |
      | initiatingUserIdentifier | 00000000-0000-0000-0000-000000000000 |
    Then I expect exactly 1 event to be published on stream "Neos.ContentRepository:ContentStream:c75ae6a2-7254-4d42-a31b-a629e264069d"
    And event at index 0 is of type "Neos.ContentRepository:RootNodeWasCreated" with payload:
      | Key                      | Expected                             |
      | contentStreamIdentifier  | c75ae6a2-7254-4d42-a31b-a629e264069d |
      | nodeIdentifier           | 5387cb08-2aaf-44dc-a8a1-483497aa0a03 |
      | initiatingUserIdentifier | 00000000-0000-0000-0000-000000000000 |


  Scenario: Create node aggregate with node without auto-created child nodes
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
      | nodeIdentifier           | 5387cb08-2aaf-44dc-a8a1-483497aa0a03 |
      | initiatingUserIdentifier | 00000000-0000-0000-0000-000000000000 |

    When the command "CreateNodeAggregateWithNode" is executed with payload:
      | Key                     | Value                                                           | Type                |
      | contentStreamIdentifier | c75ae6a2-7254-4d42-a31b-a629e264069d                            |                     |
      | nodeAggregateIdentifier | 35411439-94d1-4bd4-8fac-0646856c6a1f                            |                     |
      | nodeTypeName            | Neos.ContentRepository.Testing:NodeWithoutAutoCreatedChildNodes |                     |
      | dimensionSpacePoint     | {}                                                              | DimensionSpacePoint |
      | nodeIdentifier          | 75106e9a-7dfb-4b48-8b7a-3c4ab2546b81                            |                     |
      | parentNodeIdentifier    | 5387cb08-2aaf-44dc-a8a1-483497aa0a03                            |                     |
      | nodeName                | foo                                                             |                     |

    # event 1 is the one from the "Given" part
    Then I expect exactly 2 events to be published on stream "Neos.ContentRepository:ContentStream:c75ae6a2-7254-4d42-a31b-a629e264069d"
    And event at index 1 is of type "Neos.ContentRepository:NodeAggregateWithNodeWasCreated" with payload:
      | Key                                      | Expected                                                        | AssertionType |
      | contentStreamIdentifier                  | c75ae6a2-7254-4d42-a31b-a629e264069d                            |               |
      | nodeAggregateIdentifier                  | 35411439-94d1-4bd4-8fac-0646856c6a1f                            |               |
      | nodeTypeName                             | Neos.ContentRepository.Testing:NodeWithoutAutoCreatedChildNodes |               |
      | dimensionSpacePoint                      | {"coordinates": []}                                             | json          |
      | visibleDimensionSpacePoints              | {"points":[{"coordinates":[]}]}                                 | json          |
      | nodeIdentifier                           | 75106e9a-7dfb-4b48-8b7a-3c4ab2546b81                            |               |
      | parentNodeIdentifier                     | 5387cb08-2aaf-44dc-a8a1-483497aa0a03                            |               |
      | nodeName                                 | foo                                                             |               |
      | propertyDefaultValuesAndTypes.text.value | my default                                                      |               |
      | propertyDefaultValuesAndTypes.text.type  | string                                                          |               |


  Scenario: Create node aggregate with node with auto-created child nodes
    Given I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository.Testing:SubSubNode': {}
    'Neos.ContentRepository.Testing:SubNode':
      childNodes:
        foo:
          type: 'Neos.ContentRepository.Testing:SubSubNode'

    'Neos.ContentRepository.Testing:NodeWithAutoCreatedChildNodes':
      properties:
        text:
          defaultValue: 'my default'
          type: string
      childNodes:
        main:
          type: 'Neos.ContentRepository.Testing:SubNode'
    """
    Given the Event "Neos.ContentRepository:RootNodeWasCreated" was published to stream "Neos.ContentRepository:ContentStream:c75ae6a2-7254-4d42-a31b-a629e264069d" with payload:
      | Key                      | Value                                |
      | contentStreamIdentifier  | c75ae6a2-7254-4d42-a31b-a629e264069d |
      | nodeIdentifier           | 5387cb08-2aaf-44dc-a8a1-483497aa0a03 |
      | initiatingUserIdentifier | 00000000-0000-0000-0000-000000000000 |

    When the command "CreateNodeAggregateWithNode" is executed with payload:
      | Key                     | Value                                                        | Type                |
      | contentStreamIdentifier | c75ae6a2-7254-4d42-a31b-a629e264069d                         |                     |
      | nodeAggregateIdentifier | 35411439-94d1-4bd4-8fac-0646856c6a1f                         |                     |
      | nodeTypeName            | Neos.ContentRepository.Testing:NodeWithAutoCreatedChildNodes |                     |
      | dimensionSpacePoint     | {}                                                           | DimensionSpacePoint |
      | nodeIdentifier          | 75106e9a-7dfb-4b48-8b7a-3c4ab2546b81                         |                     |
      | parentNodeIdentifier    | 5387cb08-2aaf-44dc-a8a1-483497aa0a03                         |                     |
      | nodeName                | foo                                                          |                     |

    # event 1 is the one from the "Given" part
    Then I expect exactly 4 events to be published on stream "Neos.ContentRepository:ContentStream:c75ae6a2-7254-4d42-a31b-a629e264069d"
    And event at index 1 is of type "Neos.ContentRepository:NodeAggregateWithNodeWasCreated" with payload:
      | Key                                      | Expected                                                     | AssertionType |
      | contentStreamIdentifier                  | c75ae6a2-7254-4d42-a31b-a629e264069d                         |               |
      | nodeAggregateIdentifier                  | 35411439-94d1-4bd4-8fac-0646856c6a1f                         |               |
      | nodeTypeName                             | Neos.ContentRepository.Testing:NodeWithAutoCreatedChildNodes |               |
      | dimensionSpacePoint                      | {"coordinates": []}                                          | json          |
      | visibleDimensionSpacePoints              | {"points":[{"coordinates":[]}]}                              | json          |
      | nodeIdentifier                           | 75106e9a-7dfb-4b48-8b7a-3c4ab2546b81                         |               |
      | parentNodeIdentifier                     | 5387cb08-2aaf-44dc-a8a1-483497aa0a03                         |               |
      | nodeName                                 | foo                                                          |               |
      | propertyDefaultValuesAndTypes.text.value | my default                                                   |               |
      | propertyDefaultValuesAndTypes.text.type  | string                                                       |               |

    And event at index 2 is of type "Neos.ContentRepository:NodeAggregateWithNodeWasCreated" with payload:
      | Key                         | Expected                               | AssertionType |
      | contentStreamIdentifier     | c75ae6a2-7254-4d42-a31b-a629e264069d   |               |
      | nodeTypeName                | Neos.ContentRepository.Testing:SubNode |               |
      | dimensionSpacePoint         | {"coordinates": []}                    | json          |
      | visibleDimensionSpacePoints | {"points":[{"coordinates":[]}]}        | json          |
      | parentNodeIdentifier        | 75106e9a-7dfb-4b48-8b7a-3c4ab2546b81   |               |
      | nodeName                    | main                                   |               |

    And event at index 3 is of type "Neos.ContentRepository:NodeAggregateWithNodeWasCreated" with payload:
      | Key                         | Expected                                  | AssertionType |
      | contentStreamIdentifier     | c75ae6a2-7254-4d42-a31b-a629e264069d      |               |
      | nodeName                    | foo                                       |               |
      | nodeTypeName                | Neos.ContentRepository.Testing:SubSubNode |               |
      | dimensionSpacePoint         | {"coordinates": []}                       | json          |
      | visibleDimensionSpacePoints | {"points":[{"coordinates":[]}]}           | json          |

  Scenario: Create node aggregate with node with content dimensions
    Given I have the following content dimensions:
      | Identifier | Default | Presets                                     |
      | language   | mul     | mul=mul; de=de,mul; en=en,mul; ch=ch,de,mul |

# FIXME This is not supported yet in IntraDimensionalFallbackGraph (missing mul preset)
#    Given I have the following content dimensions:
#      | Identifier | Default | Presets                            |
#      | language   | mul     | de=de,mul; en=en,mul; ch=ch,de,mul |

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
      | nodeIdentifier           | 5387cb08-2aaf-44dc-a8a1-483497aa0a03 |
      | initiatingUserIdentifier | 00000000-0000-0000-0000-000000000000 |

    When the command "CreateNodeAggregateWithNode" is executed with payload:
      | Key                     | Value                                                           | Type                |
      | contentStreamIdentifier | c75ae6a2-7254-4d42-a31b-a629e264069d                            |                     |
      | nodeAggregateIdentifier | 35411439-94d1-4bd4-8fac-0646856c6a1f                            |                     |
      | nodeTypeName            | Neos.ContentRepository.Testing:NodeWithoutAutoCreatedChildNodes |                     |
      | dimensionSpacePoint     | {"language": "de"}                                              | DimensionSpacePoint |
      | nodeIdentifier          | 75106e9a-7dfb-4b48-8b7a-3c4ab2546b81                            |                     |
      | parentNodeIdentifier    | 5387cb08-2aaf-44dc-a8a1-483497aa0a03                            |                     |
      | nodeName                | foo                                                             |                     |

    # event 1 is the one from the "Given" part
    Then I expect exactly 2 events to be published on stream "Neos.ContentRepository:ContentStream:c75ae6a2-7254-4d42-a31b-a629e264069d"
    And event at index 1 is of type "Neos.ContentRepository:NodeAggregateWithNodeWasCreated" with payload:
      | Key                                      | Expected                                                                         | AssertionType |
      | contentStreamIdentifier                  | c75ae6a2-7254-4d42-a31b-a629e264069d                                             |               |
      | nodeAggregateIdentifier                  | 35411439-94d1-4bd4-8fac-0646856c6a1f                                             |               |
      | nodeTypeName                             | Neos.ContentRepository.Testing:NodeWithoutAutoCreatedChildNodes                  |               |
      | dimensionSpacePoint                      | {"coordinates":{"language":"de"}}                                                | json          |
      | visibleDimensionSpacePoints              | {"points":[{"coordinates":{"language":"de"}},{"coordinates":{"language":"ch"}}]} | json          |
      | nodeIdentifier                           | 75106e9a-7dfb-4b48-8b7a-3c4ab2546b81                                             |               |
      | parentNodeIdentifier                     | 5387cb08-2aaf-44dc-a8a1-483497aa0a03                                             |               |
      | nodeName                                 | foo                                                                              |               |
      | propertyDefaultValuesAndTypes.text.value | my default                                                                       |               |
      | propertyDefaultValuesAndTypes.text.type  | string                                                                           |               |
