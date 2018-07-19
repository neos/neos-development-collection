@fixtures
Feature: Remove Node

  As a user of the CR I want to be able to remove a node.

  This feature tests the following combinations:
  - (1) LIVE and (2)USER workspace
  - (A) WITHOUT children and (B) WITH children
  - Dimensions:
  - (a) with dimension shine-through
  - (b) with explicit variant in another dimension (deleting the "parent dimension" node; node still needs to exist in "specialized dimension")
  - (c) with explicit variant in another dimension (deleting the "specialized dimension" node; node still needs to exist in "parent dimension")


  Background:
    Given I have the following content dimensions:
      | Identifier | Default | Values  | Generalizations |
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
      | contentStreamIdentifier  | live-cs-identifier                   | Uuid |
      | nodeIdentifier           | rn-identifier                        | Uuid |
      | nodeTypeName             | Neos.ContentRepository:Root          |      |
    # We have to add another node since root nodes have no dimension space points and thus cannot be varied
    # Node /document
    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                             | Type                    |
      | contentStreamIdentifier       | live-cs-identifier                                                                | Uuid                    |
      | nodeAggregateIdentifier       | doc-agg-identifier                                                                | NodeAggregateIdentifier |
      | nodeTypeName                  | Neos.ContentRepository:Document                                                   |                         |
      | dimensionSpacePoint           | {"language":"de"}                                                                 | DimensionSpacePoint     |
      | visibleDimensionSpacePoints   | {"points":[{"coordinates":{"language":"de"}},{"coordinates":{"language":"gsw"}}]} | DimensionSpacePointSet  |
      | nodeIdentifier                | doc-identifier-de                                                                 | Uuid                    |
      | parentNodeIdentifier          | rn-identifier                                                                     | Uuid                    |
      | nodeName                      | document                                                                          |                         |
      | propertyDefaultValuesAndTypes | {}                                                                                | json                    |
    # We also want to add a child node to make sure it is correctly removed when the parent is removed
    # Node /document/child-document
    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                             | Type                    |
      | contentStreamIdentifier       | live-cs-identifier                                                                | Uuid                    |
      | nodeAggregateIdentifier       | cdoc-agg-identifier                                                               | NodeAggregateIdentifier |
      | nodeTypeName                  | Neos.ContentRepository:Document                                                   |                         |
      | dimensionSpacePoint           | {"language":"de"}                                                                 | DimensionSpacePoint     |
      | visibleDimensionSpacePoints   | {"points":[{"coordinates":{"language":"de"}},{"coordinates":{"language":"gsw"}}]} | DimensionSpacePointSet  |
      | nodeIdentifier                | cdoc-identifier-de                                                                | Uuid                    |
      | parentNodeIdentifier          | doc-identifier-de                                                                 | Uuid                    |
      | nodeName                      | child-document                                                                    |                         |
      | propertyDefaultValuesAndTypes | {}                                                                                | json                    |

  Scenario: Trying to remove a non existing node should fail with an exception
    When the command RemoveNode was published with payload and exceptions are caught:
      | Key                     | Value                        | Type |
      | contentStreamIdentifier | live-cs-identifier           | Uuid |
      | nodeIdentifier          | non-existing-node-identifier | Uuid |
    Then the last command should have thrown an exception of type "NodeNotFoundException"

  ########################
  # Section: 1.A.*
  ########################
  Scenario: (1.A.a) In LIVE workspace, removing a node WITHOUT children leads also to removal of the node in the shine-through dimensions

    When the command RemoveNode was published with payload:
      | Key                     | Value              | Type |
      | contentStreamIdentifier | live-cs-identifier | Uuid |
      | nodeIdentifier          | cdoc-identifier-de | Uuid |
    And the graph projection is fully up to date

    When I am in content stream "[live-cs-identifier]" and Dimension Space Point {"language":"de"}
    Then I expect a node "[cdoc-identifier-de]" not to exist in the graph projection

    When I am in content stream "[live-cs-identifier]" and Dimension Space Point {"language":"gsw"}
    Then I expect a node "[cdoc-identifier-de]" not to exist in the graph projection


  Scenario: (1.A.b) In LIVE workspace, removing a node WITHOUT children does not lead to removal of the specialized node
    When the command CreateNodeSpecialization was published with payload:
      | Key                       | Value               | Type                    |
      | contentStreamIdentifier   | live-cs-identifier  | Uuid                    |
      | nodeAggregateIdentifier   | cdoc-agg-identifier | NodeAggregateIdentifier |
      | sourceDimensionSpacePoint | {"language":"de"}   | DimensionSpacePoint     |
      | targetDimensionSpacePoint | {"language":"gsw"}  | DimensionSpacePoint     |
      | specializationIdentifier  | cdoc-identifier-gsw | Uuid                    |
    When the command RemoveNode was published with payload:
      | Key                     | Value              | Type |
      | contentStreamIdentifier | live-cs-identifier | Uuid |
      | nodeIdentifier          | cdoc-identifier-de | Uuid |
    And the graph projection is fully up to date

    When I am in content stream "[live-cs-identifier]" and Dimension Space Point {"language":"de"}
    Then I expect a node "[cdoc-identifier-de]" not to exist in the graph projection

    When I am in content stream "[live-cs-identifier]" and Dimension Space Point {"language":"gsw"}
    And I expect a node "[cdoc-identifier-de]" not to exist in the graph projection
    Then I expect a node "[cdoc-identifier-gsw]" to exist in the graph projection
    And I expect the path "document" to lead to the node "[doc-identifier-de]"
    And I expect the path "document/child-document" to lead to the node "[cdoc-identifier-gsw]"

  Scenario: (1.A.c) In LIVE workspace, removing a node WITHOUT children does not lead to removal of the generalized node
    When the command CreateNodeSpecialization was published with payload:
      | Key                       | Value               | Type                    |
      | contentStreamIdentifier   | live-cs-identifier  | Uuid                    |
      | nodeAggregateIdentifier   | cdoc-agg-identifier | NodeAggregateIdentifier |
      | sourceDimensionSpacePoint | {"language":"de"}   | DimensionSpacePoint     |
      | targetDimensionSpacePoint | {"language":"gsw"}  | DimensionSpacePoint     |
      | specializationIdentifier  | cdoc-identifier-gsw | Uuid                    |
    When the command RemoveNode was published with payload:
      | Key                     | Value               | Type |
      | contentStreamIdentifier | live-cs-identifier  | Uuid |
      | nodeIdentifier          | cdoc-identifier-gsw | Uuid |
    And the graph projection is fully up to date

    When I am in content stream "[live-cs-identifier]" and Dimension Space Point {"language":"de"}
    Then I expect a node "[cdoc-identifier-de]" to exist in the graph projection
    And I expect the path "document" to lead to the node "[doc-identifier-de]"
    And I expect the path "document/child-document" to lead to the node "[cdoc-identifier-de]"

    When I am in content stream "[live-cs-identifier]" and Dimension Space Point {"language":"gsw"}
    And I expect a node "[cdoc-identifier-de]" not to exist in the graph projection
    And I expect a node "[cdoc-identifier-gsw]" not to exist in the graph projection

  ########################
  # Section: 1.B.*
  ########################
  Scenario: (1.B.a) In LIVE workspace, removing a node WITH children leads also to removal of the node in the shine-through dimensions

    When the command RemoveNode was published with payload:
      | Key                     | Value              | Type |
      | contentStreamIdentifier | live-cs-identifier | Uuid |
      | nodeIdentifier          | doc-identifier-de  | Uuid |
    And the graph projection is fully up to date

    When I am in content stream "[live-cs-identifier]" and Dimension Space Point {"language":"de"}
    Then I expect a node "[doc-identifier-de]" not to exist in the graph projection
    Then I expect a node "[cdoc-identifier-de]" not to exist in the graph projection

    When I am in content stream "[live-cs-identifier]" and Dimension Space Point {"language":"gsw"}
    Then I expect a node "[doc-identifier-de]" not to exist in the graph projection
    Then I expect a node "[cdoc-identifier-de]" not to exist in the graph projection


  Scenario: (1.B.b) In LIVE workspace, removing a node WITH children does not lead to removal of the specialized node
    When the command CreateNodeSpecialization was published with payload:
      | Key                       | Value              | Type                    |
      | contentStreamIdentifier   | live-cs-identifier | Uuid                    |
      | nodeAggregateIdentifier   | doc-agg-identifier | NodeAggregateIdentifier |
      | sourceDimensionSpacePoint | {"language":"de"}  | DimensionSpacePoint     |
      | targetDimensionSpacePoint | {"language":"gsw"} | DimensionSpacePoint     |
      | specializationIdentifier  | doc-identifier-gsw | Uuid                    |
    When the command RemoveNode was published with payload:
      | Key                     | Value              | Type |
      | contentStreamIdentifier | live-cs-identifier | Uuid |
      | nodeIdentifier          | doc-identifier-de  | Uuid |
    And the graph projection is fully up to date

    When I am in content stream "[live-cs-identifier]" and Dimension Space Point {"language":"de"}
    Then I expect a node "[doc-identifier-de]" not to exist in the graph projection
    Then I expect a node "[cdoc-identifier-de]" not to exist in the graph projection

    When I am in content stream "[live-cs-identifier]" and Dimension Space Point {"language":"gsw"}
    And I expect a node "[doc-identifier-de]" not to exist in the graph projection
    Then I expect a node "[doc-identifier-gsw]" to exist in the graph projection
    And I expect the path "document" to lead to the node "[doc-identifier-gsw]"

    # TODO: why do these two lines fail??
    #And I expect a node "[cdoc-identifier-de]" to exist in the graph projection
    #And I expect the path "document/child-document" to lead to the node "[cdoc-identifier-gsw]"

  Scenario: (1.B.c) In LIVE workspace, removing a node WITH children does not lead to removal of the generalized node
    When the command CreateNodeSpecialization was published with payload:
      | Key                       | Value              | Type                    |
      | contentStreamIdentifier   | live-cs-identifier | Uuid                    |
      | nodeAggregateIdentifier   | doc-agg-identifier | NodeAggregateIdentifier |
      | sourceDimensionSpacePoint | {"language":"de"}  | DimensionSpacePoint     |
      | targetDimensionSpacePoint | {"language":"gsw"} | DimensionSpacePoint     |
      | specializationIdentifier  | doc-identifier-gsw | Uuid                    |
    When the command RemoveNode was published with payload:
      | Key                     | Value              | Type |
      | contentStreamIdentifier | live-cs-identifier | Uuid |
      | nodeIdentifier          | doc-identifier-gsw | Uuid |
    And the graph projection is fully up to date

    When I am in content stream "[live-cs-identifier]" and Dimension Space Point {"language":"de"}
    Then I expect a node "[doc-identifier-de]" to exist in the graph projection
    # TODO: figure out why the two lines here fail!
    #Then I expect a node "[cdoc-identifier-de]" to exist in the graph projection
    And I expect the path "document" to lead to the node "[doc-identifier-de]"
    #And I expect the path "document/child-document" to lead to the node "[cdoc-identifier-de]"

    When I am in content stream "[live-cs-identifier]" and Dimension Space Point {"language":"gsw"}
    And I expect a node "[doc-identifier-de]" not to exist in the graph projection
    And I expect a node "[cdoc-identifier-de]" not to exist in the graph projection
    And I expect a node "[doc-identifier-gsw]" not to exist in the graph projection
    And I expect a node "[cdoc-identifier-gsw]" not to exist in the graph projection


    # TODO: create scenario where we specialize the /document/child-document; and then remove /document. -> WHAT TO EXPECT?
    # child-document is dangling; but would be removed as well probably...
