@fixtures @adapters=DoctrineDBAL,Postgres
Feature: Set node properties: Constraint checks

  As a user of the CR I want to modify node properties.

  Background:
    Given I have the following content dimensions:
      | Identifier | Default | Values  | Generalizations |
      | language   | de      | de, gsw | gsw->de         |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:Document':
      properties:
        text:
          type: string
        postalAddress:
          type: 'Neos\ContentRepository\Tests\Behavior\Fixtures\PostalAddress'
    """
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value                |
      | workspaceName              | "live"               |
      | workspaceTitle             | "Live"               |
      | workspaceDescription       | "The live workspace" |
      | newContentStreamIdentifier | "cs-identifier"      |
    And I am in content stream "cs-identifier" and dimension space point {"language":"de"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                     | Value                         |
      | nodeAggregateIdentifier | "lady-eleonode-rootford"      |
      | nodeTypeName            | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date
    # We have to add another node since root nodes have no dimension space points and thus cannot be varied
    # Node /document
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateIdentifier | nodeName | parentNodeAggregateIdentifier | nodeTypeName                            |
      | nody-mc-nodeface        | document | lady-eleonode-rootford        | Neos.ContentRepository.Testing:Document |

  Scenario: Try to set properties in a content stream that does not exist yet
    When the command SetNodeProperties is executed with payload and exceptions are caught:
      | Key                       | Value                |
      | contentStreamIdentifier   | "i-do-not-exist-yet" |
      | nodeAggregateIdentifier   | "nody-mc-nodeface"   |
      | originDimensionSpacePoint | {"language":"de"}    |
      | propertyValues            | {"text":"New text"}  |
    Then the last command should have thrown an exception of type "ContentStreamDoesNotExistYet"

  Scenario: Try to set properties on a node aggregate that currently does not exist
    When the command SetNodeProperties is executed with payload and exceptions are caught:
      | Key                       | Value                      |
      | nodeAggregateIdentifier   | "i-currently-do-not-exist" |
      | originDimensionSpacePoint | {"language":"de"}          |
      | propertyValues            | {"text":"New text"}        |
    Then the last command should have thrown an exception of type "NodeAggregateCurrentlyDoesNotExist"

  Scenario: Try to set properties on a root node aggregate
    When the command SetNodeProperties is executed with payload and exceptions are caught:
      | Key                       | Value                    |
      | nodeAggregateIdentifier   | "lady-eleonode-rootford" |
      | originDimensionSpacePoint | {"language":"de"}        |
      | propertyValues            | {}                       |
    Then the last command should have thrown an exception of type "NodeAggregateIsRoot"

  Scenario: Try to set properties in an origin dimension space point that does not exist
    When the command SetNodeProperties is executed with payload and exceptions are caught:
      | Key                       | Value               |
      | nodeAggregateIdentifier   | "nody-mc-nodeface"  |
      | originDimensionSpacePoint | {"language":"wat"}  |
      | propertyValues            | {"text":"New text"} |
    Then the last command should have thrown an exception of type "DimensionSpacePointNotFound"

  Scenario: Try to set properties in an origin dimension space point the node aggregate does not occupy
    When the command SetNodeProperties is executed with payload and exceptions are caught:
      | Key                       | Value               |
      | nodeAggregateIdentifier   | "nody-mc-nodeface"  |
      | originDimensionSpacePoint | {"language":"gsw"}  |
      | propertyValues            | {"text":"New text"} |
    Then the last command should have thrown an exception of type "DimensionSpacePointIsNotYetOccupied"

  Scenario: Try to set a property the node type does not declare
    When the command SetNodeProperties is executed with payload and exceptions are caught:
      | Key                     | Value                          |
      | nodeAggregateIdentifier | "nody-mc-nodeface"             |
      | propertyValues          | {"i-do-not-exist": "whatever"} |
    Then the last command should have thrown an exception of type "PropertyCannotBeSet" with code 1615664798

  Scenario: Try to set a property with a value of a wrong type
    When the command SetNodeProperties is executed with payload and exceptions are caught:
      | Key                     | Value                                           |
      | nodeAggregateIdentifier | "nody-mc-nodeface"                              |
      | propertyValues          | {"postalAddress": "28 31st of February Street"} |
    Then the last command should have thrown an exception of type "PropertyCannotBeSet" with code 1615466573
