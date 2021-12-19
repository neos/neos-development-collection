@fixtures
Feature: On forking a content stream, hidden nodes should be correctly copied as well.

  Because we store hidden node information in an extra DB table, this needs to be copied correctly on ForkContentStream
  as well.

  Background:
    Given I have no content dimensions
    And I have the following NodeTypes configuration:
    """
    Neos.ContentRepository:Root: {}
    'Neos.ContentRepository.Testing:Content':
      properties:
        text:
          type: string
    """
    And the event RootWorkspaceWasCreated was published with payload:
      | Key                        | Value                                  |
      | workspaceName              | "live"                                 |
      | workspaceTitle             | "Live"                                 |
      | workspaceDescription       | "The live workspace"                   |
      | initiatingUserIdentifier   | "00000000-0000-0000-0000-000000000000" |
      | newContentStreamIdentifier | "cs-identifier"                        |
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                  |
      | contentStreamIdentifier     | "cs-identifier"                        |
      | nodeAggregateIdentifier     | "lady-eleonode-rootford"               |
      | nodeTypeName                | "Neos.ContentRepository:Root"          |
      | coveredDimensionSpacePoints | [{}]                                   |
      | initiatingUserIdentifier    | "00000000-0000-0000-0000-000000000000" |
      | nodeAggregateClassification | "root"                                 |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                    |
      | contentStreamIdentifier       | "cs-identifier"                          |
      | nodeAggregateIdentifier       | "the-great-nodini"                       |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content" |
      | originDimensionSpacePoint     | {}                                       |
      | coveredDimensionSpacePoints   | [{}]                                     |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                 |
      | nodeName                      | "court-magician"                         |
      | nodeAggregateClassification   | "regular"                                |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                    |
      | contentStreamIdentifier       | "cs-identifier"                          |
      | nodeAggregateIdentifier       | "nodingers-cat"                          |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content" |
      | originDimensionSpacePoint     | {}                                       |
      | coveredDimensionSpacePoints   | [{}]                                     |
      | parentNodeAggregateIdentifier | "the-great-nodini"                       |
      | nodeName                      | "pet"                                    |
      | nodeAggregateClassification   | "regular"                                |
    And the graph projection is fully up to date
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                                  |
      | contentStreamIdentifier      | "cs-identifier"                        |
      | nodeAggregateIdentifier      | "the-great-nodini"                     |
      | coveredDimensionSpacePoint   | {}                                     |
      | nodeVariantSelectionStrategy | "allVariants"                          |
      | initiatingUserIdentifier     | "00000000-0000-0000-0000-000000000000" |
    And the graph projection is fully up to date

  Scenario: on ForkContentStream, the disabled nodes in the target content stream should still be invisible.
    When the command ForkContentStream is executed with payload:
      | Key                           | Value                                  |
      | sourceContentStreamIdentifier | "cs-identifier"                        |
      | contentStreamIdentifier       | "user-cs-identifier"                   |
      | initiatingUserIdentifier      | "00000000-0000-0000-0000-000000000000" |

    When the graph projection is fully up to date

    # node aggregate occupation and coverage is not relevant without dimensions and thus not tested

    When I am in content stream "user-cs-identifier" and dimension space point {}
    And VisibilityConstraints are set to "withoutRestrictions"
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node user-cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name           | NodeDiscriminator                      |
      | court-magician | user-cs-identifier;the-great-nodini;{} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | the-great-nodini        |
      | 2     | nodingers-cat           |

    And VisibilityConstraints are set to "frontend"
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node user-cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no child nodes
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
    And I expect node aggregate identifier "the-great-nodini" and node path "court-magician" to lead to no node
    And I expect node aggregate identifier "nodingers-cat" and node path "court-magician/pet" to lead to no node
