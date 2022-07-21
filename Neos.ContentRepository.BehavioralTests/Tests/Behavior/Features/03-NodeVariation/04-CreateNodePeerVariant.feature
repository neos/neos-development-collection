@fixtures @adapters=DoctrineDBAL,Postgres
Feature: Create node peer variant

  As a user of the CR I want to create a copy of a node within an aggregate to a peer dimension space point, i.e. one that is neither a generalization nor a specialization.

  Background:
    Given I have the following content dimensions:
      | Identifier | Values          | Generalizations |
      | market     | DE, CH          | CH->DE          |
      | language   | en, de, fr, gsw | gsw->de->en     |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
    'Neos.ContentRepository.Testing:Tethered': []
    'Neos.ContentRepository.Testing:TetheredDocument':
      childNodes:
        tethered:
          type: 'Neos.ContentRepository.Testing:Tethered'
    'Neos.ContentRepository.Testing:Document':
      childNodes:
        tethered-document:
          type: 'Neos.ContentRepository.Testing:TetheredDocument'
    'Neos.ContentRepository.Testing:DocumentWithoutTetheredChildren': []
    """
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value                |
      | workspaceName              | "live"               |
      | workspaceTitle             | "Live"               |
      | workspaceDescription       | "The live workspace" |
      | newContentStreamIdentifier | "cs-identifier"      |
      | initiatingUserIdentifier   | "user-id"            |
    And I am in content stream "cs-identifier" and dimension space point {"market":"DE", "language":"en"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                     | Value                         |
      | nodeAggregateIdentifier | "lady-eleonode-rootford"      |
      | nodeTypeName            | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateIdentifier | originDimensionSpacePoint        | nodeName            | parentNodeAggregateIdentifier | nodeTypeName                                                   | tetheredDescendantNodeAggregateIdentifiers                                               |
    # We have to add another node since root nodes have no origin dimension space points and thus cannot be varied.
    # We also need a tethered child node to test that it is reachable from the freshly created peer variant of the parent
    # and we need a tethered child node of the tethered child node to test that this works recursively
      | sir-david-nodenborough  | {"market":"DE", "language":"en"} | document            | lady-eleonode-rootford        | Neos.ContentRepository.Testing:Document                        | {"tethered-document": "nodimus-prime", "tethered-document/tethered": "nodimus-mediocre"} |
    # and we need a non-tethered child node to make sure it is _not_ reachable from the freshly created peer variant of the parent
      | nody-mc-nodeface        | {"market":"DE", "language":"en"} | child-document      | sir-david-nodenborough        | Neos.ContentRepository.Testing:DocumentWithoutTetheredChildren | {}                                                                                       |
    # We have to add another node as a peer.
    # We also need a tethered child node to test that it is reachable from the freshly created peer variant of the parent
    # and we need a tethered child node of the tethered child node to test that this works recursively
      | madame-lanode           | {"market":"CH", "language":"fr"} | peer-document       | lady-eleonode-rootford        | Neos.ContentRepository.Testing:Document                        | {"tethered-document": "nodesis-prime", "tethered-document/tethered": "nodesis-mediocre"} |
    # and we need a non-tethered child node to make sure it is _not_ reachable from the freshly created peer variant of the parent
      | nodette                 | {"market":"CH", "language":"fr"} | peer-child-document | madame-lanode                 | Neos.ContentRepository.Testing:DocumentWithoutTetheredChildren | {}                                                                                       |

  Scenario: Create peer variant of node to dimension space point without specializations
    When the command CreateNodeVariant is executed with payload:
      | Key                     | Value                            |
      | contentStreamIdentifier | "cs-identifier"                  |
      | nodeAggregateIdentifier | "sir-david-nodenborough"         |
      | sourceOrigin            | {"market":"DE", "language":"en"} |
      | targetOrigin            | {"market":"CH", "language":"fr"} |
    Then I expect exactly 13 events to be published on stream "Neos.ContentRepository:ContentStream:cs-identifier"
    And event at index 10 is of type "NodePeerVariantWasCreated" with payload:
      | Key                     | Expected                           |
      | contentStreamIdentifier | "cs-identifier"                    |
      | nodeAggregateIdentifier | "sir-david-nodenborough"           |
      | sourceOrigin            | {"market":"DE", "language":"en"}   |
      | peerOrigin              | {"market":"CH", "language":"fr"}   |
      | peerCoverage            | [{"market":"CH", "language":"fr"}] |
    And event at index 11 is of type "NodePeerVariantWasCreated" with payload:
      | Key                     | Expected                           |
      | contentStreamIdentifier | "cs-identifier"                    |
      | nodeAggregateIdentifier | "nodimus-prime"                    |
      | sourceOrigin            | {"market":"DE", "language":"en"}   |
      | peerOrigin              | {"market":"CH", "language":"fr"}   |
      | peerCoverage            | [{"market":"CH", "language":"fr"}] |
    And event at index 12 is of type "NodePeerVariantWasCreated" with payload:
      | Key                     | Expected                           |
      | contentStreamIdentifier | "cs-identifier"                    |
      | nodeAggregateIdentifier | "nodimus-mediocre"                 |
      | sourceOrigin            | {"market":"DE", "language":"en"}   |
      | peerOrigin              | {"market":"CH", "language":"fr"}   |
      | peerCoverage            | [{"market":"CH", "language":"fr"}] |

    When the graph projection is fully up to date
    Then I expect the graph projection to consist of exactly 12 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{"market":"CH", "language":"fr"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"market":"DE", "language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimus-prime;{"market":"DE", "language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimus-prime;{"market":"CH", "language":"fr"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimus-mediocre;{"market":"DE", "language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimus-mediocre;{"market":"CH", "language":"fr"} to exist in the content graph
    And I expect a node identified by cs-identifier;madame-lanode;{"market":"CH", "language":"fr"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodette;{"market":"CH", "language":"fr"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodesis-prime;{"market":"CH", "language":"fr"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodesis-mediocre;{"market":"CH", "language":"fr"} to exist in the content graph

    When I am in content stream "cs-identifier"
    Then I expect the node aggregate "lady-eleonode-rootford" to exist
    And I expect this node aggregate to occupy dimension space points [{}]
    And I expect this node aggregate to cover dimension space points [{"market":"DE", "language":"en"},{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"DE", "language":"fr"},{"market":"CH", "language":"en"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"},{"market":"CH", "language":"fr"}]

    And I expect the node aggregate "sir-david-nodenborough" to exist
    And I expect this node aggregate to occupy dimension space points [{"market":"DE", "language":"en"},{"market":"CH", "language":"fr"}]
    And I expect this node aggregate to cover dimension space points [{"market":"DE", "language":"en"},{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"en"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"},{"market":"CH", "language":"fr"}]

    And I expect the node aggregate "nody-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"market":"DE", "language":"en"}]
    And I expect this node aggregate to cover dimension space points [{"market":"DE", "language":"en"},{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"en"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"}]

    And I expect the node aggregate "nodimus-prime" to exist
    And I expect this node aggregate to occupy dimension space points [{"market":"DE", "language":"en"},{"market":"CH", "language":"fr"}]
    And I expect this node aggregate to cover dimension space points [{"market":"DE", "language":"en"},{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"en"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"},{"market":"CH", "language":"fr"}]

    And I expect the node aggregate "nodimus-mediocre" to exist
    And I expect this node aggregate to occupy dimension space points [{"market":"DE", "language":"en"},{"market":"CH", "language":"fr"}]
    And I expect this node aggregate to cover dimension space points [{"market":"DE", "language":"en"},{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"en"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"},{"market":"CH", "language":"fr"}]

    And I expect the node aggregate "madame-lanode" to exist
    And I expect this node aggregate to occupy dimension space points [{"market":"CH", "language":"fr"}]
    And I expect this node aggregate to cover dimension space points [{"market":"CH", "language":"fr"}]

    And I expect the node aggregate "nodette" to exist
    And I expect this node aggregate to occupy dimension space points [{"market":"CH", "language":"fr"}]
    And I expect this node aggregate to cover dimension space points [{"market":"CH", "language":"fr"}]

    And I expect the node aggregate "nodesis-prime" to exist
    And I expect this node aggregate to occupy dimension space points [{"market":"CH", "language":"fr"}]
    And I expect this node aggregate to cover dimension space points [{"market":"CH", "language":"fr"}]

    And I expect the node aggregate "nodesis-mediocre" to exist
    And I expect this node aggregate to occupy dimension space points [{"market":"CH", "language":"fr"}]
    And I expect this node aggregate to cover dimension space points [{"market":"CH", "language":"fr"}]

    When I am in content stream "cs-identifier" and dimension space point {"market":"DE", "language":"en"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nodimus-prime" and node path "document/tethered-document" to lead to node cs-identifier;nodimus-prime;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nodimus-mediocre" and node path "document/tethered-document/tethered" to lead to node cs-identifier;nodimus-mediocre;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "madame-lanode" and node path "peer-document" to lead to no node
    And I expect node aggregate identifier "nodette" and node path "peer-document/peer-child-document" to lead to no node
    And I expect node aggregate identifier "nodesis-prime" and node path "peer-document/tethered-document" to lead to no node
    And I expect node aggregate identifier "nodesis-mediocre" and node path "peer-document/tethered-document/tethered" to lead to no node

    When I am in content stream "cs-identifier" and dimension space point {"market":"DE", "language":"de"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nodimus-prime" and node path "document/tethered-document" to lead to node cs-identifier;nodimus-prime;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nodimus-mediocre" and node path "document/tethered-document/tethered" to lead to node cs-identifier;nodimus-mediocre;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "madame-lanode" and node path "peer-document" to lead to no node
    And I expect node aggregate identifier "nodette" and node path "peer-document/peer-child-document" to lead to no node
    And I expect node aggregate identifier "nodesis-prime" and node path "peer-document/tethered-document" to lead to no node
    And I expect node aggregate identifier "nodesis-mediocre" and node path "peer-document/tethered-document/tethered" to lead to no node

    When I am in content stream "cs-identifier" and dimension space point {"market":"DE", "language":"gsw"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nodimus-prime" and node path "document/tethered-document" to lead to node cs-identifier;nodimus-prime;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nodimus-mediocre" and node path "document/tethered-document/tethered" to lead to node cs-identifier;nodimus-mediocre;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "madame-lanode" and node path "peer-document" to lead to no node
    And I expect node aggregate identifier "nodette" and node path "peer-document/peer-child-document" to lead to no node
    And I expect node aggregate identifier "nodesis-prime" and node path "peer-document/tethered-document" to lead to no node
    And I expect node aggregate identifier "nodesis-mediocre" and node path "peer-document/tethered-document/tethered" to lead to no node

    When I am in content stream "cs-identifier" and dimension space point {"market":"DE", "language":"fr"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to no node
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to no node
    And I expect node aggregate identifier "nodimus-prime" and node path "document/tethered-document" to lead to no node
    And I expect node aggregate identifier "nodimus-mediocre" and node path "document/tethered-document/tethered" to lead to no node
    And I expect node aggregate identifier "madame-lanode" and node path "peer-document" to lead to no node
    And I expect node aggregate identifier "nodette" and node path "peer-document/peer-child-document" to lead to no node
    And I expect node aggregate identifier "nodesis-prime" and node path "peer-document/tethered-document" to lead to no node
    And I expect node aggregate identifier "nodesis-mediocre" and node path "peer-document/tethered-document/tethered" to lead to no node

    When I am in content stream "cs-identifier" and dimension space point {"market":"CH", "language":"en"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nodimus-prime" and node path "document/tethered-document" to lead to node cs-identifier;nodimus-prime;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nodimus-mediocre" and node path "document/tethered-document/tethered" to lead to node cs-identifier;nodimus-mediocre;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "madame-lanode" and node path "peer-document" to lead to no node
    And I expect node aggregate identifier "nodette" and node path "peer-document/peer-child-document" to lead to no node
    And I expect node aggregate identifier "nodesis-prime" and node path "peer-document/tethered-document" to lead to no node
    And I expect node aggregate identifier "nodesis-mediocre" and node path "peer-document/tethered-document/tethered" to lead to no node

    When I am in content stream "cs-identifier" and dimension space point {"market":"CH", "language":"de"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nodimus-prime" and node path "document/tethered-document" to lead to node cs-identifier;nodimus-prime;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nodimus-mediocre" and node path "document/tethered-document/tethered" to lead to node cs-identifier;nodimus-mediocre;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "madame-lanode" and node path "peer-document" to lead to no node
    And I expect node aggregate identifier "nodette" and node path "peer-document/peer-child-document" to lead to no node
    And I expect node aggregate identifier "nodesis-prime" and node path "peer-document/tethered-document" to lead to no node
    And I expect node aggregate identifier "nodesis-mediocre" and node path "peer-document/tethered-document/tethered" to lead to no node

    When I am in content stream "cs-identifier" and dimension space point {"market":"CH", "language":"gsw"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nodimus-prime" and node path "document/tethered-document" to lead to node cs-identifier;nodimus-prime;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nodimus-mediocre" and node path "document/tethered-document/tethered" to lead to node cs-identifier;nodimus-mediocre;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "madame-lanode" and node path "peer-document" to lead to no node
    And I expect node aggregate identifier "nodette" and node path "peer-document/peer-child-document" to lead to no node
    And I expect node aggregate identifier "nodesis-prime" and node path "peer-document/tethered-document" to lead to no node
    And I expect node aggregate identifier "nodesis-mediocre" and node path "peer-document/tethered-document/tethered" to lead to no node

    When I am in content stream "cs-identifier" and dimension space point {"market":"CH", "language":"fr"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"market":"CH", "language":"fr"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to no node
    And I expect node aggregate identifier "nodimus-prime" and node path "document/tethered-document" to lead to node cs-identifier;nodimus-prime;{"market":"CH", "language":"fr"}
    And I expect node aggregate identifier "nodimus-mediocre" and node path "document/tethered-document/tethered" to lead to node cs-identifier;nodimus-mediocre;{"market":"CH", "language":"fr"}
    And I expect node aggregate identifier "madame-lanode" and node path "peer-document" to lead to node cs-identifier;madame-lanode;{"market":"CH", "language":"fr"}
    And I expect node aggregate identifier "nodette" and node path "peer-document/peer-child-document" to lead to node cs-identifier;nodette;{"market":"CH", "language":"fr"}
    And I expect node aggregate identifier "nodesis-prime" and node path "peer-document/tethered-document" to lead to node cs-identifier;nodesis-prime;{"market":"CH", "language":"fr"}
    And I expect node aggregate identifier "nodesis-mediocre" and node path "peer-document/tethered-document/tethered" to lead to node cs-identifier;nodesis-mediocre;{"market":"CH", "language":"fr"}

  Scenario: Create peer variant of node to dimension space point with specializations that are partially occupied
    Given the event NodePeerVariantWasCreated was published with payload:
      | Key                     | Value                                                                                                                                   |
      | contentStreamIdentifier | "cs-identifier"                                                                                                                         |
      | nodeAggregateIdentifier | "madame-lanode"                                                                                                                         |
      | sourceOrigin            | {"market":"CH", "language":"fr"}                                                                                                        |
      | peerOrigin              | {"market":"DE", "language":"de"}                                                                                                        |
      | peerCoverage            | [{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"}] |
    And the event NodePeerVariantWasCreated was published with payload:
      | Key                     | Value                                                                                                                                   |
      | contentStreamIdentifier | "cs-identifier"                                                                                                                         |
      | nodeAggregateIdentifier | "nodesis-prime"                                                                                                                         |
      | sourceOrigin            | {"market":"CH", "language":"fr"}                                                                                                        |
      | peerOrigin              | {"market":"DE", "language":"de"}                                                                                                        |
      | peerCoverage            | [{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"}] |
    And the event NodePeerVariantWasCreated was published with payload:
      | Key                     | Value                                                                                                                                   |
      | contentStreamIdentifier | "cs-identifier"                                                                                                                         |
      | nodeAggregateIdentifier | "nodesis-mediocre"                                                                                                                      |
      | sourceOrigin            | {"market":"CH", "language":"fr"}                                                                                                        |
      | peerOrigin              | {"market":"DE", "language":"de"}                                                                                                        |
      | peerCoverage            | [{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"}] |
    And the graph projection is fully up to date

    When the command CreateNodeVariant is executed with payload:
      | Key                     | Value                            |
      | contentStreamIdentifier | "cs-identifier"                  |
      | nodeAggregateIdentifier | "madame-lanode"                  |
      | sourceOrigin            | {"market":"CH", "language":"fr"} |
      | targetOrigin            | {"market":"DE", "language":"en"} |
    Then I expect exactly 16 events to be published on stream "Neos.ContentRepository:ContentStream:cs-identifier"
    # The first event is NodeAggregateWithNodeWasCreated
    # The second is the first above
    And event at index 13 is of type "NodePeerVariantWasCreated" with payload:
      | Key                     | Expected                                                            |
      | contentStreamIdentifier | "cs-identifier"                                                     |
      | nodeAggregateIdentifier | "madame-lanode"                                                     |
      | sourceOrigin            | {"market":"CH", "language":"fr"}                                    |
      | peerOrigin              | {"market":"DE", "language":"en"}                                    |
      | peerCoverage            | [{"market":"DE", "language":"en"},{"market":"CH", "language":"en"}] |
    # The first event is NodeAggregateWithNodeWasCreated
    # The second is the second above
    And event at index 14 is of type "NodePeerVariantWasCreated" with payload:
      | Key                     | Expected                                                            |
      | contentStreamIdentifier | "cs-identifier"                                                     |
      | nodeAggregateIdentifier | "nodesis-prime"                                                     |
      | sourceOrigin            | {"market":"CH", "language":"fr"}                                    |
      | peerOrigin              | {"market":"DE", "language":"en"}                                    |
      | peerCoverage            | [{"market":"DE", "language":"en"},{"market":"CH", "language":"en"}] |
    # The first event is NodeAggregateWithNodeWasCreated
    # The second is the third above
    And event at index 15 is of type "NodePeerVariantWasCreated" with payload:
      | Key                     | Expected                                                            |
      | contentStreamIdentifier | "cs-identifier"                                                     |
      | nodeAggregateIdentifier | "nodesis-mediocre"                                                  |
      | sourceOrigin            | {"market":"CH", "language":"fr"}                                    |
      | peerOrigin              | {"market":"DE", "language":"en"}                                    |
      | peerCoverage            | [{"market":"DE", "language":"en"},{"market":"CH", "language":"en"}] |
    # No peer node creation for non-auto created child nodes

    When the graph projection is fully up to date
    Then I expect the graph projection to consist of exactly 15 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"market":"DE", "language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimus-prime;{"market":"DE", "language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimus-mediocre;{"market":"DE", "language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;madame-lanode;{"market":"CH", "language":"fr"} to exist in the content graph
    And I expect a node identified by cs-identifier;madame-lanode;{"market":"DE", "language":"de"} to exist in the content graph
    And I expect a node identified by cs-identifier;madame-lanode;{"market":"DE", "language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodette;{"market":"CH", "language":"fr"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodesis-prime;{"market":"CH", "language":"fr"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodesis-prime;{"market":"DE", "language":"de"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodesis-prime;{"market":"DE", "language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodesis-mediocre;{"market":"CH", "language":"fr"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodesis-mediocre;{"market":"DE", "language":"de"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodesis-mediocre;{"market":"DE", "language":"en"} to exist in the content graph

    When I am in content stream "cs-identifier"
    Then I expect the node aggregate "lady-eleonode-rootford" to exist
    And I expect this node aggregate to occupy dimension space points [{}]
    And I expect this node aggregate to cover dimension space points [{"market":"DE", "language":"en"},{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"DE", "language":"fr"},{"market":"CH", "language":"en"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"},{"market":"CH", "language":"fr"}]

    And I expect the node aggregate "sir-david-nodenborough" to exist
    And I expect this node aggregate to occupy dimension space points [{"market":"DE", "language":"en"}]
    And I expect this node aggregate to cover dimension space points [{"market":"DE", "language":"en"},{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"en"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"}]

    And I expect the node aggregate "nody-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"market":"DE", "language":"en"}]
    And I expect this node aggregate to cover dimension space points [{"market":"DE", "language":"en"},{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"en"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"}]

    And I expect the node aggregate "nodimus-prime" to exist
    And I expect this node aggregate to occupy dimension space points [{"market":"DE", "language":"en"}]
    And I expect this node aggregate to cover dimension space points [{"market":"DE", "language":"en"},{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"en"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"}]

    And I expect the node aggregate "nodimus-mediocre" to exist
    And I expect this node aggregate to occupy dimension space points [{"market":"DE", "language":"en"}]
    And I expect this node aggregate to cover dimension space points [{"market":"DE", "language":"en"},{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"en"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"}]

    And I expect the node aggregate "madame-lanode" to exist
    And I expect this node aggregate to occupy dimension space points [{"market":"CH", "language":"fr"},{"market":"DE", "language":"de"},{"market":"DE", "language":"en"}]
    And I expect this node aggregate to cover dimension space points [{"market":"CH", "language":"fr"},{"market":"DE", "language":"en"},{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"en"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"}]

    And I expect the node aggregate "nodette" to exist
    And I expect this node aggregate to occupy dimension space points [{"market":"CH", "language":"fr"}]
    And I expect this node aggregate to cover dimension space points [{"market":"CH", "language":"fr"}]

    And I expect the node aggregate "nodesis-prime" to exist
    And I expect this node aggregate to occupy dimension space points [{"market":"CH", "language":"fr"},{"market":"DE", "language":"de"},{"market":"DE", "language":"en"}]
    And I expect this node aggregate to cover dimension space points [{"market":"CH", "language":"fr"},{"market":"DE", "language":"en"},{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"en"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"}]

    And I expect the node aggregate "nodesis-mediocre" to exist
    And I expect this node aggregate to occupy dimension space points [{"market":"CH", "language":"fr"},{"market":"DE", "language":"de"},{"market":"DE", "language":"en"}]
    And I expect this node aggregate to cover dimension space points [{"market":"CH", "language":"fr"},{"market":"DE", "language":"en"},{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"en"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"}]

    When I am in content stream "cs-identifier" and dimension space point {"market":"DE", "language":"en"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nodimus-prime" and node path "document/tethered-document" to lead to node cs-identifier;nodimus-prime;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nodimus-mediocre" and node path "document/tethered-document/tethered" to lead to node cs-identifier;nodimus-mediocre;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "madame-lanode" and node path "peer-document" to lead to node cs-identifier;madame-lanode;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nodette" and node path "peer-document/peer-child-document" to lead to no node
    And I expect node aggregate identifier "nodesis-prime" and node path "peer-document/tethered-document" to lead to node cs-identifier;nodesis-prime;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nodesis-mediocre" and node path "peer-document/tethered-document/tethered" to lead to node cs-identifier;nodesis-mediocre;{"market":"DE", "language":"en"}

    When I am in content stream "cs-identifier" and dimension space point {"market":"DE", "language":"de"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nodimus-prime" and node path "document/tethered-document" to lead to node cs-identifier;nodimus-prime;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nodimus-mediocre" and node path "document/tethered-document/tethered" to lead to node cs-identifier;nodimus-mediocre;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "madame-lanode" and node path "peer-document" to lead to node cs-identifier;madame-lanode;{"market":"DE", "language":"de"}
    And I expect node aggregate identifier "nodette" and node path "peer-document/peer-child-document" to lead to no node
    And I expect node aggregate identifier "nodesis-prime" and node path "peer-document/tethered-document" to lead to node cs-identifier;nodesis-prime;{"market":"DE", "language":"de"}
    And I expect node aggregate identifier "nodesis-mediocre" and node path "peer-document/tethered-document/tethered" to lead to node cs-identifier;nodesis-mediocre;{"market":"DE", "language":"de"}

    When I am in content stream "cs-identifier" and dimension space point {"market":"DE", "language":"gsw"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nodimus-prime" and node path "document/tethered-document" to lead to node cs-identifier;nodimus-prime;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nodimus-mediocre" and node path "document/tethered-document/tethered" to lead to node cs-identifier;nodimus-mediocre;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "madame-lanode" and node path "peer-document" to lead to node cs-identifier;madame-lanode;{"market":"DE", "language":"de"}
    And I expect node aggregate identifier "nodette" and node path "peer-document/peer-child-document" to lead to no node
    And I expect node aggregate identifier "nodesis-prime" and node path "peer-document/tethered-document" to lead to node cs-identifier;nodesis-prime;{"market":"DE", "language":"de"}
    And I expect node aggregate identifier "nodesis-mediocre" and node path "peer-document/tethered-document/tethered" to lead to node cs-identifier;nodesis-mediocre;{"market":"DE", "language":"de"}

    When I am in content stream "cs-identifier" and dimension space point {"market":"DE", "language":"fr"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to no node
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to no node
    And I expect node aggregate identifier "nodimus-prime" and node path "document/tethered-document" to lead to no node
    And I expect node aggregate identifier "nodimus-mediocre" and node path "document/tethered-document/tethered" to lead to no node
    And I expect node aggregate identifier "madame-lanode" and node path "peer-document" to lead to no node
    And I expect node aggregate identifier "nodette" and node path "peer-document/peer-child-document" to lead to no node
    And I expect node aggregate identifier "nodesis-prime" and node path "peer-document/tethered-document" to lead to no node
    And I expect node aggregate identifier "nodesis-mediocre" and node path "peer-document/tethered-document/tethered" to lead to no node

    When I am in content stream "cs-identifier" and dimension space point {"market":"CH", "language":"en"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nodimus-prime" and node path "document/tethered-document" to lead to node cs-identifier;nodimus-prime;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nodimus-mediocre" and node path "document/tethered-document/tethered" to lead to node cs-identifier;nodimus-mediocre;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "madame-lanode" and node path "peer-document" to lead to node cs-identifier;madame-lanode;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nodette" and node path "peer-document/peer-child-document" to lead to no node
    And I expect node aggregate identifier "nodesis-prime" and node path "peer-document/tethered-document" to lead to node cs-identifier;nodesis-prime;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nodesis-mediocre" and node path "peer-document/tethered-document/tethered" to lead to node cs-identifier;nodesis-mediocre;{"market":"DE", "language":"en"}

    When I am in content stream "cs-identifier" and dimension space point {"market":"CH", "language":"de"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nodimus-prime" and node path "document/tethered-document" to lead to node cs-identifier;nodimus-prime;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nodimus-mediocre" and node path "document/tethered-document/tethered" to lead to node cs-identifier;nodimus-mediocre;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "madame-lanode" and node path "peer-document" to lead to node cs-identifier;madame-lanode;{"market":"DE", "language":"de"}
    And I expect node aggregate identifier "nodette" and node path "peer-document/peer-child-document" to lead to no node
    And I expect node aggregate identifier "nodesis-prime" and node path "peer-document/tethered-document" to lead to node cs-identifier;nodesis-prime;{"market":"DE", "language":"de"}
    And I expect node aggregate identifier "nodesis-mediocre" and node path "peer-document/tethered-document/tethered" to lead to node cs-identifier;nodesis-mediocre;{"market":"DE", "language":"de"}

    When I am in content stream "cs-identifier" and dimension space point {"market":"CH", "language":"gsw"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nodimus-prime" and node path "document/tethered-document" to lead to node cs-identifier;nodimus-prime;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nodimus-mediocre" and node path "document/tethered-document/tethered" to lead to node cs-identifier;nodimus-mediocre;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "madame-lanode" and node path "peer-document" to lead to node cs-identifier;madame-lanode;{"market":"DE", "language":"de"}
    And I expect node aggregate identifier "nodette" and node path "peer-document/peer-child-document" to lead to no node
    And I expect node aggregate identifier "madame-lanode" and node path "peer-document" to lead to node cs-identifier;madame-lanode;{"market":"DE", "language":"de"}
    And I expect node aggregate identifier "nodesis-prime" and node path "peer-document/tethered-document" to lead to node cs-identifier;nodesis-prime;{"market":"DE", "language":"de"}
    And I expect node aggregate identifier "nodesis-mediocre" and node path "peer-document/tethered-document/tethered" to lead to node cs-identifier;nodesis-mediocre;{"market":"DE", "language":"de"}

    When I am in content stream "cs-identifier" and dimension space point {"market":"CH", "language":"fr"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to no node
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to no node
    And I expect node aggregate identifier "nodimus-prime" and node path "document/tethered-document" to lead to no node
    And I expect node aggregate identifier "nodimus-mediocre" and node path "document/tethered-document/tethered" to lead to no node
    And I expect node aggregate identifier "madame-lanode" and node path "peer-document" to lead to node cs-identifier;madame-lanode;{"market":"CH", "language":"fr"}
    And I expect node aggregate identifier "nodette" and node path "peer-document/peer-child-document" to lead to node cs-identifier;nodette;{"market":"CH", "language":"fr"}
    And I expect node aggregate identifier "nodesis-prime" and node path "peer-document/tethered-document" to lead to node cs-identifier;nodesis-prime;{"market":"CH", "language":"fr"}
    And I expect node aggregate identifier "nodesis-mediocre" and node path "peer-document/tethered-document/tethered" to lead to node cs-identifier;nodesis-mediocre;{"market":"CH", "language":"fr"}

  Scenario: Create peer variant of node to dimension space point that is already covered
    Given the event NodePeerVariantWasCreated was published with payload:
      | Key                     | Value                                                                                                                                                                                                     |
      | contentStreamIdentifier | "cs-identifier"                                                                                                                                                                                           |
      | nodeAggregateIdentifier | "madame-lanode"                                                                                                                                                                                           |
      | sourceOrigin            | {"market":"CH", "language":"fr"}                                                                                                                                                                          |
      | peerOrigin              | {"market":"DE", "language":"en"}                                                                                                                                                                          |
      | peerCoverage            | [{"market":"DE", "language":"en"},{"market":"CH", "language":"en"},{"market":"DE", "language":"de"},{"market":"CH", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"gsw"}] |
    And the event NodePeerVariantWasCreated was published with payload:
      | Key                     | Value                                                                                                                                                                                                     |
      | contentStreamIdentifier | "cs-identifier"                                                                                                                                                                                           |
      | nodeAggregateIdentifier | "nodesis-prime"                                                                                                                                                                                           |
      | sourceOrigin            | {"market":"CH", "language":"fr"}                                                                                                                                                                          |
      | peerOrigin              | {"market":"DE", "language":"en"}                                                                                                                                                                          |
      | peerCoverage            | [{"market":"DE", "language":"en"},{"market":"CH", "language":"en"},{"market":"DE", "language":"de"},{"market":"CH", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"gsw"}] |
    And the event NodePeerVariantWasCreated was published with payload:
      | Key                     | Value                                                                                                                                                                                                     |
      | contentStreamIdentifier | "cs-identifier"                                                                                                                                                                                           |
      | nodeAggregateIdentifier | "nodesis-mediocre"                                                                                                                                                                                        |
      | sourceOrigin            | {"market":"CH", "language":"fr"}                                                                                                                                                                          |
      | peerOrigin              | {"market":"DE", "language":"en"}                                                                                                                                                                          |
      | peerCoverage            | [{"market":"DE", "language":"en"},{"market":"CH", "language":"en"},{"market":"DE", "language":"de"},{"market":"CH", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"gsw"}] |
    And the graph projection is fully up to date

    When the command CreateNodeVariant is executed with payload:
      | Key                     | Value                            |
      | contentStreamIdentifier | "cs-identifier"                  |
      | nodeAggregateIdentifier | "madame-lanode"                  |
      | sourceOrigin            | {"market":"CH", "language":"fr"} |
      | targetOrigin            | {"market":"DE", "language":"de"} |
    Then I expect exactly 16 events to be published on stream "Neos.ContentRepository:ContentStream:cs-identifier"
    # The first event is NodeAggregateWithNodeWasCreated
    # The second is the first above
    And event at index 13 is of type "NodePeerVariantWasCreated" with payload:
      | Key                     | Expected                                                                                                                                |
      | contentStreamIdentifier | "cs-identifier"                                                                                                                         |
      | nodeAggregateIdentifier | "madame-lanode"                                                                                                                         |
      | sourceOrigin            | {"market":"CH", "language":"fr"}                                                                                                        |
      | peerOrigin              | {"market":"DE", "language":"de"}                                                                                                        |
      | peerCoverage            | [{"market":"DE", "language":"de"},{"market":"CH", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"gsw"}] |
    # The first event is NodeAggregateWithNodeWasCreated
    # The second is the second above
    And event at index 14 is of type "NodePeerVariantWasCreated" with payload:
      | Key                     | Expected                                                                                                                                |
      | contentStreamIdentifier | "cs-identifier"                                                                                                                         |
      | nodeAggregateIdentifier | "nodesis-prime"                                                                                                                         |
      | sourceOrigin            | {"market":"CH", "language":"fr"}                                                                                                        |
      | peerOrigin              | {"market":"DE", "language":"de"}                                                                                                        |
      | peerCoverage            | [{"market":"DE", "language":"de"},{"market":"CH", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"gsw"}] |
    # The first event is NodeAggregateWithNodeWasCreated
    # The second is the third above
    And event at index 15 is of type "NodePeerVariantWasCreated" with payload:
      | Key                     | Expected                                                                                                                                |
      | contentStreamIdentifier | "cs-identifier"                                                                                                                         |
      | nodeAggregateIdentifier | "nodesis-mediocre"                                                                                                                      |
      | sourceOrigin            | {"market":"CH", "language":"fr"}                                                                                                        |
      | peerOrigin              | {"market":"DE", "language":"de"}                                                                                                        |
      | peerCoverage            | [{"market":"DE", "language":"de"},{"market":"CH", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"gsw"}] |
    # No peer node creation for non-auto created child nodes

    When the graph projection is fully up to date
    Then I expect the graph projection to consist of exactly 15 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"market":"DE", "language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimus-prime;{"market":"DE", "language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimus-mediocre;{"market":"DE", "language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;madame-lanode;{"market":"CH", "language":"fr"} to exist in the content graph
    And I expect a node identified by cs-identifier;madame-lanode;{"market":"DE", "language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;madame-lanode;{"market":"DE", "language":"de"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodette;{"market":"CH", "language":"fr"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodesis-prime;{"market":"CH", "language":"fr"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodesis-prime;{"market":"DE", "language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodesis-prime;{"market":"DE", "language":"de"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodesis-mediocre;{"market":"CH", "language":"fr"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodesis-mediocre;{"market":"DE", "language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodesis-mediocre;{"market":"DE", "language":"de"} to exist in the content graph

    When I am in content stream "cs-identifier"
    Then I expect the node aggregate "lady-eleonode-rootford" to exist
    And I expect this node aggregate to occupy dimension space points [{}]
    And I expect this node aggregate to cover dimension space points [{"market":"DE", "language":"en"},{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"DE", "language":"fr"},{"market":"CH", "language":"en"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"},{"market":"CH", "language":"fr"}]

    And I expect the node aggregate "sir-david-nodenborough" to exist
    And I expect this node aggregate to occupy dimension space points [{"market":"DE", "language":"en"}]
    And I expect this node aggregate to cover dimension space points [{"market":"DE", "language":"en"},{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"en"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"}]

    And I expect the node aggregate "nody-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"market":"DE", "language":"en"}]
    And I expect this node aggregate to cover dimension space points [{"market":"DE", "language":"en"},{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"en"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"}]

    And I expect the node aggregate "nodimus-prime" to exist
    And I expect this node aggregate to occupy dimension space points [{"market":"DE", "language":"en"}]
    And I expect this node aggregate to cover dimension space points [{"market":"DE", "language":"en"},{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"en"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"}]

    And I expect the node aggregate "nodimus-mediocre" to exist
    And I expect this node aggregate to occupy dimension space points [{"market":"DE", "language":"en"}]
    And I expect this node aggregate to cover dimension space points [{"market":"DE", "language":"en"},{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"en"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"}]

    And I expect the node aggregate "madame-lanode" to exist
    And I expect this node aggregate to occupy dimension space points [{"market":"CH", "language":"fr"},{"market":"DE", "language":"de"},{"market":"DE", "language":"en"}]
    And I expect this node aggregate to cover dimension space points [{"market":"CH", "language":"fr"},{"market":"DE", "language":"en"},{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"en"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"}]

    And I expect the node aggregate "nodette" to exist
    And I expect this node aggregate to occupy dimension space points [{"market":"CH", "language":"fr"}]
    And I expect this node aggregate to cover dimension space points [{"market":"CH", "language":"fr"}]

    And I expect the node aggregate "nodesis-prime" to exist
    And I expect this node aggregate to occupy dimension space points [{"market":"CH", "language":"fr"},{"market":"DE", "language":"de"},{"market":"DE", "language":"en"}]
    And I expect this node aggregate to cover dimension space points [{"market":"CH", "language":"fr"},{"market":"DE", "language":"en"},{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"en"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"}]

    And I expect the node aggregate "nodesis-mediocre" to exist
    And I expect this node aggregate to occupy dimension space points [{"market":"CH", "language":"fr"},{"market":"DE", "language":"de"},{"market":"DE", "language":"en"}]
    And I expect this node aggregate to cover dimension space points [{"market":"CH", "language":"fr"},{"market":"DE", "language":"en"},{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"CH", "language":"en"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"}]

    When I am in content stream "cs-identifier" and dimension space point {"market":"DE", "language":"en"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"en"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{"market":"DE", "language":"en"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nodimus-prime" and node path "document/tethered-document" to lead to node cs-identifier;nodimus-prime;{"market":"DE", "language":"en"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nodimus-mediocre" and node path "document/tethered-document/tethered" to lead to node cs-identifier;nodimus-mediocre;{"market":"DE", "language":"en"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "madame-lanode" and node path "peer-document" to lead to node cs-identifier;madame-lanode;{"market":"DE", "language":"en"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "nodette" and node path "peer-document/peer-child-document" to lead to no node
    And I expect node aggregate identifier "nodesis-prime" and node path "peer-document/tethered-document" to lead to node cs-identifier;nodesis-prime;{"market":"DE", "language":"en"}
    And I expect this node to be a child of node cs-identifier;madame-lanode;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nodesis-mediocre" and node path "peer-document/tethered-document/tethered" to lead to node cs-identifier;nodesis-mediocre;{"market":"DE", "language":"en"}
    And I expect this node to be a child of node cs-identifier;nodesis-prime;{"market":"DE", "language":"en"}

    When I am in content stream "cs-identifier" and dimension space point {"market":"DE", "language":"de"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"en"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{"market":"DE", "language":"en"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nodimus-prime" and node path "document/tethered-document" to lead to node cs-identifier;nodimus-prime;{"market":"DE", "language":"en"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nodimus-mediocre" and node path "document/tethered-document/tethered" to lead to node cs-identifier;nodimus-mediocre;{"market":"DE", "language":"en"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "madame-lanode" and node path "peer-document" to lead to node cs-identifier;madame-lanode;{"market":"DE", "language":"de"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "nodette" and node path "peer-document/peer-child-document" to lead to no node
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "nodesis-prime" and node path "peer-document/tethered-document" to lead to node cs-identifier;nodesis-prime;{"market":"DE", "language":"de"}
    And I expect this node to be a child of node cs-identifier;madame-lanode;{"market":"DE", "language":"de"}
    And I expect node aggregate identifier "nodesis-mediocre" and node path "peer-document/tethered-document/tethered" to lead to node cs-identifier;nodesis-mediocre;{"market":"DE", "language":"de"}
    And I expect this node to be a child of node cs-identifier;nodesis-prime;{"market":"DE", "language":"de"}

    When I am in content stream "cs-identifier" and dimension space point {"market":"DE", "language":"gsw"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"en"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{"market":"DE", "language":"en"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nodimus-prime" and node path "document/tethered-document" to lead to node cs-identifier;nodimus-prime;{"market":"DE", "language":"en"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nodimus-mediocre" and node path "document/tethered-document/tethered" to lead to node cs-identifier;nodimus-mediocre;{"market":"DE", "language":"en"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "madame-lanode" and node path "peer-document" to lead to node cs-identifier;madame-lanode;{"market":"DE", "language":"de"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "nodette" and node path "peer-document/peer-child-document" to lead to no node
    And I expect node aggregate identifier "nodesis-prime" and node path "peer-document/tethered-document" to lead to node cs-identifier;nodesis-prime;{"market":"DE", "language":"de"}
    And I expect this node to be a child of node cs-identifier;madame-lanode;{"market":"DE", "language":"de"}
    And I expect node aggregate identifier "nodesis-mediocre" and node path "peer-document/tethered-document/tethered" to lead to node cs-identifier;nodesis-mediocre;{"market":"DE", "language":"de"}
    And I expect this node to be a child of node cs-identifier;nodesis-prime;{"market":"DE", "language":"de"}

    When I am in content stream "cs-identifier" and dimension space point {"market":"DE", "language":"fr"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to no node
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to no node
    And I expect node aggregate identifier "nodimus-prime" and node path "document/tethered-document" to lead to no node
    And I expect node aggregate identifier "nodimus-mediocre" and node path "document/tethered-document/tethered" to lead to no node
    And I expect node aggregate identifier "madame-lanode" and node path "peer-document" to lead to no node
    And I expect node aggregate identifier "nodette" and node path "peer-document/peer-child-document" to lead to no node
    And I expect node aggregate identifier "nodesis-prime" and node path "peer-document/tethered-document" to lead to no node
    And I expect node aggregate identifier "nodesis-mediocre" and node path "peer-document/tethered-document/tethered" to lead to no node

    When I am in content stream "cs-identifier" and dimension space point {"market":"CH", "language":"en"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"en"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{"market":"DE", "language":"en"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nodimus-prime" and node path "document/tethered-document" to lead to node cs-identifier;nodimus-prime;{"market":"DE", "language":"en"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nodimus-mediocre" and node path "document/tethered-document/tethered" to lead to node cs-identifier;nodimus-mediocre;{"market":"DE", "language":"en"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "madame-lanode" and node path "peer-document" to lead to node cs-identifier;madame-lanode;{"market":"DE", "language":"en"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "nodette" and node path "peer-document/peer-child-document" to lead to no node
    And I expect node aggregate identifier "nodesis-prime" and node path "peer-document/tethered-document" to lead to node cs-identifier;nodesis-prime;{"market":"DE", "language":"en"}
    And I expect this node to be a child of node cs-identifier;madame-lanode;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nodesis-mediocre" and node path "peer-document/tethered-document/tethered" to lead to node cs-identifier;nodesis-mediocre;{"market":"DE", "language":"en"}
    And I expect this node to be a child of node cs-identifier;nodesis-prime;{"market":"DE", "language":"en"}

    When I am in content stream "cs-identifier" and dimension space point {"market":"CH", "language":"de"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"en"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{"market":"DE", "language":"en"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nodimus-prime" and node path "document/tethered-document" to lead to node cs-identifier;nodimus-prime;{"market":"DE", "language":"en"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nodimus-mediocre" and node path "document/tethered-document/tethered" to lead to node cs-identifier;nodimus-mediocre;{"market":"DE", "language":"en"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "madame-lanode" and node path "peer-document" to lead to node cs-identifier;madame-lanode;{"market":"DE", "language":"de"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "nodette" and node path "peer-document/peer-child-document" to lead to no node
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "nodesis-prime" and node path "peer-document/tethered-document" to lead to node cs-identifier;nodesis-prime;{"market":"DE", "language":"de"}
    And I expect this node to be a child of node cs-identifier;madame-lanode;{"market":"DE", "language":"de"}
    And I expect node aggregate identifier "nodesis-mediocre" and node path "peer-document/tethered-document/tethered" to lead to node cs-identifier;nodesis-mediocre;{"market":"DE", "language":"de"}
    And I expect this node to be a child of node cs-identifier;nodesis-prime;{"market":"DE", "language":"de"}

    When I am in content stream "cs-identifier" and dimension space point {"market":"CH", "language":"gsw"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"en"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{"market":"DE", "language":"en"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nodimus-prime" and node path "document/tethered-document" to lead to node cs-identifier;nodimus-prime;{"market":"DE", "language":"en"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nodimus-mediocre" and node path "document/tethered-document/tethered" to lead to node cs-identifier;nodimus-mediocre;{"market":"DE", "language":"en"}
    And I expect this node to be a child of node cs-identifier;nodimus-prime;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "madame-lanode" and node path "peer-document" to lead to node cs-identifier;madame-lanode;{"market":"DE", "language":"de"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "nodette" and node path "peer-document/peer-child-document" to lead to no node
    And I expect node aggregate identifier "nodesis-prime" and node path "peer-document/tethered-document" to lead to node cs-identifier;nodesis-prime;{"market":"DE", "language":"de"}
    And I expect this node to be a child of node cs-identifier;madame-lanode;{"market":"DE", "language":"de"}
    And I expect node aggregate identifier "nodesis-mediocre" and node path "peer-document/tethered-document/tethered" to lead to node cs-identifier;nodesis-mediocre;{"market":"DE", "language":"de"}
    And I expect this node to be a child of node cs-identifier;nodesis-prime;{"market":"DE", "language":"de"}

    When I am in content stream "cs-identifier" and dimension space point {"market":"CH", "language":"fr"}
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to no node
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to no node
    And I expect node aggregate identifier "nodimus-prime" and node path "document/tethered-document" to lead to no node
    And I expect node aggregate identifier "nodimus-mediocre" and node path "document/tethered-document/tethered" to lead to no node
    And I expect node aggregate identifier "madame-lanode" and node path "peer-document" to lead to node cs-identifier;madame-lanode;{"market":"CH", "language":"fr"}
    And I expect this node to be a child of node cs-identifier;lady-eleonode-rootford;{}
    And I expect node aggregate identifier "nodette" and node path "peer-document/peer-child-document" to lead to node cs-identifier;nodette;{"market":"CH", "language":"fr"}
    And I expect this node to be a child of node cs-identifier;madame-lanode;{"market":"CH", "language":"fr"}
    And I expect node aggregate identifier "nodesis-prime" and node path "peer-document/tethered-document" to lead to node cs-identifier;nodesis-prime;{"market":"CH", "language":"fr"}
    And I expect this node to be a child of node cs-identifier;madame-lanode;{"market":"CH", "language":"fr"}
    And I expect node aggregate identifier "nodesis-mediocre" and node path "peer-document/tethered-document/tethered" to lead to node cs-identifier;nodesis-mediocre;{"market":"CH", "language":"fr"}
    And I expect this node to be a child of node cs-identifier;nodesis-prime;{"market":"CH", "language":"fr"}

  Scenario: Create a peer node variant to a dimension space point with specializations and where the parent node aggregate is already specialized in
    Given the command CreateNodeVariant is executed with payload:
      | Key                     | Value                            |
      | contentStreamIdentifier | "cs-identifier"                  |
      | nodeAggregateIdentifier | "sir-david-nodenborough"         |
      | sourceOrigin            | {"market":"DE", "language":"en"} |
      | targetOrigin            | {"market":"DE", "language":"fr"} |
    And the graph projection is fully up to date
    And the command CreateNodeVariant is executed with payload:
      | Key                     | Value                            |
      | contentStreamIdentifier | "cs-identifier"                  |
      | nodeAggregateIdentifier | "sir-david-nodenborough"         |
      | sourceOrigin            | {"market":"DE", "language":"fr"} |
      | targetOrigin            | {"market":"CH", "language":"fr"} |
    And the graph projection is fully up to date

    When the command CreateNodeVariant is executed with payload:
      | Key                     | Value                            |
      | contentStreamIdentifier | "cs-identifier"                  |
      | nodeAggregateIdentifier | "nody-mc-nodeface"               |
      | sourceOrigin            | {"market":"DE", "language":"en"} |
      | targetOrigin            | {"market":"DE", "language":"fr"} |
    Then I expect exactly 17 events to be published on stream "Neos.ContentRepository:ContentStream:cs-identifier"
    # Event 1 is ContentStreamWasCreated
    # Event 2 is RootNodeAggregateWithNodeWasCreated
    # Event 3-5 are NodeAggregateWithNodeWasCreated for Sir David Nodenborough and tethered children
    # Event 6 is NodeAggregateWithNodeWasCreated for Nody McNodeface
    # Events 7-9 are NodeAggregateWithNodeWasCreated for Madame Lanode and tethered children
    # Event 10 is NodeAggregateWithNodeWasCreated for Nodette
    # Events 11-13 are NodePeerVariantWasCreated for Sir David Nodenborough and tethered children
    # Events 14-16 are NodeSpecializationVariantWasCreated for Sir David Nodenborough and tethered children
    # Event 17 is NodePeerVariantWasCreated for Nody McNodeface
    And event at index 16 is of type "NodePeerVariantWasCreated" with payload:
      | Key                     | Expected                                                            |
      | contentStreamIdentifier | "cs-identifier"                                                     |
      | nodeAggregateIdentifier | "nody-mc-nodeface"                                                  |
      | sourceOrigin            | {"market":"DE", "language":"en"}                                    |
      | peerOrigin              | {"market":"DE", "language":"fr"}                                    |
      | peerCoverage            | [{"market":"DE", "language":"fr"},{"market":"CH", "language":"fr"}] |

    When the graph projection is fully up to date
    Then I expect the graph projection to consist of exactly 16 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"fr"} to exist in the content graph
    And I expect a node identified by cs-identifier;sir-david-nodenborough;{"market":"CH", "language":"fr"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"market":"DE", "language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"market":"DE", "language":"fr"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimus-prime;{"market":"DE", "language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimus-prime;{"market":"DE", "language":"fr"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimus-prime;{"market":"CH", "language":"fr"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimus-mediocre;{"market":"DE", "language":"en"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimus-mediocre;{"market":"DE", "language":"fr"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimus-mediocre;{"market":"CH", "language":"fr"} to exist in the content graph
    And I expect a node identified by cs-identifier;madame-lanode;{"market":"CH", "language":"fr"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodette;{"market":"CH", "language":"fr"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodesis-prime;{"market":"CH", "language":"fr"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodesis-mediocre;{"market":"CH", "language":"fr"} to exist in the content graph

    When I am in content stream "cs-identifier"

    # only nodenborough and mc-nodeface are affected

    Then I expect the node aggregate "sir-david-nodenborough" to exist
    And I expect this node aggregate to occupy dimension space points [{"market":"DE", "language":"en"},{"market":"DE", "language":"fr"},{"market":"CH", "language":"fr"}]
    And I expect this node aggregate to cover dimension space points [{"market":"DE", "language":"en"},{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"DE", "language":"fr"},{"market":"CH", "language":"en"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"},{"market":"CH", "language":"fr"}]

    And I expect the node aggregate "nody-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"market":"DE", "language":"en"},{"market":"DE", "language":"fr"}]
    And I expect this node aggregate to cover dimension space points [{"market":"DE", "language":"en"},{"market":"DE", "language":"de"},{"market":"DE", "language":"gsw"},{"market":"DE", "language":"fr"},{"market":"CH", "language":"en"},{"market":"CH", "language":"de"},{"market":"CH", "language":"gsw"},{"market":"CH", "language":"fr"}]


    When I am in content stream "cs-identifier" and dimension space point {"market":"DE", "language":"en"}
    Then I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{"market":"DE", "language":"en"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"en"}

    When I am in content stream "cs-identifier" and dimension space point {"market":"DE", "language":"de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{"market":"DE", "language":"en"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"en"}

    When I am in content stream "cs-identifier" and dimension space point {"market":"DE", "language":"gsw"}
    Then I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{"market":"DE", "language":"en"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"en"}

    When I am in content stream "cs-identifier" and dimension space point {"market":"DE", "language":"fr"}
    Then I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"fr"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{"market":"DE", "language":"fr"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"fr"}

    When I am in content stream "cs-identifier" and dimension space point {"market":"CH", "language":"en"}
    Then I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{"market":"DE", "language":"en"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"en"}

    When I am in content stream "cs-identifier" and dimension space point {"market":"CH", "language":"de"}
    Then I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{"market":"DE", "language":"en"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"en"}

    When I am in content stream "cs-identifier" and dimension space point {"market":"CH", "language":"gsw"}
    Then I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"en"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{"market":"DE", "language":"en"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"market":"DE", "language":"en"}

    When I am in content stream "cs-identifier" and dimension space point {"market":"CH", "language":"fr"}
    Then I expect node aggregate identifier "sir-david-nodenborough" and node path "document" to lead to node cs-identifier;sir-david-nodenborough;{"market":"CH", "language":"fr"}
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document/child-document" to lead to node cs-identifier;nody-mc-nodeface;{"market":"DE", "language":"fr"}
    And I expect this node to be a child of node cs-identifier;sir-david-nodenborough;{"market":"CH", "language":"fr"}
