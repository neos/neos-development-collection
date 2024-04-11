@contentrepository @adapters=DoctrineDBAL,Postgres
Feature: Find nodes using the findNodeByPath query

  Background:
    Given using the following content dimensions:
      | Identifier | Values          | Generalizations      |
      | language   | mul, de, en, ch | ch->de->mul, en->mul |
    And using the following node types:
    """yaml
    'Neos.ContentRepository:AnotherRoot':
      superTypes:
        'Neos.ContentRepository:Root': true
    'Neos.ContentRepository.Testing:AbstractPage':
      abstract: true
      properties:
        text:
          type: string
      references:
        refs:
          properties:
            foo:
              type: string
        ref:
          constraints:
            maxItems: 1
          properties:
            foo:
              type: string
    'Neos.ContentRepository.Testing:SomeMixin':
      abstract: true
    'Neos.ContentRepository.Testing:Homepage':
      superTypes:
        'Neos.ContentRepository.Testing:AbstractPage': true
      childNodes:
        terms:
          type: 'Neos.ContentRepository.Testing:Terms'
        contact:
          type: 'Neos.ContentRepository.Testing:Contact'

    'Neos.ContentRepository.Testing:Terms':
      superTypes:
        'Neos.ContentRepository.Testing:AbstractPage': true
      properties:
        text:
          defaultValue: 'Terms default'
    'Neos.ContentRepository.Testing:Contact':
      superTypes:
        'Neos.ContentRepository.Testing:AbstractPage': true
        'Neos.ContentRepository.Testing:SomeMixin': true
      properties:
        text:
          defaultValue: 'Contact default'
    'Neos.ContentRepository.Testing:Page':
      superTypes:
        'Neos.ContentRepository.Testing:AbstractPage': true
    'Neos.ContentRepository.Testing:SpecialPage':
      superTypes:
        'Neos.ContentRepository.Testing:AbstractPage': true
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
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | nodeName | nodeTypeName                               | parentNodeAggregateId  | initialPropertyValues | tetheredDescendantNodeAggregateIds       |
      | home            | home     | Neos.ContentRepository.Testing:Homepage    | lady-eleonode-rootford | {}                    | {"terms": "terms", "contact": "contact"} |
      | a               | a        | Neos.ContentRepository.Testing:Page        | home                   | {"text": "a"}         | {}                                       |
      | a1              | a1       | Neos.ContentRepository.Testing:Page        | a                      | {"text": "a1"}        | {}                                       |
      | a2              | a2       | Neos.ContentRepository.Testing:Page        | a                      | {"text": "a2"}        | {}                                       |
      | a2a             | a2a      | Neos.ContentRepository.Testing:SpecialPage | a2                     | {"text": "a2a"}       | {}                                       |
      | a2a1            | a2a1     | Neos.ContentRepository.Testing:Page        | a2a                    | {"text": "a2a1"}      | {}                                       |
      | a2a2            | a2a2     | Neos.ContentRepository.Testing:Page        | a2a                    | {"text": "a2a2"}      | {}                                       |
      | a2a2a           | a2a2a    | Neos.ContentRepository.Testing:Page        | a2a2                   | {"text": "a2a2a"}     | {}                                       |
      | b               | b        | Neos.ContentRepository.Testing:Page        | home                   | {"text": "b"}         | {}                                       |
      | b1              | b1       | Neos.ContentRepository.Testing:Page        | b                      | {"text": "b1"}        | {}                                       |
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value         |
      | nodeAggregateId              | "a2a2"        |
      | nodeVariantSelectionStrategy | "allVariants" |

  Scenario:
    # absolute paths without result
    When I execute the findNodeByAbsolutePath query for path "/<Neos.ContentRepository:AnotherRoot>" I expect no node to be returned
    When I execute the findNodeByAbsolutePath query for path "/<Neos.ContentRepository:AnotherRoot>/non-existing" I expect no node to be returned
    # node "a2a2" is disabled and should not lead to a result
    When I execute the findNodeByAbsolutePath query for path "/<Neos.ContentRepository:AnotherRoot>/home/a/a2/a2a/a2a2" I expect no node to be returned
    When I execute the findNodeByAbsolutePath query for path "/<Neos.ContentRepository:AnotherRoot>/home/a/a2/a2a/a2a2/a2a2a" I expect no node to be returned

    # absolute paths  with result
    When I execute the findNodeByAbsolutePath query for path "/<Neos.ContentRepository:Root>" I expect the node "lady-eleonode-rootford" to be returned
    When I execute the findNodeByAbsolutePath query for path "/<Neos.ContentRepository:Root>/home" I expect the node "home" to be returned
    When I execute the findNodeByAbsolutePath query for path "/<Neos.ContentRepository:Root>/home/a" I expect the node "a" to be returned
    When I execute the findNodeByAbsolutePath query for path "/<Neos.ContentRepository:Root>/home/a/a1" I expect the node "a1" to be returned
    When I execute the findNodeByAbsolutePath query for path "/<Neos.ContentRepository:Root>/home/a/a2/a2a/a2a1" I expect the node "a2a1" to be returned

    # relative paths without result
    When I execute the findNodeByPath query for path "non-existing" and starting node aggregate id "non-existing" I expect no node to be returned
    When I execute the findNodeByPath query for path "home" and starting node aggregate id "non-existing" I expect no node to be returned
    When I execute the findNodeByPath query for path "a/a1/a2a" and starting node aggregate id "home" I expect no node to be returned
    # node "a2a2" is disabled and should not lead to a result
    When I execute the findNodeByPath query for path "a/a2/a2a/a2a2" and starting node aggregate id "home" I expect no node to be returned
    When I execute the findNodeByPath query for path "a/a2/a2a/a2a2/a2a2a" and starting node aggregate id "home" I expect no node to be returned

    # relative paths with result
    When I execute the findNodeByPath query for path "/home" and starting node aggregate id "lady-eleonode-rootford" I expect the node "home" to be returned
    When I execute the findNodeByPath query for path "a" and starting node aggregate id "home" I expect the node "a" to be returned
    When I execute the findNodeByPath query for path "/a/a1" and starting node aggregate id "home" I expect the node "a1" to be returned
    When I execute the findNodeByPath query for path "a/a2/a2a/a2a1" and starting node aggregate id "home" I expect the node "a2a1" to be returned
