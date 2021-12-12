@fixtures
Feature: Rebasing auto-created nodes works

  Tests a bugfix for auto-created nodes, which appeared in Neos UI.

  Basic fixture setup is:
  - root workspace with a single "root" node inside.
  - then, a nested workspace user-test is created
  - In the user-test workspace, we create a new node with auto-created child nodes WITHOUT SPECIFYING THE
  NESTED NODE IDENTIFIERS (tetheredDescendantNodeAggregateIdentifiers)
  - then, for the auto-created child node, set a property.
  - finally, try to rebase the whole thing.

  This operation only is successful if the auto-created child node's node identifier is the same during the
  rebase as in the original content stream -- and this was not the case in Neos for some time.

  Background:
    Given I have no content dimensions
    And I have the following NodeTypes configuration:
    """
    'Neos.ContentRepository:Root': {}
    'Neos.ContentRepository.Testing:Content':
      childNodes:
        foo:
          type: 'Neos.ContentRepository.Testing:ContentNested'
    'Neos.ContentRepository.Testing:ContentNested':
      properties:
        text:
          type: string
    """
    And the command CreateRootWorkspace is executed with payload:
      | Key                        | Value           |
      | workspaceName              | "live"          |
      | newContentStreamIdentifier | "cs-identifier" |
      | initiatingUserIdentifier   | "user-id"       |
    And the graph projection is fully up to date
    And the event RootNodeAggregateWithNodeWasCreated was published with payload:
      | Key                         | Value                         |
      | contentStreamIdentifier     | "cs-identifier"               |
      | nodeAggregateIdentifier     | "lady-eleonode-rootford"      |
      | nodeTypeName                | "Neos.ContentRepository:Root" |
      | coveredDimensionSpacePoints | [{}]                          |
      | initiatingUserIdentifier    | "initiating-user-identifier"  |
      | nodeAggregateClassification | "root"                        |
    And the graph projection is fully up to date

    And the command CreateWorkspace is executed with payload:
      | Key                        | Value                |
      | workspaceName              | "user-test"          |
      | baseWorkspaceName          | "live"               |
      | newContentStreamIdentifier | "user-cs-identifier" |
      | initiatingUserIdentifier   | "user"               |
    And the graph projection is fully up to date

  Scenario: complex scenario (to reproduce the bug) -- see the feature description
    # USER workspace: create a new node with auto-created child nodes
    When the command CreateNodeAggregateWithNodeAndSerializedProperties is executed with payload:
      | Key                           | Value                                    |
      | contentStreamIdentifier       | "user-cs-identifier"                     |
      | nodeAggregateIdentifier       | "nody-mc-nodeface"                       |
      | nodeTypeName                  | "Neos.ContentRepository.Testing:Content" |
      | nodeName                      | "mcnodeface"                             |
      | originDimensionSpacePoint     | {}                                       |
      | initiatingUserIdentifier      | "00000000-0000-0000-0000-000000000000"   |
      | parentNodeAggregateIdentifier | "lady-eleonode-rootford"                 |
    And the graph projection is fully up to date
    And I am in content stream "user-cs-identifier" and dimension space point {}
    Then I expect node aggregate identifier "nody-mc-nodeface" to lead to node user-cs-identifier;nody-mc-nodeface;{}
    When I get the node at path "mcnodeface/foo"
    And I expect this node to be a child of node user-cs-identifier;nody-mc-nodeface;{}

    # - then, for the auto-created child node, set a property.
    When the command "SetSerializedNodeProperties" is executed with payload:
      | Key                       | Value                                          |
      | contentStreamIdentifier   | "user-cs-identifier"                           |
      | nodeAggregateIdentifier   | $this->currentNodeAggregateIdentifier          |
      | originDimensionSpacePoint | {}                                             |
      | propertyValues            | {"text": {"value":"Modified","type":"string"}} |
      | initiatingUserIdentifier  | "initiating-user-identifier"                   |
    And the graph projection is fully up to date

    When the command "RebaseWorkspace" is executed with payload:
      | Key                      | Value                        |
      | workspaceName            | "user-test"                  |
      | initiatingUserIdentifier | "initiating-user-identifier" |
    And the graph projection is fully up to date
    # This should properly work; no error.

