@fixtures @adapters=DoctrineDBAL
Feature: Run projection integrity violation detection regarding root connection

  As a user of the CR I want to be able to check whether there are nodes that are not connected to a root node.
  This is the first part of (a)cyclicality checks

  Background:
    Given I have the following content dimensions:
      | Identifier | Values  | Generalizations |
      | language   | de, gsw | gsw->de         |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:Document': []
    """
    And the event RootWorkspaceWasCreated was published with payload:
      | Key                        | Value                                  |
      | workspaceName              | "live"                                 |
      | workspaceTitle             | "Live"                                 |
      | workspaceDescription       | "The live workspace"                   |
      | initiatingUserIdentifier   | "00000000-0000-0000-0000-000000000000" |
      | newContentStreamIdentifier | "cs-identifier"                        |
    And the graph projection is fully up to date

  Scenario: Create a cycle
    When the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                     |
      | contentStreamIdentifier     | "cs-identifier"                           |
      | nodeAggregateIdentifier     | "lady-eleonode-rootford"                  |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Document" |
      | coveredDimensionSpacePoints | [{"language":"de"},{"language":"gsw"}]    |
      | initiatingUserIdentifier    | "00000000-0000-0000-0000-000000000000"    |
      | nodeAggregateClassification | "root"                                    |
    When the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                     |
      | contentStreamIdentifier       | "cs-identifier"                           |
      | nodeAggregateIdentifier       | "sir-david-nodenborough"                  |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {"language":"de"}                         |
      | coveredDimensionSpacePoints   | [{"language":"de"},{"language":"gsw"}]    |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                  |
      | nodeName                      | "document"                                |
      | nodeAggregateClassification   | "regular"                                 |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                     |
      | contentStreamIdentifier       | "cs-identifier"                           |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                        |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {"language":"de"}                         |
      | coveredDimensionSpacePoints   | [{"language":"de"},{"language":"gsw"}]    |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                  |
      | nodeName                      | "child-document"                          |
      | nodeAggregateClassification   | "regular"                                 |
    And the graph projection is fully up to date
    And the event NodeAggregateWasMoved was published with payload:
      | Key                               | Value                                                                                                                                                                                                                                                                                                                                                                               |
      | contentStreamIdentifier           | "cs-identifier"                                                                                                                                                                                                                                                                                                                                                                     |
      | nodeAggregateIdentifier           | "sir-david-nodenborough"                                                                                                                                                                                                                                                                                                                                                            |
      | nodeMoveMappings                  | [{"movedNodeOrigin": {"language":"de"}, "newParentAssignments": {"1041cc1fe1030c1a82ac24346f8c69a7": {"nodeAggregateIdentifier": "nody-mc-nodeface", "originDimensionSpacePoint": {"language":"de"}}, "67b30a9436c8470107f1b237a14dc638": {"nodeAggregateIdentifier": "nody-mc-nodeface", "originDimensionSpacePoint": {"language":"de"}}}, "newSucceedingSiblingAssignments": []}] |
      | repositionNodesWithoutAssignments | []                                                                                                                                                                                                                                                                                                                                                                                  |
    And the graph projection is fully up to date
    And I run integrity violation detection
    # one error per subgraph
    Then I expect the integrity violation detection result to contain exactly 2 errors
    And I expect integrity violation detection result error number 1 to have code 1597754245
    And I expect integrity violation detection result error number 2 to have code 1597754245
