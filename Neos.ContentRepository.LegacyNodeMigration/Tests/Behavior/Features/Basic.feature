@contentrepository
Feature: Simple migrations without content dimensions

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.Neos:Site': {}
    'Some.Package:Homepage':
      superTypes:
        'Neos.Neos:Site': true
      properties:
        'text':
          type: string
          defaultValue: 'My default text'
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"

  Scenario: Single homepage node with one property
    When I have the following node data rows:
      | Identifier    | Path             | Node Type             | Properties      |
      | sites-node-id | /sites           | unstructured          |                 |
      | site-node-id  | /sites/test-site | Some.Package:Homepage | {"text": "foo"} |
    And I run the event migration for content stream "cs-id"
    Then I expect the following events to be exported
      | Type                                | Payload                                                                                                                                                                                                                                                                                      |
      | RootNodeAggregateWithNodeWasCreated | {"contentStreamId": "cs-id", "nodeAggregateId": "sites-node-id", "nodeTypeName": "Neos.Neos:Sites", "nodeAggregateClassification": "root"}                                                                                                                                                   |
      | NodeAggregateWithNodeWasCreated     | {"contentStreamId": "cs-id", "nodeAggregateId": "site-node-id", "nodeTypeName": "Some.Package:Homepage", "nodeName": "test-site", "parentNodeAggregateId": "sites-node-id", "nodeAggregateClassification": "regular", "initialPropertyValues": {"text": {"type": "string", "value": "foo"}}} |
