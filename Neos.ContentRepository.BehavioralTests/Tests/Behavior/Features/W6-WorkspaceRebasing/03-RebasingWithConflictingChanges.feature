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
      | nodeAggregateId  | nodeTypeName                           | parentNodeAggregateId  |
      | nody-mc-nodeface | Neos.ContentRepository.Testing:Content | lady-eleonode-rootford |
      | sir-nodebelig    | Neos.ContentRepository.Testing:Content | lady-eleonode-rootford |
      | nobody-node      | Neos.ContentRepository.Testing:Content | lady-eleonode-rootford |

    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                |
      | nodeAggregateId           | "nody-mc-nodeface"   |
      | originDimensionSpacePoint | {}                   |
      | propertyValues            | {"text": "Original"} |

  Scenario: Conflicting changes lead to WorkspaceRebaseFailed exception which can be recovered from via forced rebase

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

    # Rebase without force fails
    When the command RebaseWorkspace is executed with payload and exceptions are caught:
      | Key                         | Value                 |
      | workspaceName               | "user-ws-two"         |
      | rebasedContentStreamId      | "user-cs-two-rebased" |
    Then I expect the content stream "user-cs-two" to exist
    Then I expect the content stream "user-cs-two-rebased" to not exist
    Then the last command should have thrown the WorkspaceRebaseFailed exception with:
      | SequenceNumber | Event                 | Exception                          |
      | 13             | NodePropertiesWereSet | NodeAggregateCurrentlyDoesNotExist |

    When the command RebaseWorkspace is executed with payload:
      | Key                         | Value                 |
      | workspaceName               | "user-ws-two"         |
      | rebasedContentStreamId      | "user-cs-two-rebased" |
      | rebaseErrorHandlingStrategy | "force"               |

    Then workspaces live,user-ws-one,user-ws-two have status UP_TO_DATE

    Then I expect the content stream "user-cs-two" to not exist
    Then I expect the content stream "user-cs-two-rebased" to exist

    When I am in workspace "user-ws-two" and dimension space point {}
    Then I expect node aggregate identifier "noderus-secundus" to lead to node user-cs-two-rebased;noderus-secundus;{}

  Scenario: Not conflicting changes are preserved on force rebase
    And the command CreateWorkspace is executed with payload:
      | Key                | Value                |
      | workspaceName      | "user-ws"            |
      | baseWorkspaceName  | "live"               |
      | newContentStreamId | "user-cs-identifier" |

    When the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value           |
      | workspaceName                | "live"          |
      | nodeAggregateId              | "sir-nodebelig" |
      | coveredDimensionSpacePoint   | {}              |
      | nodeVariantSelectionStrategy | "allVariants"   |

    When the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value         |
      | workspaceName                | "live"        |
      | nodeAggregateId              | "nobody-node" |
      | coveredDimensionSpacePoint   | {}            |
      | nodeVariantSelectionStrategy | "allVariants" |

    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                     |
      | workspaceName             | "user-ws"                 |
      | nodeAggregateId           | "sir-nodebelig"           |
      | originDimensionSpacePoint | {}                        |
      | propertyValues            | {"text": "Modified text"} |

    # change that is rebaseable
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                               |
      | workspaceName             | "user-ws"                           |
      | nodeAggregateId           | "nody-mc-nodeface"                  |
      | originDimensionSpacePoint | {}                                  |
      | propertyValues            | {"text": "Rebaseable change in ws"} |

    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                     |
      | workspaceName             | "user-ws"                 |
      | nodeAggregateId           | "nobody-node"             |
      | originDimensionSpacePoint | {}                        |
      | propertyValues            | {"text": "Modified text"} |

    # Rebase without force fails
    When the command RebaseWorkspace is executed with payload and exceptions are caught:
      | Key                | Value                        |
      | workspaceName      | "user-ws"                    |
      | newContentStreamId | "user-cs-identifier-rebased" |
    Then I expect the content stream "user-cs-identifier" to exist
    Then I expect the content stream "user-cs-identifier-rebased" to not exist
    Then the last command should have thrown the WorkspaceRebaseFailed exception with:
      | SequenceNumber | Event                 | Exception                          |
      | 12             | NodePropertiesWereSet | NodeAggregateCurrentlyDoesNotExist |
      | 14             | NodePropertiesWereSet | NodeAggregateCurrentlyDoesNotExist |

    When the command RebaseWorkspace is executed with payload:
      | Key                         | Value                        |
      | workspaceName               | "user-ws"                    |
      | rebasedContentStreamId      | "user-cs-identifier-rebased" |
      | rebaseErrorHandlingStrategy | "force"                      |

    Then I expect exactly 2 events to be published on stream with prefix "Workspace:user-ws"
    And event at index 1 is of type "WorkspaceWasRebased" with payload:
      | Key                     | Expected                     |
      | workspaceName           | "user-ws"                    |
      | newContentStreamId      | "user-cs-identifier-rebased" |
      | previousContentStreamId | "user-cs-identifier"         |
      | skippedEvents           | [12,14]                      |

    Then I expect the content stream "user-cs-identifier" to not exist
    Then I expect the content stream "user-cs-identifier-rebased" to exist

    When I am in workspace "user-ws" and dimension space point {}
    Then I expect node aggregate identifier "sir-nodebelig" to lead to no node
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node user-cs-identifier-rebased;nody-mc-nodeface;{}
    And I expect this node to have the following properties:
      | Key  | Value                     |
      | text | "Rebaseable change in ws" |
