@fixtures
Feature: Provide and configure a custom read model

  As a user of the CR I want to provide and configure a custom node-based read model.
  These are the test cases for checking against constraints

  Background:
    Given I have no content dimensions
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Intermediary.Testing:NodeWithMissingReadModel':
      class: 'I\Do\Not\Exist'
    'Neos.ContentRepository.Intermediary.Testing:NodeWithInvalidReadModel':
      class: 'Neos\ContentRepository\Intermediary\Tests\Fixtures\InvalidReadModel'
    'Neos.ContentRepository.Intermediary.Testing:NodeWithDeprecatedReadModel':
      class: 'Neos\ContentRepository\Intermediary\Tests\Fixtures\DeprecatedReadModel'
    'Neos.ContentRepository.Intermediary.Testing:NodeWithMissingPropertyCollectionModel':
      propertyCollectionClass: 'I\Do\Not\Exist'
    'Neos.ContentRepository.Intermediary.Testing:NodeWithInvalidPropertyCollectionModel':
      propertyCollectionClass: 'Neos\ContentRepository\Intermediary\Tests\Fixtures\InvalidPropertyCollection'
    'Neos.ContentRepository.Intermediary.Testing:NodeWithInvalidPropertyType':
      properties:
        foo:
          type: 'I\Do\Not\Exist'
    """
    And the event RootWorkspaceWasCreated was published with payload:
      | Key                        | Value                                  |
      | workspaceName              | "live"                                 |
      | workspaceTitle             | "Live"                                 |
      | workspaceDescription       | "The live workspace"                   |
      | initiatingUserIdentifier   | "00000000-0000-0000-0000-000000000000" |
      | newContentStreamIdentifier | "cs-identifier"                        |
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                         |
      | contentStreamIdentifier     | "cs-identifier"               |
      | nodeAggregateIdentifier     | "lady-eleonode-rootford"      |
      | nodeTypeName                | "Neos.ContentRepository:Root" |
      | coveredDimensionSpacePoints | [{}]                          |
      | initiatingUserIdentifier    | "initiating-user-identifier"  |
      | nodeAggregateClassification | "root"                        |
    And the graph projection is fully up to date

  Scenario: Try to instantiate a non-existing read model
    When the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                           | Value                                                                  |
      | contentStreamIdentifier       | "cs-identifier"                                                        |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                                                     |
      | nodeTypeName                  | "Neos.ContentRepository.Intermediary.Testing:NodeWithMissingReadModel" |
      | originDimensionSpacePoint     | {}                                                                     |
      | initiatingUserIdentifier      | "initiating-user-identifier"                                           |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                                               |
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and Dimension Space Point {}
    And the read model with node aggregate identifier "nody-mc-nodeface" is instantiated and exceptions are caught
    Then I expect the instantiation to have thrown an exception of type "NodeImplementationClassNameIsInvalid" with code 1615415122

  Scenario: Try to instantiate a read model not implementing the required interface
    When the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                           | Value                                                                  |
      | contentStreamIdentifier       | "cs-identifier"                                                        |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                                                     |
      | nodeTypeName                  | "Neos.ContentRepository.Intermediary.Testing:NodeWithInvalidReadModel" |
      | originDimensionSpacePoint     | {}                                                                     |
      | initiatingUserIdentifier      | "initiating-user-identifier"                                           |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                                               |
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and Dimension Space Point {}
    And the read model with node aggregate identifier "nody-mc-nodeface" is instantiated and exceptions are caught
    Then I expect the instantiation to have thrown an exception of type "NodeImplementationClassNameIsInvalid" with code 1615415501

  Scenario: Try to instantiate a read model implementing the deprecated node interface
    When the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                           | Value                                                                     |
      | contentStreamIdentifier       | "cs-identifier"                                                           |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                                                        |
      | nodeTypeName                  | "Neos.ContentRepository.Intermediary.Testing:NodeWithDeprecatedReadModel" |
      | originDimensionSpacePoint     | {}                                                                        |
      | initiatingUserIdentifier      | "initiating-user-identifier"                                              |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                                                  |
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and Dimension Space Point {}
    And the read model with node aggregate identifier "nody-mc-nodeface" is instantiated and exceptions are caught
    Then I expect the instantiation to have thrown an exception of type "NodeImplementationClassNameIsInvalid" with code 1615415586

  Scenario: Try to instantiate a read model with a non-existing content collection implementation
    When the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                           | Value                                                                                |
      | contentStreamIdentifier       | "cs-identifier"                                                                      |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                                                                   |
      | nodeTypeName                  | "Neos.ContentRepository.Intermediary.Testing:NodeWithMissingPropertyCollectionModel" |
      | originDimensionSpacePoint     | {}                                                                                   |
      | initiatingUserIdentifier      | "initiating-user-identifier"                                                         |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                                                             |
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and Dimension Space Point {}
    And the read model with node aggregate identifier "nody-mc-nodeface" is instantiated and exceptions are caught
    Then I expect the instantiation to have thrown an exception of type "PropertyCollectionImplementationClassNameIsInvalid" with code 1615416178

  Scenario: Try to instantiate a read model not implementing the required interface
    When the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                           | Value                                                                                |
      | contentStreamIdentifier       | "cs-identifier"                                                                      |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                                                                   |
      | nodeTypeName                  | "Neos.ContentRepository.Intermediary.Testing:NodeWithInvalidPropertyCollectionModel" |
      | originDimensionSpacePoint     | {}                                                                                   |
      | initiatingUserIdentifier      | "initiating-user-identifier"                                                         |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                                                             |
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and Dimension Space Point {}
    And the read model with node aggregate identifier "nody-mc-nodeface" is instantiated and exceptions are caught
    Then I expect the instantiation to have thrown an exception of type "PropertyCollectionImplementationClassNameIsInvalid" with code 1615416214
