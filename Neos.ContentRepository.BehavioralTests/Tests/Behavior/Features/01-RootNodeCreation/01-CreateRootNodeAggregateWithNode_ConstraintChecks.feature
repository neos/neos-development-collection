@contentrepository @adapters=DoctrineDBAL,Postgres
Feature: Create a root node aggregate

  As a user of the CR I want to create a new root node aggregate with an initial node.

  This is the tale of venerable root node aggregate Lady Elenode Rootford already persistent in the content graph for quite some time
  and Nody McNodeface, a new root node aggregate to be added.

  Background: The stage is set
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:AbstractRoot':
      abstract: true
    'Neos.ContentRepository.Testing:NonRoot': []
    'Neos.ContentRepository.Testing:OtherRoot':
      superTypes:
        'Neos.ContentRepository:Root': true
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | workspaceTitle       | "Live"               |
      | workspaceDescription | "The live workspace" |
      | newContentStreamId   | "cs-identifier"      |
    And the graph projection is fully up to date
    And I am in the active content stream of workspace "live"
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date

  Scenario: Try to create a root node aggregate in a workspace that currently does not exist:
    When the command CreateRootNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key             | Value                         |
      | workspaceName   | "i-do-not-exist"              |
      | nodeAggregateId | "nody-mc-nodeface"            |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    Then the last command should have thrown an exception of type "WorkspaceDoesNotExist"

  Scenario: Try to create a root node aggregate in a closed content stream:
    When the command CloseContentStream is executed with payload:
      | Key             | Value           |
      | contentStreamId | "cs-identifier" |
    And the command CreateRootNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key             | Value                                      |
      | nodeAggregateId | "nody-mc-nodeface"                         |
      | nodeTypeName    | "Neos.ContentRepository.Testing:OtherRoot" |
    Then the last command should have thrown an exception of type "ContentStreamIsClosed"

  Scenario: Try to create a root node aggregate in a content stream where it is already present:
    When the command CreateRootNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    Then the last command should have thrown an exception of type "NodeAggregateCurrentlyExists"

  Scenario: Try to create a root node aggregate in a content stream where a root node of its type is already present:
    When the command CreateRootNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key             | Value                         |
      | nodeAggregateId | "nody-mc-nodeface"            |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    Then the last command should have thrown an exception of type "RootNodeAggregateTypeIsAlreadyOccupied"

  Scenario: Try to create a root node aggregate of an abstract root node type:
    When the command CreateRootNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key             | Value                                         |
      | nodeAggregateId | "nody-mc-nodeface"                            |
      | nodeTypeName    | "Neos.ContentRepository.Testing:AbstractRoot" |
    Then the last command should have thrown an exception of type "NodeTypeIsAbstract"

  Scenario: Try to create a root node aggregate of a non-root node type:
    When the command CreateRootNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key             | Value                                    |
      | nodeAggregateId | "nody-mc-nodeface"                       |
      | nodeTypeName    | "Neos.ContentRepository.Testing:NonRoot" |
    Then the last command should have thrown an exception of type "NodeTypeIsNotOfTypeRoot"
