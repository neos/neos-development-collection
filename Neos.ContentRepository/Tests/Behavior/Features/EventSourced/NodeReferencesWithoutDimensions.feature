@fixtures
Feature: Node References without Dimensions

  References between nodes can be reated, overweitten, reordered and deleted

  Background:
    Given I have no content dimensions
    And the command "CreateRootWorkspace" is executed with payload:
      | Key                      | Value                                | Type |
      | workspaceName            | live                                 |      |
      | workspaceTitle           | Live                                 |      |
      | workspaceDescription     | The live workspace                   |      |
      | initiatingUserIdentifier | 00000000-0000-0000-0000-000000000000 |      |
      | contentStreamIdentifier  | cs-identifier                        | Uuid |
      | rootNodeIdentifier       | rn-identifier                        | Uuid |
      | rootNodeTypeName         | Neos.ContentRepository:Root          |      |

    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []

    'Neos.ContentRepository:NodeWithReferences':
      properties:
        referenceProperty:
          type: reference
        referencesProperty:
          type: references
    """

    And the Event "Neos.ContentRepository:NodeAggregateWithNodeWasCreated" was published to stream "Neos.ContentRepository:ContentStream:[cs-identifier]:NodeAggregate:[source-nodeAgg-identifier]" with payload:
      | Key                           | Value                                     | Type                   |
      | contentStreamIdentifier       | cs-identifier                             | Uuid                   |
      | nodeAggregateIdentifier       | source-nodeAgg-identifier                 | Uuid                   |
      | nodeTypeName                  | Neos.ContentRepository:NodeWithReferences |                        |
      | dimensionSpacePoint           | {"coordinates": []}                       | json                   |
      | visibleDimensionSpacePoints   | {"points":[{"coordinates":[]}]}           | DimensionSpacePointSet |
      | nodeIdentifier                | source-node-identifier                    | Uuid                   |
      | parentNodeIdentifier          | rn-identifier                             | Uuid                   |
      | nodeName                      | source                                    |                        |
      | propertyDefaultValuesAndTypes | {}                                        | json                   |

    And the Event "Neos.ContentRepository:NodeAggregateWithNodeWasCreated" was published to stream "Neos.ContentRepository:ContentStream:[cs-identifier]:NodeAggregate:[dest-nodeAgg-identifier]" with payload:
      | Key                           | Value                                     | Type                   |
      | contentStreamIdentifier       | cs-identifier                             | Uuid                   |
      | nodeAggregateIdentifier       | dest-1-nodeAgg-identifier                 | Uuid                   |
      | nodeTypeName                  | Neos.ContentRepository:NodeWithReferences |                        |
      | dimensionSpacePoint           | {"coordinates": []}                       | json                   |
      | visibleDimensionSpacePoints   | {"points":[{"coordinates":[]}]}           | DimensionSpacePointSet |
      | nodeIdentifier                | dest-node-identifier                      | Uuid                   |
      | parentNodeIdentifier          | rn-identifier                             | Uuid                   |
      | nodeName                      | dest-1                                    |                        |
      | propertyDefaultValuesAndTypes | {}                                        | json                   |


    And the Event "Neos.ContentRepository:NodeAggregateWithNodeWasCreated" was published to stream "Neos.ContentRepository:ContentStream:[cs-identifier]:NodeAggregate:[dest-nodeAgg-identifier]" with payload:
      | Key                           | Value                                     | Type                   |
      | contentStreamIdentifier       | cs-identifier                             | Uuid                   |
      | nodeAggregateIdentifier       | dest-2-nodeAgg-identifier                 | Uuid                   |
      | nodeTypeName                  | Neos.ContentRepository:NodeWithReferences |                        |
      | dimensionSpacePoint           | {"coordinates": []}                       | json                   |
      | visibleDimensionSpacePoints   | {"points":[{"coordinates":[]}]}           | DimensionSpacePointSet |
      | nodeIdentifier                | dest-node-identifier                      | Uuid                   |
      | parentNodeIdentifier          | rn-identifier                             | Uuid                   |
      | nodeName                      | dest-2                                    |                        |
      | propertyDefaultValuesAndTypes | {}                                        | json                   |

    And the Event "Neos.ContentRepository:NodeAggregateWithNodeWasCreated" was published to stream "Neos.ContentRepository:ContentStream:[cs-identifier]:NodeAggregate:[dest-nodeAgg-identifier]" with payload:
      | Key                           | Value                                     | Type                   |
      | contentStreamIdentifier       | cs-identifier                             | Uuid                   |
      | nodeAggregateIdentifier       | dest-3-nodeAgg-identifier                 | Uuid                   |
      | nodeTypeName                  | Neos.ContentRepository:NodeWithReferences |                        |
      | dimensionSpacePoint           | {"coordinates": []}                       | json                   |
      | visibleDimensionSpacePoints   | {"points":[{"coordinates":[]}]}           | DimensionSpacePointSet |
      | nodeIdentifier                | dest-node-identifier                      | Uuid                   |
      | parentNodeIdentifier          | rn-identifier                             | Uuid                   |
      | nodeName                      | dest-3                                    |                        |
      | propertyDefaultValuesAndTypes | {}                                        | json                   |

  Scenario: Ensure that a reference between nodes can be set and read

    When the command "SetNodeReferences" is executed with payload:
      | Key                                  | Value                     | Type   |
      | contentStreamIdentifier              | cs-identifier             | Uuid   |
      | nodeIdentifier                       | source-node-identifier    | Uuid   |
      | propertyName                         | referenceProperty         |        |
      | destinationtNodeAggregateIdentifiers | dest-1-nodeAgg-identifier | Uuid[] |

    And the graph projection is fully up to date
    And I am in content stream "[cs-identifier]" and Dimension Space Point {}

    Then I expect the Node "[source-node-identifier]" to have the references:
      | Key               | Value                     | Type   |
      | referenceProperty | dest-1-nodeAgg-identifier | Uuid[] |

  Scenario: Ensure that references between nodes can be set and overwritten

    When the command "SetNodeReferences" is executed with payload:
      | Key                                  | Value                                               | Type   |
      | contentStreamIdentifier              | cs-identifier                                       | Uuid   |
      | nodeIdentifier                       | source-node-identifier                              | Uuid   |
      | propertyName                         | referencesProperty                                  |        |
      | destinationtNodeAggregateIdentifiers | dest-2-nodeAgg-identifier,dest-3-nodeAgg-identifier | Uuid[] |

    And the graph projection is fully up to date
    And I am in content stream "[cs-identifier]" and Dimension Space Point {}

    Then I expect the Node "[source-node-identifier]" to have the references:
      | Key                | Value                                               | Type   |
      | referencesProperty | dest-2-nodeAgg-identifier,dest-3-nodeAgg-identifier | Uuid[] |

    When the command "SetNodeReferences" is executed with payload:
      | Key                                  | Value                     | Type   |
      | contentStreamIdentifier              | cs-identifier             | Uuid   |
      | nodeIdentifier                       | source-node-identifier    | Uuid   |
      | propertyName                         | referencesProperty        |        |
      | destinationtNodeAggregateIdentifiers | dest-1-nodeAgg-identifier | Uuid[] |

    And the graph projection is fully up to date
    And I am in content stream "[cs-identifier]" and Dimension Space Point {}

    Then I expect the Node "[source-node-identifier]" to have the references:
      | Key                | Value                     | Type   |
      | referencesProperty | dest-1-nodeAgg-identifier | Uuid[] |

  Scenario: Ensure that references between nodes can be set and reordered

    When the command "SetNodeReferences" is executed with payload:
      | Key                                  | Value                                               | Type   |
      | contentStreamIdentifier              | cs-identifier                                       | Uuid   |
      | nodeIdentifier                       | source-node-identifier                              | Uuid   |
      | propertyName                         | referencesProperty                                  |        |
      | destinationtNodeAggregateIdentifiers | dest-2-nodeAgg-identifier,dest-3-nodeAgg-identifier | Uuid[] |

    And the graph projection is fully up to date
    And I am in content stream "[cs-identifier]" and Dimension Space Point {}

    Then I expect the Node "[source-node-identifier]" to have the references:
      | Key                | Value                                               | Type   |
      | referencesProperty | dest-2-nodeAgg-identifier,dest-3-nodeAgg-identifier | Uuid[] |

    When the command "SetNodeReferences" is executed with payload:
      | Key                                  | Value                                               | Type   |
      | contentStreamIdentifier              | cs-identifier                                       | Uuid   |
      | nodeIdentifier                       | source-node-identifier                              | Uuid   |
      | propertyName                         | referencesProperty                                  |        |
      | destinationtNodeAggregateIdentifiers | dest-3-nodeAgg-identifier,dest-2-nodeAgg-identifier | Uuid[] |

    And the graph projection is fully up to date
    And I am in content stream "[cs-identifier]" and Dimension Space Point {}

    Then I expect the Node "[source-node-identifier]" to have the references:
      | Key                | Value                                               | Type   |
      | referencesProperty | dest-3-nodeAgg-identifier,dest-2-nodeAgg-identifier | Uuid[] |

  Scenario: Ensure that references between nodes can be deleted

    When the command "SetNodeReferences" is executed with payload:
      | Key                                  | Value                                               | Type   |
      | contentStreamIdentifier              | cs-identifier                                       | Uuid   |
      | nodeIdentifier                       | source-node-identifier                              | Uuid   |
      | propertyName                         | referencesProperty                                  |        |
      | destinationtNodeAggregateIdentifiers | dest-2-nodeAgg-identifier,dest-3-nodeAgg-identifier | Uuid[] |

    And the graph projection is fully up to date
    And I am in content stream "[cs-identifier]" and Dimension Space Point {}

    Then I expect the Node "[source-node-identifier]" to have the references:
      | Key                | Value                                               | Type   |
      | referencesProperty | dest-2-nodeAgg-identifier,dest-3-nodeAgg-identifier | Uuid[] |

    When the command "SetNodeReferences" is executed with payload:
      | Key                                  | Value                  | Type   |
      | contentStreamIdentifier              | cs-identifier          | Uuid   |
      | nodeIdentifier                       | source-node-identifier | Uuid   |
      | propertyName                         | referencesProperty     |        |
      | destinationtNodeAggregateIdentifiers |                        | Uuid[] |

    And the graph projection is fully up to date
    And I am in content stream "[cs-identifier]" and Dimension Space Point {}

    Then I expect the Node "[source-node-identifier]" to have the references:
      | Key                | Value | Type   |
      | referencesProperty |       | Uuid[] |
