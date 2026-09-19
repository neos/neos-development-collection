Feature: Enable a node aggregate

  As a user of the CR I want to enable a node aggregate and expect its descendants to also be enabled unless otherwise disabled.

  These are the test cases without dimensions being involved

  Background:
    Given using no content dimensions
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
    And I am in workspace "live" and dimension space point {}
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
    And the command SetNodeReferences is executed with payload:
      | Key                   | Value                                  |
      | sourceNodeAggregateId | "preceding-nodenborough"               |
      | references            | [{"referenceName": "references", "references": [{"target": "sir-david-nodenborough"}]}] |

  Scenario: Enable a previously disabled node with arbitrary strategy since dimensions are not involved
    Given the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | nodeVariantSelectionStrategy | "allVariants"            |

    When the command EnableNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | nodeVariantSelectionStrategy | "allVariants"            |

    Then I expect exactly 9 events to be published on stream with prefix "ContentStream:cs-identifier"
    And event at index 8 is of type "SubtreeWasUntagged" with payload:
      | Key                          | Expected                 |
      | contentStreamId              | "cs-identifier"          |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | affectedDimensionSpacePoints | [[]]                     |
      | tag                          | "disabled"               |

    Then I expect the graph projection to consist of exactly 5 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""
    And I expect a node identified by cs-identifier;preceding-nodenborough;{} to exist in the content graph
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{} to exist in the content graph
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""
    And I expect a node identified by cs-identifier;succeeding-nodenborough;{} to exist in the content graph
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{} to exist in the content graph
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect the node aggregate "sir-david-nodenborough" to exist
    And I expect this node aggregate to disable dimension space points []

    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following subtree with tags:
    """
    lady-eleonode-rootford
     preceding-nodenborough
     sir-david-nodenborough
      nody-mc-nodeface
     succeeding-nodenborough
    """

  Scenario: Enable a previously disabled node with explicitly disabled child nodes with arbitrary strategy since dimensions are not involved
    Given the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | nodeVariantSelectionStrategy | "allVariants"            |
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value              |
      | nodeAggregateId              | "nody-mc-nodeface" |
      | nodeVariantSelectionStrategy | "allVariants"      |

    And I expect a node identified by cs-identifier;sir-david-nodenborough;{} to exist in the content graph
    And I expect this node to be exactly explicitly tagged "disabled"
    And I expect this node to exactly inherit the tags ""
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{} to exist in the content graph
    And I expect this node to be exactly explicitly tagged "disabled"
    And I expect this node to exactly inherit the tags ""

    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following subtree with tags:
    """
    lady-eleonode-rootford
     preceding-nodenborough
     sir-david-nodenborough (disabled*)
      nody-mc-nodeface (disabled*)
     succeeding-nodenborough
    """

    When the command EnableNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | nodeVariantSelectionStrategy | "allVariants"            |
    Then I expect exactly 10 events to be published on stream with prefix "ContentStream:cs-identifier"
    And event at index 9 is of type "SubtreeWasUntagged" with payload:
      | Key                          | Expected                 |
      | contentStreamId              | "cs-identifier"          |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | affectedDimensionSpacePoints | [[]]                     |
      | tag                          | "disabled"               |

    Then I expect the graph projection to consist of exactly 5 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""
    And I expect a node identified by cs-identifier;preceding-nodenborough;{} to exist in the content graph
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{} to exist in the content graph
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""
    And I expect a node identified by cs-identifier;succeeding-nodenborough;{} to exist in the content graph
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{} to exist in the content graph
    And I expect this node to be exactly explicitly tagged "disabled"
    And I expect this node to exactly inherit the tags ""

    Then I expect the node aggregate "sir-david-nodenborough" to exist
    And I expect this node aggregate to disable dimension space points []
    And I expect the node aggregate "nody-mc-nodeface" to exist
    And I expect this node aggregate to disable dimension space points [{}]

    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following subtree with tags:
    """
    lady-eleonode-rootford
     preceding-nodenborough
     sir-david-nodenborough
      nody-mc-nodeface (disabled*)
     succeeding-nodenborough
    """

  Scenario: Enable a previously disabled node with explicitly disabled parent node with arbitrary strategy since dimensions are not involved
    Given the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | nodeVariantSelectionStrategy | "allVariants"            |
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value              |
      | nodeAggregateId              | "nody-mc-nodeface" |
      | nodeVariantSelectionStrategy | "allVariants"      |

    And I expect a node identified by cs-identifier;sir-david-nodenborough;{} to exist in the content graph
    And I expect this node to be exactly explicitly tagged "disabled"
    And I expect this node to exactly inherit the tags ""
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{} to exist in the content graph
    And I expect this node to be exactly explicitly tagged "disabled"
    And I expect this node to exactly inherit the tags ""

    When the command EnableNodeAggregate is executed with payload:
      | Key                          | Value              |
      | nodeAggregateId              | "nody-mc-nodeface" |
      | nodeVariantSelectionStrategy | "allVariants"      |
    Then I expect exactly 10 events to be published on stream with prefix "ContentStream:cs-identifier"
    And event at index 9 is of type "SubtreeWasUntagged" with payload:
      | Key                          | Expected           |
      | contentStreamId              | "cs-identifier"    |
      | nodeAggregateId              | "nody-mc-nodeface" |
      | affectedDimensionSpacePoints | [[]]               |
      | tag                          | "disabled"         |

    And I am in workspace "live"

    Then I expect the node aggregate "sir-david-nodenborough" to exist
    And I expect this node aggregate to disable dimension space points [{}]
    And I expect the node aggregate "nody-mc-nodeface" to exist
    And I expect this node aggregate to disable dimension space points []

    And I expect a node identified by cs-identifier;sir-david-nodenborough;{} to exist in the content graph
    And I expect this node to be exactly explicitly tagged "disabled"
    And I expect this node to exactly inherit the tags ""
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{} to exist in the content graph
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "disabled"

    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following subtree with tags:
    """
    lady-eleonode-rootford
     preceding-nodenborough
     sir-david-nodenborough (disabled*)
      nody-mc-nodeface (disabled)
     succeeding-nodenborough
    """
