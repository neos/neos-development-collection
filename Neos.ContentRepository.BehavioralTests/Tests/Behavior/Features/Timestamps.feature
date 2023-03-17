@contentrepository @adapters=DoctrineDBAL
Feature: TODO

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
      | Key                | Value     |
      | workspaceName      | "live"    |
      | newContentStreamId | "cs-live" |
    And the command CreateWorkspace is executed with payload:
      | Key                | Value       |
      | workspaceName      | "review"    |
      | baseWorkspaceName  | "live"      |
      | newContentStreamId | "cs-review" |
    And the command CreateWorkspace is executed with payload:
      | Key                | Value       |
      | workspaceName      | "user-test" |
      | baseWorkspaceName  | "review"    |
      | newContentStreamId | "cs-user"   |
      | workspaceOwner     | "some-user" |
    And the graph projection is fully up to date
    And I am in content stream "cs-user" and dimension space point {"language":"de"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date
    And the current date and time is "2023-03-16T12:00:00+01:00"
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | nodeName | nodeTypeName                               | parentNodeAggregateId  | initialPropertyValues | tetheredDescendantNodeAggregateIds       |
      | home            | home     | Neos.ContentRepository.Testing:Homepage    | lady-eleonode-rootford | {}                    | {"terms": "terms", "contact": "contact"} |
      | a               | a        | Neos.ContentRepository.Testing:Page        | home                   | {"text": "a"}         | {}                                       |
      | b               | b        | Neos.ContentRepository.Testing:Page        | home                   | {"text": "b"}         | {}                                       |

  Scenario: TODO
    And the current date and time is "2023-03-16T13:00:00+01:00"
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                |
      | contentStreamId           | "cs-user"            |
      | nodeAggregateId           | "a"                  |
      | propertyValues            | {"text": "Changed"} |
    When I execute the findNodeById query for node aggregate id "non-existing" I expect no node to be returned
    And the graph projection is fully up to date
    And I wait for 5 seconds
    And the current date and time is "2023-03-16T14:00:00+01:00"
    When the command PublishWorkspace is executed with payload:
      | Key              | Value                        |
      | workspaceName    | "user-test"                  |
    And the graph projection is fully up to date
