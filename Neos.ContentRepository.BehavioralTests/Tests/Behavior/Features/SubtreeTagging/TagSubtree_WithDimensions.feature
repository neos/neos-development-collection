@contentrepository @adapters=DoctrineDBAL
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
      | nodeAggregateId | nodeTypeName                            | parentNodeAggregateId | nodeName | originDimensionSpacePoint |
      | a               | Neos.ContentRepository.Testing:Document | root                  | a        | {"language":"mul"}        |
      | a1              | Neos.ContentRepository.Testing:Document | a                     | a1       | {"language":"de"}         |
      | a1a             | Neos.ContentRepository.Testing:Document | a1                    | a1a      | {"language":"de"}         |

  Scenario:
    Given I am in dimension space point {"language":"de"}

    When the command CreateNodeVariant is executed with payload:
      | Key             | Value              |
      | nodeAggregateId | "a1"               |
      | sourceOrigin    | {"language":"de"}  |
      | targetOrigin    | {"language":"mul"} |
    And the graph projection is fully up to date

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
    And the graph projection is fully up to date

    When I execute the findSubtree query for entry node aggregate id "a" I expect the following tree with tags:
    """
    a
     a1 (tag1*)
      a1a (tag2*,tag1)
    """

    When I am in dimension space point {"language":"mul"}
    And I execute the findSubtree query for entry node aggregate id "a" I expect the following tree with tags:
    """
    a
     a1
      a1a (tag2*)
    """
