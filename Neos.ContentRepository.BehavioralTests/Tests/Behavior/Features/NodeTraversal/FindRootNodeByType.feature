@contentrepository @adapters=DoctrineDBAL
Feature: Find root nodes by type

  Background:
    Given I have the following content dimensions:
      | Identifier | Values          | Generalizations      |
      | language   | mul, de, en, ch | ch->de->mul, en->mul |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository:AnotherRoot':
      superTypes:
        'Neos.ContentRepository:Root': true
    'Neos.ContentRepository:UnusedRoot':
      superTypes:
        'Neos.ContentRepository:Root': true
    """
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | workspaceTitle       | "Live"               |
      | workspaceDescription | "The live workspace" |
      | newContentStreamId   | "cs-identifier"      |
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and dimension space point {"language":"de"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                                 |
      | nodeAggregateId | "lady-eleonode-rootfords-evil-sister" |
      | nodeTypeName    | "Neos.ContentRepository:AnotherRoot"  |
    And the graph projection is fully up to date

  Scenario:
    When I execute the findRootNodeByType query for node type "Neos.ContentRepository:Root" I expect the node "lady-eleonode-rootford" to be returned
    When I execute the findRootNodeByType query for node type "Neos.ContentRepository:AnotherRoot" I expect the node "lady-eleonode-rootfords-evil-sister" to be returned
    When I execute the findRootNodeByType query for node type "Neos.ContentRepository:UnusedRoot" I expect no node to be returned
