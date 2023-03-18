@contentrepository @adapters=DoctrineDBAL
 # TODO implement for Postgres
Feature: Count nodes using the countChildNodes query

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
      | a2a3            | Neos.ContentRepository.Testing:Page        | a2a                    | {"text": "a2a3"}      | {}                                       |
      | b               | Neos.ContentRepository.Testing:Page        | home                   | {"text": "b"}         | {}                                       |
      | b1              | Neos.ContentRepository.Testing:Page        | b                      | {"text": "b1"}        | {}                                       |
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value              |
      | nodeAggregateId              | "a2a3"             |
      | nodeVariantSelectionStrategy | "allVariants"      |
    And the graph projection is fully up to date

  Scenario: Child nodes without filter
    When I execute the countChildNodes query for parent node aggregate id "home" I expect the result 4
    When I execute the countChildNodes query for parent node aggregate id "a" I expect the result 2
    When I execute the countChildNodes query for parent node aggregate id "a1" I expect the result 0
    When I execute the countChildNodes query for parent node aggregate id "a2a" I expect the result 2

  Scenario: Child nodes filtered by node type
    When I execute the countChildNodes query for parent node aggregate id "home" and filter '{"nodeTypeConstraints": "Neos.ContentRepository.Testing:AbstractPage"}' I expect the result 4
    When I execute the countChildNodes query for parent node aggregate id "home" and filter '{"nodeTypeConstraints": "Neos.ContentRepository.Testing:SomeMixin"}' I expect the result 1
    When I execute the countChildNodes query for parent node aggregate id "home" and filter '{"nodeTypeConstraints": "Neos.ContentRepository.Testing:SomeMixin"}' I expect the result 1
    When I execute the countChildNodes query for parent node aggregate id "home" and filter '{"nodeTypeConstraints": "Neos.ContentRepository.Testing:AbstractPage,!Neos.ContentRepository.Testing:SomeMixin"}' I expect the result 3
    When I execute the countChildNodes query for parent node aggregate id "home" and filter '{"nodeTypeConstraints": "Neos.ContentRepository.Testing:NonExistingNodeType"}' I expect the result 0
