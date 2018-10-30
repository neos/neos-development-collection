@fixtures
Feature: Node References without Dimensions

  References between nodes can be reated, overweitten, reordered and deleted

  Background:
    Given I have no content dimensions
    And the command CreateWorkspace is executed with payload:
      | Key                      | Value                                | Type |
      | workspaceName            | live                                 |      |
      | contentStreamIdentifier  | cs-identifier                        | Uuid |
      | rootNodeIdentifier       | rn-identifier                        | Uuid |

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

    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                     | Type                   |
      | contentStreamIdentifier       | cs-identifier                             | Uuid                   |
      | nodeAggregateIdentifier       | source-nodeAgg-identifier                 | Uuid                   |
      | nodeTypeName                  | Neos.ContentRepository:NodeWithReferences |                        |
      | nodeIdentifier                | source-node-identifier                    | Uuid                   |
      | parentNodeIdentifier          | rn-identifier                             | Uuid                   |
      | nodeName                      | source                                    |                        |

    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                     | Type                   |
      | contentStreamIdentifier     | cs-identifier                             | Uuid                   |
      | nodeAggregateIdentifier     | dest-1-nodeAgg-identifier                 | Uuid                   |
      | nodeTypeName                | Neos.ContentRepository:NodeWithReferences |                        |
      | visibleInDimensionSpacePoints | [{}]                                      | DimensionSpacePointSet |
      | nodeIdentifier              | dest-1-node-identifier                    | Uuid                   |
      | parentNodeIdentifier        | rn-identifier                             | Uuid                   |
      | nodeName                    | dest-1                                    |                        |


    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                     | Type                   |
      | contentStreamIdentifier     | cs-identifier                             | Uuid                   |
      | nodeAggregateIdentifier     | dest-2-nodeAgg-identifier                 | Uuid                   |
      | nodeTypeName                | Neos.ContentRepository:NodeWithReferences |                        |
      | visibleInDimensionSpacePoints | [{}]                                      | DimensionSpacePointSet |
      | nodeIdentifier              | dest-2-node-identifier                    | Uuid                   |
      | parentNodeIdentifier        | rn-identifier                             | Uuid                   |
      | nodeName                    | dest-2                                    |                        |

    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                     | Type                   |
      | contentStreamIdentifier     | cs-identifier                             | Uuid                   |
      | nodeAggregateIdentifier     | dest-3-nodeAgg-identifier                 | Uuid                   |
      | nodeTypeName                | Neos.ContentRepository:NodeWithReferences |                        |
      | visibleInDimensionSpacePoints | [{}]                                      | DimensionSpacePointSet |
      | nodeIdentifier              | dest-3-node-identifier                    | Uuid                   |
      | parentNodeIdentifier        | rn-identifier                             | Uuid                   |
      | nodeName                    | dest-3                                    |                        |

  Scenario: Ensure that a reference between nodes can be set and read

    When the command "SetNodeReferences" is executed with payload:
      | Key                                 | Value                     | Type   |
      | contentStreamIdentifier             | cs-identifier             | Uuid   |
      | nodeIdentifier                      | source-node-identifier    | Uuid   |
      | propertyName                        | referenceProperty         |        |
      | destinationNodeAggregateIdentifiers | dest-1-nodeAgg-identifier | Uuid[] |

    And the graph projection is fully up to date
    And I am in content stream "[cs-identifier]" and Dimension Space Point {}

    Then I expect the Node "[source-node-identifier]" to have the references:
      | Key               | Value                     | Type   |
      | referenceProperty | dest-1-nodeAgg-identifier | Uuid[] |

    And I expect the Node "[dest-1-node-identifier]" to be referenced by:
      | Key               | Value                     | Type   |
      | referenceProperty | source-nodeAgg-identifier | Uuid[] |

  Scenario: Ensure that references between nodes can be set and red

    When the command "SetNodeReferences" is executed with payload:
      | Key                                 | Value                                               | Type   |
      | contentStreamIdentifier             | cs-identifier                                       | Uuid   |
      | nodeIdentifier                      | source-node-identifier                              | Uuid   |
      | propertyName                        | referencesProperty                                  |        |
      | destinationNodeAggregateIdentifiers | dest-2-nodeAgg-identifier,dest-3-nodeAgg-identifier | Uuid[] |

    And the graph projection is fully up to date
    And I am in content stream "[cs-identifier]" and Dimension Space Point {}

    Then I expect the Node "[source-node-identifier]" to have the references:
      | Key                | Value                                               | Type   |
      | referencesProperty | dest-2-nodeAgg-identifier,dest-3-nodeAgg-identifier | Uuid[] |

    And I expect the Node "[dest-2-node-identifier]" to be referenced by:
      | Key                | Value                     | Type   |
      | referencesProperty | source-nodeAgg-identifier | Uuid[] |

    And I expect the Node "[dest-3-node-identifier]" to be referenced by:
      | Key                | Value                     | Type   |
      | referencesProperty | source-nodeAgg-identifier | Uuid[] |

  Scenario: Ensure that references between nodes can be set and overwritten

    When the command "SetNodeReferences" is executed with payload:
      | Key                                 | Value                                               | Type   |
      | contentStreamIdentifier             | cs-identifier                                       | Uuid   |
      | nodeIdentifier                      | source-node-identifier                              | Uuid   |
      | propertyName                        | referencesProperty                                  |        |
      | destinationNodeAggregateIdentifiers | dest-2-nodeAgg-identifier,dest-3-nodeAgg-identifier | Uuid[] |

    And the graph projection is fully up to date
    And I am in content stream "[cs-identifier]" and Dimension Space Point {}

    Then I expect the Node "[source-node-identifier]" to have the references:
      | Key                | Value                                               | Type   |
      | referencesProperty | dest-2-nodeAgg-identifier,dest-3-nodeAgg-identifier | Uuid[] |

    When the command "SetNodeReferences" is executed with payload:
      | Key                                 | Value                     | Type   |
      | contentStreamIdentifier             | cs-identifier             | Uuid   |
      | nodeIdentifier                      | source-node-identifier    | Uuid   |
      | propertyName                        | referencesProperty        |        |
      | destinationNodeAggregateIdentifiers | dest-1-nodeAgg-identifier | Uuid[] |

    And the graph projection is fully up to date
    And I am in content stream "[cs-identifier]" and Dimension Space Point {}

    Then I expect the Node "[source-node-identifier]" to have the references:
      | Key                | Value                     | Type   |
      | referencesProperty | dest-1-nodeAgg-identifier | Uuid[] |

  Scenario: Ensure that references between nodes can be set and reordered

    When the command "SetNodeReferences" is executed with payload:
      | Key                                 | Value                                               | Type   |
      | contentStreamIdentifier             | cs-identifier                                       | Uuid   |
      | nodeIdentifier                      | source-node-identifier                              | Uuid   |
      | propertyName                        | referencesProperty                                  |        |
      | destinationNodeAggregateIdentifiers | dest-2-nodeAgg-identifier,dest-3-nodeAgg-identifier | Uuid[] |

    And the graph projection is fully up to date
    And I am in content stream "[cs-identifier]" and Dimension Space Point {}

    Then I expect the Node "[source-node-identifier]" to have the references:
      | Key                | Value                                               | Type   |
      | referencesProperty | dest-2-nodeAgg-identifier,dest-3-nodeAgg-identifier | Uuid[] |

    When the command "SetNodeReferences" is executed with payload:
      | Key                                 | Value                                               | Type   |
      | contentStreamIdentifier             | cs-identifier                                       | Uuid   |
      | nodeIdentifier                      | source-node-identifier                              | Uuid   |
      | propertyName                        | referencesProperty                                  |        |
      | destinationNodeAggregateIdentifiers | dest-3-nodeAgg-identifier,dest-2-nodeAgg-identifier | Uuid[] |

    And the graph projection is fully up to date
    And I am in content stream "[cs-identifier]" and Dimension Space Point {}

    Then I expect the Node "[source-node-identifier]" to have the references:
      | Key                | Value                                               | Type   |
      | referencesProperty | dest-3-nodeAgg-identifier,dest-2-nodeAgg-identifier | Uuid[] |

  Scenario: Ensure that references between nodes can be deleted

    When the command "SetNodeReferences" is executed with payload:
      | Key                                 | Value                                               | Type   |
      | contentStreamIdentifier             | cs-identifier                                       | Uuid   |
      | nodeIdentifier                      | source-node-identifier                              | Uuid   |
      | propertyName                        | referencesProperty                                  |        |
      | destinationNodeAggregateIdentifiers | dest-2-nodeAgg-identifier,dest-3-nodeAgg-identifier | Uuid[] |

    And the graph projection is fully up to date
    And I am in content stream "[cs-identifier]" and Dimension Space Point {}

    Then I expect the Node "[source-node-identifier]" to have the references:
      | Key                | Value                                               | Type   |
      | referencesProperty | dest-2-nodeAgg-identifier,dest-3-nodeAgg-identifier | Uuid[] |

    When the command "SetNodeReferences" is executed with payload:
      | Key                                 | Value                  | Type   |
      | contentStreamIdentifier             | cs-identifier          | Uuid   |
      | nodeIdentifier                      | source-node-identifier | Uuid   |
      | propertyName                        | referencesProperty     |        |
      | destinationNodeAggregateIdentifiers |                        | Uuid[] |

    And the graph projection is fully up to date
    And I am in content stream "[cs-identifier]" and Dimension Space Point {}

    Then I expect the Node "[source-node-identifier]" to have the references:
      | Key                | Value | Type   |
      | referencesProperty |       | Uuid[] |

  Scenario: Ensure that references from multiple nodes read from the opposing side

    When the command "SetNodeReferences" is executed with payload:
      | Key                                 | Value                     | Type   |
      | contentStreamIdentifier             | cs-identifier             | Uuid   |
      | nodeIdentifier                      | source-node-identifier    | Uuid   |
      | propertyName                        | referenceProperty         |        |
      | destinationNodeAggregateIdentifiers | dest-1-nodeAgg-identifier | Uuid[] |

    And the command "SetNodeReferences" is executed with payload:
      | Key                                 | Value                     | Type   |
      | contentStreamIdentifier             | cs-identifier             | Uuid   |
      | nodeIdentifier                      | dest-2-node-identifier    | Uuid   |
      | propertyName                        | referenceProperty         |        |
      | destinationNodeAggregateIdentifiers | dest-1-nodeAgg-identifier | Uuid[] |

    And the graph projection is fully up to date

    And I am in content stream "[cs-identifier]" and Dimension Space Point {}

    Then I expect the Node "[dest-1-node-identifier]" to be referenced by:
      | Key               | Value                                               | Type   |
      | referenceProperty | source-nodeAgg-identifier,dest-2-nodeAgg-identifier | Uuid[] |

