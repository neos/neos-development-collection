@fixtures
Feature: Reading of our Graph Projection

  Background:
    Given I have the following content dimensions:
      | Identifier | Default | Values          | Generalizations      |
      | language   | mul     | mul, de, en, ch | ch->de->mul, en->mul |
    And the command "CreateRootNode" is executed with payload:
      | Key                      | Value                                |
      | contentStreamIdentifier  | c75ae6a2-7254-4d42-a31b-a629e264069d |
      | nodeIdentifier           | 5387cb08-2aaf-44dc-a8a1-483497aa0a03 |
      | initiatingUserIdentifier | 00000000-0000-0000-0000-000000000000 |
      | nodeTypeName             | Neos.ContentRepository:Root          |

  Scenario: Property Changes with two dimensions

    And the Event "Neos.EventSourcedContentRepository:NodeAggregateWithNodeWasCreated" was published to stream "Neos.ContentRepository:ContentStream:c75ae6a2-7254-4d42-a31b-a629e264069d:NodeAggregate:35411439-94d1-4bd4-8fac-0646856c6a1f" with payload:
      | Key                           | Value                                                           | Type                   |
      | contentStreamIdentifier       | c75ae6a2-7254-4d42-a31b-a629e264069d                            |                        |
      | nodeAggregateIdentifier       | 35411439-94d1-4bd4-8fac-0646856c6a1f                            |                        |
      | nodeTypeName                  | Neos.ContentRepository.Testing:NodeWithoutAutoCreatedChildNodes |                        |
      | dimensionSpacePoint           | {"language": "de"}                                              | json                   |
      | visibleInDimensionSpacePoints   | [{"language": "de"},{"language": "mul"}]                        | DimensionSpacePointSet |
      | nodeIdentifier                | 75106e9a-7dfb-4b48-8b7a-3c4ab2546b81                            |                        |
      | parentNodeIdentifier          | 5387cb08-2aaf-44dc-a8a1-483497aa0a03                            |                        |
      | nodeName                      | foo                                                             |                        |
      | propertyDefaultValuesAndTypes | {}                                                              | json                   |
    And the Event "Neos.EventSourcedContentRepository:NodePropertyWasSet" was published to stream "Neos.ContentRepository:ContentStream:c75ae6a2-7254-4d42-a31b-a629e264069d" with payload:
      | Key                     | Value                                         | Type          |
      | contentStreamIdentifier | c75ae6a2-7254-4d42-a31b-a629e264069d          |               |
      | nodeIdentifier          | 75106e9a-7dfb-4b48-8b7a-3c4ab2546b81          |               |
      | propertyName            | test                                          |               |
      | value                   | {"value": "original value", "type": "string"} | PropertyValue |

    When the graph projection is fully up to date
    And I am in content stream "c75ae6a2-7254-4d42-a31b-a629e264069d" and Dimension Space Point {"language": "mul"}

    Then I expect a node "75106e9a-7dfb-4b48-8b7a-3c4ab2546b81" to exist in the graph projection
    And I expect the node "5387cb08-2aaf-44dc-a8a1-483497aa0a03" to have the following child nodes:
      | Name | NodeIdentifier                       |
      | foo  | 75106e9a-7dfb-4b48-8b7a-3c4ab2546b81 |

  Scenario: Translation of node in aggregate

    # Node /sites
    And the Event "Neos.EventSourcedContentRepository:NodeAggregateWithNodeWasCreated" was published to stream "Neos.ContentRepository:ContentStream:c75ae6a2-7254-4d42-a31b-a629e264069d:NodeAggregate:c2037dc4-a20d-11e7-ba09-b3eb6d631979" with payload:
      | Key                           | Value                                                                      | Type |
      | contentStreamIdentifier       | c75ae6a2-7254-4d42-a31b-a629e264069d                                       |      |
      | nodeAggregateIdentifier       | c2037dc4-a20d-11e7-ba09-b3eb6d631979                                       |      |
      | nodeTypeName                  | unstructured                                                               |      |
      | dimensionSpacePoint           | {"language":"mul"}                                                         | json |
      | visibleInDimensionSpacePoints   | [{"language":"mul"},{"language":"de"},{"language":"en"},{"language":"ch"}] | json |
      | nodeIdentifier                | ead94f26-a20d-11e7-8ecc-43aabe596a03                                       |      |
      | parentNodeIdentifier          | 5387cb08-2aaf-44dc-a8a1-483497aa0a03                                       |      |
      | nodeName                      | sites                                                                      |      |
      | propertyDefaultValuesAndTypes | {}                                                                         | json |
    # Node /sites/text1 (language=de)
    And the Event "Neos.EventSourcedContentRepository:NodeAggregateWithNodeWasCreated" was published to stream "Neos.ContentRepository:ContentStream:c75ae6a2-7254-4d42-a31b-a629e264069d:NodeAggregate:35411439-94d1-4bd4-8fac-0646856c6a1f" with payload:
      | Key                           | Value                                  | Type |
      | contentStreamIdentifier       | c75ae6a2-7254-4d42-a31b-a629e264069d   |      |
      | nodeAggregateIdentifier       | 35411439-94d1-4bd4-8fac-0646856c6a1f   |      |
      | nodeTypeName                  | Neos.ContentRepository.Testing:Content |      |
      | dimensionSpacePoint           | {"language":"de"}                      | json |
      | visibleInDimensionSpacePoints   | [{"language":"de"},{"language":"ch"}]  | json |
      | nodeIdentifier                | 75106e9a-7dfb-4b48-8b7a-3c4ab2546b81   |      |
      | parentNodeIdentifier          | ead94f26-a20d-11e7-8ecc-43aabe596a03   |      |
      | nodeName                      | text1                                  |      |
      | propertyDefaultValuesAndTypes | {}                                     | json |
    # Translated node /sites/text1 (language=en)
    And the Event "Neos.EventSourcedContentRepository:NodeInAggregateWasTranslated" was published to stream "Neos.ContentRepository:ContentStream:c75ae6a2-7254-4d42-a31b-a629e264069d:NodeAggregate:35411439-94d1-4bd4-8fac-0646856c6a1f" with payload:
      | Key                             | Value                                | Type |
      | contentStreamIdentifier         | c75ae6a2-7254-4d42-a31b-a629e264069d |      |
      | sourceNodeIdentifier            | 75106e9a-7dfb-4b48-8b7a-3c4ab2546b81 |      |
      | destinationNodeIdentifier       | 01831e48-a20c-11e7-851a-dfef4f55c64c |      |
      | destinationParentNodeIdentifier | ead94f26-a20d-11e7-8ecc-43aabe596a03 |      |
      | dimensionSpacePoint             | {"language":"en"}                    | json |
      | visibleInDimensionSpacePoints     | [{"language":"en"}]                  | json |

    When the graph projection is fully up to date
    And I am in content stream "c75ae6a2-7254-4d42-a31b-a629e264069d" and Dimension Space Point {"language": "en"}

    Then I expect a node "01831e48-a20c-11e7-851a-dfef4f55c64c" to exist in the graph projection
    And I expect the path "/sites/text1" to lead to the node "01831e48-a20c-11e7-851a-dfef4f55c64c"
