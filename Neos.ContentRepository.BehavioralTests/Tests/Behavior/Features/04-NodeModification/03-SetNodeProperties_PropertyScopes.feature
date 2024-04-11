@contentrepository @adapters=DoctrineDBAL,Postgres
Feature: Set node properties with different scopes

  As a user of the CR I want to modify node properties with different scopes.

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
      | Key                        | Value                |
      | workspaceName              | "live"               |
      | workspaceTitle             | "Live"               |
      | workspaceDescription       | "The live workspace" |
      | newContentStreamId | "cs-identifier"      |
    And I am in the active content stream of workspace "live" and dimension space point {"language":"mul"}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key                     | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName            | "Neos.ContentRepository:Root" |
    # We have to add another node since root nodes have no dimension space points and thus cannot be varied
    # Node /document
    And the following CreateNodeAggregateWithNode commands are executed:
      | nodeAggregateId | nodeName | parentNodeAggregateId | nodeTypeName                            |
      | nody-mc-nodeface        | document | lady-eleonode-rootford        | Neos.ContentRepository.Testing:Document |
    And the command CreateNodeVariant is executed with payload:
      | Key                     | Value              |
      | nodeAggregateId | "nody-mc-nodeface" |
      | sourceOrigin            | {"language":"mul"} |
      | targetOrigin            | {"language":"de"}  |
    And the command CreateNodeVariant is executed with payload:
      | Key                     | Value              |
      | nodeAggregateId | "nody-mc-nodeface" |
      | sourceOrigin            | {"language":"mul"} |
      | targetOrigin            | {"language":"gsw"} |

  Scenario: Set node properties
    And the command SetNodeProperties is executed with payload:
      | Key                       | Value                                                                                                                                                                      |
      | nodeAggregateId   | "nody-mc-nodeface"                                                                                                                                                         |
      | originDimensionSpacePoint | {"language": "de"}                                                                                                                                                         |
      | propertyValues            | {"unscopedProperty":"My new string", "nodeScopedProperty":"My new string", "specializationsScopedProperty":"My new string", "nodeAggregateScopedProperty":"My new string"} |
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
