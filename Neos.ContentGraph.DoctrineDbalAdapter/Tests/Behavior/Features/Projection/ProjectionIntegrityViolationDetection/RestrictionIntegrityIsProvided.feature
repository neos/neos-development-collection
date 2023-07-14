@contentrepository
Feature: Run integrity violation detection regarding restriction relations

  As a user of the CR I want to know whether there are nodes with restriction relations missing from their ancestors

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
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | workspaceTitle       | "Live"               |
      | workspaceDescription | "The live workspace" |
      | newContentStreamId   | "cs-identifier"      |
    And the graph projection is fully up to date
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                         | Value                                                    |
      | contentStreamId             | "cs-identifier"                                          |
      | nodeAggregateId             | "lady-eleonode-rootford"                                 |
      | nodeTypeName                | "Neos.ContentRepository:Root"                            |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                                    |
      | contentStreamId             | "cs-identifier"                                          |
      | nodeAggregateId             | "sir-david-nodenborough"                                 |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Document"                |
      | originDimensionSpacePoint   | {"language":"de"}                                        |
      | coveredDimensionSpacePoints | [{"language":"de"},{"language":"gsw"},{"language":"fr"}] |
      | parentNodeAggregateId       | "lady-eleonode-rootford"                                 |
      | nodeName                    | "document"                                               |
      | nodeAggregateClassification | "regular"                                                |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                                    |
      | contentStreamId             | "cs-identifier"                                          |
      | nodeAggregateId             | "nody-mc-nodeface"                                       |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Document"                |
      | originDimensionSpacePoint   | {"language":"de"}                                        |
      | coveredDimensionSpacePoints | [{"language":"de"},{"language":"gsw"},{"language":"fr"}] |
      | parentNodeAggregateId       | "sir-david-nodenborough"                                 |
      | nodeName                    | "child-document"                                         |
      | nodeAggregateClassification | "regular"                                                |
    And the event NodeAggregateWasDisabled was published with payload:
      | Key                          | Value                                                    |
      | contentStreamId              | "cs-identifier"                                          |
      | nodeAggregateId              | "sir-david-nodenborough"                                 |
      | affectedDimensionSpacePoints | [{"language":"de"},{"language":"gsw"},{"language":"fr"}] |
    And the graph projection is fully up to date

  Scenario: Detach a restriction relation from its origin
    When I detach the following restriction relation from its origin:
      | Key                     | Value                    |
      | contentStreamId         | "cs-identifier"          |
      | dimensionSpacePoint     | {"language":"de"}        |
      | originNodeAggregateId   | "sir-david-nodenborough" |
      | affectedNodeAggregateId | "nody-mc-nodeface"       |
    And I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 1 error
    And I expect integrity violation detection result error number 1 to have code 1597846598

  Scenario: Detach a restriction relation from its target
    When I detach the following restriction relation from its target:
      | Key                     | Value                    |
      | contentStreamId         | "cs-identifier"          |
      | dimensionSpacePoint     | {"language":"de"}        |
      | originNodeAggregateId   | "sir-david-nodenborough" |
      | affectedNodeAggregateId | "sir-david-nodenborough" |
    And I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 1 error
    And I expect integrity violation detection result error number 1 to have code 1597846598
