Feature: Find and count references and their target nodes using the findReferences, findBackReferences, countReferences and countBackReferences queries

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
        'stringProperty':
          type: string
        booleanProperty:
          type: boolean
        floatProperty:
          type: float
        integerProperty:
          type: integer
        dateProperty:
          type: DateTime
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
    And using the following reference types:
    """yaml
    'Neos.ContentRepository:Featureless': []
    'Neos.ContentRepository:Reference':
      final: true
      superTypes:
        'Neos.ContentRepository:IntermediateReference': true
        'Neos.ContentRepository:BaseReference': true
    'Neos.ContentRepository:AnotherReference':
      final: true
      superTypes:
        'Neos.ContentRepository:BaseReference': true
    'Neos.ContentRepository:BaseReference':
      abstract: true
      properties:
        foo:
          type: string
    'Neos.ContentRepository:AnotherBaseReference':
      abstract: true
    'Neos.ContentRepository:IntermediateReference':
      abstract: true
      superTypeNames:
        'Neos.ContentRepository:AnotherBaseReference': true
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
      | nodeAggregateId | nodeTypeName                               | parentNodeAggregateId  | initialPropertyValues | tetheredDescendantNodeAggregateIds       |
      | home            | Neos.ContentRepository.Testing:Homepage    | lady-eleonode-rootford | {}                    | {"terms": "terms", "contact": "contact"} |
      | c               | Neos.ContentRepository.Testing:SpecialPage | home                   | {"text": "c"}         | {}                                       |
      | a               | Neos.ContentRepository.Testing:Page        | home                   | {"text": "a"}         | {}                                       |
      | a1              | Neos.ContentRepository.Testing:Page        | a                      | {"text": "a1"}        | {}                                       |
      | a2              | Neos.ContentRepository.Testing:Page        | a                      | {"text": "a2"}        | {}                                       |
      | a2a             | Neos.ContentRepository.Testing:SpecialPage | a2                     | {"text": "a2a"}       | {}                                       |
      | a2a1            | Neos.ContentRepository.Testing:Page        | a2a                    | {"text": "a2a1"}      | {}                                       |
      | a2a2            | Neos.ContentRepository.Testing:Page        | a2a                    | {"text": "a2a2"}      | {}                                       |
      | a2a3            | Neos.ContentRepository.Testing:Page        | a2a                    | {"text": "a2a3"}      | {}                                       |
      | a3              | Neos.ContentRepository.Testing:Page        | a                      | {"text": "a3"}        | {}                                       |
      | b               | Neos.ContentRepository.Testing:Page        | home                   | {"text": "b"}         | {}                                       |
      | b1              | Neos.ContentRepository.Testing:SpecialPage | b                      | {"text": "b1"}        | {}                                       |
    And the command SetNodeReferences is executed with payload:
      | Key                   | Value                                |
      | sourceNodeAggregateId | "a"                                  |
      | references            | [{"referenceName": "refs", "references": [{"target":"b1", "referenceTypeName": "Neos.ContentRepository:Featureless"}, {"target":"a2a2", "referenceTypeName": "Neos.ContentRepository:Featureless"}]}] |
    And the command SetNodeReferences is executed with payload:
      | Key                   | Value            |
      | sourceNodeAggregateId | "b1"             |
      | references            | [{"referenceName": "ref", "references": [{"target":"a", "referenceTypeName": "Neos.ContentRepository:Featureless"}]}] |
    And the command SetNodeReferences is executed with payload:
      | Key                   | Value                                                                                            |
      | sourceNodeAggregateId | "b"                                                                                              |
      | references            | [{"referenceName": "refs", "references": [{"target":"a2", "referenceTypeName": "Neos.ContentRepository:Reference", "properties": {"foo": "bar"}}, {"target":"a2a1", "referenceTypeName": "Neos.ContentRepository:AnotherReference", "properties": {"foo": "baz"}}]}] |
    And the command SetNodeReferences is executed with payload:
      | Key                   | Value                                           |
      | sourceNodeAggregateId | "a"                                             |
      | references            | [{"referenceName": "ref", "references": [{"target":"b1", "referenceTypeName": "Neos.ContentRepository:Reference", "properties": {"foo": "bar"}}]}] |
    And the command SetNodeReferences is executed with payload:
      | Key                   | Value               |
      | sourceNodeAggregateId | "a2"                |
      | references            | [{"referenceName": "ref", "references": [{"target":"a2a3", "referenceTypeName": "Neos.ContentRepository:Featureless"}]}] |
    And the command SetNodeReferences is executed with payload:
      | Key                   | Value             |
      | sourceNodeAggregateId | "a2a3"            |
      | references            | [{"referenceName": "ref", "references": [{"target":"a2", "referenceTypeName": "Neos.ContentRepository:AnotherReference"}]}] |
    And the command SetNodeReferences is executed with payload:
      | Key                   | Value                                           |
      | sourceNodeAggregateId | "b"                                             |
      | references            | [{"referenceName": "refs", "references": [{"target":"a3", "referenceTypeName": "Neos.ContentRepository:AnotherReference", "properties": {"foo": "bar"}}]}] |
    And the command SetNodeReferences is executed with payload:
      | Key                   | Value                                            |
      | sourceNodeAggregateId | "c"                                              |
      | references            | [{"referenceName": "refs", "references": [{"target":"b1", "referenceTypeName": "Neos.ContentRepository:AnotherReference", "properties": {"foo": "foos"}}, {"target": "b", "referenceTypeName": "Neos.ContentRepository:Featureless", "properties": {}}]}] |
    And the command SetNodeReferences is executed with payload:
      | Key                   | Value            |
      | sourceNodeAggregateId | "c"              |
      | references            | [{"referenceName": "ref", "references": [{"target":"b", "referenceTypeName": "Neos.ContentRepository:Reference"}]}] |
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value         |
      | nodeAggregateId              | "a2a3"        |
      | nodeVariantSelectionStrategy | "allVariants" |
    And I restrict the visibility of nodes tagged "disabled" in subgraph queries

  Scenario: Check consistency of findReferences results
    # findReferences queries without results
    When I execute the findReferences query for node aggregate id "non-existing" I expect no references to be returned
    When I execute the findReferences query for node aggregate id "c" and filter '{"nodeTypes": "Neos.ContentRepository.Testing:NonExisting"}' I expect no references to be returned
    When I execute the findReferences query for node aggregate id "a" and filter '{"nodeSearchTerm": "non-existing"}' I expect no references to be returned
    When I execute the findReferences query for node aggregate id "a" and filter '{"nodePropertyValue": "text = \"non-existing\""}' I expect no references to be returned
    When I execute the findReferences query for node aggregate id "a" and filter '{"referenceSearchTerm": "non-existing"}' I expect no references to be returned
    When I execute the findReferences query for node aggregate id "a" and filter '{"referencePropertyValue": "text = \"non-existing\""}' I expect no references to be returned
    When I execute the findReferences query for node aggregate id "a" and filter '{"referenceName": "non-existing"}' I expect no references to be returned
    When I execute the findReferences query for node aggregate id "a" and filter '{"referenceTypes": "I.Do:NotExist"}' I expect no references to be returned
    When I execute the findReferences query for node aggregate id "a" and filter '{"referenceTypes": "Neos.ContentRepository:AnotherReference"}' I expect no references to be returned
    When I execute the findReferences query for node aggregate id "home" I expect no references to be returned
    # "a2" is referenced by "a2a3" but "a2a3" is disabled so this reference should be ignored
    When I execute the findReferences query for node aggregate id "a2" I expect no references to be returned
    # "a2a3" is referenced by "a2" but "a2a3" is disabled so this reference should be ignored
    When I execute the findReferences query for node aggregate id "a2a3" I expect no references to be returned
    # findReferences queries with results
    When I execute the findReferences query for node aggregate id "a" I expect the references '[{"nodeAggregateId": "b1", "name": "ref", "referenceTypeName": "Neos.ContentRepository:Reference", "properties": {"foo": {"value": "bar", "type": "string"}}}, {"nodeAggregateId": "b1", "name": "refs", "referenceTypeName": "Neos.ContentRepository:Featureless", "properties": null}, {"nodeAggregateId": "a2a2", "name": "refs", "referenceTypeName": "Neos.ContentRepository:Featureless", "properties": null}]' to be returned
    When I execute the findReferences query for node aggregate id "a" and filter '{"referenceName": "ref"}' I expect the references '[{"nodeAggregateId": "b1", "name": "ref", "referenceTypeName": "Neos.ContentRepository:Reference", "properties": {"foo": {"value": "bar", "type": "string"}}}]' to be returned
    When I execute the findReferences query for node aggregate id "a" and filter '{"referenceTypes": "Neos.ContentRepository:Featureless"}' I expect the references '[{"nodeAggregateId": "b1", "name": "refs", "referenceTypeName": "Neos.ContentRepository:Featureless", "properties": null}, {"nodeAggregateId": "a2a2", "name": "refs", "referenceTypeName": "Neos.ContentRepository:Featureless", "properties": null}]' to be returned
    When I execute the findReferences query for node aggregate id "c" I expect the references '[{"nodeAggregateId": "b", "name": "ref", "referenceTypeName": "Neos.ContentRepository:Reference", "properties": null}, {"nodeAggregateId": "b1", "name": "refs", "referenceTypeName": "Neos.ContentRepository:AnotherReference", "properties": {"foo": {"value": "foos", "type": "string"}}}, {"nodeAggregateId": "b", "name": "refs", "referenceTypeName": "Neos.ContentRepository:Featureless", "properties": null}]' to be returned
    When I execute the findReferences query for node aggregate id "c" and filter '{"nodeTypes": "Neos.ContentRepository.Testing:SpecialPage"}' I expect the references '[{"nodeAggregateId": "b1", "name": "refs", "referenceTypeName": "Neos.ContentRepository:AnotherReference", "properties": {"foo": {"value": "foos", "type": "string"}}}]' to be returned
    When I execute the findReferences query for node aggregate id "c" and filter '{"referenceTypes": "Neos.ContentRepository:BaseReference"}' I expect the references '[{"nodeAggregateId": "b", "name": "ref", "referenceTypeName": "Neos.ContentRepository:Reference", "properties": null}, {"nodeAggregateId": "b1", "name": "refs", "referenceTypeName": "Neos.ContentRepository:AnotherReference", "properties": {"foo": {"value": "foos", "type": "string"}}}]' to be returned
    When I execute the findReferences query for node aggregate id "c" and filter '{"pagination": {"limit": 1, "offset": 1}}' I expect the references '[{"nodeAggregateId": "b1", "name": "refs", "referenceTypeName": "Neos.ContentRepository:AnotherReference", "properties": {"foo": {"value": "foos", "type": "string"}}}]' to be returned and the total count to be 3
    When I execute the findReferences query for node aggregate id "a" and filter '{"nodeSearchTerm": "b1"}' I expect the references '[{"nodeAggregateId": "b1", "name": "ref", "referenceTypeName": "Neos.ContentRepository:Reference", "properties": {"foo": {"value": "bar", "type": "string"}}}, {"nodeAggregateId": "b1", "name": "refs", "referenceTypeName": "Neos.ContentRepository:Featureless", "properties": null}]' to be returned
    When I execute the findReferences query for node aggregate id "a" and filter '{"nodePropertyValue": "text = \"b1\""}' I expect the references '[{"nodeAggregateId": "b1", "name": "ref", "referenceTypeName": "Neos.ContentRepository:Reference", "properties": {"foo": {"value": "bar", "type": "string"}}}, {"nodeAggregateId": "b1", "name": "refs", "referenceTypeName": "Neos.ContentRepository:Featureless", "properties": null}]' to be returned
    When I execute the findReferences query for node aggregate id "a" and filter '{"referenceSearchTerm": "a"}' I expect the references '[{"nodeAggregateId": "b1", "name": "ref", "referenceTypeName": "Neos.ContentRepository:Reference", "properties": {"foo": {"value": "bar", "type": "string"}}}]' to be returned
    When I execute the findReferences query for node aggregate id "a" and filter '{"referencePropertyValue": "foo = \"bar\""}' I expect the references '[{"nodeAggregateId": "b1", "name": "ref", "referenceTypeName": "Neos.ContentRepository:Reference", "properties": {"foo": {"value": "bar", "type": "string"}}}]' to be returned
    When I execute the findReferences query for node aggregate id "a" and filter '{"ordering": [{"type": "propertyName", "field": "text", "direction": "ASCENDING"}]}' I expect the references '[{"nodeAggregateId": "a2a2", "name": "refs", "referenceTypeName": "Neos.ContentRepository:Featureless", "properties": null}, {"nodeAggregateId": "b1", "name": "ref", "referenceTypeName": "Neos.ContentRepository:Reference", "properties": {"foo": {"value": "bar", "type": "string"}}}, {"nodeAggregateId": "b1", "name": "refs", "referenceTypeName": "Neos.ContentRepository:Featureless", "properties": null}]' to be returned

    # findBackReferences queries without results
    When I execute the findBackReferences query for node aggregate id "non-existing" I expect no references to be returned
    When I execute the findBackReferences query for node aggregate id "b1" and filter '{"nodeTypes": "Neos.ContentRepository.Testing:NonExisting"}' I expect no references to be returned
    When I execute the findBackReferences query for node aggregate id "b1" and filter '{"nodeSearchTerm": "non-existing"}' I expect no references to be returned
    When I execute the findBackReferences query for node aggregate id "b1" and filter '{"nodePropertyValue": "text = \"non-existing\""}' I expect no references to be returned
    When I execute the findBackReferences query for node aggregate id "b1" and filter '{"referenceSearchTerm": "non-existing"}' I expect no references to be returned
    When I execute the findBackReferences query for node aggregate id "b1" and filter '{"referencePropertyValue": "text = \"non-existing\""}' I expect no references to be returned
    When I execute the findBackReferences query for node aggregate id "b1" and filter '{"referenceName": "non-existing"}' I expect no references to be returned
    When I execute the findBackReferences query for node aggregate id "b1" and filter '{"referenceTypes": "I.Do:NotExist"}' I expect no references to be returned
    When I execute the findBackReferences query for node aggregate id "b" and filter '{"referenceTypes": "Neos.ContentRepository:AnotherReference"}' I expect no references to be returned
    When I execute the findBackReferences query for node aggregate id "home" I expect no references to be returned
    # "a2" is references node "a2a3" but "a2a3" is disabled so this reference should be ignored
    When I execute the findBackReferences query for node aggregate id "a1" I expect no references to be returned
    # "a2a3" is references node "a2" but "a2a3" is disabled so this reference should be ignored
    When I execute the findBackReferences query for node aggregate id "a2a3" I expect no references to be returned
    When I execute the findBackReferences query for node aggregate id "a" and filter '{"referenceName": "non-existing"}' I expect no references to be returned
    # findBackReferences queries with results
    When I execute the findBackReferences query for node aggregate id "a" I expect the references '[{"nodeAggregateId": "b1", "name": "ref", "referenceTypeName": "Neos.ContentRepository:Featureless", "properties": null}]' to be returned
    When I execute the findBackReferences query for node aggregate id "a3" and filter '{"referenceName": "refs"}' I expect the references '[{"nodeAggregateId": "b", "name": "refs", "referenceTypeName": "Neos.ContentRepository:AnotherReference", "properties": {"foo": {"value": "bar", "type": "string"}}}]' to be returned
    When I execute the findBackReferences query for node aggregate id "b1" I expect the references '[{"nodeAggregateId": "a", "name": "ref", "referenceTypeName": "Neos.ContentRepository:Reference", "properties": {"foo": {"value": "bar", "type": "string"}}}, {"nodeAggregateId": "a", "name": "refs", "referenceTypeName": "Neos.ContentRepository:Featureless", "properties": null}, {"nodeAggregateId": "c", "name": "refs", "referenceTypeName": "Neos.ContentRepository:AnotherReference", "properties": {"foo": {"value": "foos", "type": "string"}}}]' to be returned
    When I execute the findBackReferences query for node aggregate id "b1" and filter '{"referenceTypes": "Neos.ContentRepository:Featureless"}' I expect the references '[{"nodeAggregateId": "a", "name": "refs", "referenceTypeName": "Neos.ContentRepository:Featureless", "properties": null}]' to be returned
    When I execute the findBackReferences query for node aggregate id "b1" and filter '{"referenceTypes": "Neos.ContentRepository:BaseReference"}' I expect the references '[{"nodeAggregateId": "a", "name": "ref", "referenceTypeName": "Neos.ContentRepository:Reference", "properties": {"foo": {"value": "bar", "type": "string"}}}, {"nodeAggregateId": "c", "name": "refs", "referenceTypeName": "Neos.ContentRepository:AnotherReference", "properties": {"foo": {"value": "foos", "type": "string"}}}]' to be returned
    When I execute the findBackReferences query for node aggregate id "b1" and filter '{"nodeTypes": "Neos.ContentRepository.Testing:SpecialPage"}' I expect the references '[{"nodeAggregateId": "c", "name": "refs", "referenceTypeName": "Neos.ContentRepository:AnotherReference", "properties": {"foo": {"value": "foos", "type": "string"}}}]' to be returned
    When I execute the findBackReferences query for node aggregate id "b1" and filter '{"pagination": {"limit": 1, "offset": 1}}' I expect the references '[{"nodeAggregateId": "a", "name": "refs", "referenceTypeName": "Neos.ContentRepository:Featureless", "properties": null}]' to be returned and the total count to be 3
    When I execute the findBackReferences query for node aggregate id "b1" and filter '{"nodeSearchTerm": "c"}' I expect the references '[{"nodeAggregateId": "c", "name": "refs", "referenceTypeName": "Neos.ContentRepository:AnotherReference", "properties": {"foo": {"value": "foos", "type": "string"}}}]' to be returned
    When I execute the findBackReferences query for node aggregate id "b1" and filter '{"nodePropertyValue": "text = \"a\""}' I expect the references '[{"nodeAggregateId": "a", "name": "ref", "referenceTypeName": "Neos.ContentRepository:Reference", "properties": {"foo": {"value": "bar", "type": "string"}}}, {"nodeAggregateId": "a", "name": "refs", "referenceTypeName": "Neos.ContentRepository:Featureless", "properties": null}]' to be returned
    When I execute the findBackReferences query for node aggregate id "b1" and filter '{"referenceSearchTerm": "a"}' I expect the references '[{"nodeAggregateId": "a", "name": "ref", "referenceTypeName": "Neos.ContentRepository:Reference", "properties": {"foo": {"value": "bar", "type": "string"}}}]' to be returned
    When I execute the findBackReferences query for node aggregate id "b1" and filter '{"referencePropertyValue": "foo = \"bar\""}' I expect the references '[{"nodeAggregateId": "a", "name": "ref", "referenceTypeName": "Neos.ContentRepository:Reference", "properties": {"foo": {"value": "bar", "type": "string"}}}]' to be returned
    When I execute the findBackReferences query for node aggregate id "b1" and filter '{"ordering": [{"type": "propertyName", "field": "text", "direction": "DESCENDING"}]}' I expect the references '[{"nodeAggregateId": "c", "name": "refs", "referenceTypeName": "Neos.ContentRepository:AnotherReference", "properties": {"foo": {"value": "foos", "type": "string"}}}, {"nodeAggregateId": "a", "name": "ref", "referenceTypeName": "Neos.ContentRepository:Reference", "properties": {"foo": {"value": "bar", "type": "string"}}}, {"nodeAggregateId": "a", "name": "refs", "referenceTypeName": "Neos.ContentRepository:Featureless", "properties": null}]' to be returned
