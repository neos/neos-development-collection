@contentrepository @adapters=DoctrineDBAL
Feature: Workspace rebasing - conflicting changes

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

  Scenario: Conflicting changes lead to OUTDATED which can be recovered from via forced rebase

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
      | nodeAggregateId              | "nody-mc-nodeface" |
      | nodeVariantSelectionStrategy | "allVariants"      |
      | coveredDimensionSpacePoint   | {}                 |
      | workspaceName                | "user-ws-one"      |

    When the command SetNodeProperties is executed with payload:
      | Key                       | Value                |
      | workspaceName             | "user-ws-two"        |
      | nodeAggregateId           | "nody-mc-nodeface"   |
      | originDimensionSpacePoint | {}                   |
      | propertyValues            | {"text": "Modified"} |

    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                    |
      | nodeAggregateId           | "noderus-secundus"                       |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Content" |
      | parentNodeAggregateId     | "lady-eleonode-rootford"                 |
      | originDimensionSpacePoint | {}                                       |
      | workspaceName             | "user-ws-two"                            |

    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                      |
      | workspaceName             | "user-ws-two"              |
      | nodeAggregateId           | "noderus-secundus"         |
      | originDimensionSpacePoint | {}                         |
      | propertyValues            | {"text": "The other node"} |

    Then workspaces live,user-ws-one,user-ws-two have status UP_TO_DATE

    And the command PublishWorkspace is executed with payload:
      | Key           | Value         |
      | workspaceName | "user-ws-one" |

    Then workspaces live,user-ws-one have status UP_TO_DATE
    Then workspace user-ws-two has status OUTDATED

    When the command RebaseWorkspace is executed with payload:
      | Key                         | Value                 |
      | workspaceName               | "user-ws-two"         |
      | rebasedContentStreamId      | "user-cs-two-rebased" |
      | rebaseErrorHandlingStrategy | "force"               |

    Then workspaces live,user-ws-one,user-ws-two have status UP_TO_DATE
    And I expect a node identified by user-cs-two-rebased;noderus-secundus;{} to exist in the content graph
