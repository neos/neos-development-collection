@contentrepository @fixtures @adapters=Postgres
Feature: Restore NodeAggregate coverage

  As a user of the CR I want to be able to restore coverage of a NodeAggregate or parts of it, respecting disabled state

  Background:
    Given I have the following content dimensions:
      | Identifier | Values      | Generalizations |
      | language   | en, de, gsw | gsw->de->en     |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:Document': []
    """
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | workspaceTitle       | "Live"               |
      | workspaceDescription | "The live workspace" |
      | newContentStreamId   | "cs-identifier"      |
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and dimension space point {"language":"en"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId  | nodeTypeName                            | parentNodeAggregateId  | nodeName     | tetheredDescendantNodeAggregateIds |
      | nody-mc-nodeface | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford | document     | {}                                 |
      | the-great-nodini | Neos.ContentRepository.Testing:Document | nody-mc-nodeface       | court-magician | {}                                 |
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value              |
      | nodeAggregateId              | "nody-mc-nodeface" |
      | nodeVariantSelectionStrategy | "allVariants"      |
    And the graph projection is fully up to date
    And the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "nody-mc-nodeface"   |
      | coveredDimensionSpacePoint   | {"language":"de"}    |
      | nodeVariantSelectionStrategy | "allSpecializations" |
    And the graph projection is fully up to date

  Scenario: Restore node aggregate coverage without specializations and not recursive
    When the command RestoreNodeAggregateCoverage is executed with payload:
      | Key                        | Value                     |
      | nodeAggregateId            | "nody-mc-nodeface"        |
      | dimensionSpacePointToCover | {"language":"de"}         |
      | withSpecializations        | false                     |
      | recursionMode              | "onlyTetheredDescendants" |
    Then I expect exactly 7 events to be published on stream with prefix "ContentStream:cs-identifier"
    And event at index 6 is of type "NodeAggregateCoverageWasRestored" with payload:
      | Key                                 | Expected                  |
      | contentStreamId                     | "cs-identifier"           |
      | nodeAggregateId                     | "nody-mc-nodeface"        |
      | sourceDimensionSpacePoint           | {"language":"en"}         |
      | affectedCoveredDimensionSpacePoints | [{"language":"de"}]       |
      | recursionMode                       | "onlyTetheredDescendants" |
    When the graph projection is fully up to date
    Then I expect the graph projection to consist of exactly 3 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;the-great-nodini;{"language":"en"} to exist in the content graph
    And I expect the node aggregate "nody-mc-nodeface" to exist
    And I expect this node aggregate to cover dimension space points [{"language":"en"},{"language":"de"}]
    And I expect the node aggregate "the-great-nodini" to exist
    And I expect this node aggregate to cover dimension space points [{"language":"en"}]

    # Check the selected variant
    When I am in dimension space point {"language":"de"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no child nodes
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | nodeAggregateId        |
      | 0     | lady-eleonode-rootford |

    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to no node
    And I expect node aggregate identifier "the-great-nodini" and node path "document/court-magician" to lead to no node

    # Check the specialization
    When I am in dimension space point {"language":"gsw"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no child nodes
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | nodeAggregateId        |
      | 0     | lady-eleonode-rootford |

    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to no node
    And I expect node aggregate identifier "the-great-nodini" and node path "document/court-magician" to lead to no node

    # Check the selected variant without enabled checks
    When I am in dimension space point {"language":"de"}
    And VisibilityConstraints are set to "withoutRestrictions"
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                |
      | document | cs-identifier;nody-mc-nodeface;{"language":"en"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | nodeAggregateId        |
      | 0     | lady-eleonode-rootford |
      | 1     | nody-mc-nodeface       |

    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to node cs-identifier;nody-mc-nodeface;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no child nodes
    And I expect node aggregate identifier "the-great-nodini" and node path "document/court-magician" to lead to no node

    # Check the specialization without enabled checks
    When I am in dimension space point {"language":"gsw"}
    And VisibilityConstraints are set to "withoutRestrictions"
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no child nodes
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | nodeAggregateId        |
      | 0     | lady-eleonode-rootford |

    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to no node
    And I expect node aggregate identifier "the-great-nodini" and node path "document/court-magician" to lead to no node

  Scenario: Restore node aggregate coverage with specializations and not recursive
    When the command RestoreNodeAggregateCoverage is executed with payload:
      | Key                        | Value                     |
      | nodeAggregateId            | "nody-mc-nodeface"        |
      | dimensionSpacePointToCover | {"language":"de"}         |
      | withSpecializations        | true                      |
      | recursionMode              | "onlyTetheredDescendants" |
    Then I expect exactly 7 events to be published on stream with prefix "ContentStream:cs-identifier"
    And event at index 6 is of type "NodeAggregateCoverageWasRestored" with payload:
      | Key                                 | Expected                               |
      | contentStreamId                     | "cs-identifier"                        |
      | nodeAggregateId                     | "nody-mc-nodeface"                     |
      | sourceDimensionSpacePoint           | {"language":"en"}                      |
      | affectedCoveredDimensionSpacePoints | [{"language":"de"},{"language":"gsw"}] |
      | recursionMode                       | "onlyTetheredDescendants"              |
    When the graph projection is fully up to date
    Then I expect the graph projection to consist of exactly 3 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;the-great-nodini;{"language":"en"} to exist in the content graph
    And I expect the node aggregate "nody-mc-nodeface" to exist
    And I expect this node aggregate to cover dimension space points [{"language":"en"},{"language":"de"},{"language":"gsw"}]
    And I expect the node aggregate "the-great-nodini" to exist
    And I expect this node aggregate to cover dimension space points [{"language":"en"}]

    # Check the selected variant
    When I am in dimension space point {"language":"de"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no child nodes
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | nodeAggregateId        |
      | 0     | lady-eleonode-rootford |

    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to no node
    And I expect node aggregate identifier "the-great-nodini" and node path "document/court-magician" to lead to no node

    # Check the specialization
    When I am in dimension space point {"language":"gsw"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no child nodes
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | nodeAggregateId        |
      | 0     | lady-eleonode-rootford |

    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to no node
    And I expect node aggregate identifier "the-great-nodini" and node path "document/court-magician" to lead to no node

    # Check the selected variant without enabled checks
    When I am in dimension space point {"language":"de"}
    And VisibilityConstraints are set to "withoutRestrictions"
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                |
      | document | cs-identifier;nody-mc-nodeface;{"language":"en"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | nodeAggregateId        |
      | 0     | lady-eleonode-rootford |
      | 1     | nody-mc-nodeface       |

    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to node cs-identifier;nody-mc-nodeface;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no child nodes
    And I expect node aggregate identifier "the-great-nodini" and node path "document/court-magician" to lead to no node

    # Check the specialization without enabled checks
    When I am in dimension space point {"language":"gsw"}
    And VisibilityConstraints are set to "withoutRestrictions"
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                |
      | document | cs-identifier;nody-mc-nodeface;{"language":"en"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | nodeAggregateId        |
      | 0     | lady-eleonode-rootford |
      | 1     | nody-mc-nodeface       |

    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to node cs-identifier;nody-mc-nodeface;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no child nodes
    And I expect node aggregate identifier "the-great-nodini" and node path "document/court-magician" to lead to no node

  Scenario: Restore node aggregate coverage without specializations but recursive
    When the command RestoreNodeAggregateCoverage is executed with payload:
      | Key                        | Value                     |
      | nodeAggregateId            | "nody-mc-nodeface"        |
      | dimensionSpacePointToCover | {"language":"de"}         |
      | withSpecializations        | false                     |
      | recursionMode              | "allDescendants" |
    Then I expect exactly 7 events to be published on stream with prefix "ContentStream:cs-identifier"
    And event at index 6 is of type "NodeAggregateCoverageWasRestored" with payload:
      | Key                                 | Expected                  |
      | contentStreamId                     | "cs-identifier"           |
      | nodeAggregateId                     | "nody-mc-nodeface"        |
      | sourceDimensionSpacePoint           | {"language":"en"}         |
      | affectedCoveredDimensionSpacePoints | [{"language":"de"}]       |
      | recursionMode                       | "allDescendants" |
    When the graph projection is fully up to date
    Then I expect the graph projection to consist of exactly 3 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;the-great-nodini;{"language":"en"} to exist in the content graph
    And I expect the node aggregate "nody-mc-nodeface" to exist
    And I expect this node aggregate to cover dimension space points [{"language":"en"},{"language":"de"}]
    And I expect the node aggregate "the-great-nodini" to exist
    And I expect this node aggregate to cover dimension space points [{"language":"en"},{"language":"de"}]

    # Check the selected variant
    When I am in dimension space point {"language":"de"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no child nodes
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | nodeAggregateId        |
      | 0     | lady-eleonode-rootford |

    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to no node
    And I expect node aggregate identifier "the-great-nodini" and node path "document/court-magician" to lead to no node

    # Check the specialization
    When I am in dimension space point {"language":"gsw"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no child nodes
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | nodeAggregateId        |
      | 0     | lady-eleonode-rootford |

    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to no node
    And I expect node aggregate identifier "the-great-nodini" and node path "document/court-magician" to lead to no node

    # Check the selected variant without enabled checks
    When I am in dimension space point {"language":"de"}
    And VisibilityConstraints are set to "withoutRestrictions"
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                |
      | document | cs-identifier;nody-mc-nodeface;{"language":"en"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | nodeAggregateId        |
      | 0     | lady-eleonode-rootford |
      | 1     | nody-mc-nodeface       |
      | 2     | the-great-nodini       |

    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to node cs-identifier;nody-mc-nodeface;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                |
      | court-magician | cs-identifier;the-great-nodini;{"language":"en"} |
    And I expect node aggregate identifier "the-great-nodini" and node path "document/court-magician" to lead to node cs-identifier;the-great-nodini;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"language":"en"}
    And I expect this node to have no child nodes

    # Check the specialization without enabled checks
    When I am in dimension space point {"language":"gsw"}
    And VisibilityConstraints are set to "withoutRestrictions"
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no child nodes
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | nodeAggregateId        |
      | 0     | lady-eleonode-rootford |

    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to no node
    And I expect node aggregate identifier "the-great-nodini" and node path "document/court-magician" to lead to no node

  Scenario: Restore node aggregate coverage with specializations and recursive
    When the command RestoreNodeAggregateCoverage is executed with payload:
      | Key                        | Value                     |
      | nodeAggregateId            | "nody-mc-nodeface"        |
      | dimensionSpacePointToCover | {"language":"de"}         |
      | withSpecializations        | true                     |
      | recursionMode              | "allDescendants" |
    Then I expect exactly 7 events to be published on stream with prefix "ContentStream:cs-identifier"
    And event at index 6 is of type "NodeAggregateCoverageWasRestored" with payload:
      | Key                                 | Expected                  |
      | contentStreamId                     | "cs-identifier"           |
      | nodeAggregateId                     | "nody-mc-nodeface"        |
      | sourceDimensionSpacePoint           | {"language":"en"}         |
      | affectedCoveredDimensionSpacePoints | [{"language":"de"},{"language":"gsw"}]       |
      | recursionMode                       | "allDescendants" |
    When the graph projection is fully up to date
    Then I expect the graph projection to consist of exactly 3 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;the-great-nodini;{"language":"en"} to exist in the content graph
    And I expect the node aggregate "nody-mc-nodeface" to exist
    And I expect this node aggregate to cover dimension space points [{"language":"en"},{"language":"de"},{"language":"gsw"}]
    And I expect the node aggregate "the-great-nodini" to exist
    And I expect this node aggregate to cover dimension space points [{"language":"en"},{"language":"de"},{"language":"gsw"}]

    # Check the selected variant
    When I am in dimension space point {"language":"de"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no child nodes
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | nodeAggregateId        |
      | 0     | lady-eleonode-rootford |

    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to no node
    And I expect node aggregate identifier "the-great-nodini" and node path "document/court-magician" to lead to no node

    # Check the specialization
    When I am in dimension space point {"language":"gsw"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no child nodes
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | nodeAggregateId        |
      | 0     | lady-eleonode-rootford |

    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to no node
    And I expect node aggregate identifier "the-great-nodini" and node path "document/court-magician" to lead to no node

    # Check the selected variant without enabled checks
    When I am in dimension space point {"language":"de"}
    And VisibilityConstraints are set to "withoutRestrictions"
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                |
      | document | cs-identifier;nody-mc-nodeface;{"language":"en"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | nodeAggregateId        |
      | 0     | lady-eleonode-rootford |
      | 1     | nody-mc-nodeface       |
      | 2     | the-great-nodini       |

    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to node cs-identifier;nody-mc-nodeface;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                |
      | court-magician | cs-identifier;the-great-nodini;{"language":"en"} |
    And I expect node aggregate identifier "the-great-nodini" and node path "document/court-magician" to lead to node cs-identifier;the-great-nodini;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"language":"en"}
    And I expect this node to have no child nodes

    # Check the specialization without enabled checks
    When I am in dimension space point {"language":"gsw"}
    And VisibilityConstraints are set to "withoutRestrictions"
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                |
      | document | cs-identifier;nody-mc-nodeface;{"language":"en"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | nodeAggregateId        |
      | 0     | lady-eleonode-rootford |
      | 1     | nody-mc-nodeface       |
      | 2     | the-great-nodini       |

    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to node cs-identifier;nody-mc-nodeface;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                |
      | court-magician | cs-identifier;the-great-nodini;{"language":"en"} |
    And I expect node aggregate identifier "the-great-nodini" and node path "document/court-magician" to lead to node cs-identifier;the-great-nodini;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"language":"en"}
    And I expect this node to have no child nodes
