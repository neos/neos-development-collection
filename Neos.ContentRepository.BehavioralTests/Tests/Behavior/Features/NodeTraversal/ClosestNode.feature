@contentrepository @adapters=DoctrineDBAL
  # TODO implement for Postgres
Feature: Find nodes using the findClosestNode query

  Background:
    Given using the following content dimensions:
      | Identifier | Values          | Generalizations      |
      | language   | mul, de, en, ch | ch->de->mul, en->mul |
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:AbstractPage':
      abstract: true
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
      | newContentStreamId   | "cs-identifier"      |
    And I am in workspace "live" and dimension space point {"language":"de"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | nodeName | nodeTypeName                               | parentNodeAggregateId  | initialPropertyValues | tetheredDescendantNodeAggregateIds       |
      | home            | home     | Neos.ContentRepository.Testing:Homepage    | lady-eleonode-rootford | {}                    | {"terms": "terms", "contact": "contact"} |
      | a               | a        | Neos.ContentRepository.Testing:Page        | home                   | {}                    | {}                                       |
      | a1              | a1       | Neos.ContentRepository.Testing:Page        | a                      | {}                    | {}                                       |
      | a2              | a2       | Neos.ContentRepository.Testing:Page        | a                      | {}                    | {}                                       |
      | a2a             | a2a      | Neos.ContentRepository.Testing:SpecialPage | a2                     | {}                    | {}                                       |
      | a2a1            | a2a1     | Neos.ContentRepository.Testing:Page        | a2a                    | {}                    | {}                                       |
      | a2a2            | a2a2     | Neos.ContentRepository.Testing:Page        | a2a                    | {}                    | {}                                       |
      | a2a2a           | a2a2a    | Neos.ContentRepository.Testing:Page        | a2a2                   | {}                    | {}                                       |
      | a2a2b           | a2a2b    | Neos.ContentRepository.Testing:Page        | a2a2                   | {}                    | {}                                       |
      | a2b             | a2b      | Neos.ContentRepository.Testing:Page        | a2                     | {}                    | {}                                       |
      | a2b1            | a2b1     | Neos.ContentRepository.Testing:Page        | a2b                    | {}                    | {}                                       |
      | b               | b        | Neos.ContentRepository.Testing:Page        | home                   | {}                    | {}                                       |
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value         |
      | nodeAggregateId              | "a2a2a"       |
      | nodeVariantSelectionStrategy | "allVariants" |
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value         |
      | nodeAggregateId              | "a2b"         |
      | nodeVariantSelectionStrategy | "allVariants" |

  Scenario:
    # findClosestNode queries without results
#    When I execute the findClosestNode query for entry node aggregate id "non-existing" and filter '{"nodeTypes": "Neos.ContentRepository.Testing:Page"}' I expect no node to be returned
#    # a2a2a is disabled
#    When I execute the findClosestNode query for entry node aggregate id "a2a2a" and filter '{"nodeTypes": "Neos.ContentRepository.Testing:Page"}' I expect no node to be returned
#    # a2b is disabled
#    When I execute the findClosestNode query for entry node aggregate id "a2b1" and filter '{"nodeTypes": "Neos.ContentRepository.Testing:Page"}' I expect no node to be returned

    # findClosestNode queries with results
    When I execute the findClosestNode query for entry node aggregate id "a2a2b" and filter '{"nodeTypes": "Neos.ContentRepository.Testing:Page"}' I expect the node "a2a2b" to be returned
    When I execute the findClosestNode query for entry node aggregate id "a2a2b" and filter '{"nodeTypes": "Neos.ContentRepository.Testing:SpecialPage"}' I expect the node "a2a" to be returned
    When I execute the findClosestNode query for entry node aggregate id "a2a2b" and filter '{"nodeTypes": "!Neos.ContentRepository.Testing:Page,!Neos.ContentRepository.Testing:SpecialPage"}' I expect the node "home" to be returned
    When I execute the findClosestNode query for entry node aggregate id "a2a" and filter '{"nodeTypes": "Neos.ContentRepository.Testing:SpecialPage"}' I expect the node "a2a" to be returned
