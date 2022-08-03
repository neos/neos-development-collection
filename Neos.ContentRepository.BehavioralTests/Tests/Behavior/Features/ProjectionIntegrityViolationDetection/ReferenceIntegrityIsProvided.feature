@fixtures @adapters=DoctrineDBAL
Feature: Run integrity violation detection regarding reference relations

  As a user of the CR I want to know whether there are disconnected reference relations

  Background:
    Given I have the following content dimensions:
      | Identifier | Values      | Generalizations |
      | language   | de, gsw, fr | gsw->de         |
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
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                                    |
      | contentStreamIdentifier     | "cs-identifier"                                          |
      | nodeAggregateIdentifier     | "lady-eleonode-rootford"                                 |
      | nodeTypeName                | "Neos.ContentRepository:Root"                            |
      | coveredDimensionSpacePoints | [{"language":"de"},{"language":"gsw"},{"language":"fr"}] |
      | initiatingUserIdentifier    | "00000000-0000-0000-0000-000000000000"                   |
      | nodeAggregateClassification | "root"                                                   |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                     |
      | contentStreamIdentifier       | "cs-identifier"                           |
      | nodeAggregateIdentifier       | "source-nodandaise"                       |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {"language":"de"}                         |
      | coveredDimensionSpacePoints   | [{"language":"de"}]                       |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                  |
      | nodeAggregateClassification   | "regular"                                 |
    And the graph projection is fully up to date

  Scenario: Reference a non-existing node aggregate
    When the event NodeReferencesWereSet was published with payload:
      | Key                                      | Value                                                                      |
      | contentStreamIdentifier                  | "cs-identifier"                                                            |
      | sourceNodeAggregateIdentifier            | "source-nodandaise"                                                        |
      | affectedSourceOriginDimensionSpacePoints | [{"language":"de"}]                                                        |
      | referenceName                            | "referenceProperty"                                                        |
      | references                               | [{"targetNodeAggregateIdentifier":"anthony-destinode", "properties":null}] |
    And the graph projection is fully up to date
    And I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 1 error
    And I expect integrity violation detection result error number 1 to have code 1597919585

  Scenario: Reference a node aggregate not covering any of the DSPs the source does
    When the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                     |
      | contentStreamIdentifier       | "cs-identifier"                           |
      | nodeAggregateIdentifier       | "anthony-destinode"                       |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {"language":"fr"}                         |
      | coveredDimensionSpacePoints   | [{"language":"fr"}]                       |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                  |
      | nodeAggregateClassification   | "regular"                                 |
    And the event NodeReferencesWereSet was published with payload:
      | Key                                      | Value                                                                      |
      | contentStreamIdentifier                  | "cs-identifier"                                                            |
      | sourceNodeAggregateIdentifier            | "source-nodandaise"                                                        |
      | affectedSourceOriginDimensionSpacePoints | [{"language":"de"}]                                                        |
      | referenceName                            | "referenceProperty"                                                        |
      | references                               | [{"targetNodeAggregateIdentifier":"anthony-destinode", "properties":null}] |
    And the graph projection is fully up to date
    And I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 1 error
    And I expect integrity violation detection result error number 1 to have code 1597919585
