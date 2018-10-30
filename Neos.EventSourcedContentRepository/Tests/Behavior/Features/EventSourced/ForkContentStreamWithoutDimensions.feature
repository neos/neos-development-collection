@fixtures
Feature: ForkContentStream Without Dimensions

  We have only one node underneath the root node: /foo.
  LIVE Content Stream ID: c75ae6a2-7254-4d42-a31b-a629e264069d
  We fork the live content stream as ID d548f014-884f-4208-a49a-eafc417b83a3
  and then we commit a modification in the LIVE content stream.
  We then expect the *forked* content stream to contain the *original* value; and the *live* content stream must contain the changed value.

  Background:
    Given I have no content dimensions

    And the command "CreateRootNode" is executed with payload:
      | Key                      | Value                                |
      | contentStreamIdentifier  | c75ae6a2-7254-4d42-a31b-a629e264069d |
      | nodeIdentifier           | 5387cb08-2aaf-44dc-a8a1-483497aa0a03 |
      | initiatingUserIdentifier | 00000000-0000-0000-0000-000000000000 |
      | nodeTypeName             | Neos.ContentRepository:Root          |

    And the Event "Neos.EventSourcedContentRepository:NodeAggregateWithNodeWasCreated" was published to stream "Neos.ContentRepository:ContentStream:c75ae6a2-7254-4d42-a31b-a629e264069d:NodeAggregate:35411439-94d1-4bd4-8fac-0646856c6a1f" with payload:
      | Key                           | Value                                                           | Type                   |
      | contentStreamIdentifier       | c75ae6a2-7254-4d42-a31b-a629e264069d                            |                        |
      | nodeAggregateIdentifier       | 35411439-94d1-4bd4-8fac-0646856c6a1f                            |                        |
      | nodeTypeName                  | Neos.ContentRepository.Testing:NodeWithoutAutoCreatedChildNodes |                        |
      | dimensionSpacePoint           | []                                                              | DimensionSpacePoint    |
      | visibleInDimensionSpacePoints   | [{}]                                                            | DimensionSpacePointSet |
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


  Scenario: Ensure that the node is available in the forked content stream

    When the command "ForkContentStream" is executed with payload:
      | Key                           | Value                                | Type |
      | contentStreamIdentifier       | d548f014-884f-4208-a49a-eafc417b83a3 |      |
      | sourceContentStreamIdentifier | c75ae6a2-7254-4d42-a31b-a629e264069d |      |

    And the graph projection is fully up to date
    And I am in content stream "d548f014-884f-4208-a49a-eafc417b83a3" and Dimension Space Point {}

    Then I expect a node "75106e9a-7dfb-4b48-8b7a-3c4ab2546b81" to exist in the graph projection

  Scenario: When a change is applied to the forked content stream AFTER the fork, it is not visible in the live content stream.

    When the command "ForkContentStream" is executed with payload:
      | Key                           | Value                                | Type |
      | contentStreamIdentifier       | d548f014-884f-4208-a49a-eafc417b83a3 |      |
      | sourceContentStreamIdentifier | c75ae6a2-7254-4d42-a31b-a629e264069d |      |

    And the Event "Neos.EventSourcedContentRepository:NodePropertyWasSet" was published to stream "Neos.ContentRepository:ContentStream:d548f014-884f-4208-a49a-eafc417b83a3" with payload:

      | Key                     | Value                                         | Type |
      | contentStreamIdentifier | d548f014-884f-4208-a49a-eafc417b83a3          |      |
      | nodeIdentifier          | 75106e9a-7dfb-4b48-8b7a-3c4ab2546b81          |      |
      | propertyName            | test                                          |      |
      | value                   | {"value": "modified value", "type": "string"} | json |


    And the graph projection is fully up to date

      # live
    When I am in content stream "c75ae6a2-7254-4d42-a31b-a629e264069d" and Dimension Space Point {}
    Then I expect a node "75106e9a-7dfb-4b48-8b7a-3c4ab2546b81" to exist in the graph projection
    And I expect the Node "75106e9a-7dfb-4b48-8b7a-3c4ab2546b81" to have the properties:
      | Key  | Value          |
      | test | original value |

    # forked content stream
    When I am in content stream "d548f014-884f-4208-a49a-eafc417b83a3" and Dimension Space Point {}
    Then I expect a node "75106e9a-7dfb-4b48-8b7a-3c4ab2546b81" to exist in the graph projection
    And I expect the Node "75106e9a-7dfb-4b48-8b7a-3c4ab2546b81" to have the properties:
      | Key  | Value          |
      | test | modified value |


  # this is a "reverse" scenario of the scenario above.
  Scenario: When a change is applied on the live content stream AFTER the fork, it is NOT visible in the forked content stream.

    When the command "ForkContentStream" is executed with payload:
      | Key                           | Value                                | Type |
      | contentStreamIdentifier       | d548f014-884f-4208-a49a-eafc417b83a3 |      |
      | sourceContentStreamIdentifier | c75ae6a2-7254-4d42-a31b-a629e264069d |      |

    And the Event "Neos.EventSourcedContentRepository:NodePropertyWasSet" was published to stream "Neos.ContentRepository:ContentStream:c75ae6a2-7254-4d42-a31b-a629e264069d" with payload:

      | Key                     | Value                                         | Type |
      | contentStreamIdentifier | c75ae6a2-7254-4d42-a31b-a629e264069d          |      |
      | nodeIdentifier          | 75106e9a-7dfb-4b48-8b7a-3c4ab2546b81          |      |
      | propertyName            | test                                          |      |
      | value                   | {"value": "modified value", "type": "string"} | json |


    And the graph projection is fully up to date

    # live
    When I am in content stream "c75ae6a2-7254-4d42-a31b-a629e264069d" and Dimension Space Point {}
    Then I expect a node "75106e9a-7dfb-4b48-8b7a-3c4ab2546b81" to exist in the graph projection
    And I expect the Node "75106e9a-7dfb-4b48-8b7a-3c4ab2546b81" to have the properties:
      | Key  | Value          |
      | test | modified value |

    # forked content stream
    When I am in content stream "d548f014-884f-4208-a49a-eafc417b83a3" and Dimension Space Point {}
    Then I expect a node "75106e9a-7dfb-4b48-8b7a-3c4ab2546b81" to exist in the graph projection
    And I expect the Node "75106e9a-7dfb-4b48-8b7a-3c4ab2546b81" to have the properties:
      | Key  | Value          |
      | test | original value |
