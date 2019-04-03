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
    And the event RootWorkspaceWasCreated was published with payload:
      | Key                            | Value                                  |
      | workspaceName                  | "live"                                 |
      | workspaceTitle                 | "Live"                                 |
      | workspaceDescription           | "The live workspace"                   |
      | initiatingUserIdentifier       | "00000000-0000-0000-0000-000000000000" |
      | currentContentStreamIdentifier | "cs-identifier"                        |
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                                                                   |
      | contentStreamIdentifier       | "cs-identifier"                                                                                                                         |
      | nodeAggregateIdentifier       | "lady-eleonode-nodesworth"                                                                                                              |
      | nodeTypeName                  | "Neos.ContentRepository:Root"                                                                                                           |
      | visibleInDimensionSpacePoints | [{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"}] |
      | initiatingUserIdentifier      | "00000000-0000-0000-0000-000000000000"                                                                                                  |
    # We have to add another node since root nodes have no dimension space points and thus cannot be varied
    # Node /document
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                                                                   |
      | contentStreamIdentifier       | "cs-identifier"                                                                                                                         |
      | nodeAggregateIdentifier       | "sir-david-nodenborough"                                                                                                                |
      | nodeTypeName                  | "Neos.ContentRepository:Document"                                                                                                       |
      | originDimensionSpacePoint     | {"market":"DE", "language":"de"}                                                                                                        |
      | visibleInDimensionSpacePoints | [{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"}] |
      | parentNodeAggregateIdentifier | "lady-eleonode-nodesworth"                                                                                                              |
      | nodeName                      | "document"                                                                                                                              |
    # We also want to add a child node to make sure it is still reachable after creating a generalization of the parent
    # Node /document/child-document
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                               |
      | contentStreamIdentifier       | "cs-identifier"                     |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                  |
      | nodeTypeName                  | "Neos.ContentRepository:Document"   |
      | originDimensionSpacePoint     | {"market":"DE", "language":"de"}   |
      | visibleInDimensionSpacePoints | [{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"}] |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"            |
      | nodeName                      | "child-document"                    |

  Scenario: Try to create a node specialization in a non existing dimension space point
    When the command CreateNodeSpecialization is executed with payload and exceptions are caught:
      | Key                       | Value                                   |
      | contentStreamIdentifier   | "cs-identifier"                         |
      | nodeAggregateIdentifier   | "sir-david-nodenborough"                |
      | sourceDimensionSpacePoint | {"market":"DE", "language":"de"}        |
      | targetDimensionSpacePoint | {"market":"NOT", "language":"existing"} |
    Then the last command should have thrown an exception of type "DimensionSpacePointNotFound"

  Scenario: Try to create a node specialization from a non-occupied dimension space point
    Given the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                              |
      | contentStreamIdentifier       | "cs-identifier"                    |
      | nodeAggregateIdentifier       | "nodasaurus-rex"                   |
      | nodeTypeName                  | "Neos.ContentRepository:Document"  |
      | dimensionSpacePoint           | {"market":"DE", "language":"de"}   |
      | visibleInDimensionSpacePoints | [{"market":"DE", "language":"de"}] |
      | parentNodeAggregateIdentifier | "lady-eleonode-nodesworth"         |

    When the command CreateNodeSpecialization is executed with payload and exceptions are caught:
      | Key                       | Value                             |
      | contentStreamIdentifier   | "cs-identifier"                   |
      | nodeAggregateIdentifier   | "nodasaurus-rex"                  |
      | sourceDimensionSpacePoint | {"market":"DE", "language":"gsw"} |
      | targetDimensionSpacePoint | {"market":"CH", "language":"gsw"} |
    Then the last command should have thrown an exception of type "DimensionSpacePointIsNotYetOccupied"

  Scenario: Try to create a node specialization in an already occupied dimension space point
    Given the event NodeSpecializationWasCreated was published with payload:
      | Key                       | Value                               |
      | contentStreamIdentifier   | "cs-identifier"                     |
      | nodeAggregateIdentifier   | "sir-david-nodenborough"            |
      | sourceDimensionSpacePoint | {"market":"DE", "language":"de"}    |
      | specializationLocation    | {"market":"CH", "language":"gsw"}   |
      | specializationVisibility  | [{"market":"CH", "language":"gsw"}] |

    When the command CreateNodeSpecialization is executed with payload and exceptions are caught:
      | Key                       | Value                             |
      | contentStreamIdentifier   | "cs-identifier"                   |
      | nodeAggregateIdentifier   | "sir-david-nodenborough"          |
      | sourceDimensionSpacePoint | {"market":"DE", "language":"de"}  |
      | targetDimensionSpacePoint | {"market":"CH", "language":"gsw"} |
    Then the last command should have thrown an exception of type "DimensionSpacePointIsAlreadyOccupied"

  Scenario: Try to create a node specialization in a more general dimension space point
    Given the event NodeSpecializationWasCreated was published with payload:
      | Key                       | Value                               |
      | contentStreamIdentifier   | "cs-identifier"                     |
      | nodeAggregateIdentifier   | "sir-david-nodenborough"            |
      | sourceDimensionSpacePoint | {"market":"DE", "language":"de"}    |
      | specializationLocation    | {"market":"CH", "language":"gsw"}   |
      | specializationVisibility  | [{"market":"CH", "language":"gsw"}] |

    When the command CreateNodeSpecialization is executed with payload and exceptions are caught:
      | Key                       | Value                             |
      | contentStreamIdentifier   | "cs-identifier"                   |
      | nodeAggregateIdentifier   | "sir-david-nodenborough"          |
      | sourceDimensionSpacePoint | {"market":"CH", "language":"gsw"} |
      | targetDimensionSpacePoint | {"market":"DE", "language":"gsw"} |
      | specializationIdentifier  | "doc-identifier-de-gsw"           |
    Then the last command should have thrown an exception of type "DimensionSpacePointIsNoSpecialization"

  Scenario: Try to create a node specialization in a dimension space point the parent node's aggregate is not visible in
    Given the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                                   |
      | contentStreamIdentifier       | "cs-identifier"                                                                                         |
      | nodeAggregateIdentifier       | "sir-nodeward-nodington-iii"                                                                            |
      | nodeTypeName                  | "Neos.ContentRepository:Document"                                                                       |
      | dimensionSpacePoint           | {"market":"DE", "language":"de"}                                                                        |
      | visibleInDimensionSpacePoints | [{"market":"DE", "language":"de"},{"market": "DE", "language":"gsw"},{"market": "CH", "language":"de"}] |
      | parentNodeAggregateIdentifier | "lady-eleonode-nodesworth"                                                                              |
      | nodeName                      | "document2"                                                                                             |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                                                   |
      | contentStreamIdentifier       | "cs-identifier"                                                                                         |
      | nodeAggregateIdentifier       | "nodasaurus-rex"                                                                                        |
      | nodeTypeName                  | "Neos.ContentRepository:Document"                                                                       |
      | dimensionSpacePoint           | {"market":"DE", "language":"de"}                                                                        |
      | visibleInDimensionSpacePoints | [{"market":"DE", "language":"de"},{"market": "DE", "language":"gsw"},{"market": "CH", "language":"de"}] |
      | parentNodeAggregateIdentifier | "sir-nodeward-nodington-iii"                                                                            |
      | nodeName                      | "child-document2"                                                                                       |
    And the event NodeSpecializationWasCreated was published with payload:
      | Key                       | Value                              |
      | contentStreamIdentifier   | "cs-identifier"                    |
      | nodeAggregateIdentifier   | "nodasaurus-rex"                   |
      | sourceDimensionSpacePoint | {"market":"DE", "language":"de"}   |
      | specializationLocation    | {"market":"CH", "language":"de"}   |
      | specializationVisibility  | [{"market":"CH", "language":"de"}] |
    And the graph projection is fully up to date
    When the command CreateNodeSpecialization is executed with payload and exceptions are caught:
      | Key                       | Value                             |
      | contentStreamIdentifier   | "cs-identifier"                   |
      | nodeAggregateIdentifier   | "nodasaurus-rex"                  |
      | sourceDimensionSpacePoint | {"market":"CH", "language":"de"}  |
      | targetDimensionSpacePoint | {"market":"CH", "language":"gsw"} |
    Then the last command should have thrown an exception of type "ParentsNodeAggregateNotVisibleInDimensionSpacePoint"

  Scenario: Create a node specialization in a dimension space point without further specializations
    When the command CreateNodeSpecialization is executed with payload:
      | Key                       | Value                             |
      | contentStreamIdentifier   | "cs-identifier"                   |
      | nodeAggregateIdentifier   | "sir-david-nodenborough"          |
      | sourceDimensionSpacePoint | {"market":"DE", "language":"de"}  |
      | targetDimensionSpacePoint | {"market":"CH", "language":"gsw"} |
    And the graph projection is fully up to date
    Then I expect the graph projection to consist of exactly 4 nodes
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-nodesworth", "originDimensionSpacePoint": {}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"de"}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nody-mc-nodeface", "originDimensionSpacePoint": {"market":"DE", "language":"de"}} to exist in the content graph

    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"DE", "language":"de"}
    Then I expect node aggregate identifier "lady-eleonode-nodesworth" and path "" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-nodesworth", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "sir-david-nodenborough" and path "document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"de"}}
    And I expect node aggregate identifier "nody-mc-nodeface" and path "document/child-document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nody-mc-nodeface", "originDimensionSpacePoint": {"market":"DE", "language":"de"}}

    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"DE", "language":"gsw"}
    Then I expect node aggregate identifier "lady-eleonode-nodesworth" and path "" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-nodesworth", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "sir-david-nodenborough" and path "document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"de"}}
    And I expect node aggregate identifier "nody-mc-nodeface" and path "document/child-document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nody-mc-nodeface", "originDimensionSpacePoint": {"market":"DE", "language":"de"}}

    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"CH", "language":"de"}
    Then I expect node aggregate identifier "lady-eleonode-nodesworth" and path "" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-nodesworth", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "sir-david-nodenborough" and path "document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"de"}}
    And I expect node aggregate identifier "nody-mc-nodeface" and path "document/child-document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nody-mc-nodeface", "originDimensionSpacePoint": {"market":"DE", "language":"de"}}

    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"CH", "language":"gsw"}
    Then I expect node aggregate identifier "lady-eleonode-nodesworth" and path "" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-nodesworth", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "sir-david-nodenborough" and path "document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}}
    And I expect node aggregate identifier "nody-mc-nodeface" and path "document/child-document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nody-mc-nodeface", "originDimensionSpacePoint": {"market":"DE", "language":"de"}}

  Scenario: Create a node specialization in a dimension space point with further specializations
    When the command CreateNodeSpecialization is executed with payload:
      | Key                       | Value                             |
      | contentStreamIdentifier   | "cs-identifier"                   |
      | nodeAggregateIdentifier   | "sir-david-nodenborough"          |
      | sourceDimensionSpacePoint | {"market":"DE", "language":"de"}  |
      | targetDimensionSpacePoint | {"market":"DE", "language":"gsw"} |
    And the graph projection is fully up to date
    Then I expect the graph projection to consist of exactly 4 nodes
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-nodesworth", "originDimensionSpacePoint": {}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"de"}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"gsw"}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nody-mc-nodeface", "originDimensionSpacePoint": {"market":"DE", "language":"de"}} to exist in the content graph

    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"DE", "language":"de"}
    Then I expect node aggregate identifier "lady-eleonode-nodesworth" and path "" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-nodesworth", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "sir-david-nodenborough" and path "document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"de"}}
    And I expect node aggregate identifier "nody-mc-nodeface" and path "document/child-document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nody-mc-nodeface", "originDimensionSpacePoint": {"market":"DE", "language":"de"}}

    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"DE", "language":"gsw"}
    Then I expect node aggregate identifier "lady-eleonode-nodesworth" and path "" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-nodesworth", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "sir-david-nodenborough" and path "document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"gsw"}}
    And I expect node aggregate identifier "nody-mc-nodeface" and path "document/child-document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nody-mc-nodeface", "originDimensionSpacePoint": {"market":"DE", "language":"de"}}

    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"CH", "language":"de"}
    Then I expect node aggregate identifier "lady-eleonode-nodesworth" and path "" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-nodesworth", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "sir-david-nodenborough" and path "document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"de"}}
    And I expect node aggregate identifier "nody-mc-nodeface" and path "document/child-document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nody-mc-nodeface", "originDimensionSpacePoint": {"market":"DE", "language":"de"}}

    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"CH", "language":"gsw"}
    Then I expect node aggregate identifier "lady-eleonode-nodesworth" and path "" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-nodesworth", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "sir-david-nodenborough" and path "document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"gsw"}}
    And I expect node aggregate identifier "nody-mc-nodeface" and path "document/child-document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nody-mc-nodeface", "originDimensionSpacePoint": {"market":"DE", "language":"de"}}

  # @todo test against already existing variants in the extended visibility subspace
