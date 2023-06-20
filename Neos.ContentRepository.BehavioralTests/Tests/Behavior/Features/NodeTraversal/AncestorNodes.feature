@contentrepository @adapters=DoctrineDBAL
  # TODO implement for Postgres
Feature: Find and count nodes using the findAncestorNodes and countAncestorNodes queries

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
    # findAncestorNodes queries without results
    When I execute the findAncestorNodes query for entry node aggregate id "non-existing" I expect no nodes to be returned
    # a2a2a is disabled
    When I execute the findAncestorNodes query for entry node aggregate id "a2a2a" I expect no nodes to be returned

    # findAncestorNodes queries with results
    When I execute the findAncestorNodes query for entry node aggregate id "a2a2b" I expect the nodes "a2a2,a2a,a2,a,home,lady-eleonode-rootford" to be returned and the total count to be 6
    When I execute the findAncestorNodes query for entry node aggregate id "a2a2b" and filter '{"nodeTypeConstraints": "Neos.ContentRepository.Testing:Page"}' I expect the nodes "a2a2,a2,a" to be returned and the total count to be 3
