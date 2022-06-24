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
    And I run the migration for content stream "cs-id"
    Then I expect the following events
      | Type                                | Payload                                                                                                                      |
      | RootNodeAggregateWithNodeWasCreated | {"nodeAggregateIdentifier": "sites"}                                                                                         |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateIdentifier": "site"}                                                                                          |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateIdentifier": "a"}                                                                                             |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateIdentifier": "b"}                                                                                             |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateIdentifier": "c"}                                                                                             |
      | NodeReferencesWereSet               | {"sourceNodeAggregateIdentifier": "a", "destinationNodeAggregateIdentifiers": {"b": "b"}, "referenceName": "ref"}            |
      | NodeReferencesWereSet               | {"sourceNodeAggregateIdentifier": "c", "destinationNodeAggregateIdentifiers": {"a": "a", "b": "b"}, "referenceName": "refs"} |
