@contentrepository @adapters=DoctrineDBAL,Postgres
Feature: Find nodes using the findParentNodes query

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
      | b               | b        | Neos.ContentRepository.Testing:Page        | home                   | {"text": "b"}         | {}                                       |
      | b1              | b1       | Neos.ContentRepository.Testing:Page        | b                      | {"text": "b1"}        | {}                                       |
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value         |
      | nodeAggregateId              | "a2a"         |
      | nodeVariantSelectionStrategy | "allVariants" |
    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value              |
      | nodeAggregateId              | "b"                |
      | dimensionSpacePoint          | {"language": "ch"} |
      | newParentNodeAggregateId     | "a"                |
      | relationDistributionStrategy | "scatter"          |

  Scenario:
    Subgraph queries
    # findParentNode queries without result
    When I execute the findParentNode query for node aggregate id "non-existing" I expect no node to be returned
    When I execute the findParentNode query for node aggregate id "lady-eleonode-rootford" I expect no node to be returned
    # node "a2a" is disabled, so it should be ignored
    When I execute the findParentNode query for node aggregate id "a2a" I expect no node to be returned
    # the parent node of "a2a1", "a2a", is disabled, so it must not be returned
    When I execute the findParentNode query for node aggregate id "a2a1" I expect no node to be returned

    # findParentNode queries with result
    When I execute the findParentNode query for node aggregate id "home" I expect the node "lady-eleonode-rootford" to be returned
    When I execute the findParentNode query for node aggregate id "a2" I expect the node "a" to be returned

  Scenario:
    Contentgraph queries
    # subtree tags are fetched correctly
    When I execute the findParentNodeAggregates query for node aggregate id "a2a1" I expect the following node aggregates to be returned:
      | nodeAggregateId | nodeTypeName                               | coveredDimensionSpacePoints           | occupiedDimensionSpacePoints | explicitlyDisabledDimensions            |
      | a2a             | Neos.ContentRepository.Testing:SpecialPage | [{"language":"de"},{"language":"ch"}] | [{"language":"de"}]          | [{"language":"de"}, {"language": "ch"}] |

    # multiple parent node aggregates (via move) are fetched
    When I execute the findParentNodeAggregates query for node aggregate id "b" I expect the following node aggregates to be returned:
      | nodeAggregateId | nodeTypeName                            | coveredDimensionSpacePoints           | occupiedDimensionSpacePoints | explicitlyDisabledDimensions |
      | home            | Neos.ContentRepository.Testing:Homepage | [{"language":"de"},{"language":"ch"}] | [{"language":"de"}]          | []                           |
      | a               | Neos.ContentRepository.Testing:Page     | [{"language":"de"},{"language":"ch"}] | [{"language":"de"}]          | []                           |

    When I execute the findParentNodeAggregates query for node aggregate id "non-existing" I expect the following node aggregates to be returned:
      | nodeAggregateId | nodeTypeName                        | coveredDimensionSpacePoints           | occupiedDimensionSpacePoints | explicitlyDisabledDimensions |
