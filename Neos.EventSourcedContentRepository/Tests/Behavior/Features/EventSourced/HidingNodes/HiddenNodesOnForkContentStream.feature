@fixtures
Feature: On forking a content stream, hidden nodes should be correctly copied as well.

  Because we store hidden node information in an extra DB table, this needs to be copied correctly on ForkContentStream
  as well.

  Background:
    Given I have no content dimensions
    And I have the following NodeTypes configuration:
    """
    Neos.ContentRepository:Root: {}
    'Neos.ContentRepository.Testing:Content':
      properties:
        text:
          type: string
    """
    And the command CreateRootWorkspace is executed with payload:
      | Key                     | Value           |
      | workspaceName           | "live"          |
      | contentStreamIdentifier | "cs-identifier" |
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                  |
      | contentStreamIdentifier       | "cs-identifier"                        |
      | nodeAggregateIdentifier       | "lady-eleonode-rootford"               |
      | nodeTypeName                  | "Neos.ContentRepository:Root"          |
      | visibleInDimensionSpacePoints | [{}]                                   |
      | initiatingUserIdentifier      | "00000000-0000-0000-0000-000000000000" |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                    |
      | contentStreamIdentifier       | "cs-identifier"                          |
      | nodeAggregateIdentifier       | "the-great-nodini"                       |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content" |
      | originDimensionSpacePoint     | {}                                       |
      | visibleInDimensionSpacePoints | [{}]                                     |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                 |
      | nodeName                      | "court-magician"                         |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                    |
      | contentStreamIdentifier       | "cs-identifier"                          |
      | nodeAggregateIdentifier       | "nodingers-cat"                          |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content" |
      | originDimensionSpacePoint     | {}                                       |
      | visibleInDimensionSpacePoints | [{}]                                     |
      | parentNodeAggregateIdentifier | "the-great-nodini"                       |
      | nodeName                      | "pet"                                    |
    And the graph projection is fully up to date
    And the command "HideNode" is executed with payload:
      | Key                          | Value              |
      | contentStreamIdentifier      | "cs-identifier"    |
      | nodeAggregateIdentifier      | "the-great-nodini" |
      | affectedDimensionSpacePoints | [{}]               |
    And the graph projection is fully up to date

  Scenario: on ForkContentStream, the hidden nodes in the target content stream should still be invisible.
    When the command "ForkContentStream" is executed with payload:
      | Key                           | Value                |
      | sourceContentStreamIdentifier | "cs-identifier"      |
      | contentStreamIdentifier       | "user-cs-identifier" |
    And the graph projection is fully up to date

    When I am in content stream "user-cs-identifier" and Dimension Space Point {}

    Then I expect a node identified by aggregate identifier "the-great-nodini" not to exist in the subgraph
    Then I expect a node identified by aggregate identifier "nodingers-cat" not to exist in the subgraph
