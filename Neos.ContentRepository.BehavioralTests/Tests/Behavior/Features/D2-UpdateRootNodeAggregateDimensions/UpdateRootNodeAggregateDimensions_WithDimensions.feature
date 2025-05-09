@contentrepository @adapters=DoctrineDBAL
Feature: Update Root Node aggregate dimensions

  I want to update a root node aggregate's dimensions when the dimension config changes.

  Background:
    Given using the following content dimensions:
      | Identifier | Values | Generalizations |
      | language   | fr, de |                 |
    And using the following node types:
    """yaml
    Neos.ContentRepository.Testing:Document: {}
    Neos.ContentRepository.Testing:Root:
      superTypes:
        Neos.ContentRepository:Root: true
    Neos.ContentRepository.Testing:RootWithTethered:
      superTypes:
        Neos.ContentRepository:Root: true
      childNodes:
        tethered:
          type: "Neos.ContentRepository.Testing:Document"
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
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |

  Scenario: Adding a dimension and updating the root node works
    Given I change the content dimensions in content repository "default" to:
      | Identifier | Values     | Generalizations |
      | language   | fr, de, en |                 |

    # in "en", the root node does not exist.
    When I am in dimension space point {"language":"en"}
    Then I expect the subgraph projection to consist of exactly 0 nodes
    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to no node

    And the command UpdateRootNodeAggregateDimensions is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "lady-eleonode-rootford" |

    Then I expect exactly 3 events to be published on stream "ContentStream:cs-identifier"
    # the updated dimension config is persisted in the event stream
    And event at index 2 is of type "RootNodeAggregateDimensionsWereUpdated" with payload:
      | Key                         | Expected                                                |
      | contentStreamId             | "cs-identifier"                                         |
      | nodeAggregateId             | "lady-eleonode-rootford"                                |
      | coveredDimensionSpacePoints | [{"language":"fr"},{"language":"de"},{"language":"en"}] |
    And event metadata at index 1 is:
      | Key | Expected |

    Then I expect the node aggregate "lady-eleonode-rootford" to exist
    And I expect this node aggregate to be classified as "root"
    And I expect this node aggregate to be of type "Neos.ContentRepository:Root"
    And I expect this node aggregate to be unnamed
    And I expect this node aggregate to occupy dimension space points [[]]
    And I expect this node aggregate to cover dimension space points [{"language":"fr"},{"language":"de"},{"language":"en"}]
    And I expect this node aggregate to disable dimension space points []
    And I expect this node aggregate to have no parent node aggregates
    And I expect this node aggregate to have no child node aggregates

    And I expect the graph projection to consist of exactly 1 node
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect this node to be classified as "root"
    And I expect this node to be of type "Neos.ContentRepository:Root"
    And I expect this node to be unnamed
    And I expect this node to have no properties

    When I am in dimension space point {"language":"fr"}
    Then I expect the subgraph projection to consist of exactly 1 node
    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to be classified as "root"
    And I expect this node to have no parent node
    And I expect this node to have no child nodes
    And I expect this node to have no preceding siblings
    And I expect this node to have no succeeding siblings
    And I expect this node to have no references
    And I expect this node to not be referenced

    When I am in dimension space point {"language":"de"}
    Then I expect the subgraph projection to consist of exactly 1 node
    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}

    # now, the root node exists in "en"
    When I am in dimension space point {"language":"en"}
    Then I expect the subgraph projection to consist of exactly 1 node
    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}

  Scenario: Adding a dimension updating the root node, removing dimension, updating the root node, works (dimension gone again)
    Given I change the content dimensions in content repository "default" to:
      | Identifier | Values     | Generalizations |
      | language   | fr, de, en |                 |
    And the command UpdateRootNodeAggregateDimensions is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "lady-eleonode-rootford" |

    # now, the root node exists in "en"
    When I am in dimension space point {"language":"en"}
    Then I expect the subgraph projection to consist of exactly 1 nodes
    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}

    # again, remove "en"
    Given I change the content dimensions in content repository "default" to:
      | Identifier | Values | Generalizations |
      | language   | fr, de |                 |
    And the command UpdateRootNodeAggregateDimensions is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "lady-eleonode-rootford" |

    # now, the root node should not exist anymore in "en"
    When I am in dimension space point {"language":"en"}
    Then I expect the subgraph projection to consist of exactly 0 nodes
    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to no node

  Scenario: Introducing new dimension with new fallback
    Given I change the content dimensions in content repository "default" to:
      | Identifier | Values         | Generalizations |
      | language   | de, fr, dk, se | se -> dk        |

    And the command UpdateRootNodeAggregateDimensions is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "lady-eleonode-rootford" |

    Then I expect exactly 3 events to be published on stream "ContentStream:cs-identifier"
    And event at index 2 is of type "RootNodeAggregateDimensionsWereUpdated" with payload:
      | Key                         | Expected                                                                  |
      | contentStreamId             | "cs-identifier"                                                           |
      | nodeAggregateId             | "lady-eleonode-rootford"                                                  |
      | coveredDimensionSpacePoints | [{"language":"de"},{"language":"fr"},{"language":"dk"},{"language":"se"}] |

    Then I expect the node aggregate "lady-eleonode-rootford" to exist
    And I expect this node aggregate to occupy dimension space points [[]]
    And I expect this node aggregate to cover dimension space points [{"language":"de"},{"language":"fr"},{"language":"dk"},{"language":"se"}]

  Scenario: Removing a dimension with content and update the root node
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                     |
      | nodeAggregateId           | "sir-david-nodenborough"                  |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint | {"language": "de"}                        |
      | parentNodeAggregateId     | "lady-eleonode-rootford"                  |

    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "sir-david-nodenborough" |
      | sourceOrigin    | {"language":"de"}        |
      | targetOrigin    | {"language":"fr"}        |

    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                     |
      | nodeAggregateId           | "davids-son"                              |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint | {"language": "de"}                        |
      | parentNodeAggregateId     | "sir-david-nodenborough"                  |

    And the command CreateNodeVariant is executed with payload:
      | Key             | Value             |
      | nodeAggregateId | "davids-son"      |
      | sourceOrigin    | {"language":"de"} |
      | targetOrigin    | {"language":"fr"} |

    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                     |
      | nodeAggregateId           | "davids-datter"                           |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint | {"language": "fr"}                        |
      | parentNodeAggregateId     | "sir-david-nodenborough"                  |

    Then I expect the node aggregate "lady-eleonode-rootford" to exist
    And I expect this node aggregate to occupy dimension space points [[]]
    And I expect this node aggregate to cover dimension space points [{"language":"fr"},{"language":"de"}]

    Then I expect the node aggregate "sir-david-nodenborough" to exist
    And I expect this node aggregate to occupy dimension space points [{"language":"fr"},{"language":"de"}]
    And I expect this node aggregate to cover dimension space points [{"language":"fr"},{"language":"de"}]

    # remove fr
    Given I change the content dimensions in content repository "default" to:
      | Identifier | Values | Generalizations |
      | language   | de     |                 |

    And the command UpdateRootNodeAggregateDimensions is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "lady-eleonode-rootford" |

    Then I expect the node aggregate "lady-eleonode-rootford" to exist
    And I expect this node aggregate to occupy dimension space points [[]]
    And I expect this node aggregate to cover dimension space points [{"language":"de"}]

    Then I expect the node aggregate "sir-david-nodenborough" to exist
    And I expect this node aggregate to occupy dimension space points [{"language":"de"}]
    And I expect this node aggregate to cover dimension space points [{"language":"de"}]
    Then I expect the node aggregate "davids-son" to exist
    And I expect this node aggregate to occupy dimension space points [{"language":"de"}]
    And I expect this node aggregate to cover dimension space points [{"language":"de"}]
    And I expect the node aggregate "davids-datter" to not exist

  Scenario: Removing a dimension of a root node with tethered nodes and update the root node
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                                | Value                                             |
      | nodeAggregateId                    | "root-zwo"                                        |
      | nodeTypeName                       | "Neos.ContentRepository.Testing:RootWithTethered" |
      | originDimensionSpacePoint          | {}                                                |
      | tetheredDescendantNodeAggregateIds | { "tethered": "nodimus-tetherton"}                |

    Then I expect the node aggregate "root-zwo" to exist
    And I expect this node aggregate to occupy dimension space points [[]]
    And I expect this node aggregate to cover dimension space points [{"language":"fr"},{"language":"de"}]

    Then I expect the node aggregate "nodimus-tetherton" to exist
    And I expect this node aggregate to occupy dimension space points [{"language":"fr"},{"language":"de"}]
    And I expect this node aggregate to cover dimension space points [{"language":"fr"},{"language":"de"}]

    # remove fr
    Given I change the content dimensions in content repository "default" to:
      | Identifier | Values | Generalizations |
      | language   | de     |                 |

    And the command UpdateRootNodeAggregateDimensions is executed with payload:
      | Key             | Value      |
      | nodeAggregateId | "root-zwo" |

    Then I expect the node aggregate "root-zwo" to exist
    And I expect this node aggregate to occupy dimension space points [[]]
    And I expect this node aggregate to cover dimension space points [{"language":"de"}]

    Then I expect the node aggregate "nodimus-tetherton" to exist
    And I expect this node aggregate to occupy dimension space points [{"language":"de"}]
    And I expect this node aggregate to cover dimension space points [{"language":"de"}]

  Scenario: Removing a dimension and promoting its fallback to a root generalisation (where all nodes are varied)
    Given I change the content dimensions in content repository "default" to:
      | Identifier | Values      | Generalizations |
      | language   | fr, de, gsw | gsw->de         |

    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                 |
      | nodeAggregateId           | "root-three"                          |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Root" |
      | originDimensionSpacePoint | {}                                    |

    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                     |
      | nodeAggregateId           | "sir-david-nodenborough"                  |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint | {"language": "de"}                        |
      | parentNodeAggregateId     | "root-three"                              |

    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "sir-david-nodenborough" |
      | sourceOrigin    | {"language":"de"}        |
      | targetOrigin    | {"language":"gsw"}       |

    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                     |
      | nodeAggregateId           | "david-datter"                            |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint | {"language": "gsw"}                       |
      | parentNodeAggregateId     | "sir-david-nodenborough"                  |

    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                     |
      | nodeAggregateId           | "david-son"                               |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint | {"language": "de"}                        |
      | parentNodeAggregateId     | "sir-david-nodenborough"                  |
    And the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "david-son"          |
      | coveredDimensionSpacePoint   | {"language":"gsw"}   |
      | nodeVariantSelectionStrategy | "allSpecializations" |

    Then I expect the node aggregate "root-three" to exist
    And I expect this node aggregate to occupy dimension space points [[]]
    And I expect this node aggregate to cover dimension space points [{"language":"fr"},{"language":"de"},{"language":"gsw"}]

    Then I expect the node aggregate "sir-david-nodenborough" to exist
    And I expect this node aggregate to occupy dimension space points [{"language":"de"},{"language":"gsw"}]
    And I expect this node aggregate to cover dimension space points [{"language":"de"},{"language":"gsw"}]

    Then I expect the node aggregate "david-son" to exist
    And I expect this node aggregate to occupy dimension space points [{"language":"de"}]
    And I expect this node aggregate to cover dimension space points [{"language":"de"}]

    Then I expect the node aggregate "david-datter" to exist
    And I expect this node aggregate to occupy dimension space points [{"language":"gsw"}]
    And I expect this node aggregate to cover dimension space points [{"language":"gsw"}]

    Given I change the content dimensions in content repository "default" to:
      | Identifier | Values  | Generalizations |
      | language   | fr, gsw |                 |

    Then I expect exactly 8 events to be published on stream "ContentStream:cs-identifier"
    And the command UpdateRootNodeAggregateDimensions is executed with payload:
      | Key             | Value        |
      | nodeAggregateId | "root-three" |

    Then I expect exactly 9 events to be published on stream "ContentStream:cs-identifier"
    And event at index 8 is of type "NodeAggregateWasRemoved" with payload:
      | Key                                  | Expected            |
      | contentStreamId                      | "cs-identifier"     |
      | nodeAggregateId                      | "root-three"        |
      | affectedCoveredDimensionSpacePoints  | [{"language":"de"}] |

    Then I expect the node aggregate "root-three" to exist
    And I expect this node aggregate to occupy dimension space points [[]]
    And I expect this node aggregate to cover dimension space points [{"language":"fr"},{"language":"gsw"}]

    Then I expect the node aggregate "sir-david-nodenborough" to exist
    And I expect this node aggregate to occupy dimension space points [{"language":"gsw"}]
    And I expect this node aggregate to cover dimension space points [{"language":"gsw"}]

    Then I expect the node aggregate "david-son" to not exist

    Then I expect the node aggregate "david-datter" to exist
    And I expect this node aggregate to occupy dimension space points [{"language":"gsw"}]
    And I expect this node aggregate to cover dimension space points [{"language":"gsw"}]

  Scenario: Removing a dimension and its fallbacks
    Given I change the content dimensions in content repository "default" to:
      | Identifier | Values      | Generalizations |
      | language   | fr, de, gsw | gsw->de         |

    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                 |
      | nodeAggregateId           | "root-three"                          |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Root" |
      | originDimensionSpacePoint | {}                                    |

    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                     |
      | nodeAggregateId           | "sir-david-nodenborough"                  |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint | {"language": "de"}                        |
      | parentNodeAggregateId     | "root-three"                              |

    And the command CreateNodeVariant is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "sir-david-nodenborough" |
      | sourceOrigin    | {"language":"de"}        |
      | targetOrigin    | {"language":"fr"}        |

    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                     |
      | nodeAggregateId           | "david-datter"                            |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint | {"language": "gsw"}                       |
      | parentNodeAggregateId     | "sir-david-nodenborough"                  |

    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                     |
      | nodeAggregateId           | "david-son"                               |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Document" |
      | originDimensionSpacePoint | {"language": "de"}                        |
      | parentNodeAggregateId     | "sir-david-nodenborough"                  |
    And the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | nodeAggregateId              | "david-son"          |
      | coveredDimensionSpacePoint   | {"language":"gsw"}   |
      | nodeVariantSelectionStrategy | "allSpecializations" |

    Then I expect the node aggregate "root-three" to exist
    And I expect this node aggregate to occupy dimension space points [[]]
    And I expect this node aggregate to cover dimension space points [{"language":"fr"},{"language":"de"},{"language":"gsw"}]

    Then I expect the node aggregate "sir-david-nodenborough" to exist
    And I expect this node aggregate to occupy dimension space points [{"language":"de"},{"language":"fr"}]
    And I expect this node aggregate to cover dimension space points [{"language":"de"},{"language":"gsw"},{"language":"fr"}]

    Then I expect the node aggregate "david-son" to exist
    And I expect this node aggregate to occupy dimension space points [{"language":"de"}]
    And I expect this node aggregate to cover dimension space points [{"language":"de"}]

    Then I expect the node aggregate "david-datter" to exist
    And I expect this node aggregate to occupy dimension space points [{"language":"gsw"}]
    And I expect this node aggregate to cover dimension space points [{"language":"gsw"}]

    Given I change the content dimensions in content repository "default" to:
      | Identifier | Values | Generalizations |
      | language   | fr     |                 |

    Then I expect exactly 8 events to be published on stream "ContentStream:cs-identifier"
    And the command UpdateRootNodeAggregateDimensions is executed with payload:
      | Key             | Value        |
      | nodeAggregateId | "root-three" |

    Then I expect exactly 9 events to be published on stream "ContentStream:cs-identifier"
    And event at index 8 is of type "NodeAggregateWasRemoved" with payload:
      | Key                                  | Expected                               |
      | contentStreamId                      | "cs-identifier"                        |
      | nodeAggregateId                      | "root-three"                           |
      | affectedCoveredDimensionSpacePoints  | [{"language":"de"},{"language":"gsw"}] |

    And I expect the node aggregate "root-three" to exist
    And I expect this node aggregate to occupy dimension space points [[]]
    And I expect this node aggregate to cover dimension space points [{"language":"fr"}]

    And I expect the node aggregate "sir-david-nodenborough" to exist
    And I expect this node aggregate to occupy dimension space points [{"language":"fr"}]
    And I expect this node aggregate to cover dimension space points [{"language":"fr"}]

    And I expect the node aggregate "david-son" to not exist

    And I expect the node aggregate "david-datter" to not exist

    And I expect the graph projection to consist of exactly 3 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;[] to exist in the content graph
    And I expect a node identified by cs-identifier;root-three;[] to exist in the content graph
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{"language":"fr"} to exist in the content graph

  Scenario: Adding a dimension updating the root node keeps subtree tags
    Given I change the content dimensions in content repository "default" to:
      | Identifier | Values      | Generalizations |
      | language   | fr, de, en |                 |

    When I am in dimension space point {"language":"de"}
    And the command DisableNodeAggregate is executed with payload:
      | Key                          | Value                    |
      | nodeAggregateId              | "lady-eleonode-rootford" |
      | nodeVariantSelectionStrategy | "allVariants"            |

    And I expect the node aggregate "lady-eleonode-rootford" to exist
    And I expect this node aggregate to cover dimension space points [{"language":"fr"},{"language":"de"}]
    And I expect this node aggregate to disable dimension space points [{"language":"fr"},{"language":"de"}]

    And the command UpdateRootNodeAggregateDimensions is executed with payload:
      | Key             | Value                    |
      | nodeAggregateId | "lady-eleonode-rootford" |

    And I expect the node aggregate "lady-eleonode-rootford" to exist
    And I expect this node aggregate to cover dimension space points [{"language":"fr"},{"language":"de"},{"language":"en"}]
    And I expect this node aggregate to disable dimension space points [{"language":"fr"},{"language":"de"}]
