@contentrepository @adapters=DoctrineDBAL
Feature: Move a node with content dimensions

  As a user of the CR I want to move a node to a new parent
  - before the first of its new siblings
  - between two of its new siblings
  - after the last of its new siblings

  These are the test cases for moving nodes with content dimensions being involved, which is a lot more fun!

  Background:
    Given using the following content dimensions:
      | Identifier | Values                     | Generalizations                     |
      | language   | general, source, peer, gsw | gsw->source->general, peer->general |
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
    And the graph projection is fully up to date
    And I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId            | nodeTypeName                            | parentNodeAggregateId      | nodeName          |
      | sir-david-nodenborough     | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford     | parent-document   |
      | eldest-mc-nodeface         | Neos.ContentRepository.Testing:Document | sir-david-nodenborough     | eldest-document   |
      | elder-mc-nodeface          | Neos.ContentRepository.Testing:Document | sir-david-nodenborough     | elder-document    |
      | younger-mc-nodeface        | Neos.ContentRepository.Testing:Document | sir-david-nodenborough     | younger-document  |
      | youngest-mc-nodeface       | Neos.ContentRepository.Testing:Document | sir-david-nodenborough     | youngest-document |
      | sir-nodeward-nodington-iii | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford     | esquire           |
      | source-elder-mc-nodeface   | Neos.ContentRepository.Testing:Document | sir-nodeward-nodington-iii | elder-document    |
      | nody-mc-nodeface           | Neos.ContentRepository.Testing:Document | sir-nodeward-nodington-iii | document          |
      | source-younger-mc-nodeface | Neos.ContentRepository.Testing:Document | sir-nodeward-nodington-iii | younger-document  |
      | bustling-mc-nodeface       | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford     | bustling-document |

  # Test cases for the gatherAll strategy

  Scenario: Move a complete node aggregate to a new parent before the first of its new siblings
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                    |
      | nodeAggregateId                     | "nody-mc-nodeface"       |
      | dimensionSpacePoint                 | {"example": "source"}    |
      | newParentNodeAggregateId            | "sir-david-nodenborough" |
      | newSucceedingSiblingNodeAggregateId | "eldest-mc-nodeface"     |
      | relationDistributionStrategy        | "gatherAll"              |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}   |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}   |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}   |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}   |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

  Scenario: Move a complete node aggregate to a new parent before the first of its new siblings - which does not exist in all variants
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "eldest-mc-nodeface" |
      | coveredDimensionSpacePoint   | {"example": "spec"}  |
      | nodeVariantSelectionStrategy | "allSpecializations" |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                    |
      | nodeAggregateId                     | "nody-mc-nodeface"       |
      | dimensionSpacePoint                 | {"example": "source"}    |
      | newParentNodeAggregateId            | "sir-david-nodenborough" |
      | newSucceedingSiblingNodeAggregateId | "eldest-mc-nodeface"     |
      | relationDistributionStrategy        | "gatherAll"              |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}   |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}   |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}   |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

  Scenario: Move a complete node aggregate to a new parent before a siblings which is partially the first
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "eldest-mc-nodeface" |
      | coveredDimensionSpacePoint   | {"example": "spec"}  |
      | nodeVariantSelectionStrategy | "allSpecializations" |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                    |
      | nodeAggregateId                     | "nody-mc-nodeface"       |
      | dimensionSpacePoint                 | {"example": "source"}    |
      | newParentNodeAggregateId            | "sir-david-nodenborough" |
      | newSucceedingSiblingNodeAggregateId | "elder-mc-nodeface"      |
      | relationDistributionStrategy        | "gatherAll"              |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

  Scenario: Move a complete node aggregate to a new parent before one of its new siblings, which is not the first
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                    |
      | nodeAggregateId                     | "nody-mc-nodeface"       |
      | dimensionSpacePoint                 | {"example": "general"}   |
      | newParentNodeAggregateId            | "sir-david-nodenborough" |
      | newSucceedingSiblingNodeAggregateId | "elder-mc-nodeface"      |
      | relationDistributionStrategy        | "gatherAll"              |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

  Scenario: Move a complete node aggregate to a new parent before one of its siblings, which is not the first and does not exist in all variants
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "elder-mc-nodeface"  |
      | coveredDimensionSpacePoint   | {"example": "spec"}  |
      | nodeVariantSelectionStrategy | "allSpecializations" |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                    |
      | nodeAggregateId                     | "nody-mc-nodeface"       |
      | newParentNodeAggregateId            | "sir-david-nodenborough" |
      | newSucceedingSiblingNodeAggregateId | "elder-mc-nodeface"      |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

  Scenario: Move a complete node aggregate to a new parent before one of its siblings, which is the last and does not exist in all variants
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                  |
      | nodeAggregateId              | "youngest-mc-nodeface" |
      | coveredDimensionSpacePoint   | {"example": "spec"}    |
      | nodeVariantSelectionStrategy | "allSpecializations"   |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                    |
      | nodeAggregateId                     | "nody-mc-nodeface"       |
      | dimensionSpacePoint                 | {"example": "source"}    |
      | newParentNodeAggregateId            | "sir-david-nodenborough" |
      | newSucceedingSiblingNodeAggregateId | "youngest-mc-nodeface"   |
      | relationDistributionStrategy        | "gatherAll"              |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}  |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}  |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}  |
    And I expect this node to have no succeeding siblings

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}  |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

  Scenario: Move a complete node aggregate to a new parent after the last of its siblings
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                    |
      | nodeAggregateId                     | "nody-mc-nodeface"       |
      | dimensionSpacePoint                 | {"example": "source"}    |
      | newParentNodeAggregateId            | "sir-david-nodenborough" |
      | newSucceedingSiblingNodeAggregateId | null                     |
      | relationDistributionStrategy        | "gatherAll"              |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}   |
    And I expect this node to have no succeeding siblings

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}   |
    And I expect this node to have no succeeding siblings

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}   |
    And I expect this node to have no succeeding siblings

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}   |
    And I expect this node to have no succeeding siblings

  Scenario: Move a complete node aggregate to a new parent after the last of its siblings, which does not exist in all variants
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                  |
      | nodeAggregateId              | "youngest-mc-nodeface" |
      | coveredDimensionSpacePoint   | {"example": "spec"}    |
      | nodeVariantSelectionStrategy | "allSpecializations"   |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                    |
      | nodeAggregateId                     | "nody-mc-nodeface"       |
      | dimensionSpacePoint                 | {"example": "source"}    |
      | newParentNodeAggregateId            | "sir-david-nodenborough" |
      | newSucceedingSiblingNodeAggregateId | null                     |
      | relationDistributionStrategy        | "gatherAll"              |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}   |
    And I expect this node to have no succeeding siblings

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}   |
    And I expect this node to have no succeeding siblings

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}  |
    And I expect this node to have no succeeding siblings

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}   |
    And I expect this node to have no succeeding siblings

  Scenario: Move a complete node aggregate to a new parent after one of its siblings, which is partially the last
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                  |
      | nodeAggregateId              | "youngest-mc-nodeface" |
      | coveredDimensionSpacePoint   | {"example": "spec"}    |
      | nodeVariantSelectionStrategy | "allSpecializations"   |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                | Value                    |
      | nodeAggregateId                    | "nody-mc-nodeface"       |
      | dimensionSpacePoint                | {"example": "source"}    |
      | newParentNodeAggregateId           | "sir-david-nodenborough" |
      | newPrecedingSiblingNodeAggregateId | "younger-mc-nodeface"    |
      | relationDistributionStrategy       | "gatherAll"              |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}  |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}  |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}  |
    And I expect this node to have no succeeding siblings

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}  |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

  Scenario: Move a complete node aggregate to a new parent after one of its siblings, which is not the last
    When the command MoveNodeAggregate is executed with payload:
      | Key                                | Value                    |
      | nodeAggregateId                    | "nody-mc-nodeface"       |
      | dimensionSpacePoint                | {"example": "source"}    |
      | newParentNodeAggregateId           | "sir-david-nodenborough" |
      | newPrecedingSiblingNodeAggregateId | "younger-mc-nodeface"    |
      | relationDistributionStrategy       | "gatherAll"              |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}  |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}  |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}  |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}  |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

  Scenario: Move a complete node aggregate to a new parent after one of its siblings, which is not the last and does not exist in all variants
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                 |
      | nodeAggregateId              | "younger-mc-nodeface" |
      | coveredDimensionSpacePoint   | {"example": "spec"}   |
      | nodeVariantSelectionStrategy | "allSpecializations"  |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                | Value                    |
      | nodeAggregateId                    | "nody-mc-nodeface"       |
      | dimensionSpacePoint                | {"example": "source"}    |
      | newParentNodeAggregateId           | "sir-david-nodenborough" |
      | newPrecedingSiblingNodeAggregateId | "younger-mc-nodeface"    |
      | relationDistributionStrategy       | "gatherAll"              |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}  |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}  |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}  |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}  |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

  Scenario: Move a complete node aggregate to a new parent after one of its siblings, which is the first and does not exist in all variants
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "eldest-mc-nodeface" |
      | coveredDimensionSpacePoint   | {"example": "spec"}  |
      | nodeVariantSelectionStrategy | "allSpecializations" |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                | Value                    |
      | nodeAggregateId                    | "nody-mc-nodeface"       |
      | dimensionSpacePoint                | {"example": "source"}    |
      | newParentNodeAggregateId           | "sir-david-nodenborough" |
      | newPrecedingSiblingNodeAggregateId | "eldest-mc-nodeface"     |
      | relationDistributionStrategy       | "gatherAll"              |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}   |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

  # Test cases for the gatherSpecializations strategy

  Scenario: Move a complete node aggregate to a new parent before the first of its new siblings
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                    |
      | nodeAggregateId                     | "nody-mc-nodeface"       |
      | dimensionSpacePoint                 | {"example": "source"}    |
      | newParentNodeAggregateId            | "sir-david-nodenborough" |
      | newSucceedingSiblingNodeAggregateId | "eldest-mc-nodeface"     |
      | relationDistributionStrategy        | "gatherSpecializations"  |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}   |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}   |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

  Scenario: Move a complete node aggregate to a new parent before the first of its new siblings - which does not exist in all variants
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "eldest-mc-nodeface" |
      | coveredDimensionSpacePoint   | {"example": "spec"}  |
      | nodeVariantSelectionStrategy | "allSpecializations" |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                    |
      | nodeAggregateId                     | "nody-mc-nodeface"       |
      | dimensionSpacePoint                 | {"example": "source"}    |
      | newParentNodeAggregateId            | "sir-david-nodenborough" |
      | newSucceedingSiblingNodeAggregateId | "eldest-mc-nodeface"     |
      | relationDistributionStrategy        | "gatherSpecializations"  |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}   |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

  Scenario: Move a complete node aggregate to a new parent before a siblings which is partially the first
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "eldest-mc-nodeface" |
      | coveredDimensionSpacePoint   | {"example": "spec"}  |
      | nodeVariantSelectionStrategy | "allSpecializations" |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                    |
      | nodeAggregateId                     | "nody-mc-nodeface"       |
      | dimensionSpacePoint                 | {"example": "source"}    |
      | newParentNodeAggregateId            | "sir-david-nodenborough" |
      | newSucceedingSiblingNodeAggregateId | "elder-mc-nodeface"      |
      | relationDistributionStrategy        | "gatherSpecializations"  |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

  Scenario: Move a complete node aggregate to a new parent before one of its new siblings, which is not the first
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                    |
      | nodeAggregateId                     | "nody-mc-nodeface"       |
      | dimensionSpacePoint                 | {"example": "general"}   |
      | newParentNodeAggregateId            | "sir-david-nodenborough" |
      | newSucceedingSiblingNodeAggregateId | "elder-mc-nodeface"      |
      | relationDistributionStrategy        | "gatherSpecializations"  |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

  Scenario: Move a complete node aggregate to a new parent before one of its siblings, which is not the first and does not exist in all variants
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "elder-mc-nodeface"  |
      | coveredDimensionSpacePoint   | {"example": "spec"}  |
      | nodeVariantSelectionStrategy | "allSpecializations" |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                    |
      | nodeAggregateId                     | "nody-mc-nodeface"       |
      | newParentNodeAggregateId            | "sir-david-nodenborough" |
      | newSucceedingSiblingNodeAggregateId | "elder-mc-nodeface"      |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

  Scenario: Move a complete node aggregate to a new parent before one of its siblings, which is the last and does not exist in all variants
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                  |
      | nodeAggregateId              | "youngest-mc-nodeface" |
      | coveredDimensionSpacePoint   | {"example": "spec"}    |
      | nodeVariantSelectionStrategy | "allSpecializations"   |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                    |
      | nodeAggregateId                     | "nody-mc-nodeface"       |
      | dimensionSpacePoint                 | {"example": "source"}    |
      | newParentNodeAggregateId            | "sir-david-nodenborough" |
      | newSucceedingSiblingNodeAggregateId | "youngest-mc-nodeface"   |
      | relationDistributionStrategy        | "gatherSpecializations"  |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}  |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}  |
    And I expect this node to have no succeeding siblings

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

  Scenario: Move a complete node aggregate to a new parent after the last of its siblings
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                    |
      | nodeAggregateId                     | "nody-mc-nodeface"       |
      | dimensionSpacePoint                 | {"example": "source"}    |
      | newParentNodeAggregateId            | "sir-david-nodenborough" |
      | newSucceedingSiblingNodeAggregateId | null                     |
      | relationDistributionStrategy        | "gatherSpecializations"  |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}   |
    And I expect this node to have no succeeding siblings

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}   |
    And I expect this node to have no succeeding siblings

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

  Scenario: Move a complete node aggregate to a new parent after the last of its siblings, which does not exist in all variants
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                  |
      | nodeAggregateId              | "youngest-mc-nodeface" |
      | coveredDimensionSpacePoint   | {"example": "spec"}    |
      | nodeVariantSelectionStrategy | "allSpecializations"   |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                    |
      | nodeAggregateId                     | "nody-mc-nodeface"       |
      | dimensionSpacePoint                 | {"example": "source"}    |
      | newParentNodeAggregateId            | "sir-david-nodenborough" |
      | newSucceedingSiblingNodeAggregateId | null                     |
      | relationDistributionStrategy        | "gatherSpecializations"  |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}   |
    And I expect this node to have no succeeding siblings

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}  |
    And I expect this node to have no succeeding siblings

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

  Scenario: Move a complete node aggregate to a new parent after one of its siblings, which is partially the last
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                  |
      | nodeAggregateId              | "youngest-mc-nodeface" |
      | coveredDimensionSpacePoint   | {"example": "spec"}    |
      | nodeVariantSelectionStrategy | "allSpecializations"   |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                | Value                    |
      | nodeAggregateId                    | "nody-mc-nodeface"       |
      | dimensionSpacePoint                | {"example": "source"}    |
      | newParentNodeAggregateId           | "sir-david-nodenborough" |
      | newPrecedingSiblingNodeAggregateId | "younger-mc-nodeface"    |
      | relationDistributionStrategy       | "gatherSpecializations"  |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}  |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}  |
    And I expect this node to have no succeeding siblings

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

  Scenario: Move a complete node aggregate to a new parent after one of its siblings, which is not the last
    When the command MoveNodeAggregate is executed with payload:
      | Key                                | Value                    |
      | nodeAggregateId                    | "nody-mc-nodeface"       |
      | dimensionSpacePoint                | {"example": "source"}    |
      | newParentNodeAggregateId           | "sir-david-nodenborough" |
      | newPrecedingSiblingNodeAggregateId | "younger-mc-nodeface"    |
      | relationDistributionStrategy       | "gatherSpecializations"  |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}  |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}  |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

  Scenario: Move a complete node aggregate to a new parent after one of its siblings, which is not the last and does not exist in all variants
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                 |
      | nodeAggregateId              | "younger-mc-nodeface" |
      | coveredDimensionSpacePoint   | {"example": "spec"}   |
      | nodeVariantSelectionStrategy | "allSpecializations"  |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                | Value                    |
      | nodeAggregateId                    | "nody-mc-nodeface"       |
      | dimensionSpacePoint                | {"example": "source"}    |
      | newParentNodeAggregateId           | "sir-david-nodenborough" |
      | newPrecedingSiblingNodeAggregateId | "younger-mc-nodeface"    |
      | relationDistributionStrategy       | "gatherSpecializations"  |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}  |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}  |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

  Scenario: Move a complete node aggregate to a new parent after one of its siblings, which is the first and does not exist in all variants
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "eldest-mc-nodeface" |
      | coveredDimensionSpacePoint   | {"example": "spec"}  |
      | nodeVariantSelectionStrategy | "allSpecializations" |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                | Value                    |
      | nodeAggregateId                    | "nody-mc-nodeface"       |
      | dimensionSpacePoint                | {"example": "source"}    |
      | newParentNodeAggregateId           | "sir-david-nodenborough" |
      | newPrecedingSiblingNodeAggregateId | "eldest-mc-nodeface"     |
      | relationDistributionStrategy       | "gatherSpecializations"  |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}   |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

  # Test cases for the scatter strategy

  Scenario: Move a complete node aggregate to a new parent before the first of its new siblings
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                    |
      | nodeAggregateId                     | "nody-mc-nodeface"       |
      | dimensionSpacePoint                 | {"example": "source"}    |
      | newParentNodeAggregateId            | "sir-david-nodenborough" |
      | newSucceedingSiblingNodeAggregateId | "eldest-mc-nodeface"     |
      | relationDistributionStrategy        | "scatter"                |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}   |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

  Scenario: Move a complete node aggregate to a new parent before the first of its new siblings - which does not exist in all variants
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "eldest-mc-nodeface" |
      | coveredDimensionSpacePoint   | {"example": "spec"}  |
      | nodeVariantSelectionStrategy | "allSpecializations" |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                    |
      | nodeAggregateId                     | "nody-mc-nodeface"       |
      | dimensionSpacePoint                 | {"example": "source"}    |
      | newParentNodeAggregateId            | "sir-david-nodenborough" |
      | newSucceedingSiblingNodeAggregateId | "eldest-mc-nodeface"     |
      | relationDistributionStrategy        | "scatter"                |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}   |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

  Scenario: Move a complete node aggregate to a new parent before a siblings which is partially the first
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "eldest-mc-nodeface" |
      | coveredDimensionSpacePoint   | {"example": "spec"}  |
      | nodeVariantSelectionStrategy | "allSpecializations" |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                    |
      | nodeAggregateId                     | "nody-mc-nodeface"       |
      | dimensionSpacePoint                 | {"example": "source"}    |
      | newParentNodeAggregateId            | "sir-david-nodenborough" |
      | newSucceedingSiblingNodeAggregateId | "elder-mc-nodeface"      |
      | relationDistributionStrategy        | "scatter"                |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

  Scenario: Move a complete node aggregate to a new parent before one of its new siblings, which is not the first
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                    |
      | nodeAggregateId                     | "nody-mc-nodeface"       |
      | dimensionSpacePoint                 | {"example": "general"}   |
      | newParentNodeAggregateId            | "sir-david-nodenborough" |
      | newSucceedingSiblingNodeAggregateId | "elder-mc-nodeface"      |
      | relationDistributionStrategy        | "scatter"                |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

  Scenario: Move a complete node aggregate to a new parent before one of its siblings, which is not the first and does not exist in all variants
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "elder-mc-nodeface"  |
      | coveredDimensionSpacePoint   | {"example": "spec"}  |
      | nodeVariantSelectionStrategy | "allSpecializations" |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                    |
      | nodeAggregateId                     | "nody-mc-nodeface"       |
      | newParentNodeAggregateId            | "sir-david-nodenborough" |
      | newSucceedingSiblingNodeAggregateId | "elder-mc-nodeface"      |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

  Scenario: Move a complete node aggregate to a new parent before one of its siblings, which is the last and does not exist in all variants
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                  |
      | nodeAggregateId              | "youngest-mc-nodeface" |
      | coveredDimensionSpacePoint   | {"example": "spec"}    |
      | nodeVariantSelectionStrategy | "allSpecializations"   |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                    |
      | nodeAggregateId                     | "nody-mc-nodeface"       |
      | dimensionSpacePoint                 | {"example": "source"}    |
      | newParentNodeAggregateId            | "sir-david-nodenborough" |
      | newSucceedingSiblingNodeAggregateId | "youngest-mc-nodeface"   |
      | relationDistributionStrategy        | "scatter"                |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}  |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

  Scenario: Move a complete node aggregate to a new parent after the last of its siblings
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                    |
      | nodeAggregateId                     | "nody-mc-nodeface"       |
      | dimensionSpacePoint                 | {"example": "source"}    |
      | newParentNodeAggregateId            | "sir-david-nodenborough" |
      | newSucceedingSiblingNodeAggregateId | null                     |
      | relationDistributionStrategy        | "scatter"                |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}   |
    And I expect this node to have no succeeding siblings

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

  Scenario: Move a complete node aggregate to a new parent after the last of its siblings, which does not exist in all variants
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                  |
      | nodeAggregateId              | "youngest-mc-nodeface" |
      | coveredDimensionSpacePoint   | {"example": "spec"}    |
      | nodeVariantSelectionStrategy | "allSpecializations"   |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                    |
      | nodeAggregateId                     | "nody-mc-nodeface"       |
      | dimensionSpacePoint                 | {"example": "source"}    |
      | newParentNodeAggregateId            | "sir-david-nodenborough" |
      | newSucceedingSiblingNodeAggregateId | null                     |
      | relationDistributionStrategy        | "scatter"                |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}   |
    And I expect this node to have no succeeding siblings

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

  Scenario: Move a complete node aggregate to a new parent after one of its siblings, which is partially the last
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                  |
      | nodeAggregateId              | "youngest-mc-nodeface" |
      | coveredDimensionSpacePoint   | {"example": "spec"}    |
      | nodeVariantSelectionStrategy | "allSpecializations"   |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                | Value                    |
      | nodeAggregateId                    | "nody-mc-nodeface"       |
      | dimensionSpacePoint                | {"example": "source"}    |
      | newParentNodeAggregateId           | "sir-david-nodenborough" |
      | newPrecedingSiblingNodeAggregateId | "younger-mc-nodeface"    |
      | relationDistributionStrategy       | "scatter"                |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}  |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

  Scenario: Move a complete node aggregate to a new parent after one of its siblings, which is not the last
    When the command MoveNodeAggregate is executed with payload:
      | Key                                | Value                    |
      | nodeAggregateId                    | "nody-mc-nodeface"       |
      | dimensionSpacePoint                | {"example": "source"}    |
      | newParentNodeAggregateId           | "sir-david-nodenborough" |
      | newPrecedingSiblingNodeAggregateId | "younger-mc-nodeface"    |
      | relationDistributionStrategy       | "scatter"                |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}  |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

  Scenario: Move a complete node aggregate to a new parent after one of its siblings, which is not the last and does not exist in all variants
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                 |
      | nodeAggregateId              | "younger-mc-nodeface" |
      | coveredDimensionSpacePoint   | {"example": "spec"}   |
      | nodeVariantSelectionStrategy | "allSpecializations"  |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                | Value                    |
      | nodeAggregateId                    | "nody-mc-nodeface"       |
      | dimensionSpacePoint                | {"example": "source"}    |
      | newParentNodeAggregateId           | "sir-david-nodenborough" |
      | newPrecedingSiblingNodeAggregateId | "younger-mc-nodeface"    |
      | relationDistributionStrategy       | "scatter"                |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}  |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

  Scenario: Move a complete node aggregate to a new parent after one of its siblings, which is the first and does not exist in all variants
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "eldest-mc-nodeface" |
      | coveredDimensionSpacePoint   | {"example": "spec"}  |
      | nodeVariantSelectionStrategy | "allSpecializations" |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                | Value                    |
      | nodeAggregateId                    | "nody-mc-nodeface"       |
      | dimensionSpacePoint                | {"example": "source"}    |
      | newParentNodeAggregateId           | "sir-david-nodenborough" |
      | newPrecedingSiblingNodeAggregateId | "eldest-mc-nodeface"     |
      | relationDistributionStrategy       | "scatter"                |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                             |
      | cs-identifier;source-elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                               |
      | cs-identifier;source-younger-mc-nodeface;{"example": "general"} |

  # Legacy test cases

  Scenario: Move a complete node aggregate to a new parent after another of its new siblings - which does not exist in all variants
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                 |
      | nodeAggregateId              | "younger-mc-nodeface" |
      | coveredDimensionSpacePoint   | {"example": "spec"}   |
      | nodeVariantSelectionStrategy | "allSpecializations"  |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                | Value                    |
      | nodeAggregateId                    | "nody-mc-nodeface"       |
      | dimensionSpacePoint                | {"example": "general"}   |
      | newParentNodeAggregateId           | "sir-david-nodenborough" |
      | newPrecedingSiblingNodeAggregateId | "elder-mc-nodeface"      |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}  |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}  |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"} |
    And I expect this node to have no succeeding siblings

  Scenario: Move a complete node aggregate to a new parent after the last of its new siblings - with a predecessor which does not exist in all variants
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                 |
      | nodeAggregateId              | "younger-mc-nodeface" |
      | coveredDimensionSpacePoint   | {"example": "spec"}   |
      | nodeVariantSelectionStrategy | "allSpecializations"  |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                    |
      | nodeAggregateId                     | "nody-mc-nodeface"       |
      | dimensionSpacePoint                 | {"example": "general"}   |
      | newParentNodeAggregateId            | "sir-david-nodenborough" |
      | newSucceedingSiblingNodeAggregateId | null                     |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}  |
    And I expect this node to have no succeeding siblings

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}  |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"} |
    And I expect this node to have no succeeding siblings

  Scenario: Move a single node in a node aggregate to a new parent after the last of its new siblings
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                    |
      | nodeAggregateId                     | "nody-mc-nodeface"       |
      | dimensionSpacePoint                 | {"example": "source"}    |
      | newParentNodeAggregateId            | "sir-david-nodenborough" |
      | newSucceedingSiblingNodeAggregateId | null                     |
      | relationDistributionStrategy        | "scatter"                |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"example": "general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}  |
    And I expect this node to have no succeeding siblings

  Scenario: Move a node and its specializations in a node aggregate to a new parent after the last of its new siblings
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                    |
      | nodeAggregateId                     | "nody-mc-nodeface"       |
      | dimensionSpacePoint                 | {"example": "source"}    |
      | newParentNodeAggregateId            | "sir-david-nodenborough" |
      | newSucceedingSiblingNodeAggregateId | null                     |
      | relationDistributionStrategy        | "gatherSpecializations"  |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"example": "general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}  |
    And I expect this node to have no succeeding siblings

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}  |
    And I expect this node to have no succeeding siblings

  Scenario: Move a complete node aggregate to a new parent between siblings with different parents in other variants
    Given the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                  |
      | nodeAggregateId                     | "elder-mc-nodeface"    |
      | dimensionSpacePoint                 | {"example": "spec"}    |
      | newParentNodeAggregateId            | "bustling-mc-nodeface" |
      | newSucceedingSiblingNodeAggregateId | null                   |
      | relationDistributionStrategy        | "scatter"              |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                    |
      | nodeAggregateId                     | "nody-mc-nodeface"       |
      | dimensionSpacePoint                 | {"example": "general"}   |
      | newParentNodeAggregateId            | "sir-david-nodenborough" |
      | newPrecedingSiblingNodeAggregateId  | "eldest-mc-nodeface"     |
      | newSucceedingSiblingNodeAggregateId | "elder-mc-nodeface"      |
      | relationDistributionStrategy        | "gatherAll"              |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}   |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |

    # An explicitly given parent node aggregate identifier should overrule given sibling identifiers
    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |

  Scenario: Move a complete node aggregate between siblings with different parents in other variants (without explicit new parent)
    Given the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                  |
      | nodeAggregateId                     | "elder-mc-nodeface"    |
      | dimensionSpacePoint                 | {"example": "spec"}    |
      | newParentNodeAggregateId            | "bustling-mc-nodeface" |
      | newSucceedingSiblingNodeAggregateId | null                   |
      | relationDistributionStrategy        | "scatter"              |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                  |
      | nodeAggregateId                     | "nody-mc-nodeface"     |
      | dimensionSpacePoint                 | {"example": "general"} |
      | newPrecedingSiblingNodeAggregateId  | "eldest-mc-nodeface"   |
      | newSucceedingSiblingNodeAggregateId | "elder-mc-nodeface"    |
      | relationDistributionStrategy        | "gatherAll"            |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}   |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "bustling-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;bustling-mc-nodeface;{"example": "general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;elder-mc-nodeface;{"example": "general"} |

  Scenario: Move a node that has no name
    Given the command CreateNodeAggregateWithNode is executed with payload:
      | Key                   | Value                                     |
      | nodeAggregateId       | "nody-mc-nodeface-ii"                     |
      | nodeTypeName          | "Neos.ContentRepository.Testing:Document" |
      | parentNodeAggregateId | "sir-david-nodenborough"                  |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "nody-mc-nodeface-ii"    |
      | newParentNodeAggregateId     | "lady-eleonode-rootford" |
      | relationDistributionStrategy | "scatter"                |
    And the graph projection is fully up to date
    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    And I expect node aggregate identifier "nody-mc-nodeface-ii" to lead to node cs-identifier;nody-mc-nodeface-ii;{"example": "general"}
