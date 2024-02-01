@contentrepository @adapters=DoctrineDBAL,Postgres
Feature: Create node aggregate with node

  As a user of the CR I want to define NodeType constraints which will restrict the allowed child nodes
  in a specific dimension space point.

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.ContentRepository.Testing:RestrictedCollection':
      constraints:
        nodeTypes:
          # deny all
          '*': false

    'Neos.ContentRepository.Testing.ConstrainedChildNodes':
      childNodes:
        collection:
          type: 'Neos.ContentRepository.Testing:RestrictedCollection'
          constraints:
            nodeTypes:
              # only allow this type
              'Neos.ContentRepository.Testing:Node': true

    'Neos.ContentRepository.Testing:Node': {}

    'Neos.ContentRepository.Testing:UglyNode': {}
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
    And I am in content stream "cs-identifier"
    And I am in dimension space point {}
    And the command CreateRootNodeAggregateWithNode is executed with payload:
      | Key             | Value                         |
      | nodeAggregateId | "lady-eleonode-rootford"      |
      | nodeTypeName    | "Neos.ContentRepository:Root" |
    And the graph projection is fully up to date

  Scenario: Direct allowance of grandchild NodeType constraints overrule deny all on NodeType
    # issue https://github.com/neos/neos-development-collection/issues/4351
    Given the command CreateNodeAggregateWithNode is executed with payload:
      | Key                   | Value                                 |
      | nodeAggregateId       | "sir-david-nodenborough"              |
      | nodeTypeName          | "Neos.ContentRepository.Testing.ConstrainedChildNodes" |
      | parentNodeAggregateId | "lady-eleonode-rootford"              |
      | tetheredDescendantNodeAggregateIds | { "collection": "collection-node-id"} |
    And the graph projection is fully up to date
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                 |
      | nodeAggregateId           | "nody-mc-nodeface"                    |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Node" |
      | parentNodeAggregateId     | "collection-node-id"                  |
    And the graph projection is fully up to date
    And I expect the node aggregate "sir-david-nodenborough" to exist
    And I expect the node aggregate "collection-node-id" to exist
    And I expect this node aggregate to have the child node aggregates ["nody-mc-nodeface"]
