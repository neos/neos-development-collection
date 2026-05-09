Feature: Tag subtree without dimensions

  As a user of the CR I want to tag a node aggregate and expect its descendants to also be tagged.

  These are the test cases without dimensions being involved

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Tethered': {}
    'Neos.ContentRepository.Testing:Document': {}
    'Neos.ContentRepository.Testing:DocumentWithTethered':
      childNodes:
        tethered:
          type: 'Neos.ContentRepository.Testing:Tethered'
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live" and dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "root"                        |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | nodeTypeName                                        | parentNodeAggregateId | tetheredDescendantNodeAggregateIds |
      | a               | Neos.ContentRepository.Testing:Document             | root                  |                                    |
      | a1              | Neos.ContentRepository.Testing:Document             | a                     |                                    |
      | a1a             | Neos.ContentRepository.Testing:Document             | a1                    |                                    |
      | a1a1            | Neos.ContentRepository.Testing:DocumentWithTethered | a1a                   | {"tethered": "a1a1a"}              |
      | a1a1b           | Neos.ContentRepository.Testing:Document             | a1a1                  |                                    |
      | a1a2            | Neos.ContentRepository.Testing:Document             | a1a                   |                                    |
      | a1b             | Neos.ContentRepository.Testing:Document             | a1                    |                                    |
      | a2              | Neos.ContentRepository.Testing:Document             | a                     |                                    |
      | b               | Neos.ContentRepository.Testing:Document             | root                  |                                    |
      | b1              | Neos.ContentRepository.Testing:Document             | b                     |                                    |

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

    Then I expect node aggregate identifier "a" to lead to node cs-identifier;a;{}
    And I expect this node to have the following subtree with tags:
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
    Then I expect node aggregate identifier "b" to lead to node cs-identifier;b;{}
    And I expect this node to have the following subtree with tags:
    """
    b (tag2*)
     b1 (tag3*,tag2)
    """

    When the command MoveNodeAggregate is executed with payload:
      | Key                      | Value |
      | nodeAggregateId          | "a1a" |
      | newParentNodeAggregateId | "b1"  |
    Then I expect node aggregate identifier "a" to lead to node cs-identifier;a;{}
    And I expect this node to have the following subtree with tags:
    """
    a
     a1 (tag1*)
      a1b (tag1)
     a2
    """
    Then I expect node aggregate identifier "b" to lead to node cs-identifier;b;{}
    And I expect this node to have the following subtree with tags:
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
    Then I expect node aggregate identifier "b" to lead to node cs-identifier;b;{}
    And I expect this node to have the following subtree with tags:
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
    Then I expect node aggregate identifier "b" to lead to node cs-identifier;b;{}
    And I expect this node to have the following subtree with tags:
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

  Scenario: Tagging root nodes
    When the command TagSubtree is executed with payload:
      | Key                          | Value         |
      | nodeAggregateId              | "root"       |
      | nodeVariantSelectionStrategy | "allVariants" |
      | tag                          | "tag1"        |
    Then I expect node aggregate identifier "root" to lead to node cs-identifier;root;{}
    And I expect this node to have the following subtree with tags:
    """
    root (tag1*)
     a (tag1)
      a1 (tag1)
       a1a (tag1)
        a1a1 (tag1)
         a1a1a (tag1)
         a1a1b (tag1)
        a1a2 (tag1)
       a1b (tag1)
      a2 (tag1)
     b (tag1)
      b1 (tag1)
    """

  Scenario: Tagging tethered nodes
    When the command TagSubtree is executed with payload:
      | Key                          | Value         |
      | nodeAggregateId              | "a1a1a"       |
      | nodeVariantSelectionStrategy | "allVariants" |
      | tag                          | "tag1"        |
    Then I expect node aggregate identifier "a1a1" to lead to node cs-identifier;a1a1;{}
    And I expect this node to have the following subtree with tags:
    """
    a1a1
     a1a1a (tag1*)
     a1a1b
    """
