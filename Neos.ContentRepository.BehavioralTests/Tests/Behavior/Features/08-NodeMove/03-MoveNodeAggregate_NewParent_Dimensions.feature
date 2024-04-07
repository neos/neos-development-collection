@contentrepository @adapters=DoctrineDBAL
Feature: Move a node with content dimensions

  As a user of the CR I want to move a node to a new parent
  - before the first of its new siblings
  - between two of its new siblings
  - after the last of its new siblings

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
    And I am in the active content stream of workspace "live" and dimension space point {"language": "mul"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId            | nodeTypeName                            | parentNodeAggregateId      | nodeName         |
      | sir-david-nodenborough     | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford     | document         |
      | anthony-destinode          | Neos.ContentRepository.Testing:Document | sir-david-nodenborough     | child-document-a |
      | berta-destinode            | Neos.ContentRepository.Testing:Document | sir-david-nodenborough     | child-document-b |
      | carl-destinode             | Neos.ContentRepository.Testing:Document | sir-david-nodenborough     | child-document-c |
      | sir-nodeward-nodington-iii | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford     | esquire          |
      | nody-mc-nodeface           | Neos.ContentRepository.Testing:Document | sir-nodeward-nodington-iii | child-document-n |
      | lady-abigail-nodenborough  | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford     | document2        |

  Scenario: Move a complete node aggregate to a new parent before the first of its new siblings
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                    |
      | nodeAggregateId                     | "nody-mc-nodeface"       |
      | newParentNodeAggregateId            | "sir-david-nodenborough" |
      | newSucceedingSiblingNodeAggregateId | "anthony-destinode"      |
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

  Scenario: Move a complete node aggregate to a new parent before the first of its new siblings - which does not exist in all variants
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "anthony-destinode"  |
      | coveredDimensionSpacePoint   | {"language": "gsw"}  |
      | nodeVariantSelectionStrategy | "allSpecializations" |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                    |
      | nodeAggregateId                     | "nody-mc-nodeface"       |
      | newParentNodeAggregateId            | "sir-david-nodenborough" |
      | newSucceedingSiblingNodeAggregateId | "anthony-destinode"      |
      | relationDistributionStrategy        | "gatherAll"              |
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

  Scenario: Move a complete node aggregate to a new parent before one of its new siblings
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                    |
      | nodeAggregateId                     | "nody-mc-nodeface"       |
      | dimensionSpacePoint                 | {"language": "mul"}      |
      | newParentNodeAggregateId            | "sir-david-nodenborough" |
      | newSucceedingSiblingNodeAggregateId | "berta-destinode"        |
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

  Scenario: Move a complete node aggregate to a new parent before one of its siblings - which does not exist in all variants
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "berta-destinode"    |
      | coveredDimensionSpacePoint   | {"language": "gsw"}  |
      | nodeVariantSelectionStrategy | "allSpecializations" |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                    |
      | nodeAggregateId                     | "nody-mc-nodeface"       |
      | newParentNodeAggregateId            | "sir-david-nodenborough" |
      | newSucceedingSiblingNodeAggregateId | "berta-destinode"        |
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

  Scenario: Move a complete node aggregate to a new parent after another of its new siblings - which does not exist in all variants
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "carl-destinode"     |
      | coveredDimensionSpacePoint   | {"language": "gsw"}  |
      | nodeVariantSelectionStrategy | "allSpecializations" |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                | Value                    |
      | nodeAggregateId                    | "nody-mc-nodeface"       |
      | dimensionSpacePoint                | {"language": "mul"}      |
      | newParentNodeAggregateId           | "sir-david-nodenborough" |
      | newPrecedingSiblingNodeAggregateId | "berta-destinode"        |
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

  Scenario: Move a complete node aggregate to a new parent after the last of its new siblings - with a predecessor which does not exist in all variants
    Given the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "carl-destinode"     |
      | coveredDimensionSpacePoint   | {"language": "gsw"}  |
      | nodeVariantSelectionStrategy | "allSpecializations" |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                    |
      | nodeAggregateId                     | "nody-mc-nodeface"       |
      | dimensionSpacePoint                 | {"language": "mul"}      |
      | newParentNodeAggregateId            | "sir-david-nodenborough" |
      | newSucceedingSiblingNodeAggregateId | null                     |
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

  Scenario: Move a single node in a node aggregate to a new parent after the last of its new siblings
    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                    |
      | nodeAggregateId                     | "nody-mc-nodeface"       |
      | dimensionSpacePoint                 | {"language": "de"}       |
      | newParentNodeAggregateId            | "sir-david-nodenborough" |
      | newSucceedingSiblingNodeAggregateId | null                     |
      | relationDistributionStrategy        | "scatter"                |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"language": "mul"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"language": "mul"}
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings

    When I am in the active content stream of workspace "live" and dimension space point {"language": "de"}
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
      | Key                                 | Value                    |
      | nodeAggregateId                     | "nody-mc-nodeface"       |
      | dimensionSpacePoint                 | {"language": "de"}       |
      | newParentNodeAggregateId            | "sir-david-nodenborough" |
      | newSucceedingSiblingNodeAggregateId | null                     |
      | relationDistributionStrategy        | "gatherSpecializations"  |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"language": "mul"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "esquire/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"language": "mul"}
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings

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

  Scenario: Move a complete node aggregate to a new parent between siblings with different parents in other variants
    Given the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                       |
      | nodeAggregateId                     | "berta-destinode"           |
      | dimensionSpacePoint                 | {"language": "gsw"}         |
      | newParentNodeAggregateId            | "lady-abigail-nodenborough" |
      | newSucceedingSiblingNodeAggregateId | null                        |
      | relationDistributionStrategy        | "scatter"                   |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value                    |
      | nodeAggregateId                     | "nody-mc-nodeface"       |
      | dimensionSpacePoint                 | {"language": "mul"}      |
      | newParentNodeAggregateId            | "sir-david-nodenborough" |
      | newPrecedingSiblingNodeAggregateId  | "anthony-destinode"      |
      | newSucceedingSiblingNodeAggregateId | "berta-destinode"        |
      | relationDistributionStrategy        | "gatherAll"              |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"language": "mul"}
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
    When I am in the active content stream of workspace "live" and dimension space point {"language": "gsw"}
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
      | Key                                 | Value                       |
      | nodeAggregateId                     | "berta-destinode"           |
      | dimensionSpacePoint                 | {"language": "gsw"}         |
      | newParentNodeAggregateId            | "lady-abigail-nodenborough" |
      | newSucceedingSiblingNodeAggregateId | null                        |
      | relationDistributionStrategy        | "scatter"                   |
    And the graph projection is fully up to date

    When the command MoveNodeAggregate is executed with payload:
      | Key                                 | Value               |
      | nodeAggregateId                     | "nody-mc-nodeface"  |
      | dimensionSpacePoint                 | {"language": "mul"} |
      | newPrecedingSiblingNodeAggregateId  | "anthony-destinode" |
      | newSucceedingSiblingNodeAggregateId | "berta-destinode"   |
      | relationDistributionStrategy        | "gatherAll"         |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and dimension space point {"language": "mul"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language": "mul"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                   |
      | cs-identifier;anthony-destinode;{"language": "mul"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                 |
      | cs-identifier;berta-destinode;{"language": "mul"} |
      | cs-identifier;carl-destinode;{"language": "mul"}  |

    When I am in the active content stream of workspace "live" and dimension space point {"language": "gsw"}
    Then I expect node aggregate identifier "nody-mc-nodeface" and node path "document2/child-document-n" to lead to node cs-identifier;nody-mc-nodeface;{"language": "mul"}
    And I expect this node to be a child of node cs-identifier;lady-abigail-nodenborough;{"language": "mul"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                 |
      | cs-identifier;berta-destinode;{"language": "mul"} |

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
    When I am in the active content stream of workspace "live" and dimension space point {"language": "mul"}
    And I expect node aggregate identifier "nody-mc-nodeface-ii" to lead to node cs-identifier;nody-mc-nodeface-ii;{"language": "mul"}
