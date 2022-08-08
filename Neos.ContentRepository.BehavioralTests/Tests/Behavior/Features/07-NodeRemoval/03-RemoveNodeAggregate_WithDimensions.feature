@fixtures @adapters=DoctrineDBAL,Postgres
Feature: Remove NodeAggregate

  As a user of the CR I want to be able to remove a NodeAggregate or parts of it.

  These are the test cases without dimensions being involved

  Background:
    Given I have the following content dimensions:
      | Identifier | Values          | Generalizations     |
      | language   | en, de, gsw, fr | gsw->de->en, fr->en |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': {}
    'Neos.ContentRepository.Testing:Document':
      properties:
        references:
          type: references
    """
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value                |
      | workspaceName              | "live"               |
      | workspaceTitle             | "Live"               |
      | workspaceDescription       | "The live workspace" |
      | newContentStreamIdentifier | "cs-identifier"      |
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and dimension space point {"language":"en"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                     | Value                         |
      | nodeAggregateIdentifier | "lady-eleonode-rootford"      |
      | nodeTypeName            | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateIdentifier | nodeTypeName                            | parentNodeAggregateIdentifier | nodeName |
      | sir-david-nodenborough  | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford        | document |
      | nodingers-cat           | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford        | pet      |
      | nodingers-kitten        | Neos.ContentRepository.Testing:Document | nodingers-cat                 | kitten   |
    And the command SetNodeReferences is executed with payload:
      | Key                           | Value                                  |
      | sourceNodeAggregateIdentifier | "nodingers-cat"                        |
      | referenceName                 | "references"                           |
      | references                    | [{"target": "sir-david-nodenborough"}] |

  Scenario: Remove a node aggregate with strategy allSpecializations
    When the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | nodeAggregateIdentifier      | "nodingers-cat"      |
      | coveredDimensionSpacePoint   | {"language":"de"}    |
      | nodeVariantSelectionStrategy | "allSpecializations" |
    Then I expect exactly 7 events to be published on stream with prefix "Neos.ContentRepository:ContentStream:cs-identifier"
    And event at index 6 is of type "NodeAggregateWasRemoved" with payload:
      | Key                                  | Expected                               |
      | contentStreamIdentifier              | "cs-identifier"                        |
      | nodeAggregateIdentifier              | "nodingers-cat"                        |
      | affectedOccupiedDimensionSpacePoints | [{"language":"en"}]                    |
      | affectedCoveredDimensionSpacePoints  | [{"language":"de"},{"language":"gsw"}] |
      | initiatingUserIdentifier             | "initiating-user-identifier"           |
      | removalAttachmentPoint               | null                                   |
    When the graph projection is fully up to date
    Then I expect the graph projection to consist of exactly 4 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{"language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodingers-cat;{"language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodingers-kitten;{"language":"en"} to exist in the content graph
    And I expect the node aggregate "nodingers-cat" to exist
    And I expect this node aggregate to cover dimension space points [{"language":"en"},{"language":"fr"}]
    And I expect the node aggregate "nodingers-kitten" to exist
    And I expect this node aggregate to cover dimension space points [{"language":"en"},{"language":"fr"}]

    # Check the selected variant
    When I am in dimension space point {"language":"de"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                      |
      | document | cs-identifier;sir-david-nodenborough;{"language":"en"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | sir-david-nodenborough  |

    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no child nodes
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nodingers-cat" and node path "pet" to lead to no node
    And I expect node aggregate identifier "nodingers-kitten" and node path "pet/kitten" to lead to no node

    # Check the generalization
    When I am in dimension space point {"language":"en"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                      |
      | document | cs-identifier;sir-david-nodenborough;{"language":"en"} |
      | pet      | cs-identifier;nodingers-cat;{"language":"en"}          |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | sir-david-nodenborough  |
      | 1     | nodingers-cat           |
      | 2     | nodingers-kitten        |

    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no child nodes
    And I expect this node to have no references
    And I expect this node to be referenced by:
      | Name       | Node                                          | Properties |
      | references | cs-identifier;nodingers-cat;{"language":"en"} | null       |

    And I expect node aggregate identifier "nodingers-cat" and node path "pet" to lead to node cs-identifier;nodingers-cat;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name   | NodeDiscriminator                                |
      | kitten | cs-identifier;nodingers-kitten;{"language":"en"} |
    And I expect this node to have the following references:
      | Name       | Node                                                   | Properties |
      | references | cs-identifier;sir-david-nodenborough;{"language":"en"} | null       |
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nodingers-kitten" and node path "pet/kitten" to lead to node cs-identifier;nodingers-kitten;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;nodingers-cat;{"language":"en"}
    And I expect this node to have no child nodes
    And I expect this node to have no references
    And I expect this node to not be referenced

    # Check the specialization
    When I am in dimension space point {"language":"gsw"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                      |
      | document | cs-identifier;sir-david-nodenborough;{"language":"en"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | sir-david-nodenborough  |

    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no child nodes
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nodingers-cat" and node path "pet" to lead to no node

    And I expect node aggregate identifier "nodingers-kitten" and node path "pet/kitten" to lead to no node

    # Check the peer
    When I am in dimension space point {"language":"fr"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                      |
      | document | cs-identifier;sir-david-nodenborough;{"language":"en"} |
      | pet      | cs-identifier;nodingers-cat;{"language":"en"}          |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | sir-david-nodenborough  |
      | 1     | nodingers-cat           |
      | 2     | nodingers-kitten        |

    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no child nodes
    And I expect this node to have no references
    And I expect this node to be referenced by:
      | Name       | Node                                          | Properties |
      | references | cs-identifier;nodingers-cat;{"language":"en"} | null       |

    And I expect node aggregate identifier "nodingers-cat" and node path "pet" to lead to node cs-identifier;nodingers-cat;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name   | NodeDiscriminator                                |
      | kitten | cs-identifier;nodingers-kitten;{"language":"en"} |
    And I expect this node to have the following references:
      | Name       | Node                                                   | Properties |
      | references | cs-identifier;sir-david-nodenborough;{"language":"en"} | null       |
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nodingers-kitten" and node path "pet/kitten" to lead to node cs-identifier;nodingers-kitten;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;nodingers-cat;{"language":"en"}
    And I expect this node to have no child nodes
    And I expect this node to have no references
    And I expect this node to not be referenced

  Scenario: Remove a node aggregate with strategy allVariants
    When the command CreateNodeVariant is executed with payload:
      | Key                     | Value             |
      | nodeAggregateIdentifier | "nodingers-cat"   |
      | sourceOrigin            | {"language":"en"} |
      | targetOrigin            | {"language":"de"} |
    And the graph projection is fully up to date

    When the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value             |
      | nodeAggregateIdentifier      | "nodingers-cat"   |
      | coveredDimensionSpacePoint   | {"language":"de"} |
      | nodeVariantSelectionStrategy | "allVariants"     |
    Then I expect exactly 8 events to be published on stream with prefix "Neos.ContentRepository:ContentStream:cs-identifier"
    And event at index 7 is of type "NodeAggregateWasRemoved" with payload:
      | Key                                  | Expected                                                                   |
      | contentStreamIdentifier              | "cs-identifier"                                                            |
      | nodeAggregateIdentifier              | "nodingers-cat"                                                            |
      | affectedOccupiedDimensionSpacePoints | [{"language":"en"},{"language":"de"}]                                      |
      | affectedCoveredDimensionSpacePoints  | [{"language":"en"},{"language":"de"},{"language":"fr"},{"language":"gsw"}] |
      | initiatingUserIdentifier             | "initiating-user-identifier"                                               |
      | removalAttachmentPoint               | null                                                                       |
    When the graph projection is fully up to date
    Then I expect the graph projection to consist of exactly 2 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{"language":"en"} to exist in the content graph

    # Check the selected variant
    When I am in dimension space point {"language":"de"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                      |
      | document | cs-identifier;sir-david-nodenborough;{"language":"en"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | sir-david-nodenborough  |

    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no child nodes
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nodingers-cat" and node path "pet" to lead to no node
    And I expect node aggregate identifier "nodingers-kitten" and node path "pet/kitten" to lead to no node

    # Check the generalization
    When I am in dimension space point {"language":"en"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                      |
      | document | cs-identifier;sir-david-nodenborough;{"language":"en"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | sir-david-nodenborough  |

    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no child nodes
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nodingers-cat" and node path "pet" to lead to no node
    And I expect node aggregate identifier "nodingers-kitten" and node path "pet/kitten" to lead to no node

    # Check the specialization
    When I am in dimension space point {"language":"gsw"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                      |
      | document | cs-identifier;sir-david-nodenborough;{"language":"en"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | sir-david-nodenborough  |

    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no child nodes
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nodingers-cat" and node path "pet" to lead to no node
    And I expect node aggregate identifier "nodingers-kitten" and node path "pet/kitten" to lead to no node

    # Check the peer
    When I am in dimension space point {"language":"fr"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                      |
      | document | cs-identifier;sir-david-nodenborough;{"language":"en"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | sir-david-nodenborough  |

    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no child nodes
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nodingers-cat" and node path "pet" to lead to no node
    And I expect node aggregate identifier "nodingers-kitten" and node path "pet/kitten" to lead to no node

  Scenario: Disable a node aggregate, completely remove it, recreate it and expect it to be enabled but have no references
    When the command DisableNodeAggregate is executed with payload:
      | Key                          | Value           |
      | nodeAggregateIdentifier      | "nodingers-cat" |
      | nodeVariantSelectionStrategy | "allVariants"   |
    And the graph projection is fully up to date
    And the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value           |
      | nodeAggregateIdentifier      | "nodingers-cat" |
      | nodeVariantSelectionStrategy | "allVariants"   |
    And the graph projection is fully up to date
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateIdentifier | nodeTypeName                            | parentNodeAggregateIdentifier | nodeName |
      | nodingers-cat           | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford        | pet      |
      | nodingers-kitten        | Neos.ContentRepository.Testing:Document | nodingers-cat                 | kitten   |

    Then I expect the node aggregate "nodingers-cat" to exist
    And I expect this node aggregate to disable dimension space points []
    And I expect the graph projection to consist of exactly 4 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{"language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodingers-cat;{"language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodingers-kitten;{"language":"en"} to exist in the content graph
    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                      |
      | document | cs-identifier;sir-david-nodenborough;{"language":"en"} |
      | pet      | cs-identifier;nodingers-cat;{"language":"en"}          |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | sir-david-nodenborough  |
      | 1     | nodingers-cat           |
      | 2     | nodingers-kitten        |

    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no child nodes
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nodingers-cat" and node path "pet" to lead to node cs-identifier;nodingers-cat;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name   | NodeDiscriminator                                |
      | kitten | cs-identifier;nodingers-kitten;{"language":"en"} |
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nodingers-kitten" and node path "pet/kitten" to lead to node cs-identifier;nodingers-kitten;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;nodingers-cat;{"language":"en"}
    And I expect this node to have no child nodes
    And I expect this node to have no references
    And I expect this node to not be referenced

  Scenario: Disable a node aggregate, partially remove it, recreate it and expect the recreated nodes to be enabled and have their source's references
    When the command DisableNodeAggregate is executed with payload:
      | Key                          | Value           |
      | nodeAggregateIdentifier      | "nodingers-cat" |
      | nodeVariantSelectionStrategy | "allVariants"   |
    And the graph projection is fully up to date
    And the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | nodeAggregateIdentifier      | "nodingers-cat"      |
      | coveredDimensionSpacePoint   | {"language":"de"}    |
      | nodeVariantSelectionStrategy | "allSpecializations" |
    And the graph projection is fully up to date
    And the command CreateNodeVariant is executed with payload:
      | Key                     | Value             |
      | nodeAggregateIdentifier | "nodingers-cat"   |
      | sourceOrigin            | {"language":"en"} |
      | targetOrigin            | {"language":"de"} |
    And the graph projection is fully up to date

    Then I expect the node aggregate "nodingers-cat" to exist
    And I expect this node aggregate to disable dimension space points [{"language":"en"},{"language":"fr"}]
    And I expect the graph projection to consist of exactly 5 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{"language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodingers-cat;{"language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodingers-cat;{"language":"de"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodingers-kitten;{"language":"en"} to exist in the content graph

    # Check the selected variant
    When I am in dimension space point {"language":"de"}

    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                      |
      | document | cs-identifier;sir-david-nodenborough;{"language":"en"} |
      | pet      | cs-identifier;nodingers-cat;{"language":"de"}          |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | sir-david-nodenborough  |
      | 1     | nodingers-cat           |

    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no child nodes
    And I expect this node to have no references
    And I expect this node to be referenced by:
      | Name       | Node                                          | Properties |
      | references | cs-identifier;nodingers-cat;{"language":"de"} | null       |

    And I expect node aggregate identifier "nodingers-cat" and node path "pet" to lead to node cs-identifier;nodingers-cat;{"language":"de"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no child nodes
    And I expect this node to have the following references:
      | Name       | Node                                                   | Properties |
      | references | cs-identifier;sir-david-nodenborough;{"language":"en"} | null       |
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nodingers-kitten" and node path "pet/kitten" to lead to no node

    # Check the specialization
    When I am in dimension space point {"language":"gsw"}

    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                      |
      | document | cs-identifier;sir-david-nodenborough;{"language":"en"} |
      | pet      | cs-identifier;nodingers-cat;{"language":"de"}          |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | sir-david-nodenborough  |
      | 1     | nodingers-cat           |

    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no child nodes
    And I expect this node to have no references
    And I expect this node to be referenced by:
      | Name       | Node                                          | Properties |
      | references | cs-identifier;nodingers-cat;{"language":"de"} | null       |

    And I expect node aggregate identifier "nodingers-cat" and node path "pet" to lead to node cs-identifier;nodingers-cat;{"language":"de"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no child nodes
    And I expect this node to have the following references:
      | Name       | Node                                                   | Properties |
      | references | cs-identifier;sir-david-nodenborough;{"language":"en"} | null       |
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nodingers-kitten" and node path "pet/kitten" to lead to no node

    # Check the generalization
    When I am in dimension space point {"language":"en"}

    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                      |
      | document | cs-identifier;sir-david-nodenborough;{"language":"en"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | sir-david-nodenborough  |

    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no child nodes
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nodingers-cat" and node path "pet" to lead to no node
    And I expect node aggregate identifier "nodingers-kitten" and node path "pet/kitten" to lead to no node

    # Check the peer variant
    When I am in dimension space point {"language":"fr"}

    And I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                      |
      | document | cs-identifier;sir-david-nodenborough;{"language":"en"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | sir-david-nodenborough  |

    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no child nodes
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nodingers-cat" and node path "pet" to lead to no node
    And I expect node aggregate identifier "nodingers-kitten" and node path "pet/kitten" to lead to no node
