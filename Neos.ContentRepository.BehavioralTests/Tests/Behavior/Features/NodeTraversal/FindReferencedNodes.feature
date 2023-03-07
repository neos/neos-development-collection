@contentrepository @adapters=DoctrineDBAL,Postgres
Feature: Find nodes using the findReferencedNodes query

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
    And the command SetNodeReferences is executed with payload:
      | Key                   | Value                                |
      | sourceNodeAggregateId | "a"                                  |
      | referenceName         | "refs"                               |
      | references            | [{"target":"b1"}, {"target":"a2a2"}] |
    And the command SetNodeReferences is executed with payload:
      | Key                   | Value            |
      | sourceNodeAggregateId | "b1"             |
      | referenceName         | "ref"            |
      | references            | [{"target":"a"}] |
    And the command SetNodeReferences is executed with payload:
      | Key                   | Value                                                                                            |
      | sourceNodeAggregateId | "b"                                                                                              |
      | referenceName         | "refs"                                                                                           |
      | references            | [{"target":"a2", "properties": {"foo": "bar"}}, {"target":"a2a1", "properties": {"foo": "baz"}}] |
    And the command SetNodeReferences is executed with payload:
      | Key                   | Value                                           |
      | sourceNodeAggregateId | "a"                                             |
      | referenceName         | "ref"                                           |
      | references            | [{"target":"b1", "properties": {"foo": "bar"}}] |
    And the graph projection is fully up to date

  Scenario: findReferencedNodes queries without results
    When I execute the findReferencedNodes query for node aggregate id "a" and filter '{"referenceName": "non-existing"}' I expect no references to be returned
    When I execute the findReferencedNodes query for node aggregate id "non-existing" I expect no references to be returned

  Scenario: findReferencedNodes queries with results
    When I execute the findReferencedNodes query for node aggregate id "a" I expect the references '[{"nodeAggregateId": "b1", "name": "ref", "properties": {"foo": {"value": "bar", "type": "string"}}}, {"nodeAggregateId": "b1", "name": "refs", "properties": null}, {"nodeAggregateId": "a2a2", "name": "refs", "properties": null}]' to be returned
    When I execute the findReferencedNodes query for node aggregate id "a" and filter '{"referenceName": "ref"}' I expect the references '[{"nodeAggregateId": "b1", "name": "ref", "properties": {"foo": {"value": "bar", "type": "string"}}}]' to be returned
