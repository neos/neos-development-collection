Feature: Remove node
  In order to remove nodes
  As an API user of the content repository
  I need support to remove nodes and child nodes

  Background:
    Given I have the following nodes:
      | Identifier                           | Path                         | Node Type                  | Properties           |
      | ecf40ad1-3119-0a43-d02e-55f8b5aa3c70 | /sites                       | unstructured               |                      |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/content-repository               | Neos.ContentRepository.Testing:Page | {"title": "Home"}    |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/content-repository/company       | Neos.ContentRepository.Testing:Page | {"title": "Company"} |
      | 52540602-b417-11e3-9358-14109fd7a2dd | /sites/content-repository/company/about | Neos.ContentRepository.Testing:Page | {"title": "About"}   |
    And I am authenticated with role "Neos.Neos:Editor"

  @fixtures
  Scenario: Remove a node in user workspace and publish removes the node itself
    When I get a node by path "/sites/content-repository/company" with the following context:
      | Workspace  |
      | user-admin |
    And I remove the node
    And I publish the workspace "user-admin"
    And I get a node by path "/sites/content-repository/company" with the following context:
      | Workspace |
      | live      |
    Then I should have 0 nodes

  @fixtures
  Scenario: Remove a node in user workspace and publish removes all child nodes
    When I get a node by path "/sites/content-repository/company" with the following context:
      | Workspace  |
      | user-admin |
    And I remove the node
    And I publish the workspace "user-admin"
    And I get a node by path "/sites/content-repository/company/about" with the following context:
      | Workspace |
      | live      |
    Then I should have 0 nodes

  @fixtures
  Scenario: Remove a node in user workspace and don't publish the changes
    When I get a node by path "/sites/content-repository/company" with the following context:
      | Workspace  |
      | user-admin |
    And I remove the node
    Then the unpublished node count in workspace "user-admin" should be 4

  @fixtures
  Scenario: Create and remove a node in a personal workspace without publishing it should leave no traces
    Given I have the following nodes:
      | Path                | Node Type                  | Properties        | Workspace  |
      | /sites/content-repository/test | Neos.ContentRepository.Testing:Page | {"title": "Test"} | user-admin |
    When I get a node by path "/sites/content-repository/test" with the following context:
      | Workspace  |
      | user-admin |
    And I remove the node
    Then the unpublished node count in workspace "user-admin" should be 0
