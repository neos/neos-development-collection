@contentrepository @adapters=DoctrineDBAL
Feature: Change node name

  As a user of the CR I want to change the name of a hierarchical relation between two nodes (e.g. in taxonomies)

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Content': []
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value                |
      | workspaceName              | "live"               |
      | workspaceTitle             | "Live"               |
      | workspaceDescription       | "The live workspace" |
      | newContentStreamId | "cs-identifier"      |
    And the graph projection is fully up to date

    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                         | Value                         |
      | contentStreamId     | "cs-identifier"               |
      | nodeAggregateId     | "lady-eleonode-rootford"      |
      | nodeTypeName                | "Neos.ContentRepository:Root" |

  Scenario: Change node name of content node
    Given the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                    |
      | contentStreamId       | "cs-identifier"                          |
      | nodeAggregateId       | "nody-mc-nodeface"                       |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content" |
      | originDimensionSpacePoint     | {}                                       |
      | coveredDimensionSpacePoints   | [{}]                                     |
      | parentNodeAggregateId | "lady-eleonode-rootford"                 |
      | nodeName                      | "dog"                                    |
      | nodeAggregateClassification   | "regular"                                |

    And the graph projection is fully up to date
    When the command "ChangeNodeAggregateName" is executed with payload:
      | Key                      | Value                        |
      | contentStreamId  | "cs-identifier"              |
      | nodeAggregateId  | "nody-mc-nodeface"           |
      | newNodeName              | "cat"                        |

    Then I expect exactly 4 events to be published on stream with prefix "ContentStream:cs-identifier"
    And event at index 3 is of type "NodeAggregateNameWasChanged" with payload:
      | Key                     | Expected           |
      | contentStreamId | "cs-identifier"    |
      | nodeAggregateId | "nody-mc-nodeface" |
      | newNodeName             | "cat"              |

  Scenario: Change node name actually updates projection
    Given the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                    |
      | contentStreamId       | "cs-identifier"                          |
      | nodeAggregateId       | "nody-mc-nodeface"                       |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content" |
      | originDimensionSpacePoint     | {}                                       |
      | coveredDimensionSpacePoints   | [{}]                                     |
      | parentNodeAggregateId | "lady-eleonode-rootford"                 |
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
      | contentStreamId  | "cs-identifier"              |
      | nodeAggregateId  | "nody-mc-nodeface"           |
      | newNodeName              | "cat"                        |
    And the graph projection is fully up to date

    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    Then I expect this node to have the following child nodes:
      | Name | NodeDiscriminator                 |
      | cat  | cs-identifier;nody-mc-nodeface;{} |

