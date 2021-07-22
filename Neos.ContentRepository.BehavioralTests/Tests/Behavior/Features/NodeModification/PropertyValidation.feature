@fixtures
Feature: Validate initial and default properties

  As a user of the CR I want declared properties to be validated on node instantiation

  Background:
    Given I have no content dimensions
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Intermediary.Testing:Node':
      properties:
        postalAddress:
          type: 'Neos\EventSourcedContentRepository\Tests\Behavior\Fixtures\PostalAddress'
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

  Scenario: Try to set a property with a value of a wrong type
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                           | Value                                              |
      | contentStreamIdentifier       | "cs-identifier"                                    |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                                 |
      | nodeTypeName                  | "Neos.ContentRepository.Intermediary.Testing:Node" |
      | originDimensionSpacePoint     | {}                                                 |
      | initiatingUserIdentifier      | "initiating-user-identifier"                       |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                           |
    And the graph projection is fully up to date
    And the intermediary command SetNodeProperties is executed with payload and exceptions are caught:
      | Key                       | Value                                           |
      | contentStreamIdentifier   | "cs-identifier"                                 |
      | nodeAggregateIdentifier   | "nody-mc-nodeface"                              |
      | originDimensionSpacePoint | {}                                              |
      | propertyValues            | {"postalAddress": "28 31st of February Street"} |
      | initiatingUserIdentifier  | "initiating-user-identifier"                    |
    Then the last command should have thrown an exception of type "PropertyCannotBeSet" with code 1615466573

  Scenario: Try to set a property the node type does not declare
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                           | Value                                              |
      | contentStreamIdentifier       | "cs-identifier"                                    |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                                 |
      | nodeTypeName                  | "Neos.ContentRepository.Intermediary.Testing:Node" |
      | originDimensionSpacePoint     | {}                                                 |
      | initiatingUserIdentifier      | "initiating-user-identifier"                       |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                           |
    And the graph projection is fully up to date
    And the intermediary command SetNodeProperties is executed with payload and exceptions are caught:
      | Key                       | Value                          |
      | contentStreamIdentifier   | "cs-identifier"                |
      | nodeAggregateIdentifier   | "nody-mc-nodeface"             |
      | originDimensionSpacePoint | {}                             |
      | propertyValues            | {"i-do-not-exist": "whatever"} |
      | initiatingUserIdentifier  | "initiating-user-identifier"   |
    Then the last command should have thrown an exception of type "PropertyCannotBeSet" with code 1615664798
