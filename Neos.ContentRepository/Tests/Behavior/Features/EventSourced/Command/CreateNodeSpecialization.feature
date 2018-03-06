@fixtures
Feature: Create node specialization

  As a user of the CR I want to create a copy of a node within an aggregate in another dimension space point as a
  specialization.

  Background:
    Given I have the following content dimensions:
      | Identifier | Default | Values  | Generalizations |
      | market     | DE      | DE, CH  | CH->DE          |
      | language   | de      | de, gsw | gsw->de         |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository:Document': []
    """
    And the Event RootNodeWasCreated was published with payload:
      | Key                      | Value                                | Type |
      | workspaceName            | live                                 |      |
      | workspaceTitle           | Live                                 |      |
      | workspaceDescription     | The live workspace                   |      |
      | initiatingUserIdentifier | 00000000-0000-0000-0000-000000000000 |      |
      | contentStreamIdentifier  | cs-identifier                        | Uuid |
      | nodeIdentifier           | rn-identifier                        | Uuid |
      | nodeTypeName             | Neos.ContentRepository:Root          |      |
    # We have to add another node since root nodes have no dimension space points and thus cannot be varied
    # Node /document
    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                                                                                                                                                 | Type                   |
      | contentStreamIdentifier       | cs-identifier                                                                                                                                                                                                         | Uuid                   |
      | nodeAggregateIdentifier       | doc-agg-identifier                                                                                                                                                                                                    | Uuid                   |
      | nodeTypeName                  | Neos.ContentRepository:Document                                                                                                                                                                                       |                        |
      | dimensionSpacePoint           | {"market":"DE", "language":"de"}                                                                                                                                                                                      | DimensionSpacePoint    |
      | visibleDimensionSpacePoints   | {"points":[{"coordinates":{"market":"DE", "language":"de"}},{"coordinates":{"market": "DE", "language":"gsw"}},{"coordinates":{"market": "CH", "language":"de"}},{"coordinates":{"market": "CH", "language":"gsw"}}]} | DimensionSpacePointSet |
      | nodeIdentifier                | doc-identifier-de-de                                                                                                                                                                                                  | Uuid                   |
      | parentNodeIdentifier          | rn-identifier                                                                                                                                                                                                         | Uuid                   |
      | nodeName                      | document                                                                                                                                                                                                              |                        |
      | propertyDefaultValuesAndTypes | {}                                                                                                                                                                                                                    | json                   |
    # We also want to add a child node to make sure it is still reachable after specializing the parent
    # Node /document/child-document
    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                                                                                                                                                 | Type                   |
      | contentStreamIdentifier       | cs-identifier                                                                                                                                                                                                         | Uuid                   |
      | nodeAggregateIdentifier       | cdoc-agg-identifier                                                                                                                                                                                                   | Uuid                   |
      | nodeTypeName                  | Neos.ContentRepository:Document                                                                                                                                                                                       |                        |
      | dimensionSpacePoint           | {"market":"DE", "language":"de"}                                                                                                                                                                                      | DimensionSpacePoint    |
      | visibleDimensionSpacePoints   | {"points":[{"coordinates":{"market":"DE", "language":"de"}},{"coordinates":{"market": "DE", "language":"gsw"}},{"coordinates":{"market": "CH", "language":"de"}},{"coordinates":{"market": "CH", "language":"gsw"}}]} | DimensionSpacePointSet |
      | nodeIdentifier                | cdoc-identifier-de-de                                                                                                                                                                                                 | Uuid                   |
      | parentNodeIdentifier          | doc-identifier-de-de                                                                                                                                                                                                  | Uuid                   |
      | nodeName                      | child-document                                                                                                                                                                                                        |                        |
      | propertyDefaultValuesAndTypes | {}                                                                                                                                                                                                                    | json                   |

  Scenario: Specialize node to dimension space point without further specializations
    When the command CreateNodeSpecialization was published with payload:
      | Key                       | Value                             | Type                |
      | contentStreamIdentifier   | cs-identifier                     | Uuid                |
      | nodeIdentifier            | doc-identifier-de-de              | Uuid                |
      | targetDimensionSpacePoint | {"market":"CH", "language":"gsw"} | DimensionSpacePoint |
      | specializationIdentifier  | doc-identifier-ch-gsw             | Uuid                |
    And the graph projection is fully up to date
    And I am in content stream "[cs-identifier]" and Dimension Space Point {"market":"CH", "language":"gsw"}
    Then I expect a node "[doc-identifier-ch-gsw]" to exist in the graph projection
    And I expect a node "[doc-identifier-de-de]" not to exist in the graph projection
    And I expect the path "document" to lead to the node "[doc-identifier-ch-gsw]"
    And I expect the path "document/child-document" to lead to the node "[cdoc-identifier-de-de]"
    When I am in content stream "[cs-identifier]" and Dimension Space Point {"market":"DE", "language":"de"}
    Then I expect a node "[doc-identifier-de-de]" to exist in the graph projection
    And I expect a node "[doc-identifier-ch-gsw]" not to exist in the graph projection
    And I expect the path "document" to lead to the node "[doc-identifier-de-de]"
    And I expect the path "document/child-document" to lead to the node "[cdoc-identifier-de-de]"
    When I am in content stream "[cs-identifier]" and Dimension Space Point {"market":"DE", "language":"gsw"}
    Then I expect a node "[doc-identifier-de-de]" to exist in the graph projection
    And I expect a node "[doc-identifier-ch-gsw]" not to exist in the graph projection
    And I expect the path "document" to lead to the node "[doc-identifier-de-de]"
    And I expect the path "document/child-document" to lead to the node "[cdoc-identifier-de-de]"
    When I am in content stream "[cs-identifier]" and Dimension Space Point {"market":"CH", "language":"de"}
    Then I expect a node "[doc-identifier-de-de]" to exist in the graph projection
    And I expect a node "[doc-identifier-ch-gsw]" not to exist in the graph projection
    And I expect the path "document" to lead to the node "[doc-identifier-de-de]"
    And I expect the path "document/child-document" to lead to the node "[cdoc-identifier-de-de]"

  Scenario: Specialize node to dimension space point with further specializations
    When the command CreateNodeSpecialization was published with payload:
      | Key                       | Value                             | Type                |
      | contentStreamIdentifier   | cs-identifier                     | Uuid                |
      | nodeIdentifier            | doc-identifier-de-de              | Uuid                |
      | targetDimensionSpacePoint | {"market":"DE", "language":"gsw"} | DimensionSpacePoint |
      | specializationIdentifier  | doc-identifier-de-gsw             | Uuid                |
    And the graph projection is fully up to date
    And I am in content stream "[cs-identifier]" and Dimension Space Point {"market":"CH", "language":"gsw"}
    Then I expect a node "[doc-identifier-de-gsw]" to exist in the graph projection
    And I expect a node "[doc-identifier-de-de]" not to exist in the graph projection
    And I expect the path "document" to lead to the node "[doc-identifier-de-gsw]"
    And I expect the path "document/child-document" to lead to the node "[cdoc-identifier-de-de]"
    When I am in content stream "[cs-identifier]" and Dimension Space Point {"market":"DE", "language":"de"}
    Then I expect a node "[doc-identifier-de-de]" to exist in the graph projection
    And I expect a node "[doc-identifier-de-gsw]" not to exist in the graph projection
    And I expect the path "document" to lead to the node "[doc-identifier-de-de]"
    And I expect the path "document/child-document" to lead to the node "[cdoc-identifier-de-de]"
    When I am in content stream "[cs-identifier]" and Dimension Space Point {"market":"DE", "language":"gsw"}
    Then I expect a node "[doc-identifier-de-de]" not to exist in the graph projection
    And I expect a node "[doc-identifier-de-gsw]" to exist in the graph projection
    And I expect the path "document" to lead to the node "[doc-identifier-de-gsw]"
    And I expect the path "document/child-document" to lead to the node "[cdoc-identifier-de-de]"
    When I am in content stream "[cs-identifier]" and Dimension Space Point {"market":"CH", "language":"de"}
    Then I expect a node "[doc-identifier-de-de]" to exist in the graph projection
    And I expect a node "[doc-identifier-de-gsw]" not to exist in the graph projection
    And I expect the path "document" to lead to the node "[doc-identifier-de-de]"
    And I expect the path "document/child-document" to lead to the node "[cdoc-identifier-de-de]"

  # @todo test against already existing variants in the extended visibility subspace
  # @todo test against parent visibility subspace

  Scenario: Try to specialize a node to a more general dimension space point
    Given the event NodeSpecializationWasCreated was published with payload:
      | Key                      | Value                                                          | Type                   |
      | contentStreamIdentifier  | cs-identifier                                                  | Uuid                   |
      | nodeIdentifier           | doc-identifier-de-de                                           | Uuid                   |
      | specializationIdentifier | doc-identifier-ch-gsw                                          | Uuid                   |
      | specializationLocation   | {"market":"CH", "language":"gsw"}                              | DimensionSpacePoint    |
      | specializationVisibility | {"points":[{"coordinates":{"market":"CH", "language":"gsw"}}]} | DimensionSpacePointSet |

    When the command CreateNodeSpecialization was published with payload and exceptions are caught:
      | Key                       | Value                             | Type                |
      | contentStreamIdentifier   | cs-identifier                     | Uuid                |
      | nodeIdentifier            | doc-identifier-ch-gsw             | Uuid                |
      | targetDimensionSpacePoint | {"market":"DE", "language":"gsw"} | DimensionSpacePoint |
      | specializationIdentifier  | doc-identifier-de-gsw             | Uuid                |
    Then the last command should have thrown an exception of type "DimensionSpacePointIsNoSpecialization"

  Scenario: Try to specialize a node to an already occupied dimension space point
    Given the event NodeSpecializationWasCreated was published with payload:
      | Key                      | Value                                                          | Type                   |
      | contentStreamIdentifier  | cs-identifier                                                  | Uuid                   |
      | nodeIdentifier           | doc-identifier-de-de                                           | Uuid                   |
      | specializationIdentifier | doc-identifier-ch-gsw                                          | Uuid                   |
      | specializationLocation   | {"market":"CH", "language":"gsw"}                              | DimensionSpacePoint    |
      | specializationVisibility | {"points":[{"coordinates":{"market":"CH", "language":"gsw"}}]} | DimensionSpacePointSet |

    When the command CreateNodeSpecialization was published with payload and exceptions are caught:
      | Key                       | Value                             | Type                |
      | contentStreamIdentifier   | cs-identifier                     | Uuid                |
      | nodeIdentifier            | doc-identifier-de-de              | Uuid                |
      | targetDimensionSpacePoint | {"market":"CH", "language":"gsw"} | DimensionSpacePoint |
      | specializationIdentifier  | doc-identifier-ch-gsw             | Uuid                |
    Then the last command should have thrown an exception of type "DimensionSpacePointIsAlreadyOccupiedInNodeAggregate"