@fixtures
Feature: Create node generalization

  As a user of the CR I want to create a copy of a node within an aggregate in another dimension space point as a
  generalization.

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
    And the command "CreateRootNode" is executed with payload:
      | Key                      | Value                                  |
      | contentStreamIdentifier  | "cs-identifier"                        |
      | nodeIdentifier           | "rn-identifier"                        |
      | initiatingUserIdentifier | "00000000-0000-0000-0000-000000000000" |
      | nodeTypeName             | "Neos.ContentRepository:Root"          |
    # We have to add another node since root nodes have no dimension space points and thus cannot be varied
    # Node /document
    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                               |
      | contentStreamIdentifier       | "cs-identifier"                     |
      | nodeAggregateIdentifier       | "doc-agg-identifier"                |
      | nodeTypeName                  | "Neos.ContentRepository:Document"   |
      | dimensionSpacePoint           | {"market":"CH", "language":"gsw"}   |
      | visibleInDimensionSpacePoints | [{"market":"CH", "language":"gsw"}] |
      | nodeIdentifier                | "doc-identifier-ch-gsw"             |
      | parentNodeIdentifier          | "rn-identifier"                     |
      | nodeName                      | "document"                          |
      | propertyDefaultValuesAndTypes | {}                                  |
    # We also want to add a child node to make sure it is still reachable after creating a generalization of the parent
    # Node /document/child-document
    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                               |
      | contentStreamIdentifier       | "cs-identifier"                     |
      | nodeAggregateIdentifier       | "cdoc-agg-identifier"               |
      | nodeTypeName                  | "Neos.ContentRepository:Document"   |
      | dimensionSpacePoint           | {"market":"CH", "language":"gsw"}   |
      | visibleInDimensionSpacePoints | [{"market":"CH", "language":"gsw"}] |
      | nodeIdentifier                | "cdoc-identifier-ch-gsw"            |
      | parentNodeIdentifier          | "doc-identifier-ch-gsw"             |
      | nodeName                      | "child-document"                    |
      | propertyDefaultValuesAndTypes | {}                                  |

  Scenario: Create generalization of node to dimension space point without further generalizations
    When the command CreateNodeGeneralization was published with payload:
      | Key                       | Value                             |
      | contentStreamIdentifier   | "cs-identifier"                   |
      | nodeAggregateIdentifier   | "doc-agg-identifier"              |
      | sourceDimensionSpacePoint | {"market":"CH", "language":"gsw"} |
      | targetDimensionSpacePoint | {"market":"DE", "language":"de"}  |
      | generalizationIdentifier  | "doc-identifier-de-de"            |
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and Dimension Space Point {"market":"CH", "language":"gsw"}
    Then I expect a node "doc-identifier-ch-gsw" to exist in the graph projection
    And I expect a node "doc-identifier-de-de" not to exist in the graph projection
    And I expect a node "cdoc-identifier-ch-gsw" to exist in the graph projection
    And I expect the path "document" to lead to the node "doc-identifier-ch-gsw"
    And I expect the path "document/child-document" to lead to the node "cdoc-identifier-ch-gsw"
    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"DE", "language":"de"}
    Then I expect a node "doc-identifier-de-de" to exist in the graph projection
    And I expect a node "doc-identifier-ch-gsw" not to exist in the graph projection
    And I expect a node "cdoc-identifier-ch-gsw" not to exist in the graph projection
    And I expect the path "document" to lead to the node "doc-identifier-de-de"
    And I expect the path "document/child-document" to lead to no node
    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"DE", "language":"gsw"}
    Then I expect a node "doc-identifier-de-de" to exist in the graph projection
    And I expect a node "doc-identifier-ch-gsw" not to exist in the graph projection
    And I expect a node "cdoc-identifier-ch-gsw" not to exist in the graph projection
    And I expect the path "document" to lead to the node "doc-identifier-de-de"
    And I expect the path "document/child-document" to lead to no node
    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"CH", "language":"de"}
    Then I expect a node "doc-identifier-de-de" to exist in the graph projection
    And I expect a node "doc-identifier-ch-gsw" not to exist in the graph projection
    And I expect a node "cdoc-identifier-ch-gsw" not to exist in the graph projection
    And I expect the path "document" to lead to the node "doc-identifier-de-de"
    And I expect the path "document/child-document" to lead to no node

  Scenario: Create generalization of node to dimension space point with further generalizations
    When the command CreateNodeGeneralization was published with payload:
      | Key                       | Value                             |
      | contentStreamIdentifier   | "cs-identifier"                   |
      | nodeAggregateIdentifier   | "doc-agg-identifier"              |
      | sourceDimensionSpacePoint | {"market":"CH", "language":"gsw"} |
      | targetDimensionSpacePoint | {"market":"DE", "language":"gsw"} |
      | generalizationIdentifier  | "doc-identifier-de-gsw"           |
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and Dimension Space Point {"market":"CH", "language":"gsw"}
    Then I expect a node "doc-identifier-ch-gsw" to exist in the graph projection
    And I expect a node "doc-identifier-de-gsw" not to exist in the graph projection
    And I expect a node "cdoc-identifier-ch-gsw" to exist in the graph projection
    And I expect the path "document" to lead to the node "doc-identifier-ch-gsw"
    And I expect the path "document/child-document" to lead to the node "cdoc-identifier-ch-gsw"
    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"DE", "language":"de"}
    And I expect a node "doc-identifier-ch-gsw" not to exist in the graph projection
    And I expect a node "doc-identifier-de-gsw" not to exist in the graph projection
    And I expect a node "cdoc-identifier-ch-gsw" not to exist in the graph projection
    And I expect the path "document" to lead to no node
    And I expect the path "document/child-document" to lead to no node
    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"DE", "language":"gsw"}
    Then I expect a node "doc-identifier-de-gsw" to exist in the graph projection
    And I expect a node "doc-identifier-ch-gsw" not to exist in the graph projection
    And I expect a node "cdoc-identifier-ch-gsw" not to exist in the graph projection
    And I expect the path "document" to lead to the node "doc-identifier-de-gsw"
    And I expect the path "document/child-document" to lead to no node
    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"CH", "language":"de"}
    Then I expect a node "doc-identifier-de-gsw" not to exist in the graph projection
    And I expect a node "doc-identifier-ch-gsw" not to exist in the graph projection
    And I expect a node "cdoc-identifier-ch-gsw" not to exist in the graph projection
    And I expect the path "document" to lead to no node
    And I expect the path "document/child-document" to lead to no node

    # @todo test against already existing variants in the extended visibility subspace
  # @todo test against parent visibility subspace

  Scenario: Try to create a generalization of a node in a more specialized dimension space point
    When the command CreateNodeGeneralization was published with payload and exceptions are caught:
      | Key                       | Value                             |
      | contentStreamIdentifier   | "cs-identifier"                   |
      | nodeAggregateIdentifier   | "doc-agg-identifier"              |
      | sourceDimensionSpacePoint | {"market":"DE", "language":"de"}  |
      | targetDimensionSpacePoint | {"market":"DE", "language":"gsw"} |
      | generalizationIdentifier  | "doc-identifier-de-gsw"           |
    Then the last command should have thrown an exception of type "DimensionSpacePointIsNoGeneralizationException"

  Scenario: Try to create a generalization of a node to an already occupied dimension space point
    Given the event NodeGeneralizationWasCreated was published with payload:
      | Key                       | Value                                                                                                 |
      | contentStreamIdentifier   | "cs-identifier"                                                                                       |
      | nodeAggregateIdentifier   | "doc-agg-identifier"                                                                                  |
      | sourceDimensionSpacePoint | {"market":"CH", "language":"gsw"}                                                                     |
      | generalizationIdentifier  | "doc-identifier-de-de"                                                                                |
      | generalizationLocation    | {"market":"DE", "language":"de"}                                                                      |
      | generalizationVisibility  | [{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"de"}] |

    When the command CreateNodeGeneralization was published with payload and exceptions are caught:
      | Key                       | Value                             |
      | contentStreamIdentifier   | "cs-identifier"                   |
      | nodeAggregateIdentifier   | "doc-agg-identifier"              |
      | sourceDimensionSpacePoint | {"market":"CH", "language":"gsw"} |
      | targetDimensionSpacePoint | {"market":"DE", "language":"de"}  |
      | generalizationIdentifier  | "doc-identifier-de-de"            |
    Then the last command should have thrown an exception of type "DimensionSpacePointIsAlreadyOccupied"
