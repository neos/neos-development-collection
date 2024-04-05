@contentrepository
Feature: Migrating nodes with content dimensions

  Background:
    Given using the following content dimensions:
      | Identifier | Default | Values     | Generalizations |
      | language   | en      | en, de, ch | ch->de          |
    And using the following node types:
    """yaml
    'Neos.Neos:Site': {}
    'Some.Package:Homepage':
      superTypes:
        'Neos.Neos:Site': true
    'Some.Package:Thing': {}
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"

  Scenario: Node specialization variants are prioritized over peer variants
    When I have the following node data rows:
      | Identifier    | Path             | Node Type             | Dimension Values     |
      | sites-node-id | /sites           | unstructured          |                      |
      | site-node-id  | /sites/test-site | Some.Package:Homepage | {"language": ["de"]} |
      | site-node-id  | /sites/test-site | Some.Package:Homepage | {"language": ["en"]} |
      | site-node-id  | /sites/test-site | Some.Package:Homepage | {"language": ["ch"]} |
    And I run the event migration
    Then I expect the following events to be exported
      | Type                                | Payload                                                                                                                                                                                                                                                                                                                                                                                                            |
      | RootNodeAggregateWithNodeWasCreated | {"nodeAggregateId": "sites-node-id", "nodeTypeName": "Neos.Neos:Sites", "coveredDimensionSpacePoints": [{"language": "en"},{"language": "de"},{"language": "ch"}], "nodeAggregateClassification": "root"}                                                                                                                                                                                                          |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "site-node-id", "nodeTypeName": "Some.Package:Homepage", "nodeName": "test-site", "originDimensionSpacePoint": {"language": "de"}, "succeedingSiblingsForCoverage": [{"dimensionSpacePoint":{"language": "de"},"nodeAggregateId":null},{"dimensionSpacePoint":{"language": "ch"},"nodeAggregateId":null}], "parentNodeAggregateId": "sites-node-id", "nodeAggregateClassification": "regular"} |
      | NodePeerVariantWasCreated           | {"nodeAggregateId": "site-node-id", "sourceOrigin": {"language": "de"}, "peerOrigin": {"language": "en"}, "peerSucceedingSiblings": [{"dimensionSpacePoint":{"language": "en"},"nodeAggregateId":null}]}                                                                                                                                                                                                           |
      | NodeSpecializationVariantWasCreated | {"nodeAggregateId": "site-node-id", "sourceOrigin": {"language": "de"}, "specializationOrigin": {"language": "ch"}, "specializationSiblings": [{"dimensionSpacePoint":{"language": "ch"},"nodeAggregateId":null}]}                                                                                                                                                                                                 |

  Scenario: Node generalization variants are prioritized over peer variants
    When I have the following node data rows:
      | Identifier    | Path             | Node Type             | Dimension Values     |
      | sites-node-id | /sites           | unstructured          |                      |
      | site-node-id  | /sites/test-site | Some.Package:Homepage | {"language": ["ch"]} |
      | site-node-id  | /sites/test-site | Some.Package:Homepage | {"language": ["en"]} |
      | site-node-id  | /sites/test-site | Some.Package:Homepage | {"language": ["de"]} |
    And I run the event migration
    Then I expect the following events to be exported
      | Type                                | Payload                                                                                                                                                                                                                                                                                                                                          |
      | RootNodeAggregateWithNodeWasCreated | {"nodeAggregateId": "sites-node-id", "nodeTypeName": "Neos.Neos:Sites", "coveredDimensionSpacePoints": [{"language": "en"},{"language": "de"},{"language": "ch"}], "nodeAggregateClassification": "root"}                                                                                                                                        |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "site-node-id", "nodeTypeName": "Some.Package:Homepage", "nodeName": "test-site", "originDimensionSpacePoint": {"language": "ch"}, "succeedingSiblingsForCoverage": [{"dimensionSpacePoint":{"language": "ch"},"nodeAggregateId":null}], "parentNodeAggregateId": "sites-node-id", "nodeAggregateClassification": "regular"} |
      | NodePeerVariantWasCreated           | {"nodeAggregateId": "site-node-id", "sourceOrigin": {"language": "ch"}, "peerOrigin": {"language": "en"}, "peerSucceedingSiblings": [{"dimensionSpacePoint":{"language": "en"},"nodeAggregateId":null}]}                                                                                                                                         |
      | NodeGeneralizationVariantWasCreated | {"nodeAggregateId": "site-node-id", "sourceOrigin": {"language": "ch"}, "generalizationOrigin": {"language": "de"}, "variantSucceedingSiblings": [{"dimensionSpacePoint":{"language": "de"},"nodeAggregateId":null}]}                                                                                                                            |

  Scenario: Node variant with a subset of the original dimension space points (NodeSpecializationVariantWasCreated covers languages "de" _and_ "ch")
    Given I change the content dimensions in content repository "default" to:
      | Identifier | Default | Values          | Generalizations |
      | language   | mul     | mul, en, de, ch | ch->de->mul     |
    When I have the following node data rows:
      | Identifier | Path        | Node Type             | Dimension Values      |
      | sites      | /sites      | unstructured          |                       |
      | site       | /sites/site | Some.Package:Homepage | {"language": ["mul"]} |
      | site       | /sites/site | Some.Package:Homepage | {"language": ["de"]}  |
    And I run the event migration
    Then I expect the following events to be exported
      | Type                                | Payload                                                                                                                                                                                                                                                                       |
      | RootNodeAggregateWithNodeWasCreated | {}                                                                                                                                                                                                                                                                            |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "site", "parentNodeAggregateId": "sites"}                                                                                                                                                                                                                 |
      | NodeSpecializationVariantWasCreated | {"nodeAggregateId": "site", "sourceOrigin": {"language": "mul"}, "specializationOrigin": {"language": "de"}, "specializationSiblings": [{"dimensionSpacePoint":{"language": "de"},"nodeAggregateId":null},{"dimensionSpacePoint":{"language": "ch"},"nodeAggregateId":null}]} |

  Scenario: Node variant with different parent node (moved)
    When I have the following node data rows:
      | Identifier | Path             | Node Type             | Dimension Values     |
      | sites      | /sites           | unstructured          |                      |
      | site       | /sites/site      | Some.Package:Homepage | {"language": ["de"]} |
      | a          | /sites/site/a    | Some.Package:Thing    | {"language": ["de"]} |
      | a1         | /sites/site/a/a1 | Some.Package:Thing    | {"language": ["de"]} |
      | b          | /sites/site/b    | Some.Package:Thing    | {"language": ["de"]} |
      | a1         | /sites/site/b/a1 | Some.Package:Thing    | {"language": ["ch"]} |
    And I run the event migration
    Then I expect the following events to be exported
      | Type                                | Payload                                                                                                                                                                                                                                                                                                                               |
      | RootNodeAggregateWithNodeWasCreated | {}                                                                                                                                                                                                                                                                                                                                    |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "site", "parentNodeAggregateId": "sites"}                                                                                                                                                                                                                                                                         |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "a", "parentNodeAggregateId": "site"}                                                                                                                                                                                                                                                                             |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "a1", "parentNodeAggregateId": "a", "originDimensionSpacePoint": {"language": "de"}, "succeedingSiblingsForCoverage": [{"dimensionSpacePoint":{"language": "de"},"nodeAggregateId":null},{"dimensionSpacePoint":{"language": "ch"},"nodeAggregateId":null}]}                                                      |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "b", "parentNodeAggregateId": "site"}                                                                                                                                                                                                                                                                             |
      | NodeSpecializationVariantWasCreated | {"nodeAggregateId": "a1", "sourceOrigin": {"language": "de"}, "specializationOrigin": {"language": "ch"}, "specializationSiblings": [{"dimensionSpacePoint":{"language": "ch"},"nodeAggregateId":null}]}                                                                                                                              |
      | NodeAggregateWasMoved               | {"nodeAggregateId": "a1", "nodeMoveMappings": [{"movedNodeOrigin":{"language":"ch"},"newLocations":[{"coveredDimensionSpacePoint":{"language":"ch"},"newSucceedingSibling":{"nodeAggregateId":"b","originDimensionSpacePoint":{"language":"de"},"parentNodeAggregateId":"a","parentOriginDimensionSpacePoint":{"language":"de"}}}]}]} |


  Scenario: Node variant with different grand parent node (ancestor node was moved) - Note: There is only NodeAggregateWasMoved event for "a" and not for "a1"
    When I have the following node data rows:
      | Identifier | Path               | Node Type             | Dimension Values     |
      | sites      | /sites             | unstructured          |                      |
      | site       | /sites/site        | Some.Package:Homepage | {"language": ["de"]} |
      | a          | /sites/site/a      | Some.Package:Thing    | {"language": ["de"]} |
      | a1         | /sites/site/a/a1   | Some.Package:Thing    | {"language": ["de"]} |
      | b          | /sites/site/b      | Some.Package:Thing    | {"language": ["de"]} |
      | a          | /sites/site/b/a    | Some.Package:Thing    | {"language": ["ch"]} |
      | a1         | /sites/site/b/a/a1 | Some.Package:Thing    | {"language": ["ch"]} |
    And I run the event migration
    Then I expect the following events to be exported
      | Type                                | Payload                                                                                                                                                                                                                                                                                                                                 |
      | RootNodeAggregateWithNodeWasCreated | {}                                                                                                                                                                                                                                                                                                                                      |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "site", "parentNodeAggregateId": "sites"}                                                                                                                                                                                                                                                                           |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "a", "parentNodeAggregateId": "site"}                                                                                                                                                                                                                                                                               |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "a1", "parentNodeAggregateId": "a", "originDimensionSpacePoint": {"language": "de"}, "succeedingSiblingsForCoverage": [{"dimensionSpacePoint":{"language": "de"},"nodeAggregateId":null}, {"dimensionSpacePoint":{"language": "ch"},"nodeAggregateId":null}]}                                                       |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "b", "parentNodeAggregateId": "site"}                                                                                                                                                                                                                                                                               |
      | NodeSpecializationVariantWasCreated | {"nodeAggregateId": "a", "sourceOrigin": {"language": "de"}, "specializationOrigin": {"language": "ch"}, "specializationSiblings": [{"dimensionSpacePoint":{"language": "ch"},"nodeAggregateId":null}]}                                                                                                                                 |
      | NodeAggregateWasMoved               | {"nodeAggregateId": "a", "nodeMoveMappings": [{"movedNodeOrigin":{"language":"ch"},"newLocations":[{"coveredDimensionSpacePoint":{"language":"ch"},"newSucceedingSibling":{"nodeAggregateId":"b","originDimensionSpacePoint":{"language":"de"},"parentNodeAggregateId":"site","parentOriginDimensionSpacePoint":{"language":"de"}}}]}]} |
      | NodeSpecializationVariantWasCreated | {"nodeAggregateId": "a1", "sourceOrigin": {"language": "de"}, "specializationOrigin": {"language": "ch"}, "specializationSiblings": [{"dimensionSpacePoint":{"language": "ch"},"nodeAggregateId":null}]}                                                                                                                                |

