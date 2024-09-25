@contentrepository @adapters=DoctrineDBAL
Feature: Run projection integrity violation detection regarding root connection

  As a user of the CR I want to be able to check whether there are nodes that are not connected to a root node.
  This is the first part of (a)cyclicality checks

  Background:
    Given using the following content dimensions:
      | Identifier | Values  | Generalizations |
      | language   | de, gsw | gsw->de         |
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Root':
      superTypes:
        'Neos.ContentRepository:Root': true
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

  Scenario: Create a cycle
    When the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                         | Value                                     |
      | workspaceName               | "live"                                    |
      | nodeAggregateId             | "lady-eleonode-rootford"                  |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Root"     |
      | coveredDimensionSpacePoints | [{"language":"de"},{"language":"gsw"}]    |
      | nodeAggregateClassification | "root"                                    |
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                         | Value                                     |
      | workspaceName               | "live"                                    |
      | nodeAggregateId             | "sir-david-nodenborough"                  |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint   | {"language":"de"}                         |
      | coveredDimensionSpacePoints | [{"language":"de"},{"language":"gsw"}]    |
      | parentNodeAggregateId       | "lady-eleonode-rootford"                  |
      | nodeName                    | "document"                                |
      | nodeAggregateClassification | "regular"                                 |
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                         | Value                                     |
      | workspaceName               | "live"                                    |
      | nodeAggregateId             | "nody-mc-nodeface"                        |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint   | {"language":"de"}                         |
      | coveredDimensionSpacePoints | [{"language":"de"},{"language":"gsw"}]    |
      | parentNodeAggregateId       | "sir-david-nodenborough"                  |
      | nodeName                    | "child-document"                          |
      | nodeAggregateClassification | "regular"                                 |

    When I change the following hierarchy relation's parent:
      | Key                        | Value                                      |
      | contentStreamId            | "cs-identifier"                            |
      | dimensionSpacePoint        | {"language":"de"}                          |
      | parentNodeAggregateId      | "lady-eleonode-rootford"                   |
      | childNodeAggregateId       | "sir-david-nodenborough"                   |
      | newParentNodeAggregateId   | "nody-mc-nodeface"                         |
    And I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 1 errors
    And I expect integrity violation detection result error number 1 to have code 1597754245

    # Another error. One error per subgraph
    When I change the following hierarchy relation's parent:
      | Key                        | Value                                       |
      | contentStreamId            | "cs-identifier"                             |
      | dimensionSpacePoint        | {"language":"gsw"}                          |
      | parentNodeAggregateId      | "lady-eleonode-rootford"                    |
      | childNodeAggregateId       | "sir-david-nodenborough"                    |
      | newParentNodeAggregateId   | "nody-mc-nodeface"                          |
    And I run integrity violation detection
    Then I expect the integrity violation detection result to contain exactly 2 errors
    And I expect integrity violation detection result error number 1 to have code 1597754245
    And I expect integrity violation detection result error number 2 to have code 1597754245
