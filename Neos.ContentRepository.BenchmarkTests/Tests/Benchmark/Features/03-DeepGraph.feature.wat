@contentrepository @adapters=DoctrineDBAL,Postgres
Feature: Run benchmark tests on a deep graph, i.e. 1 child per parent

  Background: The stage is set
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:Node': []
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | newContentStreamId   | "cs-identifier"      |
    And I am in workspace "live" and dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |

  Scenario: Create a deep graph of 11,111 nodes
    When I create descendants of node "lady-eleonode-rootford" of type "Neos.ContentRepository.Testing:Node" and depth 11110 and breadth 1

