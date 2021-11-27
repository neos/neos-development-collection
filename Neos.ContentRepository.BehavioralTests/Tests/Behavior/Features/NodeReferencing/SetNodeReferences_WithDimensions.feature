@fixtures
Feature: Node References with Dimensions

  As a user of the CR I want to disable a node aggregate and expect its descendants to also be disabled.

  References between nodes are created are available in specializations but not in generalizations or peer variants.

  Background:
    Given I have the following content dimensions:
      | Identifier | Default | Values          | Generalizations      |
      | language   | mul     | mul, de, en, ch | ch->de->mul, en->mul |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:NodeWithReferences':
      properties:
        referenceProperty:
          type: reference
        referencesProperty:
          type: references
        text:
          type: string
    """
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value                                  |
      | workspaceName              | "live"                                 |
      | workspaceTitle             | "Live"                                 |
      | workspaceDescription       | "The live workspace"                   |
      | initiatingUserIdentifier   | "00000000-0000-0000-0000-000000000000" |
      | newContentStreamIdentifier | "cs-identifier"                        |
    And the graph projection is fully up to date
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                      | Value                                  |
      | contentStreamIdentifier  | "cs-identifier"                        |
      | nodeAggregateIdentifier  | "lady-eleonode-rootford"               |
      | nodeTypeName             | "Neos.ContentRepository:Root"          |
      | initiatingUserIdentifier | "00000000-0000-0000-0000-000000000000" |
    And the graph projection is fully up to date
    And the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                           | Value                                               |
      | contentStreamIdentifier       | "cs-identifier"                                     |
      | nodeAggregateIdentifier       | "source-nodandaise"                                 |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:NodeWithReferences" |
      | originDimensionSpacePoint     | {"language": "de"}                                  |
      | initiatingUserIdentifier      | "00000000-0000-0000-0000-000000000000"              |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                            |
    And the graph projection is fully up to date
    And the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                           | Value                                               |
      | nodeAggregateIdentifier       | "anthony-destinode"                                 |
      | originDimensionSpacePoint     | {"language": "de"}                                  |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:NodeWithReferences" |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                            |
    And the graph projection is fully up to date


  Scenario: Create a reference and check whether they can be read in the different subgraphs
    When the command SetNodeReferences is executed with payload:
      | Key                                 | Value                 |
      | contentStreamIdentifier             | "cs-identifier"       |
      | sourceNodeAggregateIdentifier       | "source-nodandaise"   |
      | sourceOriginDimensionSpacePoint     | {"language": "de"}    |
      | referenceName                       | "referenceProperty"   |
      | destinationNodeAggregateIdentifiers | ["anthony-destinode"] |
    And the graph projection is fully up to date

    When I am in content stream "cs-identifier" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{"language": "de"}
    And I expect this node to have the following references:
      | Key               | Value                                                                                     |
      | referenceProperty | ["cs-identifier;anthony-destinode;{"language": "de"}"] |
    Then I expect node aggregate identifier "anthony-destinode" to lead to node cs-identifier;anthony-destinode;{"language": "de"}
    And I expect this node to be referenced by:
      | Key               | Value                 |
      | referenceProperty | ["cs-identifier;source-nodandaise{"language": "de"}"] |

    When I am in content stream "cs-identifier" and dimension space point {"language": "ch"}
    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{"language": "de"}
    And I expect this node to have the following references:
      | Key               | Value                                                                                     |
      | referenceProperty | ["cs-identifier;anthony-destinode;{"language": "de"}"] |
    Then I expect node aggregate identifier "anthony-destinode" to lead to node cs-identifier;anthony-destinode;{"language": "de"}
    And I expect this node to be referenced by:
      | Key               | Value                 |
      | referenceProperty | ["cs-identifier;source-nodandaise{"language": "de"}"] |

    # todo: does this case even make sense?
    When I am in content stream "cs-identifier" and dimension space point {"language": "en"}
    Then I expect node aggregate identifier "source-nodandaise" to lead to no node
    Then I expect node aggregate identifier "anthony-destinode" to lead to no node


  Scenario: Create a reference, trigger copy-on-write of the nodes, and ensure reference still exists.
    When the command SetNodeReferences is executed with payload:
      | Key                                 | Value                 |
      | contentStreamIdentifier             | "cs-identifier"       |
      | sourceNodeAggregateIdentifier       | "source-nodandaise"   |
      | sourceOriginDimensionSpacePoint     | {"language": "de"}    |
      | referenceName                       | "referenceProperty"   |
      | destinationNodeAggregateIdentifiers | ["anthony-destinode"] |
    And the graph projection is fully up to date

    When the command "ForkContentStream" is executed with payload:
      | Key                           | Value                |
      | contentStreamIdentifier       | "user-cs-identifier" |
      | sourceContentStreamIdentifier | "cs-identifier"      |
      | initiatingUserIdentifier      | "user-identifier"    |
    And the graph projection is fully up to date

    # after forking, the reference must still exist on the forked content stream (no surprises here).
    When I am in content stream "user-cs-identifier" and dimension space point {"language": "de"}
    Then I expect the node aggregate "source-nodandaise" to have the references:
      | Key               | Value                 |
      | referenceProperty | ["anthony-destinode"] |
    And I expect the node aggregate "anthony-destinode" to be referenced by:
      | Key               | Value                 |
      | referenceProperty | ["source-nodandaise"] |

    When I am in content stream "user-cs-identifier" and dimension space point {"language": "ch"}
    Then I expect the node aggregate "source-nodandaise" to have the references:
      | Key               | Value                 |
      | referenceProperty | ["anthony-destinode"] |
    And I expect the node aggregate "anthony-destinode" to be referenced by:
      | Key               | Value                 |
      | referenceProperty | ["source-nodandaise"] |

    When I am in content stream "user-cs-identifier" and dimension space point {"language": "en"}
    Then I expect the node aggregate "source-nodandaise" to have the references:
      | Key               | Value |
      | referenceProperty | []    |
    And I expect the node aggregate "anthony-destinode" to be referenced by:
      | Key               | Value |
      | referenceProperty | []    |

    # after then modifying the node's properties (thus triggering copy-on-write), the reference property
    # should still exist (this was a BUG)
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                                  |
      | contentStreamIdentifier   | "user-cs-identifier"                   |
      | nodeAggregateIdentifier   | "source-nodandaise"                    |
      | originDimensionSpacePoint | {"language": "de"}                     |
      | propertyValues            | {"text": "Modified in live workspace"} |
    And the graph projection is fully up to date
    When I am in content stream "user-cs-identifier" and dimension space point {"language": "de"}
    Then I expect the node aggregate "source-nodandaise" to have the references:
      | Key               | Value                 |
      | referenceProperty | ["anthony-destinode"] |
    And I expect the node aggregate "anthony-destinode" to be referenced by:
      | Key               | Value                 |
      | referenceProperty | ["source-nodandaise"] |

    When I am in content stream "user-cs-identifier" and dimension space point {"language": "ch"}
    Then I expect the node aggregate "source-nodandaise" to have the references:
      | Key               | Value                 |
      | referenceProperty | ["anthony-destinode"] |
    And I expect the node aggregate "anthony-destinode" to be referenced by:
      | Key               | Value                 |
      | referenceProperty | ["source-nodandaise"] |

    When I am in content stream "user-cs-identifier" and dimension space point {"language": "en"}
    Then I expect the node aggregate "source-nodandaise" to have the references:
      | Key               | Value |
      | referenceProperty | []    |
    And I expect the node aggregate "anthony-destinode" to be referenced by:
      | Key               | Value |
      | referenceProperty | []    |


  Scenario: Create a reference, then specialize the source node; and the references should exist on the specialization
    When the command SetNodeReferences is executed with payload:
      | Key                                 | Value                 |
      | contentStreamIdentifier             | "cs-identifier"       |
      | sourceNodeAggregateIdentifier       | "source-nodandaise"   |
      | sourceOriginDimensionSpacePoint     | {"language": "de"}    |
      | referenceName                       | "referenceProperty"   |
      | destinationNodeAggregateIdentifiers | ["anthony-destinode"] |
    And the graph projection is fully up to date

    When the command CreateNodeVariant is executed with payload:
      | Key                     | Value               |
      | contentStreamIdentifier | "cs-identifier"     |
      | nodeAggregateIdentifier | "source-nodandaise" |
      | sourceOrigin            | {"language":"de"}   |
      | targetOrigin            | {"language":"ch"}   |
    And the graph projection is fully up to date

    # after specialization, the reference must still exist on the specialized node
    When I am in content stream "cs-identifier" and dimension space point {"language": "ch"}
    Then I expect the node aggregate "source-nodandaise" to have the references:
      | Key               | Value                 |
      | referenceProperty | ["anthony-destinode"] |
    And I expect the node aggregate "anthony-destinode" to be referenced by:
      | Key               | Value                 |
      | referenceProperty | ["source-nodandaise"] |

    # the reference must also exist on the non-touched nodes
    When I am in content stream "cs-identifier" and dimension space point {"language": "de"}
    Then I expect the node aggregate "source-nodandaise" to have the references:
      | Key               | Value                 |
      | referenceProperty | ["anthony-destinode"] |
    And I expect the node aggregate "anthony-destinode" to be referenced by:
      | Key               | Value                 |
      | referenceProperty | ["source-nodandaise"] |

    When I am in content stream "cs-identifier" and dimension space point {"language": "en"}
    Then I expect the node aggregate "source-nodandaise" to have the references:
      | Key               | Value |
      | referenceProperty | []    |
    And I expect the node aggregate "anthony-destinode" to be referenced by:
      | Key               | Value |
      | referenceProperty | []    |

    # now, when modifying the specialization reference, only the specialization is changed.
    When the command SetNodeReferences is executed with payload:
      | Key                                 | Value                 |
      | contentStreamIdentifier             | "cs-identifier"       |
      | sourceNodeAggregateIdentifier       | "source-nodandaise"   |
      | sourceOriginDimensionSpacePoint     | {"language": "ch"}    |
      | referenceName                       | "referenceProperty"   |
      | destinationNodeAggregateIdentifiers | ["source-nodandaise"] |
    And the graph projection is fully up to date

    # reference to self (modified 2 lines above)
    When I am in content stream "cs-identifier" and dimension space point {"language": "ch"}
    Then I expect the node aggregate "source-nodandaise" to have the references:
      | Key               | Value                 |
      | referenceProperty | ["source-nodandaise"] |
    And I expect the node aggregate "source-nodandaise" to be referenced by:
      | Key               | Value                 |
      | referenceProperty | ["source-nodandaise"] |

    # unmodified on the untouched nodes
    When I am in content stream "cs-identifier" and dimension space point {"language": "de"}
    Then I expect the node aggregate "source-nodandaise" to have the references:
      | Key               | Value                 |
      | referenceProperty | ["anthony-destinode"] |
    And I expect the node aggregate "anthony-destinode" to be referenced by:
      | Key               | Value                 |
      | referenceProperty | ["source-nodandaise"] |

    When I am in content stream "cs-identifier" and dimension space point {"language": "en"}
    Then I expect the node aggregate "source-nodandaise" to have the references:
      | Key               | Value |
      | referenceProperty | []    |
    And I expect the node aggregate "anthony-destinode" to be referenced by:
      | Key               | Value |
      | referenceProperty | []    |

  Scenario: specialize the source node, only set reference on the specialization. Then, the reference should only appear on the specialization
    When the command CreateNodeVariant is executed with payload:
      | Key                     | Value               |
      | contentStreamIdentifier | "cs-identifier"     |
      | nodeAggregateIdentifier | "source-nodandaise" |
      | sourceOrigin            | {"language":"de"}   |
      | targetOrigin            | {"language":"ch"}   |
    And the graph projection is fully up to date

    When the command SetNodeReferences is executed with payload:
      | Key                                 | Value                 |
      | contentStreamIdentifier             | "cs-identifier"       |
      | sourceNodeAggregateIdentifier       | "source-nodandaise"   |
      | sourceOriginDimensionSpacePoint     | {"language": "ch"}    |
      | referenceName                       | "referenceProperty"   |
      | destinationNodeAggregateIdentifiers | ["anthony-destinode"] |
    And the graph projection is fully up to date


    # on the specialization, the reference exists.
    When I am in content stream "cs-identifier" and dimension space point {"language": "ch"}
    Then I expect the node aggregate "source-nodandaise" to have the references:
      | Key               | Value                 |
      | referenceProperty | ["anthony-destinode"] |
    And I expect the node aggregate "anthony-destinode" to be referenced by:
      | Key               | Value                 |
      | referenceProperty | ["source-nodandaise"] |

    # on the other nodes, the reference does not exist.
    When I am in content stream "cs-identifier" and dimension space point {"language": "de"}
    Then I expect the node aggregate "source-nodandaise" to have the references:
      | Key               | Value |
      | referenceProperty | []    |
    And I expect the node aggregate "anthony-destinode" to be referenced by:
      | Key               | Value |
      | referenceProperty | []    |

    When I am in content stream "cs-identifier" and dimension space point {"language": "en"}
    Then I expect the node aggregate "source-nodandaise" to have the references:
      | Key               | Value |
      | referenceProperty | []    |
    And I expect the node aggregate "anthony-destinode" to be referenced by:
      | Key               | Value |
      | referenceProperty | []    |


  Scenario: Create a reference, then create a peer variant of the source node; and the references should exist on the peer
    # prerequisite: "anthony-destinode" also exists in EN
    When the command CreateNodeVariant is executed with payload:
      | Key                     | Value               |
      | contentStreamIdentifier | "cs-identifier"     |
      | nodeAggregateIdentifier | "anthony-destinode" |
      | sourceOrigin            | {"language":"de"}   |
      | targetOrigin            | {"language":"en"}   |
    And the graph projection is fully up to date

    When the command SetNodeReferences is executed with payload:
      | Key                                 | Value                 |
      | contentStreamIdentifier             | "cs-identifier"       |
      | sourceNodeAggregateIdentifier       | "source-nodandaise"   |
      | sourceOriginDimensionSpacePoint     | {"language": "de"}    |
      | referenceName                       | "referenceProperty"   |
      | destinationNodeAggregateIdentifiers | ["anthony-destinode"] |
    And the graph projection is fully up to date

    When the command CreateNodeVariant is executed with payload:
      | Key                     | Value               |
      | contentStreamIdentifier | "cs-identifier"     |
      | nodeAggregateIdentifier | "source-nodandaise" |
      | sourceOrigin            | {"language":"de"}   |
      | targetOrigin            | {"language":"en"}   |
    And the graph projection is fully up to date

    # after creating a peer, the reference must still exist on the peer node
    When I am in content stream "cs-identifier" and dimension space point {"language": "en"}
    Then I expect the node aggregate "source-nodandaise" to have the references:
      | Key               | Value                 |
      | referenceProperty | ["anthony-destinode"] |
    And I expect the node aggregate "anthony-destinode" to be referenced by:
      | Key               | Value                 |
      | referenceProperty | ["source-nodandaise"] |

    # the reference must also exist on the non-touched nodes
    When I am in content stream "cs-identifier" and dimension space point {"language": "de"}
    Then I expect the node aggregate "source-nodandaise" to have the references:
      | Key               | Value                 |
      | referenceProperty | ["anthony-destinode"] |
    And I expect the node aggregate "anthony-destinode" to be referenced by:
      | Key               | Value                 |
      | referenceProperty | ["source-nodandaise"] |

    When I am in content stream "cs-identifier" and dimension space point {"language": "ch"}
    Then I expect the node aggregate "source-nodandaise" to have the references:
      | Key               | Value                 |
      | referenceProperty | ["anthony-destinode"] |
    And I expect the node aggregate "anthony-destinode" to be referenced by:
      | Key               | Value                 |
      | referenceProperty | ["source-nodandaise"] |


    # now, when modifying the peer reference, only the peer is changed.
    When the command SetNodeReferences is executed with payload:
      | Key                                 | Value                 |
      | contentStreamIdentifier             | "cs-identifier"       |
      | sourceNodeAggregateIdentifier       | "source-nodandaise"   |
      | sourceOriginDimensionSpacePoint     | {"language": "en"}    |
      | referenceName                       | "referenceProperty"   |
      | destinationNodeAggregateIdentifiers | ["source-nodandaise"] |
    And the graph projection is fully up to date

    # reference to self (modified 2 lines above)
    When I am in content stream "cs-identifier" and dimension space point {"language": "en"}
    Then I expect the node aggregate "source-nodandaise" to have the references:
      | Key               | Value                 |
      | referenceProperty | ["source-nodandaise"] |
    And I expect the node aggregate "source-nodandaise" to be referenced by:
      | Key               | Value                 |
      | referenceProperty | ["source-nodandaise"] |

    # unmodified on the untouched nodes
    When I am in content stream "cs-identifier" and dimension space point {"language": "de"}
    Then I expect the node aggregate "source-nodandaise" to have the references:
      | Key               | Value                 |
      | referenceProperty | ["anthony-destinode"] |
    And I expect the node aggregate "anthony-destinode" to be referenced by:
      | Key               | Value                 |
      | referenceProperty | ["source-nodandaise"] |

    When I am in content stream "cs-identifier" and dimension space point {"language": "ch"}
    Then I expect the node aggregate "source-nodandaise" to have the references:
      | Key               | Value                 |
      | referenceProperty | ["anthony-destinode"] |
    And I expect the node aggregate "anthony-destinode" to be referenced by:
      | Key               | Value                 |
      | referenceProperty | ["source-nodandaise"] |

  Scenario: Create a reference, then create a generalization of the source node; and the references should exist on the generalization
    # We need to create a new ch-only node to test this; as by default, only a german node already exists shining through in ch
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                           | Value                                               |
      | contentStreamIdentifier       | "cs-identifier"                                     |
      | nodeAggregateIdentifier       | "ch-only"                                           |
      | originDimensionSpacePoint     | {"language": "ch"}                                  |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:NodeWithReferences" |
      | initiatingUserIdentifier      | "00000000-0000-0000-0000-000000000000"              |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                            |
    And the graph projection is fully up to date

    When the command SetNodeReferences is executed with payload:
      | Key                                 | Value                 |
      | contentStreamIdentifier             | "cs-identifier"       |
      | sourceNodeAggregateIdentifier       | "ch-only"             |
      | sourceOriginDimensionSpacePoint     | {"language": "ch"}    |
      | referenceName                       | "referenceProperty"   |
      | destinationNodeAggregateIdentifiers | ["anthony-destinode"] |
    And the graph projection is fully up to date

    # here we generalize
    When the command CreateNodeVariant is executed with payload:
      | Key                     | Value             |
      | contentStreamIdentifier | "cs-identifier"   |
      | nodeAggregateIdentifier | "ch-only"         |
      | sourceOrigin            | {"language":"ch"} |
      | targetOrigin            | {"language":"de"} |
    And the graph projection is fully up to date

    # after generalizing, the reference must still exist on the generalized node
    When I am in content stream "cs-identifier" and dimension space point {"language": "de"}
    Then I expect the node aggregate "ch-only" to have the references:
      | Key               | Value                 |
      | referenceProperty | ["anthony-destinode"] |
    And I expect the node aggregate "anthony-destinode" to be referenced by:
      | Key               | Value       |
      | referenceProperty | ["ch-only"] |

    # the reference must also exist on the non-touched node
    When I am in content stream "cs-identifier" and dimension space point {"language": "ch"}
    Then I expect the node aggregate "ch-only" to have the references:
      | Key               | Value                 |
      | referenceProperty | ["anthony-destinode"] |
    And I expect the node aggregate "anthony-destinode" to be referenced by:
      | Key               | Value       |
      | referenceProperty | ["ch-only"] |

  Scenario: Error on invalid dimension space point
    When the command SetNodeReferences is executed with payload and exceptions are caught:
      | Key                                 | Value                 |
      | contentStreamIdentifier             | "cs-identifier"       |
      | sourceNodeAggregateIdentifier       | "source-nodandaise"   |
      | sourceOriginDimensionSpacePoint     | {"language": "ch"}    |
      | referenceName                       | "referenceProperty"   |
      | destinationNodeAggregateIdentifiers | ["anthony-destinode"] |
    Then the last command should have thrown an exception of type "DimensionSpacePointIsNotYetOccupied"


  Scenario: Error on non-existing target
    When the command SetNodeReferences is executed with payload and exceptions are caught:
      | Key                                 | Value               |
      | contentStreamIdentifier             | "cs-identifier"     |
      | sourceNodeAggregateIdentifier       | "source-nodandaise" |
      | sourceOriginDimensionSpacePoint     | {"language": "de"}  |
      | referenceName                       | "referenceProperty" |
      | destinationNodeAggregateIdentifiers | ["mytarget"]        |
    Then the last command should have thrown an exception of type "NodeAggregateCurrentlyDoesNotExist"

  Scenario: Error on target which exists, but not in this dimension space point
    When the command CreateNodeVariant is executed with payload:
      | Key                     | Value               |
      | contentStreamIdentifier | "cs-identifier"     |
      | nodeAggregateIdentifier | "source-nodandaise" |
      | sourceOrigin            | {"language":"de"}   |
      | targetOrigin            | {"language":"ch"}   |
    When the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value               |
      | contentStreamIdentifier      | "cs-identifier"     |
      | nodeAggregateIdentifier      | "anthony-destinode" |
      | nodeVariantSelectionStrategy | "onlyGivenVariant"  |
      | coveredDimensionSpacePoint   | {"language":"ch"}   |
    And the graph projection is fully up to date

    When the command SetNodeReferences is executed with payload and exceptions are caught:
      | Key                                 | Value                 |
      | contentStreamIdentifier             | "cs-identifier"       |
      | sourceNodeAggregateIdentifier       | "source-nodandaise"   |
      | sourceOriginDimensionSpacePoint     | {"language": "ch"}    |
      | referenceName                       | "referenceProperty"   |
      | destinationNodeAggregateIdentifiers | ["anthony-destinode"] |
    Then the last command should have thrown an exception of type "NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint"
