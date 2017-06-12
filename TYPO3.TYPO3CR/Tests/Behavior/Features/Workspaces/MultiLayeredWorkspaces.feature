Feature: Multi layered workspaces
  In order to publish nodes across nested workspaces
  As an API user of the content repository
  I need support to publish and move nodes and child nodes considering nested workspaces

  Background:
    Given I am authenticated with role "TYPO3.Neos:Administrator"
    And I have the following nodes:
      | Identifier                           | Path                   | Node Type                  | Properties              | Workspace |
      | ecf40ad1-3119-0a43-d02e-55f8b5aa3c70 | /sites                 | unstructured               |                         | live      |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/neos            | TYPO3.TYPO3CR.Testing:Page | {"title": "Home"}       | live      |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/neos/foundation | TYPO3.TYPO3CR.Testing:Page | {"title": "Foundation"} | live      |
      | 52540602-b417-11e3-9358-14109fd7a2dd | /sites/neos/service    | TYPO3.TYPO3CR.Testing:Page | {"title": "Service"}    | live      |
      | dc48851c-f653-ebd5-4d35-3feac69a3e09 | /sites/neos/about      | TYPO3.TYPO3CR.Testing:Page | {"title": "About"}      | live      |
    And I have the following workspaces:
      | Name       | Base Workspace |
      | staging    | live           |
      | campaign   | staging        |
      | user-admin | campaign       |

  # See https://github.com/neos/neos-development-collection/issues/1608
  @fixtures
  Scenario: Move node in nested workspace which has been modified earlier

    When I get a node by path "/sites/neos/foundation" with the following context:
      | Workspace  |
      | user-admin |
    And I set the node property "title" to "Foundation (changed)"
    And I publish the workspace "user-admin"
    And I get a node by path "/sites/neos/foundation" with the following context:
      | Workspace |
      | campaign  |
    Then I should have one node
    And the node property "title" should be "Foundation (changed)"

    When I get a node by path "/sites/neos/foundation" with the following context:
      | Workspace  |
      | user-admin |
    And I move the node into the node with path "/sites/neos/about"
    And I publish the workspace "user-admin"
    And I get a node by path "/sites/neos/about/foundation" with the following context:
      | Workspace  |
      | campaign |
    Then the node property "title" should be "Foundation (changed)"
    When I get a node by path "/sites/neos/foundation" with the following context:
      | Workspace  |
      | campaign |
    Then I should have 0 nodes
