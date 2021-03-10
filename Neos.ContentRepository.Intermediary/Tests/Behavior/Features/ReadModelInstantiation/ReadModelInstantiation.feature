@fixtures
Feature: Provide and configure a custom read model

  As a user of the CR I want to provide and configure a custom node-based read model.

  Background:
    Given I have no content dimensions
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Intermediary.Testing:NodeWithCustomReadModel':
      class:
      properties:
        text:
          defaultValue: 'my default'
          type: string
    """
    And the event RootWorkspaceWasCreated was published with payload:
      | Key                            | Value                                  |
      | workspaceName                  | "live"                                 |
      | workspaceTitle                 | "Live"                                 |
      | workspaceDescription           | "The live workspace"                   |
      | initiatingUserIdentifier       | "00000000-0000-0000-0000-000000000000" |
      | newContentStreamIdentifier     | "cs-identifier"                        |
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                                                      |
      | contentStreamIdentifier     | "cs-identifier"                                                            |
      | nodeAggregateIdentifier     | "sir-david-nodenborough"                                                   |
      | nodeTypeName                | "Neos.ContentRepository:Root"                                              |
      | coveredDimensionSpacePoints | [{"language":"mul"},{"language":"de"},{"language":"en"},{"language":"ch"}] |
      | initiatingUserIdentifier    | "00000000-0000-0000-0000-000000000000"                                     |
      | nodeAggregateClassification | "root"                                                                     |
    And the graph projection is fully up to date

  Scenario:  Create node aggregate with node with content dimensions
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                           | Value                                                          |
      | contentStreamIdentifier       | "cs-identifier"                                                |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                                             |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes" |
      | originDimensionSpacePoint     | {"language": "de"}                                             |
      | initiatingUserIdentifier      | "00000000-0000-0000-0000-000000000000"                         |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                                       |
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and Dimension Space Point {"language": "de"}

    Then I expect exactly 3 events to be published on stream "Neos.ContentRepository:ContentStream:cs-identifier"
    And event at index 2 is of type "Neos.EventSourcedContentRepository:NodeAggregateWithNodeWasCreated" with payload:
      | Key                           | Expected                                                       |
      | contentStreamIdentifier       | "cs-identifier"                                                |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                                             |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:NodeWithoutTetheredChildNodes" |
      | originDimensionSpacePoint     | {"language": "de"}                                             |
      | coveredDimensionSpacePoints   | [{"language":"de"},{"language":"ch"}]                          |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                                       |
      | initialPropertyValues         | {"text": {"value": "my default", "type": "string"}}            |
      | nodeAggregateClassification   | "regular"                                                      |

    And I expect a node identified by aggregate identifier "nody-mc-nodeface" to exist in the subgraph
