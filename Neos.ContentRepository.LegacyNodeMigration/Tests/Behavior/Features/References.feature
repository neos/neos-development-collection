@contentrepository
Feature: Migrations that contain nodes with "reference" or "references properties

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'unstructured': {}
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
      | Type                                | Payload                                                                                                                                                                                                                                           |
      | RootNodeAggregateWithNodeWasCreated | {"nodeAggregateId": "sites"}                                                                                                                                                                                                                      |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "site"}                                                                                                                                                                                                                       |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "a"}                                                                                                                                                                                                                          |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "b"}                                                                                                                                                                                                                          |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "c"}                                                                                                                                                                                                                          |
      | NodeReferencesWereSet               | {"sourceNodeAggregateId":"a","affectedSourceOriginDimensionSpacePoints":[[]],"referenceName":"ref","references":{"b":{"targetNodeAggregateId":"b","properties":null}}}                                                      |
      | NodeReferencesWereSet               | {"sourceNodeAggregateId":"c","affectedSourceOriginDimensionSpacePoints":[[]],"referenceName":"refs","references":{"a":{"targetNodeAggregateId":"a","properties":null},"b":{"targetNodeAggregateId":"b","properties":null}}} |


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
      | Type                                | Payload                                                                                                                                                                                                                                                           |
      | RootNodeAggregateWithNodeWasCreated | {"nodeAggregateId": "sites"}                                                                                                                                                                                                                                      |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "site"}                                                                                                                                                                                                                                       |
      | NodePeerVariantWasCreated           | {}                                                                                                                                                                                                                                                                |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "a"}                                                                                                                                                                                                                                          |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "b"}                                                                                                                                                                                                                                          |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "c"}                                                                                                                                                                                                                                          |
      | NodeReferencesWereSet               | {"sourceNodeAggregateId":"a","affectedSourceOriginDimensionSpacePoints":[{"language": "en"}],"referenceName":"ref","references":{"b":{"targetNodeAggregateId":"b","properties":null}}}                                                      |
      | NodeReferencesWereSet               | {"sourceNodeAggregateId":"b","affectedSourceOriginDimensionSpacePoints":[{"language": "de"}],"referenceName":"ref","references":{"a":{"targetNodeAggregateId":"a","properties":null}}}                                                      |
      | NodeReferencesWereSet               | {"sourceNodeAggregateId":"c","affectedSourceOriginDimensionSpacePoints":[{"language": "ch"}],"referenceName":"refs","references":{"a":{"targetNodeAggregateId":"a","properties":null},"b":{"targetNodeAggregateId":"b","properties":null}}} |
