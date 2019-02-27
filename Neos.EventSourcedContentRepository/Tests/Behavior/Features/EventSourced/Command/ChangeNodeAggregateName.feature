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
