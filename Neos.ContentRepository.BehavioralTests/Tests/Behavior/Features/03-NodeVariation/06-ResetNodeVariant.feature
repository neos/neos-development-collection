@fixtures @adapters=DoctrineDBAL,Postgres
Feature: Reset a node variant

  As a user of the CR I want to reset a variant to its closest generalization

  Background:
    Given I have the following content dimensions:
      | Identifier | Values      | Generalizations |
      | language   | en, de, gsw | gsw->de->en     |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:Document': []
    'Neos.ContentRepository.Testing:Tethered': []
    'Neos.ContentRepository.Testing:TetheringDocument':
      childNodes:
        tethered:
          type: 'Neos.ContentRepository.Testing:Tethered'
    """
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value                |
      | workspaceName              | "live"               |
      | workspaceTitle             | "Live"               |
      | workspaceDescription       | "The live workspace" |
      | newContentStreamIdentifier | "cs-identifier"      |
      | initiatingUserIdentifier   | "user-id"            |
    And I am in content stream "cs-identifier" and dimension space point {"language":"en"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                     | Value                         |
      | nodeAggregateIdentifier | "lady-eleonode-rootford"      |
      | nodeTypeName            | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateIdentifier    | nodeName | parentNodeAggregateIdentifier | nodeTypeName                                     | tetheredDescendantNodeAggregateIdentifiers |
      | nody-mc-nodeface           | document | lady-eleonode-rootford        | Neos.ContentRepository.Testing:TetheringDocument | {"tethered": "nodewyn-tetherton"}          |
      | sir-nodeward-nodington-iii | esquire  | nody-mc-nodeface              | Neos.ContentRepository.Testing:Document          | {}                                         |
    And the command CreateNodeVariant is executed with payload:
      | Key                     | Value              |
      | nodeAggregateIdentifier | "nody-mc-nodeface" |
      | sourceOrigin            | {"language":"en"}  |
      | targetOrigin            | {"language":"de"}  |
    And the graph projection is fully up to date
    And the command CreateNodeVariant is executed with payload:
      | Key                     | Value              |
      | nodeAggregateIdentifier | "nody-mc-nodeface" |
      | sourceOrigin            | {"language":"de"}  |
      | targetOrigin            | {"language":"gsw"} |
    And the graph projection is fully up to date
    And the command CreateNodeVariant is executed with payload:
      | Key                     | Value                        |
      | nodeAggregateIdentifier | "sir-nodeward-nodington-iii" |
      | sourceOrigin            | {"language":"en"}            |
      | targetOrigin            | {"language":"de"}            |
    And the graph projection is fully up to date
    And the command CreateNodeVariant is executed with payload:
      | Key                     | Value                        |
      | nodeAggregateIdentifier | "sir-nodeward-nodington-iii" |
      | sourceOrigin            | {"language":"de"}            |
      | targetOrigin            | {"language":"gsw"}           |
    And the graph projection is fully up to date

  Scenario: Reset a node variant to its origin
    When the command ResetNodeVariant is executed with payload:
      | Key                       | Value              |
      | contentStreamIdentifier   | "cs-identifier"    |
      | nodeAggregateIdentifier   | "nody-mc-nodeface" |
      | originDimensionSpacePoint | {"language":"gsw"} |
    Then I expect exactly 12 events to be published on stream "Neos.ContentRepository:ContentStream:cs-identifier"
    And event at index 11 is of type "NodeVariantWasReset" with payload:
      | Key                     | Expected           |
      | contentStreamIdentifier | "cs-identifier"    |
      | nodeAggregateIdentifier | "nody-mc-nodeface" |
      | sourceOrigin            | {"language":"gsw"} |
      | generalizationOrigin    | {"language":"de"}  |
    When the graph projection is fully up to date
    Then I expect the graph projection to consist of exactly 8 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"language":"de"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodewyn-tetherton;{"language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodewyn-tetherton;{"language":"de"} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-nodeward-nodington-iii;{"language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-nodeward-nodington-iii;{"language":"de"} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-nodeward-nodington-iii;{"language":"gsw"} to exist in the content graph

    And I expect the node aggregate "lady-eleonode-rootford" to exist
    And I expect this node aggregate to occupy dimension space points [{}]
    And I expect this node aggregate to cover dimension space points [{"language":"en"},{"language":"de"},{"language":"gsw"}]

    And I expect the node aggregate "nody-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"language":"en"},{"language":"de"}]
    And I expect this node aggregate to cover dimension space points [{"language":"en"},{"language":"de"},{"language":"gsw"}]

    And I expect the node aggregate "nodewyn-tetherton" to exist
    And I expect this node aggregate to occupy dimension space points [{"language":"en"},{"language":"de"}]
    And I expect this node aggregate to cover dimension space points [{"language":"en"},{"language":"de"},{"language":"gsw"}]

    And I expect the node aggregate "sir-nodeward-nodington-iii" to exist
    And I expect this node aggregate to occupy dimension space points [{"language":"en"},{"language":"de"},{"language":"gsw"}]
    And I expect this node aggregate to cover dimension space points [{"language":"en"},{"language":"de"},{"language":"gsw"}]

    # Check the generalization
    When I am in dimension space point {"language":"en"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                |
      | document | cs-identifier;nody-mc-nodeface;{"language":"en"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier    |
      | 0     | lady-eleonode-rootford     |
      | 1     | nody-mc-nodeface           |
      | 2     | nodewyn-tetherton          |
      | 2     | sir-nodeward-nodington-iii |

    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to node cs-identifier;nody-mc-nodeface;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                          |
      | tethered | cs-identifier;nodewyn-tetherton;{"language":"en"}          |
      | esquire  | cs-identifier;sir-nodeward-nodington-iii;{"language":"en"} |

    And I expect node aggregate identifier "nodewyn-tetherton" and node path "document/tethered" to lead to node cs-identifier;nodewyn-tetherton;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"language":"en"}

    And I expect node aggregate identifier "sir-nodeward-nodington-iii" and node path "document/esquire" to lead to node cs-identifier;sir-nodeward-nodington-iii;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"language":"en"}

    # Check the selected variant
    When I am in dimension space point {"language":"de"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                |
      | document | cs-identifier;nody-mc-nodeface;{"language":"de"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier    |
      | 0     | lady-eleonode-rootford     |
      | 1     | nody-mc-nodeface           |
      | 2     | nodewyn-tetherton          |
      | 2     | sir-nodeward-nodington-iii |

    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to node cs-identifier;nody-mc-nodeface;{"language":"de"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                          |
      | tethered | cs-identifier;nodewyn-tetherton;{"language":"de"}          |
      | esquire  | cs-identifier;sir-nodeward-nodington-iii;{"language":"de"} |

    And I expect node aggregate identifier "nodewyn-tetherton" and node path "document/tethered" to lead to node cs-identifier;nodewyn-tetherton;{"language":"de"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"language":"de"}

    And I expect node aggregate identifier "sir-nodeward-nodington-iii" and node path "document/esquire" to lead to node cs-identifier;sir-nodeward-nodington-iii;{"language":"de"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"language":"de"}

    # Check the reset variant
    When I am in dimension space point {"language":"gsw"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                |
      | document | cs-identifier;nody-mc-nodeface;{"language":"de"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 2 levels deep should be:
      | Level | NodeAggregateIdentifier    |
      | 0     | lady-eleonode-rootford     |
      | 1     | nody-mc-nodeface           |
      | 2     | nodewyn-tetherton          |
      | 2     | sir-nodeward-nodington-iii |

    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to node cs-identifier;nody-mc-nodeface;{"language":"de"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                           |
      | tethered | cs-identifier;nodewyn-tetherton;{"language":"de"}           |
      | esquire  | cs-identifier;sir-nodeward-nodington-iii;{"language":"gsw"} |

    And I expect node aggregate identifier "nodewyn-tetherton" and node path "document/tethered" to lead to node cs-identifier;nodewyn-tetherton;{"language":"de"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"language":"de"}

    And I expect node aggregate identifier "sir-nodeward-nodington-iii" and node path "document/esquire" to lead to node cs-identifier;sir-nodeward-nodington-iii;{"language":"gsw"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"language":"de"}
