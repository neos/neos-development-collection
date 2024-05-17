@contentrepository
Feature: Run integrity violation detection regarding subtree tag inheritance

  As a user of the CR I want to know whether there are nodes with subtree tags that are not inherited from its ancestors

  Background:
    Given using the following content dimensions:
      | Identifier | Values      | Generalizations |
      | language   | de, gsw, fr | gsw->de         |
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Document': []
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | workspaceTitle       | "Live"               |
      | workspaceDescription | "The live workspace" |
      | newContentStreamId   | "cs-identifier"      |
    And the graph projection is fully up to date
    And I am in workspace "live" and dimension space point {"language":"de"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                         | Value                                                    |
      | nodeAggregateId             | "lady-eleonode-rootford"                                 |
      | nodeTypeName                | "Neos.ContentRepository:Root"                            |
    And the graph projection is fully up to date

  Scenario: Create nodes, disable the topmost and remove some restriction edges manually
    When the event NodeAggregateWithNodeWasCreated was published with payload:
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
      | nodeAggregateId             | "sir-nodeward-nodington-iii"                             |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Document"                |
      | originDimensionSpacePoint   | {"language":"de"}                                        |
      | coveredDimensionSpacePoints | [{"language":"de"},{"language":"gsw"},{"language":"fr"}] |
      | parentNodeAggregateId       | "sir-david-nodenborough"                                 |
      | nodeName                    | "esquire"                                                |
      | nodeAggregateClassification | "regular"                                                |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                                    |
      | contentStreamId             | "cs-identifier"                                          |
      | nodeAggregateId             | "nody-mc-nodeface"                                       |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Document"                |
      | originDimensionSpacePoint   | {"language":"de"}                                        |
      | coveredDimensionSpacePoints | [{"language":"de"},{"language":"gsw"},{"language":"fr"}] |
      | parentNodeAggregateId       | "sir-nodeward-nodington-iii"                             |
      | nodeName                    | "child-document"                                         |
      | nodeAggregateClassification | "regular"                                                |
    And the event SubtreeWasTagged was published with payload:
      | Key                          | Value                                                    |
      | contentStreamId              | "cs-identifier"                                          |
      | nodeAggregateId              | "sir-david-nodenborough"                                 |
      | affectedDimensionSpacePoints | [{"language":"de"},{"language":"gsw"},{"language":"fr"}] |
      | tag                          | "disabled"                                               |
    And the graph projection is fully up to date
    And I remove the following subtree tag:
      | Key                   | Value                        |
      | contentStreamId       | "cs-identifier"              |
      | dimensionSpacePoint   | {"language":"de"}            |
      | parentNodeAggregateId | "sir-nodeward-nodington-iii" |
      | childNodeAggregateId  | "nody-mc-nodeface"           |
      | subtreeTag            | "disabled"                   |
    And I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 1 error
    And I expect integrity violation detection result error number 1 to have code 1597837797
