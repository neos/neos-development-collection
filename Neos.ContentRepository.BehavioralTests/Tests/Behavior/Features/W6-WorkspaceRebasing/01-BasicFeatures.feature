Feature: Rebasing with no conflict

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Content': {}
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
    When I am in workspace "live" and dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | workspaceName   | "live"                        |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                         | Value                                               |
      | workspaceName               | "live"                                              |
      | nodeAggregateId             | "sir-david-nodenborough"                            |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Content"            |
      | parentNodeAggregateId       | "lady-eleonode-rootford"                            |

    # Create user workspace
    And the command CreateWorkspace is executed with payload:
      | Key                | Value                |
      | workspaceName      | "user-test"          |
      | baseWorkspaceName  | "live"               |
      | newContentStreamId | "user-cs-identifier" |

    Then workspaces live,user-test have status UP_TO_DATE

  Scenario: Rebase is skipped (via exception) if there are no changes
    When the command RebaseWorkspace is executed with payload and exceptions are caught:
      | Key                         | Value                 |
      | workspaceName               | "user-test"           |
      | rebasedContentStreamId      | "user-cs-rebased"     |
    Then the last command should have thrown an exception of type "WorkspaceCommandSkipped" with code 1730463693 and message:
    """
    Skipped rebase workspace "user-test" because it is not outdated.
    """
    Then I expect the content stream "user-cs-rebased" to not exist

    When I am in workspace "live" and dimension space point {}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{}

    When I am in workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node user-cs-identifier;sir-david-nodenborough;{}

  Scenario: Rebase via force creates new content stream even if there are no changes
    # force flag used
    When the command RebaseWorkspace is executed with payload:
      | Key                         | Value                 |
      | workspaceName               | "user-test"           |
      | rebasedContentStreamId      | "user-cs-rebased"     |
      | rebaseErrorHandlingStrategy | "force"               |

    Then I expect exactly 2 events to be published on stream with prefix "Workspace:user-test"
    And event at index 1 is of type "WorkspaceWasRebased" with payload:
      | Key                     | Expected             |
      | workspaceName           | "user-test"          |
      | newContentStreamId      | "user-cs-rebased"    |
      | previousContentStreamId | "user-cs-identifier" |
      | skippedEvents           | []                   |

    Then I expect exactly 1 events to be published on stream with prefix "ContentStream:user-cs-rebased"
    And event at index 0 is of type "ContentStreamWasForked" with payload:
      | Key                   | Expected          |
      | newContentStreamId    | "user-cs-rebased" |
      | sourceContentStreamId | "cs-identifier"   |

    Then I expect exactly 3 events to be published on stream with prefix "ContentStream:user-cs-identifier"
    And event at index 2 is of type "ContentStreamWasRemoved" with payload:
      | Key             | Expected             |
      | contentStreamId | "user-cs-identifier" |

    Then I expect the content stream "user-cs-identifier" to not exist

    Then I expect exactly 2 events to be published on stream with prefix "Workspace:user-test"
    And event at index 1 is of type "WorkspaceWasRebased" with payload:
      | Key                     | Expected             |
      | workspaceName           | "user-test"          |
      | newContentStreamId      | "user-cs-rebased"    |
      | previousContentStreamId | "user-cs-identifier" |
      | skippedEvents           | []                   |

    When I am in workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node user-cs-rebased;sir-david-nodenborough;{}

  Scenario: Rebase only the base contains changes
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                         | Value                                                   |
      | workspaceName               | "live"                                                  |
      | nodeAggregateId             | "sir-nodeward-nodington-iii"                            |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Content"                |
      | parentNodeAggregateId       | "lady-eleonode-rootford"                                |
    Then workspaces user-test has status OUTDATED

    When the command RebaseWorkspace is executed with payload:
      | Key                         | Value                 |
      | workspaceName               | "user-test"           |
      | rebasedContentStreamId      | "user-cs-rebased"     |

    Then I expect exactly 2 events to be published on stream with prefix "Workspace:user-test"
    And event at index 1 is of type "WorkspaceWasRebased" with payload:
      | Key                     | Expected             |
      | workspaceName           | "user-test"          |
      | newContentStreamId      | "user-cs-rebased"    |
      | previousContentStreamId | "user-cs-identifier" |
      | skippedEvents           | []                   |

    Then I expect exactly 1 events to be published on stream with prefix "ContentStream:user-cs-rebased"
    And event at index 0 is of type "ContentStreamWasForked" with payload:
      | Key                   | Expected          |
      | newContentStreamId    | "user-cs-rebased" |
      | sourceContentStreamId | "cs-identifier"   |

    Then I expect exactly 3 events to be published on stream with prefix "ContentStream:user-cs-identifier"
    And event at index 2 is of type "ContentStreamWasRemoved" with payload:
      | Key             | Expected             |
      | contentStreamId | "user-cs-identifier" |

    Then I expect the content stream "user-cs-identifier" to not exist
    Then workspaces live,user-test have status UP_TO_DATE

    When I am in workspace "live" and dimension space point {}
    Then I expect node aggregate identifier "sir-nodeward-nodington-iii" to lead to node cs-identifier;sir-nodeward-nodington-iii;{}

    When I am in workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "sir-nodeward-nodington-iii" to lead to node user-cs-rebased;sir-nodeward-nodington-iii;{}

    # workspace is writeable (version checks work)
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                    |
      | workspaceName             | "user-test"                              |
      | nodeAggregateId           | "nody-mc-nodeface"                       |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Content" |
      | originDimensionSpacePoint | {}                                       |
      | parentNodeAggregateId     | "lady-eleonode-rootford"                 |
    # publish events that _actually_ are written on the workspace stream
    When the command DiscardWorkspace is executed with payload:
      | Key                | Value                          |
      | workspaceName      | "user-test"                    |
      | newContentStreamId | "user-cs-identifier-discarded" |

  Scenario: Rebase workspace and base contains changes
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                         | Value                                                   |
      | workspaceName               | "live"                                                  |
      | nodeAggregateId             | "sir-nodeward-nodington-iii"                            |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Content"                |
      | parentNodeAggregateId       | "lady-eleonode-rootford"                                |
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                         | Value                                                   |
      | workspaceName               | "user-test"                                             |
      | nodeAggregateId             | "nordisch-nodel"                                        |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Content"                |
      | parentNodeAggregateId       | "lady-eleonode-rootford"                                |
    Then workspaces user-test has status OUTDATED

    When the command RebaseWorkspace is executed with payload:
      | Key                         | Value                 |
      | workspaceName               | "user-test"           |
      | rebasedContentStreamId      | "user-cs-rebased"     |

    Then I expect exactly 2 events to be published on stream with prefix "Workspace:user-test"
    And event at index 1 is of type "WorkspaceWasRebased" with payload:
      | Key                     | Expected             |
      | workspaceName           | "user-test"          |
      | newContentStreamId      | "user-cs-rebased"    |
      | previousContentStreamId | "user-cs-identifier" |
      | skippedEvents           | []                   |

    Then I expect exactly 4 events to be published on stream with prefix "ContentStream:user-cs-rebased"
    And event at index 0 is of type "ContentStreamWasForked" with payload:
      | Key                   | Expected          |
      | newContentStreamId    | "user-cs-rebased" |
      | sourceContentStreamId | "cs-identifier"   |

    Then I expect exactly 4 events to be published on stream with prefix "ContentStream:user-cs-identifier"
    And event at index 3 is of type "ContentStreamWasRemoved" with payload:
      | Key             | Expected             |
      | contentStreamId | "user-cs-identifier" |

    Then I expect the content stream "user-cs-identifier" to not exist

    Then workspaces live,user-test have status UP_TO_DATE

    When I am in workspace "live" and dimension space point {}
    Then I expect node aggregate identifier "sir-nodeward-nodington-iii" to lead to node cs-identifier;sir-nodeward-nodington-iii;{}
    Then I expect node aggregate identifier "nordisch-nodel" to lead to no node

    When I am in workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "sir-nodeward-nodington-iii" to lead to node user-cs-rebased;sir-nodeward-nodington-iii;{}
    Then I expect node aggregate identifier "nordisch-nodel" to lead to node user-cs-rebased;nordisch-nodel;{}

  Scenario: Rebase two direct workspaces and base contains changes
      # Create second user workspace from live
    And the command CreateWorkspace is executed with payload:
      | Key                | Value                 |
      | workspaceName      | "user-test2"         |
      | baseWorkspaceName  | "live"                |
      | newContentStreamId | "user-cs2-identifier" |

    Then workspaces live,user-test,user-test2 have status UP_TO_DATE

    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                         | Value                                                   |
      | workspaceName               | "live"                                                  |
      | nodeAggregateId             | "sir-nodeward-nodington-iii"                            |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Content"                |
      | parentNodeAggregateId       | "lady-eleonode-rootford"                                |
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                         | Value                                                   |
      | workspaceName               | "user-test"                                             |
      | nodeAggregateId             | "nordisch-nodel"                                        |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Content"                |
      | parentNodeAggregateId       | "lady-eleonode-rootford"                                |
    Then workspaces user-test,user-test2 have status OUTDATED

    When the command RebaseWorkspace is executed with payload:
      | Key                         | Value                 |
      | workspaceName               | "user-test"           |
      | rebasedContentStreamId      | "user-cs-rebased"     |
    Then I expect the content stream "user-cs-identifier" to not exist

    When the command RebaseWorkspace is executed with payload:
      | Key                         | Value                 |
      | workspaceName               | "user-test2"           |
      | rebasedContentStreamId      | "user-cs2-rebased"     |
    Then I expect the content stream "user-cs2-identifier" to not exist

    Then workspaces live,user-test,user-test2 have status UP_TO_DATE

    When I am in workspace "live" and dimension space point {}
    Then I expect node aggregate identifier "sir-nodeward-nodington-iii" to lead to node cs-identifier;sir-nodeward-nodington-iii;{}
    Then I expect node aggregate identifier "nordisch-nodel" to lead to no node

    When I am in workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "sir-nodeward-nodington-iii" to lead to node user-cs-rebased;sir-nodeward-nodington-iii;{}
    Then I expect node aggregate identifier "nordisch-nodel" to lead to node user-cs-rebased;nordisch-nodel;{}

    When I am in workspace "user-test2" and dimension space point {}
    Then I expect node aggregate identifier "sir-nodeward-nodington-iii" to lead to node user-cs2-rebased;sir-nodeward-nodington-iii;{}
    Then I expect node aggregate identifier "nordisch-nodel" to lead to no node

    # Assert that the live workspace mutations are not immoderately present after the content stream was removed and fork events
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                   | Value                                    |
      | workspaceName         | "live"                                   |
      | nodeAggregateId       | "nody-mc-nodeface"                       |
      | nodeTypeName          | "Neos.ContentRepository.Testing:Content" |
      | parentNodeAggregateId | "lady-eleonode-rootford"                 |
    When I am in workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to no node

    When I am in workspace "user-test2" and dimension space point {}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to no node
