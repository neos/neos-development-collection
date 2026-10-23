Feature: Migrating hidden nodes with content dimensions

  Background:
    Given using the following content dimensions:
      | Identifier | Default | Values     | Generalizations |
      | language   | en      | en, de, ch | ch->de->en      |
    And using the following node types:
    """yaml
    'Neos.Neos:Site': {}
    'Some.Package:Homepage':
      superTypes:
        'Neos.Neos:Site': true
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"

  Scenario: A hidden node variant processed before a visible variant of the same node must not disable the visible variant
    When I have the following node data rows:
      | Identifier    | Path             | Node Type             | Dimension Values     | Hidden |
      | sites-node-id | /sites           | unstructured          |                      | 0      |
      | site-node-id  | /sites/test-site | Some.Package:Homepage | {"language": ["en"]} | 1      |
      | site-node-id  | /sites/test-site | Some.Package:Homepage | {"language": ["de"]} | 0      |
    And I run the event migration
    Then I expect the following events to be exported
      | Type                                | Payload                                                                                                      |
      | RootNodeAggregateWithNodeWasCreated | {"nodeAggregateId": "sites-node-id"}                                                                         |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "site-node-id", "originDimensionSpacePoint": {"language": "en"}}                         |
      | NodeSpecializationVariantWasCreated | {"nodeAggregateId": "site-node-id", "sourceOrigin": {"language": "en"}, "specializationOrigin": {"language": "de"}} |
      | SubtreeWasTagged                    | {"nodeAggregateId": "site-node-id", "affectedDimensionSpacePoints": [{"language": "en"}], "tag": "disabled"} |

  Scenario: A hidden node without variants in fallback dimensions must stay hidden in all dimensions it shines through to
    When I have the following node data rows:
      | Identifier    | Path             | Node Type             | Dimension Values     | Hidden |
      | sites-node-id | /sites           | unstructured          |                      | 0      |
      | site-node-id  | /sites/test-site | Some.Package:Homepage | {"language": ["en"]} | 1      |
    And I run the event migration
    Then I expect the following events to be exported
      | Type                                | Payload                                                                                                                                            |
      | RootNodeAggregateWithNodeWasCreated | {"nodeAggregateId": "sites-node-id"}                                                                                                               |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "site-node-id", "originDimensionSpacePoint": {"language": "en"}}                                                               |
      | SubtreeWasTagged                    | {"nodeAggregateId": "site-node-id", "affectedDimensionSpacePoints": [{"language": "en"},{"language": "de"},{"language": "ch"}], "tag": "disabled"} |

  Scenario: Two hidden variants of the same node must each be disabled with their own coverage
    When I have the following node data rows:
      | Identifier    | Path             | Node Type             | Dimension Values     | Hidden |
      | sites-node-id | /sites           | unstructured          |                      | 0      |
      | site-node-id  | /sites/test-site | Some.Package:Homepage | {"language": ["en"]} | 1      |
      | site-node-id  | /sites/test-site | Some.Package:Homepage | {"language": ["de"]} | 1      |
    And I run the event migration
    Then I expect the following events to be exported
      | Type                                | Payload                                                                                                                         |
      | RootNodeAggregateWithNodeWasCreated | {"nodeAggregateId": "sites-node-id"}                                                                                            |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "site-node-id", "originDimensionSpacePoint": {"language": "en"}}                                            |
      | NodeSpecializationVariantWasCreated | {"nodeAggregateId": "site-node-id", "sourceOrigin": {"language": "en"}, "specializationOrigin": {"language": "de"}}             |
      | SubtreeWasTagged                    | {"nodeAggregateId": "site-node-id", "affectedDimensionSpacePoints": [{"language": "en"}], "tag": "disabled"}                    |
      | SubtreeWasTagged                    | {"nodeAggregateId": "site-node-id", "affectedDimensionSpacePoints": [{"language": "de"},{"language": "ch"}], "tag": "disabled"} |

  Scenario: A hidden node variant processed after a visible variant of the same node must not disable the visible variant
    When I have the following node data rows:
      | Identifier    | Path             | Node Type             | Dimension Values     | Hidden |
      | sites-node-id | /sites           | unstructured          |                      | 0      |
      | site-node-id  | /sites/test-site | Some.Package:Homepage | {"language": ["de"]} | 0      |
      | site-node-id  | /sites/test-site | Some.Package:Homepage | {"language": ["en"]} | 1      |
    And I run the event migration
    Then I expect the following events to be exported
      | Type                                | Payload                                                                                                                         |
      | RootNodeAggregateWithNodeWasCreated | {"nodeAggregateId": "sites-node-id"}                                                                                            |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "site-node-id", "originDimensionSpacePoint": {"language": "de"}}                                            |
      | NodeGeneralizationVariantWasCreated | {"nodeAggregateId": "site-node-id", "sourceOrigin": {"language": "de"}, "generalizationOrigin": {"language": "en"}}             |
      | SubtreeWasTagged                    | {"nodeAggregateId": "site-node-id", "affectedDimensionSpacePoints": [{"language": "en"}], "tag": "disabled"}                    |

  Scenario: A hidden node variant processed before a visible generalization variant must stay hidden in the dimensions it shines through to
    When I have the following node data rows:
      | Identifier    | Path             | Node Type             | Dimension Values     | Hidden |
      | sites-node-id | /sites           | unstructured          |                      | 0      |
      | site-node-id  | /sites/test-site | Some.Package:Homepage | {"language": ["de"]} | 1      |
      | site-node-id  | /sites/test-site | Some.Package:Homepage | {"language": ["en"]} | 0      |
    And I run the event migration
    Then I expect the following events to be exported
      | Type                                | Payload                                                                                                                         |
      | RootNodeAggregateWithNodeWasCreated | {"nodeAggregateId": "sites-node-id"}                                                                                            |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "site-node-id", "originDimensionSpacePoint": {"language": "de"}}                                            |
      | NodeGeneralizationVariantWasCreated | {"nodeAggregateId": "site-node-id", "sourceOrigin": {"language": "de"}, "generalizationOrigin": {"language": "en"}}             |
      | SubtreeWasTagged                    | {"nodeAggregateId": "site-node-id", "affectedDimensionSpacePoints": [{"language": "de"},{"language": "ch"}], "tag": "disabled"} |

  Scenario: A hidden node variant processed after a visible generalization variant must stay hidden in the dimensions it shines through to
    When I have the following node data rows:
      | Identifier    | Path             | Node Type             | Dimension Values     | Hidden |
      | sites-node-id | /sites           | unstructured          |                      | 0      |
      | site-node-id  | /sites/test-site | Some.Package:Homepage | {"language": ["en"]} | 0      |
      | site-node-id  | /sites/test-site | Some.Package:Homepage | {"language": ["de"]} | 1      |
    And I run the event migration
    Then I expect the following events to be exported
      | Type                                | Payload                                                                                                                         |
      | RootNodeAggregateWithNodeWasCreated | {"nodeAggregateId": "sites-node-id"}                                                                                            |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "site-node-id", "originDimensionSpacePoint": {"language": "en"}}                                            |
      | NodeSpecializationVariantWasCreated | {"nodeAggregateId": "site-node-id", "sourceOrigin": {"language": "en"}, "specializationOrigin": {"language": "de"}}             |
      | SubtreeWasTagged                    | {"nodeAggregateId": "site-node-id", "affectedDimensionSpacePoints": [{"language": "de"},{"language": "ch"}], "tag": "disabled"} |

  Scenario: A hidden node without dimension values must be disabled in every dimension it was created in
    When I have the following node data rows:
      | Identifier    | Path             | Node Type             | Dimension Values | Hidden |
      | sites-node-id | /sites           | unstructured          |                  | 0      |
      | site-node-id  | /sites/test-site | Some.Package:Homepage |                  | 1      |
    And I run the event migration
    Then I expect the following events to be exported
      | Type                                | Payload                                                                                                             |
      | RootNodeAggregateWithNodeWasCreated | {"nodeAggregateId": "sites-node-id"}                                                                                |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "site-node-id", "originDimensionSpacePoint": {"language": "en"}}                                |
      | NodeSpecializationVariantWasCreated | {"nodeAggregateId": "site-node-id", "sourceOrigin": {"language": "en"}, "specializationOrigin": {"language": "de"}} |
      | NodeSpecializationVariantWasCreated | {"nodeAggregateId": "site-node-id", "sourceOrigin": {"language": "en"}, "specializationOrigin": {"language": "ch"}} |
      | SubtreeWasTagged                    | {"nodeAggregateId": "site-node-id", "affectedDimensionSpacePoints": [{"language": "en"}], "tag": "disabled"}        |
      | SubtreeWasTagged                    | {"nodeAggregateId": "site-node-id", "affectedDimensionSpacePoints": [{"language": "de"}], "tag": "disabled"}        |
      | SubtreeWasTagged                    | {"nodeAggregateId": "site-node-id", "affectedDimensionSpacePoints": [{"language": "ch"}], "tag": "disabled"}        |
