@fixtures
Feature: Provide and configure a custom read model

  As a user of the CR I want to provide and configure a custom node-based read model.

  Background:
    Given I have no content dimensions
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Intermediary.Testing:NodeWithCustomReadModel':
      class: 'Neos\EventSourcedContentRepository\Tests\Behavior\Fixtures\Thing'
    """
    And the event RootWorkspaceWasCreated was published with payload:
      | Key                        | Value                                  |
      | workspaceName              | "live"                                 |
      | workspaceTitle             | "Live"                                 |
      | workspaceDescription       | "The live workspace"                   |
      | initiatingUserIdentifier   | "00000000-0000-0000-0000-000000000000" |
      | newContentStreamIdentifier | "cs-identifier"                        |
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                  |
      | contentStreamIdentifier     | "cs-identifier"                        |
      | nodeAggregateIdentifier     | "lady-eleonode-rootford"               |
      | nodeTypeName                | "Neos.ContentRepository:Root"          |
      | coveredDimensionSpacePoints | [{}]                                   |
      | initiatingUserIdentifier    | "00000000-0000-0000-0000-000000000000" |
      | nodeAggregateClassification | "root"                                 |
    And the graph projection is fully up to date

  Scenario: Instantiate a read model
    When the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                           | Value                                                                 |
      | contentStreamIdentifier       | "cs-identifier"                                                       |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                                                    |
      | nodeTypeName                  | "Neos.ContentRepository.Intermediary.Testing:NodeWithCustomReadModel" |
      | originDimensionSpacePoint     | {}                                                                    |
      | initiatingUserIdentifier      | "initiating-user-identifier"                                          |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                                              |
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and Dimension Space Point {}
    And the read model with node aggregate identifier "nody-mc-nodeface" is instantiated
    Then I expect this read model to be an instance of "Neos\EventSourcedContentRepository\Tests\Behavior\Fixtures\Thing"
