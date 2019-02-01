@fixtures
Feature: Remove Nodes from Aggregate

  As a user of the CR I want to be able to remove a node.

  This feature tests the following combinations:
  - workspaces:
  - (1) LIVE
  - (2) USER workspace
  - children:
  - (A) WITHOUT children
  - (B) WITH children
  - Dimensions:
  - (a) with dimension shine-through - when both DimensionSpacePoints are scheduled to be deleted, the node is acutually removed fully.
  - (b) with explicit variant in another dimension (deleting the "child dimension" node; node still needs to exist in "parent dimension")


  Background:
    Given I have the following content dimensions:
      | Identifier | Default | Values  | Generalizations |
      | language   | de      | de, gsw | gsw->de         |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository:Document': []
    """
    And the command "CreateRootNode" is executed with payload:
      | Key                      | Value                                  |
      | contentStreamIdentifier  | "live-cs-identifier"                   |
      | nodeIdentifier           | "rn-identifier"                        |
      | initiatingUserIdentifier | "00000000-0000-0000-0000-000000000000" |
      | nodeTypeName             | "Neos.ContentRepository:Root"          |

    # We have to add another node since root nodes have no dimension space points and thus cannot be varied
    # Node /document
    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                  |
      | contentStreamIdentifier       | "live-cs-identifier"                   |
      | nodeAggregateIdentifier       | "doc-agg-identifier"                   |
      | nodeTypeName                  | "Neos.ContentRepository:Document"      |
      | dimensionSpacePoint           | {"language":"de"}                      |
      | visibleInDimensionSpacePoints | [{"language":"de"},{"language":"gsw"}] |
      | nodeIdentifier                | "doc-identifier-de"                    |
      | parentNodeIdentifier          | "rn-identifier"                        |
      | nodeName                      | "document"                             |
      | propertyDefaultValuesAndTypes | {}                                     |
    # We also want to add a child node to make sure it is correctly removed when the parent is removed
    # Node /document/child-document
    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                  |
      | contentStreamIdentifier       | "live-cs-identifier"                   |
      | nodeAggregateIdentifier       | "cdoc-agg-identifier"                  |
      | nodeTypeName                  | "Neos.ContentRepository:Document"      |
      | dimensionSpacePoint           | {"language":"de"}                      |
      | visibleInDimensionSpacePoints | [{"language":"de"},{"language":"gsw"}] |
      | nodeIdentifier                | "cdoc-identifier-de"                   |
      | parentNodeIdentifier          | "doc-identifier-de"                    |
      | nodeName                      | "child-document"                       |
      | propertyDefaultValuesAndTypes | {}                                     |

    # We also want to add a grandchild node to make sure it is correctly removed when the parent is removed
    # Node /document/child-document/grandchild-document
    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                  |
      | contentStreamIdentifier       | "live-cs-identifier"                   |
      | nodeAggregateIdentifier       | "gcdoc-agg-identifier"                 |
      | nodeTypeName                  | "Neos.ContentRepository:Document"      |
      | dimensionSpacePoint           | {"language":"de"}                      |
      | visibleInDimensionSpacePoints | [{"language":"de"},{"language":"gsw"}] |
      | nodeIdentifier                | "gcdoc-identifier-de"                  |
      | parentNodeIdentifier          | "cdoc-identifier-de"                   |
      | nodeName                      | "grandchild-document"                  |
      | propertyDefaultValuesAndTypes | {}                                     |
    And the graph projection is fully up to date


  ########################
  # Section: EXTRA testcases
  ########################
  Scenario: (Exception) Trying to remove a non existing node should fail with an exception
    When the command RemoveNodesFromAggregate was published with payload and exceptions are caught:
      | Key                     | Value                                  |
      | contentStreamIdentifier | "live-cs-identifier"                   |
      | nodeAggregateIdentifier | "non-existing-agg-identifier"          |
      | dimensionSpacePointSet  | [{"language":"de"},{"language":"gsw"}] |
    Then the last command should have thrown an exception of type "NodeAggregateNotFound"

  Scenario: (Exception) Trying to remove a node in a parent dimension without specializing the corresponding specialization dimension throw an exception
    When the command RemoveNodesFromAggregate was published with payload and exceptions are caught:
      | Key                     | Value                |
      | contentStreamIdentifier | "live-cs-identifier" |
      | nodeAggregateIdentifier | "doc-agg-identifier" |
      | dimensionSpacePointSet  | [{"language":"de"}]  |
    Then the last command should have thrown an exception of type "SpecializedDimensionsMustBePartOfDimensionSpacePointSet"


  ########################
  # Section: 1.A.*
  ########################
  Scenario: (1.A.a) In LIVE workspace, removing a node WITHOUT children leads also to removal of the node in the shine-through dimensions if specified

    When the command RemoveNodesFromAggregate was published with payload:
      | Key                     | Value                                  |
      | contentStreamIdentifier | "live-cs-identifier"                   |
      | nodeAggregateIdentifier | "cdoc-agg-identifier"                  |
      | dimensionSpacePointSet  | [{"language":"de"},{"language":"gsw"}] |
    And the graph projection is fully up to date

    When I am in content stream "live-cs-identifier" and Dimension Space Point {"language":"de"}
    Then I expect a node "cdoc-identifier-de" not to exist in the graph projection

    When I am in content stream "live-cs-identifier" and Dimension Space Point {"language":"gsw"}
    Then I expect a node "cdoc-identifier-de" not to exist in the graph projection


  Scenario: (1.A.b) In LIVE workspace, removing a node WITHOUT children does not lead to removal of the node in the parent dimension
    When the command CreateNodeSpecialization was published with payload:
      | Key                       | Value                 |
      | contentStreamIdentifier   | "live-cs-identifier"  |
      | nodeAggregateIdentifier   | "cdoc-agg-identifier" |
      | sourceDimensionSpacePoint | {"language":"de"}     |
      | targetDimensionSpacePoint | {"language":"gsw"}    |
      | specializationIdentifier  | "cdoc-identifier-gsw" |
    And the graph projection is fully up to date
    When the command RemoveNodesFromAggregate was published with payload:
      | Key                     | Value                 |
      | contentStreamIdentifier | "live-cs-identifier"  |
      | nodeAggregateIdentifier | "cdoc-agg-identifier" |
      | dimensionSpacePointSet  | [{"language":"gsw"}]  |
    And the graph projection is fully up to date

    When I am in content stream "live-cs-identifier" and Dimension Space Point {"language":"de"}
    Then I expect a node "cdoc-identifier-de" to exist in the graph projection
    And I expect a node "cdoc-identifier-gsw" not to exist in the graph projection
    And I expect the path "document" to lead to the node "doc-identifier-de"
    And I expect the path "document/child-document" to lead to the node "cdoc-identifier-de"

    When I am in content stream "live-cs-identifier" and Dimension Space Point {"language":"gsw"}
    Then I expect a node "cdoc-identifier-gsw" not to exist in the graph projection


  ########################
  # Section: 1.B.*
  ########################
  Scenario: (1.B.a) In LIVE workspace, removing a node WITH children leads also to removal of the node in the shine-through dimensions if specified

    When the command RemoveNodesFromAggregate was published with payload:
      | Key                     | Value                                  |
      | contentStreamIdentifier | "live-cs-identifier"                   |
      | nodeAggregateIdentifier | "doc-agg-identifier"                   |
      | dimensionSpacePointSet  | [{"language":"de"},{"language":"gsw"}] |
    And the graph projection is fully up to date

    When I am in content stream "live-cs-identifier" and Dimension Space Point {"language":"de"}
    Then I expect a node "doc-identifier-de" not to exist in the graph projection
    Then I expect a node "cdoc-identifier-de" not to exist in the graph projection

    When I am in content stream "live-cs-identifier" and Dimension Space Point {"language":"gsw"}
    Then I expect a node "doc-identifier-de" not to exist in the graph projection
    Then I expect a node "cdoc-identifier-de" not to exist in the graph projection


  Scenario: (1.B.b) In LIVE workspace, removing a node WITH children does not lead to removal of the node in the parent dimension
    When the command CreateNodeSpecialization was published with payload:
      | Key                       | Value                |
      | contentStreamIdentifier   | "live-cs-identifier" |
      | nodeAggregateIdentifier   | "doc-agg-identifier" |
      | sourceDimensionSpacePoint | {"language":"de"}    |
      | targetDimensionSpacePoint | {"language":"gsw"}   |
      | specializationIdentifier  | "doc-identifier-gsw" |
    When the command RemoveNodesFromAggregate was published with payload:
      | Key                     | Value                |
      | contentStreamIdentifier | "live-cs-identifier" |
      | nodeAggregateIdentifier | "doc-agg-identifier" |
      | dimensionSpacePointSet  | [{"language":"gsw"}] |
    And the graph projection is fully up to date

    When I am in content stream "live-cs-identifier" and Dimension Space Point {"language":"de"}
    Then I expect a node "doc-identifier-de" to exist in the graph projection
    Then I expect a node "cdoc-identifier-de" to exist in the graph projection
    And I expect the path "document" to lead to the node "doc-identifier-de"
    And I expect a node "gcdoc-identifier-de" to exist in the graph projection
    And I expect the path "document/child-document/grandchild-document" to lead to the node "gcdoc-identifier-de"

    When I am in content stream "live-cs-identifier" and Dimension Space Point {"language":"gsw"}
    And I expect a node "doc-identifier-gsw" not to exist in the graph projection
    And I expect a node "cdoc-identifier-de" not to exist in the graph projection
    And I expect a node "gcdoc-identifier-de" not to exist in the graph projection


  ########################
  # Section: 2.A.*
  ########################
  Scenario: (2.A.a) In USER workspace, removing a node WITHOUT children leads also to removal of the node in the shine-through dimensions if specified
    When the command "ForkContentStream" is executed with payload:
      | Key                           | Value                |
      | contentStreamIdentifier       | "user-cs-identifier" |
      | sourceContentStreamIdentifier | "live-cs-identifier" |
    And the graph projection is fully up to date
    When the command RemoveNodesFromAggregate was published with payload:
      | Key                     | Value                                  |
      | contentStreamIdentifier | "user-cs-identifier"                   |
      | nodeAggregateIdentifier | "cdoc-agg-identifier"                  |
      | dimensionSpacePointSet  | [{"language":"de"},{"language":"gsw"}] |
    And the graph projection is fully up to date

    When I am in content stream "user-cs-identifier" and Dimension Space Point {"language":"de"}
    Then I expect a node "cdoc-identifier-de" not to exist in the graph projection

    When I am in content stream "user-cs-identifier" and Dimension Space Point {"language":"gsw"}
    Then I expect a node "cdoc-identifier-de" not to exist in the graph projection

    # ensure LIVE ContentStream is untouched
    When I am in content stream "live-cs-identifier" and Dimension Space Point {"language":"de"}
    And I expect the path "document" to lead to the node "doc-identifier-de"
    Then I expect a node "cdoc-identifier-de" to exist in the graph projection
    And I expect the path "document/child-document" to lead to the node "cdoc-identifier-de"

    When I am in content stream "live-cs-identifier" and Dimension Space Point {"language":"gsw"}
    And I expect the path "document" to lead to the node "doc-identifier-de"
    Then I expect a node "cdoc-identifier-de" to exist in the graph projection
    And I expect the path "document/child-document" to lead to the node "cdoc-identifier-de"


  Scenario: (2.A.b) In USER workspace, removing a node WITHOUT children does not lead to removal of the node in the parent dimension
    When the command CreateNodeSpecialization was published with payload:
      | Key                       | Value                 |
      | contentStreamIdentifier   | "live-cs-identifier"  |
      | nodeAggregateIdentifier   | "cdoc-agg-identifier" |
      | sourceDimensionSpacePoint | {"language":"de"}     |
      | targetDimensionSpacePoint | {"language":"gsw"}    |
      | specializationIdentifier  | "cdoc-identifier-gsw" |

    When the command "ForkContentStream" is executed with payload:
      | Key                           | Value                |
      | contentStreamIdentifier       | "user-cs-identifier" |
      | sourceContentStreamIdentifier | "live-cs-identifier" |
    And the graph projection is fully up to date

    When the command RemoveNodesFromAggregate was published with payload:
      | Key                     | Value                 |
      | contentStreamIdentifier | "user-cs-identifier"  |
      | nodeAggregateIdentifier | "cdoc-agg-identifier" |
      | dimensionSpacePointSet  | [{"language":"gsw"}]  |
    And the graph projection is fully up to date

    When I am in content stream "user-cs-identifier" and Dimension Space Point {"language":"de"}
    Then I expect a node "doc-identifier-de" to exist in the graph projection
    Then I expect a node "cdoc-identifier-de" to exist in the graph projection
    And I expect the path "document" to lead to the node "doc-identifier-de"
    And I expect a node "gcdoc-identifier-de" to exist in the graph projection
    And I expect the path "document/child-document/grandchild-document" to lead to the node "gcdoc-identifier-de"

    When I am in content stream "user-cs-identifier" and Dimension Space Point {"language":"gsw"}
    And I expect a node "doc-identifier-gsw" not to exist in the graph projection
    And I expect a node "cdoc-identifier-de" not to exist in the graph projection
    And I expect a node "gcdoc-identifier-de" not to exist in the graph projection

    # ensure LIVE ContentStream is untouched
    When I am in content stream "live-cs-identifier" and Dimension Space Point {"language":"de"}
    And I expect the path "document" to lead to the node "doc-identifier-de"
    Then I expect a node "cdoc-identifier-de" to exist in the graph projection
    And I expect the path "document/child-document" to lead to the node "cdoc-identifier-de"

    When I am in content stream "live-cs-identifier" and Dimension Space Point {"language":"gsw"}
    And I expect the path "document" to lead to the node "doc-identifier-de"
    Then I expect a node "cdoc-identifier-gsw" to exist in the graph projection
    And I expect the path "document/child-document" to lead to the node "cdoc-identifier-gsw"


  ########################
  # Section: 2.B.*
  ########################
  Scenario: (2.B.a) In USER workspace, removing a node WITH children leads also to removal of the node in the shine-through dimensions if specified

    When the command "ForkContentStream" is executed with payload:
      | Key                           | Value                |
      | contentStreamIdentifier       | "user-cs-identifier" |
      | sourceContentStreamIdentifier | "live-cs-identifier" |
    And the graph projection is fully up to date

    When the command RemoveNodesFromAggregate was published with payload:
      | Key                     | Value                                  |
      | contentStreamIdentifier | "user-cs-identifier"                   |
      | nodeAggregateIdentifier | "doc-agg-identifier"                   |
      | dimensionSpacePointSet  | [{"language":"de"},{"language":"gsw"}] |
    And the graph projection is fully up to date

    When I am in content stream "user-cs-identifier" and Dimension Space Point {"language":"de"}
    Then I expect a node "doc-identifier-de" not to exist in the graph projection
    Then I expect a node "cdoc-identifier-de" not to exist in the graph projection

    When I am in content stream "user-cs-identifier" and Dimension Space Point {"language":"gsw"}
    Then I expect a node "doc-identifier-de" not to exist in the graph projection
    Then I expect a node "cdoc-identifier-de" not to exist in the graph projection

    # ensure LIVE ContentStream is untouched
    When I am in content stream "live-cs-identifier" and Dimension Space Point {"language":"de"}
    And I expect the path "document" to lead to the node "doc-identifier-de"
    Then I expect a node "cdoc-identifier-de" to exist in the graph projection
    And I expect the path "document/child-document" to lead to the node "cdoc-identifier-de"

    When I am in content stream "live-cs-identifier" and Dimension Space Point {"language":"gsw"}
    And I expect the path "document" to lead to the node "doc-identifier-de"
    Then I expect a node "cdoc-identifier-de" to exist in the graph projection
    And I expect the path "document/child-document" to lead to the node "cdoc-identifier-de"


  Scenario: (2.B.b) In USER workspace, removing a node WITH children does not lead to removal of the node in the parent dimension
    When the command CreateNodeSpecialization was published with payload:
      | Key                       | Value                |
      | contentStreamIdentifier   | "live-cs-identifier" |
      | nodeAggregateIdentifier   | "doc-agg-identifier" |
      | sourceDimensionSpacePoint | {"language":"de"}    |
      | targetDimensionSpacePoint | {"language":"gsw"}   |
      | specializationIdentifier  | "doc-identifier-gsw" |

    When the command "ForkContentStream" is executed with payload:
      | Key                           | Value                |
      | contentStreamIdentifier       | "user-cs-identifier" |
      | sourceContentStreamIdentifier | "live-cs-identifier" |
    And the graph projection is fully up to date

    When the command RemoveNodesFromAggregate was published with payload:
      | Key                     | Value                |
      | contentStreamIdentifier | "user-cs-identifier" |
      | nodeAggregateIdentifier | "doc-agg-identifier" |
      | dimensionSpacePointSet  | [{"language":"gsw"}] |
    And the graph projection is fully up to date

    When I am in content stream "user-cs-identifier" and Dimension Space Point {"language":"de"}
    Then I expect a node "doc-identifier-de" to exist in the graph projection
    Then I expect a node "cdoc-identifier-de" to exist in the graph projection
    And I expect the path "document" to lead to the node "doc-identifier-de"
    And I expect a node "gcdoc-identifier-de" to exist in the graph projection
    And I expect the path "document/child-document/grandchild-document" to lead to the node "gcdoc-identifier-de"

    When I am in content stream "user-cs-identifier" and Dimension Space Point {"language":"gsw"}
    And I expect a node "doc-identifier-gsw" not to exist in the graph projection
    And I expect a node "cdoc-identifier-de" not to exist in the graph projection
    And I expect a node "gcdoc-identifier-de" not to exist in the graph projection

    # ensure LIVE ContentStream is untouched
    When I am in content stream "live-cs-identifier" and Dimension Space Point {"language":"de"}
    And I expect the path "document" to lead to the node "doc-identifier-de"
    Then I expect a node "cdoc-identifier-de" to exist in the graph projection
    And I expect the path "document/child-document" to lead to the node "cdoc-identifier-de"

    When I am in content stream "live-cs-identifier" and Dimension Space Point {"language":"gsw"}
    And I expect the path "document" to lead to the node "doc-identifier-gsw"
    Then I expect a node "cdoc-identifier-de" to exist in the graph projection
    And I expect the path "document/child-document" to lead to the node "cdoc-identifier-de"
