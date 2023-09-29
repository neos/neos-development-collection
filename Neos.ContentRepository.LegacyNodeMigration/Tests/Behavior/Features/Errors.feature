@contentrepository
Feature: Exceptional cases during migrations

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'unstructured': []
    'Some.Package:SomeNodeType':
      properties:
        'text':
          type: string
          defaultValue: 'My default text'
    'Some.Package:SomeOtherNodeType': []
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"

  Scenario: Node variant with different type
    Given I change the content dimensions in content repository "default" to:
      | Identifier | Default | Values     | Generalizations |
      | language   | en      | en, de, ch | ch->de          |
    When I have the following node data rows:
      | Identifier    | Path             | Node Type                      | Dimension Values     |
      | sites-node-id | /sites           | unstructured                   |                      |
      | site-node-id  | /sites/test-site | Some.Package:SomeNodeType      | {"language": ["de"]} |
      | site-node-id  | /sites/test-site | Some.Package:SomeOtherNodeType | {"language": ["en"]} |
    And I run the event migration
    Then I expect a MigrationError with the message
    """
    Node aggregate with id "site-node-id" has a type of "Some.Package:SomeOtherNodeType" in content dimension [{"language":"en"}]. I was visited previously for content dimension [{"language":"de"}] with the type "Some.Package:SomeNodeType". Node variants must not have different types
    """

    # Note: The behavior was changed with https://github.com/neos/neos-development-collection/pull/4201
  Scenario: Node with missing parent
    When I have the following node data rows:
      | Identifier | Path       |
      | sites      | /sites     |
      | a          | /sites/a   |
      | c          | /sites/b/c |
    And I run the event migration
    Then I expect the following events to be exported
      | Type                                | Payload                                                                                                |
      | RootNodeAggregateWithNodeWasCreated | {"nodeAggregateId": "sites", "nodeTypeName": "Neos.Neos:Sites", "nodeAggregateClassification": "root"} |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "a", "parentNodeAggregateId": "sites"}                                             |
    And I expect the following errors to be logged
      | Failed to find parent node for node with id "c" and dimensions: []. Please ensure that the new content repository has a valid content dimension configuration. Also note that the old CR can sometimes have orphaned nodes. |


    # Note: The behavior was changed with https://github.com/neos/neos-development-collection/pull/4201
  Scenario: Nodes out of order
    When I have the following node data rows:
      | Identifier | Path       |
      | sites      | /sites     |
      | a          | /sites/a   |
      | c          | /sites/b/c |
      | b          | /sites/b   |
    And I run the event migration
    Then I expect the following events to be exported
      | Type                                | Payload                                                                                                |
      | RootNodeAggregateWithNodeWasCreated | {"nodeAggregateId": "sites", "nodeTypeName": "Neos.Neos:Sites", "nodeAggregateClassification": "root"} |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "a", "parentNodeAggregateId": "sites"}                                             |
      | NodeAggregateWithNodeWasCreated     | {"nodeAggregateId": "b", "parentNodeAggregateId": "sites"}                                             |
    And I expect the following errors to be logged
      | Failed to find parent node for node with id "c" and dimensions: []. Please ensure that the new content repository has a valid content dimension configuration. Also note that the old CR can sometimes have orphaned nodes. |


  Scenario: Invalid dimension configuration (unknown value)
    Given I change the content dimensions in content repository "default" to:
      | Identifier | Default | Values     | Generalizations |
      | language   | en      | en, de, ch | ch->de          |
    When I have the following node data rows:
      | Identifier | Path     | Dimension Values          |
      | sites      | /sites   |                           |
      | a          | /sites/a | {"language": ["unknown"]} |
    And I run the event migration
    Then I expect the following events to be exported
      | Type                                | Payload                                                                                                |
      | RootNodeAggregateWithNodeWasCreated | {"nodeAggregateId": "sites", "nodeTypeName": "Neos.Neos:Sites", "nodeAggregateClassification": "root"} |
    And I expect the following errors to be logged
      | Failed to find parent node for node with id "a" and dimensions: {"language":"unknown"}. Please ensure that the new content repository has a valid content dimension configuration. Also note that the old CR can sometimes have orphaned nodes. |

  Scenario: Invalid dimension configuration (no json)
    When I have the following node data rows:
      | Identifier | Path     | Dimension Values |
      | sites      | /sites   |                  |
      | a          | /sites/a | not json         |
    And I run the event migration
    Then I expect a MigrationError

  Scenario: Invalid node properties (no JSON)
    When I have the following node data rows:
      | Identifier | Path     | Properties | Node Type                 |
      | sites      | /sites   |            |                           |
      | a          | /sites/a | not json   | Some.Package:SomeNodeType |
    And I run the event migration
    Then I expect a MigrationError with the message
    """
    Failed to decode properties "not json" of node "a" (type: "Some.Package:SomeNodeType"): Could not convert database value "not json" to Doctrine Type flow_json_array
    """

  Scenario: Node variants with the same dimension
    Given I change the content dimensions in content repository "default" to:
      | Identifier | Default | Values     | Generalizations |
      | language   | en      | en, de, ch | ch->de          |
    When I have the following node data rows:
      | Identifier    | Path             | Dimension Values     |
      | sites-node-id | /sites           |                      |
      | site-node-id  | /sites/test-site | {"language": ["de"]} |
      | site-node-id  | /sites/test-site | {"language": ["ch"]} |
      | site-node-id  | /sites/test-site | {"language": ["ch"]} |
    And I run the event migration
    Then I expect a MigrationError with the message
    """
    Node "site-node-id" with dimension space point "{"language":"ch"}" was already visited before
    """

  Scenario: Duplicate nodes
    Given I change the content dimensions in content repository "default" to:
      | Identifier | Default | Values     | Generalizations |
      | language   | en      | en, de, ch | ch->de          |
    When I have the following node data rows:
      | Identifier    | Path             | Dimension Values     |
      | sites-node-id | /sites           |                      |
      | site-node-id  | /sites/test-site | {"language": ["de"]} |
      | site-node-id  | /sites/test-site | {"language": ["de"]} |
    And I run the event migration
    Then I expect a MigrationError with the message
    """
    Node "site-node-id" for dimension {"language":"de"} was already created previously
    """
