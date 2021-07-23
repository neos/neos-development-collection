@fixtures
Feature: Remove disallowed Child Nodes and grandchild nodes

  As a user of the CR I want to be able to detect and remove disallowed child nodes according to the constraints

  Background:
    Given I have no content dimensions

  Scenario: Direct constraints
    ########################
    # SETUP
    ########################
    And I have the following NodeTypes configuration:
    """
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
    And the event RootWorkspaceWasCreated was published with payload:
      | Key                        | Value                |
      | workspaceName              | "live"               |
      | workspaceTitle             | "Live"               |
      | workspaceDescription       | "The live workspace" |
      | newContentStreamIdentifier | "cs-identifier"      |
      | initiatingUserIdentifier   | "system-user"        |
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                         |
      | contentStreamIdentifier     | "cs-identifier"               |
      | nodeAggregateIdentifier     | "lady-eleonode-rootford"      |
      | nodeTypeName                | "Neos.ContentRepository:Root" |
      | coveredDimensionSpacePoints | [{}]                          |
      | initiatingUserIdentifier    | "system-user"                 |
      | nodeAggregateClassification | "root"                        |
    # Node /document
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                     |
      | contentStreamIdentifier       | "cs-identifier"                           |
      | nodeAggregateIdentifier       | "sir-david-nodenborough"                  |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {}                                        |
      | coveredDimensionSpacePoints   | [{}]                                      |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                  |
      | nodeName                      | "document"                                |
      | nodeAggregateClassification   | "regular"                                 |
    # Node /document/sub
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                        |
      | contentStreamIdentifier       | "cs-identifier"                              |
      | nodeAggregateIdentifier       | "subdoc"                                     |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:SubDocument" |
      | originDimensionSpacePoint     | {}                                           |
      | coveredDimensionSpacePoints   | [{}]                                         |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                     |
      | nodeName                      | "sub"                                        |
      | nodeAggregateClassification   | "regular"                                    |

    And the graph projection is fully up to date
    Then I expect no needed structure adjustments for type "Neos.ContentRepository:Root"
    Then I expect no needed structure adjustments for type "Neos.ContentRepository.Testing:Document"
    Then I expect no needed structure adjustments for type "Neos.ContentRepository.Testing:SubDocument"

    ########################
    # Actual Test
    ########################
    When I have the following NodeTypes configuration:
    """
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
      | Type                  | nodeAggregateIdentifier |
      | DISALLOWED_CHILD_NODE | sir-david-nodenborough  |

    When I adjust the node structure for node type "Neos.ContentRepository.Testing:Document"
    Then I expect no needed structure adjustments for type "Neos.ContentRepository.Testing:Document"
    When I am in content stream "cs-identifier" and dimension space point {}
    And I expect node aggregate identifier "sir-david-nodenborough" to lead to no node


  Scenario: Tethered Node constraints
    ########################
    # SETUP
    ########################
    And I have the following NodeTypes configuration:
    """
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
    And the event RootWorkspaceWasCreated was published with payload:
      | Key                        | Value                |
      | workspaceName              | "live"               |
      | workspaceTitle             | "Live"               |
      | workspaceDescription       | "The live workspace" |
      | newContentStreamIdentifier | "cs-identifier"      |
      | initiatingUserIdentifier   | "system-user"        |
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                         |
      | contentStreamIdentifier     | "cs-identifier"               |
      | nodeAggregateIdentifier     | "lady-eleonode-rootford"      |
      | nodeTypeName                | "Neos.ContentRepository:Root" |
      | coveredDimensionSpacePoints | [{}]                          |
      | initiatingUserIdentifier    | "system-user"                 |
      | nodeAggregateClassification | "root"                        |
    # Node /document
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                     |
      | contentStreamIdentifier       | "cs-identifier"                           |
      | nodeAggregateIdentifier       | "sir-david-nodenborough"                  |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {}                                        |
      | coveredDimensionSpacePoints   | [{}]                                      |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                  |
      | nodeName                      | "document"                                |
      | nodeAggregateClassification   | "tethered"                                |
    # Node /document/sub
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                        |
      | contentStreamIdentifier       | "cs-identifier"                              |
      | nodeAggregateIdentifier       | "subdoc"                                     |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:SubDocument" |
      | originDimensionSpacePoint     | {}                                           |
      | coveredDimensionSpacePoints   | [{}]                                         |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                     |
      | nodeName                      | "sub"                                        |
      | nodeAggregateClassification   | "regular"                                    |

    And the graph projection is fully up to date
    Then I expect no needed structure adjustments for type "Neos.ContentRepository:Root"
    Then I expect no needed structure adjustments for type "Neos.ContentRepository.Testing:Document"
    Then I expect no needed structure adjustments for type "Neos.ContentRepository.Testing:SubDocument"

    ########################
    # Actual Test
    ########################
    When I have the following additional NodeTypes configuration:
    """
    'Neos.ContentRepository:Root':
      childNodes:
        document:
          constraints:
            nodeTypes:
              'Neos.ContentRepository.Testing:SubDocument': false

    """

    Then I expect no needed structure adjustments for type "Neos.ContentRepository.Testing:Document"
    Then I expect no needed structure adjustments for type "Neos.ContentRepository.Testing:Document"
    Then I expect the following structure adjustments for type "Neos.ContentRepository.Testing:SubDocument":
      | Type                  | nodeAggregateIdentifier |
      | DISALLOWED_CHILD_NODE | subdoc                  |

    When I adjust the node structure for node type "Neos.ContentRepository.Testing:SubDocument"
    Then I expect no needed structure adjustments for type "Neos.ContentRepository.Testing:SubDocument"
    When I am in content stream "cs-identifier" and dimension space point {}
    And I expect node aggregate identifier "subdoc" to lead to no node

