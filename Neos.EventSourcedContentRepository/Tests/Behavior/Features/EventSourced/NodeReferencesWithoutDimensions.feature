@fixtures
Feature: Node References without Dimensions

  References between nodes can be reated, overweitten, reordered and deleted

  Background:
    Given I have no content dimensions
    And the command CreateWorkspace is executed with payload:
      | Key                     | Value           |
      | workspaceName           | "live"          |
      | contentStreamIdentifier | "cs-identifier" |
      | rootNodeIdentifier      | "rn-identifier" |

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
      | Key                     | Value                                       |
      | contentStreamIdentifier | "cs-identifier"                             |
      | nodeAggregateIdentifier | "source-node-agg-identifier"                |
      | nodeTypeName            | "Neos.ContentRepository:NodeWithReferences" |
      | nodeIdentifier          | "source-node-identifier"                    |
      | parentNodeIdentifier    | "rn-identifier"                             |
      | nodeName                | "source"                                    |

    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                       |
      | contentStreamIdentifier       | "cs-identifier"                             |
      | nodeAggregateIdentifier       | "dest-1-node-agg-identifier"                |
      | nodeTypeName                  | "Neos.ContentRepository:NodeWithReferences" |
      | visibleInDimensionSpacePoints | [{}]                                        |
      | nodeIdentifier                | "dest-1-node-identifier"                    |
      | parentNodeIdentifier          | "rn-identifier"                             |
      | nodeName                      | "dest-1"                                    |


    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                       |
      | contentStreamIdentifier       | "cs-identifier"                             |
      | nodeAggregateIdentifier       | "dest-2-node-agg-identifier"                |
      | nodeTypeName                  | "Neos.ContentRepository:NodeWithReferences" |
      | visibleInDimensionSpacePoints | [{}]                                        |
      | nodeIdentifier                | "dest-2-node-identifier"                    |
      | parentNodeIdentifier          | "rn-identifier"                             |
      | nodeName                      | "dest-2"                                    |

    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                       |
      | contentStreamIdentifier       | "cs-identifier"                             |
      | nodeAggregateIdentifier       | "dest-3-node-agg-identifier"                |
      | nodeTypeName                  | "Neos.ContentRepository:NodeWithReferences" |
      | visibleInDimensionSpacePoints | [{}]                                        |
      | nodeIdentifier                | "dest-3-node-identifier"                    |
      | parentNodeIdentifier          | "rn-identifier"                             |
      | nodeName                      | "dest-3"                                    |

    And the graph projection is fully up to date

  Scenario: Ensure that a reference between nodes can be set and read

    When the command "SetNodeReferences" is executed with payload:
      | Key                                 | Value                          |
      | contentStreamIdentifier             | "cs-identifier"                |
      | nodeIdentifier                      | "source-node-identifier"       |
      | propertyName                        | "referenceProperty"            |
      | destinationNodeAggregateIdentifiers | ["dest-1-node-agg-identifier"] |

    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and Dimension Space Point {}

    Then I expect the Node aggregate "source-node-agg-identifier" to have the references:
      | Key               | Value                          |
      | referenceProperty | ["dest-1-node-agg-identifier"] |

    And I expect the Node aggregate "dest-1-node-agg-identifier" to be referenced by:
      | Key               | Value                          |
      | referenceProperty | ["source-node-agg-identifier"] |

  Scenario: Ensure that references between nodes can be set and red

    When the command "SetNodeReferences" is executed with payload:
      | Key                                 | Value                                                        |
      | contentStreamIdentifier             | "cs-identifier"                                              |
      | nodeIdentifier                      | "source-node-identifier"                                     |
      | propertyName                        | "referencesProperty"                                         |
      | destinationNodeAggregateIdentifiers | ["dest-2-node-agg-identifier", "dest-3-node-agg-identifier"] |

    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and Dimension Space Point {}

    Then I expect the Node aggregate "source-node-agg-identifier" to have the references:
      | Key                | Value                                                        |
      | referencesProperty | ["dest-2-node-agg-identifier", "dest-3-node-agg-identifier"] |

    And I expect the Node aggregate "dest-2-node-agg-identifier" to be referenced by:
      | Key                | Value                          |
      | referencesProperty | ["source-node-agg-identifier"] |

    And I expect the Node aggregate "dest-3-node-agg-identifier" to be referenced by:
      | Key                | Value                          |
      | referencesProperty | ["source-node-agg-identifier"] |

  Scenario: Ensure that references between nodes can be set and overwritten

    When the command "SetNodeReferences" is executed with payload:
      | Key                                 | Value                                                        |
      | contentStreamIdentifier             | "cs-identifier"                                              |
      | nodeIdentifier                      | "source-node-identifier"                                     |
      | propertyName                        | "referencesProperty"                                         |
      | destinationNodeAggregateIdentifiers | ["dest-2-node-agg-identifier", "dest-3-node-agg-identifier"] |

    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and Dimension Space Point {}

    Then I expect the Node aggregate "source-node-agg-identifier" to have the references:
      | Key                | Value                                                        |
      | referencesProperty | ["dest-2-node-agg-identifier", "dest-3-node-agg-identifier"] |

    When the command "SetNodeReferences" is executed with payload:
      | Key                                 | Value                          |
      | contentStreamIdentifier             | "cs-identifier"                |
      | nodeIdentifier                      | "source-node-identifier"       |
      | propertyName                        | "referencesProperty"           |
      | destinationNodeAggregateIdentifiers | ["dest-1-node-agg-identifier"] |

    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and Dimension Space Point {}

    Then I expect the Node aggregate "source-node-agg-identifier" to have the references:
      | Key                | Value                          |
      | referencesProperty | ["dest-1-node-agg-identifier"] |

  Scenario: Ensure that references between nodes can be set and reordered

    When the command "SetNodeReferences" is executed with payload:
      | Key                                 | Value                                                        |
      | contentStreamIdentifier             | "cs-identifier"                                              |
      | nodeIdentifier                      | "source-node-identifier"                                     |
      | propertyName                        | "referencesProperty"                                         |
      | destinationNodeAggregateIdentifiers | ["dest-2-node-agg-identifier", "dest-3-node-agg-identifier"] |

    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and Dimension Space Point {}

    Then I expect the Node aggregate "source-node-agg-identifier" to have the references:
      | Key                | Value                                                        |
      | referencesProperty | ["dest-2-node-agg-identifier", "dest-3-node-agg-identifier"] |

    When the command "SetNodeReferences" is executed with payload:
      | Key                                 | Value                                                        |
      | contentStreamIdentifier             | "cs-identifier"                                              |
      | nodeIdentifier                      | "source-node-identifier"                                     |
      | propertyName                        | "referencesProperty"                                         |
      | destinationNodeAggregateIdentifiers | ["dest-3-node-agg-identifier", "dest-2-node-agg-identifier"] |

    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and Dimension Space Point {}

    Then I expect the Node aggregate "source-node-agg-identifier" to have the references:
      | Key                | Value                                                        |
      | referencesProperty | ["dest-3-node-agg-identifier", "dest-2-node-agg-identifier"] |

  Scenario: Ensure that references between nodes can be deleted

    When the command "SetNodeReferences" is executed with payload:
      | Key                                 | Value                                                        |
      | contentStreamIdentifier             | "cs-identifier"                                              |
      | nodeIdentifier                      | "source-node-identifier"                                     |
      | propertyName                        | "referencesProperty"                                         |
      | destinationNodeAggregateIdentifiers | ["dest-2-node-agg-identifier", "dest-3-node-agg-identifier"] |

    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and Dimension Space Point {}

    Then I expect the Node aggregate "source-node-agg-identifier" to have the references:
      | Key                | Value                                                        |
      | referencesProperty | ["dest-2-node-agg-identifier", "dest-3-node-agg-identifier"] |

    When the command "SetNodeReferences" is executed with payload:
      | Key                                 | Value                    |
      | contentStreamIdentifier             | "cs-identifier"          |
      | nodeIdentifier                      | "source-node-identifier" |
      | propertyName                        | "referencesProperty"     |
      | destinationNodeAggregateIdentifiers | []                       |

    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and Dimension Space Point {}

    Then I expect the Node aggregate "source-node-agg-identifier" to have the references:
      | Key                | Value |
      | referencesProperty | []    |

  Scenario: Ensure that references from multiple nodes read from the opposing side

    When the command "SetNodeReferences" is executed with payload:
      | Key                                 | Value                          |
      | contentStreamIdentifier             | "cs-identifier"                |
      | nodeIdentifier                      | "source-node-identifier"       |
      | propertyName                        | "referenceProperty"            |
      | destinationNodeAggregateIdentifiers | ["dest-1-node-agg-identifier"] |

    And the command "SetNodeReferences" is executed with payload:
      | Key                                 | Value                          |
      | contentStreamIdentifier             | "cs-identifier"                |
      | nodeIdentifier                      | "dest-2-node-identifier"       |
      | propertyName                        | "referenceProperty"            |
      | destinationNodeAggregateIdentifiers | ["dest-1-node-agg-identifier"] |

    And the graph projection is fully up to date

    And I am in content stream "cs-identifier" and Dimension Space Point {}

    And I expect the Node aggregate "dest-1-node-agg-identifier" to be referenced by:
      | Key               | Value                                                        |
      | referenceProperty | ["source-node-agg-identifier", "dest-2-node-agg-identifier"] |

