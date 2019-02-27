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
      | Key                           | Value                               |
      | contentStreamIdentifier       | "cs-identifier"                     |
      | nodeAggregateIdentifier       | "sir-david-nodenborough"            |
      | nodeTypeName                  | "Neos.ContentRepository:Document"   |
      | originDimensionSpacePoint     | {"market":"CH", "language":"gsw"}   |
      | visibleInDimensionSpacePoints | [{"market":"CH", "language":"gsw"}] |
      | parentNodeAggregateIdentifier | "lady-eleonode-nodesworth"          |
      | nodeName                      | "document"                          |
    # We also want to add a child node to make sure it is still reachable after creating a generalization of the parent
    # Node /document/child-document
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                               |
      | contentStreamIdentifier       | "cs-identifier"                     |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                  |
      | nodeTypeName                  | "Neos.ContentRepository:Document"   |
      | originDimensionSpacePoint     | {"market":"CH", "language":"gsw"}   |
      | visibleInDimensionSpacePoints | [{"market":"CH", "language":"gsw"}] |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"            |
      | nodeName                      | "child-document"                    |

  Scenario: Create generalization of node to dimension space point without further generalizations
    When the command CreateNodeGeneralization was published with payload:
      | Key                       | Value                             |
      | contentStreamIdentifier   | "cs-identifier"                   |
      | nodeAggregateIdentifier   | "sir-david-nodenborough"          |
      | sourceDimensionSpacePoint | {"market":"CH", "language":"gsw"} |
      | targetDimensionSpacePoint | {"market":"DE", "language":"de"}  |
    And the graph projection is fully up to date
    Then I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-nodesworth", "originDimensionSpacePoint": {}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"de"}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nody-mc-nodeface", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}} to exist in the content graph

    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"CH", "language":"gsw"}
    Then I expect node aggregate identifier "lady-eleonode-nodesworth" and path "" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-nodesworth", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "sir-david-nodenborough" and path "document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}}
    And I expect node aggregate identifier "nody-mc-nodeface" and path "document/child-document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nody-mc-nodeface", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}}

    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"DE", "language":"de"}
    Then I expect node aggregate identifier "lady-eleonode-nodesworth" and path "" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-nodesworth", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "sir-david-nodenborough" and path "document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"de"}}
    And I expect node aggregate identifier "nody-mc-nodeface" and path "document/child-document" to lead to no node

    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"DE", "language":"gsw"}
    Then I expect node aggregate identifier "lady-eleonode-nodesworth" and path "" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-nodesworth", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "sir-david-nodenborough" and path "document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"de"}}
    And I expect node aggregate identifier "nody-mc-nodeface" and path "document/child-document" to lead to no node

    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"CH", "language":"de"}
    Then I expect node aggregate identifier "lady-eleonode-nodesworth" and path "" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-nodesworth", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "sir-david-nodenborough" and path "document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"de"}}
    And I expect node aggregate identifier "nody-mc-nodeface" and path "document/child-document" to lead to no node

  Scenario: Create generalization of node to dimension space point with further generalizations
    When the command CreateNodeGeneralization was published with payload:
      | Key                       | Value                             |
      | contentStreamIdentifier   | "cs-identifier"                   |
      | nodeAggregateIdentifier   | "sir-david-nodenborough"          |
      | sourceDimensionSpacePoint | {"market":"CH", "language":"gsw"} |
      | targetDimensionSpacePoint | {"market":"DE", "language":"gsw"} |
    And the graph projection is fully up to date
    Then I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-nodesworth", "originDimensionSpacePoint": {}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"gsw"}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nody-mc-nodeface", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}} to exist in the content graph

    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"CH", "language":"gsw"}
    Then I expect node aggregate identifier "lady-eleonode-nodesworth" and path "" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-nodesworth", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "sir-david-nodenborough" and path "document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}}
    And I expect node aggregate identifier "nody-mc-nodeface" and path "document/child-document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nody-mc-nodeface", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}}

    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"DE", "language":"de"}
    Then I expect node aggregate identifier "lady-eleonode-nodesworth" and path "" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-nodesworth", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "sir-david-nodenborough" and path "document" to lead to no node
    And I expect node aggregate identifier "nody-mc-nodeface" and path "document/child-document" to lead to no node

    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"DE", "language":"gsw"}
    Then I expect node aggregate identifier "lady-eleonode-nodesworth" and path "" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-nodesworth", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "sir-david-nodenborough" and path "document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"gsw"}}
    And I expect node aggregate identifier "nody-mc-nodeface" and path "document/child-document" to lead to no node

    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"CH", "language":"de"}
    Then I expect node aggregate identifier "lady-eleonode-nodesworth" and path "" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-nodesworth", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "sir-david-nodenborough" and path "document" to lead to no node
    And I expect node aggregate identifier "nody-mc-nodeface" and path "document/child-document" to lead to no node

    # @todo test against already existing variants in the extended visibility subspace
  # @todo test against parent visibility subspace

  Scenario: Try to create a generalization of a node in a more specialized dimension space point
    When the command CreateNodeGeneralization was published with payload and exceptions are caught:
      | Key                       | Value                             |
      | contentStreamIdentifier   | "cs-identifier"                   |
      | nodeAggregateIdentifier   | "sir-david-nodenborough"          |
      | sourceDimensionSpacePoint | {"market":"DE", "language":"de"}  |
      | targetDimensionSpacePoint | {"market":"DE", "language":"gsw"} |
    Then the last command should have thrown an exception of type "DimensionSpacePointIsNoGeneralizationException"

  Scenario: Try to create a generalization of a node to an already occupied dimension space point
    Given the event NodeGeneralizationWasCreated was published with payload:
      | Key                       | Value                                                                                                 |
      | contentStreamIdentifier   | "cs-identifier"                                                                                       |
      | nodeAggregateIdentifier   | "sir-david-nodenborough"                                                                              |
      | sourceDimensionSpacePoint | {"market":"CH", "language":"gsw"}                                                                     |
      | generalizationLocation    | {"market":"DE", "language":"de"}                                                                      |
      | generalizationVisibility  | [{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"de"}] |

    When the command CreateNodeGeneralization was published with payload and exceptions are caught:
      | Key                       | Value                             |
      | contentStreamIdentifier   | "cs-identifier"                   |
      | nodeAggregateIdentifier   | "sir-david-nodenborough"          |
      | sourceDimensionSpacePoint | {"market":"CH", "language":"gsw"} |
      | targetDimensionSpacePoint | {"market":"DE", "language":"de"}  |
    Then the last command should have thrown an exception of type "DimensionSpacePointIsAlreadyOccupied"
