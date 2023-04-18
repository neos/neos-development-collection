@contentrepository @adapters=DoctrineDBAL,Postgres
Feature: Find nodes using the findSubtree query

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
      | nodeAggregateId | nodeName | nodeTypeName                               | parentNodeAggregateId  | initialPropertyValues | tetheredDescendantNodeAggregateIds       |
      | home            | home     | Neos.ContentRepository.Testing:Homepage    | lady-eleonode-rootford | {}                    | {"terms": "terms", "contact": "contact"} |
      | a               | a        | Neos.ContentRepository.Testing:Page        | home                   | {"text": "a"}         | {}                                       |
      | a1              | a1       | Neos.ContentRepository.Testing:Page        | a                      | {"text": "a1"}        | {}                                       |
      | a2              | a2       | Neos.ContentRepository.Testing:Page        | a                      | {"text": "a2"}        | {}                                       |
      | a2a             | a2a      | Neos.ContentRepository.Testing:SpecialPage | a2                     | {"text": "a2a"}       | {}                                       |
      | a2a1            | a2a1     | Neos.ContentRepository.Testing:Page        | a2a                    | {"text": "a2a1"}      | {}                                       |
      | a2a2            | a2a2     | Neos.ContentRepository.Testing:Page        | a2a                    | {"text": "a2a2"}      | {}                                       |
      | a2a2a           | a2a2a    | Neos.ContentRepository.Testing:Page        | a2a2                   | {"text": "a2a2a"}     | {}                                       |
      | a3              | a3       | Neos.ContentRepository.Testing:Page        | a                      | {"text": "a3"}        | {}                                       |
      | b               | b        | Neos.ContentRepository.Testing:Page        | home                   | {"text": "b"}         | {}                                       |
      | b1              | b1       | Neos.ContentRepository.Testing:Page        | b                      | {"text": "b1"}        | {}                                       |
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value         |
      | nodeAggregateId              | "a2a2a"        |
      | nodeVariantSelectionStrategy | "allVariants" |
    And the graph projection is fully up to date

  Scenario: findSubtree queries without results
    When I execute the findSubtree query for entry node aggregate id "non-existing" I expect no results
    # node "a2a2a" is disabled so it should not yield results
    When I execute the findSubtree query for entry node aggregate id "a2a2a" I expect no results

  Scenario: findSubtree queries with results
    When I execute the findSubtree query for entry node aggregate id "a2a2" I expect no results
    """
    a2a2
    """
    When I execute the findSubtree query for entry node aggregate id "b1" I expect the following tree:
    """
    b1
    """
    When I execute the findSubtree query for entry node aggregate id "home" I expect the following tree:
    """
    home
     terms
     contact
     a
      a1
      a2
       a2a
        a2a1
        a2a2
      a3
     b
      b1
    """
    When I execute the findSubtree query for entry node aggregate id "home" and filter '{"nodeTypeConstraints": "Neos.ContentRepository.Testing:NonExisting"}' I expect the following tree:
    """
    home
    """
    When I execute the findSubtree query for entry node aggregate id "home" and filter '{"maximumLevels": 2}' I expect the following tree:
    """
    home
     terms
     contact
     a
      a1
      a2
      a3
     b
      b1
    """
    When I execute the findSubtree query for entry node aggregate id "home" and filter '{"nodeTypeConstraints": "Neos.ContentRepository.Testing:Page,Neos.ContentRepository.Testing:SpecialPage"}' I expect the following tree:
    """
    home
     a
      a1
      a2
       a2a
        a2a1
        a2a2
      a3
     b
      b1
    """
    When I execute the findSubtree query for entry node aggregate id "home" and filter '{"nodeTypeConstraints": "Neos.ContentRepository.Testing:Page,Neos.ContentRepository.Testing:SpecialPage", "maximumLevels": 3}' I expect the following tree:
    """
    home
     a
      a1
      a2
       a2a
      a3
     b
      b1
    """
