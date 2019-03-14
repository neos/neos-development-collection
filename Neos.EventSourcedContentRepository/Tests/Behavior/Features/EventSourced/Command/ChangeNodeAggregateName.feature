@fixtures
Feature: Change node name

  As a user of the CR I want to change the name of a hierarchical relation between two nodes (e.g. in taxonomies)

  Background:
    Given I have no content dimensions
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                    |
      | contentStreamIdentifier       | "cs-identifier"                          |
      | nodeAggregateIdentifier       | "lady-eleonode-nodesworth"               |
      | nodeTypeName                  | "Neos.ContentRepository:Root"            |
      | visibleInDimensionSpacePoints | [{"language": "de"},{"language": "gsw"}] |
      | initiatingUserIdentifier      | "system"                                 |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository.Testing:Content': []
    """

  Scenario: Change node name of content node
    Given the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                    |
      | contentStreamIdentifier       | "cs-identifier"                          |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                       |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content" |
      | originDimensionSpacePoint     | {}                                       |
      | visibleInDimensionSpacePoints | [{}]                                     |
      | parentNodeAggregateIdentifier | "lady-eleonode-nodesworth"               |
      | nodeName                      | "dog"                                    |

    And the graph projection is fully up to date
    When the command "ChangeNodeAggregateName" is executed with payload:
      | Key                     | Value              |
      | contentStreamIdentifier | "cs-identifier"    |
      | nodeAggregateIdentifier | "nody-mc-nodeface" |
      | newNodeName             | "cat"              |

    Then I expect exactly 2 events to be published on stream with prefix "Neos.ContentRepository:ContentStream:cs-identifier:NodeAggregate:nody-mc-nodeface"
    And event at index 1 is of type "Neos.EventSourcedContentRepository:NodeAggregateNameWasChanged" with payload:
      | Key                     | Expected           |
      | contentStreamIdentifier | "cs-identifier"    |
      | nodeAggregateIdentifier | "nody-mc-nodeface" |
      | newNodeName             | "cat"              |

  Scenario: Change node name actually updates projection
    Given the Event "Neos.EventSourcedContentRepository:NodeAggregateWithNodeWasCreated" was published to stream "Neos.ContentRepository:ContentStream:c75ae6a2-7254-4d42-a31b-a629e264069d:NodeAggregate:35411439-94d1-4bd4-8fac-0646856c6a1f" with payload:
      | Key                           | Value                                    |
      | contentStreamIdentifier       | "c75ae6a2-7254-4d42-a31b-a629e264069d"   |
      | nodeAggregateIdentifier       | "35411439-94d1-4bd4-8fac-0646856c6a1f"   |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content" |
      | dimensionSpacePoint           | {}                                       |
      | visibleInDimensionSpacePoints | [{}]                                     |
      | nodeIdentifier                | "75106e9a-7dfb-4b48-8b7a-3c4ab2546b81"   |
      | parentNodeIdentifier          | "5387cb08-2aaf-44dc-a8a1-483497aa0a03"   |
      | nodeName                      | "text1"                                  |
      | propertyDefaultValuesAndTypes | {}                                       |
    And the graph projection is fully up to date
    When the command "ChangeNodeName" is executed with payload:
      | Key                     | Value                                  |
      | contentStreamIdentifier | "c75ae6a2-7254-4d42-a31b-a629e264069d" |
      | nodeIdentifier          | "75106e9a-7dfb-4b48-8b7a-3c4ab2546b81" |
      | newNodeName             | "text1modified"                        |
    And the graph projection is fully up to date

    When I am in content stream "c75ae6a2-7254-4d42-a31b-a629e264069d" and Dimension Space Point {}
    Then I expect the node aggregate "root" to have the following child nodes:
      | Name          | NodeIdentifier                       |
      | text1modified | 75106e9a-7dfb-4b48-8b7a-3c4ab2546b81 |