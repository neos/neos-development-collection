@contentrepository @adapters=DoctrineDBAL
Feature: If content streams are not in use anymore by the workspace, they can be properly pruned - this is
  tested here.

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live" and dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "root-node"                   |
      | nodeTypeName    | "Neos.ContentRepository:Root" |

  #
  # Before Neos 9 beta 15 (publishing version 3 #5301), dangling content streams were not removed during publishing, discard or rebase
  # The first scenarios assert that the automatic deletion works correctly
  #

  Scenario: content streams are in use after creation
    Then I expect the content stream "non-existing" to not exist
    Then I expect the content stream "cs-identifier" to exist

    Then I expect the content stream pruner status output:
    """
    Okay. No dangling streams found

    Okay. No pruneable streams in the event stream
    """

  Scenario: on creating a nested workspace, the new content stream is not pruned
    When the command CreateWorkspace is executed with payload:
      | Key                | Value                |
      | workspaceName      | "user-test"          |
      | baseWorkspaceName  | "live"               |
      | newContentStreamId | "user-cs-identifier" |
    Then I expect the content stream "user-cs-identifier" to exist

    Then I expect the content stream pruner status output:
    """
    Okay. No dangling streams found

    Okay. No pruneable streams in the event stream
    """

  Scenario: no longer in use content streams will be properly cleaned from the graph projection.
    When the command CreateWorkspace is executed with payload:
      | Key                | Value                |
      | workspaceName      | "user-test"          |
      | baseWorkspaceName  | "live"               |
      | newContentStreamId | "user-cs-identifier" |
    When I am in workspace "user-test" and dimension space point {}
    # Ensure that we are in content user-cs-identifier
    Then I expect node aggregate identifier "root-node" to lead to node user-cs-identifier;root-node;{}

    When the command RebaseWorkspace is executed with payload:
      | Key                    | Value                        |
      | workspaceName          | "user-test"                  |
      | rebasedContentStreamId | "user-cs-identifier-rebased" |
      | rebaseErrorHandlingStrategy | "force"               |
    # now, we have one unused content stream (the old content stream of the user-test workspace)

    Then I expect the content stream "user-cs-identifier" to not exist

    When I am in workspace "user-test" and dimension space point {}
    # todo test that the graph projection really is cleaned up and that no hierarchy stil exist?
    Then I expect node aggregate identifier "root-node" to lead to node user-cs-identifier-rebased;root-node;{}

  Scenario: no longer in use content streams can be cleaned up completely (simple case)

    When the command CreateWorkspace is executed with payload:
      | Key                | Value                |
      | workspaceName      | "user-test"          |
      | baseWorkspaceName  | "live"               |
      | newContentStreamId | "user-cs-identifier" |
    When the command RebaseWorkspace is executed with payload:
      | Key                    | Value                        |
      | workspaceName          | "user-test"                  |
      | rebasedContentStreamId | "user-cs-identifier-rebased" |
      | rebaseErrorHandlingStrategy | "force"               |

    Then I expect the content stream "user-cs-identifier-rebased" to exist
    Then I expect the content stream "user-cs-identifier" to not exist

    # now, we have one unused content stream (the old content stream of the user-test workspace)

    Then I expect the content stream pruner status output:
    """
    Okay. No dangling streams found

    Removed content streams that can be pruned from the event stream
      id: user-cs-identifier previous state: no longer in use
    To prune the removed streams from the event stream run ./flow contentStream:pruneRemovedFromEventstream
    """

    And I prune removed content streams from the event stream

    Then I expect exactly 0 events to be published on stream "ContentStream:user-cs-identifier"

    Then I expect the content stream pruner status output:
    """
    Okay. No dangling streams found

    Okay. No pruneable streams in the event stream
    """

  Scenario: no longer in use content streams are only cleaned up if no other content stream which is still in use depends on it
    # we build a "review" workspace, and then a "user-test" workspace depending on the review workspace.
    When the command CreateWorkspace is executed with payload:
      | Key                | Value                  |
      | workspaceName      | "review"               |
      | baseWorkspaceName  | "live"                 |
      | newContentStreamId | "review-cs-identifier" |
    And the command CreateWorkspace is executed with payload:
      | Key                | Value                |
      | workspaceName      | "user-test"          |
      | baseWorkspaceName  | "review"             |
      | newContentStreamId | "user-cs-identifier" |

    # now, we rebase the "review" workspace, effectively marking the "review-cs-identifier" content stream as no longer in use.
    # however, we are not allowed to drop the content stream from the event store yet, because the "user-cs-identifier" is based
    # on the (no-longer-in-direct-use) review-cs-identifier.
    When the command RebaseWorkspace is executed with payload:
      | Key           | Value    |
      | workspaceName | "review" |
      | rebaseErrorHandlingStrategy | "force"               |

    And I prune removed content streams from the event stream

    # the events should still exist
    Then I expect exactly 3 events to be published on stream "ContentStream:review-cs-identifier"

    Then I expect the content stream pruner status output:
    """
    Okay. No dangling streams found

    Okay. No pruneable streams in the event stream
    """
