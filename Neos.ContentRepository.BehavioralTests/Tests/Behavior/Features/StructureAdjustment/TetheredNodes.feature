@contentrepository @adapters=DoctrineDBAL
Feature: Tethered Nodes integrity violations

  As a user of the CR I want to be able to detect and fix tethered nodes that are missing, not allowed or otherwise incorrect

  Background:
    Given using the following content dimensions:
      | Identifier | Values      | Generalizations |
      | market     | DE, CH      | CH->DE          |
      | language   | en, de, gsw | gsw->de->en     |
    And using the following node types:
    """yaml
    'Neos.ContentRepository:Root':
      childNodes:
        'originally-tethered-node':
          type: 'Neos.ContentRepository.Testing:Tethered'
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
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | workspaceTitle       | "Live"               |
      | workspaceDescription | "The live workspace" |
      | newContentStreamId   | "cs-identifier"      |
    And the graph projection is fully up to date
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                                | Value                                               |
      | contentStreamId                    | "cs-identifier"                                     |
      | nodeAggregateId                    | "lady-eleonode-rootford"                            |
      | nodeTypeName                       | "Neos.ContentRepository:Root"                       |
      | tetheredDescendantNodeAggregateIds | {"originally-tethered-node": "originode-tetherton"} |
    # We have to add another node since root nodes have no dimension space points and thus cannot be varied
    # Node /document
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                     |
      | contentStreamId             | "cs-identifier"                           |
      | nodeAggregateId             | "sir-david-nodenborough"                  |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint   | {"market":"CH", "language":"gsw"}         |
      | coveredDimensionSpacePoints | [{"market":"CH", "language":"gsw"}]       |
      | parentNodeAggregateId       | "lady-eleonode-rootford"                  |
      | nodeName                    | "document"                                |
      | nodeAggregateClassification | "regular"                                 |
    # We add a tethered child node to provide for test cases for node aggregates of that classification
    # Node /document/tethered-node
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                     |
      | contentStreamId             | "cs-identifier"                           |
      | nodeAggregateId             | "nodewyn-tetherton"                       |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Tethered" |
      | originDimensionSpacePoint   | {"market":"CH", "language":"gsw"}         |
      | coveredDimensionSpacePoints | [{"market":"CH", "language":"gsw"}]       |
      | parentNodeAggregateId       | "sir-david-nodenborough"                  |
      | nodeName                    | "tethered-node"                           |
      | nodeAggregateClassification | "tethered"                                |
    # We add a tethered grandchild node to provide for test cases that this works recursively
    # Node /document/tethered-node/tethered-leaf
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                         |
      | contentStreamId             | "cs-identifier"                               |
      | nodeAggregateId             | "nodimer-tetherton"                           |
      | nodeTypeName                | "Neos.ContentRepository.Testing:TetheredLeaf" |
      | originDimensionSpacePoint   | {"market":"CH", "language":"gsw"}             |
      | coveredDimensionSpacePoints | [{"market":"CH", "language":"gsw"}]           |
      | parentNodeAggregateId       | "nodewyn-tetherton"                           |
      | nodeName                    | "tethered-leaf"                               |
      | nodeAggregateClassification | "tethered"                                    |
    And the graph projection is fully up to date
    Then I expect no needed structure adjustments for type "Neos.ContentRepository.Testing:Document"

  Scenario: Adjusting the schema adding a new tethered node leads to a MissingTetheredNode integrity violation
    Given I change the node types in content repository "default" to:
    """yaml
    'Neos.ContentRepository:Root':
      childNodes:
        'originally-tethered-node':
          type: 'Neos.ContentRepository.Testing:Tethered'
        'tethered-node':
          type: 'Neos.ContentRepository.Testing:Tethered'
    'Neos.ContentRepository.Testing:Document':
      childNodes:
        'tethered-node':
          type: 'Neos.ContentRepository.Testing:Tethered'
        'new-tethered-node':
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
    Then I expect the following structure adjustments for type "Neos.ContentRepository.Testing:Document":
      | Type                  | nodeAggregateId        |
      | TETHERED_NODE_MISSING | sir-david-nodenborough |
    And I expect the following structure adjustments for type "Neos.ContentRepository:Root":
      | Type                  | nodeAggregateId        |
      | TETHERED_NODE_MISSING | lady-eleonode-rootford |

  Scenario: Adding missing tethered nodes resolves the corresponding integrity violations
    Given I change the node types in content repository "default" to:
    """yaml
    'Neos.ContentRepository:Root':
      childNodes:
        'originally-tethered-node':
          type: 'Neos.ContentRepository.Testing:Tethered'
        'tethered-node':
          type: 'Neos.ContentRepository.Testing:Tethered'
    'Neos.ContentRepository.Testing:Document':
      childNodes:
        'tethered-node':
          type: 'Neos.ContentRepository.Testing:Tethered'
        'some-new-child':
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
    When I adjust the node structure for node type "Neos.ContentRepository.Testing:Document"
    Then I expect no needed structure adjustments for type "Neos.ContentRepository.Testing:Document"
    When I adjust the node structure for node type "Neos.ContentRepository:Root"
    Then I expect no needed structure adjustments for type "Neos.ContentRepository:Root"

    When I am in the active content stream of workspace "live" and dimension space point {"market":"CH", "language":"gsw"}
    And I get the node at path "document/some-new-child"
    And I expect this node to have the following properties:
      | Key | Value                |
      | foo | "my default applied" |
    And I get the node at path "tethered-node"
    And I expect this node to have the following properties:
      | Key | Value                |
      | foo | "my default applied" |

  Scenario: Adding the same
    Given I change the node types in content repository "default" to:
    """yaml
    'Neos.ContentRepository.Testing:Document':
      childNodes:
        'tethered-node':
          type: 'Neos.ContentRepository.Testing:Tethered'
        'some-new-child':
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
    When I adjust the node structure for node type "Neos.ContentRepository.Testing:Document"
    Then I expect exactly 8 events to be published on stream "ContentStream:cs-identifier"
    When I adjust the node structure for node type "Neos.ContentRepository.Testing:Document"
    Then I expect exactly 8 events to be published on stream "ContentStream:cs-identifier"

  Scenario: Adjusting the schema removing a tethered node leads to a DisallowedTetheredNode integrity violation (which can be fixed)
    Given I change the node types in content repository "default" to:
    """yaml
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:Document': []
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
    Then I expect the following structure adjustments for type "Neos.ContentRepository.Testing:Document":
      | Type                     | nodeAggregateId   |
      | DISALLOWED_TETHERED_NODE | nodewyn-tetherton |
    Then I expect the following structure adjustments for type "Neos.ContentRepository:Root":
      | Type                     | nodeAggregateId     |
      | DISALLOWED_TETHERED_NODE | originode-tetherton |
    When I adjust the node structure for node type "Neos.ContentRepository.Testing:Document"
    Then I expect no needed structure adjustments for type "Neos.ContentRepository.Testing:Document"
    When I adjust the node structure for node type "Neos.ContentRepository:Root"
    Then I expect no needed structure adjustments for type "Neos.ContentRepository:Root"
    When I am in content stream "cs-identifier" and dimension space point {"market":"CH", "language":"gsw"}
    Then I expect node aggregate identifier "nodewyn-tetherton" to lead to no node
    Then I expect node aggregate identifier "nodimer-tetherton" to lead to no node
    And  I expect path "tethered-node" to lead to no node

  Scenario: Adjusting the schema changing the type of a tethered node leads to a InvalidTetheredNodeType integrity violation
    Given I change the node types in content repository "default" to:
    """yaml
    'Neos.ContentRepository.Testing:Document':
      childNodes:
        'tethered-node':
          type: 'Neos.ContentRepository.Testing:TetheredLeaf'
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
    Then I expect the following structure adjustments for type "Neos.ContentRepository.Testing:Document":
      | Type                     | nodeAggregateId   |
      | TETHERED_NODE_TYPE_WRONG | nodewyn-tetherton |

