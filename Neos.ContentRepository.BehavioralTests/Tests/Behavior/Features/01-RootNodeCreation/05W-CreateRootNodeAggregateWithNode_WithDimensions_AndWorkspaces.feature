Feature: Create a root node aggregate

  As a user of the CR I want to create a new root node aggregate with an initial node.

  This is the tale of venerable root node aggregate Lady Eleonode Rootford already persistent in the content graph for quite some time
  and Nody McRootface, a new root node aggregate to be added.

  Background:
    Given using the following content dimensions:
      | Identifier | Values                       | Generalizations              |
      | example    | source, spec, peer, peerSpec | spec->source, peerSpec->peer |
    And using the following node types:
    """yaml
    'Neos.ContentRepository:AnotherRoot':
      superTypes:
        'Neos.ContentRepository:Root': true
    'Neos.ContentRepository:YetAnotherRoot':
      superTypes:
        'Neos.ContentRepository:Root': true
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"
    And I set up the edge case workspace tree
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                                | Value                                                                             |
      | workspaceName                      | "local"                                                                           |
      | nodeAggregateId                    | "lady-eleonode-rootford"                                                          |
      | nodeTypeName                       | "Neos.ContentRepository:Root"                       |
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                                | Value                                                                                 |
      | workspaceName                      | "local"                                                                               |
      | nodeAggregateId                    | "sire-frode-rootford"                                                                 |
      | nodeTypeName                       | "Neos.ContentRepository:AnotherRoot"                    |
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                                | Value                                                                               |
      | workspaceName                      | "local-3"                                                                           |
      | nodeAggregateId                    | "nody-mc-rootface"                                                                  |
      | nodeTypeName                       | "Neos.ContentRepository:YetAnotherRoot"               |

  Scenario: Publish root node creation
    When I memorise the global graph state
    And the command PublishWorkspace is executed with payload:
      | Key                | Value             |
      | workspaceName      | "local"           |
      | newContentStreamId | "new-local-cs-id" |
    And I expect the graph state for workspace "local" to be unchanged
    Then I expect the graph state for workspace "live" to be unchanged
    And I expect the graph state for workspace "local-2" to be unchanged
    And I expect the graph state for workspace "local-3" to be unchanged
    And I expect the graph state for workspace "intermediate" to equal that of workspace "local"

  Scenario: Partial Publishing
    Given the current date and time is "2026-05-11T14:19:00+00:00"
    And I memorise the global graph state
    And the command PublishIndividualNodesFromWorkspace is executed with payload:
      | Key                             | Value                      |
      | workspaceName                   | "local"                    |
      | nodesToPublish                  | ["lady-eleonode-rootford"] |
      | contentStreamIdForRemainingPart | "remaining-local-cs-id"    |
    And I expect the graph state for workspace "local" to be unchanged
    Then I expect the graph state for workspace "live" to be unchanged
    And I expect the graph state for workspace "local-2" to be unchanged
    And I expect the graph state for workspace "local-3" to be unchanged
    And I expect the graph state for workspace "intermediate" to have changed as declared in the snapshot
