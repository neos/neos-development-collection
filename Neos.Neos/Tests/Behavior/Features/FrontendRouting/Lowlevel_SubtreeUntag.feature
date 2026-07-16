Feature:

  Background:
    Given using the following content dimensions:
      | Identifier | Values                      | Generalizations                      |
      | example    | general, source, spec, peer | spec->source->general, peer->general |
    And using the following node types:
    """yaml
    'Neos.Neos:Document': {}
    'Neos.ContentRepository.Testing:Document':
      superTypes:
        'Neos.Neos:Document': true
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live"
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "root"                        |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | nodeTypeName                            | parentNodeAggregateId | nodeName | originDimensionSpacePoint |
      | a               | Neos.ContentRepository.Testing:Document | root                  | a        | {"example":"general"}     |
      | a1              | Neos.ContentRepository.Testing:Document | a                     | a1       | {"example":"general"}     |
      | a1a             | Neos.ContentRepository.Testing:Document | a1                    | a1a      | {"example":"general"}     |

  Scenario Outline: Untag "<subtreeTag>" with greater variant selection (allVariants)
    And the command TagSubtree is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "a"                  |
      | coveredDimensionSpacePoint   | {"example":"source"} |
      | nodeVariantSelectionStrategy | "allSpecializations" |
      | tag                          | "<subtreeTag>"       |

    When I am in dimension space point {"example":"source"}
    Then I expect node aggregate identifier "a" to lead to node cs-identifier;a;{"example":"general"}
    And I expect this node to have the following subtree with tags:
    """
    a (<subtreeTag>*)
     a1 (<subtreeTag>)
      a1a (<subtreeTag>)
    """

    When I am in dimension space point {"example":"peer"}
    Then I expect node aggregate identifier "a" to lead to node cs-identifier;a;{"example":"general"}
    And I expect this node to have the following subtree with tags:
    """
    a
     a1
      a1a
    """

    Then I expect the documenturipath table to contain exactly:
      | nodeaggregateid | dimensionspacepointhash   | <subtreeTagColumn> |
      | "root"          | hash{"example":"general"} | 0                  |
      | "root"          | hash{"example":"source"}  | 0                  |
      | "root"          | hash{"example":"spec"}    | 0                  |
      | "root"          | hash{"example":"peer"}    | 0                  |
      | "a"             | hash{"example":"general"} | 0                  |
      | "a"             | hash{"example":"source"}  | 1                  |
      | "a"             | hash{"example":"spec"}    | 1                  |
      | "a"             | hash{"example":"peer"}    | 0                  |
      | "a1"            | hash{"example":"general"} | 0                  |
      | "a1"            | hash{"example":"source"}  | 1                  |
      | "a1"            | hash{"example":"spec"}    | 1                  |
      | "a1"            | hash{"example":"peer"}    | 0                  |
      | "a1a"           | hash{"example":"general"} | 0                  |
      | "a1a"           | hash{"example":"source"}  | 1                  |
      | "a1a"           | hash{"example":"spec"}    | 1                  |
      | "a1a"           | hash{"example":"peer"}    | 0                  |

    When the command UntagSubtree is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "a"                  |
      | coveredDimensionSpacePoint   | {"example":"source"} |
      | nodeVariantSelectionStrategy | "allVariants"        |
      | tag                          | "<subtreeTag>"       |

    When I am in dimension space point {"example":"source"}
    Then I expect node aggregate identifier "a" to lead to node cs-identifier;a;{"example":"general"}
    And I expect this node to have the following subtree with tags:
    """
    a
     a1
      a1a
    """

    When I am in dimension space point {"example":"peer"}
    Then I expect node aggregate identifier "a" to lead to node cs-identifier;a;{"example":"general"}
    And I expect this node to have the following subtree with tags:
    """
    a
     a1
      a1a
    """

    Then I expect the documenturipath table to contain exactly:
      | nodeaggregateid | dimensionspacepointhash   | <subtreeTagColumn> |
      | "root"          | hash{"example":"general"} | 0                  |
      | "root"          | hash{"example":"source"}  | 0                  |
      | "root"          | hash{"example":"spec"}    | 0                  |
      | "root"          | hash{"example":"peer"}    | 0                  |
      | "a"             | hash{"example":"general"} | 0                  |
      | "a"             | hash{"example":"source"}  | 0                  |
      | "a"             | hash{"example":"spec"}    | 0                  |
      | "a"             | hash{"example":"peer"}    | 0                  |
      | "a1"            | hash{"example":"general"} | 0                  |
      | "a1"            | hash{"example":"source"}  | 0                  |
      | "a1"            | hash{"example":"spec"}    | 0                  |
      | "a1"            | hash{"example":"peer"}    | 0                  |
      | "a1a"           | hash{"example":"general"} | 0                  |
      | "a1a"           | hash{"example":"source"}  | 0                  |
      | "a1a"           | hash{"example":"spec"}    | 0                  |
      | "a1a"           | hash{"example":"peer"}    | 0                  |

    Examples:
      | subtreeTag | subtreeTagColumn |
      | disabled   | disabled         |
      | removed    | removed          |

  Scenario: Untag child node with greater variant selection (allVariants)
    And the command TagSubtree is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "a"                  |
      | coveredDimensionSpacePoint   | {"example":"source"} |
      | nodeVariantSelectionStrategy | "allSpecializations" |
      | tag                          | "disabled"           |

    And the command TagSubtree is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "a1"                 |
      | coveredDimensionSpacePoint   | {"example":"peer"}   |
      | nodeVariantSelectionStrategy | "allSpecializations" |
      | tag                          | "disabled"           |

    When I am in dimension space point {"example":"source"}
    Then I expect node aggregate identifier "a" to lead to node cs-identifier;a;{"example":"general"}
    And I expect this node to have the following subtree with tags:
    """
    a (disabled*)
     a1 (disabled)
      a1a (disabled)
    """

    When I am in dimension space point {"example":"peer"}
    Then I expect node aggregate identifier "a" to lead to node cs-identifier;a;{"example":"general"}
    And I expect this node to have the following subtree with tags:
    """
    a
     a1 (disabled*)
      a1a (disabled)
    """

    Then I expect the documenturipath table to contain exactly:
      | nodeaggregateid | dimensionspacepointhash   | disabled |
      | "root"          | hash{"example":"general"} | 0        |
      | "root"          | hash{"example":"source"}  | 0        |
      | "root"          | hash{"example":"spec"}    | 0        |
      | "root"          | hash{"example":"peer"}    | 0        |
      | "a"             | hash{"example":"general"} | 0        |
      | "a"             | hash{"example":"source"}  | 1        |
      | "a"             | hash{"example":"spec"}    | 1        |
      | "a"             | hash{"example":"peer"}    | 0        |
      | "a1"            | hash{"example":"general"} | 0        |
      | "a1"            | hash{"example":"source"}  | 1        |
      | "a1"            | hash{"example":"spec"}    | 1        |
      | "a1"            | hash{"example":"peer"}    | 1        |
      | "a1a"           | hash{"example":"general"} | 0        |
      | "a1a"           | hash{"example":"source"}  | 1        |
      | "a1a"           | hash{"example":"spec"}    | 1        |
      | "a1a"           | hash{"example":"peer"}    | 1        |

    When the command UntagSubtree is executed with payload:
      | Key                          | Value              |
      | nodeAggregateId              | "a1"               |
      | coveredDimensionSpacePoint   | {"example":"peer"} |
      | nodeVariantSelectionStrategy | "allVariants"      |
      | tag                          | "disabled"         |

    When I am in dimension space point {"example":"source"}
    Then I expect node aggregate identifier "a" to lead to node cs-identifier;a;{"example":"general"}
    And I expect this node to have the following subtree with tags:
    """
    a (disabled*)
     a1 (disabled)
      a1a (disabled)
    """

    When I am in dimension space point {"example":"peer"}
    Then I expect node aggregate identifier "a" to lead to node cs-identifier;a;{"example":"general"}
    And I expect this node to have the following subtree with tags:
    """
    a
     a1
      a1a
    """

    Then I expect the documenturipath table to contain exactly:
      | nodeaggregateid | dimensionspacepointhash   | disabled |
      | "root"          | hash{"example":"general"} | 0        |
      | "root"          | hash{"example":"source"}  | 0        |
      | "root"          | hash{"example":"spec"}    | 0        |
      | "root"          | hash{"example":"peer"}    | 0        |
      | "a"             | hash{"example":"general"} | 0        |
      | "a"             | hash{"example":"source"}  | 1        |
      | "a"             | hash{"example":"spec"}    | 1        |
      | "a"             | hash{"example":"peer"}    | 0        |
      | "a1"            | hash{"example":"general"} | 0        |
      | "a1"            | hash{"example":"source"}  | 1        |
      | "a1"            | hash{"example":"spec"}    | 1        |
      | "a1"            | hash{"example":"peer"}    | 0        |
      | "a1a"           | hash{"example":"general"} | 0        |
      | "a1a"           | hash{"example":"source"}  | 1        |
      | "a1a"           | hash{"example":"spec"}    | 1        |
      | "a1a"           | hash{"example":"peer"}    | 0        |

  Scenario: Untag a node first in spezialisation then in source
    And the command TagSubtree is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "a"                  |
      | coveredDimensionSpacePoint   | {"example":"source"} |
      | nodeVariantSelectionStrategy | "allSpecializations" |
      | tag                          | "disabled"           |

    When I am in dimension space point {"example":"source"}
    Then I expect node aggregate identifier "a" to lead to node cs-identifier;a;{"example":"general"}
    And I expect this node to have the following subtree with tags:
    """
    a (disabled*)
     a1 (disabled)
      a1a (disabled)
    """

    When I am in dimension space point {"example":"spec"}
    Then I expect node aggregate identifier "a" to lead to node cs-identifier;a;{"example":"general"}
    And I expect this node to have the following subtree with tags:
    """
    a (disabled*)
     a1 (disabled)
      a1a (disabled)
    """

    When I am in dimension space point {"example":"peer"}
    Then I expect node aggregate identifier "a" to lead to node cs-identifier;a;{"example":"general"}
    And I expect this node to have the following subtree with tags:
    """
    a
     a1
      a1a
    """

    Then I expect the documenturipath table to contain exactly:
      | nodeaggregateid | dimensionspacepointhash   | disabled |
      | "root"          | hash{"example":"general"} | 0        |
      | "root"          | hash{"example":"source"}  | 0        |
      | "root"          | hash{"example":"spec"}    | 0        |
      | "root"          | hash{"example":"peer"}    | 0        |
      | "a"             | hash{"example":"general"} | 0        |
      | "a"             | hash{"example":"source"}  | 1        |
      | "a"             | hash{"example":"spec"}    | 1        |
      | "a"             | hash{"example":"peer"}    | 0        |
      | "a1"            | hash{"example":"general"} | 0        |
      | "a1"            | hash{"example":"source"}  | 1        |
      | "a1"            | hash{"example":"spec"}    | 1        |
      | "a1"            | hash{"example":"peer"}    | 0        |
      | "a1a"           | hash{"example":"general"} | 0        |
      | "a1a"           | hash{"example":"source"}  | 1        |
      | "a1a"           | hash{"example":"spec"}    | 1        |
      | "a1a"           | hash{"example":"peer"}    | 0        |

    When the command UntagSubtree is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "a"                  |
      | coveredDimensionSpacePoint   | {"example":"spec"}   |
      | nodeVariantSelectionStrategy | "allSpecializations" |
      | tag                          | "disabled"           |

    When I am in dimension space point {"example":"source"}
    Then I expect node aggregate identifier "a" to lead to node cs-identifier;a;{"example":"general"}
    And I expect this node to have the following subtree with tags:
    """
    a (disabled*)
     a1 (disabled)
      a1a (disabled)
    """

    When I am in dimension space point {"example":"spec"}
    Then I expect node aggregate identifier "a" to lead to node cs-identifier;a;{"example":"general"}
    And I expect this node to have the following subtree with tags:
    """
    a
     a1
      a1a
    """

    When I am in dimension space point {"example":"peer"}
    Then I expect node aggregate identifier "a" to lead to node cs-identifier;a;{"example":"general"}
    And I expect this node to have the following subtree with tags:
    """
    a
     a1
      a1a
    """

    Then I expect the documenturipath table to contain exactly:
      | nodeaggregateid | dimensionspacepointhash   | disabled |
      | "root"          | hash{"example":"general"} | 0        |
      | "root"          | hash{"example":"source"}  | 0        |
      | "root"          | hash{"example":"spec"}    | 0        |
      | "root"          | hash{"example":"peer"}    | 0        |
      | "a"             | hash{"example":"general"} | 0        |
      | "a"             | hash{"example":"source"}  | 1        |
      # untagged
      | "a"             | hash{"example":"spec"}    | 0        |
      | "a"             | hash{"example":"peer"}    | 0        |
      | "a1"            | hash{"example":"general"} | 0        |
      | "a1"            | hash{"example":"source"}  | 1        |
      # untagged
      | "a1"            | hash{"example":"spec"}    | 0        |
      | "a1"            | hash{"example":"peer"}    | 0        |
      | "a1a"           | hash{"example":"general"} | 0        |
      | "a1a"           | hash{"example":"source"}  | 1        |
      # untagged
      | "a1a"           | hash{"example":"spec"}    | 0        |
      | "a1a"           | hash{"example":"peer"}    | 0        |

    When the command UntagSubtree is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "a"                  |
      | coveredDimensionSpacePoint   | {"example":"source"} |
      | nodeVariantSelectionStrategy | "allSpecializations" |
      | tag                          | "disabled"           |

    When I am in dimension space point {"example":"source"}
    Then I expect node aggregate identifier "a" to lead to node cs-identifier;a;{"example":"general"}
    And I expect this node to have the following subtree with tags:
    """
    a
     a1
      a1a
    """

    When I am in dimension space point {"example":"spec"}
    Then I expect node aggregate identifier "a" to lead to node cs-identifier;a;{"example":"general"}
    And I expect this node to have the following subtree with tags:
    """
    a
     a1
      a1a
    """

    When I am in dimension space point {"example":"peer"}
    Then I expect node aggregate identifier "a" to lead to node cs-identifier;a;{"example":"general"}
    And I expect this node to have the following subtree with tags:
    """
    a
     a1
      a1a
    """

    Then I expect the documenturipath table to contain exactly:
      | nodeaggregateid | dimensionspacepointhash   | disabled |
      | "root"          | hash{"example":"general"} | 0        |
      | "root"          | hash{"example":"source"}  | 0        |
      | "root"          | hash{"example":"spec"}    | 0        |
      | "root"          | hash{"example":"peer"}    | 0        |
      | "a"             | hash{"example":"general"} | 0        |
      | "a"             | hash{"example":"source"}  | 0        |
      | "a"             | hash{"example":"spec"}    | 0        |
      | "a"             | hash{"example":"peer"}    | 0        |
      | "a1"            | hash{"example":"general"} | 0        |
      | "a1"            | hash{"example":"source"}  | 0        |
      | "a1"            | hash{"example":"spec"}    | 0        |
      | "a1"            | hash{"example":"peer"}    | 0        |
      | "a1a"           | hash{"example":"general"} | 0        |
      | "a1a"           | hash{"example":"source"}  | 0        |
      | "a1a"           | hash{"example":"spec"}    | 0        |
      | "a1a"           | hash{"example":"peer"}    | 0        |
