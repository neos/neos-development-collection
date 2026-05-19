Feature: Run integrity violation detection regarding parent relations

  As a user of the CR I want to know whether there are nodes that have multiple parents per subgraph

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
    And I am in workspace "live" and dimension space point {"language":"de"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                         | Value                                                    |
      | workspaceName               | "live"                                                   |
      | nodeAggregateId             | "sir-david-nodenborough"                                 |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Document"                |
      | originDimensionSpacePoint   | {"language":"de"}                                        |
      | parentNodeAggregateId       | "lady-eleonode-rootford"                                 |
      | nodeName                    | "document"                                               |
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                         | Value                                                    |
      | workspaceName               | "live"                                                   |
      | nodeAggregateId             | "sir-nodeward-nodington-iii"                             |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Document"                |
      | originDimensionSpacePoint   | {"language":"de"}                                        |
      | parentNodeAggregateId       | "lady-eleonode-rootford"                                 |
      | nodeName                    | "esquire"                                                |
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                         | Value                                                    |
      | workspaceName               | "live"                                                   |
      | nodeAggregateId             | "nody-mc-nodeface"                                       |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Document"                |
      | originDimensionSpacePoint   | {"language":"de"}                                        |
      | parentNodeAggregateId       | "sir-david-nodenborough"                                 |
      | nodeName                    | "child-document"                                         |

  Scenario: Set a second parent for Nody McNodeface
    And I add the following hierarchy relation:
      | Key                   | Value                        |
      | contentStreamId       | "cs-identifier"              |
      | dimensionSpacePoint   | {"language":"de"}            |
      | parentNodeAggregateId | "sir-nodeward-nodington-iii" |
      | childNodeAggregateId  | "nody-mc-nodeface"           |
    And I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 1 error
    And I expect integrity violation detection result error number 1 to have code 1597925698
