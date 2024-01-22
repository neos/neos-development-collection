@contentrepository @adapters=DoctrineDBAL,Postgres
Feature: Set node properties: Constraint checks

  As a user of the CR I want to modify node properties.

  Background:
    Given using the following content dimensions:
      | Identifier | Values  | Generalizations |
      | language   | de, gsw | gsw->de         |
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Document':
      properties:
        text:
          type: string
        postalAddress:
          type: 'Neos\ContentRepository\Core\Tests\Behavior\Fixtures\PostalAddress'
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value                |
      | workspaceName              | "live"               |
      | workspaceTitle             | "Live"               |
      | workspaceDescription       | "The live workspace" |
      | newContentStreamId | "cs-identifier"      |
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and dimension space point {"language":"de"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                     | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName            | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date
    # We have to add another node since root nodes have no dimension space points and thus cannot be varied
    # Node /document
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | nodeName | parentNodeAggregateId | nodeTypeName                            |
      | nody-mc-nodeface        | document | lady-eleonode-rootford        | Neos.ContentRepository.Testing:Document |

  Scenario: Try to set properties in a content stream that does not exist yet
    When the command SetNodeProperties is executed with payload and exceptions are caught:
      | Key                       | Value                |
      | contentStreamId   | "i-do-not-exist-yet" |
      | nodeAggregateId   | "nody-mc-nodeface"   |
      | originDimensionSpacePoint | {"language":"de"}    |
      | propertyValues            | {"text":"New text"}  |
    Then the last command should have thrown an exception of type "ContentStreamDoesNotExistYet"

  Scenario: Try to set properties on a node aggregate that currently does not exist
    When the command SetNodeProperties is executed with payload and exceptions are caught:
      | Key                       | Value                      |
      | nodeAggregateId   | "i-currently-do-not-exist" |
      | originDimensionSpacePoint | {"language":"de"}          |
      | propertyValues            | {"text":"New text"}        |
    Then the last command should have thrown an exception of type "NodeAggregateCurrentlyDoesNotExist"

  Scenario: Try to set properties on a root node aggregate
    When the command SetNodeProperties is executed with payload and exceptions are caught:
      | Key                       | Value                    |
      | nodeAggregateId   | "lady-eleonode-rootford" |
      | originDimensionSpacePoint | {"language":"de"}        |
      | propertyValues            | {}                       |
    Then the last command should have thrown an exception of type "NodeAggregateIsRoot"

  Scenario: Try to set properties in an origin dimension space point that does not exist
    When the command SetNodeProperties is executed with payload and exceptions are caught:
      | Key                       | Value               |
      | nodeAggregateId   | "nody-mc-nodeface"  |
      | originDimensionSpacePoint | {"language":"wat"}  |
      | propertyValues            | {"text":"New text"} |
    Then the last command should have thrown an exception of type "DimensionSpacePointNotFound"

  Scenario: Try to set properties in an origin dimension space point the node aggregate does not occupy
    When the command SetNodeProperties is executed with payload and exceptions are caught:
      | Key                       | Value               |
      | nodeAggregateId   | "nody-mc-nodeface"  |
      | originDimensionSpacePoint | {"language":"gsw"}  |
      | propertyValues            | {"text":"New text"} |
    Then the last command should have thrown an exception of type "DimensionSpacePointIsNotYetOccupied"

  Scenario: Try to set a property the node type does not declare
    When the command SetNodeProperties is executed with payload and exceptions are caught:
      | Key                     | Value                          |
      | nodeAggregateId | "nody-mc-nodeface"             |
      | propertyValues          | {"i-do-not-exist": "whatever"} |
    Then the last command should have thrown an exception of type "PropertyCannotBeSet" with code 1615664798

  Scenario: Try to set a property with a value of a wrong type
    When the command SetNodeProperties is executed with payload and exceptions are caught:
      | Key                     | Value                                           |
      | nodeAggregateId | "nody-mc-nodeface"                              |
      | propertyValues          | {"postalAddress": "28 31st of February Street"} |
    Then the last command should have thrown an exception of type "PropertyCannotBeSet" with code 1615466573
