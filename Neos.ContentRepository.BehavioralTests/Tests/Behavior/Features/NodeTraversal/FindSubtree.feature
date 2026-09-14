Feature: Find nodes using the findSubtree query

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
      | newContentStreamId   | "cs-identifier"      |
    And I am in workspace "live" and dimension space point {"language":"de"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
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
      | nodeAggregateId              | "a2a2a"       |
      | nodeVariantSelectionStrategy | "allVariants" |
    And I restrict the visibility of nodes tagged "disabled" in subgraph queries

  Scenario:
    # findSubtree queries without results
    When I execute the findSubtree query for entry node aggregate id "non-existing" I expect no results
    # node "a2a2a" is disabled so it should not yield results
    When I execute the findSubtree query for entry node aggregate id "a2a2a" I expect no results

    # findSubtree queries with results
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
    When I execute the findSubtree query for entry node aggregate id "home" and filter '{"nodeTypes": "Neos.ContentRepository.Testing:NonExisting"}' I expect the following tree:
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
    When I execute the findSubtree query for entry node aggregate id "home" and filter '{"nodeTypes": "Neos.ContentRepository.Testing:Page,Neos.ContentRepository.Testing:SpecialPage"}' I expect the following tree:
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
    When I execute the findSubtree query for entry node aggregate id "home" and filter '{"nodeTypes": "Neos.ContentRepository.Testing:Page,Neos.ContentRepository.Testing:SpecialPage", "maximumLevels": 3}' I expect the following tree:
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

  Scenario: A node excluded by the node type filter takes its whole subtree with it
    # "contact" is a Contact, which the filter below does not include. Its child here is a Page, which the
    # filter does include - and it must still be dropped, because it is only reachable through an excluded
    # node. This is the pruning property of findSubtree, and the one behaviour that a flat sort path range
    # scan {@see NodeSortPath} does not get for free: it is restored by the re-nesting dropping orphans.
    Given the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | nodeName     | nodeTypeName                        | parentNodeAggregateId | initialPropertyValues    | tetheredDescendantNodeAggregateIds |
      | contactchild    | contactchild | Neos.ContentRepository.Testing:Page | contact               | {"text": "contactchild"} | {}                                 |

    # without a filter it is part of the tree
    When I execute the findSubtree query for entry node aggregate id "home" I expect the following tree:
    """
    home
     terms
     contact
      contactchild
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
    # with the filter, both "contact" and its matching child are gone
    When I execute the findSubtree query for entry node aggregate id "home" and filter '{"nodeTypes": "Neos.ContentRepository.Testing:Page,Neos.ContentRepository.Testing:SpecialPage"}' I expect the following tree:
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
    # but it is reachable when queried from an entry node below the excluded one, since the entry node itself
    # is never subject to the node type filter
    When I execute the findSubtree query for entry node aggregate id "contact" and filter '{"nodeTypes": "Neos.ContentRepository.Testing:Page,Neos.ContentRepository.Testing:SpecialPage"}' I expect the following tree:
    """
    contact
     contactchild
    """
