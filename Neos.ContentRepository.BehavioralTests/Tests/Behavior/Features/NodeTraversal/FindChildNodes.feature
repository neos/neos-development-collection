@contentrepository @adapters=DoctrineDBAL,Postgres
Feature: Find nodes using the findChildNodes query

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
      | nodeAggregateId | nodeTypeName                               | parentNodeAggregateId  | initialPropertyValues | tetheredDescendantNodeAggregateIds       |
      | home            | Neos.ContentRepository.Testing:Homepage    | lady-eleonode-rootford | {}                    | {"terms": "terms", "contact": "contact"} |
      | a               | Neos.ContentRepository.Testing:Page        | home                   | {"text": "a"}         | {}                                       |
      | a1              | Neos.ContentRepository.Testing:Page        | a                      | {"text": "a1"}        | {}                                       |
      | a2              | Neos.ContentRepository.Testing:Page        | a                      | {"text": "a2"}        | {}                                       |
      | a2a             | Neos.ContentRepository.Testing:SpecialPage | a2                     | {"text": "a2a"}       | {}                                       |
      | a2a1            | Neos.ContentRepository.Testing:Page        | a2a                    | {"text": "a2a1"}      | {}                                       |
      | a2a2            | Neos.ContentRepository.Testing:Page        | a2a                    | {"text": "a2a2"}      | {}                                       |
      | b               | Neos.ContentRepository.Testing:Page        | home                   | {"text": "b"}         | {}                                       |
      | b1              | Neos.ContentRepository.Testing:Page        | b                      | {"text": "b1"}        | {}                                       |

  Scenario: Child nodes without filter
    When I execute the findChildNodes query for parent node aggregate id "home" I expect the nodes "terms,contact,a,b" to be returned
    When I execute the findChildNodes query for parent node aggregate id "a" I expect the nodes "a1,a2" to be returned
    When I execute the findChildNodes query for parent node aggregate id "a1" I expect no nodes to be returned

  Scenario: Child nodes filtered by node type
    When I execute the findChildNodes query for parent node aggregate id "home" and filter '{"nodeTypeConstraints": "Neos.ContentRepository.Testing:AbstractPage"}' I expect the nodes "terms,contact,a,b" to be returned
    When I execute the findChildNodes query for parent node aggregate id "home" and filter '{"nodeTypeConstraints": "Neos.ContentRepository.Testing:SomeMixin"}' I expect the nodes "contact" to be returned
    When I execute the findChildNodes query for parent node aggregate id "home" and filter '{"nodeTypeConstraints": "Neos.ContentRepository.Testing:SomeMixin"}' I expect the nodes "contact" to be returned
    When I execute the findChildNodes query for parent node aggregate id "home" and filter '{"nodeTypeConstraints": "Neos.ContentRepository.Testing:AbstractPage,!Neos.ContentRepository.Testing:SomeMixin"}' I expect the nodes "terms,a,b" to be returned
    When I execute the findChildNodes query for parent node aggregate id "home" and filter '{"nodeTypeConstraints": "Neos.ContentRepository.Testing:NonExistingNodeType"}' I expect no nodes to be returned

  Scenario: Child nodes paginated
    When I execute the findChildNodes query for parent node aggregate id "home" and filter '{"limit": 3}' I expect the nodes "terms,contact,a" to be returned
    When I execute the findChildNodes query for parent node aggregate id "home" and filter '{"limit": 2, "offset": 2}' I expect the nodes "a,b" to be returned
    When I execute the findChildNodes query for parent node aggregate id "home" and filter '{"offset": 1}' I expect the nodes "contact,a,b" to be returned

  Scenario: Child nodes filtered by node type, paginated
    When I execute the findChildNodes query for parent node aggregate id "home" and filter '{"nodeTypeConstraints": "Neos.ContentRepository.Testing:AbstractPage", "limit": 3}' I expect the nodes "terms,contact,a" to be returned
    When I execute the findChildNodes query for parent node aggregate id "home" and filter '{"nodeTypeConstraints": "Neos.ContentRepository.Testing:AbstractPage,!Neos.ContentRepository.Testing:SomeMixin", "limit": 2, "offset": 1}' I expect the nodes "a,b" to be returned
