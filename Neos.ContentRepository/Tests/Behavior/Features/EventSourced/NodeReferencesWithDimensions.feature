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
      | Key                           | Value                                                                               | Type                   |
      | contentStreamIdentifier       | cs-identifier                                                                       | Uuid                   |
      | nodeAggregateIdentifier       | source-nodeAgg-identifier                                                           | Uuid                   |
      | nodeTypeName                  | Neos.ContentRepository:NodeWithReferences                                           |                        |
      | dimensionSpacePoint           | {"coordinates": {"language": "de"}}                                                 | json                   |
      | visibleDimensionSpacePoints   | {"points":[{"coordinates":{"language": "de"}}, {"coordinates":{"language": "ch"}}]} | DimensionSpacePointSet |
      | nodeIdentifier                | source-node-identifier                                                              | Uuid                   |
      | parentNodeIdentifier          | rn-identifier                                                                       | Uuid                   |
      | nodeName                      | dest                                                                                |                        |
      | propertyDefaultValuesAndTypes | {}                                                                                  | json                   |


    And the Event "Neos.ContentRepository:NodeAggregateWithNodeWasCreated" was published to stream "Neos.ContentRepository:ContentStream:[cs-identifier]:NodeAggregate:[source-nodeAgg-identifier]" with payload:
      | Key                           | Value                                                                                                                                                        | Type                   |
      | contentStreamIdentifier       | cs-identifier                                                                                                                                                | Uuid                   |
      | nodeAggregateIdentifier       | dest-nodeAgg-identifier                                                                                                                                      | Uuid                   |
      | nodeTypeName                  | Neos.ContentRepository:NodeWithReferences                                                                                                                    |                        |
      | dimensionSpacePoint           | {"coordinates": {"language": "mul"}}                                                                                                                         | json                   |
      | visibleDimensionSpacePoints   | {"points":[{"coordinates":{"language": "de"}}, {"coordinates":{"language": "en"}}, {"coordinates":{"language": "ch"}}, {"coordinates":{"language": "mul"}}]} | DimensionSpacePointSet |
      | nodeIdentifier                | dest-node-identifier                                                                                                                                         | Uuid                   |
      | parentNodeIdentifier          | rn-identifier                                                                                                                                                | Uuid                   |
      | nodeName                      | dest                                                                                                                                                         |                        |
      | propertyDefaultValuesAndTypes | {}                                                                                                                                                           | json                   |


    And the command "SetNodeReferences" is executed with payload:
      | Key                                  | Value                   | Type   |
      | contentStreamIdentifier              | cs-identifier           | Uuid   |
      | nodeIdentifier                       | source-node-identifier  | Uuid   |
      | propertyName                         | referenceProperty       |        |
      | destinationtNodeAggregateIdentifiers | dest-nodeAgg-identifier | Uuid[] |

    And the graph projection is fully up to date

  Scenario: Ensure that the reference can be read in current dimension

    And I am in content stream "[cs-identifier]" and Dimension Space Point {"language": "de"}

    Then I expect the Node "[source-node-identifier]" to have the references:
      | Key               | Value                   | Type   |
      | referenceProperty | dest-nodeAgg-identifier | Uuid[] |

  Scenario: Ensure that the reference can be read in fallback dimension

    And I am in content stream "[cs-identifier]" and Dimension Space Point {"language": "ch"}

    Then I expect the Node "[source-node-identifier]" to have the references:
      | Key               | Value                   | Type   |
      | referenceProperty | dest-nodeAgg-identifier | Uuid[] |

  Scenario: Ensure that the reference cannot be read in independent dimension

    And I am in content stream "[cs-identifier]" and Dimension Space Point {"language": "en"}

    Then I expect the Node "[source-node-identifier]" to have the references:
      | Key               | Value | Type   |
      | referenceProperty |       | Uuid[] |



