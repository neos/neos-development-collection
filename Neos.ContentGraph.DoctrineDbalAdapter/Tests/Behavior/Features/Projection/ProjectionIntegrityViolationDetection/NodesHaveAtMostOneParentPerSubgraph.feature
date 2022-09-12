@contentrepository
Feature: Run integrity violation detection regarding parent relations

  As a user of the CR I want to know whether there are nodes that have multiple parents per subgraph

  Background:
    Given I have the following content dimensions:
      | Identifier | Values      | Generalizations |
      | language   | de, gsw, fr | gsw->de         |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:Document': []
    """
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                                  |
      | workspaceName        | "live"                                 |
      | workspaceTitle       | "Live"                                 |
      | workspaceDescription | "The live workspace"                   |
      | initiatingUserId     | "00000000-0000-0000-0000-000000000000" |
      | newContentStreamId   | "cs-identifier"                        |
    And the graph projection is fully up to date
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                                    |
      | contentStreamId             | "cs-identifier"                                          |
      | nodeAggregateId             | "lady-eleonode-rootford"                                 |
      | nodeTypeName                | "Neos.ContentRepository:Root"                            |
      | coveredDimensionSpacePoints | [{"language":"de"},{"language":"gsw"},{"language":"fr"}] |
      | initiatingUserId            | "00000000-0000-0000-0000-000000000000"                   |
      | nodeAggregateClassification | "root"                                                   |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                                    |
      | contentStreamId             | "cs-identifier"                                          |
      | nodeAggregateId             | "sir-david-nodenborough"                                 |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Document"                |
      | originDimensionSpacePoint   | {"language":"de"}                                        |
      | coveredDimensionSpacePoints | [{"language":"de"},{"language":"gsw"},{"language":"fr"}] |
      | parentnodeAggregateId       | "lady-eleonode-rootford"                                 |
      | nodeName                    | "document"                                               |
      | nodeAggregateClassification | "regular"                                                |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                                    |
      | contentStreamId             | "cs-identifier"                                          |
      | nodeAggregateId             | "sir-nodeward-nodington-iii"                             |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Document"                |
      | originDimensionSpacePoint   | {"language":"de"}                                        |
      | coveredDimensionSpacePoints | [{"language":"de"},{"language":"gsw"},{"language":"fr"}] |
      | parentnodeAggregateId       | "lady-eleonode-rootford"                                 |
      | nodeName                    | "esquire"                                                |
      | nodeAggregateClassification | "regular"                                                |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                                    |
      | contentStreamId             | "cs-identifier"                                          |
      | nodeAggregateId             | "nody-mc-nodeface"                                       |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Document"                |
      | originDimensionSpacePoint   | {"language":"de"}                                        |
      | coveredDimensionSpacePoints | [{"language":"de"},{"language":"gsw"},{"language":"fr"}] |
      | parentnodeAggregateId       | "sir-david-nodenborough"                                 |
      | nodeName                    | "child-document"                                         |
      | nodeAggregateClassification | "regular"                                                |
    And the graph projection is fully up to date

  Scenario: Set a second parent for Nody McNodeface
    And I add the following hierarchy relation:
      | Key                   | Value                        |
      | contentStreamId       | "cs-identifier"              |
      | dimensionSpacePoint   | {"language":"de"}            |
      | parentnodeAggregateId | "sir-nodeward-nodington-iii" |
      | childnodeAggregateId  | "nody-mc-nodeface"           |
    And I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 1 error
    And I expect integrity violation detection result error number 1 to have code 1597925698
