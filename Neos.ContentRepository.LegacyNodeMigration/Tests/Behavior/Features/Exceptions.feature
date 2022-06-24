Feature: Exceptional cases during migrations

  Background:
    Given I have the following NodeTypes configuration:
    """
    'unstructured': []
    'Some.Package:SomeNodeType':
      properties:
        'text':
          type: string
          defaultValue: 'My default text'
    'Some.Package:SomeOtherNodeType': []
      """

  Scenario: Node variant with different type
    Given I have the following content dimensions:
      | Identifier | Default | Values     | Generalizations |
      | language   | en      | en, de, ch | ch->de          |
    When I have the following node data rows:
      | Identifier    | Path             | Node Type                      | Dimension Values     |
      | sites-node-id | /sites           | unstructured                   |                      |
      | site-node-id  | /sites/test-site | Some.Package:SomeNodeType      | {"language": ["de"]} |
      | site-node-id  | /sites/test-site | Some.Package:SomeOtherNodeType | {"language": ["en"]} |
    And I run the migration
    Then I expect a MigrationException with the message
    """
    Node aggregate with id "site-node-id" has a type of "Some.Package:SomeOtherNodeType" in content dimension [{"language":"en"}]. I was visited previously for content dimension [{"language":"de"}] with the type "Some.Package:SomeNodeType". Node variants must not have different types
    """

  Scenario: Node with missing parent
    Given I have no content dimensions
    When I have the following node data rows:
      | Identifier | Path       |
      | sites      | /sites     |
      | a          | /sites/a   |
      | c          | /sites/b/c |
    And I run the migration
    Then I expect a MigrationException with the message
    """
    Failed to find parent node for node with id "c" and dimensions: []
    """

  # TODO: is it possible that nodes are processed in an order where a ancestor node is processed after a child node? -> in that case the following example should work (i.e. the scenario should fail)
  Scenario: Nodes out of order
    Given I have no content dimensions
    When I have the following node data rows:
      | Identifier | Path       |
      | sites      | /sites     |
      | a          | /sites/a   |
      | c          | /sites/b/c |
      | b          | /sites/b   |
    And I run the migration
    Then I expect a MigrationException with the message
    """
    Failed to find parent node for node with id "c" and dimensions: []
    """

  Scenario: Invalid dimension configuration (unknown value)
    Given I have the following content dimensions:
      | Identifier | Default | Values     | Generalizations |
      | language   | en      | en, de, ch | ch->de          |
    When I have the following node data rows:
      | Identifier | Path     | Dimension Values          |
      | sites      | /sites   |                           |
      | a          | /sites/a | {"language": ["unknown"]} |
    And I run the migration
    Then I expect a MigrationException

  Scenario: Invalid dimension configuration (no json)
    Given I have no content dimensions
    When I have the following node data rows:
      | Identifier | Path     | Dimension Values |
      | sites      | /sites   |                  |
      | a          | /sites/a | not json         |
    And I run the migration
    Then I expect a MigrationException

  Scenario: Invalid node properties (no JSON)
    Given I have no content dimensions
    When I have the following node data rows:
      | Identifier | Path     | Properties | Node Type                 |
      | sites      | /sites   |            |                           |
      | a          | /sites/a | not json   | Some.Package:SomeNodeType |
    And I run the migration
    Then I expect a MigrationException with the message
    """
    Failed to decode properties "not json" of node "a" (type: "Some.Package:SomeNodeType")
    """

  Scenario: Node variants with the same dimension
    Given I have the following content dimensions:
      | Identifier | Default | Values     | Generalizations |
      | language   | en      | en, de, ch | ch->de          |
    When I have the following node data rows:
      | Identifier    | Path             | Dimension Values     |
      | sites-node-id | /sites           |                      |
      | site-node-id  | /sites/test-site | {"language": ["de"]} |
      | site-node-id  | /sites/test-site | {"language": ["ch"]} |
      | site-node-id  | /sites/test-site | {"language": ["ch"]} |
    And I run the migration
    Then I expect a MigrationException with the message
    """
    Node "site-node-id" with dimension space point "{"language":"ch"}" was already visited before
    """

  Scenario: Duplicate nodes
    Given I have the following content dimensions:
      | Identifier | Default | Values     | Generalizations |
      | language   | en      | en, de, ch | ch->de          |
    When I have the following node data rows:
      | Identifier    | Path             | Dimension Values     |
      | sites-node-id | /sites           |                      |
      | site-node-id  | /sites/test-site | {"language": ["de"]} |
      | site-node-id  | /sites/test-site | {"language": ["de"]} |
    And I run the migration
    Then I expect a MigrationException with the message
    """
    Node "site-node-id" for dimension {"language":"de"} was already created previously
    """
