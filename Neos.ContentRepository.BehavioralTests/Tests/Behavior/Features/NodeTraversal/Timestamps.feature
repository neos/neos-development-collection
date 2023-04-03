@contentrepository @adapters=DoctrineDBAL
 # TODO implement for Postgres
Feature: Behavior of Node timestamp properties "created", "originalCreated", "lastModified" and "originalLastModified"

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
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | nodeName | nodeTypeName                            | parentNodeAggregateId  | initialPropertyValues | tetheredDescendantNodeAggregateIds       |
      | home            | home     | Neos.ContentRepository.Testing:Homepage | lady-eleonode-rootford | {}                    | {"terms": "terms", "contact": "contact"} |
      | a               | a        | Neos.ContentRepository.Testing:Page     | home                   | {"text": "a"}         | {}                                       |
      | b               | b        | Neos.ContentRepository.Testing:Page     | home                   | {"text": "b"}         | {}                                       |
    And the current date and time is "2023-03-16T12:30:00+01:00"
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value             |
      | contentStreamId | "cs-user"         |
      | nodeAggregateId | "a"               |
      | sourceOrigin    | {"language":"de"} |
      | targetOrigin    | {"language":"ch"} |
    And the graph projection is fully up to date

  Scenario: NodePropertiesWereSet events update last modified timestamps
    When the current date and time is "2023-03-16T13:00:00+01:00"
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value               |
      | contentStreamId           | "cs-user"           |
      | originDimensionSpacePoint | {"language": "ch"}  |
      | nodeAggregateId           | "a"                 |
      | propertyValues            | {"text": "Changed"} |
    And the graph projection is fully up to date
    And I am in content stream "cs-user" and dimension space point {"language":"de"}
    Then I expect the node "a" to have the following timestamps:
      | created             | originalCreated     | lastModified | originalLastModified |
      | 2023-03-16 12:00:00 | 2023-03-16 12:00:00 |              |                      |

    When I am in content stream "cs-user" and dimension space point {"language":"ch"}
    Then I expect the node "a" to have the following timestamps:
      | created             | originalCreated     | lastModified        | originalLastModified |
      | 2023-03-16 12:30:00 | 2023-03-16 12:30:00 | 2023-03-16 13:00:00 | 2023-03-16 13:00:00  |

  Scenario: NodeAggregateNameWasChanged events update last modified timestamps
    When the current date and time is "2023-03-16T13:00:00+01:00"
    And the command "ChangeNodeAggregateName" is executed with payload:
      | Key             | Value       |
      | contentStreamId | "cs-user"   |
      | nodeAggregateId | "a"         |
      | newNodeName     | "a-renamed" |
    And the graph projection is fully up to date
    And I am in content stream "cs-user" and dimension space point {"language":"de"}
    Then I expect the node "a" to have the following timestamps:
      | created             | originalCreated     | lastModified        | originalLastModified |
      | 2023-03-16 12:00:00 | 2023-03-16 12:00:00 | 2023-03-16 13:00:00 | 2023-03-16 13:00:00  |

    When I am in content stream "cs-user" and dimension space point {"language":"ch"}
    Then I expect the node "a" to have the following timestamps:
      | created             | originalCreated     | lastModified        | originalLastModified |
      | 2023-03-16 12:30:00 | 2023-03-16 12:30:00 | 2023-03-16 13:00:00 | 2023-03-16 13:00:00  |

  Scenario: NodeReferencesWereSet events update last modified timestamps
    When the current date and time is "2023-03-16T13:00:00+01:00"
    And the command SetNodeReferences is executed with payload:
      | Key                             | Value              |
      | contentStreamId                 | "cs-user"          |
      | sourceOriginDimensionSpacePoint | {"language": "ch"} |
      | sourceNodeAggregateId           | "a"                |
      | referenceName                   | "ref"              |
      | references                      | [{"target": "b"}]  |
    And the graph projection is fully up to date
    And I am in content stream "cs-user" and dimension space point {"language":"de"}
    Then I expect the node "a" to have the following timestamps:
      | created             | originalCreated     | lastModified | originalLastModified |
      | 2023-03-16 12:00:00 | 2023-03-16 12:00:00 |              |                      |
    And I expect the node "b" to have the following timestamps:
      | created             | originalCreated     | lastModified | originalLastModified |
      | 2023-03-16 12:00:00 | 2023-03-16 12:00:00 |              |                      |

    When I am in content stream "cs-user" and dimension space point {"language":"ch"}
    Then I expect the node "a" to have the following timestamps:
      | created             | originalCreated     | lastModified        | originalLastModified |
      | 2023-03-16 12:30:00 | 2023-03-16 12:30:00 | 2023-03-16 13:00:00 | 2023-03-16 13:00:00  |
    And I expect the node "b" to have the following timestamps:
      | created             | originalCreated     | lastModified | originalLastModified |
      | 2023-03-16 12:00:00 | 2023-03-16 12:00:00 |              |                      |

  Scenario: NodeAggregateTypeWasChanged events update last modified timestamps
    When the current date and time is "2023-03-16T13:00:00+01:00"
    And the command ChangeNodeAggregateType was published with payload:
      | Key             | Value                                        |
      | contentStreamId | "cs-user"                                    |
      | nodeAggregateId | "a"                                          |
      | newNodeTypeName | "Neos.ContentRepository.Testing:SpecialPage" |
      | strategy        | "happypath"                                  |
    And the graph projection is fully up to date
    And I am in content stream "cs-user" and dimension space point {"language":"de"}
    Then I expect the node "a" to have the following timestamps:
      | created             | originalCreated     | lastModified        | originalLastModified |
      | 2023-03-16 12:00:00 | 2023-03-16 12:00:00 | 2023-03-16 13:00:00 | 2023-03-16 13:00:00  |

    When I am in content stream "cs-user" and dimension space point {"language":"ch"}
    Then I expect the node "a" to have the following timestamps:
      | created             | originalCreated     | lastModified        | originalLastModified |
      | 2023-03-16 12:30:00 | 2023-03-16 12:30:00 | 2023-03-16 13:00:00 | 2023-03-16 13:00:00  |

  Scenario: NodePeerVariantWasCreated events set new created timestamps
    When the current date and time is "2023-03-16T13:00:00+01:00"
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value             |
      | nodeAggregateId | "home"            |
      | sourceOrigin    | {"language":"de"} |
      | targetOrigin    | {"language":"en"} |
    And the graph projection is fully up to date
    And I am in content stream "cs-user" and dimension space point {"language":"de"}
    Then I expect the node "home" to have the following timestamps:
      | created             | originalCreated     | lastModified | originalLastModified |
      | 2023-03-16 12:00:00 | 2023-03-16 12:00:00 |              |                      |

    When I am in content stream "cs-user" and dimension space point {"language":"en"}
    Then I expect the node "home" to have the following timestamps:
      | created             | originalCreated     | lastModified | originalLastModified |
      | 2023-03-16 13:00:00 | 2023-03-16 13:00:00 |              |                      |

  Scenario: NodeGeneralizationVariantWasCreated events set new created timestamps
    When the current date and time is "2023-03-16T13:00:00+01:00"
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value              |
      | nodeAggregateId | "home"             |
      | sourceOrigin    | {"language":"de"}  |
      | targetOrigin    | {"language":"mul"} |
    And the graph projection is fully up to date
    And I am in content stream "cs-user" and dimension space point {"language":"de"}
    Then I expect the node "home" to have the following timestamps:
      | created             | originalCreated     | lastModified | originalLastModified |
      | 2023-03-16 12:00:00 | 2023-03-16 12:00:00 |              |                      |

    When I am in content stream "cs-user" and dimension space point {"language":"mul"}
    Then I expect the node "home" to have the following timestamps:
      | created             | originalCreated     | lastModified | originalLastModified |
      | 2023-03-16 13:00:00 | 2023-03-16 13:00:00 |              |                      |


  Scenario: NodeAggregateWasMoved events don't update last modified timestamps
    When the current date and time is "2023-03-16T13:00:00+01:00"
    And the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                   |
      | contentStreamId              | "cs-user"               |
      | dimensionSpacePoint          | {"language": "ch"}      |
      | relationDistributionStrategy | "gatherSpecializations" |
      | nodeAggregateId              | "a"                     |
      | newParentNodeAggregateId     | "b"                     |
    And the graph projection is fully up to date
    And I am in content stream "cs-user" and dimension space point {"language":"de"}
    Then I expect the node "a" to have the following timestamps:
      | created             | originalCreated     | lastModified | originalLastModified |
      | 2023-03-16 12:00:00 | 2023-03-16 12:00:00 |              |                      |

    When I am in content stream "cs-user" and dimension space point {"language":"ch"}
    Then I expect the node "a" to have the following timestamps:
      | created             | originalCreated     | lastModified | originalLastModified |
      | 2023-03-16 12:30:00 | 2023-03-16 12:30:00 |              |                      |

  Scenario: RootNodeAggregateDimensionsWereUpdated events don't update last modified timestamps
    When the current date and time is "2023-03-16T13:00:00+01:00"
    And the command UpdateRootNodeAggregateDimensions is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "lady-eleonode-rootford" |
    And the graph projection is fully up to date
    And I am in content stream "cs-user" and dimension space point {"language":"de"}
    Then I expect the node "a" to have the following timestamps:
      | created             | originalCreated     | lastModified | originalLastModified |
      | 2023-03-16 12:00:00 | 2023-03-16 12:00:00 |              |                      |

    When I am in content stream "cs-user" and dimension space point {"language":"ch"}
    Then I expect the node "a" to have the following timestamps:
      | created             | originalCreated     | lastModified | originalLastModified |
      | 2023-03-16 12:30:00 | 2023-03-16 12:30:00 |              |                      |

  Scenario: NodeAggregateWasEnabled and NodeAggregateWasDisabled events don't update last modified timestamps
    When the current date and time is "2023-03-16T13:00:00+01:00"
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                |
      | contentStreamId              | "cs-user"            |
      | coveredDimensionSpacePoint   | {"language": "ch"}   |
      | nodeAggregateId              | "a"                  |
      | nodeVariantSelectionStrategy | "allSpecializations" |
    And the graph projection is fully up to date
    And I am in content stream "cs-user" and dimension space point {"language":"de"}
    And VisibilityConstraints are set to "withoutRestrictions"
    Then I expect the node "a" to have the following timestamps:
      | created             | originalCreated     | lastModified | originalLastModified |
      | 2023-03-16 12:00:00 | 2023-03-16 12:00:00 |              |                      |

    When I am in content stream "cs-user" and dimension space point {"language":"ch"}
    Then I expect the node "a" to have the following timestamps:
      | created             | originalCreated     | lastModified | originalLastModified |
      | 2023-03-16 12:30:00 | 2023-03-16 12:30:00 |              |                      |

    When the current date and time is "2023-03-16T14:00:00+01:00"
    And the command EnableNodeAggregate is executed with payload:
      | Key                          | Value                |
      | contentStreamId              | "cs-user"            |
      | coveredDimensionSpacePoint   | {"language": "ch"}   |
      | nodeAggregateId              | "a"                  |
      | nodeVariantSelectionStrategy | "allSpecializations" |
    And the graph projection is fully up to date
    And I am in content stream "cs-user" and dimension space point {"language":"de"}
    Then I expect the node "a" to have the following timestamps:
      | created             | originalCreated     | lastModified | originalLastModified |
      | 2023-03-16 12:00:00 | 2023-03-16 12:00:00 |              |                      |

    When I am in content stream "cs-user" and dimension space point {"language":"ch"}
    Then I expect the node "a" to have the following timestamps:
      | created             | originalCreated     | lastModified | originalLastModified |
      | 2023-03-16 12:30:00 | 2023-03-16 12:30:00 |              |                      |


  Scenario: Original created and last modified timestamps when publishing nodes over multiple content streams
    When the current date and time is "2023-03-16T13:00:00+01:00"
    And the command SetNodeProperties is executed with payload:
      | Key             | Value               |
      | contentStreamId | "cs-user"           |
      | nodeAggregateId | "a"                 |
      | propertyValues  | {"text": "Changed"} |
    And I execute the findNodeById query for node aggregate id "non-existing" I expect no node to be returned
    And the graph projection is fully up to date
    And the current date and time is "2023-03-16T14:00:00+01:00"
    And the command PublishWorkspace is executed with payload:
      | Key           | Value       |
      | workspaceName | "user-test" |
    And the graph projection is fully up to date

    And I am in content stream "cs-user"
    Then I expect the node "a" to have the following timestamps:
      | created             | originalCreated     | lastModified        | originalLastModified |
      | 2023-03-16 12:00:00 | 2023-03-16 12:00:00 | 2023-03-16 13:00:00 | 2023-03-16 13:00:00  |
    And I expect the node "b" to have the following timestamps:
      | created             | originalCreated     | lastModified | originalLastModified |
      | 2023-03-16 12:00:00 | 2023-03-16 12:00:00 |              |                      |

    And I am in content stream "cs-review"
    Then I expect the node "a" to have the following timestamps:
      | created             | originalCreated     | lastModified        | originalLastModified |
      | 2023-03-16 14:00:00 | 2023-03-16 12:00:00 | 2023-03-16 14:00:00 | 2023-03-16 13:00:00  |
    And I expect the node "b" to have the following timestamps:
      | created             | originalCreated     | lastModified | originalLastModified |
      | 2023-03-16 14:00:00 | 2023-03-16 12:00:00 |              |                      |

    When the current date and time is "2023-03-16T15:00:00+01:00"
    And the command PublishWorkspace is executed with payload:
      | Key           | Value    |
      | workspaceName | "review" |
    And the graph projection is fully up to date
    And I am in content stream "cs-live"
    Then I expect the node "a" to have the following timestamps:
      | created             | originalCreated     | lastModified        | originalLastModified |
      | 2023-03-16 15:00:00 | 2023-03-16 12:00:00 | 2023-03-16 15:00:00 | 2023-03-16 13:00:00  |
    And I expect the node "b" to have the following timestamps:
      | created             | originalCreated     | lastModified | originalLastModified |
      | 2023-03-16 15:00:00 | 2023-03-16 12:00:00 |              |                      |
