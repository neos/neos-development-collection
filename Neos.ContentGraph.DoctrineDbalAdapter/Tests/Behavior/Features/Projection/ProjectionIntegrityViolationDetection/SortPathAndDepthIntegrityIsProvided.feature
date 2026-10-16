Feature: Run integrity violation detection regarding sort paths and depths

  As a user of the CR I want to know whether there are hierarchy relations with a sort path or depth
  that is inconsistent with itself or with its parent hierarchy relation

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
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live" and dimension space point {"language":"de"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId        | nodeTypeName                            | parentNodeAggregateId  |
      | nody-mc-nodeface       | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford |
      | sir-david-nodenborough | Neos.ContentRepository.Testing:Document | nody-mc-nodeface       |
      | noderella-mc-nodeface  | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford |

  Scenario: Run integrity violation detection on an intact nested graph
    When I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 0 errors

  Scenario: Set a sort path which is not prefixed by the parent's sort path
    When I set the following sortPath:
      | Key                  | Value                   |
      | contentStreamId      | "cs-identifier"         |
      | dimensionSpacePoint  | {"language":"de"}       |
      | childNodeAggregateId | "noderella-mc-nodeface" |
      | newSortPath          | "a1/a0"                 |
    And I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 1 error
    And I expect integrity violation detection result error number 1 to have code 1597909228

  Scenario: Set a sort path whose depth is not the parent's depth plus one
    When I set the following sortPath:
      | Key                  | Value                   |
      | contentStreamId      | "cs-identifier"         |
      | dimensionSpacePoint  | {"language":"de"}       |
      | childNodeAggregateId | "noderella-mc-nodeface" |
      | newSortPath          | "a0/a1/a0"              |
    And I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 1 error
    And I expect integrity violation detection result error number 1 to have code 1597909228

  Scenario: Set a depth which is inconsistent with the number of sort path segments
    When I set the following sortPath:
      | Key                  | Value                   |
      | contentStreamId      | "cs-identifier"         |
      | dimensionSpacePoint  | {"language":"de"}       |
      | childNodeAggregateId | "noderella-mc-nodeface" |
      | newSortPath          | "a0/a1/a2"              |
      | newDepth             | 1                       |
    And I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 1 error
    And I expect integrity violation detection result error number 1 to have code 1597909228

  Scenario: Set an invalid sort path on a parent which also invalidates its child
    When I set the following sortPath:
      | Key                  | Value              |
      | contentStreamId      | "cs-identifier"    |
      | dimensionSpacePoint  | {"language":"de"}  |
      | childNodeAggregateId | "nody-mc-nodeface" |
      | newSortPath          | "a1/a0"            |
    And I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 2 errors
    And I expect integrity violation detection result error number 1 to have code 1597909228
    And I expect integrity violation detection result error number 2 to have code 1597909228
