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
    And I am in workspace "live" and dimension space point {}
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

  Scenario: Tagging the same node twice with the same subtree tag is ignored
    When the command TagSubtree is executed with payload:
      | Key                          | Value         |
      | nodeAggregateId              | "a1"          |
      | nodeVariantSelectionStrategy | "allVariants" |
      | tag                          | "tag1"        |
    And the graph projection is fully up to date
    Then I expect exactly 14 events to be published on stream with prefix "ContentStream:cs-identifier"
    And event at index 13 is of type "SubtreeWasTagged" with payload:
      | Key                          | Expected        |
      | contentStreamId              | "cs-identifier" |
      | nodeAggregateId              | "a1"            |
      | affectedDimensionSpacePoints | [[]]            |
      | tag                          | "tag1"          |
    When the command TagSubtree is executed with payload:
      | Key                          | Value         |
      | nodeAggregateId              | "a1"          |
      | nodeVariantSelectionStrategy | "allVariants" |
      | tag                          | "tag1"        |
    Then I expect exactly 14 events to be published on stream with prefix "ContentStream:cs-identifier"

  Scenario: Untagging a node without tags is ignored
    Then I expect exactly 13 events to be published on stream with prefix "ContentStream:cs-identifier"
    When the command UntagSubtree is executed with payload:
      | Key                          | Value         |
      | nodeAggregateId              | "a1"          |
      | nodeVariantSelectionStrategy | "allVariants" |
      | tag                          | "tag1"        |
    Then I expect exactly 13 events to be published on stream with prefix "ContentStream:cs-identifier"

  Scenario: Untagging a node that is only implicitly tagged (inherited) is ignored
    When the command TagSubtree is executed with payload:
      | Key                          | Value         |
      | nodeAggregateId              | "a1"          |
      | nodeVariantSelectionStrategy | "allVariants" |
      | tag                          | "tag1"        |
    And the graph projection is fully up to date
    Then I expect exactly 14 events to be published on stream with prefix "ContentStream:cs-identifier"
    And event at index 13 is of type "SubtreeWasTagged" with payload:
      | Key                          | Expected        |
      | contentStreamId              | "cs-identifier" |
      | nodeAggregateId              | "a1"            |
      | affectedDimensionSpacePoints | [[]]            |
      | tag                          | "tag1"          |
    When the command UntagSubtree is executed with payload:
      | Key                          | Value         |
      | nodeAggregateId              | "a1a"         |
      | nodeVariantSelectionStrategy | "allVariants" |
      | tag                          | "tag1"        |
    Then I expect exactly 14 events to be published on stream with prefix "ContentStream:cs-identifier"

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
    And I am in workspace "live"
    Then I expect the graph projection to consist of exactly 12 nodes

    When I am in workspace "live" and dimension space point {}
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

    When I execute the findSubtree query for entry node aggregate id "a" I expect the following tree with tags:
    """
    a
     a1 (tag1*)
      a1a (tag4*,tag1)
       a1a1 (tag1*,tag4)
        a1a1a (tag1,tag4)
        a1a1b (tag1,tag4)
       a1a2 (tag1,tag4)
      a1b (tag1)
     a2
    """
    When I execute the findSubtree query for entry node aggregate id "b" I expect the following tree with tags:
    """
    b (tag2*)
     b1 (tag3*,tag2)
    """

    When the command MoveNodeAggregate is executed with payload:
      | Key                      | Value           |
      | nodeAggregateId          | "a1a"           |
      | newParentNodeAggregateId | "b1"            |
    And the graph projection is fully up to date
    When I execute the findSubtree query for entry node aggregate id "a" I expect the following tree with tags:
    """
    a
     a1 (tag1*)
      a1b (tag1)
     a2
    """
    When I execute the findSubtree query for entry node aggregate id "b" I expect the following tree with tags:
    """
    b (tag2*)
     b1 (tag3*,tag2)
      a1a (tag4*,tag2,tag3)
       a1a1 (tag1*,tag2,tag3,tag4)
        a1a1a (tag1,tag2,tag3,tag4)
        a1a1b (tag1,tag2,tag3,tag4)
       a1a2 (tag2,tag3,tag4)
    """

    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                   | Value                                     |
      | nodeAggregateId       | "a1a3"                                    |
      | nodeTypeName          | "Neos.ContentRepository.Testing:Document" |
      | parentNodeAggregateId | "a1a"                                     |
    When I execute the findSubtree query for entry node aggregate id "b" I expect the following tree with tags:
    """
    b (tag2*)
     b1 (tag3*,tag2)
      a1a (tag4*,tag2,tag3)
       a1a1 (tag1*,tag2,tag3,tag4)
        a1a1a (tag1,tag2,tag3,tag4)
        a1a1b (tag1,tag2,tag3,tag4)
       a1a2 (tag2,tag3,tag4)
       a1a3 (tag2,tag3,tag4)
    """

    When the command UntagSubtree is executed with payload:
      | Key                          | Value         |
      | nodeAggregateId              | "a1a"         |
      | nodeVariantSelectionStrategy | "allVariants" |
      | tag                          | "tag4"        |
    When I execute the findSubtree query for entry node aggregate id "b" I expect the following tree with tags:
    """
    b (tag2*)
     b1 (tag3*,tag2)
      a1a (tag2,tag3)
       a1a1 (tag1*,tag2,tag3)
        a1a1a (tag1,tag2,tag3)
        a1a1b (tag1,tag2,tag3)
       a1a2 (tag2,tag3)
       a1a3 (tag2,tag3)
    """
