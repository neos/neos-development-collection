@contentrepository @adapters=DoctrineDBAL
Feature: Change node name

  As a user of the CR I want to change the name of a hierarchical relation between two nodes (e.g. in taxonomies)

  These are the base test cases for the NodeAggregateCommandHandler to block invalid commands.

  Background:
    Given using the following content dimensions:
      | Identifier | Values                      | Generalizations                      |
      | example    | general, source, spec, peer | spec->source->general, peer->general |
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
    And I am in workspace "live" and dimension space point {"example":"source"}

    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId        | nodeName | nodeTypeName                            | parentNodeAggregateId  | tetheredDescendantNodeAggregateIds |
      | sir-david-nodenborough | null     | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | {"tethered": "nodewyn-tetherton"}  |
      | nody-mc-nodeface       | occupied | Neos.ContentRepository.Testing:Document | sir-david-nodenborough | {}                                 |

  Scenario: Try to rename a node aggregate in a non-existing workspace
    When the command ChangeNodeAggregateName is executed with payload and exceptions are caught:
      | Key             | Value                    |
      | workspaceName   | "i-do-not-exist"         |
      | nodeAggregateId | "sir-david-nodenborough" |
      | newNodeName     | "new-name"               |
    Then the last command should have thrown an exception of type "WorkspaceDoesNotExist"

  Scenario: Try to rename a node aggregate in a workspace whose content stream is closed:
    When the command CloseContentStream is executed with payload:
      | Key             | Value           |
      | contentStreamId | "cs-identifier" |
    When the command ChangeNodeAggregateName is executed with payload and exceptions are caught:
      | Key             | Value                    |
      | nodeAggregateId | "sir-david-nodenborough" |
      | newNodeName     | "new-name"               |
    Then the last command should have thrown an exception of type "ContentStreamIsClosed"

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

  Scenario: Try to rename a node aggregate using an already covered name
    When the command ChangeNodeAggregateName is executed with payload and exceptions are caught:
      | Key             | Value              |
      | nodeAggregateId | "nody-mc-nodeface" |
      | newNodeName     | "tethered"         |
    Then the last command should have thrown an exception of type "NodeNameIsAlreadyCovered"

  Scenario: Try to rename a node aggregate using a partially covered name
    # Could happen via creation or move with the same effect
    Given the command CreateNodeVariant is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "sir-david-nodenborough" |
      | sourceOrigin    | {"example": "source"}    |
      | targetOrigin    | {"example": "peer"}      |
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                     |
      | nodeAggregateId           | "sir-nodeward-nodington-iii"              |
      | originDimensionSpacePoint | {"example": "peer"}                       |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Document" |
      | parentNodeAggregateId     | "sir-david-nodenborough"                  |
      | nodeName                  | "esquire"                                 |
    When the command ChangeNodeAggregateName is executed with payload and exceptions are caught:
      | Key             | Value              |
      | nodeAggregateId | "nody-mc-nodeface" |
      | newNodeName     | "esquire"          |
    Then the last command should have thrown an exception of type "NodeNameIsAlreadyCovered"

  Scenario: Try to rename a node aggregate using a name of a not yet existent, tethered child
    Given I change the node types in content repository "default" to:
    """yaml
    'Neos.ContentRepository.Testing:Content': []
    'Neos.ContentRepository.Testing:Document':
      childNodes:
        tethered:
          type: 'Neos.ContentRepository.Testing:Content'
        another-tethered:
          type: 'Neos.ContentRepository.Testing:Content'
    """
    # We don't run structure adjustments here on purpose
    When the command ChangeNodeAggregateName is executed with payload and exceptions are caught:
      | Key             | Value              |
      | nodeAggregateId | "nody-mc-nodeface" |
      | newNodeName     | "another-tethered" |
    Then the last command should have thrown an exception of type "NodeNameIsAlreadyCovered"
