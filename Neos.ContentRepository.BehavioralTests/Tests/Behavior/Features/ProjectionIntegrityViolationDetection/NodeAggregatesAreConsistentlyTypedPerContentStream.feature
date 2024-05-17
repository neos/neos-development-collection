@contentrepository @adapters=DoctrineDBAL
Feature: Run projection integrity violation detection regarding node aggregate type consistency

  As a user of the CR I want to be able to detect whether there are node aggregates of ambiguous type in a content stream

  Background:
    Given using the following content dimensions:
      | Identifier | Values  | Generalizations |
      | language   | de, gsw | gsw->de         |
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Document': []
    'Neos.ContentRepository.Testing:DocumentA': []
    'Neos.ContentRepository.Testing:DocumentB': []
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value                                  |
      | workspaceName              | "live"                                 |
      | workspaceTitle             | "Live"                                 |
      | workspaceDescription       | "The live workspace"                   |
      | newContentStreamId | "cs-identifier"                        |
    And I am in workspace "live" and dimension space point {"language":"de"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                         | Value                                     |
      | nodeAggregateId     | "lady-eleonode-rootford"                  |
      | nodeTypeName                | "Neos.ContentRepository:Root" |

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
    And I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 1 errors
    And I expect integrity violation detection result error number 1 to have code 1597747062
