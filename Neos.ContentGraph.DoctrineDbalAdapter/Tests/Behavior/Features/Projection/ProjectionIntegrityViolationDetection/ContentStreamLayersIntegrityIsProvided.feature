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
      | newContentStreamId   | "cs-identifier"      |
    And I am in workspace "live" and dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |

  Scenario: Detach a hierarchy relation from its parent
    When the command CreateWorkspace is executed with payload:
      | Key                | Value                |
      | workspaceName      | "user-test"          |
      | baseWorkspaceName  | "live"               |
      | newContentStreamId | "user-cs-identifier" |

    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                     |
      | workspaceName             | "live"                                    |
      | originDimensionSpacePoint | {"language":"de"}                         |
      | nodeAggregateId           | "sir-david-nodenborough"                  |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Document" |
      | parentNodeAggregateId     | "lady-eleonode-rootford"                  |

    # Basically a DeleteWorkspace without the event ContentStreamWasRemoved as we avoid the internal cleanup
    When the content stream "user-cs-identifier" was removed without layer cleanup
    When the event WorkspaceWasRemoved was published with payload:
      | Key           | Value       |
      | workspaceName | "user-test" |

    And I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 1 error
    And I expect integrity violation detection result error number 1 to have code 1597909228 and message:
    """
    Redundant layer 1 to 2 found for content streams cs-identifier
    """
