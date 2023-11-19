@contentrepository @adapters=DoctrineDBAL
Feature: Tag subtree without dimensions

  As a user of the CR I want to tag a node aggregate and expect its descendants to also be tagged.

  These are the test cases without dimensions being involved

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Document': {}
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | workspaceTitle       | "Live"               |
      | workspaceDescription | "The live workspace" |
      | newContentStreamId   | "cs-identifier"      |
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "root"                        |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | nodeTypeName                            | parentNodeAggregateId | nodeName |
      | a               | Neos.ContentRepository.Testing:Document | root                  | a        |
      | a1              | Neos.ContentRepository.Testing:Document | a                     | a1       |
      | a1a             | Neos.ContentRepository.Testing:Document | a1                    | a1a      |
      | a1a1            | Neos.ContentRepository.Testing:Document | a1a                   | a1a1     |
      | a1a1a           | Neos.ContentRepository.Testing:Document | a1a1                  | a1a1a    |
      | a1a1b           | Neos.ContentRepository.Testing:Document | a1a1                  | a1a1b    |
      | a1a2            | Neos.ContentRepository.Testing:Document | a1a                   | a1a2     |
      | a1b             | Neos.ContentRepository.Testing:Document | a1                    | a1b      |
      | a2              | Neos.ContentRepository.Testing:Document | a                     | a2       |
      | b               | Neos.ContentRepository.Testing:Document | root                  | b        |
      | b1              | Neos.ContentRepository.Testing:Document | b                     | b1       |

  Scenario: Tagging subtree with arbitrary strategy since dimensions are not involved
    When the command TagSubtree is executed with payload:
      | Key                          | Value         |
      | nodeAggregateId              | "a1"          |
      | nodeVariantSelectionStrategy | "allVariants" |
      | tag                          | "tag1"        |

    Then I expect exactly 14 events to be published on stream with prefix "ContentStream:cs-identifier"
    And event at index 13 is of type "SubtreeWasTagged" with payload:
      | Key                          | Expected        |
      | contentStreamId              | "cs-identifier" |
      | nodeAggregateId              | "a1"            |
      | affectedDimensionSpacePoints | [[]]            |
      | tag                          | "tag1"          |

    When the graph projection is fully up to date
    And I am in content stream "cs-identifier"
    Then I expect the graph projection to consist of exactly 12 nodes

    When I am in content stream "cs-identifier" and dimension space point {}
    Then I expect the node with aggregate identifier "a1" to be explicitly tagged "tag1"
    Then I expect the node with aggregate identifier "a1a" to inherit the tag "tag1"
    Then I expect the node with aggregate identifier "a1a1" to inherit the tag "tag1"
    Then I expect the node with aggregate identifier "a1a1b" to inherit the tag "tag1"

    When the command TagSubtree is executed with payload:
      | Key                          | Value         |
      | nodeAggregateId              | "a1a1"        |
      | nodeVariantSelectionStrategy | "allVariants" |
      | tag                          | "tag1"        |
    And the command TagSubtree is executed with payload:
      | Key                          | Value         |
      | nodeAggregateId              | "b"           |
      | nodeVariantSelectionStrategy | "allVariants" |
      | tag                          | "tag2"        |
    And the command TagSubtree is executed with payload:
      | Key                          | Value         |
      | nodeAggregateId              | "b1"          |
      | nodeVariantSelectionStrategy | "allVariants" |
      | tag                          | "tag3"        |
    And the command TagSubtree is executed with payload:
      | Key                          | Value         |
      | nodeAggregateId              | "a1a"         |
      | nodeVariantSelectionStrategy | "allVariants" |
      | tag                          | "tag4"        |
    And the graph projection is fully up to date
    Then I expect the node with aggregate identifier "a1" to be explicitly tagged "tag1"
    Then I expect the node with aggregate identifier "a1a" to inherit the tag "tag1"
    Then I expect the node with aggregate identifier "a1a1" to be explicitly tagged "tag1"
    Then I expect the node with aggregate identifier "a1a1b" to inherit the tag "tag1"

    When the command MoveNodeAggregate is executed with payload:
      | Key                      | Value           |
      | contentStreamId          | "cs-identifier" |
      | nodeAggregateId          | "a1a"           |
      | newParentNodeAggregateId | "b1"            |
    And the graph projection is fully up to date
    Then I expect the node with aggregate identifier "a1a" to not contain the tag "tag1"
    Then I expect the node with aggregate identifier "a1a" to be explicitly tagged "tag4"
    And I expect the node with aggregate identifier "a1a" to inherit the tag "tag2"
    And I expect the node with aggregate identifier "a1a" to inherit the tag "tag3"
