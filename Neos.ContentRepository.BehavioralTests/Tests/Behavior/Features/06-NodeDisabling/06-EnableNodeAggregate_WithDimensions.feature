Feature: Enable a node aggregate

  As a user of the CR I want to disable a node aggregate and expect its descendants to also be disabled.

  These are the test cases with dimensions being involved

  Background:
    Given using the following content dimensions:
      | Identifier | Values                | Generalizations                     |
      | language   | mul, de, en, gsw, ltz | ltz->de->mul, gsw->de->mul, en->mul |
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Document':
      properties:
        references:
          type: references
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | newContentStreamId   | "cs-identifier"      |
    And I am in workspace "live" and dimension space point {"language":"mul"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId         | nodeTypeName                            | parentNodeAggregateId  | nodeName            |
      | preceding-nodenborough  | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | preceding-document  |
      | sir-david-nodenborough  | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | document            |
      | succeeding-nodenborough | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | succeeding-document |
      | nody-mc-nodeface        | Neos.ContentRepository.Testing:Document | sir-david-nodenborough | child-document      |
      | the-great-nodini        | Neos.ContentRepository.Testing:Document | sir-david-nodenborough | court-magician      |
    And the command SetNodeReferences is executed with payload:
      | Key                   | Value                                  |
      | sourceNodeAggregateId | "preceding-nodenborough"               |
      | references            | [{"referenceName": "references", "references": [{"target": "sir-david-nodenborough"}]}] |
    # We need both a real and a virtual specialization to test the different selection strategies
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "sir-david-nodenborough" |
      | sourceOrigin    | {"language":"mul"}       |
      | targetOrigin    | {"language":"ltz"}       |
    # Disable our reference node aggregate in all variants
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | nodeVariantSelectionStrategy | "allVariants"            |
    # Explicitly disable a child node aggregate in all variants
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value              |
      | nodeAggregateId              | "the-great-nodini" |
      | nodeVariantSelectionStrategy | "allVariants"      |
    # Set the DSP to the "central" variant having variants of all kind
    And I am in dimension space point {"language":"de"}

  Scenario: Enable node aggregate with strategy allSpecializations
    When I am in dimension space point {"language":"de"}
    And the command EnableNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | nodeVariantSelectionStrategy | "allSpecializations"     |

    Then I expect exactly 12 events to be published on stream with prefix "ContentStream:cs-identifier"
    And event at index 11 is of type "SubtreeWasUntagged" with payload:
      | Key                          | Expected                                                  |
      | contentStreamId              | "cs-identifier"                                           |
      | nodeAggregateId              | "sir-david-nodenborough"                                  |
      | affectedDimensionSpacePoints | [{"language":"de"},{"language":"ltz"},{"language":"gsw"}] |
      | tag                          | "disabled"                                                |

    And I am in workspace "live"
    Then I expect the graph projection to consist of exactly 7 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""
    And I expect a node identified by cs-identifier;preceding-nodenborough;{"language":"mul"} to exist in the content graph
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{"language":"mul"} to exist in the content graph
    And I expect this node to be exactly explicitly tagged "disabled"
    And I expect this node to exactly inherit the tags ""
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{"language":"ltz"} to exist in the content graph
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""
    And I expect a node identified by cs-identifier;succeeding-nodenborough;{"language":"mul"} to exist in the content graph
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"language":"mul"} to exist in the content graph
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "disabled"
    And I expect a node identified by cs-identifier;the-great-nodini;{"language":"mul"} to exist in the content graph
    And I expect this node to be exactly explicitly tagged "disabled"
    And I expect this node to exactly inherit the tags ""

    And I expect the node aggregate "sir-david-nodenborough" to exist
    And I expect this node aggregate to disable dimension space points [{"language":"mul"},{"language":"en"}]

    And I expect the node aggregate "the-great-nodini" to exist
    And I expect this node aggregate to disable dimension space points [{"language":"mul"},{"language":"de"},{"language":"ltz"},{"language":"gsw"},{"language":"en"}]

    # Tests for the given variant
    When I am in dimension space point {"language":"de"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following subtree with tags:
    """
    lady-eleonode-rootford
     preceding-nodenborough
     sir-david-nodenborough
      nody-mc-nodeface
      the-great-nodini (disabled*)
     succeeding-nodenborough
    """

    # Tests for the generalization
    When I am in dimension space point {"language":"mul"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following subtree with tags:
    """
    lady-eleonode-rootford
     preceding-nodenborough
     sir-david-nodenborough (disabled*)
      nody-mc-nodeface (disabled)
      the-great-nodini (disabled*)
     succeeding-nodenborough
    """

    # Tests for the virtual specialization
    When I am in dimension space point {"language":"gsw"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following subtree with tags:
    """
    lady-eleonode-rootford
     preceding-nodenborough
     sir-david-nodenborough
      nody-mc-nodeface
      the-great-nodini (disabled*)
     succeeding-nodenborough
    """

    # Tests for the real specialization
    When I am in dimension space point {"language":"ltz"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following subtree with tags:
    """
    lady-eleonode-rootford
     preceding-nodenborough
     sir-david-nodenborough
      nody-mc-nodeface
      the-great-nodini (disabled*)
     succeeding-nodenborough
    """

    # Tests for the peer variant
    When I am in dimension space point {"language":"en"}
    And I expect this node to have the following subtree with tags:
    """
    lady-eleonode-rootford
     preceding-nodenborough
     sir-david-nodenborough (disabled*)
      nody-mc-nodeface (disabled)
      the-great-nodini (disabled*)
     succeeding-nodenborough
    """

  Scenario: Enable node aggregate with strategy allVariants
    When I am in dimension space point {"language":"de"}
    And the command EnableNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | nodeVariantSelectionStrategy | "allVariants"            |

    Then I expect exactly 12 events to be published on stream with prefix "ContentStream:cs-identifier"
    And event at index 11 is of type "SubtreeWasUntagged" with payload:
      | Key                          | Expected                                                                                       |
      | contentStreamId              | "cs-identifier"                                                                                |
      | nodeAggregateId              | "sir-david-nodenborough"                                                                       |
      | affectedDimensionSpacePoints | [{"language":"mul"},{"language":"de"},{"language":"en"},{"language":"gsw"},{"language":"ltz"}] |
      | tag                          | "disabled"                                                                                     |

    And I am in workspace "live"
    Then I expect the graph projection to consist of exactly 7 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""
    And I expect a node identified by cs-identifier;preceding-nodenborough;{"language":"mul"} to exist in the content graph
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{"language":"mul"} to exist in the content graph
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{"language":"ltz"} to exist in the content graph
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""
    And I expect a node identified by cs-identifier;succeeding-nodenborough;{"language":"mul"} to exist in the content graph
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"language":"mul"} to exist in the content graph
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""
    And I expect a node identified by cs-identifier;the-great-nodini;{"language":"mul"} to exist in the content graph
    And I expect this node to be exactly explicitly tagged "disabled"
    And I expect this node to exactly inherit the tags ""

    And I expect the node aggregate "sir-david-nodenborough" to exist
    And I expect this node aggregate to disable dimension space points []

    And I expect the node aggregate "the-great-nodini" to exist
    And I expect this node aggregate to disable dimension space points [{"language":"mul"},{"language":"de"},{"language":"ltz"},{"language":"gsw"},{"language":"en"}]

    # Tests for the given variant
    When I am in dimension space point {"language":"de"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following subtree with tags:
    """
    lady-eleonode-rootford
     preceding-nodenborough
     sir-david-nodenborough
      nody-mc-nodeface
      the-great-nodini (disabled*)
     succeeding-nodenborough
    """

    # Tests for the generalization
    When I am in dimension space point {"language":"mul"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following subtree with tags:
    """
    lady-eleonode-rootford
     preceding-nodenborough
     sir-david-nodenborough
      nody-mc-nodeface
      the-great-nodini (disabled*)
     succeeding-nodenborough
    """

    # Tests for the virtual specialization
    When I am in dimension space point {"language":"gsw"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following subtree with tags:
    """
    lady-eleonode-rootford
     preceding-nodenborough
     sir-david-nodenborough
      nody-mc-nodeface
      the-great-nodini (disabled*)
     succeeding-nodenborough
    """

    # Tests for the real specialization
    When I am in dimension space point {"language":"ltz"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following subtree with tags:
    """
    lady-eleonode-rootford
     preceding-nodenborough
     sir-david-nodenborough
      nody-mc-nodeface
      the-great-nodini (disabled*)
     succeeding-nodenborough
    """

    # Tests for the peer variant
    When I am in dimension space point {"language":"en"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following subtree with tags:
    """
    lady-eleonode-rootford
     preceding-nodenborough
     sir-david-nodenborough
      nody-mc-nodeface
      the-great-nodini (disabled*)
     succeeding-nodenborough
    """

  Scenario: Enable node aggregate with hidden ancestors
    When I am in dimension space point {"language":"de"}
    And the command EnableNodeAggregate is executed with payload:
      | Key                          | Value              |
      | nodeAggregateId              | "the-great-nodini" |
      | nodeVariantSelectionStrategy | "allVariants"      |

    Then I expect the graph projection to consist of exactly 7 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""
    And I expect a node identified by cs-identifier;preceding-nodenborough;{"language":"mul"} to exist in the content graph
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{"language":"mul"} to exist in the content graph
    And I expect this node to be exactly explicitly tagged "disabled"
    And I expect this node to exactly inherit the tags ""
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{"language":"ltz"} to exist in the content graph
    And I expect this node to be exactly explicitly tagged "disabled"
    And I expect this node to exactly inherit the tags ""
    And I expect a node identified by cs-identifier;succeeding-nodenborough;{"language":"mul"} to exist in the content graph
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"language":"mul"} to exist in the content graph
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "disabled"
    And I expect a node identified by cs-identifier;the-great-nodini;{"language":"mul"} to exist in the content graph
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "disabled"

    And I am in workspace "live"

    And I expect the node aggregate "sir-david-nodenborough" to exist
    And I expect this node aggregate to disable dimension space points [{"language":"mul"},{"language":"de"},{"language":"ltz"},{"language":"gsw"},{"language":"en"}]

    Then I expect the node aggregate "the-great-nodini" to exist
    And I expect this node aggregate to disable dimension space points []

    # Tests for the given variant
    When I am in dimension space point {"language":"de"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following subtree with tags:
    """
    lady-eleonode-rootford
     preceding-nodenborough
     sir-david-nodenborough (disabled*)
      nody-mc-nodeface (disabled)
      the-great-nodini (disabled)
     succeeding-nodenborough
    """

    # Tests for the generalization
    When I am in dimension space point {"language":"mul"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following subtree with tags:
    """
    lady-eleonode-rootford
     preceding-nodenborough
     sir-david-nodenborough (disabled*)
      nody-mc-nodeface (disabled)
      the-great-nodini (disabled)
     succeeding-nodenborough
    """

    # Tests for the virtual specialization
    When I am in dimension space point {"language":"gsw"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following subtree with tags:
    """
    lady-eleonode-rootford
     preceding-nodenborough
     sir-david-nodenborough (disabled*)
      nody-mc-nodeface (disabled)
      the-great-nodini (disabled)
     succeeding-nodenborough
    """

    # Tests for the real specialization
    When I am in dimension space point {"language":"ltz"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following subtree with tags:
    """
    lady-eleonode-rootford
     preceding-nodenborough
     sir-david-nodenborough (disabled*)
      nody-mc-nodeface (disabled)
      the-great-nodini (disabled)
     succeeding-nodenborough
    """

    # Tests for the peer variant
    When I am in dimension space point {"language":"en"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following subtree with tags:
    """
    lady-eleonode-rootford
     preceding-nodenborough
     sir-david-nodenborough (disabled*)
      nody-mc-nodeface (disabled)
      the-great-nodini (disabled)
     succeeding-nodenborough
    """
