Feature: Create a root node aggregate with tethered children

  As a user of the CR I want to create a new root node aggregate with an initial node and tethered children.

  These are the test cases without dimensions involved

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:SubSubNode':
      properties:
        text:
          defaultValue: 'my sub sub default'
          type: string
    'Neos.ContentRepository.Testing:SubNode':
      childNodes:
        grandchild-node:
          type: 'Neos.ContentRepository.Testing:SubSubNode'
      properties:
        text:
          defaultValue: 'my sub default'
          type: string
    'Neos.ContentRepository.Testing:RootWithTetheredChildNodes':
      superTypes:
        'Neos.ContentRepository:Root': true
      childNodes:
        child-node:
          type: 'Neos.ContentRepository.Testing:SubNode'
    'Neos.ContentRepository.Testing:AnotherRootWithTetheredChildNodes':
      superTypes:
        'Neos.ContentRepository:Root': true
      childNodes:
        child-node:
          type: 'Neos.ContentRepository.Testing:SubNode'
    'Neos.ContentRepository.Testing:YetAnotherRootWithTetheredChildNodes':
      superTypes:
        'Neos.ContentRepository:Root': true
      childNodes:
        child-node:
          type: 'Neos.ContentRepository.Testing:SubNode'
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live" and dimension space point {}
    And I am user identified by "initiating-user-identifier"

  Scenario: Publish root node creation
    Given I set up the edge case workspace tree
    When the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                                | Value                                                                             |
      | workspaceName                      | "local"                                                                           |
      | nodeAggregateId                    | "lady-eleonode-rootford"                                                          |
      | nodeTypeName                       | "Neos.ContentRepository.Testing:RootWithTetheredChildNodes"                       |
      | tetheredDescendantNodeAggregateIds | {"child-node": "nody-mc-nodeface", "child-node/grandchild-node": "nodimus-prime"} |
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                                | Value                                                                                 |
      | workspaceName                      | "local"                                                                               |
      | nodeAggregateId                    | "sire-frode-rootford"                                                                 |
      | nodeTypeName                       | "Neos.ContentRepository.Testing:AnotherRootWithTetheredChildNodes"                    |
      | tetheredDescendantNodeAggregateIds | {"child-node": "nodewyn-tetherton", "child-node/grandchild-node": "nodimus-mediocre"} |
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                                | Value                                                                               |
      | workspaceName                      | "local-3"                                                                           |
      | nodeAggregateId                    | "nody-mc-rootface"                                                                  |
      | nodeTypeName                       | "Neos.ContentRepository.Testing:YetAnotherRootWithTetheredChildNodes"               |
      | tetheredDescendantNodeAggregateIds | {"child-node": "nodimer-tetherton", "child-node/grandchild-node": "nodimus-subpar"} |
    And I memorise the global graph state
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
    Given I set up the edge case workspace tree
    When the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                                | Value                                                                             |
      | workspaceName                      | "local"                                                                           |
      | nodeAggregateId                    | "lady-eleonode-rootford"                                                          |
      | nodeTypeName                       | "Neos.ContentRepository.Testing:RootWithTetheredChildNodes"                       |
      | tetheredDescendantNodeAggregateIds | {"child-node": "nody-mc-nodeface", "child-node/grandchild-node": "nodimus-prime"} |
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                                | Value                                                                                 |
      | workspaceName                      | "local"                                                                               |
      | nodeAggregateId                    | "sire-frode-rootford"                                                                 |
      | nodeTypeName                       | "Neos.ContentRepository.Testing:AnotherRootWithTetheredChildNodes"                    |
      | tetheredDescendantNodeAggregateIds | {"child-node": "nodewyn-tetherton", "child-node/grandchild-node": "nodimus-mediocre"} |
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                                | Value                                                                               |
      | workspaceName                      | "local-3"                                                                           |
      | nodeAggregateId                    | "nody-mc-rootface"                                                                  |
      | nodeTypeName                       | "Neos.ContentRepository.Testing:YetAnotherRootWithTetheredChildNodes"               |
      | tetheredDescendantNodeAggregateIds | {"child-node": "nodimer-tetherton", "child-node/grandchild-node": "nodimus-subpar"} |
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
