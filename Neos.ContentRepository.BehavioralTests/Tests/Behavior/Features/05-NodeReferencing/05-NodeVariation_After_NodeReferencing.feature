@fixtures @adapters=DoctrineDBAL,Postgres
Feature: Node References with Dimensions

  As a user of the CR I want to be able to create, overwrite, reorder and delete reference between nodes

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
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value                |
      | workspaceName              | "live"               |
      | workspaceTitle             | "Live"               |
      | workspaceDescription       | "The live workspace" |
      | newContentStreamIdentifier | "cs-identifier"      |
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and dimension space point {"language":"de"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                     | Value                         |
      | nodeAggregateIdentifier | "lady-eleonode-rootford"      |
      | nodeTypeName            | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateIdentifier | nodeTypeName                                      | parentNodeAggregateIdentifier |
      | source-nodandaise       | Neos.ContentRepository.Testing:NodeWithReferences | lady-eleonode-rootford        |
      | anthony-destinode       | Neos.ContentRepository.Testing:NodeWithReferences | lady-eleonode-rootford        |

  Scenario: Create a reference, then specialize the source node; and the references should exist on the specialization
    When the command SetNodeReferences is executed with payload:
      | Key                                 | Value                 |
      | sourceNodeAggregateIdentifier       | "source-nodandaise"   |
      | referenceName                       | "referenceProperty"   |
      | destinationNodeAggregateIdentifiers | ["anthony-destinode"] |
    And the graph projection is fully up to date

    When the command CreateNodeVariant is executed with payload:
      | Key                     | Value               |
      | nodeAggregateIdentifier | "source-nodandaise" |
      | sourceOrigin            | {"language":"de"}   |
      | targetOrigin            | {"language":"ch"}   |
    And the graph projection is fully up to date

    # after specialization, the reference must still exist on the specialized node
    When I am in content stream "cs-identifier" and dimension space point {"language": "ch"}
    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{"language": "ch"}
    And I expect this node to have the following references:
      | Key               | Value                                                      |
      | referenceProperty | ["cs-identifier;anthony-destinode;{\"language\": \"de\"}"] |
    Then I expect node aggregate identifier "anthony-destinode" to lead to node cs-identifier;anthony-destinode;{"language": "de"}
    And I expect this node to be referenced by:
      | Key               | Value                                                      |
      | referenceProperty | ["cs-identifier;source-nodandaise;{\"language\": \"ch\"}"] |

    # the reference must also exist on the non-touched nodes
    When I am in content stream "cs-identifier" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{"language": "de"}
    And I expect this node to have the following references:
      | Key               | Value                                                      |
      | referenceProperty | ["cs-identifier;anthony-destinode;{\"language\": \"de\"}"] |
    Then I expect node aggregate identifier "anthony-destinode" to lead to node cs-identifier;anthony-destinode;{"language": "de"}
    And I expect this node to be referenced by:
      | Key               | Value                                                      |
      | referenceProperty | ["cs-identifier;source-nodandaise;{\"language\": \"de\"}"] |

    # now, when modifying the specialization reference, only the specialization is changed.
    When the command SetNodeReferences is executed with payload:
      | Key                                 | Value                 |
      | sourceNodeAggregateIdentifier       | "source-nodandaise"   |
      | sourceOriginDimensionSpacePoint     | {"language": "ch"}    |
      | referenceName                       | "referenceProperty"   |
      | destinationNodeAggregateIdentifiers | ["source-nodandaise"] |
    And the graph projection is fully up to date

    # reference to self (modified 2 lines above)
    When I am in content stream "cs-identifier" and dimension space point {"language": "ch"}
    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{"language": "ch"}
    And I expect this node to have the following references:
      | Key               | Value                                                      |
      | referenceProperty | ["cs-identifier;source-nodandaise;{\"language\": \"ch\"}"] |
    And I expect this node to be referenced by:
      | Key               | Value                                                      |
      | referenceProperty | ["cs-identifier;source-nodandaise;{\"language\": \"ch\"}"] |

    # unmodified on the untouched nodes
    When I am in content stream "cs-identifier" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{"language": "de"}
    And I expect this node to have the following references:
      | Key               | Value                                                      |
      | referenceProperty | ["cs-identifier;anthony-destinode;{\"language\": \"de\"}"] |
    Then I expect node aggregate identifier "anthony-destinode" to lead to node cs-identifier;anthony-destinode;{"language": "de"}
    And I expect this node to be referenced by:
      | Key               | Value                                                      |
      | referenceProperty | ["cs-identifier;source-nodandaise;{\"language\": \"de\"}"] |

  Scenario: specialize the source node, only set reference on the specialization. Then, the reference should only appear on the specialization
    When the command CreateNodeVariant is executed with payload:
      | Key                     | Value               |
      | nodeAggregateIdentifier | "source-nodandaise" |
      | sourceOrigin            | {"language":"de"}   |
      | targetOrigin            | {"language":"ch"}   |
    And the graph projection is fully up to date

    When the command SetNodeReferences is executed with payload:
      | Key                                 | Value                 |
      | sourceNodeAggregateIdentifier       | "source-nodandaise"   |
      | sourceOriginDimensionSpacePoint     | {"language": "ch"}    |
      | referenceName                       | "referenceProperty"   |
      | destinationNodeAggregateIdentifiers | ["anthony-destinode"] |
    And the graph projection is fully up to date


    # on the specialization, the reference exists.
    When I am in content stream "cs-identifier" and dimension space point {"language": "ch"}
    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{"language": "ch"}
    And I expect this node to have the following references:
      | Key               | Value                                                      |
      | referenceProperty | ["cs-identifier;anthony-destinode;{\"language\": \"de\"}"] |
    Then I expect node aggregate identifier "anthony-destinode" to lead to node cs-identifier;anthony-destinode;{"language": "de"}
    And I expect this node to be referenced by:
      | Key               | Value                                                      |
      | referenceProperty | ["cs-identifier;source-nodandaise;{\"language\": \"ch\"}"] |

    # on the other nodes, the reference does not exist.
    When I am in content stream "cs-identifier" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{"language": "de"}
    And I expect this node to have the following references:
      | Key               | Value |
      | referenceProperty | []    |
    Then I expect node aggregate identifier "anthony-destinode" to lead to node cs-identifier;anthony-destinode;{"language": "de"}
    And I expect this node to be referenced by:
      | Key               | Value |
      | referenceProperty | []    |

  Scenario: Create a reference, then create a peer variant of the source node; and the references should exist on the peer
    # prerequisite: "anthony-destinode" also exists in EN
    When the command CreateNodeVariant is executed with payload:
      | Key                     | Value               |
      | nodeAggregateIdentifier | "anthony-destinode" |
      | sourceOrigin            | {"language":"de"}   |
      | targetOrigin            | {"language":"en"}   |
    And the graph projection is fully up to date

    When the command SetNodeReferences is executed with payload:
      | Key                                 | Value                 |
      | sourceNodeAggregateIdentifier       | "source-nodandaise"   |
      | referenceName                       | "referenceProperty"   |
      | destinationNodeAggregateIdentifiers | ["anthony-destinode"] |
    And the graph projection is fully up to date

    When the command CreateNodeVariant is executed with payload:
      | Key                     | Value               |
      | nodeAggregateIdentifier | "source-nodandaise" |
      | sourceOrigin            | {"language":"de"}   |
      | targetOrigin            | {"language":"en"}   |
    And the graph projection is fully up to date

    # after creating a peer, the reference must still exist on the peer node
    When I am in content stream "cs-identifier" and dimension space point {"language": "en"}
    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{"language": "en"}
    And I expect this node to have the following references:
      | Key               | Value                                                      |
      | referenceProperty | ["cs-identifier;anthony-destinode;{\"language\": \"en\"}"] |
    Then I expect node aggregate identifier "anthony-destinode" to lead to node cs-identifier;anthony-destinode;{"language": "en"}
    And I expect this node to be referenced by:
      | Key               | Value                                                      |
      | referenceProperty | ["cs-identifier;source-nodandaise;{\"language\": \"en\"}"] |

    # the reference must also exist on the non-touched nodes
    When I am in content stream "cs-identifier" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{"language": "de"}
    And I expect this node to have the following references:
      | Key               | Value                                                      |
      | referenceProperty | ["cs-identifier;anthony-destinode;{\"language\": \"de\"}"] |
    Then I expect node aggregate identifier "anthony-destinode" to lead to node cs-identifier;anthony-destinode;{"language": "de"}
    And I expect this node to be referenced by:
      | Key               | Value                                                      |
      | referenceProperty | ["cs-identifier;source-nodandaise;{\"language\": \"de\"}"] |

    When I am in content stream "cs-identifier" and dimension space point {"language": "ch"}
    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{"language": "de"}
    And I expect this node to have the following references:
      | Key               | Value                                                      |
      | referenceProperty | ["cs-identifier;anthony-destinode;{\"language\": \"de\"}"] |
    Then I expect node aggregate identifier "anthony-destinode" to lead to node cs-identifier;anthony-destinode;{"language": "de"}
    And I expect this node to be referenced by:
      | Key               | Value                                                      |
      | referenceProperty | ["cs-identifier;source-nodandaise;{\"language\": \"de\"}"] |


    # now, when modifying the peer reference, only the peer is changed.
    When the command SetNodeReferences is executed with payload:
      | Key                                 | Value                 |
      | sourceNodeAggregateIdentifier       | "source-nodandaise"   |
      | sourceOriginDimensionSpacePoint     | {"language": "en"}    |
      | referenceName                       | "referenceProperty"   |
      | destinationNodeAggregateIdentifiers | ["source-nodandaise"] |
    And the graph projection is fully up to date

    # reference to self (modified 2 lines above)
    When I am in content stream "cs-identifier" and dimension space point {"language": "en"}
    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{"language": "en"}
    And I expect this node to have the following references:
      | Key               | Value                                                      |
      | referenceProperty | ["cs-identifier;source-nodandaise;{\"language\": \"en\"}"] |
    And I expect this node to be referenced by:
      | Key               | Value                                                      |
      | referenceProperty | ["cs-identifier;source-nodandaise;{\"language\": \"en\"}"] |

    # unmodified on the untouched nodes
    When I am in content stream "cs-identifier" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{"language": "de"}
    And I expect this node to have the following references:
      | Key               | Value                                                      |
      | referenceProperty | ["cs-identifier;anthony-destinode;{\"language\": \"de\"}"] |
    Then I expect node aggregate identifier "anthony-destinode" to lead to node cs-identifier;anthony-destinode;{"language": "de"}
    And I expect this node to be referenced by:
      | Key               | Value                                                      |
      | referenceProperty | ["cs-identifier;source-nodandaise;{\"language\": \"de\"}"] |

    When I am in content stream "cs-identifier" and dimension space point {"language": "ch"}
    Then I expect node aggregate identifier "source-nodandaise" to lead to node cs-identifier;source-nodandaise;{"language": "de"}
    And I expect this node to have the following references:
      | Key               | Value                                                      |
      | referenceProperty | ["cs-identifier;anthony-destinode;{\"language\": \"de\"}"] |
    Then I expect node aggregate identifier "anthony-destinode" to lead to node cs-identifier;anthony-destinode;{"language": "de"}
    And I expect this node to be referenced by:
      | Key               | Value                                                      |
      | referenceProperty | ["cs-identifier;source-nodandaise;{\"language\": \"de\"}"] |

  Scenario: Create a reference, then create a generalization of the source node; and the references should exist on the generalization
    # We need to create a new ch-only node to test this; as by default, only a german node already exists shining through in ch
    And the command CreateNodeAggregateWithNode is executed with payload:
      | Key                           | Value                                               |
      | nodeAggregateIdentifier       | "ch-only"                                           |
      | originDimensionSpacePoint     | {"language": "ch"}                                  |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:NodeWithReferences" |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                            |
    And the graph projection is fully up to date

    When the command SetNodeReferences is executed with payload:
      | Key                                 | Value                 |
      | sourceNodeAggregateIdentifier       | "ch-only"             |
      | sourceOriginDimensionSpacePoint     | {"language": "ch"}    |
      | referenceName                       | "referenceProperty"   |
      | destinationNodeAggregateIdentifiers | ["anthony-destinode"] |
    And the graph projection is fully up to date

    # here we generalize
    When the command CreateNodeVariant is executed with payload:
      | Key                     | Value             |
      | nodeAggregateIdentifier | "ch-only"         |
      | sourceOrigin            | {"language":"ch"} |
      | targetOrigin            | {"language":"de"} |
    And the graph projection is fully up to date

    # after generalizing, the reference must still exist on the generalized node
    When I am in content stream "cs-identifier" and dimension space point {"language": "de"}
    Then I expect node aggregate identifier "ch-only" to lead to node cs-identifier;ch-only;{"language": "de"}
    Then I expect this node to have the following references:
      | Key               | Value                                                      |
      | referenceProperty | ["cs-identifier;anthony-destinode;{\"language\": \"de\"}"] |
    Then I expect node aggregate identifier "anthony-destinode" to lead to node cs-identifier;anthony-destinode;{"language": "de"}
    And I expect this node to be referenced by:
      | Key               | Value                                            |
      | referenceProperty | ["cs-identifier;ch-only;{\"language\": \"de\"}"] |

    # the reference must also exist on the non-touched node
    When I am in content stream "cs-identifier" and dimension space point {"language": "ch"}
    Then I expect node aggregate identifier "ch-only" to lead to node cs-identifier;ch-only;{"language": "ch"}
    Then I expect this node to have the following references:
      | Key               | Value                                                      |
      | referenceProperty | ["cs-identifier;anthony-destinode;{\"language\": \"de\"}"] |
    Then I expect node aggregate identifier "anthony-destinode" to lead to node cs-identifier;anthony-destinode;{"language": "de"}
    And I expect this node to be referenced by:
      | Key               | Value                                            |
      | referenceProperty | ["cs-identifier;ch-only;{\"language\": \"ch\"}"] |

