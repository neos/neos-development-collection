@contentrepository @adapters=DoctrineDBAL
Feature: Tag and untag nodes after moving their children in or out

  As a user of the CR I want to
  - move child nodes were out
  - move nodes in as new children
  using the
  - scatter
  - gatherSpecializations
  - gatherAll
  strategy and then
  - tag
  - untag
  the parent in
  - allSpecializations
  - allVariants
  Idea for testing TAGGING ("... then tag the parent ..."):
  - lady-eleonode-rootford
  -- sir-david-nodenborough  <-- (2) Tag
  --- nody-mc-nodeface       <-- (1) Move Source
  ---- nodimus-mediocre
  -- sir-nodeward-nodington-iii
  --- nodimus-prime
  --- .....................  <-- (1) Move Target

  Idea for testing UNTAGGING ("... then untag the parent ..."):
  - lady-eleonode-rootford
  -- sir-david-nodenborough  <-- (1) Tag         (3) untag
  --- nody-mc-nodeface       <-- (1) Move Source
  ---- nodimus-mediocre
  -- sir-nodeward-nodington-iii <-- (1) Tag
  --- nodimus-prime
  --- .....................  <-- (1) Move Target

  Background:
    Given using the following content dimensions:
      | Identifier | Values                      | Generalizations                      |
      | example    | general, source, peer, spec | spec->source->general, peer->general |
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Document': []
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live" and dimension space point {"example": "general"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId            | parentNodeAggregateId      | nodeTypeName                            | nodeName        |
      | sir-david-nodenborough     | lady-eleonode-rootford     | Neos.ContentRepository.Testing:Document | parent-document |
      | nody-mc-nodeface           | sir-david-nodenborough     | Neos.ContentRepository.Testing:Document | document        |
      | nodimus-mediocre           | nody-mc-nodeface           | Neos.ContentRepository.Testing:Document | child-document  |
      | sir-nodeward-nodington-iii | lady-eleonode-rootford     | Neos.ContentRepository.Testing:Document | esquire         |
      | nodimus-prime              | sir-nodeward-nodington-iii | Neos.ContentRepository.Testing:Document | esquire-child   |

  Scenario: Move a child node out via scatter strategy, then tag the parent and all its specializations
    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "scatter"                    |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allSpecializations"     |
      | tag                          | "my-tag"                 |

    And I am in dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

  Scenario: Move a child node out via scatter strategy, then tag the parent and all its variants
    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "scatter"                    |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allVariants"            |
      | tag                          | "my-tag"                 |

    And I am in dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

  Scenario: Move a child node out via scatter strategy, then untag the parent and all its specializations
    When the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allVariants"            |
      | tag                          | "my-tag"                 |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "source"}        |
      | nodeVariantSelectionStrategy | "allVariants"                |
      | tag                          | "my-tag"                     |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "scatter"                    |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |

    And the command UntagSubtree is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allSpecializations"     |
      | tag                          | "my-tag"                 |

    And I am in dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""

  Scenario: Move a child node out via scatter strategy, then untag the parent and all its variants
    When the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allVariants"            |
      | tag                          | "my-tag"                 |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "source"}        |
      | nodeVariantSelectionStrategy | "allVariants"                |
      | tag                          | "my-tag"                     |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "scatter"                    |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |

    And the command UntagSubtree is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allVariants"            |
      | tag                          | "my-tag"                 |

    And I am in dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""

  Scenario: Move a child node out via gatherSpecializations strategy, then tag the parent and all its specializations
    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "gatherSpecializations"      |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allSpecializations"     |
      | tag                          | "my-tag"                 |

    And I am in dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""

  Scenario: Move a child node out via gatherSpecializations strategy, then tag the parent and all its variants
    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "gatherSpecializations"      |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allVariants"            |
      | tag                          | "my-tag"                 |

    And I am in dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""

  Scenario: Move a child node out via gatherSpecializations strategy, then untag the parent and all its specializations
    When the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allVariants"            |
      | tag                          | "my-tag"                 |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "source"}        |
      | nodeVariantSelectionStrategy | "allVariants"                |
      | tag                          | "my-tag"                     |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "gatherSpecializations"      |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |

    And the command UntagSubtree is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allSpecializations"     |
      | tag                          | "my-tag"                 |

    And I am in dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

  Scenario: Move a child node out via gatherSpecializations strategy, then untag the parent and all its variants
    When the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allVariants"            |
      | tag                          | "my-tag"                 |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "source"}        |
      | nodeVariantSelectionStrategy | "allVariants"                |
      | tag                          | "my-tag"                     |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "gatherSpecializations"      |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |

    And the command UntagSubtree is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allVariants"            |
      | tag                          | "my-tag"                 |

    And I am in dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

  Scenario: Move a child node out via gatherAll strategy, then tag the parent and all its specializations
    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "gatherAll"                  |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allSpecializations"     |
      | tag                          | "my-tag"                 |

    And I am in dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""

  Scenario: Move a child node out via gatherAll strategy, then tag the parent and all its variants
    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "gatherSpecializations"      |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allVariants"            |
      | tag                          | "my-tag"                 |

    And I am in dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""

  Scenario: Move a child node out via gatherAll strategy, then untag the parent and all its specializations
    When the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allVariants"            |
      | tag                          | "my-tag"                 |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "source"}        |
      | nodeVariantSelectionStrategy | "allVariants"                |
      | tag                          | "my-tag"                     |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "gatherAll"                  |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |

    And the command UntagSubtree is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allSpecializations"     |
      | tag                          | "my-tag"                 |

    And I am in dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

  Scenario: Move a child node out via gatherAll strategy, then untag the parent and all its variants
    When the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allVariants"            |
      | tag                          | "my-tag"                 |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "source"}        |
      | nodeVariantSelectionStrategy | "allVariants"                |
      | tag                          | "my-tag"                     |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "gatherAll"                  |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |

    And the command UntagSubtree is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allVariants"            |
      | tag                          | "my-tag"                 |

    And I am in dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

  Scenario: Move a child node in via scatter strategy, then tag the parent and all its specializations
    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "scatter"                    |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "source"}        |
      | nodeVariantSelectionStrategy | "allSpecializations"         |
      | tag                          | "my-tag"                     |

    And I am in dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""

  Scenario: Move a child node in via scatter strategy, then tag the parent and all its variants
    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "scatter"                    |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "source"}        |
      | nodeVariantSelectionStrategy | "allVariants"                |
      | tag                          | "my-tag"                     |

    And I am in dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""

  Scenario: Move a child node in via scatter strategy, then untag the parent and all its specializations
    When the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allVariants"            |
      | tag                          | "my-tag"                 |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "source"}        |
      | nodeVariantSelectionStrategy | "allVariants"                |
      | tag                          | "my-tag"                     |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "scatter"                    |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |

    And the command UntagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "source"}        |
      | nodeVariantSelectionStrategy | "allSpecializations"         |
      | tag                          | "my-tag"                     |

    And I am in dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

  Scenario: Move a child node in via scatter strategy, then untag the parent and all its variants
    When the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allVariants"            |
      | tag                          | "my-tag"                 |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "source"}        |
      | nodeVariantSelectionStrategy | "allVariants"                |
      | tag                          | "my-tag"                     |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "scatter"                    |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |

    And the command UntagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "source"}        |
      | nodeVariantSelectionStrategy | "allVariants"                |
      | tag                          | "my-tag"                     |

    And I am in dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

  Scenario: Move a child node in via gatherSpecializations strategy, then tag the parent and all its specializations
    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "gatherSpecializations"      |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "source"}        |
      | nodeVariantSelectionStrategy | "allSpecializations"         |
      | tag                          | "my-tag"                     |

    And I am in dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

  Scenario: Move a child node in via gatherSpecializations strategy, then tag the parent and all its variants
    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "gatherSpecializations"      |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "source"}        |
      | nodeVariantSelectionStrategy | "allVariants"                |
      | tag                          | "my-tag"                     |

    And I am in dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

  Scenario: Move a child node in via gatherSpecializations strategy, then untag the parent and all its specializations
    When the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allVariants"            |
      | tag                          | "my-tag"                 |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "source"}        |
      | nodeVariantSelectionStrategy | "allVariants"                |
      | tag                          | "my-tag"                     |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "gatherSpecializations"      |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |

    And the command UntagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "source"}        |
      | nodeVariantSelectionStrategy | "allSpecializations"         |
      | tag                          | "my-tag"                     |

    And I am in dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""

  Scenario: Move a child node in via gatherSpecializations strategy, then untag the parent and all its variants
    When the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allVariants"            |
      | tag                          | "my-tag"                 |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "source"}        |
      | nodeVariantSelectionStrategy | "allVariants"                |
      | tag                          | "my-tag"                     |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "gatherSpecializations"      |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |

    And the command UntagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "source"}        |
      | nodeVariantSelectionStrategy | "allVariants"                |
      | tag                          | "my-tag"                     |

    And I am in dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""

  Scenario: Move a child node in via gatherAll strategy, then tag the parent and all its specializations
    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "gatherAll"                  |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "source"}        |
      | nodeVariantSelectionStrategy | "allSpecializations"         |
      | tag                          | "my-tag"                     |

    And I am in dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

  Scenario: Move a child node in via gatherAll strategy, then tag the parent and all its variants
    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "gatherAll"                  |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "source"}        |
      | nodeVariantSelectionStrategy | "allVariants"                |
      | tag                          | "my-tag"                     |

    And I am in dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

  Scenario: Move a child node in via gatherAll strategy, then untag the parent and all its specializations
    When the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allVariants"            |
      | tag                          | "my-tag"                 |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "source"}        |
      | nodeVariantSelectionStrategy | "allVariants"                |
      | tag                          | "my-tag"                     |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "gatherAll"                  |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |

    And the command UntagSubtree is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allSpecializations"     |
      | tag                          | "my-tag"                 |

    And I am in dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags "my-tag"
    And I expect this node to be exactly explicitly tagged ""

  Scenario: Move a child node in via gatherAll strategy, then untag the parent and all its variants
    When the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allVariants"            |
      | tag                          | "my-tag"                 |
    And the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "source"}        |
      | nodeVariantSelectionStrategy | "allVariants"                |
      | tag                          | "my-tag"                     |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | dimensionSpacePoint          | {"example": "source"}        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | relationDistributionStrategy | "gatherAll"                  |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |

    And the command UntagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "source"}        |
      | nodeVariantSelectionStrategy | "allVariants"                |
      | tag                          | "my-tag"                     |

    And I am in dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""

    And I am in dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""
    And I expect node aggregate identifier "nodimus-mediocre" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to exactly inherit the tags ""
    And I expect this node to be exactly explicitly tagged ""
