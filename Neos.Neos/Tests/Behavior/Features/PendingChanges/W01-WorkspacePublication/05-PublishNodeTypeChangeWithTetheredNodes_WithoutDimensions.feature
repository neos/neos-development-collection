@contentrepository @adapters=DoctrineDBAL
@flowEntities
Feature: Partial publish after node type change tagging tethered nodes (without dimensions)

  Publishing a document whose node type was changed to one with fewer tethered children
  (using the mark_with_tag:removed strategy) must succeed even when a grandchild node
  that lived inside the now-removed tethered child still exists in the workspace.

  See https://github.com/neos/neos-development-collection/pull/5767

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository:Root': {}
    'Neos.Neos:Sites':
      superTypes:
        'Neos.ContentRepository:Root': true
    'Neos.Neos:Site':
      superTypes:
        'Neos.Neos:Document': true
    'Neos.Neos:Document': {}
    'Neos.Neos:Content': {}
    'Neos.Neos:ContentCollection': {}
    'Neos.ContentRepository.Testing:DocumentWithMain':
      superTypes:
        'Neos.Neos:Document': true
      childNodes:
        main:
          type: 'Neos.Neos:ContentCollection'
    'Neos.ContentRepository.Testing:Content':
      superTypes:
        'Neos.Neos:Content': true
      properties:
        text:
          type: string
    'Neos.ContentRepository.Testing:Shortcut':
      superTypes:
        'Neos.Neos:Document': true
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live"
    And I am in dimension space point {}
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value             |
      | nodeAggregateId | "root"            |
      | nodeTypeName    | "Neos.Neos:Sites" |
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                   | Value            |
      | nodeAggregateId       | "site"           |
      | nodeTypeName          | "Neos.Neos:Site" |
      | parentNodeAggregateId | "root"           |
      | nodeName              | "site"           |

  Scenario: publishChangesInDocument succeeds after node type change that tags tethered children
    # Step 1: Set up a user workspace
    Given the command CreateWorkspace is executed with payload:
      | Key                | Value                |
      | workspaceName      | "user-test"          |
      | baseWorkspaceName  | "live"               |
      | newContentStreamId | "user-cs-identifier" |
    And I am in workspace "user-test"

    # Step 2: Create a DocumentWithMain page with a tethered "main" ContentCollection child, and a content node into it
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId        | nodeTypeName                                    | parentNodeAggregateId | nodeName | tetheredDescendantNodeAggregateIds |
      | sir-david-nodenborough | Neos.ContentRepository.Testing:DocumentWithMain | site                  | document | {"main": "main-collection-id"}     |
      | nody-mc-nodeface       | Neos.ContentRepository.Testing:Content          | main-collection-id    | text1    |                                    |

    # Step 3: Change the page's node type to Shortcut (tags "main" and cascades to content node)
    And the command ChangeNodeAggregateType is executed with payload:
      | Key             | Value                                     |
      | workspaceName   | "user-test"                               |
      | nodeAggregateId | "sir-david-nodenborough"                  |
      | newNodeTypeName | "Neos.ContentRepository.Testing:Shortcut" |
      | strategy        | "mark_with_tag:removed"                   |

    # Step 5: Publish changes in document via WorkspacePublishingService — must not throw PartialWorkspaceRebaseFailed
    # The count covers: sir-david (type changed), main-collection (tagged), nody-mc-nodeface (tagged+created)
    When I publish the 3 changes in document "sir-david-nodenborough" from workspace "user-test" to "live"

    # Step 6: Assert page is Shortcut in live and tethered child is tagged (not hard-deleted)
    # nody-mc-nodeface was not in the published set so it does not exist in live
    Then I am in workspace "live" and dimension space point {}
    When VisibilityConstraints are set to "empty"
    And I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{}
    And I expect this node to be of type "Neos.ContentRepository.Testing:Shortcut"
    And I expect the node with aggregate identifier "main-collection-id" to be explicitly tagged "removed"
