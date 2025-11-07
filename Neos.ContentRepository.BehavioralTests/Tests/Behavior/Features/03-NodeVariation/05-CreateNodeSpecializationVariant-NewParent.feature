@contentrepository @adapters=DoctrineDBAL,Postgres
Feature: Create node specialization
  As a user of the CR I want to create a variant of a node within an aggregate
  to a more specialized dimension space point
  and assign that variant to a different parent
  * before the first of its new siblings
  * before the first of its new siblings - which does not exist in all variants
  * before one of its new siblings, wich is partially the first
  * before one of its new siblings, which is not the first
  * before one of its new siblings, which is not the first and does not exist in all variants
  * before one of its new siblings, which is the last and does not exist in all variants
  * after the last of its new siblings
  * after the last of its new siblings, which does not exist in all variants
  * after one of its new siblings, which is partially the last
  * after one of its new siblings, which is not the last

  Background:
    Given using the following content dimensions:
      | Identifier | Values                      | Generalizations                      |
      | example    | general, source, spec, peer | spec->source->general, peer->general |
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Document':
      childNodes:
        tethered-node:
          type: 'Neos.ContentRepository.Testing:Tethered'
    'Neos.ContentRepository.Testing:Tethered':
      childNodes:
        tethered-leaf:
          type: 'Neos.ContentRepository.Testing:TetheredLeaf'
    'Neos.ContentRepository.Testing:TetheredLeaf': []
    'Neos.ContentRepository.Testing:LeafDocument': []
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                | Value           |
      | workspaceName      | "live"          |
      | newContentStreamId | "cs-identifier" |
    And I am in workspace "live" and dimension space point {"example":"general"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |

    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId            | nodeName          | nodeTypeName                            | parentNodeAggregateId      | tetheredDescendantNodeAggregateIds                                                                           |
      | sir-david-nodenborough     | parent-document   | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford     | {}                                                                                                           |
      | eldest-mc-nodeface         | eldest-document   | Neos.ContentRepository.Testing:Document | sir-david-nodenborough     | {"tethered-node": "eldest-nodewyn-tetherton", "tethered-node/tethered-leaf": "eldest-nodimer-tetherton"}     |
      | elder-mc-nodeface          | elder-document    | Neos.ContentRepository.Testing:Document | sir-david-nodenborough     | {"tethered-node": "elder-nodewyn-tetherton", "tethered-node/tethered-leaf": "elder-nodimer-tetherton"}       |
      | younger-mc-nodeface        | younger-document  | Neos.ContentRepository.Testing:Document | sir-david-nodenborough     | {"tethered-node": "younger-nodewyn-tetherton", "tethered-node/tethered-leaf": "younger-nodimer-tetherton"}   |
      | youngest-mc-nodeface       | youngest-document | Neos.ContentRepository.Testing:Document | sir-david-nodenborough     | {"tethered-node": "youngest-nodewyn-tetherton", "tethered-node/tethered-leaf": "youngest-nodimer-tetherton"} |
      | sir-nodeward-nodington-iii | esquire           | Neos.ContentRepository.Testing:Document | lady-eleonode-rootford     | {}                                                                                                           |
      | nody-mc-nodeface           | document          | Neos.ContentRepository.Testing:Document | sir-nodeward-nodington-iii | {"tethered-node": "nodewyn-tetherton", "tethered-node/tethered-leaf": "nodimer-tetherton"}                   |

  Scenario: Create specialization variant to a new parent before the first of its new siblings
    When the command CreateNodeVariant is executed with payload:
      | Key                              | Value                    |
      | nodeAggregateId                  | "nody-mc-nodeface"       |
      | sourceOrigin                     | {"example":"general"}    |
      | targetOrigin                     | {"example":"source"}     |
      | parentNodeAggregateId            | "sir-david-nodenborough" |
      | succeedingSiblingNodeAggregateId | "eldest-mc-nodeface"     |
    Then I expect exactly 26 events to be published on stream "ContentStream:cs-identifier"
    And event at index 23 is of type "NodeSpecializationVariantWasCreated" with payload:
      | Key                    | Expected                                                                                                                                                                |
      | contentStreamId        | "cs-identifier"                                                                                                                                                         |
      | nodeAggregateId        | "nody-mc-nodeface"                                                                                                                                                      |
      | sourceOrigin           | {"example":"general"}                                                                                                                                                   |
      | specializationOrigin   | {"example":"source"}                                                                                                                                                    |
      | specializationSiblings | [{"dimensionSpacePoint":{"example":"source"},"nodeAggregateId":"eldest-mc-nodeface"},{"dimensionSpacePoint":{"example":"spec"},"nodeAggregateId":"eldest-mc-nodeface"}] |
      | parentNodeAggregateId  | "sir-david-nodenborough"                                                                                                                                                |
    And event at index 24 is of type "NodeSpecializationVariantWasCreated" with payload:
      | Key                    | Expected                                                                                                                                                                        |
      | contentStreamId        | "cs-identifier"                                                                                                                                                                 |
      | nodeAggregateId        | "nodewyn-tetherton"                                                                                                                                                             |
      | sourceOrigin           | {"example":"general"}                                                                                                                                                           |
      | specializationOrigin   | {"example":"source"}                                                                                                                                                            |
      | specializationSiblings | [{"dimensionSpacePoint":{"example":"source"},"nodeAggregateId":"invariable-mc-nodeface"},{"dimensionSpacePoint":{"example":"spec"},"nodeAggregateId":"invariable-mc-nodeface"}] |
      | parentNodeAggregateId  | null                                                                                                                                                                            |
    And event at index 25 is of type "NodeSpecializationVariantWasCreated" with payload:
      | Key                    | Expected                                                                                                                                |
      | contentStreamId        | "cs-identifier"                                                                                                                         |
      | nodeAggregateId        | "nodimer-tetherton"                                                                                                                     |
      | sourceOrigin           | {"example":"general"}                                                                                                                   |
      | specializationOrigin   | {"example":"source"}                                                                                                                    |
      | specializationSiblings | [{"dimensionSpacePoint":{"example":"source"},"nodeAggregateId":null},{"dimensionSpacePoint":{"example":"spec"},"nodeAggregateId":null}] |
      | parentNodeAggregateId  | null                                                                                                                                    |

    Then I expect the graph projection to consist of exactly 12 nodes
    And I expect a node identified by cs-identifier;lady-eleonode-rootford;{} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;nody-mc-nodeface;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodewyn-tetherton;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodewyn-tetherton;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimer-tetherton;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;nodimer-tetherton;{"example":"source"} to exist in the content graph
    And I expect a node identified by cs-identifier;eldest-mc-nodeface;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;elder-mc-nodeface;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;younger-mc-nodeface;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;youngest-mc-nodeface;{"example":"general"} to exist in the content graph
    And I expect a node identified by cs-identifier;invariable-mc-nodeface;{"example":"general"} to exist in the content graph

    When I am in workspace "live"
    Then I expect the node aggregate "lady-eleonode-rootford" to exist
    And I expect this node aggregate to occupy dimension space points [{}]
    And I expect this node aggregate to cover dimension space points [{"example":"general"},{"example":"source"},{"example":"spec"}]

    And I expect the node aggregate "eldest-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"general"}]
    And I expect this node aggregate to cover dimension space points [{"example":"general"},{"example":"source"},{"example":"spec"}]

    And I expect the node aggregate "elder-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"general"}]
    And I expect this node aggregate to cover dimension space points [{"example":"general"},{"example":"source"},{"example":"spec"}]

    And I expect the node aggregate "nody-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"general"},{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"general"},{"example":"source"},{"example":"spec"}]

    And I expect the node aggregate "nodewyn-tetherton" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"general"},{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"general"},{"example":"source"},{"example":"spec"}]

    And I expect the node aggregate "nodimer-tetherton" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"general"},{"example":"source"}]
    And I expect this node aggregate to cover dimension space points [{"example":"general"},{"example":"source"},{"example":"spec"}]

    And I expect the node aggregate "invariable-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"general"}]
    And I expect this node aggregate to cover dimension space points [{"example":"general"},{"example":"source"},{"example":"spec"}]

    And I expect the node aggregate "younger-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"general"}]
    And I expect this node aggregate to cover dimension space points [{"example":"general"},{"example":"source"},{"example":"spec"}]

    And I expect the node aggregate "youngest-mc-nodeface" to exist
    And I expect this node aggregate to occupy dimension space points [{"example":"general"}]
    And I expect this node aggregate to cover dimension space points [{"example":"general"},{"example":"source"},{"example":"spec"}]

    When I am in workspace "live" and dimension space point {"example":"source"}
    Then I expect the subgraph projection to consist of exactly 9 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And the subtree for node aggregate "lady-eleonode-rootford" with node types "" and 3 levels deep should be:
      | Level | nodeAggregateId        |
      | 0     | lady-eleonode-rootford |
      | 1     | eldest-mc-nodeface     |
      | 1     | elder-mc-nodeface      |
      | 1     | nody-mc-nodeface       |
      | 2     | nodewyn-tetherton      |
      | 3     | nodimer-tetherton      |
      | 2     | invariable-mc-nodeface |
      | 1     | younger-mc-nodeface    |
      | 1     | youngest-mc-nodeface   |
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                        |
      | eldest-document   | cs-identifier;eldest-mc-nodeface;{"example":"general"}   |
      | elder-document    | cs-identifier;elder-mc-nodeface;{"example":"general"}    |
      | document          | cs-identifier;nody-mc-nodeface;{"example":"source"}      |
      | younger-document  | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | youngest-document | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "eldest-mc-nodeface" and node path "eldest-document" to lead to node cs-identifier;eldest-mc-nodeface;{"example":"general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}    |
      | cs-identifier;nody-mc-nodeface;{"example":"source"}      |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "elder-mc-nodeface" and node path "elder-document" to lead to node cs-identifier;elder-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;nody-mc-nodeface;{"example":"source"}      |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"source"}
    And I expect this node to have the following child nodes:
      | Name                | NodeDiscriminator                                          |
      | tethered-node       | cs-identifier;nodewyn-tetherton;{"example":"source"}       |
      | invariable-document | cs-identifier;invariable-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}  |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "nodewyn-tetherton" and node path "document/tethered-node" to lead to node cs-identifier;nodewyn-tetherton;{"example":"source"}
    And I expect this node to have the following child nodes:
      | Name          | NodeDiscriminator                                    |
      | tethered-leaf | cs-identifier;nodimer-tetherton;{"example":"source"} |
    And I expect node aggregate identifier "nodimer-tetherton" and node path "document/tethered-node/tethered-leaf" to lead to node cs-identifier;nodimer-tetherton;{"example":"source"}
    And I expect node aggregate identifier "invariable-mc-nodeface" and node path "document/invariable-document" to lead to node cs-identifier;invariable-mc-nodeface;{"example":"general"}
    And I expect node aggregate identifier "younger-mc-nodeface" and node path "younger-document" to lead to node cs-identifier;younger-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;nody-mc-nodeface;{"example":"source"}    |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}  |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "youngest-mc-nodeface" and node path "youngest-document" to lead to node cs-identifier;youngest-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;younger-mc-nodeface;{"example":"general"} |
      | cs-identifier;nody-mc-nodeface;{"example":"source"}     |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"}  |
    And I expect this node to have no succeeding siblings

    When I am in workspace "live" and dimension space point {"example":"spec"}
    Then I expect the subgraph projection to consist of exactly 9 nodes
    Then I expect node aggregate identifier "lady-eleonode-rootford" to lead to node cs-identifier;lady-eleonode-rootford;{}
    And I expect this node to have the following child nodes:
      | Name              | NodeDiscriminator                                        |
      | eldest-document   | cs-identifier;eldest-mc-nodeface;{"example":"general"}   |
      | elder-document    | cs-identifier;elder-mc-nodeface;{"example":"general"}    |
      | document          | cs-identifier;nody-mc-nodeface;{"example":"source"}      |
      | younger-document  | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | youngest-document | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "eldest-mc-nodeface" and node path "eldest-document" to lead to node cs-identifier;eldest-mc-nodeface;{"example":"general"}
    And I expect this node to have no preceding siblings
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}    |
      | cs-identifier;nody-mc-nodeface;{"example":"source"}      |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "elder-mc-nodeface" and node path "elder-document" to lead to node cs-identifier;elder-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;nody-mc-nodeface;{"example":"source"}      |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "nody-mc-nodeface" and node path "document" to lead to node cs-identifier;nody-mc-nodeface;{"example":"source"}
    And I expect this node to have the following child nodes:
      | Name                | NodeDiscriminator                                          |
      | tethered-node       | cs-identifier;nodewyn-tetherton;{"example":"source"}       |
      | invariable-document | cs-identifier;invariable-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}  |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;younger-mc-nodeface;{"example":"general"}  |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "nodewyn-tetherton" and node path "document/tethered-node" to lead to node cs-identifier;nodewyn-tetherton;{"example":"source"}
    And I expect this node to have the following child nodes:
      | Name          | NodeDiscriminator                                    |
      | tethered-leaf | cs-identifier;nodimer-tetherton;{"example":"source"} |
    And I expect node aggregate identifier "nodimer-tetherton" and node path "document/tethered-node/tethered-leaf" to lead to node cs-identifier;nodimer-tetherton;{"example":"source"}
    And I expect node aggregate identifier "invariable-mc-nodeface" and node path "document/invariable-document" to lead to node cs-identifier;invariable-mc-nodeface;{"example":"general"}
    And I expect node aggregate identifier "younger-mc-nodeface" and node path "younger-document" to lead to node cs-identifier;younger-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                      |
      | cs-identifier;nody-mc-nodeface;{"example":"source"}    |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}  |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"} |
    And I expect this node to have the following succeeding siblings:
      | NodeDiscriminator                                        |
      | cs-identifier;youngest-mc-nodeface;{"example":"general"} |
    And I expect node aggregate identifier "youngest-mc-nodeface" and node path "youngest-document" to lead to node cs-identifier;youngest-mc-nodeface;{"example":"general"}
    And I expect this node to have the following preceding siblings:
      | NodeDiscriminator                                       |
      | cs-identifier;younger-mc-nodeface;{"example":"general"} |
      | cs-identifier;nody-mc-nodeface;{"example":"source"}     |
      | cs-identifier;elder-mc-nodeface;{"example":"general"}   |
      | cs-identifier;eldest-mc-nodeface;{"example":"general"}  |
    And I expect this node to have no succeeding siblings
