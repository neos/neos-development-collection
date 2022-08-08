Feature: Migrations that contain nodes with "reference" or "references properties

  Background:
    Given I have no content dimensions
    And I have the following NodeTypes configuration:
    """
    'Some.Package:Homepage': []
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
      | Type                                | Payload                                                                                                                                                                                                                                                                           |
      | RootNodeAggregateWithNodeWasCreated | {"nodeAggregateIdentifier": "sites"}                                                                                                                                                                                                                                              |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateIdentifier": "site"}                                                                                                                                                                                                                                               |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateIdentifier": "a"}                                                                                                                                                                                                                                                  |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateIdentifier": "b"}                                                                                                                                                                                                                                                  |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateIdentifier": "c"}                                                                                                                                                                                                                                                  |
      | NodeReferencesWereSet               | {"nodeAggregateIdentifier":"a","sourceNodeAggregateIdentifier":"a","affectedSourceOriginDimensionSpacePoints":[[]],"referenceName":"ref","references":{"b":{"targetNodeAggregateIdentifier":"b","properties":null}}}                                                              |
      | NodeReferencesWereSet               | {"nodeAggregateIdentifier":"c","sourceNodeAggregateIdentifier":"c","affectedSourceOriginDimensionSpacePoints":[[]],"referenceName":"refs","references":{"a":{"targetNodeAggregateIdentifier":"a","properties":null},"b":{"targetNodeAggregateIdentifier":"b","properties":null}}} |


  Scenario: Node with references in one dimension
    Given I have the following content dimensions:
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
      | Type                                | Payload                                                                                                                                                                                                                                                                                           |
      | RootNodeAggregateWithNodeWasCreated | {"nodeAggregateIdentifier": "sites"}                                                                                                                                                                                                                                                              |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateIdentifier": "site"}                                                                                                                                                                                                                                                               |
      | NodePeerVariantWasCreated           | {}                                                                                                                                                                                                                                                                                                |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateIdentifier": "a"}                                                                                                                                                                                                                                                                  |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateIdentifier": "b"}                                                                                                                                                                                                                                                                  |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateIdentifier": "c"}                                                                                                                                                                                                                                                                  |
      | NodeReferencesWereSet               | {"nodeAggregateIdentifier":"a","sourceNodeAggregateIdentifier":"a","affectedSourceOriginDimensionSpacePoints":[{"language": "en"}],"referenceName":"ref","references":{"b":{"targetNodeAggregateIdentifier":"b","properties":null}}}                                                              |
      | NodeReferencesWereSet               | {"nodeAggregateIdentifier":"b","sourceNodeAggregateIdentifier":"b","affectedSourceOriginDimensionSpacePoints":[{"language": "de"}],"referenceName":"ref","references":{"a":{"targetNodeAggregateIdentifier":"a","properties":null}}}                                                              |
      | NodeReferencesWereSet               | {"nodeAggregateIdentifier":"c","sourceNodeAggregateIdentifier":"c","affectedSourceOriginDimensionSpacePoints":[{"language": "ch"}],"referenceName":"refs","references":{"a":{"targetNodeAggregateIdentifier":"a","properties":null},"b":{"targetNodeAggregateIdentifier":"b","properties":null}}} |
