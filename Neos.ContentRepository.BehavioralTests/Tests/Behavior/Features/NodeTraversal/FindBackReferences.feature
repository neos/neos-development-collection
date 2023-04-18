@contentrepository @adapters=DoctrineDBAL,Postgres
Feature: Find nodes using the findBackReferences query

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
      | c               | c        | Neos.ContentRepository.Testing:Page        | home                   | {"text": "c"}         | {}                                       |
      | a               | a        | Neos.ContentRepository.Testing:Page        | home                   | {"text": "a"}         | {}                                       |
      | a1              | a1       | Neos.ContentRepository.Testing:Page        | a                      | {"text": "a1"}        | {}                                       |
      | a2              | a2       | Neos.ContentRepository.Testing:Page        | a                      | {"text": "a2"}        | {}                                       |
      | a2a             | a2a      | Neos.ContentRepository.Testing:SpecialPage | a2                     | {"text": "a2a"}       | {}                                       |
      | a2a1            | a2a1     | Neos.ContentRepository.Testing:Page        | a2a                    | {"text": "a2a1"}      | {}                                       |
      | a2a2            | a2a2     | Neos.ContentRepository.Testing:Page        | a2a                    | {"text": "a2a2"}      | {}                                       |
      | a2a3            | a2a3     | Neos.ContentRepository.Testing:Page        | a2a                    | {"text": "a2a3"}      | {}                                       |
      | a3              | a3       | Neos.ContentRepository.Testing:Page        | a                      | {"text": "a3"}        | {}                                       |
      | b               | b        | Neos.ContentRepository.Testing:Page        | home                   | {"text": "b"}         | {}                                       |
      | b1              | b1       | Neos.ContentRepository.Testing:Page        | b                      | {"text": "b1"}        | {}                                       |
    And the command SetNodeReferences is executed with payload:
      | Key                   | Value                                            |
      | sourceNodeAggregateId | "c"                                              |
      | referenceName         | "refs"                                           |
      | references            | [{"target":"b1", "properties": {"foo": "foos"}}] |
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
      | references            | [{"target":"a3", "properties": {"foo": "bar"}}, {"target":"a2a1", "properties": {"foo": "baz"}}] |
    And the command SetNodeReferences is executed with payload:
      | Key                   | Value                                                            |
      | sourceNodeAggregateId | "a"                                                              |
      | referenceName         | "refs"                                                           |
      | references            | [{"target":"b1", "properties": {"foo": "bar"}}, {"target": "b"}] |
    And the command SetNodeReferences is executed with payload:
      | Key                   | Value               |
      | sourceNodeAggregateId | "a2"                |
      | referenceName         | "ref"               |
      | references            | [{"target":"a2a3"}] |
    And the command SetNodeReferences is executed with payload:
      | Key                   | Value             |
      | sourceNodeAggregateId | "a2a3"            |
      | referenceName         | "ref"             |
      | references            | [{"target":"a2"}] |
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value         |
      | nodeAggregateId              | "a2a3"        |
      | nodeVariantSelectionStrategy | "allVariants" |
    And the graph projection is fully up to date

  Scenario: findBackReferences queries without results
    When I execute the findBackReferences query for node aggregate id "non-existing" I expect no references to be returned
    When I execute the findBackReferences query for node aggregate id "home" I expect no references to be returned
    # "a2" is references node "a2a3" but "a2a3" is disabled so this reference should be ignored
    When I execute the findBackReferences query for node aggregate id "a2" I expect no references to be returned
    # "a2a3" is references node "a2" but "a2a3" is disabled so this reference should be ignored
    When I execute the findBackReferences query for node aggregate id "a2a3" I expect no references to be returned
    When I execute the findBackReferences query for node aggregate id "a" and filter '{"referenceName": "non-existing"}' I expect no references to be returned

  Scenario: findBackReferences queries with results
    When I execute the findBackReferences query for node aggregate id "a" I expect the references '[{"nodeAggregateId": "b1", "name": "ref", "properties": null}]' to be returned
    When I execute the findBackReferences query for node aggregate id "a3" and filter '{"referenceName": "refs"}' I expect the references '[{"nodeAggregateId": "b", "name": "refs", "properties": {"foo": {"value": "bar", "type": "string"}}}]' to be returned
    When I execute the findBackReferences query for node aggregate id "b1" I expect the references '[{"nodeAggregateId": "a", "name": "refs", "properties": {"foo": {"value": "bar", "type": "string"}}}, {"nodeAggregateId": "c", "name": "refs", "properties": {"foo": {"value": "foos", "type": "string"}}}]' to be returned
