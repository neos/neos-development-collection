@fixtures
Feature: Run projection integrity violation detection regarding node aggregate type consistency

  As a user of the CR I want to be able to detect whether there are node aggregates of ambiguous type in a content stream

  Background:
    Given I have the following content dimensions:
      | Identifier | Default | Values  | Generalizations |
      | language   | de      | de, gsw | gsw->de         |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:DocumentA': []
    'Neos.ContentRepository.Testing:DocumentB': []
    """
    And the event RootWorkspaceWasCreated was published with payload:
      | Key                        | Value                                  |
      | workspaceName              | "live"                                 |
      | workspaceTitle             | "Live"                                 |
      | workspaceDescription       | "The live workspace"                   |
      | initiatingUserIdentifier   | "00000000-0000-0000-0000-000000000000" |
      | newContentStreamIdentifier | "cs-identifier"                        |
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                     |
      | contentStreamIdentifier     | "cs-identifier"                           |
      | nodeAggregateIdentifier     | "lady-eleonode-rootford"                  |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Document" |
      | coveredDimensionSpacePoints | [{"language":"de"},{"language":"gsw"}]    |
      | initiatingUserIdentifier    | "00000000-0000-0000-0000-000000000000"    |
      | nodeAggregateClassification | "root"                                    |
    And the graph projection is fully up to date

  Scenario: Create node variants of different type
    When the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                      |
      | contentStreamIdentifier       | "cs-identifier"                            |
      | nodeAggregateIdentifier       | "sir-david-nodenborough"                   |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:DocumentA" |
      | originDimensionSpacePoint     | {"language":"de"}                          |
      | coveredDimensionSpacePoints   | [{"language":"de"}]                        |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                   |
      | nodeName                      | "document"                                 |
      | nodeAggregateClassification   | "regular"                                  |
    When the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                      |
      | contentStreamIdentifier       | "cs-identifier"                            |
      | nodeAggregateIdentifier       | "sir-david-nodenborough"                   |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:DocumentB" |
      | originDimensionSpacePoint     | {"language":"gsw"}                         |
      | coveredDimensionSpacePoints   | [{"language":"gsw"}]                       |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                   |
      | nodeName                      | "document"                                 |
      | nodeAggregateClassification   | "regular"                                  |
    And the graph projection is fully up to date
    And I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 1 errors
    And I expect integrity violation detection result error number 1 to have code 1597747062
