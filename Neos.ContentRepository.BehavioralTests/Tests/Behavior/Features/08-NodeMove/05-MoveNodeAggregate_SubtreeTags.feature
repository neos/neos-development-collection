@contentrepository @adapters=DoctrineDBAL
Feature: Move a node aggregate into and out of a tagged parent

  As a user of the CR I want to move a node that
  - is untagged
  - tags itself (partially or completely)
  - is tagged by one of its ancestors (partially or completely)

  to a new parent that
  - is untagged (except when the source is untagged too, which is covered by the other move test cases)
  - tags itself, same or differently than the source (partially or completely)
  - is tagged by one of its ancestors, same or differently than the source (partially or completely)

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
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | workspaceTitle       | "Live"               |
      | workspaceDescription | "The live workspace" |
      | newContentStreamId   | "cs-identifier"      |
    And I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
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

  # move untagged to self-tagging

  Scenario: Move an untagged node to a new parent that tags itself
    Given the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "source"}        |
      | nodeVariantSelectionStrategy | "allSpecializations"         |
      | tag                          | "tag1"                       |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | dimensionSpacePoint          | {"example": "source"}        |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
      | relationDistributionStrategy | "gatherSpecializations"      |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

  Scenario: Move an untagged node to a new parent that tags itself partially
    Given the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "spec"}          |
      | nodeVariantSelectionStrategy | "allSpecializations"         |
      | tag                          | "tag1"                       |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | dimensionSpacePoint          | {"example": "source"}        |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
      | relationDistributionStrategy | "gatherSpecializations"      |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

  # move untagged to tagged

  Scenario: Move an untagged node to a new parent that is tagged by its ancestors
    Given the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "source"}        |
      | nodeVariantSelectionStrategy | "allSpecializations"         |
      | tag                          | "tag1"                       |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                   |
      | nodeAggregateId              | "nody-mc-nodeface"      |
      | dimensionSpacePoint          | {"example": "source"}   |
      | newParentNodeAggregateId     | "nodimus-prime"         |
      | relationDistributionStrategy | "gatherSpecializations" |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

  Scenario: Move an untagged node to a new parent that is partially tagged by its ancestors
    Given the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "spec"}          |
      | nodeVariantSelectionStrategy | "allSpecializations"         |
      | tag                          | "tag1"                       |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                   |
      | nodeAggregateId              | "nody-mc-nodeface"      |
      | dimensionSpacePoint          | {"example": "source"}   |
      | newParentNodeAggregateId     | "nodimus-prime"         |
      | relationDistributionStrategy | "gatherSpecializations" |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

  # move tagging to untagged

  Scenario: Move a node that tags itself to a new, untagged parent

    Given the command TagSubtree is executed with payload:
      | Key                          | Value                 |
      | nodeAggregateId              | "nody-mc-nodeface"    |
      | coveredDimensionSpacePoint   | {"example": "source"} |
      | nodeVariantSelectionStrategy | "allSpecializations"  |
      | tag                          | "tag1"                |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | dimensionSpacePoint          | {"example": "source"}        |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
      | relationDistributionStrategy | "gatherSpecializations"      |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"example":"general"}
    And I expect this node to be exactly explicitly tagged "tag1"
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"example":"general"}
    And I expect this node to be exactly explicitly tagged "tag1"
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

  Scenario: Move a node that tags itself partially to a new, untagged parent

    Given the command TagSubtree is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "nody-mc-nodeface"   |
      | coveredDimensionSpacePoint   | {"example": "spec"}  |
      | nodeVariantSelectionStrategy | "allSpecializations" |
      | tag                          | "tag1"               |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | dimensionSpacePoint          | {"example": "source"}        |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
      | relationDistributionStrategy | "gatherSpecializations"      |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"example":"general"}
    And I expect this node to be exactly explicitly tagged "tag1"
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

  # move tagging to tagging

  Scenario: Move a node that tags itself to a new parent that tags the same

    Given the command TagSubtree is executed with payload:
      | Key                          | Value                 |
      | nodeAggregateId              | "nody-mc-nodeface"    |
      | coveredDimensionSpacePoint   | {"example": "source"} |
      | nodeVariantSelectionStrategy | "allSpecializations"  |
      | tag                          | "tag1"                |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "source"}        |
      | nodeVariantSelectionStrategy | "allSpecializations"         |
      | tag                          | "tag1"                       |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | dimensionSpacePoint          | {"example": "source"}        |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
      | relationDistributionStrategy | "gatherSpecializations"      |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"example":"general"}
    And I expect this node to be exactly explicitly tagged "tag1"
    And I expect this node to exactly inherit the tags "tag1"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"example":"general"}
    And I expect this node to be exactly explicitly tagged "tag1"
    And I expect this node to exactly inherit the tags "tag1"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

  Scenario: Move a node that tags itself to a new parent that tags the same, partially

    Given the command TagSubtree is executed with payload:
      | Key                          | Value                 |
      | nodeAggregateId              | "nody-mc-nodeface"    |
      | coveredDimensionSpacePoint   | {"example": "source"} |
      | nodeVariantSelectionStrategy | "allSpecializations"  |
      | tag                          | "tag1"                |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "spec"}          |
      | nodeVariantSelectionStrategy | "allSpecializations"         |
      | tag                          | "tag1"                       |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | dimensionSpacePoint          | {"example": "source"}        |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
      | relationDistributionStrategy | "gatherSpecializations"      |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"example":"general"}
    And I expect this node to be exactly explicitly tagged "tag1"
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"example":"general"}
    And I expect this node to be exactly explicitly tagged "tag1"
    And I expect this node to exactly inherit the tags "tag1"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

  Scenario: Move a node that tags itself partially to a new parent that tags the same

    Given the command TagSubtree is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "nody-mc-nodeface"   |
      | coveredDimensionSpacePoint   | {"example": "spec"}  |
      | nodeVariantSelectionStrategy | "allSpecializations" |
      | tag                          | "tag1"               |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "source"}        |
      | nodeVariantSelectionStrategy | "allSpecializations"         |
      | tag                          | "tag1"                       |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | dimensionSpacePoint          | {"example": "source"}        |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
      | relationDistributionStrategy | "gatherSpecializations"      |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"example":"general"}
    And I expect this node to be exactly explicitly tagged "tag1"
    And I expect this node to exactly inherit the tags "tag1"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

  Scenario: Move a node that tags itself partially to a new parent that tags the same, partially

    Given the command TagSubtree is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "nody-mc-nodeface"   |
      | coveredDimensionSpacePoint   | {"example": "spec"}  |
      | nodeVariantSelectionStrategy | "allSpecializations" |
      | tag                          | "tag1"               |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "spec"}          |
      | nodeVariantSelectionStrategy | "allSpecializations"         |
      | tag                          | "tag1"                       |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | dimensionSpacePoint          | {"example": "source"}        |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
      | relationDistributionStrategy | "gatherSpecializations"      |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"example":"general"}
    And I expect this node to be exactly explicitly tagged "tag1"
    And I expect this node to exactly inherit the tags "tag1"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

  Scenario: Move a node that tags itself to a new parent that tags differently

    Given the command TagSubtree is executed with payload:
      | Key                          | Value                 |
      | nodeAggregateId              | "nody-mc-nodeface"    |
      | coveredDimensionSpacePoint   | {"example": "source"} |
      | nodeVariantSelectionStrategy | "allSpecializations"  |
      | tag                          | "tag1"                |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "source"}        |
      | nodeVariantSelectionStrategy | "allSpecializations"         |
      | tag                          | "tag2"                       |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | dimensionSpacePoint          | {"example": "source"}        |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
      | relationDistributionStrategy | "gatherSpecializations"      |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"example":"general"}
    And I expect this node to be exactly explicitly tagged "tag1"
    And I expect this node to exactly inherit the tags "tag2"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1,tag2"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"example":"general"}
    And I expect this node to be exactly explicitly tagged "tag1"
    And I expect this node to exactly inherit the tags "tag2"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1,tag2"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

  Scenario: Move a node that tags itself to a new parent that tags differently, partially

    Given the command TagSubtree is executed with payload:
      | Key                          | Value                 |
      | nodeAggregateId              | "nody-mc-nodeface"    |
      | coveredDimensionSpacePoint   | {"example": "source"} |
      | nodeVariantSelectionStrategy | "allSpecializations"  |
      | tag                          | "tag1"                |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "spec"}          |
      | nodeVariantSelectionStrategy | "allSpecializations"         |
      | tag                          | "tag2"                       |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | dimensionSpacePoint          | {"example": "source"}        |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
      | relationDistributionStrategy | "gatherSpecializations"      |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"example":"general"}
    And I expect this node to be exactly explicitly tagged "tag1"
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"example":"general"}
    And I expect this node to be exactly explicitly tagged "tag1"
    And I expect this node to exactly inherit the tags "tag2"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1,tag2"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

  Scenario: Move a node that tags itself partially to a new parent that tags differently

    Given the command TagSubtree is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "nody-mc-nodeface"   |
      | coveredDimensionSpacePoint   | {"example": "spec"}  |
      | nodeVariantSelectionStrategy | "allSpecializations" |
      | tag                          | "tag1"               |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "source"}        |
      | nodeVariantSelectionStrategy | "allSpecializations"         |
      | tag                          | "tag2"                       |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | dimensionSpacePoint          | {"example": "source"}        |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
      | relationDistributionStrategy | "gatherSpecializations"      |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag2"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag2"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"example":"general"}
    And I expect this node to be exactly explicitly tagged "tag1"
    And I expect this node to exactly inherit the tags "tag2"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1,tag2"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

  Scenario: Move a node that tags itself partially to a new parent that tags differently, partially

    Given the command TagSubtree is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "nody-mc-nodeface"   |
      | coveredDimensionSpacePoint   | {"example": "spec"}  |
      | nodeVariantSelectionStrategy | "allSpecializations" |
      | tag                          | "tag1"               |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "spec"}          |
      | nodeVariantSelectionStrategy | "allSpecializations"         |
      | tag                          | "tag2"                       |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | dimensionSpacePoint          | {"example": "source"}        |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
      | relationDistributionStrategy | "gatherSpecializations"      |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"example":"general"}
    And I expect this node to be exactly explicitly tagged "tag1"
    And I expect this node to exactly inherit the tags "tag2"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1,tag2"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

  # move tagging to tagged

  Scenario: Move a node that tags itself to a new parent that is tagged the same

    Given the command TagSubtree is executed with payload:
      | Key                          | Value                 |
      | nodeAggregateId              | "nody-mc-nodeface"    |
      | coveredDimensionSpacePoint   | {"example": "source"} |
      | nodeVariantSelectionStrategy | "allSpecializations"  |
      | tag                          | "tag1"                |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "source"}        |
      | nodeVariantSelectionStrategy | "allSpecializations"         |
      | tag                          | "tag1"                       |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                   |
      | nodeAggregateId              | "nody-mc-nodeface"      |
      | dimensionSpacePoint          | {"example": "source"}   |
      | newParentNodeAggregateId     | "nodimus-prime"         |
      | relationDistributionStrategy | "gatherSpecializations" |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged "tag1"
    And I expect this node to exactly inherit the tags "tag1"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged "tag1"
    And I expect this node to exactly inherit the tags "tag1"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

  Scenario: Move a node that tags itself to a new parent that is tagged the same, partially

    Given the command TagSubtree is executed with payload:
      | Key                          | Value                 |
      | nodeAggregateId              | "nody-mc-nodeface"    |
      | coveredDimensionSpacePoint   | {"example": "source"} |
      | nodeVariantSelectionStrategy | "allSpecializations"  |
      | tag                          | "tag1"                |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "spec"}          |
      | nodeVariantSelectionStrategy | "allSpecializations"         |
      | tag                          | "tag1"                       |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                   |
      | nodeAggregateId              | "nody-mc-nodeface"      |
      | dimensionSpacePoint          | {"example": "source"}   |
      | newParentNodeAggregateId     | "nodimus-prime"         |
      | relationDistributionStrategy | "gatherSpecializations" |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged "tag1"
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged "tag1"
    And I expect this node to exactly inherit the tags "tag1"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

  Scenario: Move a node that tags itself partially to a new parent that is tagged the same

    Given the command TagSubtree is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "nody-mc-nodeface"   |
      | coveredDimensionSpacePoint   | {"example": "spec"}  |
      | nodeVariantSelectionStrategy | "allSpecializations" |
      | tag                          | "tag1"               |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "source"}        |
      | nodeVariantSelectionStrategy | "allSpecializations"         |
      | tag                          | "tag1"                       |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                   |
      | nodeAggregateId              | "nody-mc-nodeface"      |
      | dimensionSpacePoint          | {"example": "source"}   |
      | newParentNodeAggregateId     | "nodimus-prime"         |
      | relationDistributionStrategy | "gatherSpecializations" |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged "tag1"
    And I expect this node to exactly inherit the tags "tag1"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

  Scenario: Move a node that tags itself partially to a new parent that is tagged the same, partially

    Given the command TagSubtree is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "nody-mc-nodeface"   |
      | coveredDimensionSpacePoint   | {"example": "spec"}  |
      | nodeVariantSelectionStrategy | "allSpecializations" |
      | tag                          | "tag1"               |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "spec"}          |
      | nodeVariantSelectionStrategy | "allSpecializations"         |
      | tag                          | "tag1"                       |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                   |
      | nodeAggregateId              | "nody-mc-nodeface"      |
      | dimensionSpacePoint          | {"example": "source"}   |
      | newParentNodeAggregateId     | "nodimus-prime"         |
      | relationDistributionStrategy | "gatherSpecializations" |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged "tag1"
    And I expect this node to exactly inherit the tags "tag1"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

  Scenario: Move a node that tags itself to a new parent that is tagged differently

    Given the command TagSubtree is executed with payload:
      | Key                          | Value                 |
      | nodeAggregateId              | "nody-mc-nodeface"    |
      | coveredDimensionSpacePoint   | {"example": "source"} |
      | nodeVariantSelectionStrategy | "allSpecializations"  |
      | tag                          | "tag1"                |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "source"}        |
      | nodeVariantSelectionStrategy | "allSpecializations"         |
      | tag                          | "tag2"                       |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                   |
      | nodeAggregateId              | "nody-mc-nodeface"      |
      | dimensionSpacePoint          | {"example": "source"}   |
      | newParentNodeAggregateId     | "nodimus-prime"         |
      | relationDistributionStrategy | "gatherSpecializations" |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged "tag1"
    And I expect this node to exactly inherit the tags "tag2"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1,tag2"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged "tag1"
    And I expect this node to exactly inherit the tags "tag2"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1,tag2"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

  Scenario: Move a node that tags itself to a new parent that is tagged differently, partially

    Given the command TagSubtree is executed with payload:
      | Key                          | Value                 |
      | nodeAggregateId              | "nody-mc-nodeface"    |
      | coveredDimensionSpacePoint   | {"example": "source"} |
      | nodeVariantSelectionStrategy | "allSpecializations"  |
      | tag                          | "tag1"                |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "spec"}          |
      | nodeVariantSelectionStrategy | "allSpecializations"         |
      | tag                          | "tag2"                       |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                   |
      | nodeAggregateId              | "nody-mc-nodeface"      |
      | dimensionSpacePoint          | {"example": "source"}   |
      | newParentNodeAggregateId     | "nodimus-prime"         |
      | relationDistributionStrategy | "gatherSpecializations" |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged "tag1"
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged "tag1"
    And I expect this node to exactly inherit the tags "tag2"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1,tag2"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

  Scenario: Move a node that tags itself partially to a new parent that tags differently

    Given the command TagSubtree is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "nody-mc-nodeface"   |
      | coveredDimensionSpacePoint   | {"example": "spec"}  |
      | nodeVariantSelectionStrategy | "allSpecializations" |
      | tag                          | "tag1"               |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "source"}        |
      | nodeVariantSelectionStrategy | "allSpecializations"         |
      | tag                          | "tag2"                       |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | dimensionSpacePoint          | {"example": "source"}        |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
      | relationDistributionStrategy | "gatherSpecializations"      |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag2"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag2"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"example":"general"}
    And I expect this node to be exactly explicitly tagged "tag1"
    And I expect this node to exactly inherit the tags "tag2"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1,tag2"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

  Scenario: Move a node that tags itself partially to a new parent that is tagged differently, partially

    Given the command TagSubtree is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "nody-mc-nodeface"   |
      | coveredDimensionSpacePoint   | {"example": "spec"}  |
      | nodeVariantSelectionStrategy | "allSpecializations" |
      | tag                          | "tag1"               |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "spec"}          |
      | nodeVariantSelectionStrategy | "allSpecializations"         |
      | tag                          | "tag2"                       |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                   |
      | nodeAggregateId              | "nody-mc-nodeface"      |
      | dimensionSpacePoint          | {"example": "source"}   |
      | newParentNodeAggregateId     | "nodimus-prime"         |
      | relationDistributionStrategy | "gatherSpecializations" |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged "tag1"
    And I expect this node to exactly inherit the tags "tag2"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1,tag2"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

  # move tagged to untagged

  Scenario: Move a tagged node to a new, untagged parent

    Given the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allSpecializations"     |
      | tag                          | "tag1"                   |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | dimensionSpacePoint          | {"example": "source"}        |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
      | relationDistributionStrategy | "gatherSpecializations"      |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

  Scenario: Move a partially tagged node to a new, untagged parent

    Given the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "spec"}      |
      | nodeVariantSelectionStrategy | "allSpecializations"     |
      | tag                          | "tag1"                   |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "nody-mc-nodeface"           |
      | dimensionSpacePoint          | {"example": "source"}        |
      | newParentNodeAggregateId     | "sir-nodeward-nodington-iii" |
      | relationDistributionStrategy | "gatherSpecializations"      |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

  # move tagged to tagging

  Scenario: Move a tagged node to a new parent that tags the same

    Given the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allSpecializations"     |
      | tag                          | "tag1"                   |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                 |
      | nodeAggregateId              | "nodimus-prime"       |
      | coveredDimensionSpacePoint   | {"example": "source"} |
      | nodeVariantSelectionStrategy | "allSpecializations"  |
      | tag                          | "tag1"                |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                   |
      | nodeAggregateId              | "nody-mc-nodeface"      |
      | dimensionSpacePoint          | {"example": "source"}   |
      | newParentNodeAggregateId     | "nodimus-prime"         |
      | relationDistributionStrategy | "gatherSpecializations" |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

  Scenario: Move a tagged node to a new parent that tags the same, partially

    Given the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allSpecializations"     |
      | tag                          | "tag1"                   |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "nodimus-prime"      |
      | coveredDimensionSpacePoint   | {"example": "spec"}  |
      | nodeVariantSelectionStrategy | "allSpecializations" |
      | tag                          | "tag1"               |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                   |
      | nodeAggregateId              | "nody-mc-nodeface"      |
      | dimensionSpacePoint          | {"example": "source"}   |
      | newParentNodeAggregateId     | "nodimus-prime"         |
      | relationDistributionStrategy | "gatherSpecializations" |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged "tag1"
    And I expect this node to exactly inherit the tags "tag1"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

  Scenario: Move a partially tagged node to a new parent that tags the same

    Given the command TagSubtree is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "nody-mc-nodeface"   |
      | coveredDimensionSpacePoint   | {"example": "spec"}  |
      | nodeVariantSelectionStrategy | "allSpecializations" |
      | tag                          | "tag1"               |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                 |
      | nodeAggregateId              | "nodimus-prime"       |
      | coveredDimensionSpacePoint   | {"example": "source"} |
      | nodeVariantSelectionStrategy | "allSpecializations"  |
      | tag                          | "tag1"                |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                   |
      | nodeAggregateId              | "nody-mc-nodeface"      |
      | dimensionSpacePoint          | {"example": "source"}   |
      | newParentNodeAggregateId     | "nodimus-prime"         |
      | relationDistributionStrategy | "gatherSpecializations" |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

  Scenario: Move a partially tagged node to a new parent that tags the same, partially

    Given the command TagSubtree is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "nody-mc-nodeface"   |
      | coveredDimensionSpacePoint   | {"example": "spec"}  |
      | nodeVariantSelectionStrategy | "allSpecializations" |
      | tag                          | "tag1"               |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "nodimus-prime"      |
      | coveredDimensionSpacePoint   | {"example": "spec"}  |
      | nodeVariantSelectionStrategy | "allSpecializations" |
      | tag                          | "tag1"               |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                   |
      | nodeAggregateId              | "nody-mc-nodeface"      |
      | dimensionSpacePoint          | {"example": "source"}   |
      | newParentNodeAggregateId     | "nodimus-prime"         |
      | relationDistributionStrategy | "gatherSpecializations" |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

  Scenario: Move a tagged node to a new parent that tags differently

    Given the command TagSubtree is executed with payload:
      | Key                          | Value                 |
      | nodeAggregateId              | "nody-mc-nodeface"    |
      | coveredDimensionSpacePoint   | {"example": "source"} |
      | nodeVariantSelectionStrategy | "allSpecializations"  |
      | tag                          | "tag1"                |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                 |
      | nodeAggregateId              | "nodimus-prime"       |
      | coveredDimensionSpacePoint   | {"example": "source"} |
      | nodeVariantSelectionStrategy | "allSpecializations"  |
      | tag                          | "tag2"                |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                   |
      | nodeAggregateId              | "nody-mc-nodeface"      |
      | dimensionSpacePoint          | {"example": "source"}   |
      | newParentNodeAggregateId     | "nodimus-prime"         |
      | relationDistributionStrategy | "gatherSpecializations" |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag2"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag2"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag2"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag2"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

  Scenario: Move a tagged node to a new parent that tags differently, partially

    Given the command TagSubtree is executed with payload:
      | Key                          | Value                 |
      | nodeAggregateId              | "nody-mc-nodeface"    |
      | coveredDimensionSpacePoint   | {"example": "source"} |
      | nodeVariantSelectionStrategy | "allSpecializations"  |
      | tag                          | "tag1"                |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "nodimus-prime"      |
      | coveredDimensionSpacePoint   | {"example": "spec"}  |
      | nodeVariantSelectionStrategy | "allSpecializations" |
      | tag                          | "tag2"               |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                   |
      | nodeAggregateId              | "nody-mc-nodeface"      |
      | dimensionSpacePoint          | {"example": "source"}   |
      | newParentNodeAggregateId     | "nodimus-prime"         |
      | relationDistributionStrategy | "gatherSpecializations" |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag2"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag2"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

  Scenario: Move a partially tagged node to a new parent that tags differently

    Given the command TagSubtree is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "nody-mc-nodeface"   |
      | coveredDimensionSpacePoint   | {"example": "spec"}  |
      | nodeVariantSelectionStrategy | "allSpecializations" |
      | tag                          | "tag1"               |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                 |
      | nodeAggregateId              | "nodimus-prime"       |
      | coveredDimensionSpacePoint   | {"example": "source"} |
      | nodeVariantSelectionStrategy | "allSpecializations"  |
      | tag                          | "tag2"                |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                   |
      | nodeAggregateId              | "nody-mc-nodeface"      |
      | dimensionSpacePoint          | {"example": "source"}   |
      | newParentNodeAggregateId     | "nodimus-prime"         |
      | relationDistributionStrategy | "gatherSpecializations" |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag2"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag2"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag2"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag2"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

  Scenario: Move a partially tagged node to a new parent that tags differently, partially

    Given the command TagSubtree is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "nody-mc-nodeface"   |
      | coveredDimensionSpacePoint   | {"example": "spec"}  |
      | nodeVariantSelectionStrategy | "allSpecializations" |
      | tag                          | "tag1"               |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "nodimus-prime"      |
      | coveredDimensionSpacePoint   | {"example": "spec"}  |
      | nodeVariantSelectionStrategy | "allSpecializations" |
      | tag                          | "tag2"               |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                   |
      | nodeAggregateId              | "nody-mc-nodeface"      |
      | dimensionSpacePoint          | {"example": "source"}   |
      | newParentNodeAggregateId     | "nodimus-prime"         |
      | relationDistributionStrategy | "gatherSpecializations" |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag2"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag2"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

  # move tagged to tagged

  Scenario: Move a tagged node to a new parent that is tagged the same

    Given the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allSpecializations"     |
      | tag                          | "tag1"                   |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "source"}        |
      | nodeVariantSelectionStrategy | "allSpecializations"         |
      | tag                          | "tag1"                       |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                   |
      | nodeAggregateId              | "nody-mc-nodeface"      |
      | dimensionSpacePoint          | {"example": "source"}   |
      | newParentNodeAggregateId     | "nodimus-prime"         |
      | relationDistributionStrategy | "gatherSpecializations" |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

  Scenario: Move a tagged node to a new parent that is tagged the same, partially

    Given the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allSpecializations"     |
      | tag                          | "tag1"                   |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "spec"}          |
      | nodeVariantSelectionStrategy | "allSpecializations"         |
      | tag                          | "tag1"                       |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                   |
      | nodeAggregateId              | "nody-mc-nodeface"      |
      | dimensionSpacePoint          | {"example": "source"}   |
      | newParentNodeAggregateId     | "nodimus-prime"         |
      | relationDistributionStrategy | "gatherSpecializations" |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

  Scenario: Move a partially tagged node to a new parent that is tagged the same

    Given the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "spec"}      |
      | nodeVariantSelectionStrategy | "allSpecializations"     |
      | tag                          | "tag1"                   |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "source"}        |
      | nodeVariantSelectionStrategy | "allSpecializations"         |
      | tag                          | "tag1"                       |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                   |
      | nodeAggregateId              | "nody-mc-nodeface"      |
      | dimensionSpacePoint          | {"example": "source"}   |
      | newParentNodeAggregateId     | "nodimus-prime"         |
      | relationDistributionStrategy | "gatherSpecializations" |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

  Scenario: Move a partially tagged node to a new parent that is tagged the same, partially

    Given the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "spec"}      |
      | nodeVariantSelectionStrategy | "allSpecializations"     |
      | tag                          | "tag1"                   |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "spec"}          |
      | nodeVariantSelectionStrategy | "allSpecializations"         |
      | tag                          | "tag1"                       |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                   |
      | nodeAggregateId              | "nody-mc-nodeface"      |
      | dimensionSpacePoint          | {"example": "source"}   |
      | newParentNodeAggregateId     | "nodimus-prime"         |
      | relationDistributionStrategy | "gatherSpecializations" |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag1"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

  Scenario: Move a tagged node to a new parent that is tagged differently

    Given the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allSpecializations"     |
      | tag                          | "tag1"                   |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "source"}        |
      | nodeVariantSelectionStrategy | "allSpecializations"         |
      | tag                          | "tag2"                       |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                   |
      | nodeAggregateId              | "nody-mc-nodeface"      |
      | dimensionSpacePoint          | {"example": "source"}   |
      | newParentNodeAggregateId     | "nodimus-prime"         |
      | relationDistributionStrategy | "gatherSpecializations" |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag2"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag2"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag2"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag2"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

  Scenario: Move a tagged node to a new parent that is tagged differently, partially

    Given the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "source"}    |
      | nodeVariantSelectionStrategy | "allSpecializations"     |
      | tag                          | "tag1"                   |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "spec"}          |
      | nodeVariantSelectionStrategy | "allSpecializations"         |
      | tag                          | "tag2"                       |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                   |
      | nodeAggregateId              | "nody-mc-nodeface"      |
      | dimensionSpacePoint          | {"example": "source"}   |
      | newParentNodeAggregateId     | "nodimus-prime"         |
      | relationDistributionStrategy | "gatherSpecializations" |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag2"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag2"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

  Scenario: Move a partially tagged node to a new parent that is tagged differently

    Given the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "spec"}      |
      | nodeVariantSelectionStrategy | "allSpecializations"     |
      | tag                          | "tag1"                   |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "source"}        |
      | nodeVariantSelectionStrategy | "allSpecializations"         |
      | tag                          | "tag2"                       |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                   |
      | nodeAggregateId              | "nody-mc-nodeface"      |
      | dimensionSpacePoint          | {"example": "source"}   |
      | newParentNodeAggregateId     | "nodimus-prime"         |
      | relationDistributionStrategy | "gatherSpecializations" |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag2"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag2"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag2"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag2"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

  Scenario: Move a partially tagged node to a new parent that is tagged differently, partially

    Given the command TagSubtree is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "sir-david-nodenborough" |
      | coveredDimensionSpacePoint   | {"example": "spec"}      |
      | nodeVariantSelectionStrategy | "allSpecializations"     |
      | tag                          | "tag1"                   |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                        |
      | nodeAggregateId              | "sir-nodeward-nodington-iii" |
      | coveredDimensionSpacePoint   | {"example": "spec"}          |
      | nodeVariantSelectionStrategy | "allSpecializations"         |
      | tag                          | "tag2"                       |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                   |
      | nodeAggregateId              | "nody-mc-nodeface"      |
      | dimensionSpacePoint          | {"example": "source"}   |
      | newParentNodeAggregateId     | "nodimus-prime"         |
      | relationDistributionStrategy | "gatherSpecializations" |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag2"

    And I expect node aggregate identifier "nodimus-mediocre" and node path "esquire/esquire-child/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags "tag2"

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""

    And I expect node aggregate identifier "nodimus-mediocre" and node path "parent-document/document/child-document" to lead to node cs-identifier;nodimus-mediocre;{"example":"general"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"example":"general"}
    And I expect this node to be exactly explicitly tagged ""
    And I expect this node to exactly inherit the tags ""
