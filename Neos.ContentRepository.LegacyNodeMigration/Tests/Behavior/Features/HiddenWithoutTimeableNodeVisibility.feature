@contentrepository
Feature: Simple migrations without content dimensions for hidden state migration without installed Neos.TimeableNodeVisibility

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

  Scenario: A node with a "hidden" property true must get disabled
    When I have the following node data rows:
      | Identifier    | Path             | Node Type             | Properties      | Hidden | Hidden after DateTime | Hidden before DateTime |
      | sites-node-id | /sites           | unstructured          |                 | 0      |                       |                        |
      | site-node-id  | /sites/test-site | Some.Package:Homepage | {"text": "foo"} | 1      |                       |                        |
    And I run the event migration for content stream "cs-id"
    Then I expect the following events to be exported
      | Type                                | Payload                                                                                                                                                                                                                                                                                      |
      | RootNodeAggregateWithNodeWasCreated | {"contentStreamId": "cs-id", "nodeAggregateId": "sites-node-id", "nodeTypeName": "Neos.Neos:Sites", "nodeAggregateClassification": "root"}                                                                                                                                                   |
      | NodeAggregateWithNodeWasCreated     | {"contentStreamId": "cs-id", "nodeAggregateId": "site-node-id", "nodeTypeName": "Some.Package:Homepage", "nodeName": "test-site", "parentNodeAggregateId": "sites-node-id", "nodeAggregateClassification": "regular", "initialPropertyValues": {"text": {"type": "string", "value": "foo"}}} |
      | SubtreeWasTagged                    | {"contentStreamId": "cs-id", "nodeAggregateId": "site-node-id", "tag": "disabled"}                                                                                                                                                                                                                              |

  Scenario: A node with a "hidden" property false must not get disabled
    When I have the following node data rows:
      | Identifier    | Path             | Node Type             | Properties      | Hidden | Hidden after DateTime | Hidden before DateTime |
      | sites-node-id | /sites           | unstructured          |                 | 0      |                       |                        |
      | site-node-id  | /sites/test-site | Some.Package:Homepage | {"text": "foo"} | 0      |                       |                        |
    And I run the event migration for content stream "cs-id"
    Then I expect the following events to be exported
      | Type                                | Payload                                                                                                                                                                                                                                                                                      |
      | RootNodeAggregateWithNodeWasCreated | {"contentStreamId": "cs-id", "nodeAggregateId": "sites-node-id", "nodeTypeName": "Neos.Neos:Sites", "nodeAggregateClassification": "root"}                                                                                                                                                   |
      | NodeAggregateWithNodeWasCreated     | {"contentStreamId": "cs-id", "nodeAggregateId": "site-node-id", "nodeTypeName": "Some.Package:Homepage", "nodeName": "test-site", "parentNodeAggregateId": "sites-node-id", "nodeAggregateClassification": "regular", "initialPropertyValues": {"text": {"type": "string", "value": "foo"}}} |

  Scenario: A node with active "hidden after" property, after a "hidden before" property must get disabled
    When I have the following node data rows:
      | Identifier    | Path             | Node Type             | Properties      | Hidden | Hidden after DateTime | Hidden before DateTime |
      | sites-node-id | /sites           | unstructured          |                 | 0      |                       |                        |
      | site-node-id  | /sites/test-site | Some.Package:Homepage | {"text": "foo"} | 0      | 1990-01-01 10:10:10   | 1989-01-01 10:10:10    |
    And I run the event migration for content stream "cs-id"
    Then I expect the following events to be exported
      | Type                                | Payload                                                                                                                                                                                                                                                                                      |
      | RootNodeAggregateWithNodeWasCreated | {"contentStreamId": "cs-id", "nodeAggregateId": "sites-node-id", "nodeTypeName": "Neos.Neos:Sites", "nodeAggregateClassification": "root"}                                                                                                                                                   |
      | NodeAggregateWithNodeWasCreated     | {"contentStreamId": "cs-id", "nodeAggregateId": "site-node-id", "nodeTypeName": "Some.Package:Homepage", "nodeName": "test-site", "parentNodeAggregateId": "sites-node-id", "nodeAggregateClassification": "regular", "initialPropertyValues": {"text": {"type": "string", "value": "foo"}}} |
      | SubtreeWasTagged                    | {"contentStreamId": "cs-id", "nodeAggregateId": "site-node-id", "tag": "disabled"}                                                                                                                                                                                                                              |
    And I expect the following warnings to be logged
      | Skipped the migration of your "hiddenBeforeDateTime" and "hiddenAfterDateTime" properties as your target NodeTypes do not inherit "Neos.TimeableNodeVisibility:Timeable". Please install neos/timeable-node-visibility, if you want to migrate them. |

  Scenario: A node with active "hidden before" property, after a "hidden after" property must not get disabled
    When I have the following node data rows:
      | Identifier    | Path             | Node Type             | Properties      | Hidden | Hidden after DateTime | Hidden before DateTime |
      | sites-node-id | /sites           | unstructured          |                 | 0      |                       |                        |
      | site-node-id  | /sites/test-site | Some.Package:Homepage | {"text": "foo"} | 0      | 1989-01-01 10:10:10   | 1990-01-01 10:10:10    |
    And I run the event migration for content stream "cs-id"
    Then I expect the following events to be exported
      | Type                                | Payload                                                                                                                                                                                                                                                                                      |
      | RootNodeAggregateWithNodeWasCreated | {"contentStreamId": "cs-id", "nodeAggregateId": "sites-node-id", "nodeTypeName": "Neos.Neos:Sites", "nodeAggregateClassification": "root"}                                                                                                                                                   |
      | NodeAggregateWithNodeWasCreated     | {"contentStreamId": "cs-id", "nodeAggregateId": "site-node-id", "nodeTypeName": "Some.Package:Homepage", "nodeName": "test-site", "parentNodeAggregateId": "sites-node-id", "nodeAggregateClassification": "regular", "initialPropertyValues": {"text": {"type": "string", "value": "foo"}}} |
    And I expect the following warnings to be logged
      | Skipped the migration of your "hiddenBeforeDateTime" and "hiddenAfterDateTime" properties as your target NodeTypes do not inherit "Neos.TimeableNodeVisibility:Timeable". Please install neos/timeable-node-visibility, if you want to migrate them. |

  Scenario: A node with a active "hidden before" property and a "hidden after" property in future must not get disabled
    When I have the following node data rows:
      | Identifier    | Path             | Node Type             | Properties      | Hidden | Hidden after DateTime | Hidden before DateTime |
      | sites-node-id | /sites           | unstructured          |                 | 0      |                       |                        |
      | site-node-id  | /sites/test-site | Some.Package:Homepage | {"text": "foo"} | 0      | 2099-01-01 10:10:10   | 1990-01-01 10:10:10    |
    And I run the event migration for content stream "cs-id"
    Then I expect the following events to be exported
      | Type                                | Payload                                                                                                                                                                                                                                                                                      |
      | RootNodeAggregateWithNodeWasCreated | {"contentStreamId": "cs-id", "nodeAggregateId": "sites-node-id", "nodeTypeName": "Neos.Neos:Sites", "nodeAggregateClassification": "root"}                                                                                                                                                   |
      | NodeAggregateWithNodeWasCreated     | {"contentStreamId": "cs-id", "nodeAggregateId": "site-node-id", "nodeTypeName": "Some.Package:Homepage", "nodeName": "test-site", "parentNodeAggregateId": "sites-node-id", "nodeAggregateClassification": "regular", "initialPropertyValues": {"text": {"type": "string", "value": "foo"}}} |
    And I expect the following warnings to be logged
      | Skipped the migration of your "hiddenBeforeDateTime" and "hiddenAfterDateTime" properties as your target NodeTypes do not inherit "Neos.TimeableNodeVisibility:Timeable". Please install neos/timeable-node-visibility, if you want to migrate them. |

  Scenario: A node with a active "hidden after" property and a "hidden before" property in future must get disabled
    When I have the following node data rows:
      | Identifier    | Path             | Node Type             | Properties      | Hidden | Hidden after DateTime | Hidden before DateTime |
      | sites-node-id | /sites           | unstructured          |                 | 0      |                       |                        |
      | site-node-id  | /sites/test-site | Some.Package:Homepage | {"text": "foo"} | 0      | 1990-01-01 10:10:10   | 2099-01-01 10:10:10    |
    And I run the event migration for content stream "cs-id"
    Then I expect the following events to be exported
      | Type                                | Payload                                                                                                                                                                                                                                                                                      |
      | RootNodeAggregateWithNodeWasCreated | {"contentStreamId": "cs-id", "nodeAggregateId": "sites-node-id", "nodeTypeName": "Neos.Neos:Sites", "nodeAggregateClassification": "root"}                                                                                                                                                   |
      | NodeAggregateWithNodeWasCreated     | {"contentStreamId": "cs-id", "nodeAggregateId": "site-node-id", "nodeTypeName": "Some.Package:Homepage", "nodeName": "test-site", "parentNodeAggregateId": "sites-node-id", "nodeAggregateClassification": "regular", "initialPropertyValues": {"text": {"type": "string", "value": "foo"}}} |
      | SubtreeWasTagged                    | {"contentStreamId": "cs-id", "nodeAggregateId": "site-node-id", "tag": "disabled"}                                                                                                                                                                                                                              |
    And I expect the following warnings to be logged
      | Skipped the migration of your "hiddenBeforeDateTime" and "hiddenAfterDateTime" properties as your target NodeTypes do not inherit "Neos.TimeableNodeVisibility:Timeable". Please install neos/timeable-node-visibility, if you want to migrate them. |

  Scenario: A node with a "hidden after" property in future and a "hidden before" property later in future must not get disabled
    When I have the following node data rows:
      | Identifier    | Path             | Node Type             | Properties      | Hidden | Hidden after DateTime | Hidden before DateTime |
      | sites-node-id | /sites           | unstructured          |                 | 0      |                       |                        |
      | site-node-id  | /sites/test-site | Some.Package:Homepage | {"text": "foo"} | 0      | 2098-01-01 10:10:10   | 2099-01-01 10:10:10    |
    And I run the event migration for content stream "cs-id"
    Then I expect the following events to be exported
      | Type                                | Payload                                                                                                                                                                                                                                                                                      |
      | RootNodeAggregateWithNodeWasCreated | {"contentStreamId": "cs-id", "nodeAggregateId": "sites-node-id", "nodeTypeName": "Neos.Neos:Sites", "nodeAggregateClassification": "root"}                                                                                                                                                   |
      | NodeAggregateWithNodeWasCreated     | {"contentStreamId": "cs-id", "nodeAggregateId": "site-node-id", "nodeTypeName": "Some.Package:Homepage", "nodeName": "test-site", "parentNodeAggregateId": "sites-node-id", "nodeAggregateClassification": "regular", "initialPropertyValues": {"text": {"type": "string", "value": "foo"}}} |
    And I expect the following warnings to be logged
      | Skipped the migration of your "hiddenBeforeDateTime" and "hiddenAfterDateTime" properties as your target NodeTypes do not inherit "Neos.TimeableNodeVisibility:Timeable". Please install neos/timeable-node-visibility, if you want to migrate them. |

  Scenario: A node with a "hidden before" property in future and a "hidden after" property later in future must get disabled
    When I have the following node data rows:
      | Identifier    | Path             | Node Type             | Properties      | Hidden | Hidden after DateTime | Hidden before DateTime |
      | sites-node-id | /sites           | unstructured          |                 | 0      |                       |                        |
      | site-node-id  | /sites/test-site | Some.Package:Homepage | {"text": "foo"} | 0      | 2099-01-01 10:10:10   | 2098-01-01 10:10:10    |
    And I run the event migration for content stream "cs-id"
    Then I expect the following events to be exported
      | Type                                | Payload                                                                                                                                                                                                                                                                                      |
      | RootNodeAggregateWithNodeWasCreated | {"contentStreamId": "cs-id", "nodeAggregateId": "sites-node-id", "nodeTypeName": "Neos.Neos:Sites", "nodeAggregateClassification": "root"}                                                                                                                                                   |
      | NodeAggregateWithNodeWasCreated     | {"contentStreamId": "cs-id", "nodeAggregateId": "site-node-id", "nodeTypeName": "Some.Package:Homepage", "nodeName": "test-site", "parentNodeAggregateId": "sites-node-id", "nodeAggregateClassification": "regular", "initialPropertyValues": {"text": {"type": "string", "value": "foo"}}} |
      | SubtreeWasTagged                    | {"contentStreamId": "cs-id", "nodeAggregateId": "site-node-id", "tag": "disabled"}                                                                                                                                                                                                                              |
    And I expect the following warnings to be logged
      | Skipped the migration of your "hiddenBeforeDateTime" and "hiddenAfterDateTime" properties as your target NodeTypes do not inherit "Neos.TimeableNodeVisibility:Timeable". Please install neos/timeable-node-visibility, if you want to migrate them. |

  Scenario: A node with a active "hidden before" property must not get disabled
    When I have the following node data rows:
      | Identifier    | Path             | Node Type             | Properties      | Hidden | Hidden after DateTime | Hidden before DateTime |
      | sites-node-id | /sites           | unstructured          |                 | 0      |                       |                        |
      | site-node-id  | /sites/test-site | Some.Package:Homepage | {"text": "foo"} | 0      |                       | 1990-01-01 10:10:10    |
    And I run the event migration for content stream "cs-id"
    Then I expect the following events to be exported
      | Type                                | Payload                                                                                                                                                                                                                                                                                      |
      | RootNodeAggregateWithNodeWasCreated | {"contentStreamId": "cs-id", "nodeAggregateId": "sites-node-id", "nodeTypeName": "Neos.Neos:Sites", "nodeAggregateClassification": "root"}                                                                                                                                                   |
      | NodeAggregateWithNodeWasCreated     | {"contentStreamId": "cs-id", "nodeAggregateId": "site-node-id", "nodeTypeName": "Some.Package:Homepage", "nodeName": "test-site", "parentNodeAggregateId": "sites-node-id", "nodeAggregateClassification": "regular", "initialPropertyValues": {"text": {"type": "string", "value": "foo"}}} |
    And I expect the following warnings to be logged
      | Skipped the migration of your "hiddenBeforeDateTime" and "hiddenAfterDateTime" properties as your target NodeTypes do not inherit "Neos.TimeableNodeVisibility:Timeable". Please install neos/timeable-node-visibility, if you want to migrate them. |

  Scenario: A node with a active "hidden after" property must get disabled
    When I have the following node data rows:
      | Identifier    | Path             | Node Type             | Properties      | Hidden | Hidden after DateTime | Hidden before DateTime |
      | sites-node-id | /sites           | unstructured          |                 | 0      |                       |                        |
      | site-node-id  | /sites/test-site | Some.Package:Homepage | {"text": "foo"} | 0      | 1990-01-01 10:10:10   |                        |
    And I run the event migration for content stream "cs-id"
    Then I expect the following events to be exported
      | Type                                | Payload                                                                                                                                                                                                                                                                                      |
      | RootNodeAggregateWithNodeWasCreated | {"contentStreamId": "cs-id", "nodeAggregateId": "sites-node-id", "nodeTypeName": "Neos.Neos:Sites", "nodeAggregateClassification": "root"}                                                                                                                                                   |
      | NodeAggregateWithNodeWasCreated     | {"contentStreamId": "cs-id", "nodeAggregateId": "site-node-id", "nodeTypeName": "Some.Package:Homepage", "nodeName": "test-site", "parentNodeAggregateId": "sites-node-id", "nodeAggregateClassification": "regular", "initialPropertyValues": {"text": {"type": "string", "value": "foo"}}} |
      | SubtreeWasTagged                    | {"contentStreamId": "cs-id", "nodeAggregateId": "site-node-id", "tag": "disabled"}                                                                                                                                                                                                                              |
    And I expect the following warnings to be logged
      | Skipped the migration of your "hiddenBeforeDateTime" and "hiddenAfterDateTime" properties as your target NodeTypes do not inherit "Neos.TimeableNodeVisibility:Timeable". Please install neos/timeable-node-visibility, if you want to migrate them. |

  Scenario: A node with a "hidden after" property in future must not get disabled
    When I have the following node data rows:
      | Identifier    | Path             | Node Type             | Properties      | Hidden | Hidden after DateTime | Hidden before DateTime |
      | sites-node-id | /sites           | unstructured          |                 | 0      |                       |                        |
      | site-node-id  | /sites/test-site | Some.Package:Homepage | {"text": "foo"} | 0      | 2099-01-01 10:10:10   |                        |
    And I run the event migration for content stream "cs-id"
    Then I expect the following events to be exported
      | Type                                | Payload                                                                                                                                                                                                                                                                                      |
      | RootNodeAggregateWithNodeWasCreated | {"contentStreamId": "cs-id", "nodeAggregateId": "sites-node-id", "nodeTypeName": "Neos.Neos:Sites", "nodeAggregateClassification": "root"}                                                                                                                                                   |
      | NodeAggregateWithNodeWasCreated     | {"contentStreamId": "cs-id", "nodeAggregateId": "site-node-id", "nodeTypeName": "Some.Package:Homepage", "nodeName": "test-site", "parentNodeAggregateId": "sites-node-id", "nodeAggregateClassification": "regular", "initialPropertyValues": {"text": {"type": "string", "value": "foo"}}} |
    And I expect the following warnings to be logged
      | Skipped the migration of your "hiddenBeforeDateTime" and "hiddenAfterDateTime" properties as your target NodeTypes do not inherit "Neos.TimeableNodeVisibility:Timeable". Please install neos/timeable-node-visibility, if you want to migrate them. |

  Scenario: A node with a "hidden before" property in future must get disabled
    When I have the following node data rows:
      | Identifier    | Path             | Node Type             | Properties      | Hidden | Hidden after DateTime | Hidden before DateTime |
      | sites-node-id | /sites           | unstructured          |                 | 0      |                       |                        |
      | site-node-id  | /sites/test-site | Some.Package:Homepage | {"text": "foo"} | 0      |                       | 2099-01-01 10:10:10    |
    And I run the event migration for content stream "cs-id"
    Then I expect the following events to be exported
      | Type                                | Payload                                                                                                                                                                                                                                                                                      |
      | RootNodeAggregateWithNodeWasCreated | {"contentStreamId": "cs-id", "nodeAggregateId": "sites-node-id", "nodeTypeName": "Neos.Neos:Sites", "nodeAggregateClassification": "root"}                                                                                                                                                   |
      | NodeAggregateWithNodeWasCreated     | {"contentStreamId": "cs-id", "nodeAggregateId": "site-node-id", "nodeTypeName": "Some.Package:Homepage", "nodeName": "test-site", "parentNodeAggregateId": "sites-node-id", "nodeAggregateClassification": "regular", "initialPropertyValues": {"text": {"type": "string", "value": "foo"}}} |
      | SubtreeWasTagged                    | {"contentStreamId": "cs-id", "nodeAggregateId": "site-node-id", "tag": "disabled"}                                                                                                                                                                                                                              |
    And I expect the following warnings to be logged
      | Skipped the migration of your "hiddenBeforeDateTime" and "hiddenAfterDateTime" properties as your target NodeTypes do not inherit "Neos.TimeableNodeVisibility:Timeable". Please install neos/timeable-node-visibility, if you want to migrate them. |
