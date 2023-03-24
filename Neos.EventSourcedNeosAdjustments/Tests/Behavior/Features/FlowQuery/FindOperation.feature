@fixtures
Feature: The FlowQuery find operation

  As a user of the CR I want to be able to find nodes from a given start point in the content graph

  Background:
    Given I have no content dimensions
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:NodeWithTetheredChildNodes':
      childNodes:
        tethered:
          type: Neos.ContentRepository.Testing:Tethered
    'Neos.ContentRepository.Testing:Tethered': []
    'Neos.ContentRepository.Testing:Document': []
    """
    And the event RootWorkspaceWasCreated was published with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | workspaceTitle       | "Live"               |
      | workspaceDescription | "The live workspace" |
      | newContentStreamId   | "cs-identifier"      |
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                         |
      | contentStreamId             | "cs-identifier"               |
      | nodeAggregateId             | "lady-eleonode-rootford"      |
      | nodeTypeName                | "Neos.ContentRepository:Root" |
      | coveredDimensionSpacePoints | [{}]                          |
      | nodeAggregateClassification | "root"                        |
    And the graph projection is fully up to date

  Scenario: Find a tethered child node, e.g. via q(node).find('tethered')
    When the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                                | Value                                                       |
      | contentStreamId                    | "cs-identifier"                                             |
      | nodeAggregateId                    | "sir-david-nodenborough"                                    |
      | nodeTypeName                       | "Neos.ContentRepository.Testing:NodeWithTetheredChildNodes" |
      | originDimensionSpacePoint          | {}                                                          |
      | parentNodeAggregateId              | "lady-eleonode-rootford"                                    |
      | tetheredDescendantNodeAggregateIds | {"tethered": "nodewyn-tetherton"}                           |
    And the graph projection is fully up to date

    When I am in content stream "cs-identifier" and Dimension Space Point {}
    And I have a FlowQuery with node "sir-david-nodenborough"
    And I call FlowQuery operation "find" with argument "tethered"
    Then I expect a node identified by aggregate identifier "nodewyn-tetherton" to exist in the FlowQuery context

  Scenario: Find named descendant, e.g. via q(node).find('parent/child')
    When the following intermediary CreateNodeAggregateWithNode commands are executed for content stream "cs-identifier" and origin "{}":
      | nodeAggregateId        | parentNodeAggregateId  | nodeTypeName                            | nodeName |
      | sir-david-nodenborough | lady-eleonode-rootford | Neos.ContentRepository.Testing:Document | parent   |
      | nody-mc-nodeface       | sir-david-nodenborough | Neos.ContentRepository.Testing:Document | child    |

    When I am in content stream "cs-identifier" and Dimension Space Point {}
    And I have a FlowQuery with node "lady-eleonode-rootford"
    And I call FlowQuery operation "find" with argument "parent/child"
    Then I expect a node identified by aggregate identifier "nody-mc-nodeface" to exist in the FlowQuery context

  Scenario: Find named node by absolute path, e.g. via q(node).find('/parent/child')
    When the following intermediary CreateNodeAggregateWithNode commands are executed for content stream "cs-identifier" and origin "{}":
      | nodeAggregateId        | parentNodeAggregateId  | nodeTypeName                            | nodeName |
      | sir-david-nodenborough | lady-eleonode-rootford | Neos.ContentRepository.Testing:Document | parent   |
      | nody-mc-nodeface       | sir-david-nodenborough | Neos.ContentRepository.Testing:Document | child    |

    When I am in content stream "cs-identifier" and Dimension Space Point {}
    And I have a FlowQuery with node "sir-david-nodenborough"
    And I call FlowQuery operation "find" with argument "/parent/child"
    Then I expect a node identified by aggregate identifier "nody-mc-nodeface" to exist in the FlowQuery context

  Scenario: Find node by identifier, e.g. via q(node).find('#nody-mc-nodeface')
    When the following intermediary CreateNodeAggregateWithNode commands are executed for content stream "cs-identifier" and origin "{}":
      | nodeAggregateId        | parentNodeAggregateId  | nodeTypeName                            | nodeName |
      | sir-david-nodenborough | lady-eleonode-rootford | Neos.ContentRepository.Testing:Document | parent   |
      | nody-mc-nodeface       | sir-david-nodenborough | Neos.ContentRepository.Testing:Document | child    |

    When I am in content stream "cs-identifier" and Dimension Space Point {}
    And I have a FlowQuery with node "lady-eleonode-rootford"
    And I call FlowQuery operation "find" with argument "#nody-mc-nodeface"
    Then I expect a node identified by aggregate identifier "nody-mc-nodeface" to exist in the FlowQuery context

  Scenario: Find nodes by node type, e.g. via q(node).find('[instanceof Neos.ContentRepository.Testing:Document]')

    When the following intermediary CreateNodeAggregateWithNode commands are executed for content stream "cs-identifier" and origin "{}":
      | nodeAggregateId            | parentNodeAggregateId      | nodeTypeName                                              |
      | sir-david-nodenborough     | lady-eleonode-rootford     | Neos.ContentRepository.Testing:Document                   |
      | sir-nodeward-nodington-iii | lady-eleonode-rootford     | Neos.ContentRepository.Testing:Document                   |
      | albert-nodesworth          | sir-david-nodenborough     | Neos.ContentRepository.Testing:Document                   |
      | berta-nodesworth           | sir-nodeward-nodington-iii | Neos.ContentRepository.Testing:Document                   |
      | carl-nodesworth            | sir-david-nodenborough     | Neos.ContentRepository.Testing:NodeWithTetheredChildNodes |

    When I am in content stream "cs-identifier" and Dimension Space Point {}

    And I have a FlowQuery with node "sir-david-nodenborough"
    And I call FlowQuery operation "find" with argument "[instanceof Neos.ContentRepository.Testing:Document]"
    Then I expect the FlowQuery context to consist of exactly 1 item
    And I expect a node identified by aggregate identifier "albert-nodesworth" to exist in the FlowQuery context

    And I have a FlowQuery with node "sir-david-nodenborough"
    And I call FlowQuery operation "find" with argument "[instanceof Neos.ContentRepository.Testing:Document],[instanceof Neos.ContentRepository.Testing:NodeWithTetheredChildNodes]"
    Then I expect the FlowQuery context to consist of exactly 2 items
    And I expect a node identified by aggregate identifier "albert-nodesworth" to exist in the FlowQuery context
    And I expect a node identified by aggregate identifier "carl-nodesworth" to exist in the FlowQuery context
