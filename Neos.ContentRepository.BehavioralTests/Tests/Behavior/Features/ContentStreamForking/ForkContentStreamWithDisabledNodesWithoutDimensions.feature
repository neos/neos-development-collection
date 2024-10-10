@contentrepository @adapters=DoctrineDBAL
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
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                    |
      | workspaceName               | "live"                                   |
      | contentStreamId             | "cs-identifier"                          |
      | nodeAggregateId             | "the-great-nodini"                       |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Content" |
      | originDimensionSpacePoint   | {}                                       |
      | coveredDimensionSpacePoints | [{}]                                     |
      | parentNodeAggregateId       | "lady-eleonode-rootford"                 |
      | nodeName                    | "court-magician"                         |
      | nodeAggregateClassification | "regular"                                |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                    |
      | workspaceName               | "live"                                   |
      | contentStreamId             | "cs-identifier"                          |
      | nodeAggregateId             | "nodingers-cat"                          |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Content" |
      | originDimensionSpacePoint   | {}                                       |
      | coveredDimensionSpacePoints | [{}]                                     |
      | parentNodeAggregateId       | "the-great-nodini"                       |
      | nodeName                    | "pet"                                    |
      | nodeAggregateClassification | "regular"                                |
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
    And VisibilityConstraints are set to "withoutRestrictions"
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node user-cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name           | NodeDiscriminator                      |
      | court-magician | user-cs-identifier;the-great-nodini;{} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | nodeAggregateId        |
      | 0     | lady-eleonode-rootford |
      | 1     | the-great-nodini       |
      | 2     | nodingers-cat          |

    And VisibilityConstraints are set to "frontend"
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node user-cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no child nodes
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | nodeAggregateId        |
      | 0     | lady-eleonode-rootford |
    And I expect node aggregate identifier "the-great-nodini" and node path "court-magician" to lead to no node
    And I expect node aggregate identifier "nodingers-cat" and node path "court-magician/pet" to lead to no node
