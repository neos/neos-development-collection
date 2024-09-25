@contentrepository @adapters=DoctrineDBAL
  # TODO implement for Postgres
Feature: Find and count nodes using the findAncestorNodes, countAncestorNodes and findAncestorNodeAggregateIds queries

  Background:
    Given using the following content dimensions:
      | Identifier | Values          | Generalizations      |
      | language   | mul, de, en, ch | ch->de->mul, en->mul |
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:AbstractPage':
      abstract: true
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
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | workspaceTitle       | "Live"               |
      | workspaceDescription | "The live workspace" |
      | newContentStreamId   | "cs-identifier"      |
    And I am in workspace "live" and dimension space point {"language":"de"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | nodeName | nodeTypeName                               | parentNodeAggregateId  | initialPropertyValues | tetheredDescendantNodeAggregateIds       |
      | home            | home     | Neos.ContentRepository.Testing:Homepage    | lady-eleonode-rootford | {}                    | {"terms": "terms", "contact": "contact"} |
      | a               | a        | Neos.ContentRepository.Testing:Page        | home                   | {}                    | {}                                       |
      | a1              | a1       | Neos.ContentRepository.Testing:Page        | a                      | {}                    | {}                                       |
      | a2              | a2       | Neos.ContentRepository.Testing:Page        | a                      | {}                    | {}                                       |
      | a2a             | a2a      | Neos.ContentRepository.Testing:SpecialPage | a2                     | {}                    | {}                                       |
      | a2a1            | a2a1     | Neos.ContentRepository.Testing:Page        | a2a                    | {}                    | {}                                       |
      | a2a2            | a2a2     | Neos.ContentRepository.Testing:Page        | a2a                    | {}                    | {}                                       |
      | a2a2a           | a2a2a    | Neos.ContentRepository.Testing:Page        | a2a2                   | {}                    | {}                                       |
      | a2a2b           | a2a2b    | Neos.ContentRepository.Testing:Page        | a2a2                   | {}                    | {}                                       |
      | a2b             | a2b      | Neos.ContentRepository.Testing:Page        | a2                     | {}                    | {}                                       |
      | a2b1            | a2b1     | Neos.ContentRepository.Testing:Page        | a2b                    | {}                    | {}                                       |
      | b               | b        | Neos.ContentRepository.Testing:Page        | home                   | {}                    | {}                                       |
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value         |
      | nodeAggregateId              | "a2a2a"       |
      | nodeVariantSelectionStrategy | "allVariants" |
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value         |
      | nodeAggregateId              | "a2b"         |
      | nodeVariantSelectionStrategy | "allVariants" |

  Scenario:
    Subgraph queries
    # findAncestorNodes queries without results
    When I execute the findAncestorNodes query for entry node aggregate id "non-existing" I expect no nodes to be returned
    # a2a2a is disabled
    When I execute the findAncestorNodes query for entry node aggregate id "a2a2a" I expect no nodes to be returned
    # a2b is disabled
    When I execute the findAncestorNodes query for entry node aggregate id "a2b1" I expect no nodes to be returned

    # findAncestorNodes queries with results
    When I execute the findAncestorNodes query for entry node aggregate id "a2a2b" I expect the nodes "a2a2,a2a,a2,a,home,lady-eleonode-rootford" to be returned and the total count to be 6
    When I execute the findAncestorNodes query for entry node aggregate id "a2a2b" and filter '{"nodeTypes": "Neos.ContentRepository.Testing:Page"}' I expect the nodes "a2a2,a2,a" to be returned and the total count to be 3

  Scenario:
    Contentgraph queries
    # findAncestorNodes queries without results
    When I execute the findAncestorNodeAggregateIds query for entry node aggregate id "non-existing" I expect no nodes to be returned

    # findAncestorNodes queries with results
    # a2a2a is disabled
    When I execute the findAncestorNodeAggregateIds query for entry node aggregate id "a2a2a" I expect the nodes "a2a2,a2a,a2,a,home,lady-eleonode-rootford" to be returned
    # a2b is disabled
    When I execute the findAncestorNodeAggregateIds query for entry node aggregate id "a2b1" I expect the nodes "a2b,a2,a,home,lady-eleonode-rootford" to be returned

    # findAncestorNodes queries with results
    When I execute the findAncestorNodeAggregateIds query for entry node aggregate id "a2a2b" I expect the nodes "a2a2,a2a,a2,a,home,lady-eleonode-rootford" to be returned
