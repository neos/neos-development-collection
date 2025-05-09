@contentrepository @adapters=DoctrineDBAL
Feature: Individual node publication

  Publishing an individual node works

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Content':
      properties:
        text:
          type: string
    'Neos.ContentRepository.Testing:Document':
      childNodes:
        child1:
          type: 'Neos.ContentRepository.Testing:Content'
        child2:
          type: 'Neos.ContentRepository.Testing:Content'
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live"
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | workspaceName   | "live"                        |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |

    # Create user workspace
    And the command CreateWorkspace is executed with payload:
      | Key                | Value                |
      | workspaceName      | "user-test"          |
      | baseWorkspaceName  | "live"               |
      | newContentStreamId | "user-cs-identifier" |

  ################
  # PUBLISHING
  ################
  Scenario: It is possible to publish a single node; and only this one is live.
    # create nodes in user WS
    Given I am in workspace "user-test"
    And I am in dimension space point {}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId        | nodeTypeName                            | parentNodeAggregateId  | nodeName | tetheredDescendantNodeAggregateIds |
      | sir-david-nodenborough | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | document | {}                                 |
    And I remember NodeAggregateId of node "sir-david-nodenborough"s child "child2" as "child2Id"
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId  | nodeTypeName                           | parentNodeAggregateId | nodeName | tetheredDescendantNodeAggregateIds |
      | nody-mc-nodeface | Neos.ContentRepository.Testing:Content | $child2Id             | nody     | {}                                 |
    When the command PublishIndividualNodesFromWorkspace is executed with payload:
      | Key                             | Value                                                                                                    |
      | nodesToPublish                  | ["sir-david-nodenborough"] |
      | contentStreamIdForRemainingPart | "user-cs-identifier-remaining"                                                                           |

    Then I expect exactly 2 events to be published on stream with prefix "Workspace:user-test"
    And event at index 1 is of type "WorkspaceWasPublished" with payload:
      | Key                           | Expected                       |
      | sourceWorkspaceName           | "user-test"                    |
      | targetWorkspaceName           | "live"                         |
      | newSourceContentStreamId      | "user-cs-identifier-remaining" |
      | previousSourceContentStreamId | "user-cs-identifier"           |
      | partial                       | true                           |

    And I am in workspace "live"

    Then I expect a node identified by cs-identifier;sir-david-nodenborough;{} to exist in the content graph


  Scenario: Partial publish is skipped (via exception) if the workspace doesnt contain any changes (and the workspace is outdated)

    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                    |
      | workspaceName             | "live"                                   |
      | nodeAggregateId           | "nody-mc-nodeface"                       |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Content" |
      | originDimensionSpacePoint | {}                                       |
      | parentNodeAggregateId     | "lady-eleonode-rootford"                 |
      | initialPropertyValues     | {"text": "Original text"}                |

    And I am in workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to no node

    When the command PublishIndividualNodesFromWorkspace is executed with payload and exceptions are caught:
      | Key                             | Value                                                            |
      | workspaceName                   | "user-test"                                                      |
      | nodesToPublish                  | ["non-existing"] |
      | contentStreamIdForRemainingPart | "user-cs-new"                                                    |

    Then the last command should have thrown an exception of type "WorkspaceCommandSkipped" with code 1730463156 and message:
    """
    Skipped publish workspace "user-test" without any publishable changes.
    """

    Then workspaces user-test has status OUTDATED

    And I am in workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to no node

    Then I expect the content stream "user-cs-new" to not exist
