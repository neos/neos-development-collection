Feature: Migrating nodes with content dimensions

  Background:
    Given I have the following content dimensions:
      | Identifier | Default | Values     | Generalizations |
      | language   | en      | en, de, ch | ch->de          |
    And I have the following NodeTypes configuration:
    """
    'unstructured': {}
    'Some.Package:SomeNodeType':
      properties:
        'text':
          type: string
          defaultValue: 'My default text'
    """

  Scenario: Node specialization variants are prioritized over peer variants
    When I have the following node data rows:
      | Identifier    | Path             | Node Type                 | Dimension Values     |
      | sites-node-id | /sites           | unstructured              |                      |
      | site-node-id  | /sites/test-site | Some.Package:SomeNodeType | {"language": ["de"]} |
      | site-node-id  | /sites/test-site | Some.Package:SomeNodeType | {"language": ["en"]} |
      | site-node-id  | /sites/test-site | Some.Package:SomeNodeType | {"language": ["ch"]} |
    And I run the migration
    Then I expect the following events
      | Type                                | Payload                                                                                                                                                                                                                                                                                                                                |
      | RootNodeAggregateWithNodeWasCreated | {"nodeAggregateIdentifier": "sites-node-id", "nodeTypeName": "Neos.Neos:Sites", "coveredDimensionSpacePoints": [{"language": "en"},{"language": "de"},{"language": "ch"}], "nodeAggregateClassification": "root"}                                                                                                                      |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateIdentifier": "site-node-id", "nodeTypeName": "Some.Package:SomeNodeType", "nodeName": "test-site", "originDimensionSpacePoint": {"language": "de"}, "coveredDimensionSpacePoints": [{"language": "de"},{"language": "ch"}], "parentNodeAggregateIdentifier": "sites-node-id", "nodeAggregateClassification": "regular"} |
      | NodePeerVariantWasCreated           | {"nodeAggregateIdentifier": "site-node-id", "sourceOrigin": {"language": "de"}, "peerOrigin": {"language": "en"}, "peerCoverage": [{"language": "en"}]}                                                                                                                                                                                |
      | NodeSpecializationVariantWasCreated | {"nodeAggregateIdentifier": "site-node-id", "sourceOrigin": {"language": "de"}, "specializationOrigin": {"language": "ch"}, "specializationCoverage": [{"language": "ch"}]}                                                                                                                                                            |

  Scenario: Node generalization variants are prioritized over peer variants
    When I have the following node data rows:
      | Identifier    | Path             | Node Type                 | Dimension Values     |
      | sites-node-id | /sites           | unstructured              |                      |
      | site-node-id  | /sites/test-site | Some.Package:SomeNodeType | {"language": ["ch"]} |
      | site-node-id  | /sites/test-site | Some.Package:SomeNodeType | {"language": ["en"]} |
      | site-node-id  | /sites/test-site | Some.Package:SomeNodeType | {"language": ["de"]} |
    And I run the migration
    Then I expect the following events
      | Type                                | Payload                                                                                                                                                                                                                                                                                                             |
      | RootNodeAggregateWithNodeWasCreated | {"nodeAggregateIdentifier": "sites-node-id", "nodeTypeName": "Neos.Neos:Sites", "coveredDimensionSpacePoints": [{"language": "en"},{"language": "de"},{"language": "ch"}], "nodeAggregateClassification": "root"}                                                                                                   |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateIdentifier": "site-node-id", "nodeTypeName": "Some.Package:SomeNodeType", "nodeName": "test-site", "originDimensionSpacePoint": {"language": "ch"}, "coveredDimensionSpacePoints": [{"language": "ch"}], "parentNodeAggregateIdentifier": "sites-node-id", "nodeAggregateClassification": "regular"} |
      | NodePeerVariantWasCreated           | {"nodeAggregateIdentifier": "site-node-id", "sourceOrigin": {"language": "ch"}, "peerOrigin": {"language": "en"}, "peerCoverage": [{"language": "en"}]}                                                                                                                                                             |
      | NodeGeneralizationVariantWasCreated | {"nodeAggregateIdentifier": "site-node-id", "sourceOrigin": {"language": "ch"}, "generalizationOrigin": {"language": "de"}, "generalizationCoverage": [{"language": "de"}]}                                                                                                                                         |

  Scenario: Node variant with a subset of the original dimension space points (NodeSpecializationVariantWasCreated covers languages "de" _and_ "ch")
    Given I have the following content dimensions:
      | Identifier | Default | Values          | Generalizations |
      | language   | mul     | mul, en, de, ch | ch->de->mul     |
    When I have the following node data rows:
      | Identifier | Path        | Dimension Values      |
      | sites      | /sites      |                       |
      | site       | /sites/site | {"language": ["mul"]} |
      | site       | /sites/site | {"language": ["de"]}  |
    And I run the migration
    Then I expect the following events
      | Type                                | Payload                                                                                                                                                                                 |
      | RootNodeAggregateWithNodeWasCreated | {}                                                                                                                                                                                      |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateIdentifier": "site", "parentNodeAggregateIdentifier": "sites"}                                                                                                           |
      | NodeSpecializationVariantWasCreated | {"nodeAggregateIdentifier": "site", "sourceOrigin": {"language": "mul"}, "specializationOrigin": {"language": "de"}, "specializationCoverage": [{"language": "de"},{"language": "ch"}]} |

  Scenario: Node variant with different parent node (moved)
    When I have the following node data rows:
      | Identifier | Path             | Dimension Values     |
      | sites      | /sites           |                      |
      | site       | /sites/site      | {"language": ["de"]} |
      | a          | /sites/site/a    | {"language": ["de"]} |
      | a1         | /sites/site/a/a1 | {"language": ["de"]} |
      | b          | /sites/site/b    | {"language": ["de"]} |
      | a1         | /sites/site/b/a1 | {"language": ["ch"]} |
    And I run the migration
    Then I expect the following events
      | Type                                | Payload                                                                                                                                                                                                                                                                         |
      | RootNodeAggregateWithNodeWasCreated | {}                                                                                                                                                                                                                                                                              |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateIdentifier": "site", "parentNodeAggregateIdentifier": "sites"}                                                                                                                                                                                                   |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateIdentifier": "a", "parentNodeAggregateIdentifier": "site"}                                                                                                                                                                                                       |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateIdentifier": "a1", "parentNodeAggregateIdentifier": "a", "originDimensionSpacePoint": {"language": "de"}, "coveredDimensionSpacePoints": [{"language": "de"}, {"language": "ch"}]}                                                                               |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateIdentifier": "b", "parentNodeAggregateIdentifier": "site"}                                                                                                                                                                                                       |
      | NodeSpecializationVariantWasCreated | {"nodeAggregateIdentifier": "a1", "sourceOrigin": {"language": "de"}, "specializationOrigin": {"language": "ch"}, "specializationCoverage": [{"language": "ch"}]}                                                                                                               |
      # "69df7cc88c79ca51434f1f69e4f61b08" => hash of content dimension "ch"
      | NodeAggregateWasMoved               | {"nodeAggregateIdentifier": "a1", "nodeMoveMappings": [{"movedNodeOrigin":{"language": "ch"},"newParentAssignments":{"69df7cc88c79ca51434f1f69e4f61b08":{"nodeAggregateIdentifier":"b","originDimensionSpacePoint":{"language": "de"}}},"newSucceedingSiblingAssignments":[]}]} |


  Scenario: Node variant with different grand parent node (ancestor node was moved) - Note: There is only NodeAggregateWasMoved event for "a" and not for "a1"
    When I have the following node data rows:
      | Identifier | Path               | Dimension Values     |
      | sites      | /sites             |                      |
      | site       | /sites/site        | {"language": ["de"]} |
      | a          | /sites/site/a      | {"language": ["de"]} |
      | a1         | /sites/site/a/a1   | {"language": ["de"]} |
      | b          | /sites/site/b      | {"language": ["de"]} |
      | a          | /sites/site/b/a    | {"language": ["ch"]} |
      | a1         | /sites/site/b/a/a1 | {"language": ["ch"]} |
    And I run the migration
    Then I expect the following events
      | Type                                | Payload                                                                                                                                                                                                                                                                      |
      | RootNodeAggregateWithNodeWasCreated | {}                                                                                                                                                                                                                                                                           |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateIdentifier": "site", "parentNodeAggregateIdentifier": "sites"}                                                                                                                                                                                                |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateIdentifier": "a", "parentNodeAggregateIdentifier": "site"}                                                                                                                                                                                                    |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateIdentifier": "a1", "parentNodeAggregateIdentifier": "a", "originDimensionSpacePoint": {"language": "de"}, "coveredDimensionSpacePoints": [{"language": "de"}, {"language": "ch"}]}                                                                            |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateIdentifier": "b", "parentNodeAggregateIdentifier": "site"}                                                                                                                                                                                                    |
      | NodeSpecializationVariantWasCreated | {"nodeAggregateIdentifier": "a", "sourceOrigin": {"language": "de"}, "specializationOrigin": {"language": "ch"}, "specializationCoverage": [{"language": "ch"}]}                                                                                                             |
      # "69df7cc88c79ca51434f1f69e4f61b08" => hash of content dimension "ch"
      | NodeAggregateWasMoved               | {"nodeAggregateIdentifier": "a", "nodeMoveMappings": [{"movedNodeOrigin":{"language":"ch"},"newParentAssignments":{"69df7cc88c79ca51434f1f69e4f61b08":{"nodeAggregateIdentifier":"b","originDimensionSpacePoint":{"language":"de"}}},"newSucceedingSiblingAssignments":[]}]} |
      | NodeSpecializationVariantWasCreated | {"nodeAggregateIdentifier": "a1", "sourceOrigin": {"language": "de"}, "specializationOrigin": {"language": "ch"}, "specializationCoverage": [{"language": "ch"}]}                                                                                                            |

