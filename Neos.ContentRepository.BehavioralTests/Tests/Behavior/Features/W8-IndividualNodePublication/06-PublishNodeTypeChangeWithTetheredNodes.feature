@contentrepository @adapters=DoctrineDBAL
Feature: Partial publish after node type change tagging tethered children

  Publishing a node type change (Document→Shortcut) that tags tethered children
  should also correctly handle grandchild nodes that were inside those tethered children.

  See https://github.com/neos/neos-development-collection/pull/5767

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Content':
      properties:
        text:
          type: string

    'Neos.ContentRepository.Testing:ContentCollection':
      constraints:
        nodeTypes:
          'Neos.ContentRepository.Testing:Content': true

    'Neos.ContentRepository.Testing:Document':
      childNodes:
        main:
          type: 'Neos.ContentRepository.Testing:ContentCollection'

    'Neos.ContentRepository.Testing:Shortcut': []
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

    And the command CreateWorkspace is executed with payload:
      | Key                | Value                |
      | workspaceName      | "user-test"          |
      | baseWorkspaceName  | "live"               |
      | newContentStreamId | "user-cs-identifier" |

  Scenario: Publish node type change from Document to Shortcut that tags tethered child containing a grandchild
    # Step 1: Create a Document page with a tethered "main" child in the user workspace
    Given I am in workspace "user-test"
    And I am in dimension space point {}
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId        | nodeTypeName                            | parentNodeAggregateId  | nodeName | tetheredDescendantNodeAggregateIds |
      | sir-david-nodenborough | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | document | {"main": "main-collection-id"}     |

    # Step 2: Create a Content node inside the tethered "main" collection
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId  | nodeTypeName                           | parentNodeAggregateId | nodeName |
      | nody-mc-nodeface | Neos.ContentRepository.Testing:Content | main-collection-id    | text1    |

    # Step 3: Change the page's node type to Shortcut (which has no tethered children → tags "main" and cascades to text node)
    And the command ChangeNodeAggregateType is executed with payload:
      | Key             | Value                                     |
      | workspaceName   | "user-test"                               |
      | nodeAggregateId | "sir-david-nodenborough"                  |
      | newNodeTypeName | "Neos.ContentRepository.Testing:Shortcut" |
      | strategy        | "mark_with_tag:removed"                   |

    # Step 4: Partial publish with only the page node ID (simulates what the Neos UI does after resolveNodeIdsToPublishOrDiscard excludes the removed text node)
    When the command PublishIndividualNodesFromWorkspace is executed with payload:
      | Key                             | Value                          |
      | workspaceName                   | "user-test"                    |
      | nodesToPublish                  | ["sir-david-nodenborough"]     |
      | contentStreamIdForRemainingPart | "user-cs-identifier-remaining" |

    # Step 5: Assert the publish succeeded — page exists in live as Shortcut, tethered child is tagged (not removed)
    # nody-mc-nodeface was only created in user-test and was not in nodesToPublish, so it does not exist in live
    Then I am in workspace "live" and dimension space point {}
    When VisibilityConstraints are set to "empty"
    And I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{}
    And I expect this node to be of type "Neos.ContentRepository.Testing:Shortcut"
    And I expect the node with aggregate identifier "main-collection-id" to be explicitly tagged "removed"

    # User workspace remaining — nody-mc-nodeface still exists (was not published) and inherits the "removed" tag
    And I am in workspace "user-test" and dimension space point {}
    When VisibilityConstraints are set to "empty"
    And I expect node aggregate identifier "sir-david-nodenborough" to lead to node user-cs-identifier-remaining;sir-david-nodenborough;{}
    And I expect this node to be of type "Neos.ContentRepository.Testing:Shortcut"
    And I expect the node with aggregate identifier "main-collection-id" to be explicitly tagged "removed"
    And I expect the node with aggregate identifier "nody-mc-nodeface" to inherit the tag "removed"
