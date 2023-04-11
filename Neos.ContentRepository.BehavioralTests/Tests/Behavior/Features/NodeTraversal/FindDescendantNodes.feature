@contentrepository @adapters=DoctrineDBAL
  # TODO implement for Postgres
Feature: Find nodes using the findDescendantNodes query

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
      | nodeAggregateId | nodeName | nodeTypeName                               | parentNodeAggregateId  | initialPropertyValues                                                                                             | tetheredDescendantNodeAggregateIds       |
      | home            | home     | Neos.ContentRepository.Testing:Homepage    | lady-eleonode-rootford | {}                                                                                                                | {"terms": "terms", "contact": "contact"} |
      | a               | a        | Neos.ContentRepository.Testing:Page        | home                   | {"text": "a", "booleanProperty": true}                                                                            | {}                                       |
      | a1              | a1       | Neos.ContentRepository.Testing:Page        | a                      | {"text": "a1", "integerProperty": 123}                                                                            | {}                                       |
      | a2              | a2       | Neos.ContentRepository.Testing:Page        | a                      | {"text": "a2", "integerProperty": 124}                                                                            | {}                                       |
      | a2a             | a2a      | Neos.ContentRepository.Testing:SpecialPage | a2                     | {"text": "a2a", "floatProperty": 123.45}                                                                          | {}                                       |
      | a2a1            | a2a1     | Neos.ContentRepository.Testing:Page        | a2a                    | {"text": "a2a1", "floatProperty": 123.46, "dateProperty": {"__type": "DateTimeImmutable", "value": "1980-12-13"}} | {}                                       |
      | a2a2            | a2a2     | Neos.ContentRepository.Testing:Page        | a2a                    | {"text": "a2a2", "dateProperty": {"__type": "DateTimeImmutable", "value": "1980-12-13 00:00:01"}}                 | {}                                       |
      | a2a2a           | a2a2a    | Neos.ContentRepository.Testing:Page        | a2a2                   | {"text": "a2a2a"}                                                                                                 | {}                                       |
      | a2a2b           | a2a2b    | Neos.ContentRepository.Testing:Page        | a2a2                   | {"text": "a2a2b", "integerProperty": 125, "booleanProperty": true}                                                | {}                                       |
      | a3              | a3       | Neos.ContentRepository.Testing:Page        | a                      | {"text": "a3", "booleanProperty": false}                                                                          | {}                                       |
      | b               | b        | Neos.ContentRepository.Testing:Page        | home                   | {"text": "b", "stringProperty": "späCial characters"}                                                             | {}                                       |
      | b1              | b1       | Neos.ContentRepository.Testing:Page        | b                      | {"text": "b1", "stringProperty": "true"}                                                                          | {}                                       |
    And the current date and time is "2023-03-16T13:00:00+01:00"
    And the command SetNodeProperties is executed with payload:
      | Key             | Value                   |
      | contentStreamId | "cs-identifier"         |
      | nodeAggregateId | "a2a2b"                 |
      | propertyValues  | {"integerProperty": 20} |
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value         |
      | nodeAggregateId              | "a2a2a"       |
      | nodeVariantSelectionStrategy | "allVariants" |
    And the graph projection is fully up to date

  Scenario:

      # findDescendantNodes queries without results
    When I execute the findDescendantNodes query for entry node aggregate id "non-existing" I expect no nodes to be returned
    When I execute the findDescendantNodes query for entry node aggregate id "home" and filter '{"searchTerm": "a2a2a"}' I expect no nodes to be returned
    When I execute the findDescendantNodes query for entry node aggregate id "home" and filter '{"searchTerm": "string"}' I expect no nodes to be returned
    When I execute the findDescendantNodes query for entry node aggregate id "home" and filter '{"propertyValue": "integerProperty > 125"}' I expect no nodes to be returned
    When I execute the findDescendantNodes query for entry node aggregate id "home" and filter '{"propertyValue": "integerProperty >= 126"}' I expect no nodes to be returned
    When I execute the findDescendantNodes query for entry node aggregate id "home" and filter '{"propertyValue": "integerProperty < 20"}' I expect no nodes to be returned
    When I execute the findDescendantNodes query for entry node aggregate id "home" and filter '{"propertyValue": "integerProperty <= 19"}' I expect no nodes to be returned
    When I execute the findDescendantNodes query for entry node aggregate id "home" and filter '{"propertyValue": "integerProperty <= 19 OR integerProperty <= 18"}' I expect no nodes to be returned
    # The following should not return node "b1" because boolean true !== "true"
    When I execute the findDescendantNodes query for entry node aggregate id "home" and filter '{"propertyValue": "stringProperty = true"}' I expect no nodes to be returned
    # The following should not return any node because date time properties are serialized into a full timestamp in the format "1980-12-13T00:00:00+00:00"
    When I execute the findDescendantNodes query for entry node aggregate id "home" and filter '{"propertyValue": "dateProperty = \"1980-12-13\""}' I expect no nodes to be returned
    When I execute the findDescendantNodes query for entry node aggregate id "home" and filter '{"propertyValue": "dateProperty <= \"1980-12-13\""}' I expect no nodes to be returned
    When I execute the findDescendantNodes query for entry node aggregate id "home" and filter '{"propertyValue": "dateProperty > \"1980-12-14\""}' I expect no nodes to be returned

      # findDescendantNodes queries with results
    When I execute the findDescendantNodes query for entry node aggregate id "home" I expect the nodes "terms,contact,a,b,a1,b1,a2,a3,a2a,a2a1,a2a2,a2a2b" to be returned
    When I execute the findDescendantNodes query for entry node aggregate id "home" and filter '{"nodeTypeConstraints": "Neos.ContentRepository.Testing:Page"}' I expect the nodes "a,b,a1,b1,a2,a3,a2a1,a2a2,a2a2b" to be returned
    When I execute the findDescendantNodes query for entry node aggregate id "home" and filter '{"searchTerm": "a2"}' I expect the nodes "a2,a2a,a2a1,a2a2,a2a2b" to be returned
    When I execute the findDescendantNodes query for entry node aggregate id "home" and filter '{"propertyValue": "text ^= \"a1\""}' I expect the nodes "a1" to be returned
    When I execute the findDescendantNodes query for entry node aggregate id "home" and filter '{"propertyValue": "text ^= \"a1\" OR text $= \"a1\""}' I expect the nodes "a1,a2a1" to be returned
    When I execute the findDescendantNodes query for entry node aggregate id "home" and filter '{"propertyValue": "stringProperty *= \"späci\" OR text $= \"a1\""}' I expect the nodes "b,a1,a2a1" to be returned
    When I execute the findDescendantNodes query for entry node aggregate id "home" and filter '{"propertyValue": "booleanProperty = true"}' I expect the nodes "a,a2a2b" to be returned
    When I execute the findDescendantNodes query for entry node aggregate id "home" and filter '{"propertyValue": "booleanProperty = false"}' I expect the nodes "a3" to be returned
    When I execute the findDescendantNodes query for entry node aggregate id "home" and filter '{"propertyValue": "integerProperty >= 20"}' I expect the nodes "a1,a2,a2a2b" to be returned
    When I execute the findDescendantNodes query for entry node aggregate id "home" and filter '{"propertyValue": "integerProperty > 20"}' I expect the nodes "a1,a2" to be returned
    When I execute the findDescendantNodes query for entry node aggregate id "home" and filter '{"propertyValue": "integerProperty <= 21"}' I expect the nodes "a2a2b" to be returned
    When I execute the findDescendantNodes query for entry node aggregate id "home" and filter '{"propertyValue": "integerProperty < 21"}' I expect the nodes "a2a2b" to be returned
    When I execute the findDescendantNodes query for entry node aggregate id "home" and filter '{"propertyValue": "floatProperty >= 123.45"}' I expect the nodes "a2a,a2a1" to be returned
    When I execute the findDescendantNodes query for entry node aggregate id "home" and filter '{"propertyValue": "floatProperty > 123.45"}' I expect the nodes "a2a1" to be returned
    When I execute the findDescendantNodes query for entry node aggregate id "home" and filter '{"propertyValue": "floatProperty = 123.45"}' I expect the nodes "a2a" to be returned
    When I execute the findDescendantNodes query for entry node aggregate id "home" and filter '{"propertyValue": "dateProperty >= \"1980-12-13\""}' I expect the nodes "a2a1,a2a2" to be returned
    When I execute the findDescendantNodes query for entry node aggregate id "home" and filter '{"propertyValue": "dateProperty > \"1980-12-13\""}' I expect the nodes "a2a1,a2a2" to be returned
    When I execute the findDescendantNodes query for entry node aggregate id "home" and filter '{"ordering": [{"type": "propertyName", "field": "integerProperty", "direction": "DESCENDING"}]}' I expect the nodes "a2a2b,a2,a1,terms,contact,a,b,b1,a3,a2a,a2a1,a2a2" to be returned
    When I execute the findDescendantNodes query for entry node aggregate id "home" and filter '{"ordering": [{"type": "propertyName", "field": "booleanProperty", "direction": "ASCENDING"}, {"type": "timestampField", "field": "LAST_MODIFIED", "direction": "DESCENDING"}]}' I expect the nodes "terms,contact,b,a1,b1,a2,a2a,a2a1,a2a2,a3,a2a2b,a" to be returned
