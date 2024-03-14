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
          # deny all except one
          '*': false
          'Neos.ContentRepository.Testing:PrettyNode': true

    'Neos.ContentRepository.Testing.TetheredCollection':
      childNodes:
        collection:
          type: 'Neos.ContentRepository.Testing:RestrictedCollection'
          constraints:
            nodeTypes:
              # additionally allow this type
              'Neos.ContentRepository.Testing:Node': true

    'Neos.ContentRepository.Testing:PrettyNode': {}

    'Neos.ContentRepository.Testing:Node': {}

    'Neos.ContentRepository.Testing:UglyNode': {}
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"
    And I am in workspace "live"
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

  # Direct allowance via grandchild NodeType constraints overrule deny all on NodeType
  # issue https://github.com/neos/neos-development-collection/issues/4351
  Scenario: Tethered restricted collection
    Given the command CreateNodeAggregateWithNode is executed with payload:
      | Key                   | Value                                 |
      | nodeAggregateId       | "sir-david-nodenborough"              |
      | nodeTypeName          | "Neos.ContentRepository.Testing.TetheredCollection" |
      | parentNodeAggregateId | "lady-eleonode-rootford"              |
      | tetheredDescendantNodeAggregateIds | { "collection": "collection-node-id"} |
    And the graph projection is fully up to date
    Then I expect the node aggregate "sir-david-nodenborough" to exist
    Then I expect the node aggregate "collection-node-id" to exist
    # TetheredCollection
    #  ↳ RestrictedCollection (tethered)

    # allowed via parent node constraints: Node
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                 |
      | nodeAggregateId           | "nody-mc-nodeface"                    |
      | nodeTypeName              | "Neos.ContentRepository.Testing:Node" |
      | parentNodeAggregateId     | "collection-node-id"                  |
    And the graph projection is fully up to date
    Then I expect the node aggregate "nody-mc-nodeface" to exist

    # allowed via grant parent node constraints: PrettyNode
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                       |
      | nodeAggregateId           | "pretty-node"                               |
      | nodeTypeName              | "Neos.ContentRepository.Testing:PrettyNode" |
      | parentNodeAggregateId     | "collection-node-id"                        |
    And the graph projection is fully up to date
    Then I expect the node aggregate "pretty-node" to exist

    # disallowed via grant parent node constraints: UglyNode
    And the command CreateNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                       | Value                                     |
      | nodeAggregateId           | "nordisch-nodel"                          |
      | nodeTypeName              | "Neos.ContentRepository.Testing:UglyNode" |
      | parentNodeAggregateId     | "collection-node-id"                      |
    Then the last command should have thrown an exception of type "NodeConstraintException" with code 1520011791

  Scenario: Non-tethered restricted collection
    Given the command CreateNodeAggregateWithNode is executed with payload:
      | Key                   | Value                                 |
      | nodeAggregateId       | "sir-david-nodenborough"              |
      | nodeTypeName          | "Neos.ContentRepository.Testing:Node" |
      | parentNodeAggregateId | "lady-eleonode-rootford"              |
    And the graph projection is fully up to date
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                                 |
      | nodeAggregateId           | "collection-node-id"                                  |
      | nodeTypeName              | "Neos.ContentRepository.Testing:RestrictedCollection" |
      | parentNodeAggregateId     | "sir-david-nodenborough"                              |
    And the graph projection is fully up to date
    Then I expect the node aggregate "sir-david-nodenborough" to exist
    Then I expect the node aggregate "collection-node-id" to exist
    # Node
    #  ↳ RestrictedCollection

    # allowed via grant parent node constraints: PrettyNode
    When the command CreateNodeAggregateWithNode is executed with payload:
      | Key                       | Value                                       |
      | nodeAggregateId           | "pretty-node"                               |
      | nodeTypeName              | "Neos.ContentRepository.Testing:PrettyNode" |
      | parentNodeAggregateId     | "collection-node-id"                        |
    And the graph projection is fully up to date
    Then I expect the node aggregate "pretty-node" to exist

    # disallowed via grant parent node constraints: UglyNode
    And the command CreateNodeAggregateWithNode is executed with payload and exceptions are caught:
      | Key                       | Value                                     |
      | nodeAggregateId           | "nordisch-nodel"                          |
      | nodeTypeName              | "Neos.ContentRepository.Testing:UglyNode" |
      | parentNodeAggregateId     | "collection-node-id"                      |
    Then the last command should have thrown an exception of type "NodeConstraintException" with code 1707561400
