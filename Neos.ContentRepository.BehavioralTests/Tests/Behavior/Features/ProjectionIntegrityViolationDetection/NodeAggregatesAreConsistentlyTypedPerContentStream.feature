@contentrepository @adapters=DoctrineDBAL
Feature: Run projection integrity violation detection regarding node aggregate type consistency

  As a user of the CR I want to be able to detect whether there are node aggregates of ambiguous type in a content stream

  Background:
    Given I have the following content dimensions:
      | Identifier | Values  | Generalizations |
      | language   | de, gsw | gsw->de         |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:Document': []
    'Neos.ContentRepository.Testing:DocumentA': []
    'Neos.ContentRepository.Testing:DocumentB': []
    """
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value                                  |
      | workspaceName              | "live"                                 |
      | workspaceTitle             | "Live"                                 |
      | workspaceDescription       | "The live workspace"                   |
      | newContentStreamId | "cs-identifier"                        |
    And the graph projection is fully up to date
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                     |
      | contentStreamId     | "cs-identifier"                           |
      | nodeAggregateId     | "lady-eleonode-rootford"                  |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Document" |
      | coveredDimensionSpacePoints | [{"language":"de"},{"language":"gsw"}]    |
      | nodeAggregateClassification | "root"                                    |
    And the graph projection is fully up to date

  Scenario: Create node variants of different type
    When the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                      |
      | contentStreamId       | "cs-identifier"                            |
      | nodeAggregateId       | "sir-david-nodenborough"                   |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:DocumentA" |
      | originDimensionSpacePoint     | {"language":"de"}                          |
      | coveredDimensionSpacePoints   | [{"language":"de"}]                        |
      | parentNodeAggregateId | "lady-eleonode-rootford"                   |
      | nodeName                      | "document"                                 |
      | nodeAggregateClassification   | "regular"                                  |
    When the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                      |
      | contentStreamId       | "cs-identifier"                            |
      | nodeAggregateId       | "sir-david-nodenborough"                   |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:DocumentB" |
      | originDimensionSpacePoint     | {"language":"gsw"}                         |
      | coveredDimensionSpacePoints   | [{"language":"gsw"}]                       |
      | parentNodeAggregateId | "lady-eleonode-rootford"                   |
      | nodeName                      | "document"                                 |
      | nodeAggregateClassification   | "regular"                                  |
    And the graph projection is fully up to date
    And I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 1 errors
    And I expect integrity violation detection result error number 1 to have code 1597747062
