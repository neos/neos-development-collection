Feature:
  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    Neos.ContentRepository:Root: {}
    'Neos.ContentRepository.Testing:Content':
      properties:
        text:
          type: string
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
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |

    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                         | Value                                    |
      | workspaceName               | "live"                                   |
      | nodeAggregateId             | "nody-mc-nodeface"                       |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Content" |
      | originDimensionSpacePoint   | {}                                       |
      | parentNodeAggregateId       | "lady-eleonode-rootford"                 |
      | nodeName                    | "child"                                  |
      | nodeAggregateClassification | "regular"                                |

    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                      |
      | workspaceName             | "live"                     |
      | nodeAggregateId           | "nody-mc-nodeface"         |
      | originDimensionSpacePoint | {}                         |
      | propertyValues            | {"text": "original value"} |
      | propertiesToUnset         | {}                         |

    # initial state
    Then I expect 0 buffered forks for content stream "cs-identifier"

  Scenario: Single buffered fork without changes
    When I create 1 buffered forks for content stream "cs-identifier"
    Then I expect 1 buffered forks for content stream "cs-identifier"

    # Uses buffered implicitly
    When the command CreateWorkspace is executed with payload:
      | Key                | Value                |
      | baseWorkspaceName  | "live"               |
      | workspaceName      | "user-test"          |
      | newContentStreamId | "user-cs-identifier" |

    Then I expect 0 buffered forks for content stream "cs-identifier"

    When I am in workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node user-cs-identifier;nody-mc-nodeface;{}

  Scenario: Multiple buffered forks without changes
    When I create 2 buffered forks for content stream "cs-identifier"
    Then I expect 2 buffered forks for content stream "cs-identifier"

    # Uses buffered implicitly
    When the command CreateWorkspace is executed with payload:
      | Key                | Value                |
      | baseWorkspaceName  | "live"               |
      | workspaceName      | "user-test"          |
      | newContentStreamId | "user-cs-identifier" |
    Then I expect 1 buffered forks for content stream "cs-identifier"

    When I am in workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node user-cs-identifier;nody-mc-nodeface;{}

    # Uses buffered implicitly
    When the command CreateWorkspace is executed with payload:
      | Key                | Value                |
      | baseWorkspaceName  | "live"               |
      | workspaceName      | "user-2-test"          |
      | newContentStreamId | "user-second-cs-identifier" |
    Then I expect 0 buffered forks for content stream "cs-identifier"

    When I am in workspace "user-2-test" and dimension space point {}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node user-second-cs-identifier;nody-mc-nodeface;{}

  Scenario: Refill buffered forks without changes
    When I create 1 buffered forks for content stream "cs-identifier"
    Then I expect 1 buffered forks for content stream "cs-identifier"
    When I create 1 buffered forks for content stream "cs-identifier"
    Then I expect 2 buffered forks for content stream "cs-identifier"

    # Uses buffered implicitly
    When the command CreateWorkspace is executed with payload:
      | Key                | Value                |
      | baseWorkspaceName  | "live"               |
      | workspaceName      | "user-test"          |
      | newContentStreamId | "user-cs-identifier" |
    Then I expect 1 buffered forks for content stream "cs-identifier"

    When I am in workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node user-cs-identifier;nody-mc-nodeface;{}

    # only if the force flag is used we enforce a fork:
    When the command RebaseWorkspace is executed with payload:
      | Key                         | Value                 |
      | workspaceName               | "user-test"           |
      | rebasedContentStreamId      | "user-cs-rebased"     |
      | rebaseErrorHandlingStrategy | "force"               |
    Then I expect 0 buffered forks for content stream "cs-identifier"

    When I am in workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node user-cs-rebased;nody-mc-nodeface;{}

  Scenario: When a change is applied on the live content stream AFTER the buffered fork, buffered forks will be fast forwarded
    When I create 1 buffered forks for content stream "cs-identifier"
    Then I expect 1 buffered forks for content stream "cs-identifier"

    # changes to live after buffered forks
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                      |
      | workspaceName             | "live"                     |
      | nodeAggregateId           | "nody-mc-nodeface"         |
      | originDimensionSpacePoint | {}                         |
      | propertyValues            | {"text": "modified value"} |
      | propertiesToUnset         | {}                         |

    # must be fast forward
    Then I expect 1 buffered forks for content stream "cs-identifier"

    When the command CreateWorkspace is executed with payload:
      | Key                | Value                |
      | baseWorkspaceName  | "live"               |
      | workspaceName      | "user-test"          |
      | newContentStreamId | "user-cs-identifier" |

    # buffer NOT used for forking, as it is behind
    Then I expect 1 buffered forks for content stream "cs-identifier"

    # fast forward and use buffer
    When I fast-forward buffered forks for content stream "cs-identifier"
    # only if the force flag is used we enforce a fork:
    When the command RebaseWorkspace is executed with payload:
      | Key                         | Value                 |
      | workspaceName               | "user-test"           |
      | rebasedContentStreamId      | "user-cs-rebased"     |
      | rebaseErrorHandlingStrategy | "force"               |
    Then I expect 0 buffered forks for content stream "cs-identifier"

    # forked content stream
    When I am in workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node user-cs-rebased;nody-mc-nodeface;{}
    And I expect this node to have the following properties:
      | Key  | Value            |
      | text | "modified value" |

    # write to user content stream
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                        |
      | workspaceName             | "user-test"                  |
      | nodeAggregateId           | "nody-mc-nodeface"           |
      | originDimensionSpacePoint | {}                           |
      | propertyValues            | {"text": "modified value 2"} |
      | propertiesToUnset         | {}                           |

    # live has original value
    When I am in workspace "live" and dimension space point {}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{}
    And I expect this node to have the following properties:
      | Key  | Value            |
      | text | "modified value" |

# Todo test that after replay there are no buffered forks
# Test that buffered forks are used during publishing and all other forks
# Test that buffered forks also work on non root workspaces?
# !!! TODO MUST be thread safe and lock other cr actions like a fork triggered by user :OOO
