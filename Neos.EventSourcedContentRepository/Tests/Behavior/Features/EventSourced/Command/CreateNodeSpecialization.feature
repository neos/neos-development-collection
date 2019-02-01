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
    And the command "CreateRootNode" is executed with payload:
      | Key                      | Value                                  |
      | contentStreamIdentifier  | "cs-identifier"                        |
      | nodeIdentifier           | "rn-identifier"                        |
      | initiatingUserIdentifier | "00000000-0000-0000-0000-000000000000" |
      | nodeTypeName             | "Neos.ContentRepository:Root"          |

    # We have to add another node since root nodes have no dimension space points and thus cannot be varied
    # Node /document
    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                                                                      |
      | contentStreamIdentifier       | "cs-identifier"                                                                                                                            |
      | nodeAggregateIdentifier       | "doc-agg-identifier"                                                                                                                       |
      | nodeTypeName                  | "Neos.ContentRepository:Document"                                                                                                          |
      | dimensionSpacePoint           | {"market":"DE", "language":"de"}                                                                                                           |
      | visibleInDimensionSpacePoints | [{"market":"DE", "language":"de"},{"market": "DE", "language":"gsw"},{"market": "CH", "language":"de"},{"market": "CH", "language":"gsw"}] |
      | nodeIdentifier                | "doc-identifier-de-de"                                                                                                                     |
      | parentNodeIdentifier          | "rn-identifier"                                                                                                                            |
      | nodeName                      | "document"                                                                                                                                 |
      | propertyDefaultValuesAndTypes | {}                                                                                                                                         |
    # We also want to add a child node to make sure it is still reachable after specializing the parent
    # Node /document/child-document
    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                                                                      |
      | contentStreamIdentifier       | "cs-identifier"                                                                                                                            |
      | nodeAggregateIdentifier       | "cdoc-agg-identifier"                                                                                                                      |
      | nodeTypeName                  | "Neos.ContentRepository:Document"                                                                                                          |
      | dimensionSpacePoint           | {"market":"DE", "language":"de"}                                                                                                           |
      | visibleInDimensionSpacePoints | [{"market":"DE", "language":"de"},{"market": "DE", "language":"gsw"},{"market": "CH", "language":"de"},{"market": "CH", "language":"gsw"}] |
      | nodeIdentifier                | "cdoc-identifier-de-de"                                                                                                                    |
      | parentNodeIdentifier          | "doc-identifier-de-de"                                                                                                                     |
      | nodeName                      | "child-document"                                                                                                                           |
      | propertyDefaultValuesAndTypes | {}                                                                                                                                         |

  Scenario: Try to create a node specialization in a non existing dimension space point
    When the command CreateNodeSpecialization was published with payload and exceptions are caught:
      | Key                       | Value                                   |
      | contentStreamIdentifier   | "cs-identifier"                         |
      | nodeAggregateIdentifier   | "doc-agg-identifier"                    |
      | sourceDimensionSpacePoint | {"market":"DE", "language":"de"}        |
      | targetDimensionSpacePoint | {"market":"NOT", "language":"existing"} |
      | specializationIdentifier  | "doc-identifier-not-existing"           |
    Then the last command should have thrown an exception of type "DimensionSpacePointNotFound"

  Scenario: Try to create a node specialization from a non-occupied dimension space point
    Given the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                              |
      | contentStreamIdentifier       | "cs-identifier"                    |
      | nodeAggregateIdentifier       | "otherdoc-agg-identifier"          |
      | nodeTypeName                  | "Neos.ContentRepository:Document"  |
      | dimensionSpacePoint           | {"market":"DE", "language":"de"}   |
      | visibleInDimensionSpacePoints | [{"market":"DE", "language":"de"}] |
      | nodeIdentifier                | "otherdoc-identifier-de-de"        |
      | parentNodeIdentifier          | "rn-identifier"                    |
      | nodeName                      | "other-document"                   |
      | propertyDefaultValuesAndTypes | {}                                 |

    When the command CreateNodeSpecialization was published with payload and exceptions are caught:
      | Key                       | Value                             |
      | contentStreamIdentifier   | "cs-identifier"                   |
      | nodeAggregateIdentifier   | "otherdoc-agg-identifier"         |
      | sourceDimensionSpacePoint | {"market":"DE", "language":"gsw"} |
      | targetDimensionSpacePoint | {"market":"CH", "language":"gsw"} |
      | specializationIdentifier  | "otherdoc-identifier-ch-gsw"      |
    Then the last command should have thrown an exception of type "DimensionSpacePointIsNotYetOccupied"

  Scenario: Try to create a node specialization in an already occupied dimension space point
    Given the event NodeSpecializationWasCreated was published with payload:
      | Key                       | Value                               |
      | contentStreamIdentifier   | "cs-identifier"                     |
      | nodeAggregateIdentifier   | "doc-agg-identifier"                |
      | sourceDimensionSpacePoint | {"market":"DE", "language":"de"}    |
      | specializationIdentifier  | "doc-identifier-ch-gsw"             |
      | specializationLocation    | {"market":"CH", "language":"gsw"}   |
      | specializationVisibility  | [{"market":"CH", "language":"gsw"}] |

    When the command CreateNodeSpecialization was published with payload and exceptions are caught:
      | Key                       | Value                             |
      | contentStreamIdentifier   | "cs-identifier"                   |
      | nodeAggregateIdentifier   | "doc-agg-identifier"              |
      | sourceDimensionSpacePoint | {"market":"DE", "language":"de"}  |
      | targetDimensionSpacePoint | {"market":"CH", "language":"gsw"} |
      | specializationIdentifier  | "doc-identifier-ch-gsw"           |
    Then the last command should have thrown an exception of type "DimensionSpacePointIsAlreadyOccupied"

  Scenario: Try to create a node specialization in a more general dimension space point
    Given the event NodeSpecializationWasCreated was published with payload:
      | Key                       | Value                               |
      | contentStreamIdentifier   | "cs-identifier"                     |
      | nodeAggregateIdentifier   | "doc-agg-identifier"                |
      | sourceDimensionSpacePoint | {"market":"DE", "language":"de"}    |
      | specializationIdentifier  | "doc-identifier-ch-gsw"             |
      | specializationLocation    | {"market":"CH", "language":"gsw"}   |
      | specializationVisibility  | [{"market":"CH", "language":"gsw"}] |

    When the command CreateNodeSpecialization was published with payload and exceptions are caught:
      | Key                       | Value                             |
      | contentStreamIdentifier   | "cs-identifier"                   |
      | nodeAggregateIdentifier   | "doc-agg-identifier"              |
      | sourceDimensionSpacePoint | {"market":"CH", "language":"gsw"} |
      | targetDimensionSpacePoint | {"market":"DE", "language":"gsw"} |
      | specializationIdentifier  | "doc-identifier-de-gsw"           |
    Then the last command should have thrown an exception of type "DimensionSpacePointIsNoSpecializationException"

  Scenario: Try to create a node specialization in a dimension space point the parent node's aggregate is not visible in
    Given the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                                   |
      | contentStreamIdentifier       | "cs-identifier"                                                                                         |
      | nodeAggregateIdentifier       | "doc2-agg-identifier"                                                                                   |
      | nodeTypeName                  | "Neos.ContentRepository:Document"                                                                       |
      | dimensionSpacePoint           | {"market":"DE", "language":"de"}                                                                        |
      | visibleInDimensionSpacePoints | [{"market":"DE", "language":"de"},{"market": "DE", "language":"gsw"},{"market": "CH", "language":"de"}] |
      | nodeIdentifier                | "doc2-identifier-de-de"                                                                                 |
      | parentNodeIdentifier          | "rn-identifier"                                                                                         |
      | nodeName                      | "document2"                                                                                             |
      | propertyDefaultValuesAndTypes | {}                                                                                                      |
    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                                   |
      | contentStreamIdentifier       | "cs-identifier"                                                                                         |
      | nodeAggregateIdentifier       | "cdoc2-agg-identifier"                                                                                  |
      | nodeTypeName                  | "Neos.ContentRepository:Document"                                                                       |
      | dimensionSpacePoint           | {"market":"DE", "language":"de"}                                                                        |
      | visibleInDimensionSpacePoints | [{"market":"DE", "language":"de"},{"market": "DE", "language":"gsw"},{"market": "CH", "language":"de"}] |
      | nodeIdentifier                | "cdoc2-identifier-de-de"                                                                                |
      | parentNodeIdentifier          | "doc2-identifier-de-de"                                                                                 |
      | nodeName                      | "child-document2"                                                                                       |
      | propertyDefaultValuesAndTypes | {}                                                                                                      |
    Given the event NodeSpecializationWasCreated was published with payload:
      | Key                       | Value                              |
      | contentStreamIdentifier   | "cs-identifier"                    |
      | nodeAggregateIdentifier   | "cdoc2-agg-identifier"             |
      | sourceDimensionSpacePoint | {"market":"DE", "language":"de"}   |
      | specializationIdentifier  | "doc-identifier-ch-gsw"            |
      | specializationLocation    | {"market":"CH", "language":"de"}   |
      | specializationVisibility  | [{"market":"CH", "language":"de"}] |
    And the graph projection is fully up to date
    When the command CreateNodeSpecialization was published with payload and exceptions are caught:
      | Key                       | Value                             |
      | contentStreamIdentifier   | "cs-identifier"                   |
      | nodeAggregateIdentifier   | "cdoc2-agg-identifier"            |
      | sourceDimensionSpacePoint | {"market":"CH", "language":"de"}  |
      | targetDimensionSpacePoint | {"market":"CH", "language":"gsw"} |
      | specializationIdentifier  | "cdoc2-identifier-ch-gsw"         |
    Then the last command should have thrown an exception of type "ParentsNodeAggregateNotVisibleInDimensionSpacePoint"

  Scenario: Create a node specialization in a dimension space point without further specializations
    When the command CreateNodeSpecialization was published with payload:
      | Key                       | Value                             |
      | contentStreamIdentifier   | "cs-identifier"                   |
      | nodeAggregateIdentifier   | "doc-agg-identifier"              |
      | sourceDimensionSpacePoint | {"market":"DE", "language":"de"}  |
      | targetDimensionSpacePoint | {"market":"CH", "language":"gsw"} |
      | specializationIdentifier  | "doc-identifier-ch-gsw"           |
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and Dimension Space Point {"market":"CH", "language":"gsw"}
    Then I expect a node "doc-identifier-ch-gsw" to exist in the graph projection
    And I expect a node "doc-identifier-de-de" not to exist in the graph projection
    And I expect the path "document" to lead to the node "doc-identifier-ch-gsw"
    And I expect the path "document/child-document" to lead to the node "cdoc-identifier-de-de"
    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"DE", "language":"de"}
    Then I expect a node "doc-identifier-de-de" to exist in the graph projection
    And I expect a node "doc-identifier-ch-gsw" not to exist in the graph projection
    And I expect the path "document" to lead to the node "doc-identifier-de-de"
    And I expect the path "document/child-document" to lead to the node "cdoc-identifier-de-de"
    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"DE", "language":"gsw"}
    Then I expect a node "doc-identifier-de-de" to exist in the graph projection
    And I expect a node "doc-identifier-ch-gsw" not to exist in the graph projection
    And I expect the path "document" to lead to the node "doc-identifier-de-de"
    And I expect the path "document/child-document" to lead to the node "cdoc-identifier-de-de"
    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"CH", "language":"de"}
    Then I expect a node "doc-identifier-de-de" to exist in the graph projection
    And I expect a node "doc-identifier-ch-gsw" not to exist in the graph projection
    And I expect the path "document" to lead to the node "doc-identifier-de-de"
    And I expect the path "document/child-document" to lead to the node "cdoc-identifier-de-de"

  Scenario: Create a node specialization in a dimension space point with further specializations
    When the command CreateNodeSpecialization was published with payload:
      | Key                       | Value                             |
      | contentStreamIdentifier   | "cs-identifier"                   |
      | nodeAggregateIdentifier   | "doc-agg-identifier"              |
      | sourceDimensionSpacePoint | {"market":"DE", "language":"de"}  |
      | targetDimensionSpacePoint | {"market":"DE", "language":"gsw"} |
      | specializationIdentifier  | "doc-identifier-de-gsw"           |
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and Dimension Space Point {"market":"CH", "language":"gsw"}
    Then I expect a node "doc-identifier-de-gsw" to exist in the graph projection
    And I expect a node "doc-identifier-de-de" not to exist in the graph projection
    And I expect the path "document" to lead to the node "doc-identifier-de-gsw"
    And I expect the path "document/child-document" to lead to the node "cdoc-identifier-de-de"
    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"DE", "language":"de"}
    Then I expect a node "doc-identifier-de-de" to exist in the graph projection
    And I expect a node "doc-identifier-de-gsw" not to exist in the graph projection
    And I expect the path "document" to lead to the node "doc-identifier-de-de"
    And I expect the path "document/child-document" to lead to the node "cdoc-identifier-de-de"
    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"DE", "language":"gsw"}
    Then I expect a node "doc-identifier-de-de" not to exist in the graph projection
    And I expect a node "doc-identifier-de-gsw" to exist in the graph projection
    And I expect the path "document" to lead to the node "doc-identifier-de-gsw"
    And I expect the path "document/child-document" to lead to the node "cdoc-identifier-de-de"
    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"CH", "language":"de"}
    Then I expect a node "doc-identifier-de-de" to exist in the graph projection
    And I expect a node "doc-identifier-de-gsw" not to exist in the graph projection
    And I expect the path "document" to lead to the node "doc-identifier-de-de"
    And I expect the path "document/child-document" to lead to the node "cdoc-identifier-de-de"

  # @todo test against already existing variants in the extended visibility subspace
