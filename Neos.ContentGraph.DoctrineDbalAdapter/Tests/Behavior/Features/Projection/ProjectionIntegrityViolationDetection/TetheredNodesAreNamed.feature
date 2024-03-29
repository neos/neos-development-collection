@contentrepository @adapters=DoctrineDBAL
Feature: Run projection integrity violation detection regarding naming of tethered nodes

  As a user of the CR I want to be able to detect whether there are unnamed tethered events

  Background:
    Given using the following content dimensions:
      | Identifier | Values  | Generalizations |
      | language   | de, gsw | gsw->de         |
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Document': []
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value                                  |
      | workspaceName              | "live"                                 |
      | workspaceTitle             | "Live"                                 |
      | workspaceDescription       | "The live workspace"                   |
      | newContentStreamId | "cs-identifier"                        |
    And the graph projection is fully up to date
    And I am in the active content stream of workspace "live" and dimension space point {"language":"de"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                         | Value                                  |
      | nodeAggregateId     | "lady-eleonode-rootford"               |
      | nodeTypeName                | "Neos.ContentRepository:Root"          |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                     |
      | contentStreamId       | "cs-identifier"                           |
      | nodeAggregateId       | "sir-david-nodenborough"                  |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {"language":"de"}                         |
      | coveredDimensionSpacePoints   | [{"language":"de"}]                       |
      | parentNodeAggregateId | "lady-eleonode-rootford"                  |
      | nodeName                      | "document"                                |
      | nodeAggregateClassification   | "regular"                                 |
    And the graph projection is fully up to date

  Scenario: Create node variants of different type
    When the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                     |
      | contentStreamId       | "cs-identifier"                           |
      | nodeAggregateId       | "nodewyn-tetherton"                       |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {"language":"de"}                         |
      | coveredDimensionSpacePoints   | [{"language":"de"}]                       |
      | parentNodeAggregateId | "sir-david-nodenborough"                  |
      | nodeName                      | "to-be-hacked-to-null"                    |
      | nodeAggregateClassification   | "tethered"                                |
    And the graph projection is fully up to date
    And I change the following hierarchy relation's name:
      | Key                        | Value                    |
      | contentStreamId            | "cs-identifier"          |
      | dimensionSpacePoint        | {"language":"de"}        |
      | parentNodeAggregateId      | "sir-david-nodenborough" |
      | childNodeAggregateId       | "nodewyn-tetherton"      |
      | newName                    | null                     |
    And I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 1 errors
    And I expect integrity violation detection result error number 1 to have code 1597923103
