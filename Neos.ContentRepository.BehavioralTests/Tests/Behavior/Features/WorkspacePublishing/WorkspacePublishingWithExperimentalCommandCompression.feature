@contentrepository @adapters=DoctrineDBAL
Feature: Workspace publishing with experimental command compression

  If people write texts, the Neos UI will send many SetSerializedNodeProperties commands touching the same node.
  This change compacts this to a single SetSerializedNodeProperties command, so that projection rebase is sped up.

  Background:
    Given I have no content dimensions
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': {}
    'Neos.ContentRepository.Testing:Content':
      properties:
        text:
          type: string
    """
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And the graph projection is fully up to date
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | contentStreamId | "cs-identifier"               |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |

    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                    |
      | contentStreamId             | "cs-identifier"                          |
      | nodeAggregateId             | "nody-mc-nodeface"                       |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Content" |
      | originDimensionSpacePoint   | {}                                       |
      | coveredDimensionSpacePoints | [{}]                                     |
      | parentNodeAggregateId       | "lady-eleonode-rootford"                 |
      | nodeName                    | "child"                                  |
      | nodeAggregateClassification | "regular"                                |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                    |
      | contentStreamId             | "cs-identifier"                          |
      | nodeAggregateId             | "hamish-mc-stonehill"                    |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Content" |
      | originDimensionSpacePoint   | {}                                       |
      | coveredDimensionSpacePoints | [{}]                                     |
      | parentNodeAggregateId       | "lady-eleonode-rootford"                 |
      | nodeName                    | "child"                                  |
      | nodeAggregateClassification | "regular"                                |

    And the graph projection is fully up to date

    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                 |
      | contentStreamId           | "cs-identifier"       |
      | nodeAggregateId           | "nody-mc-nodeface"    |
      | originDimensionSpacePoint | {}                    |
      | propertyValues            | {"text": "content 1"} |
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                         |
      | contentStreamId           | "cs-identifier"               |
      | nodeAggregateId           | "hamish-mc-stonehill"         |
      | originDimensionSpacePoint | {}                            |
      | propertyValues            | {"text": "content stonehill"} |
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
    # we need to trigger a rebase for compaction to happen; so we need one more event on the original content stream
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value              |
      | contentStreamId           | "cs-identifier"    |
      | nodeAggregateId           | "nody-mc-nodeface" |
      | originDimensionSpacePoint | {}                 |
      | propertyValues            | {"text": "c-orig"} |

    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                 |
      | contentStreamId           | "user-cs-identifier"  |
      | nodeAggregateId           | "nody-mc-nodeface"    |
      | originDimensionSpacePoint | {}                    |
      | propertyValues            | {"text": "content 2"} |
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                 |
      | contentStreamId           | "user-cs-identifier"  |
      | nodeAggregateId           | "nody-mc-nodeface"    |
      | originDimensionSpacePoint | {}                    |
      | propertyValues            | {"text": "content 3"} |
    And the graph projection is fully up to date

  Scenario: publishing without compression
    When the command RebaseWorkspace is executed with payload:
      | Key           | Value       |
      | workspaceName | "user-test" |
    And the graph projection is fully up to date
    When the command PublishWorkspace is executed with payload:
      | Key           | Value       |
      | workspaceName | "user-test" |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{}
    And I expect this node to have the following properties:
      | Key  | Value       |
      | text | "content 3" |

    Then I expect exactly 9 events to be published on stream "ContentStream:cs-identifier"


  Scenario: publishing with simple compression
    # compaction of the two write-events into one -> we expect one less event than above.
    When the content repository experiment "compactCommands" has value "compress-simple"
    When the command RebaseWorkspace is executed with payload:
      | Key           | Value       |
      | workspaceName | "user-test" |
    And the graph projection is fully up to date
    When the command PublishWorkspace is executed with payload:
      | Key           | Value       |
      | workspaceName | "user-test" |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{}
    And I expect this node to have the following properties:
      | Key  | Value       |
      | text | "content 3" |

    # THIS IS THE MODIFICATION
    Then I expect exactly 8 events to be published on stream "ContentStream:cs-identifier"


  Scenario: simple compression only happens up to a modification of ANOTHER node
    # node1 <-\
    # node1 <-- COMPRESSED
    # node2
    # node1 <-\
    # node1 <-- COMPRESSED
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                 |
      | contentStreamId           | "user-cs-identifier"  |
      | nodeAggregateId           | "hamish-mc-stonehill" |
      | originDimensionSpacePoint | {}                    |
      | propertyValues            | {"text": "other"}     |

    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                 |
      | contentStreamId           | "user-cs-identifier"  |
      | nodeAggregateId           | "nody-mc-nodeface"    |
      | originDimensionSpacePoint | {}                    |
      | propertyValues            | {"text": "content 2"} |
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                 |
      | contentStreamId           | "user-cs-identifier"  |
      | nodeAggregateId           | "nody-mc-nodeface"    |
      | originDimensionSpacePoint | {}                    |
      | propertyValues            | {"text": "content 3"} |
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                 |
      | contentStreamId           | "user-cs-identifier"  |
      | nodeAggregateId           | "nody-mc-nodeface"    |
      | originDimensionSpacePoint | {}                    |
      | propertyValues            | {"text": "content 4"} |

    When the content repository experiment "compactCommands" has value "compress-simple"
    When the command RebaseWorkspace is executed with payload:
      | Key           | Value       |
      | workspaceName | "user-test" |
    And the graph projection is fully up to date
    When the command PublishWorkspace is executed with payload:
      | Key           | Value       |
      | workspaceName | "user-test" |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{}
    And I expect this node to have the following properties:
      | Key  | Value       |
      | text | "content 4" |

    # 10 instead of 13 events
    Then I expect exactly 10 events to be published on stream "ContentStream:cs-identifier"

