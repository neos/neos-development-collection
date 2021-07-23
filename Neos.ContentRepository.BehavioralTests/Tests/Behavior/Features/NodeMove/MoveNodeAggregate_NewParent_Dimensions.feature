@fixtures
Feature: Move a node with content dimensions

  As a user of the CR I want to move a node to a new parent
  - before the first of its new siblings
  - between two of its new siblings
  - after the last of its new siblings

  These are the test cases for moving nodes with content dimensions being involved, which is a lot more fun!

  Background:
    Given I have the following content dimensions:
      | Identifier | Default | Values           | Generalizations       |
      | language   | mul     | mul, de, en, gsw | gsw->de->mul, en->mul |
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
      | Key                         | Value                                                                              |
      | contentStreamIdentifier     | "cs-identifier"                                                                    |
      | nodeAggregateIdentifier     | "lady-eleonode-rootford"                                                           |
      | nodeTypeName                | "Neos.ContentRepository:Root"                                                      |
      | coveredDimensionSpacePoints | [{"language": "mul"}, {"language": "de"}, {"language": "en"}, {"language": "gsw"}] |
      | initiatingUserIdentifier    | "00000000-0000-0000-0000-000000000000"                                             |
      | nodeAggregateClassification | "root"                                                                             |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                              |
      | contentStreamIdentifier       | "cs-identifier"                                                                    |
      | nodeAggregateIdentifier       | "sir-david-nodenborough"                                                           |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document"                                          |
      | originDimensionSpacePoint     | {"language": "mul"}                                                                |
      | coveredDimensionSpacePoints   | [{"language": "mul"}, {"language": "de"}, {"language": "en"}, {"language": "gsw"}] |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                                                           |
      | nodeName                      | "document"                                                                         |
      | nodeAggregateClassification   | "regular"                                                                          |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                              |
      | contentStreamIdentifier       | "cs-identifier"                                                                    |
      | nodeAggregateIdentifier       | "anthony-destinode"                                                                |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document"                                          |
      | originDimensionSpacePoint     | {"language": "mul"}                                                                |
      | coveredDimensionSpacePoints   | [{"language": "mul"}, {"language": "de"}, {"language": "en"}, {"language": "gsw"}] |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                                                           |
      | nodeName                      | "child-document-a"                                                                 |
      | nodeAggregateClassification   | "regular"                                                                          |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                              |
      | contentStreamIdentifier       | "cs-identifier"                                                                    |
      | nodeAggregateIdentifier       | "berta-destinode"                                                                  |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document"                                          |
      | originDimensionSpacePoint     | {"language": "mul"}                                                                |
      | coveredDimensionSpacePoints   | [{"language": "mul"}, {"language": "de"}, {"language": "en"}, {"language": "gsw"}] |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                                                           |
      | nodeName                      | "child-document-b"                                                                 |
      | nodeAggregateClassification   | "regular"                                                                          |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                              |
      | contentStreamIdentifier       | "cs-identifier"                                                                    |
      | nodeAggregateIdentifier       | "carl-destinode"                                                                   |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document"                                          |
      | originDimensionSpacePoint     | {"language": "mul"}                                                                |
      | coveredDimensionSpacePoints   | [{"language": "mul"}, {"language": "de"}, {"language": "en"}, {"language": "gsw"}] |
      | parentNodeAggregateIdentifier | "sir-david-nodenborough"                                                           |
      | nodeName                      | "child-document-c"                                                                 |
      | nodeAggregateClassification   | "regular"                                                                          |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                              |
      | contentStreamIdentifier       | "cs-identifier"                                                                    |
      | nodeAggregateIdentifier       | "sir-nodeward-nodington-iii"                                                       |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document"                                          |
      | originDimensionSpacePoint     | {"language": "mul"}                                                                |
      | coveredDimensionSpacePoints   | [{"language": "mul"}, {"language": "de"}, {"language": "en"}, {"language": "gsw"}] |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                                                           |
      | nodeName                      | "esquire"                                                                          |
      | nodeAggregateClassification   | "regular"                                                                          |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                              |
      | contentStreamIdentifier       | "cs-identifier"                                                                    |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                                                                 |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document"                                          |
      | originDimensionSpacePoint     | {"language": "mul"}                                                                |
      | coveredDimensionSpacePoints   | [{"language": "mul"}, {"language": "de"}, {"language": "en"}, {"language": "gsw"}] |
      | parentNodeAggregateIdentifier | "sir-nodeward-nodington-iii"                                                       |
      | nodeName                      | "child-document-n"                                                                 |
      | nodeAggregateClassification   | "regular"                                                                          |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                                              |
      | contentStreamIdentifier       | "cs-identifier"                                                                    |
      | nodeAggregateIdentifier       | "lady-abigail-nodenborough"                                                        |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Document"                                          |
      | originDimensionSpacePoint     | {"language": "mul"}                                                                |
      | coveredDimensionSpacePoints   | [{"language": "mul"}, {"language": "de"}, {"language": "en"}, {"language": "gsw"}] |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                                                           |
      | nodeName                      | "document2"                                                                        |
      | nodeAggregateClassification   | "regular"                                                                          |
    And the graph projection is fully up to date

  Scenario: Move a complete node aggregate to a new parent before the first of its new siblings
    When the command MoveNodeAggregate is executed with payload:
      | Key                                         | Value                    |
      | contentStreamIdentifier                     | "cs-identifier"          |
      | nodeAggregateIdentifier                     | "nody-mc-nodeface"       |
      | dimensionSpacePoint                         | {"language": "mul"}      |
      | newParentNodeAggregateIdentifier            | "sir-david-nodenborough" |
      | newSucceedingSiblingNodeAggregateIdentifier | "anthony-destinode"      |
    And the graph projection is fully up to date

    When I am in content stream "cs-identifier" and dimension space point {"language": "mul"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language": "mul"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;anthony-destinode;{"language": "mul"} |
      | cs-identifier;berta-destinode;{"language": "mul"}   |
      | cs-identifier;carl-destinode;{"language": "mul"}    |

  Scenario: Move a complete node aggregate to a new parent before the first of its new siblings - which does not exist in all variants
    Given the event NodeAggregateWasRemoved was published with payload:
      | Key                                  | Value                 |
      | contentStreamIdentifier              | "cs-identifier"       |
      | nodeAggregateIdentifier              | "anthony-destinode"   |
      | affectedOccupiedDimensionSpacePoints | []                    |
      | affectedCoveredDimensionSpacePoints  | [{"language": "gsw"}] |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                         | Value                    |
      | contentStreamIdentifier                     | "cs-identifier"          |
      | nodeAggregateIdentifier                     | "nody-mc-nodeface"       |
      | dimensionSpacePoint                         | {"language": "mul"}      |
      | newParentNodeAggregateIdentifier            | "sir-david-nodenborough" |
      | newSucceedingSiblingNodeAggregateIdentifier | "anthony-destinode"      |
      | relationDistributionStrategy                | "gatherAll"              |
    And the graph projection is fully up to date

    When I am in content stream "cs-identifier" and dimension space point {"language": "mul"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language": "mul"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;anthony-destinode;{"language": "mul"} |
      | cs-identifier;berta-destinode;{"language": "mul"}   |
      | cs-identifier;carl-destinode;{"language": "mul"}    |

    When I am in content stream "cs-identifier" and dimension space point {"language": "gsw"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language": "mul"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                 |
      | cs-identifier;berta-destinode;{"language": "mul"} |
      | cs-identifier;carl-destinode;{"language": "mul"}  |

  Scenario: Move a complete node aggregate to a new parent before one of its new siblings
    When the command MoveNodeAggregate is executed with payload:
      | Key                                         | Value                    |
      | contentStreamIdentifier                     | "cs-identifier"          |
      | nodeAggregateIdentifier                     | "nody-mc-nodeface"       |
      | dimensionSpacePoint                         | {"language": "mul"}      |
      | newParentNodeAggregateIdentifier            | "sir-david-nodenborough" |
      | newSucceedingSiblingNodeAggregateIdentifier | "berta-destinode"        |
    And the graph projection is fully up to date

    When I am in content stream "cs-identifier" and dimension space point {"language": "mul"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language": "mul"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;anthony-destinode;{"language": "mul"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                 |
      | cs-identifier;berta-destinode;{"language": "mul"} |
      | cs-identifier;carl-destinode;{"language": "mul"}  |

  Scenario: Move a complete node aggregate to a new parent before one of its siblings - which does not exist in all variants
    Given the event NodeAggregateWasRemoved was published with payload:
      | Key                                  | Value                 |
      | contentStreamIdentifier              | "cs-identifier"       |
      | nodeAggregateIdentifier              | "berta-destinode"     |
      | affectedOccupiedDimensionSpacePoints | []                    |
      | affectedCoveredDimensionSpacePoints  | [{"language": "gsw"}] |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                         | Value                    |
      | contentStreamIdentifier                     | "cs-identifier"          |
      | nodeAggregateIdentifier                     | "nody-mc-nodeface"       |
      | dimensionSpacePoint                         | {"language": "mul"}      |
      | newParentNodeAggregateIdentifier            | "sir-david-nodenborough" |
      | newSucceedingSiblingNodeAggregateIdentifier | "berta-destinode"        |
    And the graph projection is fully up to date

    When I am in content stream "cs-identifier" and dimension space point {"language": "mul"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language": "mul"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;anthony-destinode;{"language": "mul"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                 |
      | cs-identifier;berta-destinode;{"language": "mul"} |
      | cs-identifier;carl-destinode;{"language": "mul"}  |

    When I am in content stream "cs-identifier" and dimension space point {"language": "gsw"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language": "mul"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;anthony-destinode;{"language": "mul"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                |
      | cs-identifier;carl-destinode;{"language": "mul"} |

  Scenario: Move a complete node aggregate to a new parent after another of its new siblings - which does not exist in all variants
    Given the event NodeAggregateWasRemoved was published with payload:
      | Key                                  | Value                 |
      | contentStreamIdentifier              | "cs-identifier"       |
      | nodeAggregateIdentifier              | "carl-destinode"      |
      | affectedOccupiedDimensionSpacePoints | []                    |
      | affectedCoveredDimensionSpacePoints  | [{"language": "gsw"}] |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                        | Value                    |
      | contentStreamIdentifier                    | "cs-identifier"          |
      | nodeAggregateIdentifier                    | "nody-mc-nodeface"       |
      | dimensionSpacePoint                        | {"language": "mul"}      |
      | newParentNodeAggregateIdentifier           | "sir-david-nodenborough" |
      | newPrecedingSiblingNodeAggregateIdentifier | "berta-destinode"        |
    And the graph projection is fully up to date

    When I am in content stream "cs-identifier" and dimension space point {"language": "mul"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language": "mul"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;berta-destinode;{"language": "mul"}   |
      | cs-identifier;anthony-destinode;{"language": "mul"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                |
      | cs-identifier;carl-destinode;{"language": "mul"} |

    When I am in content stream "cs-identifier" and dimension space point {"language": "gsw"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language": "mul"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;berta-destinode;{"language": "mul"}   |
      | cs-identifier;anthony-destinode;{"language": "mul"} |
    And I expect this node to have no succeeding siblings

  Scenario: Move a complete node aggregate to a new parent after the last of its new siblings - with a predecessor which does not exist in all variants
    Given the event NodeAggregateWasRemoved was published with payload:
      | Key                                  | Value                 |
      | contentStreamIdentifier              | "cs-identifier"       |
      | nodeAggregateIdentifier              | "carl-destinode"      |
      | affectedOccupiedDimensionSpacePoints | []                    |
      | affectedCoveredDimensionSpacePoints  | [{"language": "gsw"}] |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                         | Value                    |
      | contentStreamIdentifier                     | "cs-identifier"          |
      | nodeAggregateIdentifier                     | "nody-mc-nodeface"       |
      | dimensionSpacePoint                         | {"language": "mul"}      |
      | newParentNodeAggregateIdentifier            | "sir-david-nodenborough" |
      | newSucceedingSiblingNodeAggregateIdentifier | null                     |
    And the graph projection is fully up to date

    When I am in content stream "cs-identifier" and dimension space point {"language": "mul"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language": "mul"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;carl-destinode;{"language": "mul"}    |
      | cs-identifier;berta-destinode;{"language": "mul"}   |
      | cs-identifier;anthony-destinode;{"language": "mul"} |
    And I expect this node to have no succeeding siblings

    When I am in content stream "cs-identifier" and dimension space point {"language": "gsw"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language": "mul"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;berta-destinode;{"language": "mul"}   |
      | cs-identifier;anthony-destinode;{"language": "mul"} |
    And I expect this node to have no succeeding siblings

  Scenario: Move a single node in a node aggregate to a new parent after the last of its new siblings
    When the command MoveNodeAggregate is executed with payload:
      | Key                                         | Value                    |
      | contentStreamIdentifier                     | "cs-identifier"          |
      | nodeAggregateIdentifier                     | "nody-mc-nodeface"       |
      | dimensionSpacePoint                         | {"language": "de"}       |
      | newParentNodeAggregateIdentifier            | "sir-david-nodenborough" |
      | newSucceedingSiblingNodeAggregateIdentifier | null                     |
      | relationDistributionStrategy                | "scatter"                |
    And the graph projection is fully up to date

    When I am in content stream "cs-identifier" and dimension space point {"language": "mul"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"language": "mul"}
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings

    When I am in content stream "cs-identifier" and dimension space point {"language": "de"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language": "mul"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;carl-destinode;{"language": "mul"}    |
      | cs-identifier;berta-destinode;{"language": "mul"}   |
      | cs-identifier;anthony-destinode;{"language": "mul"} |
    And I expect this node to have no succeeding siblings

  Scenario: Move a node and its specializations in a node aggregate to a new parent after the last of its new siblings
    When the command MoveNodeAggregate is executed with payload:
      | Key                                         | Value                    |
      | contentStreamIdentifier                     | "cs-identifier"          |
      | nodeAggregateIdentifier                     | "nody-mc-nodeface"       |
      | dimensionSpacePoint                         | {"language": "de"}       |
      | newParentNodeAggregateIdentifier            | "sir-david-nodenborough" |
      | newSucceedingSiblingNodeAggregateIdentifier | null                     |
      | relationDistributionStrategy                | "gatherSpecializations"  |
    And the graph projection is fully up to date

    When I am in content stream "cs-identifier" and dimension space point {"language": "mul"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"language": "mul"}
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings

    When I am in content stream "cs-identifier" and dimension space point {"language": "de"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language": "mul"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;carl-destinode;{"language": "mul"}    |
      | cs-identifier;berta-destinode;{"language": "mul"}   |
      | cs-identifier;anthony-destinode;{"language": "mul"} |
    And I expect this node to have no succeeding siblings

    When I am in content stream "cs-identifier" and dimension space point {"language": "gsw"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language": "mul"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;carl-destinode;{"language": "mul"}    |
      | cs-identifier;berta-destinode;{"language": "mul"}   |
      | cs-identifier;anthony-destinode;{"language": "mul"} |
    And I expect this node to have no succeeding siblings

  Scenario: Move a complete node aggregate to a new parent between siblings with different parents in other variants
    Given the command MoveNodeAggregate is executed with payload:
      | Key                                         | Value                       |
      | contentStreamIdentifier                     | "cs-identifier"             |
      | nodeAggregateIdentifier                     | "berta-destinode"           |
      | dimensionSpacePoint                         | {"language": "gsw"}         |
      | newParentNodeAggregateIdentifier            | "lady-abigail-nodenborough" |
      | newSucceedingSiblingNodeAggregateIdentifier | null                        |
      | relationDistributionStrategy                | "scatter"                   |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                         | Value                    |
      | contentStreamIdentifier                     | "cs-identifier"          |
      | nodeAggregateIdentifier                     | "nody-mc-nodeface"       |
      | dimensionSpacePoint                         | {"language": "mul"}      |
      | newParentNodeAggregateIdentifier            | "sir-david-nodenborough" |
      | newPrecedingSiblingNodeAggregateIdentifier  | "anthony-destinode"      |
      | newSucceedingSiblingNodeAggregateIdentifier | "berta-destinode"        |
      | relationDistributionStrategy                | "gatherAll"              |
    And the graph projection is fully up to date

    When I am in content stream "cs-identifier" and dimension space point {"language": "mul"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language": "mul"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;anthony-destinode;{"language": "mul"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                 |
      | cs-identifier;berta-destinode;{"language": "mul"} |
      | cs-identifier;carl-destinode;{"language": "mul"}  |

    # An explicitly given parent node aggregate identifier should overrule given sibling identifiers
    When I am in content stream "cs-identifier" and dimension space point {"language": "gsw"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language": "mul"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;anthony-destinode;{"language": "mul"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                |
      | cs-identifier;carl-destinode;{"language": "mul"} |

  Scenario: Move a complete node aggregate between siblings with different parents in other variants (without explicit new parent)
    Given the command MoveNodeAggregate is executed with payload:
      | Key                                         | Value                       |
      | contentStreamIdentifier                     | "cs-identifier"             |
      | nodeAggregateIdentifier                     | "berta-destinode"           |
      | dimensionSpacePoint                         | {"language": "gsw"}         |
      | newParentNodeAggregateIdentifier            | "lady-abigail-nodenborough" |
      | newSucceedingSiblingNodeAggregateIdentifier | null                        |
      | relationDistributionStrategy                | "scatter"                   |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                         | Value               |
      | contentStreamIdentifier                     | "cs-identifier"     |
      | nodeAggregateIdentifier                     | "nody-mc-nodeface"  |
      | dimensionSpacePoint                         | {"language": "mul"} |
      | newPrecedingSiblingNodeAggregateIdentifier  | "anthony-destinode" |
      | newSucceedingSiblingNodeAggregateIdentifier | "berta-destinode"   |
      | relationDistributionStrategy                | "gatherAll"         |
    And the graph projection is fully up to date

    When I am in content stream "cs-identifier" and dimension space point {"language": "mul"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language": "mul"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;anthony-destinode;{"language": "mul"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                 |
      | cs-identifier;berta-destinode;{"language": "mul"} |
      | cs-identifier;carl-destinode;{"language": "mul"}  |

    When I am in content stream "cs-identifier" and dimension space point {"language": "gsw"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "document2/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;lady-abigail-nodenborough;{"language": "mul"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                 |
      | cs-identifier;berta-destinode;{"language": "mul"} |
