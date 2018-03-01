@fixtures
Feature: Specialize node

  As a user of the CR I want to create a copy of a node in an aggregate in another dimension space point as a
  translation.

  Background:
    Given I have the following content dimensions:
      | Identifier | Default | Values  | Generalizations |
      | language   | de      | de, gsw | gsw->de         |
      | market     | DE      | DE, CH  | CH->DE          |
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
      | dimensionSpacePoint           | {"language":"de", "market":"DE"}                                                                                                                                                                                      | DimensionSpacePoint    |
      | visibleDimensionSpacePoints   | {"points":[{"coordinates":{"market":"DE", "language":"de"}},{"coordinates":{"market": "DE", "language":"gsw"}},{"coordinates":{"market": "CH", "language":"de"}},{"coordinates":{"market": "CH", "language":"gsw"}}]} | DimensionSpacePointSet |
      | nodeIdentifier                | doc-identifier                                                                                                                                                                                                        | Uuid                   |
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
      | dimensionSpacePoint           | {"language":"de", "market":"DE"}                                                                                                                                                                                      | DimensionSpacePoint    |
      | visibleDimensionSpacePoints   | {"points":[{"coordinates":{"market":"DE", "language":"de"}},{"coordinates":{"market": "DE", "language":"gsw"}},{"coordinates":{"market": "CH", "language":"de"}},{"coordinates":{"market": "CH", "language":"gsw"}}]} | DimensionSpacePointSet |
      | nodeIdentifier                | cdoc-identifier                                                                                                                                                                                                       | Uuid                   |
      | parentNodeIdentifier          | doc-identifier                                                                                                                                                                                                        | Uuid                   |
      | nodeName                      | child-document                                                                                                                                                                                                        |                        |
      | propertyDefaultValuesAndTypes | {}                                                                                                                                                                                                                    | json                   |

  Scenario: Try to specialize a node to a more general dimension space point
    When the command SpecializeNode was published with payload and exceptions are caught:
      | Key                       | Value                             | Type                |
      | contentStreamIdentifier   | cs-identifier                     | Uuid                |
      | nodeAggregateIdentifier   | doc-agg-identifier                | Uuid                |
      | sourceDimensionSpacePoint | {"market":"CH", "language":"gsw"} | DimensionSpacePoint |
      | targetDimensionSpacePoint | {"market":"DE", "language":"de"}  | DimensionSpacePoint |
      | specializationIdentifier  | doc-identifier-de-de              | Uuid                |
    Then the last command should have thrown an exception of type "DimensionSpacePointIsNoSpecialization"

  Scenario: Specialize node to dimension space point without further specializations
    When the command SpecializeNode was published with payload:
      | Key                       | Value                             | Type                |
      | contentStreamIdentifier   | cs-identifier                     | Uuid                |
      | nodeAggregateIdentifier   | doc-agg-identifier                | Uuid                |
      | sourceDimensionSpacePoint | {"market":"DE", "language":"de"}  | DimensionSpacePoint |
      | targetDimensionSpacePoint | {"market":"CH", "language":"gsw"} | DimensionSpacePoint |
      | specializationIdentifier  | doc-identifier-ch-gsw             | Uuid                |
    And I am in content stream "[cs-identifier]" and Dimension Space Point {"coordinates":{"language":"gsw", "market":"CH"}}
    Then I expect a node "[doc-identifier-gsw-ch]" to exist in the graph projection
    And I expect a node "[doc-identifier-gsw]" not to exist in the graph projection
    And I expect the path "[child-document]" to lead to the node "cdoc-identifier"
    When I am in content stream "[cs-identifier]" and Dimension Space Point {"coordinates":{"language":"de", "market":"DE"}}
    Then I expect a node "[doc-identifier-gsw]" to exist in the graph projection
    And I expect a node "[doc-identifier-gsw-ch]" not to exist in the graph projection
    And I expect the path "[child-document]" to lead to the node "cdoc-identifier"
    When I am in content stream "[cs-identifier]" and Dimension Space Point {"coordinates":{"language":"gsw", "market":"DE"}}
    Then I expect a node "[doc-identifier-gsw]" to exist in the graph projection
    And I expect a node "[doc-identifier-gsw-ch]" not to exist in the graph projection
    And I expect the path "[child-document]" to lead to the node "cdoc-identifier"
    When I am in content stream "[cs-identifier]" and Dimension Space Point {"coordinates":{"language":"de", "market":"CH"}}
    Then I expect a node "[doc-identifier-gsw]" to exist in the graph projection
    And I expect a node "[doc-identifier-gsw-ch]" not to exist in the graph projection
    And I expect the path "[child-document]" to lead to the node "cdoc-identifier"

  #Scenario: Specialize node to dimension space point with further specializations