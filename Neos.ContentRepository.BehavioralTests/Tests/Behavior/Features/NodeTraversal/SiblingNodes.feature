@contentrepository @adapters=DoctrineDBAL
Feature: Find sibling nodes using the findPrecedingSiblingNodes and findSucceedingSiblingNodes queries

  Background:
    Given using the following content dimensions:
      | Identifier | Values          | Generalizations      |
      | language   | mul, de, en, ch | ch->de->mul, en->mul |
    And using the following node types:
    """yaml
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
      | a2a3            | a2a3     | Neos.ContentRepository.Testing:Page        | a2a                    | {"text": "a2a3"}      | {}                                       |
      | a3              | a3       | Neos.ContentRepository.Testing:Page        | a                      | {"text": "a3"}        | {}                                       |
      | a4              | a4       | Neos.ContentRepository.Testing:SpecialPage | a                      | {"text": "a4"}        | {}                                       |
      | a5              | a5       | Neos.ContentRepository.Testing:Page        | a                      | {"text": "a5"}        | {}                                       |
      | a6              | a6       | Neos.ContentRepository.Testing:Page        | a                      | {"text": "a6"}        | {}                                       |
      | b               | b        | Neos.ContentRepository.Testing:Page        | home                   | {"text": "b"}         | {}                                       |
      | b1              | b1       | Neos.ContentRepository.Testing:Page        | b                      | {"text": "b1"}        | {}                                       |
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value         |
      | nodeAggregateId              | "a2a2"        |
      | nodeVariantSelectionStrategy | "allVariants" |
    And the graph projection is fully up to date

  Scenario:
    # findPrecedingSiblingNodes queries without result
    When I execute the findPrecedingSiblingNodes query for sibling node aggregate id "non-existing" I expect no nodes to be returned
    When I execute the findPrecedingSiblingNodes query for sibling node aggregate id "a6" and filter '{"nodeTypes": "Neos.ContentRepository.Testing:NonExisting"}' I expect no nodes to be returned
    When I execute the findPrecedingSiblingNodes query for sibling node aggregate id "a6" and filter '{"searchTerm": "non-existing"}' I expect no nodes to be returned
    When I execute the findPrecedingSiblingNodes query for sibling node aggregate id "a6" and filter '{"propertyValue": "text=\"non-existing\""}' I expect no nodes to be returned

    # findPrecedingSiblingNodes queries with result
    When I execute the findPrecedingSiblingNodes query for sibling node aggregate id "a2a3" I expect the nodes "a2a1" to be returned
    When I execute the findPrecedingSiblingNodes query for sibling node aggregate id "a6" I expect the nodes "a5,a4,a3,a2,a1" to be returned
    When I execute the findPrecedingSiblingNodes query for sibling node aggregate id "a6" and filter '{"nodeTypes": "Neos.ContentRepository.Testing:Page"}' I expect the nodes "a5,a3,a2,a1" to be returned
    When I execute the findPrecedingSiblingNodes query for sibling node aggregate id "a6" and filter '{"nodeTypes": "!Neos.ContentRepository.Testing:Page"}' I expect the nodes "a4" to be returned
    When I execute the findPrecedingSiblingNodes query for sibling node aggregate id "a6" and filter '{"pagination": {"limit": 3, "offset": 1}}' I expect the nodes "a4,a3,a2" to be returned
    When I execute the findPrecedingSiblingNodes query for sibling node aggregate id "a6" and filter '{"nodeTypes": "Neos.ContentRepository.Testing:Page,Neos.ContentRepository.Testing:NonExisting", "pagination": {"limit": 4, "offset": 2}}' I expect the nodes "a2,a1" to be returned
    When I execute the findPrecedingSiblingNodes query for sibling node aggregate id "a6" and filter '{"propertyValue": "text > \"a3\""}' I expect the nodes "a5,a4" to be returned

    # findSucceedingSiblingNodes queries without results
    When I execute the findSucceedingSiblingNodes query for sibling node aggregate id "non-existing" I expect no nodes to be returned
    When I execute the findSucceedingSiblingNodes query for sibling node aggregate id "a1" and filter '{"nodeTypes": "Neos.ContentRepository.Testing:NonExisting"}' I expect no nodes to be returned
    When I execute the findSucceedingSiblingNodes query for sibling node aggregate id "a1" and filter '{"searchTerm": "non-existing"}' I expect no nodes to be returned
    When I execute the findSucceedingSiblingNodes query for sibling node aggregate id "a1" and filter '{"propertyValue": "text=\"non-existing\""}' I expect no nodes to be returned

    # findSucceedingSiblingNodes queries with results
    When I execute the findSucceedingSiblingNodes query for sibling node aggregate id "a2a1" I expect the nodes "a2a3" to be returned
    When I execute the findSucceedingSiblingNodes query for sibling node aggregate id "a1" I expect the nodes "a2,a3,a4,a5,a6" to be returned
    When I execute the findSucceedingSiblingNodes query for sibling node aggregate id "a1" and filter '{"nodeTypes": "Neos.ContentRepository.Testing:Page"}' I expect the nodes "a2,a3,a5,a6" to be returned
    When I execute the findSucceedingSiblingNodes query for sibling node aggregate id "a1" and filter '{"nodeTypes": "!Neos.ContentRepository.Testing:Page"}' I expect the nodes "a4" to be returned
    When I execute the findSucceedingSiblingNodes query for sibling node aggregate id "a1" and filter '{"pagination": {"limit": 3, "offset": 1}}' I expect the nodes "a3,a4,a5" to be returned
    When I execute the findSucceedingSiblingNodes query for sibling node aggregate id "a1" and filter '{"nodeTypes": "Neos.ContentRepository.Testing:Page,Neos.ContentRepository.Testing:NonExisting", "pagination": {"limit": 4, "offset": 2}}' I expect the nodes "a5,a6" to be returned
    When I execute the findSucceedingSiblingNodes query for sibling node aggregate id "a2a1" and filter '{"nodeTypes": "!Neos.ContentRepository.Testing:NonExisting"}' I expect the nodes "a2a3" to be returned
    When I execute the findSucceedingSiblingNodes query for sibling node aggregate id "a1" and filter '{"propertyValue": "text < \"a4\""}' I expect the nodes "a2,a3" to be returned
