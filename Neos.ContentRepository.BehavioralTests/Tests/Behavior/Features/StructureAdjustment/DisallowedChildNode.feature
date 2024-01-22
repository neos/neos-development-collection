@contentrepository @adapters=DoctrineDBAL
Feature: Remove disallowed Child Nodes and grandchild nodes

  As a user of the CR I want to be able to detect and remove disallowed child nodes according to the constraints

  Scenario: Direct constraints
    ########################
    # SETUP
    ########################
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository:Root':
      constraints:
        nodeTypes:
          'Neos.ContentRepository.Testing:Document': true

    'Neos.ContentRepository.Testing:Document':
      constraints:
        nodeTypes:
          'Neos.ContentRepository.Testing:SubDocument': true

    'Neos.ContentRepository.Testing:SubDocument': []
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value                |
      | workspaceName              | "live"               |
      | workspaceTitle             | "Live"               |
      | workspaceDescription       | "The live workspace" |
      | newContentStreamId | "cs-identifier"      |
    And the graph projection is fully up to date
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                         | Value                         |
      | contentStreamId     | "cs-identifier"               |
      | nodeAggregateId     | "lady-eleonode-rootford"      |
      | nodeTypeName                | "Neos.ContentRepository:Root" |
    # Node /document
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                     |
      | contentStreamId       | "cs-identifier"                           |
      | nodeAggregateId       | "sir-david-nodenborough"                  |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {}                                        |
      | coveredDimensionSpacePoints   | [{}]                                      |
      | parentNodeAggregateId | "lady-eleonode-rootford"                  |
      | nodeName                      | "document"                                |
      | nodeAggregateClassification   | "regular"                                 |
    # Node /document/sub
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                        |
      | contentStreamId       | "cs-identifier"                              |
      | nodeAggregateId       | "subdoc"                                     |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:SubDocument" |
      | originDimensionSpacePoint     | {}                                           |
      | coveredDimensionSpacePoints   | [{}]                                         |
      | parentNodeAggregateId | "sir-david-nodenborough"                     |
      | nodeName                      | "sub"                                        |
      | nodeAggregateClassification   | "regular"                                    |

    And the graph projection is fully up to date
    Then I expect no needed structure adjustments for type "Neos.ContentRepository:Root"
    Then I expect no needed structure adjustments for type "Neos.ContentRepository.Testing:Document"
    Then I expect no needed structure adjustments for type "Neos.ContentRepository.Testing:SubDocument"

    ########################
    # Actual Test
    ########################
    When I change the node types in content repository "default" to:
    """yaml
    'Neos.ContentRepository:Root':
      constraints:
        nodeTypes:
          'Neos.ContentRepository.Testing:Document': false
    'Neos.ContentRepository.Testing:Document': []
    'Neos.ContentRepository.Testing:SubDocument': []
    """

    Then I expect no needed structure adjustments for type "Neos.ContentRepository:Root"
    Then I expect no needed structure adjustments for type "Neos.ContentRepository.Testing:SubDocument"
    Then I expect the following structure adjustments for type "Neos.ContentRepository.Testing:Document":
      | Type                  | nodeAggregateId |
      | DISALLOWED_CHILD_NODE | sir-david-nodenborough  |

    When I adjust the node structure for node type "Neos.ContentRepository.Testing:Document"
    Then I expect no needed structure adjustments for type "Neos.ContentRepository.Testing:Document"
    When I am in content stream "cs-identifier" and dimension space point {}
    And I expect node aggregate identifier "sir-david-nodenborough" to lead to no node


  Scenario: Tethered Node constraints
    ########################
    # SETUP
    ########################
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository:Root':
      childNodes:
        document:
          type: 'Neos.ContentRepository.Testing:Document'
          constraints:
            nodeTypes:
              'Neos.ContentRepository.Testing:SubDocument': true


    'Neos.ContentRepository.Testing:Document':
      constraints:
        nodeTypes:
          '*': false

    'Neos.ContentRepository.Testing:SubDocument': []
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value                |
      | workspaceName              | "live"               |
      | workspaceTitle             | "Live"               |
      | workspaceDescription       | "The live workspace" |
      | newContentStreamId | "cs-identifier"      |
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                         | Value                         |
      | contentStreamId     | "cs-identifier"               |
      | nodeAggregateId     | "lady-eleonode-rootford"      |
      | nodeTypeName                | "Neos.ContentRepository:Root" |
    # Node /document
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                     |
      | contentStreamId       | "cs-identifier"                           |
      | nodeAggregateId       | "sir-david-nodenborough"                  |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {}                                        |
      | coveredDimensionSpacePoints   | [{}]                                      |
      | parentNodeAggregateId | "lady-eleonode-rootford"                  |
      | nodeName                      | "document"                                |
      | nodeAggregateClassification   | "tethered"                                |
    # Node /document/sub
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                        |
      | contentStreamId       | "cs-identifier"                              |
      | nodeAggregateId       | "subdoc"                                     |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:SubDocument" |
      | originDimensionSpacePoint     | {}                                           |
      | coveredDimensionSpacePoints   | [{}]                                         |
      | parentNodeAggregateId | "sir-david-nodenborough"                     |
      | nodeName                      | "sub"                                        |
      | nodeAggregateClassification   | "regular"                                    |

    And the graph projection is fully up to date
    Then I expect no needed structure adjustments for type "Neos.ContentRepository:Root"
    Then I expect no needed structure adjustments for type "Neos.ContentRepository.Testing:Document"
    Then I expect no needed structure adjustments for type "Neos.ContentRepository.Testing:SubDocument"

    ########################
    # Actual Test
    ########################

    When I change the node types in content repository "default" to:
    """yaml
    'Neos.ContentRepository:Root':
      childNodes:
        document:
          type: 'Neos.ContentRepository.Testing:Document'
          constraints:
            nodeTypes:
              'Neos.ContentRepository.Testing:SubDocument': false

    'Neos.ContentRepository.Testing:Document':
      constraints:
        nodeTypes:
          '*': false

    'Neos.ContentRepository.Testing:SubDocument': []
    """

    Then I expect no needed structure adjustments for type "Neos.ContentRepository.Testing:Document"
    Then I expect no needed structure adjustments for type "Neos.ContentRepository.Testing:Document"
    Then I expect the following structure adjustments for type "Neos.ContentRepository.Testing:SubDocument":
      | Type                  | nodeAggregateId |
      | DISALLOWED_CHILD_NODE | subdoc                  |

    When I adjust the node structure for node type "Neos.ContentRepository.Testing:SubDocument"
    Then I expect no needed structure adjustments for type "Neos.ContentRepository.Testing:SubDocument"
    When I am in content stream "cs-identifier" and dimension space point {}
    And I expect node aggregate identifier "subdoc" to lead to no node

