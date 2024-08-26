@contentrepository
Feature: Migrations that contain nodes with "reference" or "references properties

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.Neos:Site': {}
    'Some.Package:Homepage':
      superTypes:
        'Neos.Neos:Site': true
    'Some.Package:SomeNodeType':
      properties:
        'text':
          type: string
          defaultValue: 'My default text'
        'ref':
          type: reference
        'refs':
          type: references
    'Some.Package:SomeOtherNodeType':
      properties:
        'text':
          type: string
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"

  Scenario: Two nodes with references
    When I have the following node data rows:
      | Identifier | Path          | Node Type                      | Properties                                |
      | sites      | /sites        | unstructured                   |                                           |
      | site       | /sites/site   | Some.Package:Homepage          |                                           |
      | a          | /sites/site/a | Some.Package:SomeNodeType      | {"text": "This is a", "ref": "b"}         |
      | b          | /sites/site/b | Some.Package:SomeOtherNodeType | {"text": "This is b"}                     |
      | c          | /sites/site/c | Some.Package:SomeNodeType      | {"text": "This is c", "refs": ["a", "b"]} |
    And I run the event migration
    Then I expect the following events to be exported
      | Type                                | Payload                                                                                                                                                                                                               |
      | RootNodeAggregateWithNodeWasCreated | {"nodeAggregateId": "sites"}                                                                                                                                                                                          |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "site"}                                                                                                                                                                                           |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "a"}                                                                                                                                                                                              |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "b"}                                                                                                                                                                                              |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "c"}                                                                                                                                                                                              |
      | NodeReferencesWereSet               | {"nodeAggregateId":"a","affectedSourceOriginDimensionSpacePoints":[[]],"references":{"ref": [{"target":"b","properties":null}]}}                                                      |
      | NodeReferencesWereSet               | {"nodeAggregateId":"c","affectedSourceOriginDimensionSpacePoints":[[]],"references":{"refs":[{"target":"a","properties":null},{"target":"b","properties":null}]}} |


  Scenario: Node with references in one dimension
    Given I change the content dimensions in content repository "default" to:
      | Identifier | Default | Values     | Generalizations |
      | language   | en      | en, de, ch | ch->de          |
    When I have the following node data rows:
      | Identifier | Path          | Node Type                 | Dimension Values     | Properties                                |
      | sites      | /sites        | unstructured              |                      |                                           |
      | site       | /sites/site   | Some.Package:Homepage     | {"language": ["en"]} |                                           |
      | site       | /sites/site   | Some.Package:Homepage     | {"language": ["de"]} |                                           |
      | a          | /sites/site/a | Some.Package:SomeNodeType | {"language": ["en"]} | {"text": "This is a", "ref": "b"}         |
      | b          | /sites/site/b | Some.Package:SomeNodeType | {"language": ["de"]} | {"text": "This is b", "ref": "a"}         |
      | c          | /sites/site/c | Some.Package:SomeNodeType | {"language": ["ch"]} | {"text": "This is c", "refs": ["a", "b"]} |
    And I run the event migration
    Then I expect the following events to be exported
      | Type                                | Payload                                                                                                                                                                                                                               |
      | RootNodeAggregateWithNodeWasCreated | {"nodeAggregateId": "sites"}                                                                                                                                                                                                          |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "site"}                                                                                                                                                                                                           |
      | NodePeerVariantWasCreated           | {}                                                                                                                                                                                                                                    |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "a"}                                                                                                                                                                                                              |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "b"}                                                                                                                                                                                                              |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "c"}                                                                                                                                                                                                              |
      | NodeReferencesWereSet               | {"nodeAggregateId":"a","affectedSourceOriginDimensionSpacePoints":[{"language": "en"}],"references":{"ref": [{"target":"b","properties":null}]}}                                                      |
      | NodeReferencesWereSet               | {"nodeAggregateId":"b","affectedSourceOriginDimensionSpacePoints":[{"language": "de"}],"references":{"ref": [{"target":"a","properties":null}]}}                                                      |
      | NodeReferencesWereSet               | {"nodeAggregateId":"c","affectedSourceOriginDimensionSpacePoints":[{"language": "ch"}],"references":{"refs":[{"target":"a","properties":null},{"target":"b","properties":null}]}} |

  Scenario: Nodes with properties that are not part of the node type schema (see https://github.com/neos/neos-development-collection/issues/4804)
    When I have the following node data rows:
      | Identifier    | Path             | Node Type             | Properties                 |
      | sites-node-id | /sites           | unstructured          |                            |
      | site-node-id  | /sites/test-site | Some.Package:Homepage | {"unknownProperty": "ref"} |
    And I run the event migration
    Then I expect the following events to be exported
      | Type                                | Payload                              |
      | RootNodeAggregateWithNodeWasCreated | {"nodeAggregateId": "sites-node-id"} |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "site-node-id"}  |
    And I expect the following warnings to be logged
      | Skipped node data processing for the property "unknownProperty". The property name is not part of the NodeType schema for the NodeType "Some.Package:Homepage". (Node: site-node-id) |
