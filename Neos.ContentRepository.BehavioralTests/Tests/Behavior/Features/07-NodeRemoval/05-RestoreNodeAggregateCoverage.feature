@fixtures @adapters=DoctrineDBAL,Postgres
Feature: Restore NodeAggregate coverage

  As a user of the CR I want to be able to restore coverage of a NodeAggregate or parts of it.

  Background:
    Given I have the following content dimensions:
      | Identifier | Values      | Generalizations |
      | language   | en, de, gsw | gsw->de->en     |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:Document': []
    'Neos.ContentRepository.Testing:ReferencingDocument':
      properties:
        references:
          type: 'references'
    'Neos.ContentRepository.Testing:Tethered': []
    'Neos.ContentRepository.Testing:DocumentWithTetheredChildrenAndGrandChildren':
      childNodes:
        tethered:
          type: 'Neos.ContentRepository.Testing:DocumentWithTetheredChildren'
        anothertethered:
          type: 'Neos.ContentRepository.Testing:Tethered'
    'Neos.ContentRepository.Testing:DocumentWithTetheredChildren':
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
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and dimension space point {"language":"en"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                     | Value                         |
      | nodeAggregateIdentifier | "lady-eleonode-rootford"      |
      | nodeTypeName            | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateIdentifier    | nodeTypeName                                                                | parentNodeAggregateIdentifier | nodeName | tetheredDescendantNodeAggregateIdentifiers                                                                       |
      | sir-david-nodenborough     | Neos.ContentRepository.Testing:Document                                     | lady-eleonode-rootford        | document | {}                                                                                                               |
      | nody-mc-nodeface           | Neos.ContentRepository.Testing:DocumentWithTetheredChildrenAndGrandChildren | sir-david-nodenborough        | document | {"tethered":"nodewyn-tetherton", "tethered/tethered": "nodimus-mediocre", "anothertethered":"nodimer-tetherton"} |
      | sir-nodeward-nodington-iii | Neos.ContentRepository.Testing:ReferencingDocument                          | nody-mc-nodeface              | esquire  | {}                                                                                                               |
      | nodimus-prime              | Neos.ContentRepository.Testing:ReferencingDocument                          | sir-nodeward-nodington-iii    | prime    | {}                                                                                                               |
    And the command SetNodeReferences is executed with payload:
      | Key                           | Value                                  |
      | sourceNodeAggregateIdentifier | "sir-nodeward-nodington-iii"           |
      | referenceName                 | "references"                           |
      | references                    | [{"target": "sir-david-nodenborough"}] |
    And the graph projection is fully up to date
    And the command CreateNodeVariant is executed with payload:
      | Key                     | Value                    |
      | nodeAggregateIdentifier | "sir-david-nodenborough" |
      | sourceOrigin            | {"language":"en"}        |
      | targetOrigin            | {"language":"de"}        |
    And the graph projection is fully up to date
    And the command RemoveNodeAggregate is executed with payload:
      | Key                          | Value                |
      | nodeAggregateIdentifier      | "nody-mc-nodeface"   |
      | coveredDimensionSpacePoint   | {"language":"de"}    |
      | nodeVariantSelectionStrategy | "allSpecializations" |
    And the graph projection is fully up to date

  # Hint: we don't need to check restoration using a non-occupied origin since that is forbidden by the command handler

  Scenario: Restore node aggregate coverage without specializations and not recursive
    When the command RestoreNodeAggregateCoverage is executed with payload:
      | Key                        | Value              |
      | nodeAggregateIdentifier    | "nody-mc-nodeface" |
      | dimensionSpacePointToCover | {"language":"de"}  |
      | withSpecializations        | false              |
      | recursive                  | false              |
    Then I expect exactly 13 events to be published on stream with prefix "Neos.ContentRepository:ContentStream:cs-identifier"
    And event at index 12 is of type "NodeAggregateCoverageWasRestored" with payload:
      | Key                                 | Expected                     |
      | contentStreamIdentifier             | "cs-identifier"              |
      | nodeAggregateIdentifier             | "nody-mc-nodeface"           |
      | originDimensionSpacePoint           | {"language":"en"}            |
      | affectedCoveredDimensionSpacePoints | [{"language":"de"}]          |
      | recursive                           | false                        |
      | initiatingUserIdentifier            | "initiating-user-identifier" |
    When the graph projection is fully up to date
    Then I expect the graph projection to consist of exactly 9 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{"language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{"language":"de"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodewyn-tetherton;{"language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimus-mediocre;{"language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimer-tetherton;{"language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-nodeward-nodington-iii;{"language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimus-prime;{"language":"en"} to exist in the content graph
    And I expect the node aggregate "sir-david-nodenborough" to exist
    And I expect this node aggregate to cover dimension space points [{"language":"en"},{"language":"de"},{"language":"gsw"}]
    And I expect the node aggregate "nody-mc-nodeface" to exist
    And I expect this node aggregate to cover dimension space points [{"language":"en"},{"language":"de"}]
    And I expect the node aggregate "nodewyn-tetherton" to exist
    And I expect this node aggregate to cover dimension space points [{"language":"en"},{"language":"de"}]
    And I expect the node aggregate "nodimus-mediocre" to exist
    And I expect this node aggregate to cover dimension space points [{"language":"en"},{"language":"de"}]
    And I expect the node aggregate "nodimer-tetherton" to exist
    And I expect this node aggregate to cover dimension space points [{"language":"en"},{"language":"de"}]
    And I expect the node aggregate "sir-nodeward-nodington-iii" to exist
    And I expect this node aggregate to cover dimension space points [{"language":"en"}]
    And I expect the node aggregate "nodimus-prime" to exist
    And I expect this node aggregate to cover dimension space points [{"language":"en"}]

    # Check the selected variant
    When I am in dimension space point {"language":"de"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                      |
      | document | cs-identifier;sir-david-nodenborough;{"language":"de"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 4 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | sir-david-nodenborough  |
      | 2     | nody-mc-nodeface        |
      | 3     | nodewyn-tetherton       |
      | 4     | nodimus-mediocre        |
      | 3     | nodimer-tetherton       |

    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"language":"de"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                |
      | document | cs-identifier;nody-mc-nodeface;{"language":"en"} |
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/document" to lead to node cs-identifier;nody-mc-nodeface;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language":"de"}
    And I expect this node to have the following child nodes:
      | Name            | NodeDiscriminator                                 |
      | tethered        | cs-identifier;nodewyn-tetherton;{"language":"en"} |
      | anothertethered | cs-identifier;nodimer-tetherton;{"language":"en"} |
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nodewyn-tetherton" and node path "document/document/tethered" to lead to node cs-identifier;nodewyn-tetherton;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"language":"en"}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                |
      | tethered | cs-identifier;nodimus-mediocre;{"language":"en"} |
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nodimus-mediocre" and node path "document/document/tethered/tethered" to lead to node cs-identifier;nodimus-mediocre;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;nodewyn-tetherton;{"language":"en"}
    And I expect this node to have no child nodes
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nodimer-tetherton" and node path "document/document/anothertethered" to lead to node cs-identifier;nodimer-tetherton;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"language":"en"}
    And I expect this node to have no child nodes
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "sir-nodeward-nodington-iii" and node path "document/document/esquire" to lead to no node
    And I expect node aggregate identifier "nodimus-prime" and node path "document/document/esquire/prime" to lead to no node

    # Check the specialization
    When I am in dimension space point {"language":"gsw"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                      |
      | document | cs-identifier;sir-david-nodenborough;{"language":"de"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 4 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | sir-david-nodenborough  |

    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"language":"de"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no child nodes

    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/document" to lead to no node
    And I expect node aggregate identifier "nodewyn-tetherton" and node path "document/document/tethered" to lead to no node
    And I expect node aggregate identifier "nodimus-mediocre" and node path "document/document/tethered/tethered" to lead to no node
    And I expect node aggregate identifier "nodimer-tetherton" and node path "document/document/anothertethered" to lead to no node
    And I expect node aggregate identifier "sir-nodeward-nodington-iii" and node path "document/document/esquire" to lead to no node
    And I expect node aggregate identifier "nodimus-prime" and node path "document/document/esquire/prime" to lead to no node

  Scenario: Restore node aggregate coverage with specializations but not recursive
    When the command RestoreNodeAggregateCoverage is executed with payload:
      | Key                        | Value              |
      | nodeAggregateIdentifier    | "nody-mc-nodeface" |
      | dimensionSpacePointToCover | {"language":"de"}  |
      | withSpecializations        | true               |
      | recursive                  | false              |
    Then I expect exactly 13 events to be published on stream with prefix "Neos.ContentRepository:ContentStream:cs-identifier"
    And event at index 12 is of type "NodeAggregateCoverageWasRestored" with payload:
      | Key                                 | Expected                               |
      | contentStreamIdentifier             | "cs-identifier"                        |
      | nodeAggregateIdentifier             | "nody-mc-nodeface"                     |
      | originDimensionSpacePoint           | {"language":"en"}                      |
      | affectedCoveredDimensionSpacePoints | [{"language":"de"},{"language":"gsw"}] |
      | recursive                           | false                                  |
      | initiatingUserIdentifier            | "initiating-user-identifier"           |
    When the graph projection is fully up to date
    Then I expect the graph projection to consist of exactly 9 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{"language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{"language":"de"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodewyn-tetherton;{"language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimus-mediocre;{"language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimer-tetherton;{"language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-nodeward-nodington-iii;{"language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimus-prime;{"language":"en"} to exist in the content graph
    And I expect the node aggregate "sir-david-nodenborough" to exist
    And I expect this node aggregate to cover dimension space points [{"language":"en"},{"language":"de"},{"language":"gsw"}]
    And I expect the node aggregate "nody-mc-nodeface" to exist
    And I expect this node aggregate to cover dimension space points [{"language":"en"},{"language":"de"},{"language":"gsw"}]
    And I expect the node aggregate "nodewyn-tetherton" to exist
    And I expect this node aggregate to cover dimension space points [{"language":"en"},{"language":"de"},{"language":"gsw"}]
    And I expect the node aggregate "nodimus-mediocre" to exist
    And I expect this node aggregate to cover dimension space points [{"language":"en"},{"language":"de"},{"language":"gsw"}]
    And I expect the node aggregate "nodimer-tetherton" to exist
    And I expect this node aggregate to cover dimension space points [{"language":"en"},{"language":"de"},{"language":"gsw"}]
    And I expect the node aggregate "sir-nodeward-nodington-iii" to exist
    And I expect this node aggregate to cover dimension space points [{"language":"en"}]
    And I expect the node aggregate "nodimus-prime" to exist
    And I expect this node aggregate to cover dimension space points [{"language":"en"}]

    # Check the selected variant
    When I am in dimension space point {"language":"de"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                      |
      | document | cs-identifier;sir-david-nodenborough;{"language":"de"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 4 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | sir-david-nodenborough  |
      | 2     | nody-mc-nodeface        |
      | 3     | nodewyn-tetherton       |
      | 4     | nodimus-mediocre        |
      | 3     | nodimer-tetherton       |

    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"language":"de"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                |
      | document | cs-identifier;nody-mc-nodeface;{"language":"en"} |
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/document" to lead to node cs-identifier;nody-mc-nodeface;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language":"de"}
    And I expect this node to have the following child nodes:
      | Name            | NodeDiscriminator                                 |
      | tethered        | cs-identifier;nodewyn-tetherton;{"language":"en"} |
      | anothertethered | cs-identifier;nodimer-tetherton;{"language":"en"} |
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nodewyn-tetherton" and node path "document/document/tethered" to lead to node cs-identifier;nodewyn-tetherton;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"language":"en"}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                |
      | tethered | cs-identifier;nodimus-mediocre;{"language":"en"} |
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nodimus-mediocre" and node path "document/document/tethered/tethered" to lead to node cs-identifier;nodimus-mediocre;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;nodewyn-tetherton;{"language":"en"}
    And I expect this node to have no child nodes
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nodimer-tetherton" and node path "document/document/anothertethered" to lead to node cs-identifier;nodimer-tetherton;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"language":"en"}
    And I expect this node to have no child nodes
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "sir-nodeward-nodington-iii" and node path "document/document/esquire" to lead to no node
    And I expect node aggregate identifier "nodimus-prime" and node path "document/document/esquire/prime" to lead to no node

    # Check the specialization
    When I am in dimension space point {"language":"gsw"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                      |
      | document | cs-identifier;sir-david-nodenborough;{"language":"de"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 4 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | sir-david-nodenborough  |
      | 2     | nody-mc-nodeface        |
      | 3     | nodewyn-tetherton       |
      | 4     | nodimus-mediocre        |
      | 3     | nodimer-tetherton       |

    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"language":"de"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                |
      | document | cs-identifier;nody-mc-nodeface;{"language":"en"} |
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/document" to lead to node cs-identifier;nody-mc-nodeface;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language":"de"}
    And I expect this node to have the following child nodes:
      | Name            | NodeDiscriminator                                 |
      | tethered        | cs-identifier;nodewyn-tetherton;{"language":"en"} |
      | anothertethered | cs-identifier;nodimer-tetherton;{"language":"en"} |
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nodewyn-tetherton" and node path "document/document/tethered" to lead to node cs-identifier;nodewyn-tetherton;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"language":"en"}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                |
      | tethered | cs-identifier;nodimus-mediocre;{"language":"en"} |
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nodimus-mediocre" and node path "document/document/tethered/tethered" to lead to node cs-identifier;nodimus-mediocre;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;nodewyn-tetherton;{"language":"en"}
    And I expect this node to have no child nodes
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nodimer-tetherton" and node path "document/document/anothertethered" to lead to node cs-identifier;nodimer-tetherton;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"language":"en"}
    And I expect this node to have no child nodes
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "sir-nodeward-nodington-iii" and node path "document/document/esquire" to lead to no node
    And I expect node aggregate identifier "nodimus-prime" and node path "document/document/esquire/prime" to lead to no node

  Scenario: Restore node aggregate coverage without specializations but recursive
    When the command RestoreNodeAggregateCoverage is executed with payload:
      | Key                        | Value              |
      | nodeAggregateIdentifier    | "nody-mc-nodeface" |
      | dimensionSpacePointToCover | {"language":"de"}  |
      | withSpecializations        | false              |
      | recursive                  | true               |
    Then I expect exactly 13 events to be published on stream with prefix "Neos.ContentRepository:ContentStream:cs-identifier"
    And event at index 12 is of type "NodeAggregateCoverageWasRestored" with payload:
      | Key                                 | Expected                     |
      | contentStreamIdentifier             | "cs-identifier"              |
      | nodeAggregateIdentifier             | "nody-mc-nodeface"           |
      | originDimensionSpacePoint           | {"language":"en"}            |
      | affectedCoveredDimensionSpacePoints | [{"language":"de"}]          |
      | recursive                           | true                         |
      | initiatingUserIdentifier            | "initiating-user-identifier" |
    When the graph projection is fully up to date
    Then I expect the graph projection to consist of exactly 9 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{"language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{"language":"de"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodewyn-tetherton;{"language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimus-mediocre;{"language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimer-tetherton;{"language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-nodeward-nodington-iii;{"language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimus-prime;{"language":"en"} to exist in the content graph
    And I expect the node aggregate "sir-david-nodenborough" to exist
    And I expect this node aggregate to cover dimension space points [{"language":"en"},{"language":"de"},{"language":"gsw"}]
    And I expect the node aggregate "nody-mc-nodeface" to exist
    And I expect this node aggregate to cover dimension space points [{"language":"en"},{"language":"de"}]
    And I expect the node aggregate "nodewyn-tetherton" to exist
    And I expect this node aggregate to cover dimension space points [{"language":"en"},{"language":"de"}]
    And I expect the node aggregate "nodimus-mediocre" to exist
    And I expect this node aggregate to cover dimension space points [{"language":"en"},{"language":"de"}]
    And I expect the node aggregate "nodimer-tetherton" to exist
    And I expect this node aggregate to cover dimension space points [{"language":"en"},{"language":"de"}]
    And I expect the node aggregate "sir-nodeward-nodington-iii" to exist
    And I expect this node aggregate to cover dimension space points [{"language":"en"},{"language":"de"}]
    And I expect the node aggregate "nodimus-prime" to exist
    And I expect this node aggregate to cover dimension space points [{"language":"en"},{"language":"de"}]

    # Check the selected variant
    When I am in dimension space point {"language":"de"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                      |
      | document | cs-identifier;sir-david-nodenborough;{"language":"de"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 4 levels deep should be:
      | Level | NodeAggregateIdentifier    |
      | 0     | lady-eleonode-rootford     |
      | 1     | sir-david-nodenborough     |
      | 2     | nody-mc-nodeface           |
      | 3     | nodewyn-tetherton          |
      | 4     | nodimus-mediocre           |
      | 3     | nodimer-tetherton          |
      | 3     | sir-nodeward-nodington-iii |
      | 4     | nodimus-prime              |

    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"language":"de"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                |
      | document | cs-identifier;nody-mc-nodeface;{"language":"en"} |
    And I expect this node to have no references
    And I expect this node to be referenced by:
      | Name       | Node                                                       | Properties |
      | references | cs-identifier;sir-nodeward-nodington-iii;{"language":"en"} | null       |

    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/document" to lead to node cs-identifier;nody-mc-nodeface;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language":"de"}
    And I expect this node to have the following child nodes:
      | Name            | NodeDiscriminator                                          |
      | tethered        | cs-identifier;nodewyn-tetherton;{"language":"en"}          |
      | anothertethered | cs-identifier;nodimer-tetherton;{"language":"en"}          |
      | esquire         | cs-identifier;sir-nodeward-nodington-iii;{"language":"en"} |
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nodewyn-tetherton" and node path "document/document/tethered" to lead to node cs-identifier;nodewyn-tetherton;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"language":"en"}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                |
      | tethered | cs-identifier;nodimus-mediocre;{"language":"en"} |
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nodimus-mediocre" and node path "document/document/tethered/tethered" to lead to node cs-identifier;nodimus-mediocre;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;nodewyn-tetherton;{"language":"en"}
    And I expect this node to have no child nodes
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nodimer-tetherton" and node path "document/document/anothertethered" to lead to node cs-identifier;nodimer-tetherton;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"language":"en"}
    And I expect this node to have no child nodes
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "sir-nodeward-nodington-iii" and node path "document/document/esquire" to lead to node cs-identifier;sir-nodeward-nodington-iii;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"language":"en"}
    And I expect this node to have the following child nodes:
      | Name  | NodeDiscriminator                             |
      | prime | cs-identifier;nodimus-prime;{"language":"en"} |
    And I expect this node to have the following references:
      | Name       | Node                                                    | Properties |
      | references | cs-identifier;sir-david-nodenborough;{"language": "de"} | null       |
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nodimus-prime" and node path "document/document/esquire/prime" to lead to node cs-identifier;nodimus-prime;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"language":"en"}
    And I expect this node to have no child nodes
    And I expect this node to have no references
    And I expect this node to not be referenced

    # Check the specialization
    When I am in dimension space point {"language":"gsw"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                      |
      | document | cs-identifier;sir-david-nodenborough;{"language":"de"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 4 levels deep should be:
      | Level | NodeAggregateIdentifier |
      | 0     | lady-eleonode-rootford  |
      | 1     | sir-david-nodenborough  |

    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"language":"de"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have no child nodes

    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/document" to lead to no node
    And I expect node aggregate identifier "nodewyn-tetherton" and node path "document/document/tethered" to lead to no node
    And I expect node aggregate identifier "nodimus-mediocre" and node path "document/document/tethered/tethered" to lead to no node
    And I expect node aggregate identifier "nodimer-tetherton" and node path "document/document/anothertethered" to lead to no node
    And I expect node aggregate identifier "sir-nodeward-nodington-iii" and node path "document/document/esquire" to lead to no node
    And I expect node aggregate identifier "nodimus-prime" and node path "document/document/esquire/prime" to lead to no node

  Scenario: Restore node aggregate coverage with specializations and recursive
    When the command RestoreNodeAggregateCoverage is executed with payload:
      | Key                        | Value              |
      | nodeAggregateIdentifier    | "nody-mc-nodeface" |
      | dimensionSpacePointToCover | {"language":"de"}  |
      | withSpecializations        | true               |
      | recursive                  | true               |
    Then I expect exactly 13 events to be published on stream with prefix "Neos.ContentRepository:ContentStream:cs-identifier"
    And event at index 12 is of type "NodeAggregateCoverageWasRestored" with payload:
      | Key                                 | Expected                     |
      | contentStreamIdentifier             | "cs-identifier"              |
      | nodeAggregateIdentifier             | "nody-mc-nodeface"           |
      | originDimensionSpacePoint           | {"language":"en"}            |
      | affectedCoveredDimensionSpacePoints | [{"language":"de"},{"language":"gsw"}]          |
      | recursive                           | true                         |
      | initiatingUserIdentifier            | "initiating-user-identifier" |
    When the graph projection is fully up to date
    Then I expect the graph projection to consist of exactly 9 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{"language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{"language":"de"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodewyn-tetherton;{"language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimus-mediocre;{"language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimer-tetherton;{"language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-nodeward-nodington-iii;{"language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimus-prime;{"language":"en"} to exist in the content graph
    And I expect the node aggregate "sir-david-nodenborough" to exist
    And I expect this node aggregate to cover dimension space points [{"language":"en"},{"language":"de"},{"language":"gsw"}]
    And I expect the node aggregate "nody-mc-nodeface" to exist
    And I expect this node aggregate to cover dimension space points [{"language":"en"},{"language":"de"},{"language":"gsw"}]
    And I expect the node aggregate "nodewyn-tetherton" to exist
    And I expect this node aggregate to cover dimension space points [{"language":"en"},{"language":"de"},{"language":"gsw"}]
    And I expect the node aggregate "nodimus-mediocre" to exist
    And I expect this node aggregate to cover dimension space points [{"language":"en"},{"language":"de"},{"language":"gsw"}]
    And I expect the node aggregate "nodimer-tetherton" to exist
    And I expect this node aggregate to cover dimension space points [{"language":"en"},{"language":"de"},{"language":"gsw"}]
    And I expect the node aggregate "sir-nodeward-nodington-iii" to exist
    And I expect this node aggregate to cover dimension space points [{"language":"en"},{"language":"de"},{"language":"gsw"}]
    And I expect the node aggregate "nodimus-prime" to exist
    And I expect this node aggregate to cover dimension space points [{"language":"en"},{"language":"de"},{"language":"gsw"}]

    # Check the selected variant
    When I am in dimension space point {"language":"de"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                      |
      | document | cs-identifier;sir-david-nodenborough;{"language":"de"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 4 levels deep should be:
      | Level | NodeAggregateIdentifier    |
      | 0     | lady-eleonode-rootford     |
      | 1     | sir-david-nodenborough     |
      | 2     | nody-mc-nodeface           |
      | 3     | nodewyn-tetherton          |
      | 4     | nodimus-mediocre           |
      | 3     | nodimer-tetherton          |
      | 3     | sir-nodeward-nodington-iii |
      | 4     | nodimus-prime              |

    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"language":"de"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                |
      | document | cs-identifier;nody-mc-nodeface;{"language":"en"} |
    And I expect this node to have no references
    And I expect this node to be referenced by:
      | Name       | Node                                                       | Properties |
      | references | cs-identifier;sir-nodeward-nodington-iii;{"language":"en"} | null       |

    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/document" to lead to node cs-identifier;nody-mc-nodeface;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language":"de"}
    And I expect this node to have the following child nodes:
      | Name            | NodeDiscriminator                                          |
      | tethered        | cs-identifier;nodewyn-tetherton;{"language":"en"}          |
      | anothertethered | cs-identifier;nodimer-tetherton;{"language":"en"}          |
      | esquire         | cs-identifier;sir-nodeward-nodington-iii;{"language":"en"} |
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nodewyn-tetherton" and node path "document/document/tethered" to lead to node cs-identifier;nodewyn-tetherton;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"language":"en"}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                |
      | tethered | cs-identifier;nodimus-mediocre;{"language":"en"} |
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nodimus-mediocre" and node path "document/document/tethered/tethered" to lead to node cs-identifier;nodimus-mediocre;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;nodewyn-tetherton;{"language":"en"}
    And I expect this node to have no child nodes
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nodimer-tetherton" and node path "document/document/anothertethered" to lead to node cs-identifier;nodimer-tetherton;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"language":"en"}
    And I expect this node to have no child nodes
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "sir-nodeward-nodington-iii" and node path "document/document/esquire" to lead to node cs-identifier;sir-nodeward-nodington-iii;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"language":"en"}
    And I expect this node to have the following child nodes:
      | Name  | NodeDiscriminator                             |
      | prime | cs-identifier;nodimus-prime;{"language":"en"} |
    And I expect this node to have the following references:
      | Name       | Node                                                    | Properties |
      | references | cs-identifier;sir-david-nodenborough;{"language": "de"} | null       |
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nodimus-prime" and node path "document/document/esquire/prime" to lead to node cs-identifier;nodimus-prime;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"language":"en"}
    And I expect this node to have no child nodes
    And I expect this node to have no references
    And I expect this node to not be referenced

    # Check the specialization
    When I am in dimension space point {"language":"gsw"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                      |
      | document | cs-identifier;sir-david-nodenborough;{"language":"de"} |
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 4 levels deep should be:
      | Level | NodeAggregateIdentifier    |
      | 0     | lady-eleonode-rootford     |
      | 1     | sir-david-nodenborough     |
      | 2     | nody-mc-nodeface           |
      | 3     | nodewyn-tetherton          |
      | 4     | nodimus-mediocre           |
      | 3     | nodimer-tetherton          |
      | 3     | sir-nodeward-nodington-iii |
      | 4     | nodimus-prime              |

    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"language":"de"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                |
      | document | cs-identifier;nody-mc-nodeface;{"language":"en"} |
    And I expect this node to have no references
    And I expect this node to be referenced by:
      | Name       | Node                                                       | Properties |
      | references | cs-identifier;sir-nodeward-nodington-iii;{"language":"en"} | null       |

    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/document" to lead to node cs-identifier;nody-mc-nodeface;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"language":"de"}
    And I expect this node to have the following child nodes:
      | Name            | NodeDiscriminator                                          |
      | tethered        | cs-identifier;nodewyn-tetherton;{"language":"en"}          |
      | anothertethered | cs-identifier;nodimer-tetherton;{"language":"en"}          |
      | esquire         | cs-identifier;sir-nodeward-nodington-iii;{"language":"en"} |
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nodewyn-tetherton" and node path "document/document/tethered" to lead to node cs-identifier;nodewyn-tetherton;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"language":"en"}
    And I expect this node to have the following child nodes:
      | Name     | NodeDiscriminator                                |
      | tethered | cs-identifier;nodimus-mediocre;{"language":"en"} |
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nodimus-mediocre" and node path "document/document/tethered/tethered" to lead to node cs-identifier;nodimus-mediocre;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;nodewyn-tetherton;{"language":"en"}
    And I expect this node to have no child nodes
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nodimer-tetherton" and node path "document/document/anothertethered" to lead to node cs-identifier;nodimer-tetherton;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"language":"en"}
    And I expect this node to have no child nodes
    And I expect this node to have no references
    And I expect this node to not be referenced

    And I expect node aggregate identifier "sir-nodeward-nodington-iii" and node path "document/document/esquire" to lead to node cs-identifier;sir-nodeward-nodington-iii;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;nody-mc-nodeface;{"language":"en"}
    And I expect this node to have the following child nodes:
      | Name  | NodeDiscriminator                             |
      | prime | cs-identifier;nodimus-prime;{"language":"en"} |
    And I expect this node to have the following references:
      | Name       | Node                                                    | Properties |
      | references | cs-identifier;sir-david-nodenborough;{"language": "de"} | null       |
    And I expect this node to not be referenced

    And I expect node aggregate identifier "nodimus-prime" and node path "document/document/esquire/prime" to lead to node cs-identifier;nodimus-prime;{"language":"en"}
    And I expect this node to be a child of node cs-identifier;sir-nodeward-nodington-iii;{"language":"en"}
    And I expect this node to have no child nodes
    And I expect this node to have no references
    And I expect this node to not be referenced
