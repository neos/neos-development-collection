@contentrepository @adapters=DoctrineDBAL,Postgres
Feature: Find nodes using the findNodeById query

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

    # disable a2a
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value         |
      | nodeAggregateId              | "a2a"         |
      | nodeVariantSelectionStrategy | "allVariants" |

    # disable child of a2a
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value         |
      | nodeAggregateId              | "a2a1"        |
      | nodeVariantSelectionStrategy | "allVariants" |

    # disable b only in spezialisation
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "b1"                 |
      | coveredDimensionSpacePoint   | {"language":"ch"}    |
      | nodeVariantSelectionStrategy | "allSpecializations" |

    # tag another node differently
    And the command TagSubtree is executed with payload:
      | Key                          | Value         |
      | nodeAggregateId              | "b"           |
      | nodeVariantSelectionStrategy | "allVariants" |
      | tag                          | "tag1"        |

  Scenario:
  ContentGraph queries

    When I execute the findNodeAggregatesTaggedBy query for tag "disabled" I expect the following node aggregates to be returned:
      | nodeAggregateId | nodeTypeName                               | coveredDimensionSpacePoints           | occupiedDimensionSpacePoints | explicitlyDisabledDimensions          |
      | b1              | Neos.ContentRepository.Testing:Page        | [{"language":"de"},{"language":"ch"}] | [{"language":"de"}]          | [{"language":"ch"}]                   |
      | a2a1            | Neos.ContentRepository.Testing:Page        | [{"language":"de"},{"language":"ch"}] | [{"language":"de"}]          | [{"language":"de"},{"language":"ch"}] |
      | a2a             | Neos.ContentRepository.Testing:SpecialPage | [{"language":"de"},{"language":"ch"}] | [{"language":"de"}]          | [{"language":"de"},{"language":"ch"}] |


    When I execute the findNodeAggregatesTaggedBy query for tag "tag1" I expect the following node aggregates to be returned:
      | nodeAggregateId | nodeTypeName                        | coveredDimensionSpacePoints           | occupiedDimensionSpacePoints | explicitlyDisabledDimensions |
      # b is not 'disabled' but tagged tag1
      | b               | Neos.ContentRepository.Testing:Page | [{"language":"de"},{"language":"ch"}] | [{"language":"de"}]          | []                           |
