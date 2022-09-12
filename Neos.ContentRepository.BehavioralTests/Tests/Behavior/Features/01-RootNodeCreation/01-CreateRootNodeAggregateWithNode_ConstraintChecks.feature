@contentrepository @adapters=DoctrineDBAL,Postgres
Feature: Create a root node aggregate

  As a user of the CR I want to create a new root node aggregate with an initial node.

  This is the tale of venerable root node aggregate Lady Elenode Rootford already persistent in the content graph for quite some time
  and Nody McNodeface, a new root node aggregate to be added.

  Background: The stage is set
    Given I have no content dimensions
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:AbstractRoot':
      abstract: true
    'Neos.ContentRepository.Testing:NonRoot': []
    """
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value                |
      | workspaceName              | "live"               |
      | workspaceTitle             | "Live"               |
      | workspaceDescription       | "The live workspace" |
      | newContentStreamId | "cs-identifier"      |
      | initiatingUserId   | "user-id"            |
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier"
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                     | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName            | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date

  Scenario: Try to create a root node aggregate in a content stream that currently does not exist:
    When the command CreateRootNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                     | Value                         |
      | contentStreamId | "i-do-not-exist"              |
      | nodeAggregateId | "nody-mc-nodeface"            |
      | nodeTypeName            | "Neos.ContentRepository:Root" |
    Then the last command should have thrown an exception of type "ContentStreamDoesNotExistYet"

  Scenario: Try to create a root node aggregate in a content stream where it is already present:
    When the command CreateRootNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                     | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName            | "Neos.ContentRepository:Root" |
    Then the last command should have thrown an exception of type "NodeAggregateCurrentlyExists"

  Scenario: Try to create a root node aggregate of an abstract root node type:
    When the command CreateRootNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                     | Value                                         |
      | nodeAggregateId | "nody-mc-nodeface"                            |
      | nodeTypeName            | "Neos.ContentRepository.Testing:AbstractRoot" |
    Then the last command should have thrown an exception of type "NodeTypeIsAbstract"

  Scenario: Try to create a root node aggregate of a non-root node type:
    When the command CreateRootNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                     | Value                                    |
      | nodeAggregateId | "nody-mc-nodeface"                       |
      | nodeTypeName            | "Neos.ContentRepository.Testing:NonRoot" |
    Then the last command should have thrown an exception of type "NodeTypeIsNotOfTypeRoot"
