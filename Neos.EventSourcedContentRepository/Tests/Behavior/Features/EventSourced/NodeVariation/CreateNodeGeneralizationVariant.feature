@fixtures
Feature: Create node generalization

  As a user of the CR I want to create a copy of a node within an aggregate to a more general dimension space point.

  # @todo generalize hidden nodes

  Background:
    Given I have the following content dimensions:
      | Identifier | Default | Values      | Generalizations |
      | market     | DE      | DE, CH      | CH->DE          |
      | language   | en      | en, de, gsw | gsw->de->en     |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:Document':
      childNodes:
        tethered-node:
          type: 'Neos.ContentRepository.Testing:Tethered'
    'Neos.ContentRepository.Testing:Tethered':
      childNodes:
        tethered-leaf:
          type: 'Neos.ContentRepository.Testing:TetheredLeaf'
    'Neos.ContentRepository.Testing:TetheredLeaf': []
    """
    And the event RootWorkspaceWasCreated was published with payload:
      | Key                            | Value                |
      | workspaceName                  | "live"               |
      | workspaceTitle                 | "Live"               |
      | workspaceDescription           | "The live workspace" |
      | newContentStreamIdentifier     | "cs-identifier"      |
      | initiatingUserIdentifier       | "system-user"        |
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                                                                                                                                                                                     |
      | contentStreamIdentifier     | "cs-identifier"                                                                                                                                                                                           |
      | nodeAggregateIdentifier     | "lady-eleonode-rootford"                                                                                                                                                                                  |
      | nodeTypeName                | "Neos.ContentRepository:Root"                                                                                                                                                                             |
      | coveredDimensionSpacePoints | [{"market":"DE", "language":"en"},{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"en"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"}] |
      | initiatingUserIdentifier    | "system-user"                                                                                                                                                                                             |
      | nodeAggregateClassification | "root"                                                                                                                                                                                                    |
    # We have to add another node since root nodes have no dimension space points and thus cannot be varied
    # Node /document
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                     |
      | contentStreamIdentifier       | "cs-identifier"                           |
      | nodeAggregateIdentifier       | "sir-david-nodenborough"                  |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {"market":"CH", "language":"gsw"}         |
      | coveredDimensionSpacePoints   | [{"market":"CH", "language":"gsw"}]       |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                  |
      | nodeName                      | "document"                                |
      | nodeAggregateClassification   | "regular"                                 |
    # We add a tethered child node to provide for test cases for node aggregates of that classification
    # Node /document/tethered-node
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                     |
      | contentStreamIdentifier       | "cs-identifier"                           |
      | nodeAggregateIdentifier       | "nodewyn-tetherton"                       |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Tethered" |
      | originDimensionSpacePoint     | {"market":"CH", "language":"gsw"}         |
      | coveredDimensionSpacePoints   | [{"market":"CH", "language":"gsw"}]       |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                  |
      | nodeName                      | "tethered-node"                           |
      | nodeAggregateClassification   | "tethered"                                |
    # We add a tethered grandchild node to provide for test cases that this works recursively
    # Node /document/tethered-node/tethered-leaf
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                         |
      | contentStreamIdentifier       | "cs-identifier"                               |
      | nodeAggregateIdentifier       | "nodimer-tetherton"                           |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:TetheredLeaf" |
      | originDimensionSpacePoint     | {"market":"CH", "language":"gsw"}             |
      | coveredDimensionSpacePoints   | [{"market":"CH", "language":"gsw"}]           |
      | parentNodeAggregateIdentifier | "nodewyn-tetherton"                           |
      | nodeName                      | "tethered-leaf"                               |
      | nodeAggregateClassification   | "tethered"                                    |
    # We also want to add a child node to make sure it is still reachable after creating a generalization of the parent
    # Node /document/child-document
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                     |
      | contentStreamIdentifier       | "cs-identifier"                           |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                        |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {"market":"CH", "language":"gsw"}         |
      | coveredDimensionSpacePoints   | [{"market":"CH", "language":"gsw"}]       |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                  |
      | nodeName                      | "child-document"                          |
      | nodeAggregateClassification   | "regular"                                 |
    And the graph projection is fully up to date

  Scenario: Create generalization of node to dimension space point without further generalizations
    When the command CreateNodeVariant is executed with payload:
      | Key                     | Value                             |
      | contentStreamIdentifier | "cs-identifier"                   |
      | nodeAggregateIdentifier | "sir-david-nodenborough"          |
      | sourceOrigin            | {"market":"CH", "language":"gsw"} |
      | targetOrigin            | {"market":"DE", "language":"en"}  |
    Then I expect exactly 9 events to be published on stream "Neos.ContentRepository:ContentStream:cs-identifier"
    # The first event is NodeAggregateWithNodeWasCreated
    And event at index 6 is of type "Neos.EventSourcedContentRepository:NodeGeneralizationVariantWasCreated" with payload:
      | Key                     | Expected                                                                                                                                                                |
      | contentStreamIdentifier | "cs-identifier"                                                                                                                                                         |
      | nodeAggregateIdentifier | "sir-david-nodenborough"                                                                                                                                                |
      | sourceOrigin            | {"market":"CH", "language":"gsw"}                                                                                                                                       |
      | generalizationOrigin    | {"market":"DE", "language":"en"}                                                                                                                                        |
      | generalizationCoverage  | [{"market":"DE", "language":"en"},{"market":"CH", "language":"en"},{"market":"DE", "language":"de"},{"market":"CH", "language":"de"},{"market":"DE", "language":"gsw"}] |
    # The first event is NodeAggregateWithNodeWasCreated
    And event at index 7 is of type "Neos.EventSourcedContentRepository:NodeGeneralizationVariantWasCreated" with payload:
      | Key                     | Expected                                                                                                                                                                |
      | contentStreamIdentifier | "cs-identifier"                                                                                                                                                         |
      | nodeAggregateIdentifier | "nodewyn-tetherton"                                                                                                                                                     |
      | sourceOrigin            | {"market":"CH", "language":"gsw"}                                                                                                                                       |
      | generalizationOrigin    | {"market":"DE", "language":"en"}                                                                                                                                        |
      | generalizationCoverage  | [{"market":"DE", "language":"en"},{"market":"CH", "language":"en"},{"market":"DE", "language":"de"},{"market":"CH", "language":"de"},{"market":"DE", "language":"gsw"}] |
    # The first event is NodeAggregateWithNodeWasCreated
    And event at index 8 is of type "Neos.EventSourcedContentRepository:NodeGeneralizationVariantWasCreated" with payload:
      | Key                     | Expected                                                                                                                                                                |
      | contentStreamIdentifier | "cs-identifier"                                                                                                                                                         |
      | nodeAggregateIdentifier | "nodimer-tetherton"                                                                                                                                                     |
      | sourceOrigin            | {"market":"CH", "language":"gsw"}                                                                                                                                       |
      | generalizationOrigin    | {"market":"DE", "language":"en"}                                                                                                                                        |
      | generalizationCoverage  | [{"market":"DE", "language":"en"},{"market":"CH", "language":"en"},{"market":"DE", "language":"de"},{"market":"CH", "language":"de"},{"market":"DE", "language":"gsw"}] |

    When the graph projection is fully up to date
    Then I expect the graph projection to consist of exactly 8 nodes
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"en"}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"en"}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodimer-tetherton", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodimer-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"en"}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nody-mc-nodeface", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}} to exist in the content graph

    When I am in content stream "cs-identifier"
    Then I expect the node aggregate "lady-eleonode-rootford" to exist
    And I expect this node aggregate to occupy dimension space points [{}]
    And I expect this node aggregate to cover dimension space points [{"market":"DE", "language":"en"},{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"en"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"}]

    And I expect the node aggregate "sir-david-nodenborough" to exist
    And I expect this node aggregate to occupy dimension space points [{"market":"DE", "language":"en"},{"market":"CH", "language":"gsw"}]
    And I expect this node aggregate to cover dimension space points [{"market":"DE", "language":"en"},{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"en"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"}]

    And I expect the node aggregate "nodewyn-tetherton" to exist
    And I expect this node aggregate to occupy dimension space points [{"market":"DE", "language":"en"},{"market":"CH", "language":"gsw"}]
    And I expect this node aggregate to cover dimension space points [{"market":"DE", "language":"en"},{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"en"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"}]

    And I expect the node aggregate "nodimer-tetherton" to exist
    And I expect this node aggregate to occupy dimension space points [{"market":"DE", "language":"en"},{"market":"CH", "language":"gsw"}]
    And I expect this node aggregate to cover dimension space points [{"market":"DE", "language":"en"},{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"en"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"}]

    And I expect the node aggregate "nody-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"market":"CH", "language":"gsw"}]
    And I expect this node aggregate to cover dimension space points [{"market":"CH", "language":"gsw"}]

    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"DE", "language":"en"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "sir-david-nodenborough" and path "document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"en"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "nodewyn-tetherton" and path "document/tethered-node" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"en"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"en"}}
    And I expect node aggregate identifier "nodimer-tetherton" and path "document/tethered-node/tethered-leaf" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodimer-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"en"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"en"}}
    And I expect node aggregate identifier "nody-mc-nodeface" and path "document/child-document" to lead to no node

    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"DE", "language":"de"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "sir-david-nodenborough" and path "document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"en"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "nodewyn-tetherton" and path "document/tethered-node" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"en"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"en"}}
    And I expect node aggregate identifier "nodimer-tetherton" and path "document/tethered-node/tethered-leaf" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodimer-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"en"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"en"}}
    And I expect node aggregate identifier "nody-mc-nodeface" and path "document/child-document" to lead to no node

    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"DE", "language":"gsw"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "sir-david-nodenborough" and path "document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"en"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "nodewyn-tetherton" and path "document/tethered-node" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"en"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"en"}}
    And I expect node aggregate identifier "nodimer-tetherton" and path "document/tethered-node/tethered-leaf" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodimer-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"en"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"en"}}
    And I expect node aggregate identifier "nody-mc-nodeface" and path "document/child-document" to lead to no node

    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"CH", "language":"en"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "sir-david-nodenborough" and path "document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"en"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "nodewyn-tetherton" and path "document/tethered-node" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"en"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"en"}}
    And I expect node aggregate identifier "nodimer-tetherton" and path "document/tethered-node/tethered-leaf" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodimer-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"en"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"en"}}
    And I expect node aggregate identifier "nody-mc-nodeface" and path "document/child-document" to lead to no node

    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"CH", "language":"de"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "sir-david-nodenborough" and path "document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"en"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "nodewyn-tetherton" and path "document/tethered-node" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"en"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"en"}}
    And I expect node aggregate identifier "nodimer-tetherton" and path "document/tethered-node/tethered-leaf" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodimer-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"en"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"en"}}
    And I expect node aggregate identifier "nody-mc-nodeface" and path "document/child-document" to lead to no node

    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"CH", "language":"gsw"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "sir-david-nodenborough" and path "document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "nodewyn-tetherton" and path "document/tethered-node" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}}
    And I expect node aggregate identifier "nodimer-tetherton" and path "document/tethered-node/tethered-leaf" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodimer-tetherton", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}}
    And I expect node aggregate identifier "nody-mc-nodeface" and path "document/child-document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nody-mc-nodeface", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}}

  Scenario: Create generalization of node to dimension space point with further generalizations
    When the command CreateNodeVariant is executed with payload:
      | Key                     | Value                             |
      | contentStreamIdentifier | "cs-identifier"                   |
      | nodeAggregateIdentifier | "sir-david-nodenborough"          |
      | sourceOrigin            | {"market":"CH", "language":"gsw"} |
      | targetOrigin            | {"market":"DE", "language":"gsw"} |
    Then I expect exactly 9 events to be published on stream "Neos.ContentRepository:ContentStream:cs-identifier"
    # The first event is NodeAggregateWithNodeWasCreated
    And event at index 6 is of type "Neos.EventSourcedContentRepository:NodeGeneralizationVariantWasCreated" with payload:
      | Key                     | Expected                            |
      | contentStreamIdentifier | "cs-identifier"                     |
      | nodeAggregateIdentifier | "sir-david-nodenborough"            |
      | sourceOrigin            | {"market":"CH", "language":"gsw"}   |
      | generalizationOrigin    | {"market":"DE", "language":"gsw"}   |
      | generalizationCoverage  | [{"market":"DE", "language":"gsw"}] |
    # The first event is NodeAggregateWithNodeWasCreated
    And event at index 7 is of type "Neos.EventSourcedContentRepository:NodeGeneralizationVariantWasCreated" with payload:
      | Key                     | Expected                            |
      | contentStreamIdentifier | "cs-identifier"                     |
      | nodeAggregateIdentifier | "nodewyn-tetherton"                 |
      | sourceOrigin            | {"market":"CH", "language":"gsw"}   |
      | generalizationOrigin    | {"market":"DE", "language":"gsw"}   |
      | generalizationCoverage  | [{"market":"DE", "language":"gsw"}] |
    # The first event is NodeAggregateWithNodeWasCreated
    And event at index 8 is of type "Neos.EventSourcedContentRepository:NodeGeneralizationVariantWasCreated" with payload:
      | Key                     | Expected                            |
      | contentStreamIdentifier | "cs-identifier"                     |
      | nodeAggregateIdentifier | "nodimer-tetherton"                 |
      | sourceOrigin            | {"market":"CH", "language":"gsw"}   |
      | generalizationOrigin    | {"market":"DE", "language":"gsw"}   |
      | generalizationCoverage  | [{"market":"DE", "language":"gsw"}] |

    When the graph projection is fully up to date
    Then I expect the graph projection to consist of exactly 8 nodes
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"gsw"}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"gsw"}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodimer-tetherton", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodimer-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"gsw"}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nody-mc-nodeface", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}} to exist in the content graph

    When I am in content stream "cs-identifier"
    Then I expect the node aggregate "lady-eleonode-rootford" to exist
    And I expect this node aggregate to occupy dimension space points [{}]
    And I expect this node aggregate to cover dimension space points [{"market":"DE", "language":"en"},{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"en"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"}]

    And I expect the node aggregate "sir-david-nodenborough" to exist
    And I expect this node aggregate to occupy dimension space points [{"market":"DE", "language":"gsw"},{"market":"CH", "language":"gsw"}]
    And I expect this node aggregate to cover dimension space points [{"market":"DE", "language":"gsw"},{"market":"CH", "language":"gsw"}]

    And I expect the node aggregate "nodewyn-tetherton" to exist
    And I expect this node aggregate to occupy dimension space points [{"market":"DE", "language":"gsw"},{"market":"CH", "language":"gsw"}]
    And I expect this node aggregate to cover dimension space points [{"market":"DE", "language":"gsw"},{"market":"CH", "language":"gsw"}]

    And I expect the node aggregate "nodimer-tetherton" to exist
    And I expect this node aggregate to occupy dimension space points [{"market":"DE", "language":"gsw"},{"market":"CH", "language":"gsw"}]
    And I expect this node aggregate to cover dimension space points [{"market":"DE", "language":"gsw"},{"market":"CH", "language":"gsw"}]

    And I expect the node aggregate "nody-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"market":"CH", "language":"gsw"}]
    And I expect this node aggregate to cover dimension space points [{"market":"CH", "language":"gsw"}]

    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"DE", "language":"en"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "sir-david-nodenborough" and path "document" to lead to no node
    And I expect node aggregate identifier "nodewyn-tetherton" and path "document/tethered-node" to lead to no node
    And I expect node aggregate identifier "nodimer-tetherton" and path "document/tethered-node/tethered-leaf" to lead to no node
    And I expect node aggregate identifier "nody-mc-nodeface" and path "document/child-document" to lead to no node

    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"DE", "language":"de"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "sir-david-nodenborough" and path "document" to lead to no node
    And I expect node aggregate identifier "nodewyn-tetherton" and path "document/tethered-node" to lead to no node
    And I expect node aggregate identifier "nodimer-tetherton" and path "document/tethered-node/tethered-leaf" to lead to no node
    And I expect node aggregate identifier "nody-mc-nodeface" and path "document/child-document" to lead to no node

    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"DE", "language":"gsw"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "sir-david-nodenborough" and path "document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"gsw"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "nodewyn-tetherton" and path "document/tethered-node" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"gsw"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"gsw"}}
    And I expect node aggregate identifier "nodimer-tetherton" and path "document/tethered-node/tethered-leaf" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodimer-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"gsw"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"gsw"}}
    And I expect node aggregate identifier "nody-mc-nodeface" and path "document/child-document" to lead to no node

    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"CH", "language":"en"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "sir-david-nodenborough" and path "document" to lead to no node
    And I expect node aggregate identifier "nodewyn-tetherton" and path "document/tethered-node" to lead to no node
    And I expect node aggregate identifier "nodimer-tetherton" and path "document/tethered-node/tethered-leaf" to lead to no node
    And I expect node aggregate identifier "nody-mc-nodeface" and path "document/child-document" to lead to no node

    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"CH", "language":"de"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "sir-david-nodenborough" and path "document" to lead to no node
    And I expect node aggregate identifier "nodewyn-tetherton" and path "document/tethered-node" to lead to no node
    And I expect node aggregate identifier "nodimer-tetherton" and path "document/tethered-node/tethered-leaf" to lead to no node
    And I expect node aggregate identifier "nody-mc-nodeface" and path "document/child-document" to lead to no node

    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"CH", "language":"gsw"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "sir-david-nodenborough" and path "document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "nodewyn-tetherton" and path "document/tethered-node" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}}
    And I expect node aggregate identifier "nodimer-tetherton" and path "document/tethered-node/tethered-leaf" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodimer-tetherton", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}}
    And I expect node aggregate identifier "nody-mc-nodeface" and path "document/child-document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nody-mc-nodeface", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}}

  Scenario: Create generalization of node to dimension space point with specializations that are partially occupied and covered
    When the event NodeGeneralizationVariantWasCreated was published with payload:
      | Key                     | Value                                                                                                 |
      | contentStreamIdentifier | "cs-identifier"                                                                                       |
      | nodeAggregateIdentifier | "sir-david-nodenborough"                                                                              |
      | sourceOrigin            | {"market":"CH", "language":"gsw"}                                                                     |
      | generalizationOrigin    | {"market":"DE", "language":"de"}                                                                      |
      | generalizationCoverage  | [{"market":"DE", "language":"de"},{"market":"CH", "language":"de"},{"market":"DE", "language":"gsw"}] |
    And the event NodeGeneralizationVariantWasCreated was published with payload:
      | Key                     | Value                                                                                                 |
      | contentStreamIdentifier | "cs-identifier"                                                                                       |
      | nodeAggregateIdentifier | "nodewyn-tetherton"                                                                                   |
      | sourceOrigin            | {"market":"CH", "language":"gsw"}                                                                     |
      | generalizationOrigin    | {"market":"DE", "language":"de"}                                                                      |
      | generalizationCoverage  | [{"market":"DE", "language":"de"},{"market":"CH", "language":"de"},{"market":"DE", "language":"gsw"}] |
    And the event NodeGeneralizationVariantWasCreated was published with payload:
      | Key                     | Value                                                                                                 |
      | contentStreamIdentifier | "cs-identifier"                                                                                       |
      | nodeAggregateIdentifier | "nodimer-tetherton"                                                                                   |
      | sourceOrigin            | {"market":"CH", "language":"gsw"}                                                                     |
      | generalizationOrigin    | {"market":"DE", "language":"de"}                                                                      |
      | generalizationCoverage  | [{"market":"DE", "language":"de"},{"market":"CH", "language":"de"},{"market":"DE", "language":"gsw"}] |
    And the graph projection is fully up to date

    When the command CreateNodeVariant is executed with payload:
      | Key                     | Value                             |
      | contentStreamIdentifier | "cs-identifier"                   |
      | nodeAggregateIdentifier | "sir-david-nodenborough"          |
      | sourceOrigin            | {"market":"CH", "language":"gsw"} |
      | targetOrigin            | {"market":"DE", "language":"en"}  |
    Then I expect exactly 12 events to be published on stream "Neos.ContentRepository:ContentStream:cs-identifier"
    # The first event is NodeAggregateWithNodeWasCreated
    # The second event is the above
    And event at index 9 is of type "Neos.EventSourcedContentRepository:NodeGeneralizationVariantWasCreated" with payload:
      | Key                     | Expected                                                            |
      | contentStreamIdentifier | "cs-identifier"                                                     |
      | nodeAggregateIdentifier | "sir-david-nodenborough"                                            |
      | sourceOrigin            | {"market":"CH", "language":"gsw"}                                   |
      | generalizationOrigin    | {"market":"DE", "language":"en"}                                    |
      | generalizationCoverage  | [{"market":"DE", "language":"en"},{"market":"CH", "language":"en"}] |
    # The first event is NodeAggregateWithNodeWasCreated
    # The second event is the above
    And event at index 10 is of type "Neos.EventSourcedContentRepository:NodeGeneralizationVariantWasCreated" with payload:
      | Key                     | Expected                                                            |
      | contentStreamIdentifier | "cs-identifier"                                                     |
      | nodeAggregateIdentifier | "nodewyn-tetherton"                                                 |
      | sourceOrigin            | {"market":"CH", "language":"gsw"}                                   |
      | generalizationOrigin    | {"market":"DE", "language":"en"}                                    |
      | generalizationCoverage  | [{"market":"DE", "language":"en"},{"market":"CH", "language":"en"}] |
    # The first event is NodeAggregateWithNodeWasCreated
    # The second event is the above
    And event at index 11 is of type "Neos.EventSourcedContentRepository:NodeGeneralizationVariantWasCreated" with payload:
      | Key                     | Expected                                                            |
      | contentStreamIdentifier | "cs-identifier"                                                     |
      | nodeAggregateIdentifier | "nodimer-tetherton"                                                 |
      | sourceOrigin            | {"market":"CH", "language":"gsw"}                                   |
      | generalizationOrigin    | {"market":"DE", "language":"en"}                                    |
      | generalizationCoverage  | [{"market":"DE", "language":"en"},{"market":"CH", "language":"en"}] |

    When the graph projection is fully up to date
    Then I expect the graph projection to consist of exactly 11 nodes
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"de"}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"en"}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"de"}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"en"}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodimer-tetherton", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodimer-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"de"}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodimer-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"en"}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nody-mc-nodeface", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}} to exist in the content graph

    When I am in content stream "cs-identifier"
    Then I expect the node aggregate "lady-eleonode-rootford" to exist
    And I expect this node aggregate to occupy dimension space points [{}]
    And I expect this node aggregate to cover dimension space points [{"market":"DE", "language":"en"},{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"en"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"}]

    And I expect the node aggregate "sir-david-nodenborough" to exist
    And I expect this node aggregate to occupy dimension space points [{"market":"DE", "language":"en"},{"market":"DE", "language":"de"},{"market":"CH", "language":"gsw"}]
    And I expect this node aggregate to cover dimension space points [{"market":"DE", "language":"en"},{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"en"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"}]

    And I expect the node aggregate "nodewyn-tetherton" to exist
    And I expect this node aggregate to occupy dimension space points [{"market":"DE", "language":"en"},{"market":"DE", "language":"de"},{"market":"CH", "language":"gsw"}]
    And I expect this node aggregate to cover dimension space points [{"market":"DE", "language":"en"},{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"en"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"}]

    And I expect the node aggregate "nodimer-tetherton" to exist
    And I expect this node aggregate to occupy dimension space points [{"market":"DE", "language":"en"},{"market":"DE", "language":"de"},{"market":"CH", "language":"gsw"}]
    And I expect this node aggregate to cover dimension space points [{"market":"DE", "language":"en"},{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"en"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"}]

    And I expect the node aggregate "nody-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"market":"CH", "language":"gsw"}]
    And I expect this node aggregate to cover dimension space points [{"market":"CH", "language":"gsw"}]

    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"DE", "language":"en"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "sir-david-nodenborough" and path "document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"en"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "nodewyn-tetherton" and path "document/tethered-node" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"en"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"en"}}
    And I expect node aggregate identifier "nodimer-tetherton" and path "document/tethered-node/tethered-leaf" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodimer-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"en"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"en"}}
    And I expect node aggregate identifier "nody-mc-nodeface" and path "document/child-document" to lead to no node

    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"DE", "language":"de"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "sir-david-nodenborough" and path "document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"de"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "nodewyn-tetherton" and path "document/tethered-node" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"de"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"de"}}
    And I expect node aggregate identifier "nodimer-tetherton" and path "document/tethered-node/tethered-leaf" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodimer-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"de"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"de"}}
    And I expect node aggregate identifier "nody-mc-nodeface" and path "document/child-document" to lead to no node

    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"DE", "language":"gsw"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "sir-david-nodenborough" and path "document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"de"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "nodewyn-tetherton" and path "document/tethered-node" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"de"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"de"}}
    And I expect node aggregate identifier "nodimer-tetherton" and path "document/tethered-node/tethered-leaf" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodimer-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"de"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"de"}}
    And I expect node aggregate identifier "nody-mc-nodeface" and path "document/child-document" to lead to no node

    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"CH", "language":"en"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "sir-david-nodenborough" and path "document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"en"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "nodewyn-tetherton" and path "document/tethered-node" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"en"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"en"}}
    And I expect node aggregate identifier "nodimer-tetherton" and path "document/tethered-node/tethered-leaf" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodimer-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"en"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"en"}}
    And I expect node aggregate identifier "nody-mc-nodeface" and path "document/child-document" to lead to no node

    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"CH", "language":"de"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "sir-david-nodenborough" and path "document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"de"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "nodewyn-tetherton" and path "document/tethered-node" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"de"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"de"}}
    And I expect node aggregate identifier "nodimer-tetherton" and path "document/tethered-node/tethered-leaf" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodimer-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"de"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"de"}}
    And I expect node aggregate identifier "nody-mc-nodeface" and path "document/child-document" to lead to no node

    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"CH", "language":"gsw"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "sir-david-nodenborough" and path "document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "nodewyn-tetherton" and path "document/tethered-node" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}}
    And I expect node aggregate identifier "nodimer-tetherton" and path "document/tethered-node/tethered-leaf" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodimer-tetherton", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}}
    And I expect node aggregate identifier "nody-mc-nodeface" and path "document/child-document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nody-mc-nodeface", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}}

  Scenario: Create generalization of a node to a dimension space point that is already covered by a more general generalization
    When the event NodeGeneralizationVariantWasCreated was published with payload:
      | Key                     | Value                                                                                                                                                                   |
      | contentStreamIdentifier | "cs-identifier"                                                                                                                                                         |
      | nodeAggregateIdentifier | "sir-david-nodenborough"                                                                                                                                                |
      | sourceOrigin            | {"market":"CH", "language":"gsw"}                                                                                                                                       |
      | generalizationOrigin    | {"market":"DE", "language":"en"}                                                                                                                                        |
      | generalizationCoverage  | [{"market":"DE", "language":"en"},{"market":"CH", "language":"en"},{"market":"DE", "language":"de"},{"market":"CH", "language":"de"},{"market":"DE", "language":"gsw"}] |
    And the event NodeGeneralizationVariantWasCreated was published with payload:
      | Key                     | Value                                                                                                                                                                   |
      | contentStreamIdentifier | "cs-identifier"                                                                                                                                                         |
      | nodeAggregateIdentifier | "nodewyn-tetherton"                                                                                                                                                     |
      | sourceOrigin            | {"market":"CH", "language":"gsw"}                                                                                                                                       |
      | generalizationOrigin    | {"market":"DE", "language":"en"}                                                                                                                                        |
      | generalizationCoverage  | [{"market":"DE", "language":"en"},{"market":"CH", "language":"en"},{"market":"DE", "language":"de"},{"market":"CH", "language":"de"},{"market":"DE", "language":"gsw"}] |
    And the event NodeGeneralizationVariantWasCreated was published with payload:
      | Key                     | Value                                                                                                                                                                   |
      | contentStreamIdentifier | "cs-identifier"                                                                                                                                                         |
      | nodeAggregateIdentifier | "nodimer-tetherton"                                                                                                                                                     |
      | sourceOrigin            | {"market":"CH", "language":"gsw"}                                                                                                                                       |
      | generalizationOrigin    | {"market":"DE", "language":"en"}                                                                                                                                        |
      | generalizationCoverage  | [{"market":"DE", "language":"en"},{"market":"CH", "language":"en"},{"market":"DE", "language":"de"},{"market":"CH", "language":"de"},{"market":"DE", "language":"gsw"}] |
    And the graph projection is fully up to date

    When the command CreateNodeVariant is executed with payload:
      | Key                     | Value                             |
      | contentStreamIdentifier | "cs-identifier"                   |
      | nodeAggregateIdentifier | "sir-david-nodenborough"          |
      | sourceOrigin            | {"market":"CH", "language":"gsw"} |
      | targetOrigin            | {"market":"DE", "language":"de"}  |
    Then I expect exactly 12 events to be published on stream "Neos.ContentRepository:ContentStream:cs-identifier"
    # The first event is NodeAggregateWithNodeWasCreated
    # The second event is the above
    And event at index 9 is of type "Neos.EventSourcedContentRepository:NodeGeneralizationVariantWasCreated" with payload:
      | Key                     | Expected                                                                                              |
      | contentStreamIdentifier | "cs-identifier"                                                                                       |
      | nodeAggregateIdentifier | "sir-david-nodenborough"                                                                              |
      | sourceOrigin            | {"market":"CH", "language":"gsw"}                                                                     |
      | generalizationOrigin    | {"market":"DE", "language":"de"}                                                                      |
      | generalizationCoverage  | [{"market":"DE", "language":"de"},{"market":"CH", "language":"de"},{"market":"DE", "language":"gsw"}] |
    # The first event is NodeAggregateWithNodeWasCreated
    # The second event is the above
    And event at index 10 is of type "Neos.EventSourcedContentRepository:NodeGeneralizationVariantWasCreated" with payload:
      | Key                     | Expected                                                                                              |
      | contentStreamIdentifier | "cs-identifier"                                                                                       |
      | nodeAggregateIdentifier | "nodewyn-tetherton"                                                                                   |
      | sourceOrigin            | {"market":"CH", "language":"gsw"}                                                                     |
      | generalizationOrigin    | {"market":"DE", "language":"de"}                                                                      |
      | generalizationCoverage  | [{"market":"DE", "language":"de"},{"market":"CH", "language":"de"},{"market":"DE", "language":"gsw"}] |
    # The first event is NodeAggregateWithNodeWasCreated
    # The second event is the above
    And event at index 11 is of type "Neos.EventSourcedContentRepository:NodeGeneralizationVariantWasCreated" with payload:
      | Key                     | Expected                                                                                              |
      | contentStreamIdentifier | "cs-identifier"                                                                                       |
      | nodeAggregateIdentifier | "nodimer-tetherton"                                                                                   |
      | sourceOrigin            | {"market":"CH", "language":"gsw"}                                                                     |
      | generalizationOrigin    | {"market":"DE", "language":"de"}                                                                      |
      | generalizationCoverage  | [{"market":"DE", "language":"de"},{"market":"CH", "language":"de"},{"market":"DE", "language":"gsw"}] |

    When the graph projection is fully up to date
    Then I expect the graph projection to consist of exactly 11 nodes
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"de"}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"en"}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"en"}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"de"}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodimer-tetherton", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodimer-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"en"}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodimer-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"de"}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nody-mc-nodeface", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}} to exist in the content graph

    When I am in content stream "cs-identifier"
    Then I expect the node aggregate "lady-eleonode-rootford" to exist
    And I expect this node aggregate to occupy dimension space points [{}]
    And I expect this node aggregate to cover dimension space points [{"market":"DE", "language":"en"},{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"en"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"}]

    And I expect the node aggregate "sir-david-nodenborough" to exist
    And I expect this node aggregate to occupy dimension space points [{"market":"DE", "language":"en"},{"market":"DE", "language":"de"},{"market":"CH", "language":"gsw"}]
    And I expect this node aggregate to cover dimension space points [{"market":"DE", "language":"en"},{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"en"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"}]

    And I expect the node aggregate "nodewyn-tetherton" to exist
    And I expect this node aggregate to occupy dimension space points [{"market":"DE", "language":"en"},{"market":"DE", "language":"de"},{"market":"CH", "language":"gsw"}]
    And I expect this node aggregate to cover dimension space points [{"market":"DE", "language":"en"},{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"en"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"}]

    And I expect the node aggregate "nodimer-tetherton" to exist
    And I expect this node aggregate to occupy dimension space points [{"market":"DE", "language":"en"},{"market":"DE", "language":"de"},{"market":"CH", "language":"gsw"}]
    And I expect this node aggregate to cover dimension space points [{"market":"DE", "language":"en"},{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"en"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"}]

    And I expect the node aggregate "nody-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"market":"CH", "language":"gsw"}]
    And I expect this node aggregate to cover dimension space points [{"market":"CH", "language":"gsw"}]

    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"DE", "language":"en"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "sir-david-nodenborough" and path "document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"en"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "nodewyn-tetherton" and path "document/tethered-node" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"en"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"en"}}
    And I expect node aggregate identifier "nodimer-tetherton" and path "document/tethered-node/tethered-leaf" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodimer-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"en"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"en"}}
    And I expect node aggregate identifier "nody-mc-nodeface" and path "document/child-document" to lead to no node

    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"DE", "language":"de"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "sir-david-nodenborough" and path "document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"de"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "nodewyn-tetherton" and path "document/tethered-node" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"de"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"de"}}
    And I expect node aggregate identifier "nodimer-tetherton" and path "document/tethered-node/tethered-leaf" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodimer-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"de"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"de"}}
    And I expect node aggregate identifier "nody-mc-nodeface" and path "document/child-document" to lead to no node

    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"DE", "language":"gsw"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "sir-david-nodenborough" and path "document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"de"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "nodewyn-tetherton" and path "document/tethered-node" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"de"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"de"}}
    And I expect node aggregate identifier "nodimer-tetherton" and path "document/tethered-node/tethered-leaf" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodimer-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"de"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"de"}}
    And I expect node aggregate identifier "nody-mc-nodeface" and path "document/child-document" to lead to no node

    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"CH", "language":"en"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "sir-david-nodenborough" and path "document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"en"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "nodewyn-tetherton" and path "document/tethered-node" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"en"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"en"}}
    And I expect node aggregate identifier "nodimer-tetherton" and path "document/tethered-node/tethered-leaf" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodimer-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"en"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"en"}}
    And I expect node aggregate identifier "nody-mc-nodeface" and path "document/child-document" to lead to no node

    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"CH", "language":"de"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "sir-david-nodenborough" and path "document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"de"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "nodewyn-tetherton" and path "document/tethered-node" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"de"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"DE", "language":"de"}}
    And I expect node aggregate identifier "nodimer-tetherton" and path "document/tethered-node/tethered-leaf" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodimer-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"de"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"DE", "language":"de"}}
    And I expect node aggregate identifier "nody-mc-nodeface" and path "document/child-document" to lead to no node

    When I am in content stream "cs-identifier" and Dimension Space Point {"market":"CH", "language":"gsw"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "sir-david-nodenborough" and path "document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"lady-eleonode-rootford", "originDimensionSpacePoint": {}}
    And I expect node aggregate identifier "nodewyn-tetherton" and path "document/tethered-node" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}}
    And I expect node aggregate identifier "nodimer-tetherton" and path "document/tethered-node/tethered-leaf" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodimer-tetherton", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nodewyn-tetherton", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}}
    And I expect node aggregate identifier "nody-mc-nodeface" and path "document/child-document" to lead to node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"nody-mc-nodeface", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier", "nodeAggregateIdentifier":"sir-david-nodenborough", "originDimensionSpacePoint": {"market":"CH", "language":"gsw"}}
