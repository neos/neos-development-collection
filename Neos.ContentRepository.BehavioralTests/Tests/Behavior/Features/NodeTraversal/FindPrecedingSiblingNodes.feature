@contentrepository @adapters=DoctrineDBAL,Postgres
Feature: Find nodes using the findPrecedingSiblingNodes query

  Background:
    Given I have the following content dimensions:
      | Identifier | Values          | Generalizations      |
      | language   | mul, de, en, ch | ch->de->mul, en->mul |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:AbstractPage':
      abstract: true
      properties:
        text:
          type: string
        refs:
          type: references
          properties:
            foo:
              type: string
        ref:
          type: reference
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
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | nodeName | nodeTypeName                               | parentNodeAggregateId  | initialPropertyValues | tetheredDescendantNodeAggregateIds       |
      | home            | home     | Neos.ContentRepository.Testing:Homepage    | lady-eleonode-rootford | {}                    | {"terms": "terms", "contact": "contact"} |
      | a               | a        | Neos.ContentRepository.Testing:Page        | home                   | {"text": "a"}         | {}                                       |
      | a1              | a1       | Neos.ContentRepository.Testing:Page        | a                      | {"text": "a1"}        | {}                                       |
      | a2              | a2       | Neos.ContentRepository.Testing:Page        | a                      | {"text": "a2"}        | {}                                       |
      | a2a             | a2a      | Neos.ContentRepository.Testing:SpecialPage | a2                     | {"text": "a2a"}       | {}                                       |
      | a2a1            | a2a1     | Neos.ContentRepository.Testing:Page        | a2a                    | {"text": "a2a1"}      | {}                                       |
      | a2a2            | a2a2     | Neos.ContentRepository.Testing:Page        | a2a                    | {"text": "a2a2"}      | {}                                       |
      | a3              | a3       | Neos.ContentRepository.Testing:Page        | a                      | {"text": "a3"}        | {}                                       |
      | a4              | a4       | Neos.ContentRepository.Testing:SpecialPage | a                      | {"text": "a4"}        | {}                                       |
      | a5              | a5       | Neos.ContentRepository.Testing:Page        | a                      | {"text": "a5"}        | {}                                       |
      | a6              | a6       | Neos.ContentRepository.Testing:Page        | a                      | {"text": "a6"}        | {}                                       |
      | b               | b        | Neos.ContentRepository.Testing:Page        | home                   | {"text": "b"}         | {}                                       |
      | b1              | b1       | Neos.ContentRepository.Testing:Page        | b                      | {"text": "b1"}        | {}                                       |
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value         |
      | nodeAggregateId              | "a2a1"        |
      | nodeVariantSelectionStrategy | "allVariants" |
    And the graph projection is fully up to date

  Scenario: findPrecedingSiblingNodes queries without result
    When I execute the findPrecedingSiblingNodes query for sibling node aggregate id "non-existing" I expect no nodes to be returned

  Scenario: findPrecedingSiblingNodes queries with result
    When I execute the findPrecedingSiblingNodes query for sibling node aggregate id "a6" and filter '{"nodeTypeConstraints": "Neos.ContentRepository.Testing:NonExisting"}' I expect no nodes to be returned
    When I execute the findPrecedingSiblingNodes query for sibling node aggregate id "a6" I expect the nodes "a5,a4,a3,a2,a1" to be returned
    When I execute the findPrecedingSiblingNodes query for sibling node aggregate id "a6" and filter '{"nodeTypeConstraints": "Neos.ContentRepository.Testing:Page"}' I expect the nodes "a5,a3,a2,a1" to be returned
    When I execute the findPrecedingSiblingNodes query for sibling node aggregate id "a6" and filter '{"nodeTypeConstraints": "!Neos.ContentRepository.Testing:Page"}' I expect the nodes "a4" to be returned
    When I execute the findPrecedingSiblingNodes query for sibling node aggregate id "a6" and filter '{"pagination": {"limit": 3, "offset": 1}}' I expect the nodes "a4,a3,a2" to be returned
    When I execute the findPrecedingSiblingNodes query for sibling node aggregate id "a6" and filter '{"nodeTypeConstraints": "Neos.ContentRepository.Testing:Page,Neos.ContentRepository.Testing:NonExisting", "pagination": {"limit": 4, "offset": 2}}' I expect the nodes "a2,a1" to be returned
