@fixtures
Feature: Move a node without content dimensions

  As a user of the CR I want to move a node
  - to the end of its siblings
  - before one of its siblings
  - to a new parent and the end of its children
  - to a new parent and before one of its children

  These are the test cases for moving nodes without content dimensions being involved

  Background:
    Given I have no content dimensions
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:Document': []
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
      | Key                           | Value                                     |
      | contentStreamIdentifier       | "cs-identifier"                           |
      | nodeAggregateIdentifier       | "sir-david-nodenborough"                  |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {}                                        |
      | coveredDimensionSpacePoints   | [{}]                                      |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                  |
      | nodeName                      | "document"                                |
      | nodeAggregateClassification   | "regular"                                 |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                     |
      | contentStreamIdentifier       | "cs-identifier"                           |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                        |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {}                                        |
      | coveredDimensionSpacePoints   | [{}]                                      |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                  |
      | nodeName                      | "child-document"                          |
      | nodeAggregateClassification   | "regular"                                 |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                     |
      | contentStreamIdentifier       | "cs-identifier"                           |
      | nodeAggregateIdentifier       | "sir-nodeward-nodington-iii"              |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {}                                        |
      | coveredDimensionSpacePoints   | [{}]                                      |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                  |
      | nodeName                      | "esquire"                                 |
      | nodeAggregateClassification   | "regular"                                 |
    And the graph projection is fully up to date

  Scenario: Move a node to the end of its siblings
    When the command MoveNodeAggregate is executed with payload:
      | Key                                         | Value                    |
      | contentStreamIdentifier                     | "cs-identifier"          |
      | nodeAggregateIdentifier                     | "sir-david-nodenborough" |
      | dimensionSpacePoint                         | {}                       |
      | newParentNodeAggregateIdentifier            | null                     |
      | newSucceedingSiblingNodeAggregateIdentifier | null                     |

    When the graph projection is fully up to date
    Then I expect the graph projection to consist of exactly 4 nodes
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier;lady-eleonode-rootford;{}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier;sir-david-nodenborough;{}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier;nody-mc-nodeface;{}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier;sir-nodeward-nodington-iii;{}} to exist in the content graph

    # node aggregate occupation and coverage is not relevant without dimensions and thus not tested

    When I am in content stream "cs-identifier" and dimension space point {}
    Then I expect node aggregate identifier "sir-nodeward-nodington-iii" and node path "esquire" to lead to node cs-identifier;sir-nodeward-nodington-iii;{}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                       |
      | cs-identifier;sir-david-nodenborough;{} |
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                           |
      | cs-identifier;sir-nodeward-nodington-iii;{} |
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier;sir-david-nodenborough;{}
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings

  Scenario: Move a node before one of its siblings
    When the command MoveNodeAggregate is executed with payload:
      | Key                                         | Value                        |
      | contentStreamIdentifier                     | "cs-identifier"              |
      | nodeAggregateIdentifier                     | "sir-nodeward-nodington-iii" |
      | dimensionSpacePoint                         | {}                           |
      | newParentNodeAggregateIdentifier            | null                         |
      | newSucceedingSiblingNodeAggregateIdentifier | "sir-david-nodenborough"     |

    When the graph projection is fully up to date
    Then I expect the graph projection to consist of exactly 4 nodes
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier;lady-eleonode-rootford;{}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier;sir-david-nodenborough;{}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier;nody-mc-nodeface;{}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier;sir-nodeward-nodington-iii;{}} to exist in the content graph

    # node aggregate occupation and coverage is not relevant without dimensions and thus not tested

    When I am in content stream "cs-identifier" and dimension space point {}
    Then I expect node aggregate identifier "sir-nodeward-nodington-iii" and node path "esquire" to lead to node cs-identifier;sir-nodeward-nodington-iii;{}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                       |
      | cs-identifier;sir-david-nodenborough;{} |
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                           |
      | cs-identifier;sir-nodeward-nodington-iii;{} |
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier;sir-david-nodenborough;{}
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings

  Scenario: Move a node to a new parent and the end of its children
    Given the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                     |
      | contentStreamIdentifier       | "cs-identifier"                           |
      | nodeAggregateIdentifier       | "lady-abigail-nodenborough"               |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint     | {}                                        |
      | coveredDimensionSpacePoints   | [{}]                                      |
      | parentNodeAggregateIdentifier | "sir-nodeward-nodington-iii"              |
      | nodeName                      | "other-document"                          |
      | nodeAggregateClassification   | "regular"                                 |
    And the graph projection is fully up to date
    When the command MoveNodeAggregate is executed with payload:
      | Key                              | Value                        |
      | contentStreamIdentifier          | "cs-identifier"              |
      | nodeAggregateIdentifier          | "sir-david-nodenborough"     |
      | dimensionSpacePoint              | {}                           |
      | newParentNodeAggregateIdentifier | "sir-nodeward-nodington-iii" |

    Then I expect exactly 7 events to be published on stream "Neos.ContentRepository:ContentStream:cs-identifier"
    And event at index 6 is of type "Neos.EventSourcedContentRepository:NodeAggregateWasMoved" with payload:
      | Key                     | Expected                                                                                                                                                                                                          |
      | contentStreamIdentifier | "cs-identifier"                                                                                                                                                                                                   |
      | nodeAggregateIdentifier | "sir-david-nodenborough"                                                                                                                                                                                          |
      | nodeMoveMappings        | [{"movedNodeOrigin":[],"newParentAssignments":{"d751713988987e9331980363e24189ce":{"nodeAggregateIdentifier":"sir-nodeward-nodington-iii","originDimensionSpacePoint":[]}},"newSucceedingSiblingAssignments":[]}] |

    When the graph projection is fully up to date
    Then I expect the graph projection to consist of exactly 5 nodes
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier;lady-eleonode-rootford;{}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier;sir-david-nodenborough;{}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier;nody-mc-nodeface;{}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier;sir-nodeward-nodington-iii;{}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier;lady-abigail-nodenborough;{}} to exist in the content graph

    # node aggregate occupation and coverage is not relevant without dimensions and thus not tested

    When I am in content stream "cs-identifier" and dimension space point {}
    And I expect node aggregate identifier "sir-nodeward-nodington-iii" and node path "esquire" to lead to node cs-identifier;sir-nodeward-nodington-iii;{}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "lady-abigail-nodenborough" and node path "esquire/other-document" to lead to node cs-identifier;lady-abigail-nodenborough;{}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier;sir-nodeward-nodington-iii;{}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                       |
      | cs-identifier;sir-david-nodenborough;{} |
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "esquire/document" to lead to node cs-identifier;sir-david-nodenborough;{}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier;sir-nodeward-nodington-iii;{}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                          |
      | cs-identifier;lady-abigail-nodenborough;{} |
    And I expect this node to have no succeeding siblings
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier;sir-david-nodenborough;{}
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings

  Scenario: Move a node to a new parent and before one of its children
    When the command MoveNodeAggregate is executed with payload:
      | Key                                         | Value                        |
      | contentStreamIdentifier                     | "cs-identifier"              |
      | nodeAggregateIdentifier                     | "nody-mc-nodeface"           |
      | dimensionSpacePoint                         | {}                           |
      | newParentNodeAggregateIdentifier            | "lady-eleonode-rootford"     |
      | newSucceedingSiblingNodeAggregateIdentifier | "sir-nodeward-nodington-iii" |
    Then I expect exactly 6 events to be published on stream "Neos.ContentRepository:ContentStream:cs-identifier"
    And event at index 5 is of type "Neos.EventSourcedContentRepository:NodeAggregateWasMoved" with payload:
      | Key                     | Expected                                                                                                                                                                                                                                                                                                                                |
      | contentStreamIdentifier | "cs-identifier"                                                                                                                                                                                                                                                                                                                         |
      | nodeAggregateIdentifier | "nody-mc-nodeface"                                                                                                                                                                                                                                                                                                                      |
      | nodeMoveMappings        | [{"movedNodeOrigin":[],"newParentAssignments":{"d751713988987e9331980363e24189ce":{"nodeAggregateIdentifier":"lady-eleonode-rootford","originDimensionSpacePoint":[]}},"newSucceedingSiblingAssignments":{"d751713988987e9331980363e24189ce":{"nodeAggregateIdentifier":"sir-nodeward-nodington-iii","originDimensionSpacePoint":[]}}}] |

    When the graph projection is fully up to date
    Then I expect the graph projection to consist of exactly 4 nodes
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier;lady-eleonode-rootford;{}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier;sir-david-nodenborough;{}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier;nody-mc-nodeface;{}} to exist in the content graph
    And I expect a node with identifier {"contentStreamIdentifier":"cs-identifier;sir-nodeward-nodington-iii;{}} to exist in the content graph

    # node aggregate occupation and coverage is not relevant without dimensions and thus not tested

    When I am in content stream "cs-identifier" and dimension space point {}
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                           |
      | cs-identifier;nody-mc-nodeface;{}           |
      | cs-identifier;sir-nodeward-nodington-iii;{} |
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "child-document" to lead to node cs-identifier;nody-mc-nodeface;{}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                       |
      | cs-identifier;sir-david-nodenborough;{} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                           |
      | cs-identifier;sir-nodeward-nodington-iii;{} |
    And I expect node aggregate identifier "sir-nodeward-nodington-iii" and node path "esquire" to lead to node cs-identifier;sir-nodeward-nodington-iii;{}
    And I expect this node to be a child of node {"contentStreamIdentifier":"cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                       |
      | cs-identifier;nody-mc-nodeface;{}       |
      | cs-identifier;sir-david-nodenborough;{} |
    And I expect this node to have no succeeding siblings
