@contentrepository @adapters=DoctrineDBAL
Feature: Move a node with content dimensions

  As a user of the CR I want to move a node
  - before the first of its siblings
  - between two of its siblings
  - after the last of its siblings

  These are the test cases for moving nodes with content dimensions being involved, which is a lot more fun!

  Background:
    Given using the following content dimensions:
      | Identifier | Values           | Generalizations       |
      | language   | mul, de, en, gsw | gsw->de->mul, en->mul |
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
    And I am in the active content stream of workspace "live"
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                                                              |
      | contentStreamId             | "cs-identifier"                                                                    |
      | nodeAggregateId             | "sir-david-nodenborough"                                                           |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Document"                                          |
      | originDimensionSpacePoint   | {"language": "mul"}                                                                |
      | coveredDimensionSpacePoints | [{"language": "mul"}, {"language": "de"}, {"language": "en"}, {"language": "gsw"}] |
      | parentNodeAggregateId       | "lady-eleonode-rootford"                                                           |
      | nodeName                    | "document"                                                                         |
      | nodeAggregateClassification | "regular"                                                                          |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                                                              |
      | contentStreamId             | "cs-identifier"                                                                    |
      | nodeAggregateId             | "anthony-destinode"                                                                |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Document"                                          |
      | originDimensionSpacePoint   | {"language": "mul"}                                                                |
      | coveredDimensionSpacePoints | [{"language": "mul"}, {"language": "de"}, {"language": "en"}, {"language": "gsw"}] |
      | parentNodeAggregateId       | "sir-david-nodenborough"                                                           |
      | nodeName                    | "child-document-a"                                                                 |
      | nodeAggregateClassification | "regular"                                                                          |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                                                              |
      | contentStreamId             | "cs-identifier"                                                                    |
      | nodeAggregateId             | "berta-destinode"                                                                  |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Document"                                          |
      | originDimensionSpacePoint   | {"language": "mul"}                                                                |
      | coveredDimensionSpacePoints | [{"language": "mul"}, {"language": "de"}, {"language": "en"}, {"language": "gsw"}] |
      | parentNodeAggregateId       | "sir-david-nodenborough"                                                           |
      | nodeName                    | "child-document-b"                                                                 |
      | nodeAggregateClassification | "regular"                                                                          |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                                                              |
      | contentStreamId             | "cs-identifier"                                                                    |
      | nodeAggregateId             | "nody-mc-nodeface"                                                                 |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Document"                                          |
      | originDimensionSpacePoint   | {"language": "mul"}                                                                |
      | coveredDimensionSpacePoints | [{"language": "mul"}, {"language": "de"}, {"language": "en"}, {"language": "gsw"}] |
      | parentNodeAggregateId       | "sir-david-nodenborough"                                                           |
      | nodeName                    | "child-document-n"                                                                 |
      | nodeAggregateClassification | "regular"                                                                          |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                                                              |
      | contentStreamId             | "cs-identifier"                                                                    |
      | nodeAggregateId             | "carl-destinode"                                                                   |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Document"                                          |
      | originDimensionSpacePoint   | {"language": "mul"}                                                                |
      | coveredDimensionSpacePoints | [{"language": "mul"}, {"language": "de"}, {"language": "en"}, {"language": "gsw"}] |
      | parentNodeAggregateId       | "sir-david-nodenborough"                                                           |
      | nodeName                    | "child-document-c"                                                                 |
      | nodeAggregateClassification | "regular"                                                                          |
    And the event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                                                                              |
      | contentStreamId             | "cs-identifier"                                                                    |
      | nodeAggregateId             | "sir-nodeward-nodington-iii"                                                       |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Document"                                          |
      | originDimensionSpacePoint   | {"language": "mul"}                                                                |
      | coveredDimensionSpacePoints | [{"language": "mul"}, {"language": "de"}, {"language": "en"}, {"language": "gsw"}] |
      | parentNodeAggregateId       | "lady-eleonode-rootford"                                                           |
      | nodeName                    | "esquire"                                                                          |
      | nodeAggregateClassification | "regular"                                                                          |
    And the graph projection is fully up to date

  Scenario: Move a complete node aggregate before the first of its siblings
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value               |
      | nodeAggregateId                     | "nody-mc-nodeface"  |
      | dimensionSpacePoint                 | {"language": "mul"} |
      | newParentNodeAggregateId            | null                |
      | newSucceedingSiblingNodeAggregateId | "anthony-destinode" |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"language": "mul"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language": "mul"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;anthony-destinode;{"language": "mul"} |
      | cs-identifier;berta-destinode;{"language": "mul"}   |
      | cs-identifier;carl-destinode;{"language": "mul"}    |

  Scenario: Move a complete node aggregate before the first of its siblings - which does not exist in all variants
    Given the event NodeAggregateWasRemoved was published with payload:
      | Key                                  | Value                 |
      | contentStreamId                      | "cs-identifier"       |
      | nodeAggregateId                      | "anthony-destinode"   |
      | affectedOccupiedDimensionSpacePoints | []                    |
      | affectedCoveredDimensionSpacePoints  | [{"language": "gsw"}] |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value               |
      | nodeAggregateId                     | "nody-mc-nodeface"  |
      | dimensionSpacePoint                 | {"language": "mul"} |
      | newParentNodeAggregateId            | null                |
      | newSucceedingSiblingNodeAggregateId | "anthony-destinode" |
      | relationDistributionStrategy        | "gatherAll"         |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"language": "mul"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language": "mul"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;anthony-destinode;{"language": "mul"} |
      | cs-identifier;berta-destinode;{"language": "mul"}   |
      | cs-identifier;carl-destinode;{"language": "mul"}    |

    When I am in the active content stream of workspace "live" and dimension space point {"language": "gsw"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language": "mul"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                 |
      | cs-identifier;berta-destinode;{"language": "mul"} |
      | cs-identifier;carl-destinode;{"language": "mul"}  |

  Scenario: Move a complete node aggregate before another of its siblings
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value               |
      | nodeAggregateId                     | "nody-mc-nodeface"  |
      | dimensionSpacePoint                 | {"language": "mul"} |
      | newParentNodeAggregateId            | null                |
      | newSucceedingSiblingNodeAggregateId | "berta-destinode"   |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"language": "mul"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language": "mul"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;anthony-destinode;{"language": "mul"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                 |
      | cs-identifier;berta-destinode;{"language": "mul"} |
      | cs-identifier;carl-destinode;{"language": "mul"}  |

  Scenario: Move a complete node aggregate before another of its siblings - which does not exist in all variants
    Given the event NodeAggregateWasRemoved was published with payload:
      | Key                                  | Value                 |
      | contentStreamId                      | "cs-identifier"       |
      | nodeAggregateId                      | "berta-destinode"     |
      | affectedOccupiedDimensionSpacePoints | []                    |
      | affectedCoveredDimensionSpacePoints  | [{"language": "gsw"}] |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value               |
      | nodeAggregateId                     | "nody-mc-nodeface"  |
      | dimensionSpacePoint                 | {"language": "mul"} |
      | newParentNodeAggregateId            | null                |
      | newSucceedingSiblingNodeAggregateId | "berta-destinode"   |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"language": "mul"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language": "mul"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;anthony-destinode;{"language": "mul"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                 |
      | cs-identifier;berta-destinode;{"language": "mul"} |
      | cs-identifier;carl-destinode;{"language": "mul"}  |

    When I am in the active content stream of workspace "live" and dimension space point {"language": "gsw"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language": "mul"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;anthony-destinode;{"language": "mul"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                |
      | cs-identifier;carl-destinode;{"language": "mul"} |

  Scenario: Move a complete node aggregate after another of its siblings - which does not exist in all variants
    Given the event NodeAggregateWasRemoved was published with payload:
      | Key                                  | Value                 |
      | contentStreamId                      | "cs-identifier"       |
      | nodeAggregateId                      | "carl-destinode"      |
      | affectedOccupiedDimensionSpacePoints | []                    |
      | affectedCoveredDimensionSpacePoints  | [{"language": "gsw"}] |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                | Value               |
      | nodeAggregateId                    | "nody-mc-nodeface"  |
      | dimensionSpacePoint                | {"language": "mul"} |
      | newParentNodeAggregateId           | null                |
      | newPrecedingSiblingNodeAggregateId | "berta-destinode"   |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"language": "mul"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language": "mul"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;berta-destinode;{"language": "mul"}   |
      | cs-identifier;anthony-destinode;{"language": "mul"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                |
      | cs-identifier;carl-destinode;{"language": "mul"} |

    When I am in the active content stream of workspace "live" and dimension space point {"language": "gsw"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language": "mul"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;berta-destinode;{"language": "mul"}   |
      | cs-identifier;anthony-destinode;{"language": "mul"} |
    And I expect this node to have no succeeding siblings

  Scenario: Move a complete node aggregate after the last of its siblings - with a predecessor which does not exist in all variants
    Given the event NodeAggregateWasRemoved was published with payload:
      | Key                                  | Value                 |
      | contentStreamId                      | "cs-identifier"       |
      | nodeAggregateId                      | "carl-destinode"      |
      | affectedOccupiedDimensionSpacePoints | []                    |
      | affectedCoveredDimensionSpacePoints  | [{"language": "gsw"}] |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value               |
      | nodeAggregateId                     | "nody-mc-nodeface"  |
      | dimensionSpacePoint                 | {"language": "mul"} |
      | newParentNodeAggregateId            | null                |
      | newSucceedingSiblingNodeAggregateId | null                |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"language": "mul"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language": "mul"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;carl-destinode;{"language": "mul"}    |
      | cs-identifier;berta-destinode;{"language": "mul"}   |
      | cs-identifier;anthony-destinode;{"language": "mul"} |
    And I expect this node to have no succeeding siblings

    When I am in the active content stream of workspace "live" and dimension space point {"language": "gsw"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language": "mul"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;berta-destinode;{"language": "mul"}   |
      | cs-identifier;anthony-destinode;{"language": "mul"} |
    And I expect this node to have no succeeding siblings

  Scenario: Move a single node before the first of its siblings
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value               |
      | nodeAggregateId                     | "nody-mc-nodeface"  |
      | dimensionSpacePoint                 | {"language": "mul"} |
      | newSucceedingSiblingNodeAggregateId | "anthony-destinode" |
      | relationDistributionStrategy        | "scatter"           |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"language": "mul"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language": "mul"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;anthony-destinode;{"language": "mul"} |
      | cs-identifier;berta-destinode;{"language": "mul"}   |
      | cs-identifier;carl-destinode;{"language": "mul"}    |

    When I am in the active content stream of workspace "live" and dimension space point {"language": "de"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language": "mul"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;berta-destinode;{"language": "mul"}   |
      | cs-identifier;anthony-destinode;{"language": "mul"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                |
      | cs-identifier;carl-destinode;{"language": "mul"} |

  Scenario: Move a single node between two of its siblings
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value               |
      | nodeAggregateId                     | "nody-mc-nodeface"  |
      | dimensionSpacePoint                 | {"language": "mul"} |
      | newSucceedingSiblingNodeAggregateId | "berta-destinode"   |
      | relationDistributionStrategy        | "scatter"           |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"language": "mul"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language": "mul"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;anthony-destinode;{"language": "mul"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                 |
      | cs-identifier;berta-destinode;{"language": "mul"} |
      | cs-identifier;carl-destinode;{"language": "mul"}  |

    When I am in the active content stream of workspace "live" and dimension space point {"language": "de"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language": "mul"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;berta-destinode;{"language": "mul"}   |
      | cs-identifier;anthony-destinode;{"language": "mul"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                |
      | cs-identifier;carl-destinode;{"language": "mul"} |

  Scenario: Move a single node to the end of its siblings
    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value               |
      | nodeAggregateId              | "nody-mc-nodeface"  |
      | dimensionSpacePoint          | {"language": "mul"} |
      | relationDistributionStrategy | "scatter"           |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"language": "mul"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language": "mul"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;carl-destinode;{"language": "mul"}    |
      | cs-identifier;berta-destinode;{"language": "mul"}   |
      | cs-identifier;anthony-destinode;{"language": "mul"} |
    And I expect this node to have no succeeding siblings

    When I am in the active content stream of workspace "live" and dimension space point {"language": "de"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language": "mul"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;berta-destinode;{"language": "mul"}   |
      | cs-identifier;anthony-destinode;{"language": "mul"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                |
      | cs-identifier;carl-destinode;{"language": "mul"} |

  Scenario: Move a node and its specializations before the first of its siblings
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                   |
      | nodeAggregateId                     | "nody-mc-nodeface"      |
      | dimensionSpacePoint                 | {"language": "de"}      |
      | newSucceedingSiblingNodeAggregateId | "anthony-destinode"     |
      | relationDistributionStrategy        | "gatherSpecializations" |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"language": "mul"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language": "mul"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;berta-destinode;{"language": "mul"}   |
      | cs-identifier;anthony-destinode;{"language": "mul"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                |
      | cs-identifier;carl-destinode;{"language": "mul"} |

    When I am in the active content stream of workspace "live" and dimension space point {"language": "de"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language": "mul"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;anthony-destinode;{"language": "mul"} |
      | cs-identifier;berta-destinode;{"language": "mul"}   |
      | cs-identifier;carl-destinode;{"language": "mul"}    |

    When I am in the active content stream of workspace "live" and dimension space point {"language": "gsw"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language": "mul"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;anthony-destinode;{"language": "mul"} |
      | cs-identifier;berta-destinode;{"language": "mul"}   |
      | cs-identifier;carl-destinode;{"language": "mul"}    |

  Scenario: Move a node and its specializations between two of its siblings
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                   |
      | nodeAggregateId                     | "nody-mc-nodeface"      |
      | dimensionSpacePoint                 | {"language": "de"}      |
      | newSucceedingSiblingNodeAggregateId | "berta-destinode"       |
      | relationDistributionStrategy        | "gatherSpecializations" |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"language": "mul"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language": "mul"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;berta-destinode;{"language": "mul"}   |
      | cs-identifier;anthony-destinode;{"language": "mul"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                |
      | cs-identifier;carl-destinode;{"language": "mul"} |

    When I am in the active content stream of workspace "live" and dimension space point {"language": "de"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language": "mul"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;anthony-destinode;{"language": "mul"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                 |
      | cs-identifier;berta-destinode;{"language": "mul"} |
      | cs-identifier;carl-destinode;{"language": "mul"}  |

    When I am in the active content stream of workspace "live" and dimension space point {"language": "gsw"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language": "mul"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;anthony-destinode;{"language": "mul"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                 |
      | cs-identifier;berta-destinode;{"language": "mul"} |
      | cs-identifier;carl-destinode;{"language": "mul"}  |

  Scenario: Move a node and its specializations to the end of its siblings
    When the command MoveNodeAggregate is executed with payload:
      | Key                          | Value                   |
      | nodeAggregateId              | "nody-mc-nodeface"      |
      | dimensionSpacePoint          | {"language": "de"}      |
      | relationDistributionStrategy | "gatherSpecializations" |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"language": "mul"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language": "mul"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;berta-destinode;{"language": "mul"}   |
      | cs-identifier;anthony-destinode;{"language": "mul"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                |
      | cs-identifier;carl-destinode;{"language": "mul"} |

    When I am in the active content stream of workspace "live" and dimension space point {"language": "de"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language": "mul"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;carl-destinode;{"language": "mul"}    |
      | cs-identifier;berta-destinode;{"language": "mul"}   |
      | cs-identifier;anthony-destinode;{"language": "mul"} |
    And I expect this node to have no succeeding siblings

    When I am in the active content stream of workspace "live" and dimension space point {"language": "gsw"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language": "mul"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;carl-destinode;{"language": "mul"}    |
      | cs-identifier;berta-destinode;{"language": "mul"}   |
      | cs-identifier;anthony-destinode;{"language": "mul"} |
    And I expect this node to have no succeeding siblings

  Scenario: Trigger position update in DBAL graph
    Given I am in the active content stream of workspace "live" and dimension space point {"language": "mul"}
    # distance i to x: 128
    Given the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId             | nodeTypeName                            | parentNodeAggregateId  | nodeName       |
      | lady-nodette-nodington-i    | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | nodington-i    |
      | lady-nodette-nodington-x    | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | nodington-x    |
      | lady-nodette-nodington-ix   | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | nodington-ix   |
      | lady-nodette-nodington-viii | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | nodington-viii |
      | lady-nodette-nodington-vii  | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | nodington-vii  |
      | lady-nodette-nodington-vi   | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | nodington-vi   |
      | lady-nodette-nodington-v    | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | nodington-v    |
      | lady-nodette-nodington-iv   | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | nodington-iv   |
      | lady-nodette-nodington-iii  | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | nodington-iii  |
      | lady-nodette-nodington-ii   | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | nodington-ii   |
    # distance ii to x: 64
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                       |
      | nodeAggregateId                     | "lady-nodette-nodington-ii" |
      | newSucceedingSiblingNodeAggregateId | "lady-nodette-nodington-x"  |
    And the graph projection is fully up to date
    # distance iii to x: 32
    And the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                        |
      | nodeAggregateId                     | "lady-nodette-nodington-iii" |
      | newSucceedingSiblingNodeAggregateId | "lady-nodette-nodington-x"   |
    And the graph projection is fully up to date
    # distance iv to x: 16
    And the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                       |
      | nodeAggregateId                     | "lady-nodette-nodington-iv" |
      | newSucceedingSiblingNodeAggregateId | "lady-nodette-nodington-x"  |
    And the graph projection is fully up to date
    # distance v to x: 8
    And the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                      |
      | nodeAggregateId                     | "lady-nodette-nodington-v" |
      | newSucceedingSiblingNodeAggregateId | "lady-nodette-nodington-x" |
    And the graph projection is fully up to date
    # distance vi to x: 4
    And the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                       |
      | nodeAggregateId                     | "lady-nodette-nodington-vi" |
      | newSucceedingSiblingNodeAggregateId | "lady-nodette-nodington-x"  |
    And the graph projection is fully up to date
    # distance vii to x: 2
    And the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                        |
      | nodeAggregateId                     | "lady-nodette-nodington-vii" |
      | newSucceedingSiblingNodeAggregateId | "lady-nodette-nodington-x"   |
    And the graph projection is fully up to date
    # distance viii to x: 1 -> reorder -> 128
    And the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                         |
      | nodeAggregateId                     | "lady-nodette-nodington-viii" |
      | newSucceedingSiblingNodeAggregateId | "lady-nodette-nodington-x"    |
    And the graph projection is fully up to date
    # distance ix to x: 64 after reorder
    And the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                       |
      | nodeAggregateId                     | "lady-nodette-nodington-ix" |
      | newSucceedingSiblingNodeAggregateId | "lady-nodette-nodington-x"  |
    And the graph projection is fully up to date

    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name           | NodeDiscriminator                                             |
      | document       | cs-identifier;sir-david-nodenborough;{"language": "mul"}      |
      | esquire        | cs-identifier;sir-nodeward-nodington-iii;{"language": "mul"}  |
      | nodington-i    | cs-identifier;lady-nodette-nodington-i;{"language": "mul"}    |
      | nodington-ii   | cs-identifier;lady-nodette-nodington-ii;{"language": "mul"}   |
      | nodington-iii  | cs-identifier;lady-nodette-nodington-iii;{"language": "mul"}  |
      | nodington-iv   | cs-identifier;lady-nodette-nodington-iv;{"language": "mul"}   |
      | nodington-v    | cs-identifier;lady-nodette-nodington-v;{"language": "mul"}    |
      | nodington-vi   | cs-identifier;lady-nodette-nodington-vi;{"language": "mul"}   |
      | nodington-vii  | cs-identifier;lady-nodette-nodington-vii;{"language": "mul"}  |
      | nodington-viii | cs-identifier;lady-nodette-nodington-viii;{"language": "mul"} |
      | nodington-ix   | cs-identifier;lady-nodette-nodington-ix;{"language": "mul"}   |
      | nodington-x    | cs-identifier;lady-nodette-nodington-x;{"language": "mul"}    |


