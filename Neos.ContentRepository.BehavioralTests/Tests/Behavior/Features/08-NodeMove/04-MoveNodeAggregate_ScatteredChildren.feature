@contentrepository @adapters=DoctrineDBAL
Feature: Move a node with content dimensions

  As a user of the CR, when I move a node to a new parent, where that parent or requested siblings have been scattered,
  I expect scattered parents to be considered and scattered siblings to be ignored.
  The other way around, if a scattered node is moved, the variants are to be gathered again as specified in the command.

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
      | newContentStreamId   | "cs-identifier"      |
    And I am in workspace "live" and dimension space point {"example": "general"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId            | nodeTypeName                            | parentNodeAggregateId      | nodeName                      |
      | sir-david-nodenborough     | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford     | parent-document               |
      | elder-mc-nodeface          | Neos.ContentRepository.Testing:Document | sir-david-nodenborough     | elder-document                |
      | nody-mc-nodeface           | Neos.ContentRepository.Testing:Document | sir-david-nodenborough     | document                      |
      | younger-mc-nodeface        | Neos.ContentRepository.Testing:Document | sir-david-nodenborough     | younger-document              |
      | sir-nodeward-nodington-iii | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford     | esquire                       |
      | nodimus-mediocre           | Neos.ContentRepository.Testing:Document | sir-nodeward-nodington-iii | esquire-child                 |
      | elder-destinode            | Neos.ContentRepository.Testing:Document | sir-nodeward-nodington-iii | elder-target-document         |
      | bustling-destinode         | Neos.ContentRepository.Testing:Document | sir-nodeward-nodington-iii | bustling-target-document      |
      | younger-destinode          | Neos.ContentRepository.Testing:Document | sir-nodeward-nodington-iii | younger-target-document       |
      | elder-child-destinode      | Neos.ContentRepository.Testing:Document | nodimus-mediocre           | elder-child-target-document   |
      | younger-child-destinode    | Neos.ContentRepository.Testing:Document | nodimus-mediocre           | younger-child-target-document |

  Scenario: Scatter a node aggregate by moving a specialization variant to a different parent. Then move another node to the node's parent as a new sibling
    Given the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                     |
      | nodeAggregateId                     | "bustling-destinode"      |
      | dimensionSpacePoint                 | {"example": "spec"}       |
      | newParentNodeAggregateId            | "nodimus-mediocre"        |
      | newSucceedingSiblingNodeAggregateId | "younger-child-destinode" |
      | relationDistributionStrategy        | "scatter"                 |

    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                        |
      | nodeAggregateId                     | "nody-mc-nodeface"           |
      | dimensionSpacePoint                 | {"example": "source"}        |
      | newParentNodeAggregateId            | "sir-nodeward-nodington-iii" |
      | newSucceedingSiblingNodeAggregateId | "bustling-destinode"         |
      | relationDistributionStrategy        | "gatherSpecializations"      |

    Then I expect exactly 15 events to be published on stream "ContentStream:cs-identifier"
    And event at index 14 is of type "NodeAggregateWasMoved" with payload:
      | Key                           | Expected                                                                                                                                                               |
      | contentStreamId               | "cs-identifier"                                                                                                                                                        |
      | nodeAggregateId               | "nody-mc-nodeface"                                                                                                                                                     |
      | newParentNodeAggregateId      | "sir-nodeward-nodington-iii"                                                                                                                                           |
      | succeedingSiblingsForCoverage | [{"dimensionSpacePoint":{"example":"source"},"nodeAggregateId":"bustling-destinode"},{"dimensionSpacePoint":{"example":"spec"},"nodeAggregateId":"younger-destinode"}] |

    When I am in workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |

    When I am in workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                     |
      | cs-identifier;elder-destinode;{"example": "general"}  |
      | cs-identifier;nodimus-mediocre;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;bustling-destinode;{"example": "general"} |
      | cs-identifier;younger-destinode;{"example": "general"}  |

    When I am in workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                     |
      | cs-identifier;elder-destinode;{"example": "general"}  |
      | cs-identifier;nodimus-mediocre;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;younger-destinode;{"example": "general"} |

    When I am in workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |

  Scenario: Scatter a node aggregate by moving a specialization variant to a different parent. Then move another node to the scattered node as a new child
    Given the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                     |
      | nodeAggregateId                     | "bustling-destinode"      |
      | dimensionSpacePoint                 | {"example": "spec"}       |
      | newParentNodeAggregateId            | "nodimus-mediocre"        |
      | newSucceedingSiblingNodeAggregateId | "younger-child-destinode" |
      | relationDistributionStrategy        | "scatter"                 |

    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                   |
      | nodeAggregateId              | "nody-mc-nodeface"      |
      | dimensionSpacePoint          | {"example": "source"}   |
      | newParentNodeAggregateId     | "bustling-destinode"    |
      | relationDistributionStrategy | "gatherSpecializations" |

    Then I expect exactly 15 events to be published on stream "ContentStream:cs-identifier"
    And event at index 14 is of type "NodeAggregateWasMoved" with payload:
      | Key                           | Expected                                                                                                                                |
      | contentStreamId               | "cs-identifier"                                                                                                                         |
      | nodeAggregateId               | "nody-mc-nodeface"                                                                                                                      |
      | newParentNodeAggregateId      | "bustling-destinode"                                                                                                                    |
      | succeedingSiblingsForCoverage | [{"dimensionSpacePoint":{"example":"source"},"nodeAggregateId":null},{"dimensionSpacePoint":{"example":"spec"},"nodeAggregateId":null}] |

    When I am in workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |

    When I am in workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/bustling-target-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;bustling-destinode;{"example": "general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings

    When I am in workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/esquire-child/bustling-target-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;bustling-destinode;{"example": "general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings

    When I am in workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "parent-document/document" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |

  Scenario: Scatter a node aggregate by moving a specialization variant to a different parent. Then move both variants to a new succeeding sibling only present as sibling in the general one
    Given the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                     |
      | nodeAggregateId                     | "bustling-destinode"      |
      | dimensionSpacePoint                 | {"example": "spec"}       |
      | newParentNodeAggregateId            | "nodimus-mediocre"        |
      | newSucceedingSiblingNodeAggregateId | "younger-child-destinode" |
      | relationDistributionStrategy        | "scatter"                 |

    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                   |
      | nodeAggregateId                     | "bustling-destinode"    |
      | dimensionSpacePoint                 | {"example": "source"}   |
      | newSucceedingSiblingNodeAggregateId | "elder-destinode"       |
      | relationDistributionStrategy        | "gatherSpecializations" |

    Then I expect exactly 15 events to be published on stream "ContentStream:cs-identifier"
    And event at index 14 is of type "NodeAggregateWasMoved" with payload:
      | Key                           | Expected                                                                           |
      | contentStreamId               | "cs-identifier"                                                                    |
      | nodeAggregateId               | "bustling-destinode"                                                               |
      | newParentNodeAggregateId      | null                                                                               |
      | succeedingSiblingsForCoverage | [{"dimensionSpacePoint":{"example":"source"},"nodeAggregateId":"elder-destinode"}] |

    When I am in workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "bustling-destinode" and node path "esquire/bustling-target-document" to lead to node cs-identifier;bustling-destinode;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                     |
      | cs-identifier;elder-destinode;{"example": "general"}  |
      | cs-identifier;nodimus-mediocre;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;younger-destinode;{"example": "general"} |

    When I am in workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "bustling-destinode" and node path "esquire/bustling-target-document" to lead to node cs-identifier;bustling-destinode;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                     |
      | cs-identifier;nodimus-mediocre;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;elder-destinode;{"example": "general"}   |
      | cs-identifier;younger-destinode;{"example": "general"} |

    When I am in workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "bustling-destinode" and node path "esquire/esquire-child/bustling-target-document" to lead to node cs-identifier;bustling-destinode;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;nodimus-mediocre;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                          |
      | cs-identifier;elder-child-destinode;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                            |
      | cs-identifier;younger-child-destinode;{"example": "general"} |

    When I am in workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "bustling-destinode" and node path "esquire/bustling-target-document" to lead to node cs-identifier;bustling-destinode;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                     |
      | cs-identifier;elder-destinode;{"example": "general"}  |
      | cs-identifier;nodimus-mediocre;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;younger-destinode;{"example": "general"} |

  Scenario: Scatter a node aggregate by moving a specialization variant to a different parent. Then let a sibling variant follow suit and move the sibling before the node in both variants.
    # We expect to be the node to be the sibling's succeeding sibling in both variants across parents
    Given the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                     |
      | nodeAggregateId                     | "bustling-destinode"      |
      | dimensionSpacePoint                 | {"example": "spec"}       |
      | newParentNodeAggregateId            | "nodimus-mediocre"        |
      | newSucceedingSiblingNodeAggregateId | "younger-child-destinode" |
      | relationDistributionStrategy        | "scatter"                 |
    And the command MoveNodeAggregate is executed with payload:
      | Key                                | Value                |
      | nodeAggregateId                    | "elder-destinode"    |
      | dimensionSpacePoint                | {"example": "spec"}  |
      | newParentNodeAggregateId           | "nodimus-mediocre"   |
      | newPrecedingSiblingNodeAggregateId | "bustling-destinode" |
      | relationDistributionStrategy       | "scatter"            |

    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                   |
      | nodeAggregateId                     | "elder-destinode"       |
      | dimensionSpacePoint                 | {"example": "source"}   |
      | newSucceedingSiblingNodeAggregateId | "bustling-destinode"    |
      | relationDistributionStrategy        | "gatherSpecializations" |

    Then I expect exactly 16 events to be published on stream "ContentStream:cs-identifier"
    And event at index 15 is of type "NodeAggregateWasMoved" with payload:
      | Key                           | Expected                                                                                                                                                                |
      | contentStreamId               | "cs-identifier"                                                                                                                                                         |
      | nodeAggregateId               | "elder-destinode"                                                                                                                                                       |
      | newParentNodeAggregateId      | null                                                                                                                                                                    |
      | succeedingSiblingsForCoverage | [{"dimensionSpacePoint":{"example":"source"},"nodeAggregateId":"bustling-destinode"},{"dimensionSpacePoint":{"example":"spec"},"nodeAggregateId":"bustling-destinode"}] |

    When I am in workspace "live" and dimension space point {"example": "general"}
    Then I expect node aggregate identifier "elder-destinode" and node path "esquire/elder-target-document" to lead to node cs-identifier;elder-destinode;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                     |
      | cs-identifier;nodimus-mediocre;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;bustling-destinode;{"example": "general"} |
      | cs-identifier;younger-destinode;{"example": "general"}  |

    When I am in workspace "live" and dimension space point {"example": "source"}
    Then I expect node aggregate identifier "elder-destinode" and node path "esquire/elder-target-document" to lead to node cs-identifier;elder-destinode;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                     |
      | cs-identifier;nodimus-mediocre;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;bustling-destinode;{"example": "general"} |
      | cs-identifier;younger-destinode;{"example": "general"}  |

    When I am in workspace "live" and dimension space point {"example": "spec"}
    Then I expect node aggregate identifier "elder-destinode" and node path "esquire/esquire-child/elder-target-document" to lead to node cs-identifier;elder-destinode;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;nodimus-mediocre;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                          |
      | cs-identifier;elder-child-destinode;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                            |
      | cs-identifier;bustling-destinode;{"example": "general"}      |
      | cs-identifier;younger-child-destinode;{"example": "general"} |

    When I am in workspace "live" and dimension space point {"example": "peer"}
    Then I expect node aggregate identifier "elder-destinode" and node path "esquire/elder-target-document" to lead to node cs-identifier;elder-destinode;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                     |
      | cs-identifier;nodimus-mediocre;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;bustling-destinode;{"example": "general"} |
      | cs-identifier;younger-destinode;{"example": "general"}  |
