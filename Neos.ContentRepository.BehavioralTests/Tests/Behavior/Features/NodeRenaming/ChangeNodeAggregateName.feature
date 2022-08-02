@contentrepository @adapters=DoctrineDBAL
Feature: Change node name

  As a user of the CR I want to change the name of a hierarchical relation between two nodes (e.g. in taxonomies)

  Background:
    Given I have no content dimensions
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                         |
      | contentStreamIdentifier     | "cs-identifier"               |
      | nodeAggregateIdentifier     | "lady-eleonode-rootford"      |
      | nodeTypeName                | "Neos.ContentRepository:Root" |
      | coveredDimensionSpacePoints | [{}]                          |
      | initiatingUserIdentifier    | "system"                      |
      | nodeAggregateClassification | "root"                        |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:Content': []
    """

  Scenario: Change node name of content node
    Given the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                    |
      | contentStreamIdentifier       | "cs-identifier"                          |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                       |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content" |
      | originDimensionSpacePoint     | {}                                       |
      | coveredDimensionSpacePoints   | [{}]                                     |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                 |
      | nodeName                      | "dog"                                    |
      | nodeAggregateClassification   | "regular"                                |

    And the graph projection is fully up to date
    When the command "ChangeNodeAggregateName" is executed with payload:
      | Key                      | Value                        |
      | contentStreamIdentifier  | "cs-identifier"              |
      | nodeAggregateIdentifier  | "nody-mc-nodeface"           |
      | newNodeName              | "cat"                        |
      | initiatingUserIdentifier | "initiating-user-identifier" |

    Then I expect exactly 3 events to be published on stream with prefix "Neos.ContentRepository:ContentStream:cs-identifier"
    And event at index 2 is of type "NodeAggregateNameWasChanged" with payload:
      | Key                     | Expected           |
      | contentStreamIdentifier | "cs-identifier"    |
      | nodeAggregateIdentifier | "nody-mc-nodeface" |
      | newNodeName             | "cat"              |

  Scenario: Change node name actually updates projection
    Given the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                    |
      | contentStreamIdentifier       | "cs-identifier"                          |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                       |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content" |
      | originDimensionSpacePoint     | {}                                       |
      | coveredDimensionSpacePoints   | [{}]                                     |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                 |
      | nodeName                      | "dog"                                    |
      | nodeAggregateClassification   | "regular"                                |
    And the graph projection is fully up to date
    # we read the node initially, to ensure it is filled in the cache (to check whether cache clearing actually works)
    When I am in content stream "cs-identifier" and dimension space point {}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    Then I expect this node to have the following child nodes:
      | Name | NodeDiscriminator                 |
      | dog  | cs-identifier;nody-mc-nodeface;{} |

    When the command "ChangeNodeAggregateName" is executed with payload:
      | Key                      | Value                        |
      | contentStreamIdentifier  | "cs-identifier"              |
      | nodeAggregateIdentifier  | "nody-mc-nodeface"           |
      | newNodeName              | "cat"                        |
      | initiatingUserIdentifier | "initiating-user-identifier" |
    And the graph projection is fully up to date

    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    Then I expect this node to have the following child nodes:
      | Name | NodeDiscriminator                 |
      | cat  | cs-identifier;nody-mc-nodeface;{} |

