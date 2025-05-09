@contentrepository
Feature: Simple migrations without content dimensions but other root nodetype name

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

  Scenario: Migration without rootNodeType configuration for all root nodes
    When I have the following node data rows:
      | Identifier        | Path             | Node Type             | Properties      |
      | sites-node-id     | /sites           | unstructured          |                 |
      | site-node-id      | /sites/test-site | Some.Package:Homepage | {"text": "foo"} |
      | test-root-node-id | /test            | unstructured          |                 |
      | test-node-id      | /test/test-site  | Some.Package:Homepage | {"text": "foo"} |
    And I run the event migration
    Then I expect the following errors to be logged
      | Failed to find parent node for node with id "test-root-node-id" and dimensions: []. Please ensure that the new content repository has a valid content dimension configuration. Also note that the old CR can sometimes have orphaned nodes. |
      | Failed to find parent node for node with id "test-node-id" and dimensions: []. Please ensure that the new content repository has a valid content dimension configuration. Also note that the old CR can sometimes have orphaned nodes.      |


  Scenario: Migration with rootNodeType configuration for all root nodes
    When I have the following node data rows:
      | Identifier        | Path             | Node Type             | Properties      |
      | sites-node-id     | /sites           | unstructured          |                 |
      | site-node-id      | /sites/test-site | Some.Package:Homepage | {"text": "foo"} |
      | test-root-node-id | /test            | unstructured          |                 |
      | test-node-id      | /test/test-site  | Some.Package:Homepage | {"text": "foo"} |
    And I run the event migration with rootNode mapping {"/sites": "Neos.Neos:Sites", "/test": "Neos.ContentRepository.LegacyNodeMigration:TestRoot"}
    Then I expect the following events to be exported
      | Type                                | Payload                                                                                                                                                                                                                                                                                          |
      | RootNodeAggregateWithNodeWasCreated | {"nodeAggregateId": "sites-node-id", "nodeTypeName": "Neos.Neos:Sites", "nodeAggregateClassification": "root"}                                                                                                                                                       |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "site-node-id", "nodeTypeName": "Some.Package:Homepage", "nodeName": "test-site", "parentNodeAggregateId": "sites-node-id", "nodeAggregateClassification": "regular", "initialPropertyValues": {"text": {"type": "string", "value": "foo"}}}     |
      | RootNodeAggregateWithNodeWasCreated | {"nodeAggregateId": "test-root-node-id", "nodeTypeName": "Neos.ContentRepository.LegacyNodeMigration:TestRoot", "nodeAggregateClassification": "root"}                                                                                                               |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "test-node-id", "nodeTypeName": "Some.Package:Homepage", "nodeName": "test-site", "parentNodeAggregateId": "test-root-node-id", "nodeAggregateClassification": "regular", "initialPropertyValues": {"text": {"type": "string", "value": "foo"}}} |
