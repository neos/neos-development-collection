Feature: Tag subtree with dimensions

  As a user of the CR I want to tag a node and expect its descendants to also be tagged.

  These are the test cases with dimensions being involved

  Background:
    Given using the following content dimensions:
      | Identifier | Values                | Generalizations                     |
      | language   | mul, de, en, gsw, ltz | ltz->de->mul, gsw->de->mul, en->mul |
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
      | newContentStreamId   | "cs-identifier"      |
    And I am in workspace "live"
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "root"                        |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | nodeTypeName                            | parentNodeAggregateId | nodeName | originDimensionSpacePoint |
      | a               | Neos.ContentRepository.Testing:Document | root                  | a        | {"language":"mul"}        |
      | a1              | Neos.ContentRepository.Testing:Document | a                     | a1       | {"language":"de"}         |
      | a1a             | Neos.ContentRepository.Testing:Document | a1                    | a1a      | {"language":"de"}         |

  Scenario: Subtree tags are properly copied upon node specializations
    Given I am in dimension space point {"language":"de"}

    When the command CreateNodeVariant is executed with payload:
      | Key             | Value              |
      | nodeAggregateId | "a1"               |
      | sourceOrigin    | {"language":"de"}  |
      | targetOrigin    | {"language":"mul"} |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "a1"                 |
      | nodeVariantSelectionStrategy | "allSpecializations" |
      | tag                          | "tag1"               |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "a1a"                |
      | nodeVariantSelectionStrategy | "allSpecializations" |
      | tag                          | "tag2"               |

    When the command CreateNodeVariant is executed with payload:
      | Key             | Value              |
      | nodeAggregateId | "a1a"              |
      | sourceOrigin    | {"language":"de"}  |
      | targetOrigin    | {"language":"mul"} |


    Then I expect node aggregate identifier "a" to lead to node cs-identifier;a;{"language":"mul"}
    And I expect this node to have the following subtree with tags:
    """
    a
     a1 (tag1*)
      a1a (tag2*,tag1)
    """

    When I am in dimension space point {"language":"mul"}
    Then I expect node aggregate identifier "a" to lead to node cs-identifier;a;{"language":"mul"}
    And I expect this node to have the following subtree with tags:
    """
    a
     a1
      a1a (tag2*)
    """

  Scenario: Subtree tags are properly copied upon node generalizations
    Given I am in dimension space point {"language":"de"}

    When the command CreateNodeVariant is executed with payload:
      | Key             | Value              |
      | nodeAggregateId | "a"                |
      | sourceOrigin    | {"language":"mul"} |
      | targetOrigin    | {"language":"de"}  |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "a"                  |
      | nodeVariantSelectionStrategy | "allSpecializations" |
      | tag                          | "tag1"               |

    Given I am in dimension space point {"language":"mul"}

    And the command TagSubtree is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "a"                  |
      | nodeVariantSelectionStrategy | "allSpecializations" |
      | tag                          | "tag2"               |

    When the command CreateNodeVariant is executed with payload:
      | Key             | Value              |
      | nodeAggregateId | "a1"               |
      | sourceOrigin    | {"language":"de"}  |
      | targetOrigin    | {"language":"mul"} |

    Then I expect node aggregate identifier "a" to lead to node cs-identifier;a;{"language":"mul"}
    And I expect this node to have the following subtree with tags:
    """
    a (tag2*)
     a1 (tag2)
    """

    When I am in dimension space point {"language":"de"}
    Then I expect node aggregate identifier "a" to lead to node cs-identifier;a;{"language":"de"}
    And I expect this node to have the following subtree with tags:
    """
    a (tag1*,tag2*)
     a1 (tag1,tag2)
      a1a (tag1,tag2)
    """

  # KNOWN BUG (expected to FAIL until removeSubtreeTag decides "still inherited?" per dimension):
  # removeSubtreeTag computes ONCE, globally across all affected dimensions, whether the untagged node
  # still inherits the tag from an ancestor, then applies that single decision to every dimension.
  # When the ancestor carries the tag in some dimensions but not others, the node is untagged
  # incorrectly in the dimension(s) where it does NOT actually inherit.
  Scenario: Untagging is decided per dimension when an ancestor is tagged only in some dimensions
    Given the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | nodeTypeName                            | parentNodeAggregateId | nodeName | originDimensionSpacePoint |
      | b               | Neos.ContentRepository.Testing:Document | a                     | b        | {"language":"mul"}        |
      | b1              | Neos.ContentRepository.Testing:Document | b                     | b1       | {"language":"mul"}        |

    # ancestor "a" is tagged only in the "de" specialization branch (de, gsw, ltz) - NOT in mul or en
    And the command TagSubtree is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "a"                  |
      | coveredDimensionSpacePoint   | {"language":"de"}    |
      | nodeVariantSelectionStrategy | "allSpecializations" |
      | tag                          | "tag1"               |

    # "b" is then tagged EXPLICITLY in every dimension
    And the command TagSubtree is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "b"                  |
      | coveredDimensionSpacePoint   | {"language":"mul"}   |
      | nodeVariantSelectionStrategy | "allSpecializations" |
      | tag                          | "tag1"               |

    # untag "b" everywhere at once: in "de" it STILL inherits tag1 from "a" (explicit true -> inherited
    # null), but in "en"/"mul" it does NOT inherit, so tag1 must be removed entirely from b and b1.
    When the command UntagSubtree is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "b"                  |
      | coveredDimensionSpacePoint   | {"language":"mul"}   |
      | nodeVariantSelectionStrategy | "allSpecializations" |
      | tag                          | "tag1"               |

    # de: "a" is tagged, so "b" still inherits -> b keeps tag1 as inherited, b1 keeps it inherited.
    # (a1/a1a from the Background are children of "a" in "de" too, so they also inherit tag1.)
    When I am in dimension space point {"language":"de"}
    Then I expect node aggregate identifier "a" to lead to node cs-identifier;a;{"language":"mul"}
    And I expect this node to have the following subtree with tags:
    """
    a (tag1*)
     a1 (tag1)
      a1a (tag1)
     b (tag1)
      b1 (tag1)
    """

    # en: "a" is NOT tagged, so "b" no longer inherits -> tag1 removed entirely from b and b1
    When I am in dimension space point {"language":"en"}
    Then I expect node aggregate identifier "a" to lead to node cs-identifier;a;{"language":"mul"}
    And I expect this node to have the following subtree with tags:
    """
    a
     b
      b1
    """

  Scenario: Subtree tags are properly copied upon node variant recreation
    When the command CreateWorkspace is executed with payload:
      | Key                | Value        |
      | workspaceName      | "user-ws"    |
      | baseWorkspaceName  | "live"       |
      | newContentStreamId | "user-cs-id" |
    When the command CreateNodeVariant is executed with payload:
      | Key             | Value              |
      | workspaceName   | "user-ws"          |
      | nodeAggregateId | "a1"               |
      | sourceOrigin    | {"language":"de"}  |
      | targetOrigin    | {"language":"gsw"} |
    When the command CreateNodeVariant is executed with payload:
      | Key             | Value              |
      | workspaceName   | "user-ws"          |
      | nodeAggregateId | "a1a"              |
      | sourceOrigin    | {"language":"de"}  |
      | targetOrigin    | {"language":"gsw"} |
    And the command PublishWorkspace is executed with payload:
      | Key                | Value            |
      | workspaceName      | "user-ws"        |
      | newContentStreamId | "new-user-cs-id" |

    And the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | workspaceName                | "user-ws"            |
      | nodeAggregateId              | "a1"                 |
      | coveredDimensionSpacePoint   | {"language":"gsw"}   |
      | nodeVariantSelectionStrategy | "allSpecializations" |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                |
      | workspaceName                | "user-ws"            |
      | nodeAggregateId              | "a"                  |
      | coveredDimensionSpacePoint   | {"language":"de"}    |
      | nodeVariantSelectionStrategy | "allSpecializations" |
      | tag                          | "tag1"               |

    And the command CreateNodeVariant is executed with payload:
      | Key             | Value              |
      | workspaceName   | "user-ws"          |
      | nodeAggregateId | "a1"               |
      | sourceOrigin    | {"language":"de"}  |
      | targetOrigin    | {"language":"gsw"} |
    When the command CreateNodeVariant is executed with payload:
      | Key             | Value              |
      | workspaceName   | "user-ws"          |
      | nodeAggregateId | "a1a"              |
      | sourceOrigin    | {"language":"de"}  |
      | targetOrigin    | {"language":"gsw"} |
    And I am in workspace "user-ws" and dimension space point {"language":"gsw"}
    Then I expect node aggregate identifier "a" to lead to node new-user-cs-id;a;{"language":"mul"}
    And I expect this node to have the following subtree with tags:
    """
    a (tag1*)
     a1 (tag1)
      a1a (tag1)
    """
