@contentrepository @adapters=DoctrineDBAL,Postgres
Feature: Set node properties with different scopes

  As a user of the CR I want to modify node properties with different scopes.

  Background:
    Given I have the following content dimensions:
      | Identifier | Values       | Generalizations |
      | language   | mul, de, gsw | gsw->de->mul    |
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': []
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
    And I am user identified by "initiating-user-identifier"
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value                |
      | workspaceName              | "live"               |
      | workspaceTitle             | "Live"               |
      | workspaceDescription       | "The live workspace" |
      | newContentStreamIdentifier | "cs-identifier"      |
    And the graph projection is fully up to date
    And I am in content stream "cs-identifier" and dimension space point {"language":"mul"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                     | Value                         |
      | nodeAggregateIdentifier | "lady-eleonode-rootford"      |
      | nodeTypeName            | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date
    # We have to add another node since root nodes have no dimension space points and thus cannot be varied
    # Node /document
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateIdentifier | nodeName | parentNodeAggregateIdentifier | nodeTypeName                            |
      | nody-mc-nodeface        | document | lady-eleonode-rootford        | Neos.ContentRepository.Testing:Document |
    And the command CreateNodeVariant is executed with payload:
      | Key                     | Value              |
      | nodeAggregateIdentifier | "nody-mc-nodeface" |
      | sourceOrigin            | {"language":"mul"} |
      | targetOrigin            | {"language":"de"}  |
    And the graph projection is fully up to date
    And the command CreateNodeVariant is executed with payload:
      | Key                     | Value              |
      | nodeAggregateIdentifier | "nody-mc-nodeface" |
      | sourceOrigin            | {"language":"mul"} |
      | targetOrigin            | {"language":"gsw"} |
    And the graph projection is fully up to date

  Scenario: Set node properties
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                                                                                                                                                                      |
      | contentStreamIdentifier   | "cs-identifier"                                                                                                                                                            |
      | nodeAggregateIdentifier   | "nody-mc-nodeface"                                                                                                                                                         |
      | originDimensionSpacePoint | {"language": "de"}                                                                                                                                                         |
      | propertyValues            | {"unscopedProperty":"My new string", "nodeScopedProperty":"My new string", "specializationsScopedProperty":"My new string", "nodeAggregateScopedProperty":"My new string"} |
    And the graph projection is fully up to date
    Then I expect a node identified by cs-identifier;nody-mc-nodeface;{"language":"mul"} to exist in the content graph
    And I expect this node to have the following properties:
      | Key                           | Value           |
      | unscopedProperty              | "My string"     |
      | nodeScopedProperty            | "My string"     |
      | specializationsScopedProperty | "My string"     |
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
      | unscopedProperty              | "My string"     |
      | nodeScopedProperty            | "My string"     |
      | specializationsScopedProperty | "My new string" |
      | nodeAggregateScopedProperty   | "My new string" |
