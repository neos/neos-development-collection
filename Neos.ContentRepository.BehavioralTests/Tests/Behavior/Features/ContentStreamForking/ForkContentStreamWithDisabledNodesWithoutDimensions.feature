Feature: On forking a content stream, hidden nodes should be correctly copied as well.

  Because we store hidden node information in an extra DB table, this needs to be copied correctly on ForkContentStream
  as well.

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    Neos.ContentRepository:Root: {}
    'Neos.ContentRepository.Testing:Content':
      properties:
        text:
          type: string
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | newContentStreamId   | "cs-identifier"      |
    And I am in workspace "live"
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                         | Value                                    |
      | workspaceName               | "live"                                   |
      | nodeAggregateId             | "the-great-nodini"                       |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Content" |
      | originDimensionSpacePoint   | {}                                       |
      | parentNodeAggregateId       | "lady-eleonode-rootford"                 |
      | nodeName                    | "court-magician"                         |
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                         | Value                                    |
      | workspaceName               | "live"                                   |
      | nodeAggregateId             | "nodingers-cat"                          |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Content" |
      | originDimensionSpacePoint   | {}                                       |
      | parentNodeAggregateId       | "the-great-nodini"                       |
      | nodeName                    | "pet"                                    |
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value              |
      | nodeAggregateId              | "the-great-nodini" |
      | coveredDimensionSpacePoint   | {}                 |
      | nodeVariantSelectionStrategy | "allVariants"      |

  Scenario: on ForkContentStream, the disabled nodes in the target content stream should still be invisible.
    # Uses ForkContentStream implicitly
    When the command CreateWorkspace is executed with payload:
      | Key                | Value                |
      | baseWorkspaceName  | "live"               |
      | workspaceName      | "user-test"          |
      | newContentStreamId | "user-cs-identifier" |

    # node aggregate occupation and coverage is not relevant without dimensions and thus not tested

    When I am in workspace "user-test" and dimension space point {}

    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""
    And I expect a node identified by cs-identifier;the-great-nodini;{} to exist in the content graph
    And I expect this node to be exactly explicitly tagged "disabled"
    And I expect this node to exactly inherit the tags ""
    And I expect a node identified by cs-identifier;nodingers-cat;{} to exist in the content graph
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "disabled"

    And I expect the node aggregate "the-great-nodini" to exist
    And I expect this node aggregate to disable dimension space points [{}]

    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node user-cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following subtree with tags:
    """
    lady-eleonode-rootford
     the-great-nodini (disabled*)
      nodingers-cat (disabled)
    """
