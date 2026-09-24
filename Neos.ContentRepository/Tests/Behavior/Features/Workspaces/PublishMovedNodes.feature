Feature: Publish moved nodes
  In order to publish moved nodes across workspaces
  As an API user of the content repository
  I need to ensure that all operations are always scoped to the active workspace

  Background:
    Given I have the following nodes:
      | Identifier                           | Path                              | Node Type                           | Properties           | Workspace |
      | ecf40ad1-3119-0a43-d02e-55f8b5aa3c70 | /sites                            | unstructured                        |                      | live      |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/content-repository         | Neos.ContentRepository.Testing:Page | {"title": "Home"}    | live      |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/content-repository/company | Neos.ContentRepository.Testing:Page | {"title": "Company"} | live      |
      | 52540602-b417-11e3-9358-14109fd7a2dd | /sites/content-repository/service | Neos.ContentRepository.Testing:Page | {"title": "Service"} | live      |
      | dc48851c-f653-ebd5-4d35-3feac69a3e09 | /sites/content-repository/about   | Neos.ContentRepository.Testing:Page | {"title": "About"}   | live      |
    And I am authenticated with role "Neos.Neos:Editor"
    And I have the following workspaces:
      | Name    | Base Workspace |
      | r1      | live           |
      | r2      | live           |
      | r2-user | r2             |

  @fixtures
  Scenario: Moving a node will never move child nodes in unrelated workspaces
    Given I get a node by path "/sites/content-repository/company" with the following context:
      | Workspace |
      | r1   |
    And I set the node property "title" to "Company c1"
    When I get a node by path "/sites/content-repository/company" with the following context:
      | Workspace |
      | r2-user   |
    And I set the node property "title" to "Company c2"
    And I publish the workspace "r2-user"
    And I move the node into the node with path "/sites/content-repository/about"
    And I publish the workspace "r2-user"
    # The `main` node will actually be at `/sites/content-repository/about/company/main` which is incorrect
    And I get a node by path "/sites/content-repository/company/main" with the following context:
      | Workspace |
      | live      |
    Then I should have one node
