@contentrepository @adapters=DoctrineDBAL
  # TODO implement for Postgres
Feature: Find nodes using the findChildNodes query

  Background:
    Given the current date and time is "2023-03-16T12:00:00+01:00"
    And I have the following content dimensions:
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
      | nodeAggregateId | nodeTypeName                               | parentNodeAggregateId  | initialPropertyValues                                                                                                                                                                                | tetheredDescendantNodeAggregateIds       |
      | home            | Neos.ContentRepository.Testing:Homepage    | lady-eleonode-rootford | {}                                                                                                                                                                                                   | {"terms": "terms", "contact": "contact"} |
      | a               | Neos.ContentRepository.Testing:Page        | home                   | {"text": "a"}                                                                                                                                                                                        | {}                                       |
      | a1              | Neos.ContentRepository.Testing:Page        | a                      | {"text": "a1"}                                                                                                                                                                                       | {}                                       |
      | a2              | Neos.ContentRepository.Testing:Page        | a                      | {"text": "a2"}                                                                                                                                                                                       | {}                                       |
      | a2a             | Neos.ContentRepository.Testing:SpecialPage | a2                     | {"text": "a2a"}                                                                                                                                                                                      | {}                                       |
      | a2a1            | Neos.ContentRepository.Testing:Page        | a2a                    | {"text": "a2a1", "stringProperty": "the brown fox", "booleanProperty": true, "integerProperty": 33, "floatProperty": 12.345, "dateProperty": {"__type": "DateTimeImmutable", "value": "1980-12-13"}} | {}                                       |
      | a2a2            | Neos.ContentRepository.Testing:Page        | a2a                    | {"text": "a2a2", "stringProperty": "the red fox", "booleanProperty": false, "integerProperty": 22, "floatProperty": 12.34, "dateProperty": {"__type": "DateTimeImmutable", "value": "1980-12-14"}}   | {}                                       |
      | a2a3            | Neos.ContentRepository.Testing:Page        | a2a                    | {"text": "a2a3", "stringProperty": "the red bear", "integerProperty": 19, "dateProperty": {"__type": "DateTimeImmutable", "value": "1980-12-12"}}                                                    | {}                                       |
      | a2a4            | Neos.ContentRepository.Testing:Page        | a2a                    | {"text": "a2a4", "stringProperty": "the brown bear", "integerProperty": 19, "dateProperty": {"__type": "DateTimeImmutable", "value": "1980-12-12"}}                                                  | {}                                       |
      | a2a5            | Neos.ContentRepository.Testing:Page        | a2a                    | {"text": "a2a5", "stringProperty": "the brown bear", "integerProperty": 19, "dateProperty": {"__type": "DateTimeImmutable", "value": "1980-12-13"}}                                                  | {}                                       |
      | b               | Neos.ContentRepository.Testing:Page        | home                   | {"text": "b"}                                                                                                                                                                                        | {}                                       |
      | b1              | Neos.ContentRepository.Testing:Page        | b                      | {"text": "b1"}                                                                                                                                                                                       | {}                                       |
    And the current date and time is "2023-03-16T13:00:00+01:00"
    And the command SetNodeProperties is executed with payload:
      | Key             | Value                   |
      | contentStreamId | "cs-identifier"         |
      | nodeAggregateId | "a2a5"                  |
      | propertyValues  | {"integerProperty": 20} |
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value         |
      | nodeAggregateId              | "a2a3"        |
      | nodeVariantSelectionStrategy | "allVariants" |
    And the graph projection is fully up to date

  Scenario:
      # Child nodes without filter
    When I execute the findChildNodes query for parent node aggregate id "home" I expect the nodes "terms,contact,a,b" to be returned
    When I execute the findChildNodes query for parent node aggregate id "a" I expect the nodes "a1,a2" to be returned
    When I execute the findChildNodes query for parent node aggregate id "a1" I expect no nodes to be returned
    When I execute the findChildNodes query for parent node aggregate id "a2a" I expect the nodes "a2a1,a2a2,a2a4,a2a5" to be returned

      # Child nodes filtered by node type
    When I execute the findChildNodes query for parent node aggregate id "home" and filter '{"nodeTypeConstraints": "Neos.ContentRepository.Testing:AbstractPage"}' I expect the nodes "terms,contact,a,b" to be returned
    When I execute the findChildNodes query for parent node aggregate id "home" and filter '{"nodeTypeConstraints": "Neos.ContentRepository.Testing:SomeMixin"}' I expect the nodes "contact" to be returned
    When I execute the findChildNodes query for parent node aggregate id "home" and filter '{"nodeTypeConstraints": "Neos.ContentRepository.Testing:SomeMixin"}' I expect the nodes "contact" to be returned
    When I execute the findChildNodes query for parent node aggregate id "home" and filter '{"nodeTypeConstraints": "Neos.ContentRepository.Testing:AbstractPage,!Neos.ContentRepository.Testing:SomeMixin"}' I expect the nodes "terms,a,b" to be returned
    When I execute the findChildNodes query for parent node aggregate id "home" and filter '{"nodeTypeConstraints": "Neos.ContentRepository.Testing:NonExistingNodeType"}' I expect no nodes to be returned

     # Child nodes paginated
    When I execute the findChildNodes query for parent node aggregate id "home" and filter '{"pagination": {"limit": 3}}' I expect the nodes "terms,contact,a" to be returned and the total count to be 4
    When I execute the findChildNodes query for parent node aggregate id "home" and filter '{"pagination": {"limit": 2, "offset": 2}}' I expect the nodes "a,b" to be returned and the total count to be 4
    When I execute the findChildNodes query for parent node aggregate id "home" and filter '{"pagination": {"offset": 1}}' I expect the nodes "contact,a,b" to be returned and the total count to be 4

     # Child nodes filtered by node type, paginated
    When I execute the findChildNodes query for parent node aggregate id "home" and filter '{"nodeTypeConstraints": "Neos.ContentRepository.Testing:AbstractPage", "pagination": {"limit": 3}}' I expect the nodes "terms,contact,a" to be returned and the total count to be 4
    When I execute the findChildNodes query for parent node aggregate id "home" and filter '{"nodeTypeConstraints": "Neos.ContentRepository.Testing:AbstractPage,!Neos.ContentRepository.Testing:SomeMixin", "pagination": {"limit": 2, "offset": 1}}' I expect the nodes "a,b" to be returned and the total count to be 3
    When I execute the findChildNodes query for parent node aggregate id "home" and filter '{"nodeTypeConstraints": "!Neos.ContentRepository.Testing:Contact", "pagination": {"limit": 5}}' I expect the nodes "terms,a,b" to be returned

     # Child nodes filtered by property value
    When I execute the findChildNodes query for parent node aggregate id "a2a" and filter '{"propertyValue": "stringProperty ^= \"the\""}' I expect the nodes "a2a1,a2a2,a2a4,a2a5" to be returned
    When I execute the findChildNodes query for parent node aggregate id "a2a" and filter '{"propertyValue": "stringProperty ^= \"the brown\""}' I expect the nodes "a2a1,a2a4,a2a5" to be returned
    When I execute the findChildNodes query for parent node aggregate id "a2a" and filter '{"propertyValue": "stringProperty *= \"the\"", "pagination": {"limit": 2}}' I expect the nodes "a2a1,a2a2" to be returned and the total count to be 4
    When I execute the findChildNodes query for parent node aggregate id "a2a" and filter '{"propertyValue": "dateProperty > \"1980-12-13\""}' I expect the nodes "a2a1,a2a2,a2a5" to be returned
    When I execute the findChildNodes query for parent node aggregate id "a2a" and filter '{"propertyValue": "dateProperty < \"1980-12-13\""}' I expect the nodes "a2a4" to be returned
    When I execute the findChildNodes query for parent node aggregate id "a2a" and filter '{"propertyValue": "stringProperty ^= \"the\" AND (floatProperty = 12.345 OR integerProperty = 19)"}' I expect the nodes "a2a1,a2a4" to be returned
    When I execute the findChildNodes query for parent node aggregate id "a2a" and filter '{"propertyValue": "integerProperty > 22 OR integerProperty <= 19"}' I expect the nodes "a2a1,a2a4" to be returned
    When I execute the findChildNodes query for parent node aggregate id "a2a" and filter '{"propertyValue": "booleanProperty = true"}' I expect the nodes "a2a1" to be returned

    #  Child nodes with custom ordering
    When I execute the findChildNodes query for parent node aggregate id "a2a" and filter '{"ordering": [{"type": "propertyName", "field": "text", "direction": "ASCENDING"}]}' I expect the nodes "a2a1,a2a2,a2a4,a2a5" to be returned
    When I execute the findChildNodes query for parent node aggregate id "a2a" and filter '{"ordering": [{"type": "propertyName", "field": "text", "direction": "DESCENDING"}]}' I expect the nodes "a2a5,a2a4,a2a2,a2a1" to be returned
    When I execute the findChildNodes query for parent node aggregate id "a2a" and filter '{"ordering": [{"type": "propertyName", "field": "non_existing", "direction": "ASCENDING"}]}' I expect the nodes "a2a1,a2a2,a2a4,a2a5" to be returned
    When I execute the findChildNodes query for parent node aggregate id "a2a" and filter '{"ordering": [{"type": "propertyName", "field": "booleanProperty", "direction": "ASCENDING"}, {"type": "propertyName", "field": "dateProperty", "direction": "ASCENDING"}]}' I expect the nodes "a2a4,a2a5,a2a2,a2a1" to be returned
    When I execute the findChildNodes query for parent node aggregate id "a2a" and filter '{"ordering": [{"type": "timestampField", "field": "CREATED", "direction": "ASCENDING"}]}' I expect the nodes "a2a1,a2a2,a2a4,a2a5" to be returned
    When I execute the findChildNodes query for parent node aggregate id "a2a" and filter '{"ordering": [{"type": "timestampField", "field": "LAST_MODIFIED", "direction": "DESCENDING"}]}' I expect the nodes "a2a5,a2a1,a2a2,a2a4" to be returned
