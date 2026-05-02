@contentrepository @adapters=DoctrineDBAL
Feature: ForkContentStream Without Dimensions

  We have only one node underneath the root node: /foo.
  LIVE Content Stream ID: cs-identifier
  We fork the live content stream as ID user-cs-identifier
  and then we commit a modification in the LIVE content stream.
  We then expect the *forked* content stream to contain the *original* value; and the *live* content stream must contain the changed value.

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
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                      |
      | workspaceName             | "live"                     |
      | nodeAggregateId           | "nody-mc-nodeface"         |
      | originDimensionSpacePoint | {}                         |
      | propertyValues            | {"text": "original value"} |

  Scenario: Ensure that the node is available in the forked content stream
    # Uses ForkContentStream implicitly
    When the command CreateWorkspace is executed with payload:
      | Key                | Value                |
      | baseWorkspaceName  | "live"               |
      | workspaceName      | "user-test"          |
      | newContentStreamId | "user-cs-identifier" |

    When I am in workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node user-cs-identifier;nody-mc-nodeface;{}

  Scenario: When a change is applied to the forked content stream AFTER the fork, it is not visible in the live content stream.
    # Uses ForkContentStream implicitly
    When the command CreateWorkspace is executed with payload:
      | Key                | Value                |
      | baseWorkspaceName  | "live"               |
      | workspaceName      | "user-test"          |
      | newContentStreamId | "user-cs-identifier" |
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                      |
      | workspaceName             | "user-test"                |
      | nodeAggregateId           | "nody-mc-nodeface"         |
      | originDimensionSpacePoint | {}                         |
      | propertyValues            | {"text": "modified value"} |

    # live
    When I am in workspace "live" and dimension space point {}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{}
    And I expect this node to have the following properties:
      | Key  | Value            |
      | text | "original value" |

    # forked content stream
    When I am in workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node user-cs-identifier;nody-mc-nodeface;{}
    And I expect this node to have the following properties:
      | Key  | Value            |
      | text | "modified value" |

  # this is a "reverse" scenario of the scenario above.
  Scenario: When a change is applied on the live content stream AFTER the fork, it is NOT visible in the forked content stream.
    # Uses ForkContentStream implicitly
    When the command CreateWorkspace is executed with payload:
      | Key                | Value                |
      | baseWorkspaceName  | "live"               |
      | workspaceName      | "user-test"          |
      | newContentStreamId | "user-cs-identifier" |
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                      |
      | workspaceName             | "live"                     |
      | nodeAggregateId           | "nody-mc-nodeface"         |
      | originDimensionSpacePoint | {}                         |
      | propertyValues            | {"text": "modified value"} |

    # live
    When I am in workspace "live" and dimension space point {}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{}
    And I expect this node to have the following properties:
      | Key  | Value            |
      | text | "modified value" |

    # forked content stream
    When I am in workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node user-cs-identifier;nody-mc-nodeface;{}
    And I expect this node to have the following properties:
      | Key  | Value            |
      | text | "original value" |
