@contentrepository
Feature: Run integrity violation detection regarding hierarchy relations and nodes

  As a user of the CR I want to know whether there are nodes or hierarchy relations with invalid hashes or parents / children

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
    And I am in workspace "live" and dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                         | Value                                                    |
      | nodeAggregateId             | "lady-eleonode-rootford"                                 |
      | nodeTypeName                | "Neos.ContentRepository:Root"                            |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                                    |
      | contentStreamId             | "cs-identifier"                                          |
      | nodeAggregateId             | "nody-mc-nodeface"                                       |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Document"                |
      | originDimensionSpacePoint   | {"language":"de"}                                        |
      | coveredDimensionSpacePoints | [{"language":"de"},{"language":"gsw"},{"language":"fr"}] |
      | parentNodeAggregateId       | "lady-eleonode-rootford"                                 |
      | nodeName                    | "child-document"                                         |
      | nodeAggregateClassification | "regular"                                                |
    And the graph projection is fully up to date

  Scenario: Detach a hierarchy relation from its parent
    When I add the following hierarchy relation:
      | Key                   | Value             |
      | contentStreamId       | "cs-identifier"   |
      | dimensionSpacePoint   | {"language":"de"} |
      | parentNodeAggregateId | "i-do-not-exist"  |
      | childNodeAggregateId  | "i-do-not-exist"  |
    And I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 1 error
    And I expect integrity violation detection result error number 1 to have code 1597909228

  Scenario: Change a hierarchy relation's dimension space point hash
    When I change the following hierarchy relation's dimension space point hash:
      | Key                        | Value                    |
      | contentStreamId            | "cs-identifier"          |
      | dimensionSpacePoint        | {"language":"gsw"}       |
      | parentNodeAggregateId      | "lady-eleonode-rootford" |
      | childNodeAggregateId       | "nody-mc-nodeface"       |
      | newDimensionSpacePointHash | "invalidhash"            |
    And I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 1 error
    And I expect integrity violation detection result error number 1 to have code 1597909228

  # Scenario: Change a node's origin dimension space point hash
  # This is rendundant since a node with invalid origin DSP hash will not cover its origin (see AllNodesCoverTheirOrigin)
  # or the covering relation's hash is also corrupted which is already detected by above test
