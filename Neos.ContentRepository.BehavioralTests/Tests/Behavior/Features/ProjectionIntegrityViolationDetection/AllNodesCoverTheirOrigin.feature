@contentrepository @adapters=DoctrineDBAL
Feature: Run projection integrity violation detection to find nodes that do not cover their origin dimension space point

  As a user of the CR I want to be able to detect whether there are nodes that are disconnected from the subgraph they originate in

  Background:
    Given I have the following content dimensions:
      | Identifier | Values  | Generalizations |
      | language   | de, gsw | gsw->de         |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:Document': []
    """
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value                                  |
      | workspaceName              | "live"                                 |
      | workspaceTitle             | "Live"                                 |
      | workspaceDescription       | "The live workspace"                   |
      | initiatingUserId   | "00000000-0000-0000-0000-000000000000" |
      | newContentStreamId | "cs-identifier"                        |
    And the graph projection is fully up to date
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                  |
      | contentStreamId     | "cs-identifier"                        |
      | nodeAggregateId     | "lady-eleonode-rootford"               |
      | nodeTypeName                | "Neos.ContentRepository:Root"          |
      | coveredDimensionSpacePoints | [{"language":"de"},{"language":"gsw"}] |
      | initiatingUserId    | "00000000-0000-0000-0000-000000000000" |
      | nodeAggregateClassification | "root"                                 |
    And the graph projection is fully up to date

  Scenario: Create a node not covering its origin
    When the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                     |
      | contentStreamId       | "cs-identifier"                           |
      | nodeAggregateId       | "sir-david-nodenborough"                  |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {"language":"de"}                         |
      | coveredDimensionSpacePoints   | [{"language":"de"},{"language":"gsw"}]    |
      | parentNodeAggregateId | "lady-eleonode-rootford"                  |
      | nodeName                      | "document"                                |
      | nodeAggregateClassification   | "regular"                                 |
    When the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                     |
      | contentStreamId       | "cs-identifier"                           |
      | nodeAggregateId       | "nody-mc-nodeface"                        |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {"language":"de"}                         |
      | coveredDimensionSpacePoints   | [{"language":"gsw"}]                      |
      | parentNodeAggregateId | "sir-david-nodenborough"                  |
      | nodeName                      | "document"                                |
      | nodeAggregateClassification   | "regular"                                 |
    And the graph projection is fully up to date
    And I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 1 errors
    And I expect integrity violation detection result error number 1 to have code 1597828607
