Feature: Copy nodes (without dimensions)

  Background:
    Given using no content dimensions
    Given using the following content dimensions:
      | Identifier | Values | Generalizations |
      | language   | de, en |                 |
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Document': {}
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |

    And I am in workspace "live" and dimension space point {"language":"de"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |

    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                         | Value                                     |
      | workspaceName               | "live"                                    |
      | nodeAggregateId             | "sir-david-nodenborough"                  |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint   | {"language":"de"}                         |
      | parentNodeAggregateId       | "lady-eleonode-rootford"                  |

    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                      |
      | nodeAggregateId              | "sir-david-nodenborough"   |
      | coveredDimensionSpacePoint   | {"language":"de"}          |
      | nodeVariantSelectionStrategy | "allSpecializations"       |

  Scenario: Simple node aggregate is copied across dimmensions
    When I am in workspace "live" and dimension space point {"language":"de"}
    When copy nodes recursively is executed with payload:
      | Key                                    | Value                                                     |
      | sourceDimensionSpacePoint              | {"language":"de"}                                         |
      | sourceNodeAggregateId                  | "sir-david-nodenborough"                                  |
      | targetDimensionSpacePoint              | {"language":"en"}                                         |
      | targetParentNodeAggregateId            | "lady-eleonode-rootford"                                  |
      | targetSucceedingSiblingnodeAggregateId | null                                                      |
      | nodeAggregateIdMapping                 | {"sir-david-nodenborough": "sir-david-nodenborough-copy"} |

    When I am in workspace "live" and dimension space point {"language":"en"}
    Then I expect node aggregate identifier "sir-david-nodenborough-copy" to lead to node cs-identifier;sir-david-nodenborough-copy;{"language":"en"}
    And I expect this node to be exactly explicitly tagged "disabled"