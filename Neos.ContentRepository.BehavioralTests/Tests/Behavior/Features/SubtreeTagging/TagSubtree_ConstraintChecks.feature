@contentrepository @adapters=DoctrineDBAL
Feature: Constraint checks on tag subtree

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
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live" and dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "root"                        |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | nodeTypeName                            | parentNodeAggregateId | nodeName | originDimensionSpacePoint |
      | a               | Neos.ContentRepository.Testing:Document | root                  | a        | {"language":"mul"}        |
      | a1              | Neos.ContentRepository.Testing:Document | a                     | a1       | {"language":"de"}         |
      | a1a             | Neos.ContentRepository.Testing:Document | a1                    | a1a      | {"language":"de"}         |

  Scenario: Untagging a node without tags
    Given I am in dimension space point {"language":"de"}
    Then I expect exactly 5 events to be published on stream with prefix "ContentStream:cs-identifier"
    When the command UntagSubtree is executed with payload and exceptions are caught:
      | Key                          | Value         |
      | nodeAggregateId              | "a1"          |
      | nodeVariantSelectionStrategy | "allVariants" |
      | tag                          | "tag1"        |
    Then the last command should have thrown an exception of type "SubtreeIsNotTagged"

  Scenario: Untagging a node that is only implicitly tagged (inherited)
    Given I am in dimension space point {"language":"de"}
    When the command TagSubtree is executed with payload:
      | Key                          | Value         |
      | nodeAggregateId              | "a1"          |
      | nodeVariantSelectionStrategy | "allVariants" |
      | tag                          | "tag1"        |
    Then I expect exactly 6 events to be published on stream with prefix "ContentStream:cs-identifier"
    And event at index 5 is of type "SubtreeWasTagged" with payload:
      | Key                          | Expected                                                  |
      | contentStreamId              | "cs-identifier"                                           |
      | nodeAggregateId              | "a1"                                                      |
      | affectedDimensionSpacePoints | [{"language":"de"},{"language":"ltz"},{"language":"gsw"}] |
      | tag                          | "tag1"                                                    |
    When the command UntagSubtree is executed with payload and exceptions are caught:
      | Key                          | Value         |
      | nodeAggregateId              | "a1a"         |
      | nodeVariantSelectionStrategy | "allVariants" |
      | tag                          | "tag1"        |
    Then the last command should have thrown an exception of type "SubtreeIsNotTagged"

  Scenario: Tagging the same node twice with the same subtree tag
    Given I am in dimension space point {"language":"de"}
    When the command TagSubtree is executed with payload:
      | Key                          | Value         |
      | nodeAggregateId              | "a1"          |
      | nodeVariantSelectionStrategy | "allVariants" |
      | tag                          | "tag1"        |
    Then I expect exactly 6 events to be published on stream with prefix "ContentStream:cs-identifier"
    And event at index 5 is of type "SubtreeWasTagged" with payload:
      | Key                          | Expected                                                  |
      | contentStreamId              | "cs-identifier"                                           |
      | nodeAggregateId              | "a1"                                                      |
      | affectedDimensionSpacePoints | [{"language":"de"},{"language":"ltz"},{"language":"gsw"}] |
      | tag                          | "tag1"                                                    |
    When the command TagSubtree is executed with payload and exceptions are caught:
      | Key                          | Value         |
      | nodeAggregateId              | "a1"          |
      | nodeVariantSelectionStrategy | "allVariants" |
      | tag                          | "tag1"        |
    Then the last command should have thrown an exception of type "SubtreeIsAlreadyTagged"
