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
    And the graph projection is fully up to date
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
    And the graph projection is fully up to date
    And the command CreateWorkspace is executed with payload:
      | Key                | Value                |
      | workspaceName      | "user-test"          |
      | baseWorkspaceName  | "live"               |
      | newContentStreamId | "user-cs-identifier" |
      | workspaceOwner     | "owner-identifier"   |
    And the graph projection is fully up to date

  Scenario: Conflicting changes lead to OUTDATED_CONFLICT which can be recovered from via forced rebase

    When the command CreateWorkspace is executed with payload:
      | Key                | Value                        |
      | workspaceName      | "user-ws-one"                |
      | baseWorkspaceName  | "live"                       |
      | newContentStreamId | "user-cs-one"                |
      | workspaceOwner     | "owner-identifier"           |
    And the graph projection is fully up to date
    And the command CreateWorkspace is executed with payload:
      | Key                | Value                        |
      | workspaceName      | "user-ws-two"                |
      | baseWorkspaceName  | "live"                       |
      | newContentStreamId | "user-cs-two"                |
      | workspaceOwner     | "owner-identifier"           |
    And the graph projection is fully up to date

    When the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "nody-mc-nodeface"       |
      | nodeVariantSelectionStrategy | "allVariants"            |
      | coveredDimensionSpacePoint   | {}                       |
      | workspaceName              | "user-ws-one"            |
    And the graph projection is fully up to date

    When the command SetNodeProperties is executed with payload:
      | Key                       | Value                        |
      | workspaceName             | "user-ws-two"                |
      | nodeAggregateId           | "nody-mc-nodeface"           |
      | originDimensionSpacePoint | {}                           |
      | propertyValues            | {"text": "Modified"}         |
    And the graph projection is fully up to date

    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                         | Value                                    |
      | nodeAggregateId             | "noderus-secundus"                       |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Content" |
      | parentNodeAggregateId       | "lady-eleonode-rootford"                 |
      | originDimensionSpacePoint   | {}                                       |
      | workspaceName               | "user-ws-two"                            |
    And the graph projection is fully up to date

    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                        |
      | workspaceName             | "user-ws-two"                |
      | nodeAggregateId           | "noderus-secundus"           |
      | originDimensionSpacePoint | {}                           |
      | propertyValues            | {"text": "The other node"}   |
    And the graph projection is fully up to date

    And the command PublishWorkspace is executed with payload:
      | Key              | Value            |
      | workspaceName    | "user-ws-one"    |
    And the graph projection is fully up to date

    Then workspace user-ws-two has status OUTDATED

    When the command RebaseWorkspace is executed with payload:
      | Key                            | Value                  |
      | workspaceName                  | "user-ws-two"          |
      | rebasedContentStreamId         | "user-cs-two-rebased"  |
      | rebaseErrorHandlingStrategy    | "force"                |
    And the graph projection is fully up to date

    Then workspace user-ws-two has status UP_TO_DATE
    And I expect a node identified by user-cs-two-rebased;noderus-secundus;{} to exist in the content graph
