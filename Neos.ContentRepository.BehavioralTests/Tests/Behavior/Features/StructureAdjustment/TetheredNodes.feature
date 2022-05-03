@fixtures @adapters=DoctrineDBAL
Feature: Tethered Nodes integrity violations

  As a user of the CR I want to be able to detect and fix tethered nodes that are missing, not allowed or otherwise incorrect

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
        'tethered-node':
          type: 'Neos.ContentRepository.Testing:Tethered'
    'Neos.ContentRepository.Testing:Tethered':
      properties:
        foo:
          type: "string"
          defaultValue: "my default applied"
      childNodes:
        'tethered-leaf':
          type: 'Neos.ContentRepository.Testing:TetheredLeaf'
    'Neos.ContentRepository.Testing:TetheredLeaf': []
    """
    And the event RootWorkspaceWasCreated was published with payload:
      | Key                        | Value                |
      | workspaceName              | "live"               |
      | workspaceTitle             | "Live"               |
      | workspaceDescription       | "The live workspace" |
      | newContentStreamIdentifier | "cs-identifier"      |
      | initiatingUserIdentifier   | "system-user"        |
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
    And the graph projection is fully up to date
    Then I expect no needed structure adjustments for type "Neos.ContentRepository.Testing:Document"

  Scenario: Adjusting the schema adding a new tethered node leads to a MissingTetheredNode integrity violation
    Given I have the following additional NodeTypes configuration:
    """
    'Neos.ContentRepository.Testing:Document':
      childNodes:
        'new-tethered-node':
          type: 'Neos.ContentRepository.Testing:Tethered'
    """
    Then I expect the following structure adjustments for type "Neos.ContentRepository.Testing:Document":
      | Type                  | nodeAggregateIdentifier |
      | TETHERED_NODE_MISSING | sir-david-nodenborough  |


  Scenario: Adding missing tethered nodes resolves the corresponding integrity violations
    Given I have the following additional NodeTypes configuration:
    """
    'Neos.ContentRepository.Testing:Document':
      childNodes:
        'some-new-child':
          type: 'Neos.ContentRepository.Testing:Tethered'
    """
    When I adjust the node structure for node type "Neos.ContentRepository.Testing:Document"
    Then I expect no needed structure adjustments for type "Neos.ContentRepository.Testing:Document"

    When I am in the active content stream of workspace "live" and dimension space point {"market":"CH", "language":"gsw"}
    And I get the node at path "document/some-new-child"
    And I expect this node to have the following properties:
      | Key | Value                |
      | foo | "my default applied" |

  Scenario: Adding the same
    Given I have the following additional NodeTypes configuration:
    """
    'Neos.ContentRepository.Testing:Document':
      childNodes:
        'some-new-child':
          type: 'Neos.ContentRepository.Testing:Tethered'
    """
    When I adjust the node structure for node type "Neos.ContentRepository.Testing:Document"
    Then I expect exactly 6 events to be published on stream "Neos.ContentRepository:ContentStream:cs-identifier"
    When I adjust the node structure for node type "Neos.ContentRepository.Testing:Document"
    Then I expect exactly 6 events to be published on stream "Neos.ContentRepository:ContentStream:cs-identifier"

  Scenario: Adjusting the schema removing a tethered node leads to a DisallowedTetheredNode integrity violation (which can be fixed)
    Given I have the following additional NodeTypes configuration:
    """
    'Neos.ContentRepository.Testing:Document':
      childNodes:
        'tethered-node': ~
    """
    Then I expect the following structure adjustments for type "Neos.ContentRepository.Testing:Document":
      | Type                     | nodeAggregateIdentifier |
      | DISALLOWED_TETHERED_NODE | nodewyn-tetherton       |
    When I adjust the node structure for node type "Neos.ContentRepository.Testing:Document"
    Then I expect no needed structure adjustments for type "Neos.ContentRepository.Testing:Document"
    When I am in content stream "cs-identifier" and dimension space point {"market":"CH", "language":"gsw"}
    Then I expect node aggregate identifier "nodewyn-tetherton" to lead to no node
    Then I expect node aggregate identifier "nodimer-tetherton" to lead to no node

  Scenario: Adjusting the schema changing the type of a tethered node leads to a InvalidTetheredNodeType integrity violation
    Given I have the following additional NodeTypes configuration:
    """
    'Neos.ContentRepository.Testing:Document':
      childNodes:
        'tethered-node':
          type: 'Neos.ContentRepository.Testing:TetheredLeaf'
    """
    Then I expect the following structure adjustments for type "Neos.ContentRepository.Testing:Document":
      | Type                     | nodeAggregateIdentifier |
      | TETHERED_NODE_TYPE_WRONG | nodewyn-tetherton       |

