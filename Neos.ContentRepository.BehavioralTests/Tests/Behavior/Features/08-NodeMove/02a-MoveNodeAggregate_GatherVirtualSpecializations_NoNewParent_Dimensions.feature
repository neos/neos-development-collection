@contentrepository @adapters=DoctrineDBAL
Feature: Move a node with content dimensions

  As a user of the CR I want to move a node
  - before the first of its siblings
  - between two of its siblings
  - after the last of its siblings

  These are the test cases for moving nodes with content dimensions being involved, which is a lot more fun!

  Background:
    Given using the following content dimensions:
      | Identifier | Values                                           | Generalizations                                           |
      | example    | general, source, peer, spec, childSpec, leafSpec | leafSpec->childSpec->spec->source->general, peer->general |
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
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId        | nodeTypeName                            | parentNodeAggregateId  | nodeName         |
      | sir-david-nodenborough | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | document         |
      | eldest-mc-nodeface     | Neos.ContentRepository.Testing:Document | sir-david-nodenborough | child-document-a |
      | elder-mc-nodeface      | Neos.ContentRepository.Testing:Document | sir-david-nodenborough | child-document-b |
      | nody-mc-nodeface       | Neos.ContentRepository.Testing:Document | sir-david-nodenborough | child-document-n |
      | younger-mc-nodeface    | Neos.ContentRepository.Testing:Document | sir-david-nodenborough | child-document-c |
      | youngest-mc-nodeface   | Neos.ContentRepository.Testing:Document | sir-david-nodenborough | child-document-d |
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                  |
      | nodeAggregateId | "nody-mc-nodeface"     |
      | sourceOrigin    | {"example": "general"} |
      | targetOrigin    | {"example": "source"}  |
    And the graph projection is fully up to date
    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                  |
      | nodeAggregateId | "nody-mc-nodeface"     |
      | sourceOrigin    | {"example": "general"} |
      | targetOrigin    | {"example": "childSpec"}  |
    And the graph projection is fully up to date

  Scenario: Move a node and its virtual specializations before the first of its siblings
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                 |
      | nodeAggregateId                     | "nody-mc-nodeface"    |
      | dimensionSpacePoint                 | {"example": "source"} |
      | newParentNodeAggregateId            | null                  |
      | newSucceedingSiblingNodeAggregateId | "eldest-mc-nodeface"  |
      | relationDistributionStrategy        | "gatherVirtualSpecializations"           |
    And the graph projection is fully up to date

    Then I expect exactly 11 events to be published on stream "ContentStream:cs-identifier"
    And event at index 10 is of type "NodeAggregateWasMoved" with payload:
      | Key                           | Expected                                                                                                                                                                                                                                                                                                                                           |
      | contentStreamId               | "cs-identifier"                                                                                                                                                                                                                                                                                                                                    |
      | nodeAggregateId               | "nody-mc-nodeface"                                                                                                                                                                                                                                                                                                                                 |
      | newParentNodeAggregateId      | null                                                                                                                                                                                                                                                                                                                                               |
      | succeedingSiblingsForCoverage | [{"dimensionSpacePoint":{"example":"source"},"nodeAggregateId": "eldest-mc-nodeface"},{"dimensionSpacePoint":{"example":"spec"},"nodeAggregateId": "eldest-mc-nodeface"}] |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}   |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "source"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}   |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "source"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}   |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "childSpec"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "childSpec"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}   |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "leafSpec"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "childSpec"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}   |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}   |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

  Scenario: Move a node and its virtual specializations before the first of its siblings, which does not exist in all variants
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "eldest-mc-nodeface" |
      | coveredDimensionSpacePoint   | {"example": "spec"}  |
      | nodeVariantSelectionStrategy | "allSpecializations" |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                 |
      | nodeAggregateId                     | "nody-mc-nodeface"    |
      | dimensionSpacePoint                 | {"example": "source"} |
      | newParentNodeAggregateId            | null                  |
      | newSucceedingSiblingNodeAggregateId | "eldest-mc-nodeface"  |
      | relationDistributionStrategy        | "gatherVirtualSpecializations"           |
    And the graph projection is fully up to date

    Then I expect exactly 12 events to be published on stream "ContentStream:cs-identifier"
    And event at index 11 is of type "NodeAggregateWasMoved" with payload:
      | Key                           | Expected                                                                                                                                                                                                                                                                                                                                          |
      | contentStreamId               | "cs-identifier"                                                                                                                                                                                                                                                                                                                                   |
      | nodeAggregateId               | "nody-mc-nodeface"                                                                                                                                                                                                                                                                                                                                |
      | newParentNodeAggregateId      | null                                                                                                                                                                                                                                                                                                                                              |
      | succeedingSiblingsForCoverage | [{"dimensionSpacePoint":{"example":"source"},"nodeAggregateId": "eldest-mc-nodeface"},{"dimensionSpacePoint":{"example":"spec"},"nodeAggregateId": "elder-mc-nodeface"}] |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}   |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "source"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}   |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "source"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "childSpec"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "childSpec"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "leafSpec"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "childSpec"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}   |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    # you are here
  Scenario: Move a node and its virtual specializations before a siblings which is partially the first
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "eldest-mc-nodeface" |
      | coveredDimensionSpacePoint   | {"example": "spec"}  |
      | nodeVariantSelectionStrategy | "allSpecializations" |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                 |
      | nodeAggregateId                     | "nody-mc-nodeface"    |
      | dimensionSpacePoint                 | {"example": "source"} |
      | newParentNodeAggregateId            | null                  |
      | newSucceedingSiblingNodeAggregateId | "elder-mc-nodeface"   |
      | relationDistributionStrategy        | "gatherVirtualSpecializations"           |
    And the graph projection is fully up to date

    Then I expect exactly 10 events to be published on stream "ContentStream:cs-identifier"
    And event at index 9 is of type "NodeAggregateWasMoved" with payload:
      | Key                           | Expected                                                                                                                                                                                                                                                                                                                                       |
      | contentStreamId               | "cs-identifier"                                                                                                                                                                                                                                                                                                                                |
      | nodeAggregateId               | "nody-mc-nodeface"                                                                                                                                                                                                                                                                                                                             |
      | newParentNodeAggregateId      | null                                                                                                                                                                                                                                                                                                                                           |
      | succeedingSiblingsForCoverage | [{"dimensionSpacePoint":{"example":"general"},"nodeAggregateId": "elder-mc-nodeface"},{"dimensionSpacePoint":{"example":"source"},"nodeAggregateId": "elder-mc-nodeface"},{"dimensionSpacePoint":{"example":"spec"},"nodeAggregateId": "elder-mc-nodeface"},{"dimensionSpacePoint":{"example":"peer"},"nodeAggregateId": "elder-mc-nodeface"}] |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
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
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
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
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

  Scenario: Move a node and its virtual specializations before one of its siblings, which is not the first
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                 |
      | nodeAggregateId                     | "nody-mc-nodeface"    |
      | dimensionSpacePoint                 | {"example": "source"} |
      | newParentNodeAggregateId            | null                  |
      | newSucceedingSiblingNodeAggregateId | "elder-mc-nodeface"   |
      | relationDistributionStrategy        | "gatherVirtualSpecializations"           |
    And the graph projection is fully up to date

    Then I expect exactly 9 events to be published on stream "ContentStream:cs-identifier"
    And event at index 8 is of type "NodeAggregateWasMoved" with payload:
      | Key                           | Expected                                                                                                                                                                                                                                                                                                                                       |
      | contentStreamId               | "cs-identifier"                                                                                                                                                                                                                                                                                                                                |
      | nodeAggregateId               | "nody-mc-nodeface"                                                                                                                                                                                                                                                                                                                             |
      | newParentNodeAggregateId      | null                                                                                                                                                                                                                                                                                                                                           |
      | succeedingSiblingsForCoverage | [{"dimensionSpacePoint":{"example":"general"},"nodeAggregateId": "elder-mc-nodeface"},{"dimensionSpacePoint":{"example":"source"},"nodeAggregateId": "elder-mc-nodeface"},{"dimensionSpacePoint":{"example":"spec"},"nodeAggregateId": "elder-mc-nodeface"},{"dimensionSpacePoint":{"example":"peer"},"nodeAggregateId": "elder-mc-nodeface"}] |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
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
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
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
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
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
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

  Scenario: Move a node and its virtual specializations before one of its siblings, which is not the first and does not exist in all variants
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "elder-mc-nodeface"  |
      | coveredDimensionSpacePoint   | {"example": "spec"}  |
      | nodeVariantSelectionStrategy | "allSpecializations" |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                 |
      | nodeAggregateId                     | "nody-mc-nodeface"    |
      | dimensionSpacePoint                 | {"example": "source"} |
      | newParentNodeAggregateId            | null                  |
      | newSucceedingSiblingNodeAggregateId | "elder-mc-nodeface"   |
      | relationDistributionStrategy        | "gatherVirtualSpecializations"           |
    And the graph projection is fully up to date

    Then I expect exactly 10 events to be published on stream "ContentStream:cs-identifier"
    And event at index 9 is of type "NodeAggregateWasMoved" with payload:
      | Key                           | Expected                                                                                                                                                                                                                                                                                                                                         |
      | contentStreamId               | "cs-identifier"                                                                                                                                                                                                                                                                                                                                  |
      | nodeAggregateId               | "nody-mc-nodeface"                                                                                                                                                                                                                                                                                                                               |
      | newParentNodeAggregateId      | null                                                                                                                                                                                                                                                                                                                                             |
      | succeedingSiblingsForCoverage | [{"dimensionSpacePoint":{"example":"general"},"nodeAggregateId": "elder-mc-nodeface"},{"dimensionSpacePoint":{"example":"source"},"nodeAggregateId": "elder-mc-nodeface"},{"dimensionSpacePoint":{"example":"spec"},"nodeAggregateId": "younger-mc-nodeface"},{"dimensionSpacePoint":{"example":"peer"},"nodeAggregateId": "elder-mc-nodeface"}] |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
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
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
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
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

  Scenario: Move a node and its virtual specializations before one of its siblings, which is the last and does not exist in all variants
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                  |
      | nodeAggregateId              | "youngest-mc-nodeface" |
      | coveredDimensionSpacePoint   | {"example": "spec"}    |
      | nodeVariantSelectionStrategy | "allSpecializations"   |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                  |
      | nodeAggregateId                     | "nody-mc-nodeface"     |
      | dimensionSpacePoint                 | {"example": "source"}  |
      | newParentNodeAggregateId            | null                   |
      | newSucceedingSiblingNodeAggregateId | "youngest-mc-nodeface" |
      | relationDistributionStrategy        | "gatherVirtualSpecializations"            |
    And the graph projection is fully up to date

    Then I expect exactly 10 events to be published on stream "ContentStream:cs-identifier"
    And event at index 9 is of type "NodeAggregateWasMoved" with payload:
      | Key                           | Expected                                                                                                                                                                                                                                                              |
      | contentStreamId               | "cs-identifier"                                                                                                                                                                                                                                                       |
      | nodeAggregateId               | "nody-mc-nodeface"                                                                                                                                                                                                                                                    |
      | newParentNodeAggregateId      | null                                                                                                                                                                                                                                                                  |
      | succeedingSiblingsForCoverage | [{"dimensionSpacePoint":{"example":"general"},"nodeAggregateId": "youngest-mc-nodeface"},{"dimensionSpacePoint":{"example":"source"},"nodeAggregateId": "youngest-mc-nodeface"},{"dimensionSpacePoint":{"example":"peer"},"nodeAggregateId": "youngest-mc-nodeface"}] |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
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
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
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
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}  |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
    # The given succeeding sibling cannot be resolved and since younger-mc-nodeface isn't given as a preceding sibling, nothing is done
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}  |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

  Scenario: Move a node and its virtual specializations after the last of its siblings
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                 |
      | nodeAggregateId                     | "nody-mc-nodeface"    |
      | dimensionSpacePoint                 | {"example": "source"} |
      | newParentNodeAggregateId            | null                  |
      | newSucceedingSiblingNodeAggregateId | null                  |
      | relationDistributionStrategy        | "gatherVirtualSpecializations"           |
    And the graph projection is fully up to date

    Then I expect exactly 9 events to be published on stream "ContentStream:cs-identifier"
    And event at index 8 is of type "NodeAggregateWasMoved" with payload:
      | Key                           | Expected                                                                                                                                                                                                                                                                           |
      | contentStreamId               | "cs-identifier"                                                                                                                                                                                                                                                                    |
      | nodeAggregateId               | "nody-mc-nodeface"                                                                                                                                                                                                                                                                 |
      | newParentNodeAggregateId      | null                                                                                                                                                                                                                                                                               |
      | succeedingSiblingsForCoverage | [{"dimensionSpacePoint":{"example":"general"},"nodeAggregateId": null},{"dimensionSpacePoint":{"example":"source"},"nodeAggregateId": null},{"dimensionSpacePoint":{"example":"spec"},"nodeAggregateId": null},{"dimensionSpacePoint":{"example":"peer"},"nodeAggregateId": null}] |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}   |
    And I expect this node to have no succeeding siblings

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}   |
    And I expect this node to have no succeeding siblings

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}   |
    And I expect this node to have no succeeding siblings

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}   |
    And I expect this node to have no succeeding siblings

  Scenario: Move a node and its virtual specializations after the last of its siblings, which does not exist in all variants
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                  |
      | nodeAggregateId              | "youngest-mc-nodeface" |
      | coveredDimensionSpacePoint   | {"example": "spec"}    |
      | nodeVariantSelectionStrategy | "allSpecializations"   |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                 |
      | nodeAggregateId                     | "nody-mc-nodeface"    |
      | dimensionSpacePoint                 | {"example": "source"} |
      | newParentNodeAggregateId            | null                  |
      | newSucceedingSiblingNodeAggregateId | null                  |
      | relationDistributionStrategy        | "gatherVirtualSpecializations"           |
    And the graph projection is fully up to date

    Then I expect exactly 10 events to be published on stream "ContentStream:cs-identifier"
    And event at index 9 is of type "NodeAggregateWasMoved" with payload:
      | Key                           | Expected                                                                                                                                                                                                                                                                           |
      | contentStreamId               | "cs-identifier"                                                                                                                                                                                                                                                                    |
      | nodeAggregateId               | "nody-mc-nodeface"                                                                                                                                                                                                                                                                 |
      | newParentNodeAggregateId      | null                                                                                                                                                                                                                                                                               |
      | succeedingSiblingsForCoverage | [{"dimensionSpacePoint":{"example":"general"},"nodeAggregateId": null},{"dimensionSpacePoint":{"example":"source"},"nodeAggregateId": null},{"dimensionSpacePoint":{"example":"spec"},"nodeAggregateId": null},{"dimensionSpacePoint":{"example":"peer"},"nodeAggregateId": null}] |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}   |
    And I expect this node to have no succeeding siblings

    When I am in the active content stream of workspace "live" and dimension space point {"example": "source"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}   |
    And I expect this node to have no succeeding siblings

    When I am in the active content stream of workspace "live" and dimension space point {"example": "spec"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}  |
    And I expect this node to have no succeeding siblings

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}   |
    And I expect this node to have no succeeding siblings

  Scenario: Move a node and its virtual specializations after one of its siblings, which is partially the last
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                  |
      | nodeAggregateId              | "youngest-mc-nodeface" |
      | coveredDimensionSpacePoint   | {"example": "spec"}    |
      | nodeVariantSelectionStrategy | "allSpecializations"   |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                | Value                 |
      | nodeAggregateId                    | "nody-mc-nodeface"    |
      | dimensionSpacePoint                | {"example": "source"} |
      | newParentNodeAggregateId           | null                  |
      | newPrecedingSiblingNodeAggregateId | "younger-mc-nodeface" |
      | relationDistributionStrategy       | "gatherVirtualSpecializations"           |
    And the graph projection is fully up to date

    Then I expect exactly 10 events to be published on stream "ContentStream:cs-identifier"
    And event at index 9 is of type "NodeAggregateWasMoved" with payload:
      | Key                           | Expected                                                                                                                                                                                                                                                                                                                                 |
      | contentStreamId               | "cs-identifier"                                                                                                                                                                                                                                                                                                                          |
      | nodeAggregateId               | "nody-mc-nodeface"                                                                                                                                                                                                                                                                                                                       |
      | newParentNodeAggregateId      | null                                                                                                                                                                                                                                                                                                                                     |
      | succeedingSiblingsForCoverage | [{"dimensionSpacePoint":{"example":"general"},"nodeAggregateId": "youngest-mc-nodeface"},{"dimensionSpacePoint":{"example":"source"},"nodeAggregateId": "youngest-mc-nodeface"},{"dimensionSpacePoint":{"example":"spec"},"nodeAggregateId": null},{"dimensionSpacePoint":{"example":"peer"},"nodeAggregateId": "youngest-mc-nodeface"}] |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
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
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
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
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}  |
    And I expect this node to have no succeeding siblings

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}  |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

  Scenario: Move a node and its virtual specializations after one of its siblings, which is not the last
    When the command MoveNodeAggregate is executed with payload:
      | Key                                | Value                 |
      | nodeAggregateId                    | "nody-mc-nodeface"    |
      | dimensionSpacePoint                | {"example": "source"} |
      | newParentNodeAggregateId           | null                  |
      | newPrecedingSiblingNodeAggregateId | "younger-mc-nodeface" |
      | relationDistributionStrategy       | "gatherVirtualSpecializations"           |
    And the graph projection is fully up to date

    Then I expect exactly 9 events to be published on stream "ContentStream:cs-identifier"
    And event at index 8 is of type "NodeAggregateWasMoved" with payload:
      | Key                           | Expected                                                                                                                                                                                                                                                                                                                                                   |
      | contentStreamId               | "cs-identifier"                                                                                                                                                                                                                                                                                                                                            |
      | nodeAggregateId               | "nody-mc-nodeface"                                                                                                                                                                                                                                                                                                                                         |
      | newParentNodeAggregateId      | null                                                                                                                                                                                                                                                                                                                                                       |
      | succeedingSiblingsForCoverage | [{"dimensionSpacePoint":{"example":"general"},"nodeAggregateId": "youngest-mc-nodeface"},{"dimensionSpacePoint":{"example":"source"},"nodeAggregateId": "youngest-mc-nodeface"},{"dimensionSpacePoint":{"example":"spec"},"nodeAggregateId": "youngest-mc-nodeface"},{"dimensionSpacePoint":{"example":"peer"},"nodeAggregateId": "youngest-mc-nodeface"}] |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
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
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
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
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
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
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}  |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

  Scenario: Move a node and its virtual specializations after one of its siblings, which is not the last and does not exist in all variants
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                 |
      | nodeAggregateId              | "younger-mc-nodeface" |
      | coveredDimensionSpacePoint   | {"example": "spec"}   |
      | nodeVariantSelectionStrategy | "allSpecializations"  |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                | Value                 |
      | nodeAggregateId                    | "nody-mc-nodeface"    |
      | dimensionSpacePoint                | {"example": "source"} |
      | newParentNodeAggregateId           | null                  |
      | newPrecedingSiblingNodeAggregateId | "younger-mc-nodeface" |
      | relationDistributionStrategy       | "gatherVirtualSpecializations"           |
    And the graph projection is fully up to date

    Then I expect exactly 10 events to be published on stream "ContentStream:cs-identifier"
    And event at index 9 is of type "NodeAggregateWasMoved" with payload:
      | Key                           | Expected                                                                                                                                                                                                                                                                                                                                                   |
      | contentStreamId               | "cs-identifier"                                                                                                                                                                                                                                                                                                                                            |
      | nodeAggregateId               | "nody-mc-nodeface"                                                                                                                                                                                                                                                                                                                                         |
      | newParentNodeAggregateId      | null                                                                                                                                                                                                                                                                                                                                                       |
      | succeedingSiblingsForCoverage | [{"dimensionSpacePoint":{"example":"general"},"nodeAggregateId": "youngest-mc-nodeface"},{"dimensionSpacePoint":{"example":"source"},"nodeAggregateId": "youngest-mc-nodeface"},{"dimensionSpacePoint":{"example":"spec"},"nodeAggregateId": "youngest-mc-nodeface"},{"dimensionSpacePoint":{"example":"peer"},"nodeAggregateId": "youngest-mc-nodeface"}] |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
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
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
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
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}  |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example": "general"} |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"}  |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

  Scenario: Move a node and its virtual specializations after one of its siblings, which is the first and does not exist in all variants
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "eldest-mc-nodeface" |
      | coveredDimensionSpacePoint   | {"example": "spec"}  |
      | nodeVariantSelectionStrategy | "allSpecializations" |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                | Value                 |
      | nodeAggregateId                    | "nody-mc-nodeface"    |
      | dimensionSpacePoint                | {"example": "source"} |
      | newParentNodeAggregateId           | null                  |
      | newPrecedingSiblingNodeAggregateId | "eldest-mc-nodeface"  |
      | relationDistributionStrategy       | "gatherVirtualSpecializations"           |
    And the graph projection is fully up to date

    Then I expect exactly 10 events to be published on stream "ContentStream:cs-identifier"
    And event at index 9 is of type "NodeAggregateWasMoved" with payload:
      | Key                           | Expected                                                                                                                                                                                                                                                     |
      | contentStreamId               | "cs-identifier"                                                                                                                                                                                                                                              |
      | nodeAggregateId               | "nody-mc-nodeface"                                                                                                                                                                                                                                           |
      | newParentNodeAggregateId      | null                                                                                                                                                                                                                                                         |
      | succeedingSiblingsForCoverage | [{"dimensionSpacePoint":{"example":"general"},"nodeAggregateId": "elder-mc-nodeface"},{"dimensionSpacePoint":{"example":"source"},"nodeAggregateId": "elder-mc-nodeface"},{"dimensionSpacePoint":{"example":"peer"},"nodeAggregateId": "elder-mc-nodeface"}] |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "general"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
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
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
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
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
    # The given preceding sibling cannot be resolved and since elder-mc-nodeface isn't given as a succeeding sibling, nothing is done
      | NodeDiscriminator                                      |
      | cs-identifier;elder-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |

    When I am in the active content stream of workspace "live" and dimension space point {"example": "peer"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"example": "general"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"example": "general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;eldest-mc-nodeface;{"example": "general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                         |
      | cs-identifier;elder-mc-nodeface;{"example": "general"}    |
      | cs-identifier;younger-mc-nodeface;{"example": "general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example": "general"} |
