@contentrepository @adapters=DoctrineDBAL
Feature: Workspace discarding - basic functionality

  This is an END TO END test; testing all layers of the related functionality step by step together

  Basic fixture setup is:
  - root workspace with a single "root" node inside; and an additional child node.
  - then, a nested workspace is created based on the "root" node

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
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
    And I am in workspace "live" and dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |

    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId  | nodeTypeName                           | parentNodeAggregateId  | nodeName |
      | nody-mc-nodeface | Neos.ContentRepository.Testing:Content | lady-eleonode-rootford | child    |

    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                |
      | nodeAggregateId           | "nody-mc-nodeface"   |
      | originDimensionSpacePoint | {}                   |
      | propertyValues            | {"text": "Original"} |
    # we need to ensure that the projections are up to date now; otherwise a content stream is forked with an out-
    # of-date base version. This means the content stream can never be merged back, but must always be rebased.
    And the command CreateWorkspace is executed with payload:
      | Key                | Value                |
      | workspaceName      | "user-test"          |
      | baseWorkspaceName  | "live"               |
      | newContentStreamId | "user-cs-identifier" |

  Scenario: Discarding a full workspace works
    When the command SetNodeProperties is executed with payload:
      | Key                       | Value                |
      | workspaceName             | "user-test"          |
      | nodeAggregateId           | "nody-mc-nodeface"   |
      | originDimensionSpacePoint | {}                   |
      | propertyValues            | {"text": "Modified"} |

    When I am in workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node user-cs-identifier;nody-mc-nodeface;{}
    And I expect this node to have the following properties:
      | Key  | Value      |
      | text | "Modified" |

    # Discarding
    When the command DiscardWorkspace is executed with payload:
      | Key                | Value                         |
      | workspaceName      | "user-test"                   |
      | newContentStreamId | "user-cs-identifier-modified" |

    When I am in workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node user-cs-identifier-modified;nody-mc-nodeface;{}
    And I expect this node to have the following properties:
      | Key  | Value      |
      | text | "Original" |

  Scenario: Discarding a full workspace shows the most up-to-date base workspace when the base WS was modified in the meantime
    When the command SetNodeProperties is executed with payload:
      | Key                       | Value                |
      | workspaceName             | "user-test"          |
      | nodeAggregateId           | "nody-mc-nodeface"   |
      | originDimensionSpacePoint | {}                   |
      | propertyValues            | {"text": "Modified"} |

    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                                  |
      | workspaceName             | "live"                                 |
      | nodeAggregateId           | "nody-mc-nodeface"                     |
      | originDimensionSpacePoint | {}                                     |
      | propertyValues            | {"text": "Modified in live workspace"} |

    # Discarding
    When the command DiscardWorkspace is executed with payload:
      | Key                | Value                         |
      | workspaceName      | "user-test"                   |
      | newContentStreamId | "user-cs-identifier-modified" |

    When I am in workspace "user-test" and dimension space point {}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node user-cs-identifier-modified;nody-mc-nodeface;{}
    And I expect this node to have the following properties:
      | Key  | Value                        |
      | text | "Modified in live workspace" |

  Scenario: Conflicting changes lead to exception on rebase which can be recovered from via discard

    When the command CreateWorkspace is executed with payload:
      | Key                | Value              |
      | workspaceName      | "user-ws-one"      |
      | baseWorkspaceName  | "live"             |
      | newContentStreamId | "user-cs-one"      |
    And the command CreateWorkspace is executed with payload:
      | Key                | Value              |
      | workspaceName      | "user-ws-two"      |
      | baseWorkspaceName  | "live"             |
      | newContentStreamId | "user-cs-two"      |

    Then workspaces live,user-ws-one,user-ws-two have status UP_TO_DATE

    When the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value              |
      | workspaceName                | "user-ws-one"      |
      | nodeAggregateId              | "nody-mc-nodeface" |
      | nodeVariantSelectionStrategy | "allVariants"      |
      | coveredDimensionSpacePoint   | {}                 |

    When the command SetNodeProperties is executed with payload:
      | Key                       | Value                |
      | workspaceName             | "user-ws-two"        |
      | nodeAggregateId           | "nody-mc-nodeface"   |
      | originDimensionSpacePoint | {}                   |
      | propertyValues            | {"text": "Modified"} |

    And the command PublishWorkspace is executed with payload:
      | Key           | Value         |
      | workspaceName | "user-ws-one" |

    Then workspaces live,user-ws-one have status UP_TO_DATE
    Then workspace user-ws-two has status OUTDATED

    When the command RebaseWorkspace is executed with payload and exceptions are caught:
      | Key                    | Value                 |
      | workspaceName          | "user-ws-two"         |
      | rebasedContentStreamId | "user-cs-two-rebased" |
    Then the last command should have thrown an exception of type "WorkspaceRebaseFailed"

    Then workspace user-ws-two has status OUTDATED

    When the command DiscardWorkspace is executed with payload:
      | Key                | Value                   |
      | workspaceName      | "user-ws-two"           |
      | newContentStreamId | "user-cs-two-discarded" |

    Then workspaces live,user-ws-one,user-ws-two have status UP_TO_DATE
