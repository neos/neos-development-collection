@fixtures
Feature: Publishing hide/show scenario of nodes

  Publishing an individual node works
  Node structure is as follows:
  - rn-identifier (root node)
  -- na-identifier (name=text1)
  --- cna-identifier (name=text2)
  -- na2-identifier (name=image)


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

    # Create user workspace
    And the command CreateWorkspace is executed with payload:
      | Key                     | Value             |
      | workspaceName           | "user-test"       |
      | baseWorkspaceName       | "live"            |
      | contentStreamIdentifier | "cs-2-identifier" |
    And the graph projection is fully up to date

  Scenario: It is possible to publish hiding of a node.
    # Hide "na"-Node
    Given the command "HideNode" is executed with payload:
      | Key                          | Value             |
      | contentStreamIdentifier      | "cs-2-identifier" |
      | nodeAggregateIdentifier      | "na-identifier"   |
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

  # TODO: show node again
