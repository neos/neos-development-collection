Feature: Simple migrations without content dimensions

  Background:
    Given I have no content dimensions
    And I have the following NodeTypes configuration:
    """
    'Some.Package:SomeNodeType':
      properties:
        'text':
          type: string
          defaultValue: 'My default text'
    """

  Scenario: Single homepage node with one property
    When I have the following node data rows:
      | Identifier    | Path             | Node Type                 | Properties      |
      | sites-node-id | /sites           | unstructured              |                 |
      | site-node-id  | /sites/test-site | Some.Package:SomeNodeType | {"text": "foo"} |
    And I run the event migration for content stream "cs-id"
    Then I expect the following events to be exported
      | Type                                | Payload                                                                                                                                                                                                                                                                                                                  |
      | RootNodeAggregateWithNodeWasCreated | {"contentStreamIdentifier": "cs-id", "nodeAggregateIdentifier": "sites-node-id", "nodeTypeName": "Neos.Neos:Sites", "nodeAggregateClassification": "root"}                                                                                                                                                               |
      | NodeAggregateWithNodeWasCreated     | {"contentStreamIdentifier": "cs-id", "nodeAggregateIdentifier": "site-node-id", "nodeTypeName": "Some.Package:SomeNodeType", "nodeName": "test-site", "parentNodeAggregateIdentifier": "sites-node-id", "nodeAggregateClassification": "regular", "initialPropertyValues": {"text": {"type": "string", "value": "foo"}}} |
