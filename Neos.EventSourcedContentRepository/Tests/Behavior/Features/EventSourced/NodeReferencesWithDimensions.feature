@fixtures
Feature: Node References with Dimensions

  References between nodes are created are available in fallbacks but not in generalisations or independent nodes.

#  @todo implement scenario that verifies references not available in generalisations of the source they are created in
#  @todo implement scenario that verifies references are copied when a node specialisation is created
#  @todo implement scenario that verifies references can be overwritten in node specialisation without affecting the generalization

  Background:
    Given I have the following content dimensions:
      | Identifier | Default | Values          | Generalizations      |
      | language   | mul     | mul, de, en, ch | ch->de->mul, en->mul |

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
      | Key                           | Value                                       |
      | contentStreamIdentifier       | "cs-identifier"                             |
      | nodeAggregateIdentifier       | "source-node-agg-identifier"                |
      | nodeTypeName                  | "Neos.ContentRepository:NodeWithReferences" |
      | dimensionSpacePoint           | {"language": "de"}                          |
      | visibleInDimensionSpacePoints | [{"language": "de"},{"language": "ch"}]     |
      | nodeIdentifier                | "source-node-identifier"                    |
      | parentNodeIdentifier          | "rn-identifier"                             |
      | nodeName                      | "dest"                                      |


    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                          |
      | contentStreamIdentifier       | "cs-identifier"                                                                |
      | nodeAggregateIdentifier       | "dest-node-agg-identifier"                                                     |
      | nodeTypeName                  | "Neos.ContentRepository:NodeWithReferences"                                    |
      | dimensionSpacePoint           | {"language": "mul"}                                                            |
      | visibleInDimensionSpacePoints | [{"language": "de"},{"language": "en"},{"language": "ch"},{"language": "mul"}] |
      | nodeIdentifier                | "dest-node-identifier"                                                         |
      | parentNodeIdentifier          | "rn-identifier"                                                                |
      | nodeName                      | "dest"                                                                         |

    And the graph projection is fully up to date

    And the command "SetNodeReferences" is executed with payload:
      | Key                                 | Value                        |
      | contentStreamIdentifier             | "cs-identifier"              |
      | nodeIdentifier                      | "source-node-identifier"     |
      | propertyName                        | "referenceProperty"          |
      | destinationNodeAggregateIdentifiers | ["dest-node-agg-identifier"] |

    And the graph projection is fully up to date

  Scenario: Ensure that the reference can be read in current dimension

    And I am in content stream "cs-identifier" and Dimension Space Point {"language": "de"}

    Then I expect the Node aggregate "source-node-agg-identifier" to have the references:
      | Key               | Value                        |
      | referenceProperty | ["dest-node-agg-identifier"] |

    And I expect the Node aggregate "dest-node-agg-identifier" to be referenced by:
      | Key               | Value                          |
      | referenceProperty | ["source-node-agg-identifier"] |

  Scenario: Ensure that the reference can be read in fallback dimension

    And I am in content stream "cs-identifier" and Dimension Space Point {"language": "ch"}

    Then I expect the Node aggregate "source-node-agg-identifier" to have the references:
      | Key               | Value                        |
      | referenceProperty | ["dest-node-agg-identifier"] |

    And I expect the Node aggregate "dest-node-agg-identifier" to be referenced by:
      | Key               | Value                          |
      | referenceProperty | ["source-node-agg-identifier"] |

  Scenario: Ensure that the reference cannot be read in independent dimension

    And I am in content stream "cs-identifier" and Dimension Space Point {"language": "en"}

    Then I expect the Node aggregate "source-node-agg-identifier" to have the references:
      | Key               | Value |
      | referenceProperty | []    |

    And I expect the Node aggregate "dest-node-agg-identifier" to be referenced by:
      | Key               | Value |
      | referenceProperty | []    |



