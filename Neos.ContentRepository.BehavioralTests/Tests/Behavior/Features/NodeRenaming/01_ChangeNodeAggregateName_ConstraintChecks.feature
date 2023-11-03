@contentrepository @adapters=DoctrineDBAL
Feature: Change node name

  As a user of the CR I want to change the name of a hierarchical relation between two nodes (e.g. in taxonomies)

  These are the base test cases for the NodeAggregateCommandHandler to block invalid commands.

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Content': []
    'Neos.ContentRepository.Testing:Document':
      childNodes:
        tethered:
          type: 'Neos.ContentRepository.Testing:Content'
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | workspaceTitle       | "Live"               |
      | workspaceDescription | "The live workspace" |
      | newContentStreamId   | "cs-identifier"      |
    And the graph projection is fully up to date
    And I am in the active content stream of workspace "live" and dimension space point {}

    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId        | nodeName | nodeTypeName                            | parentNodeAggregateId  | initialPropertyValues | tetheredDescendantNodeAggregateIds |
      | sir-david-nodenborough | null     | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | {}                    | {"tethered": "nodewyn-tetherton"}  |
      | nody-mc-nodeface       | occupied | Neos.ContentRepository.Testing:Document | sir-david-nodenborough | {}                    | {}                                 |

  Scenario: Try to rename a node aggregate in a non-existing content stream
    When the command ChangeNodeAggregateName is executed with payload and exceptions are caught:
      | Key             | Value                    |
      | contentStreamId | "i-do-not-exist"         |
      | nodeAggregateId | "sir-david-nodenborough" |
      | newNodeName     | "new-name"               |
    Then the last command should have thrown an exception of type "ContentStreamDoesNotExistYet"

  Scenario: Try to rename a non-existing node aggregate
    When the command ChangeNodeAggregateName is executed with payload and exceptions are caught:
      | Key             | Value            |
      | nodeAggregateId | "i-do-not-exist" |
      | newNodeName     | "new-name"       |
    Then the last command should have thrown an exception of type "NodeAggregateCurrentlyDoesNotExist"

  Scenario: Try to rename a root node aggregate
    When the command ChangeNodeAggregateName is executed with payload and exceptions are caught:
      | Key             | Value                    |
      | nodeAggregateId | "lady-eleonode-rootford" |
      | newNodeName     | "new-name"               |
    Then the last command should have thrown an exception of type "NodeAggregateIsRoot"

  Scenario: Try to rename a tethered node aggregate
    When the command ChangeNodeAggregateName is executed with payload and exceptions are caught:
      | Key             | Value               |
      | nodeAggregateId | "nodewyn-tetherton" |
      | newNodeName     | "new-name"          |
    Then the last command should have thrown an exception of type "NodeAggregateIsTethered"

  Scenario: Try to rename a node aggregate using an already occupied name
    When the command ChangeNodeAggregateName is executed with payload and exceptions are caught:
      | Key             | Value              |
      | nodeAggregateId | "nody-mc-nodeface" |
      | newNodeName     | "tethered"         |
    Then the last command should have thrown an exception of type "NodeNameIsAlreadyOccupied"
