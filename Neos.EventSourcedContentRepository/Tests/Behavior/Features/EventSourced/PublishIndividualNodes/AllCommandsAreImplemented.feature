@fixtures
Feature: Publishing hide/show scenario of nodes

  Node structure is as follows:
  - rn-identifier (root node)
  -- na-identifier (name=text1) <== this one is modified and published
  --- cna-identifier (name=text2)
  -- na2-identifier (name=image) <== this one is modified

  The setup is always as follows:
  - we modify two nodes using a certain command (e.g. HideNode) in the USER workspace
  - we publish one of them
  - we check that the user workspace still sees both nodes as hidden; and the live workspace only sees one of the changes.

  We do the same for other commands. This way, we ensure that both the command works generally;
  and the matchesNodeAddress() address of the command is actually implemented somehow properly.


  Background:
    Given I have no content dimensions
    And the command CreateWorkspace is executed with payload:
      | Key                     | Value           |
      | workspaceName           | "live"          |
      | contentStreamIdentifier | "cs-identifier" |
      | rootNodeIdentifier      | "rn-identifier" |
    And I have the following NodeTypes configuration:
    """
    Neos.ContentRepository:Root: {}
    'Neos.ContentRepository.Testing:Content':
      properties:
        text:
          type: string
    'Neos.ContentRepository.Testing:Image':
      properties:
        image:
          type: string
    """
    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                               |
      | contentStreamIdentifier       | "cs-identifier"                                     |
      | nodeAggregateIdentifier       | "na-identifier"                                     |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content"            |
      | nodeIdentifier                | "node-identifier"                                   |
      | parentNodeIdentifier          | "rn-identifier"                                     |
      | nodeName                      | "text1"                                             |
      | propertyDefaultValuesAndTypes | {"text": {"type": "string", "value": "Initial t1"}} |

    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                               |
      | contentStreamIdentifier       | "cs-identifier"                                     |
      | nodeAggregateIdentifier       | "cna-identifier"                                    |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content"            |
      | nodeIdentifier                | "cnode-identifier"                                  |
      | parentNodeIdentifier          | "node-identifier"                                   |
      | nodeName                      | "text2"                                             |
      | propertyDefaultValuesAndTypes | {"text": {"type": "string", "value": "Initial t2"}} |

    # create the "na2-node" node
    And the Event NodeAggregateWithNodeWasCreated was published with payload:
      | Key                           | Value                                                  |
      | contentStreamIdentifier       | "cs-identifier"                                        |
      | nodeAggregateIdentifier       | "na2-identifier"                                       |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Image"                 |
      | nodeIdentifier                | "imagenode-identifier"                                 |
      | parentNodeIdentifier          | "rn-identifier"                                        |
      | nodeName                      | "image"                                                |
      | propertyDefaultValuesAndTypes | {"image": {"type": "image", "value": "Initial image"}} |
    And the graph projection is fully up to date

  Scenario: (HideNode) It is possible to publish hiding of a node.
    Given the command CreateWorkspace is executed with payload:
      | Key                     | Value             |
      | workspaceName           | "user-test"       |
      | baseWorkspaceName       | "live"            |
      | contentStreamIdentifier | "cs-2-identifier" |
    And the graph projection is fully up to date

    # SETUP: hide two nodes in USER workspace
    Given the command "HideNode" is executed with payload:
      | Key                          | Value             |
      | contentStreamIdentifier      | "cs-2-identifier" |
      | nodeAggregateIdentifier      | "na-identifier"   |
      | affectedDimensionSpacePoints | [{}]              |
    Given the command "HideNode" is executed with payload:
      | Key                          | Value             |
      | contentStreamIdentifier      | "cs-2-identifier" |
      | nodeAggregateIdentifier      | "na2-identifier"  |
      | affectedDimensionSpacePoints | [{}]              |
    And the graph projection is fully up to date

    When the command "PublishIndividualNodesFromWorkspace" is executed with payload:
      | Key           | Value                                                                                                                   |
      | workspaceName | "user-test"                                                                                                             |
      | nodeAddresses | [{"nodeAggregateIdentifier": "na-identifier", "contentStreamIdentifier": "cs-2-identifier", "dimensionSpacePoint": {}}] |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and Dimension Space Point {}
    Then I expect a node identified by aggregate identifier "na-identifier" not to exist in the subgraph
    Then I expect a node identified by aggregate identifier "cna-identifier" not to exist in the subgraph
    Then I expect a node identified by aggregate identifier "na2-identifier" to exist in the subgraph

    When I am in the active content stream of workspace "user-test" and Dimension Space Point {}
    Then I expect a node identified by aggregate identifier "na-identifier" not to exist in the subgraph
    Then I expect a node identified by aggregate identifier "cna-identifier" not to exist in the subgraph
    Then I expect a node identified by aggregate identifier "na2-identifier" not to exist in the subgraph


  Scenario: (ShowNode) It is possible to publish showing of a node.
    # BEFORE: ensure two nodes are hidden in live (and user WS)
    Given the command "HideNode" is executed with payload:
      | Key                          | Value           |
      | contentStreamIdentifier      | "cs-identifier" |
      | nodeAggregateIdentifier      | "na-identifier" |
      | affectedDimensionSpacePoints | [{}]            |
    Given the command "HideNode" is executed with payload:
      | Key                          | Value            |
      | contentStreamIdentifier      | "cs-identifier"  |
      | nodeAggregateIdentifier      | "na2-identifier" |
      | affectedDimensionSpacePoints | [{}]             |
    Given the command CreateWorkspace is executed with payload:
      | Key                     | Value             |
      | workspaceName           | "user-test"       |
      | baseWorkspaceName       | "live"            |
      | contentStreamIdentifier | "cs-2-identifier" |
    And the graph projection is fully up to date

    # SETUP: show two nodes in USER workspace
    Given the command "ShowNode" is executed with payload:
      | Key                          | Value             |
      | contentStreamIdentifier      | "cs-2-identifier" |
      | nodeAggregateIdentifier      | "na-identifier"   |
      | affectedDimensionSpacePoints | [{}]              |
    Given the command "ShowNode" is executed with payload:
      | Key                          | Value             |
      | contentStreamIdentifier      | "cs-2-identifier" |
      | nodeAggregateIdentifier      | "na2-identifier"  |
      | affectedDimensionSpacePoints | [{}]              |
    And the graph projection is fully up to date

    When the command "PublishIndividualNodesFromWorkspace" is executed with payload:
      | Key           | Value                                                                                                                   |
      | workspaceName | "user-test"                                                                                                             |
      | nodeAddresses | [{"nodeAggregateIdentifier": "na-identifier", "contentStreamIdentifier": "cs-2-identifier", "dimensionSpacePoint": {}}] |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and Dimension Space Point {}
    Then I expect a node identified by aggregate identifier "na-identifier" to exist in the subgraph
    Then I expect a node identified by aggregate identifier "cna-identifier" to exist in the subgraph
    Then I expect a node identified by aggregate identifier "na2-identifier" not to exist in the subgraph

    When I am in the active content stream of workspace "user-test" and Dimension Space Point {}
    Then I expect a node identified by aggregate identifier "na-identifier" to exist in the subgraph
    Then I expect a node identified by aggregate identifier "cna-identifier" to exist in the subgraph
    Then I expect a node identified by aggregate identifier "na2-identifier" to exist in the subgraph


  Scenario: (ChangeNodeName) It is possible to publish changing the node name.
    Given the command CreateWorkspace is executed with payload:
      | Key                     | Value             |
      | workspaceName           | "user-test"       |
      | baseWorkspaceName       | "live"            |
      | contentStreamIdentifier | "cs-2-identifier" |
    And the graph projection is fully up to date

    # SETUP: change two node names in USER workspace
    Given the command "ChangeNodeName" is executed with payload:
      | Key                     | Value             |
      | contentStreamIdentifier | "cs-2-identifier" |
      | nodeIdentifier          | "node-identifier" |
      | newNodeName             | "text1mod"        |
    Given the command "ChangeNodeName" is executed with payload:
      | Key                     | Value                  |
      | contentStreamIdentifier | "cs-2-identifier"      |
      | nodeIdentifier          | "imagenode-identifier" |
      | newNodeName             | "imagemod"             |
    And the graph projection is fully up to date

    When the command "PublishIndividualNodesFromWorkspace" is executed with payload:
      | Key           | Value                                                                                                                   |
      | workspaceName | "user-test"                                                                                                             |
      | nodeAddresses | [{"nodeAggregateIdentifier": "na-identifier", "contentStreamIdentifier": "cs-2-identifier", "dimensionSpacePoint": {}}] |
    And the graph projection is fully up to date

    When I am in the active content stream of workspace "live" and Dimension Space Point {}
    Then I expect the node aggregate "root" to have the following child nodes:
      | Name     | NodeIdentifier       |
      | text1mod | node-identifier      |
      | image    | imagenode-identifier |

    When I am in the active content stream of workspace "user-test" and Dimension Space Point {}
    Then I expect the node aggregate "root" to have the following child nodes:
      | Name     | NodeIdentifier       |
      | text1mod | node-identifier      |
      | imagemod | imagenode-identifier |

