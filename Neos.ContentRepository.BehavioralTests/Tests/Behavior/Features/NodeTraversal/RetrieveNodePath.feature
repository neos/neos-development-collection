@contentrepository @adapters=DoctrineDBAL
  # TODO implement for Postgres
Feature: Find nodes using the retrieveNodePath query

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
      | a3              | a3       | Neos.ContentRepository.Testing:Page        | a                      | {"text": "a3"}        | {}                                       |
      | a4              | a4       | Neos.ContentRepository.Testing:SpecialPage | a                      | {"text": "a4"}        | {}                                       |
      | a5              | a5       | Neos.ContentRepository.Testing:Page        | a                      | {"text": "a5"}        | {}                                       |
      | a6              | a6       | Neos.ContentRepository.Testing:Page        | a                      | {"text": "a6"}        | {}                                       |
      | b               | b        | Neos.ContentRepository.Testing:Page        | home                   | {"text": "b"}         | {}                                       |
      | b1              | b1       | Neos.ContentRepository.Testing:Page        | b                      | {"text": "b1"}        | {}                                       |
      | b1a             | b1a      | Neos.ContentRepository.Testing:Page        | b1                     | {"text": "b1a"}       | {}                                       |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | nodeTypeName                            | parentNodeAggregateId |
      | unnamed         | Neos.ContentRepository.Testing:Homepage | a2a                   |
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value         |
      | nodeAggregateId              | "b1"          |
      | nodeVariantSelectionStrategy | "allVariants" |
    And the graph projection is fully up to date

  Scenario: retrieveNodePath queries without result
    When I execute the retrieveNodePath query for node aggregate id "non-existing" I expect an exception 'Failed to retrieve node path for node "non-existing"'
    # node "b1" is disabled so it must not be returned
    When I execute the retrieveNodePath query for node aggregate id "b1" I expect an exception 'Failed to retrieve node path for node "b1"'
    When I execute the retrieveNodePath query for node aggregate id "b1a" I expect an exception 'Failed to retrieve node path for node "b1a"'
    When I execute the retrieveNodePath query for node aggregate id "unnamed" I expect an exception 'The path "//home/a/a2/a2a/" is no valid NodePath because it contains a segment "" that is no valid NodeName'

  Scenario: retrieveNodePath queries with result
    When I execute the retrieveNodePath query for node aggregate id "lady-eleonode-rootford" I expect the path "/" to be returned
    When I execute the retrieveNodePath query for node aggregate id "home" I expect the path "//home" to be returned
    When I execute the retrieveNodePath query for node aggregate id "a2a2" I expect the path "//home/a/a2/a2a/a2a2" to be returned
