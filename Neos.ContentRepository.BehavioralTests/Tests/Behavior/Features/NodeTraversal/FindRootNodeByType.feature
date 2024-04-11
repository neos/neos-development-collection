@contentrepository @adapters=DoctrineDBAL,Postgres
Feature: Find root nodes by type

  Background:
    Given using the following content dimensions:
      | Identifier | Values          | Generalizations      |
      | language   | mul, de, en, ch | ch->de->mul, en->mul |
    And using the following node types:
    """yaml
    'Neos.ContentRepository:AnotherRoot':
      superTypes:
        'Neos.ContentRepository:Root': true
    'Neos.ContentRepository:UnusedRoot':
      superTypes:
        'Neos.ContentRepository:Root': true
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | workspaceTitle       | "Live"               |
      | workspaceDescription | "The live workspace" |
      | newContentStreamId   | "cs-identifier"      |
    And I am in the active content stream of workspace "live" and dimension space point {"language":"de"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                                 |
      | nodeAggregateId | "lady-eleonode-rootfords-evil-sister" |
      | nodeTypeName    | "Neos.ContentRepository:AnotherRoot"  |

  Scenario:
    When I execute the findRootNodeByType query for node type "Neos.ContentRepository:Root" I expect the node "lady-eleonode-rootford" to be returned
    When I execute the findRootNodeByType query for node type "Neos.ContentRepository:AnotherRoot" I expect the node "lady-eleonode-rootfords-evil-sister" to be returned
    When I execute the findRootNodeByType query for node type "Neos.ContentRepository:UnusedRoot" I expect no node to be returned
