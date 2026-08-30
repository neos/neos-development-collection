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

    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                         | Value                                     |
      | workspaceName               | "live"                                    |
      | nodeAggregateId             | "nody-mc-nodeface"                        |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint   | {"language":"de"}                         |
      | parentNodeAggregateId       | "lady-eleonode-rootford"                  |

    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                         | Value                                     |
      | workspaceName               | "live"                                    |
      | nodeAggregateId             | "node-wan-kenody"                         |
      | nodeTypeName                | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint   | {"language":"de"}                         |
      | parentNodeAggregateId       | "nody-mc-nodeface"                        |

    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                      |
      | nodeAggregateId              | "node-wan-kenody"          |
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

  Scenario: Simple node aggregate variant is recursively created in another dimension
    When I am in workspace "live" and dimension space point {"language":"de"}
    When dimension variants are created recursively in "default" content repository with payload:
      | Key                        | Value                     |
      | nodeAggregateId            | "sir-david-nodenborough"  |
      | targetDimensionSpacePoint  | {"language":"en"}         |
      | withContent                | false                     |

    When I am in workspace "live" and dimension space point {"language":"en"}
    Then I expect node aggregate identifier "sir-david-nodenborough" to lead to node cs-identifier;sir-david-nodenborough;{"language":"en"}
    And I expect this node to be exactly explicitly tagged "disabled"

  Scenario: Simple node aggregate variant is recursively created in another dimension with content
    When I am in workspace "live" and dimension space point {"language":"de"}
    When dimension variants are created recursively in "default" content repository with payload:
      | Key                        | Value               |
      | nodeAggregateId            | "nody-mc-nodeface"  |
      | targetDimensionSpacePoint  | {"language":"en"}   |
      | withContent                | true                |

    When I am in workspace "live" and dimension space point {"language":"en"}
    Then I expect node aggregate identifier "node-wan-kenody" to lead to node cs-identifier;node-wan-kenody;{"language":"en"}
    And I expect this node to be exactly explicitly tagged "disabled"