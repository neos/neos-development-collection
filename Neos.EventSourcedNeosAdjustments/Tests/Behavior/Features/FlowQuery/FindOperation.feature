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
      | Key                            | Value                                  |
      | workspaceName                  | "live"                                 |
      | workspaceTitle                 | "Live"                                 |
      | workspaceDescription           | "The live workspace"                   |
      | initiatingUserIdentifier       | "00000000-0000-0000-0000-000000000000" |
      | newContentStreamIdentifier     | "cs-identifier"                        |
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                  |
      | contentStreamIdentifier     | "cs-identifier"                        |
      | nodeAggregateIdentifier     | "lady-eleonode-rootford"               |
      | nodeTypeName                | "Neos.ContentRepository:Root"          |
      | coveredDimensionSpacePoints | [{}]                                   |
      | initiatingUserIdentifier    | "00000000-0000-0000-0000-000000000000" |
      | nodeAggregateClassification | "root"                                 |
    And the graph projection is fully up to date

  Scenario: Find a tethered child node, e.g. via q(node).find('tethered')
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                                        | Value                                                       |
      | contentStreamIdentifier                    | "cs-identifier"                                             |
      | nodeAggregateIdentifier                    | "sir-david-nodenborough"                                    |
      | nodeTypeName                               | "Neos.ContentRepository.Testing:NodeWithTetheredChildNodes" |
      | originDimensionSpacePoint                  | {}                                                          |
      | initiatingUserIdentifier                   | "00000000-0000-0000-0000-000000000000"                      |
      | parentNodeAggregateIdentifier              | "lady-eleonode-rootford"                                    |
      | tetheredDescendantNodeAggregateIdentifiers | {"tethered": "nodewyn-tetherton"}                           |
    And the graph projection is fully up to date

    When I am in content stream "cs-identifier" and Dimension Space Point {}
    And I have a FlowQuery with node "sir-david-nodenborough"
    And I call FlowQuery operation "find" with argument "tethered"
    Then I expect a node identified by aggregate identifier "nodewyn-tetherton" to exist in the FlowQuery context

  Scenario: Find named descendant, e.g. via q(node).find('parent/child')
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                           | Value                                     |
      | contentStreamIdentifier       | "cs-identifier"                           |
      | nodeAggregateIdentifier       | "sir-david-nodenborough"                  |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | nodeName                      | "parent"                                  |
      | originDimensionSpacePoint     | {}                                        |
      | initiatingUserIdentifier      | "00000000-0000-0000-0000-000000000000"    |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                  |
    And the graph projection is fully up to date
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                           | Value                                     |
      | contentStreamIdentifier       | "cs-identifier"                           |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                        |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | nodeName                      | "child"                                   |
      | originDimensionSpacePoint     | {}                                        |
      | initiatingUserIdentifier      | "00000000-0000-0000-0000-000000000000"    |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                  |
    And the graph projection is fully up to date

    When I am in content stream "cs-identifier" and Dimension Space Point {}
    And I have a FlowQuery with node "lady-eleonode-rootford"
    And I call FlowQuery operation "find" with argument "parent/child"
    Then I expect a node identified by aggregate identifier "nody-mc-nodeface" to exist in the FlowQuery context

  Scenario: Find named node by absolute path, e.g. via q(node).find('/parent/child')
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                           | Value                                     |
      | contentStreamIdentifier       | "cs-identifier"                           |
      | nodeAggregateIdentifier       | "sir-david-nodenborough"                  |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | nodeName                      | "parent"                                  |
      | originDimensionSpacePoint     | {}                                        |
      | initiatingUserIdentifier      | "00000000-0000-0000-0000-000000000000"    |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                  |
    And the graph projection is fully up to date
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                           | Value                                     |
      | contentStreamIdentifier       | "cs-identifier"                           |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                        |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | nodeName                      | "child"                                   |
      | originDimensionSpacePoint     | {}                                        |
      | initiatingUserIdentifier      | "00000000-0000-0000-0000-000000000000"    |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                  |
    And the graph projection is fully up to date

    When I am in content stream "cs-identifier" and Dimension Space Point {}
    And I have a FlowQuery with node "sir-david-nodenborough"
    And I call FlowQuery operation "find" with argument "/parent/child"
    Then I expect a node identified by aggregate identifier "nody-mc-nodeface" to exist in the FlowQuery context

  Scenario: Find node by identifier, e.g. via q(node).find('#nody-mc-nodeface')
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                           | Value                                     |
      | contentStreamIdentifier       | "cs-identifier"                           |
      | nodeAggregateIdentifier       | "sir-david-nodenborough"                  |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | nodeName                      | "parent"                                  |
      | originDimensionSpacePoint     | {}                                        |
      | initiatingUserIdentifier      | "00000000-0000-0000-0000-000000000000"    |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                  |
    And the graph projection is fully up to date
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                           | Value                                     |
      | contentStreamIdentifier       | "cs-identifier"                           |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                        |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | nodeName                      | "child"                                   |
      | originDimensionSpacePoint     | {}                                        |
      | initiatingUserIdentifier      | "00000000-0000-0000-0000-000000000000"    |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                  |
    And the graph projection is fully up to date

    When I am in content stream "cs-identifier" and Dimension Space Point {}
    And I have a FlowQuery with node "lady-eleonode-rootford"
    And I call FlowQuery operation "find" with argument "#nody-mc-nodeface"
    Then I expect a node identified by aggregate identifier "nody-mc-nodeface" to exist in the FlowQuery context

    Scenario: Find nodes by node type, e.g. via q(node).find('[instanceof Neos.ContentRepository.Testing:Document]')
      When the command CreateNodeAggregateWithNode is executed with payload:
        | Key                           | Value                                     |
        | contentStreamIdentifier       | "cs-identifier"                           |
        | nodeAggregateIdentifier       | "sir-david-nodenborough"                  |
        | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
        | originDimensionSpacePoint     | {}                                        |
        | initiatingUserIdentifier      | "00000000-0000-0000-0000-000000000000"    |
        | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                  |
      And the command CreateNodeAggregateWithNode is executed with payload:
        | Key                           | Value                                     |
        | contentStreamIdentifier       | "cs-identifier"                           |
        | nodeAggregateIdentifier       | "sir-nodeward-nodington-iii"              |
        | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
        | originDimensionSpacePoint     | {}                                        |
        | initiatingUserIdentifier      | "00000000-0000-0000-0000-000000000000"    |
        | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                  |
      And the graph projection is fully up to date
      And the command CreateNodeAggregateWithNode is executed with payload:
        | Key                           | Value                                     |
        | contentStreamIdentifier       | "cs-identifier"                           |
        | nodeAggregateIdentifier       | "albert-nodesworth"                       |
        | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
        | originDimensionSpacePoint     | {}                                        |
        | initiatingUserIdentifier      | "00000000-0000-0000-0000-000000000000"    |
        | parentNodeAggregateIdentifier | "sir-david-nodenborough"                  |
      And the command CreateNodeAggregateWithNode is executed with payload:
        | Key                           | Value                                     |
        | contentStreamIdentifier       | "cs-identifier"                           |
        | nodeAggregateIdentifier       | "berta-nodesworth"                       |
        | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
        | originDimensionSpacePoint     | {}                                        |
        | initiatingUserIdentifier      | "00000000-0000-0000-0000-000000000000"    |
        | parentNodeAggregateIdentifier | "sir-nodeward-nodington-iii"                  |
      And the command CreateNodeAggregateWithNode is executed with payload:
        | Key                           | Value                                     |
        | contentStreamIdentifier       | "cs-identifier"                           |
        | nodeAggregateIdentifier       | "carl-nodesworth"                       |
        | nodeTypeName                  | "Neos.ContentRepository.Testing:NodeWithTetheredChildNodes" |
        | originDimensionSpacePoint     | {}                                        |
        | initiatingUserIdentifier      | "00000000-0000-0000-0000-000000000000"    |
        | parentNodeAggregateIdentifier | "sir-david-nodenborough"                  |
      And the graph projection is fully up to date

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
