@contentrepository @adapters=DoctrineDBAL,Postgres
Feature: Create a node aggregate with initial properties in different scopes

  As a user of the CR I want to create initial node properties with different scopes.

  Background:
    Given using the following content dimensions:
      | Identifier | Values       | Generalizations |
      | language   | mul, de, gsw | gsw->de->mul    |
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:Document':
      properties:
        unscopedProperty:
          type: string
          defaultValue: 'My string'
        nodeScopedProperty:
          type: string
          scope: node
          defaultValue: 'My string'
        specializationsScopedProperty:
          type: string
          scope: specializations
          defaultValue: 'My string'
        nodeAggregateScopedProperty:
          type: string
          scope: nodeAggregate
          defaultValue: 'My string'
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                  | Value                |
      | workspaceName        | "live"               |
      | workspaceTitle       | "Live"               |
      | workspaceDescription | "The live workspace" |
      | newContentStreamId   | "cs-identifier"      |
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and dimension space point {"language":"mul"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date

  Scenario: Create node with initial scoped properties
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                   | Value                                 |
      | contentStreamId       | "cs-identifier"                       |
      | nodeAggregateId       | "nody-mc-nodeface"                    |
      | nodeTypeName          | "Neos.ContentRepository.Testing:Document" |
      | parentNodeAggregateId | "lady-eleonode-rootford"              |
      | initialPropertyValues | {"unscopedProperty":"My new string","nodeScopedProperty":"My new string","specializationsScopedProperty":"My new string","nodeAggregateScopedProperty":"My new string"} |
    And the graph projection is fully up to date
    And the command CreateNodeVariant is executed with payload:
      | Key                     | Value              |
      | nodeAggregateId         | "nody-mc-nodeface" |
      | sourceOrigin            | {"language":"mul"} |
      | targetOrigin            | {"language":"de"}  |
    And the graph projection is fully up to date
    And the command CreateNodeVariant is executed with payload:
      | Key                     | Value              |
      | nodeAggregateId | "nody-mc-nodeface" |
      | sourceOrigin            | {"language":"mul"} |
      | targetOrigin            | {"language":"gsw"} |
    And the graph projection is fully up to date
    Then I expect a node identified by cs-identifier;nody-mc-nodeface;{"language":"mul"} to exist in the content graph
    And I expect this node to have the following properties:
      | Key                           | Value           |
      | unscopedProperty              | "My new string" |
      | nodeScopedProperty            | "My new string" |
      | specializationsScopedProperty | "My new string" |
      | nodeAggregateScopedProperty   | "My new string" |
    Then I expect a node identified by cs-identifier;nody-mc-nodeface;{"language":"de"} to exist in the content graph
    And I expect this node to have the following properties:
      | Key                           | Value           |
      | unscopedProperty              | "My new string" |
      | nodeScopedProperty            | "My new string" |
      | specializationsScopedProperty | "My new string" |
      | nodeAggregateScopedProperty   | "My new string" |
    Then I expect a node identified by cs-identifier;nody-mc-nodeface;{"language":"gsw"} to exist in the content graph
    And I expect this node to have the following properties:
      | Key                           | Value           |
      | unscopedProperty              | "My new string" |
      | nodeScopedProperty            | "My new string" |
      | specializationsScopedProperty | "My new string" |
      | nodeAggregateScopedProperty   | "My new string" |
