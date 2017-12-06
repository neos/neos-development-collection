Feature: Multi layered workspaces
  In order to publish nodes across nested workspaces
  As an API user of the content repository
  I need support to publish and move nodes and child nodes considering nested workspaces

  Background:
    Given I am authenticated with role "Neos.Neos:Administrator"
    And I have the following nodes:
      | Identifier                           | Path                   | Node Type                           | Properties              | Workspace |
      | ecf40ad1-3119-0a43-d02e-55f8b5aa3c70 | /sites                 | unstructured                        |                         | live      |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/neos            | Neos.ContentRepository.Testing:Page | {"title": "Home"}       | live      |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/neos/foundation | Neos.ContentRepository.Testing:Page | {"title": "Foundation"} | live      |
      | 52540602-b417-11e3-9358-14109fd7a2dd | /sites/neos/service    | Neos.ContentRepository.Testing:Page | {"title": "Service"}    | live      |
      | dc48851c-f653-ebd5-4d35-3feac69a3e09 | /sites/neos/about      | Neos.ContentRepository.Testing:Page | {"title": "About"}      | live      |
    And I have the following workspaces:
      | Name       | Base Workspace |
      | staging    | live           |
      | campaign   | staging        |
      | user-admin | campaign       |
      | hotfix     | live           |
      | user-john  | hotfix         |

  # See https://github.com/neos/neos-development-collection/issues/1608
  @fixtures
  Scenario: Move node in nested workspace which has been modified earlier

    # First change the title of a page and publish it to "campaign":

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

    # Then move that same page and also publish that to "campaign":

    When I get a node by path "/sites/neos/foundation" with the following context:
      | Workspace  |
      | user-admin |
    And I move the node into the node with path "/sites/neos/about"
    And I publish the workspace "user-admin"
    And I get a node by path "/sites/neos/about/foundation" with the following context:
      | Workspace |
      | campaign  |
    Then the node property "title" should be "Foundation (changed)"
    When I get a node by path "/sites/neos/foundation" with the following context:
      | Workspace |
      | campaign  |
    Then I should have 0 nodes

    # Then publish the changes (move + title change) from "campaign" to "staging"
    # and check that shadow nodes are cleaned up correctly in "campaign":

    When I publish the workspace "campaign"
    And I get a node by path "/sites/neos/about/foundation" with the following context:
      | Workspace |
      | staging   |
    Then the node property "title" should be "Foundation (changed)"
    When I get a node by path "/sites/neos/foundation" with the following context:
      | Workspace |
      | staging   |
    Then I should have 0 nodes
    And the unpublished node count in workspace "campaign" should be 0

    When I publish the workspace "staging"
    And I get a node by path "/sites/neos/about/foundation" with the following context:
      | Workspace |
      | live      |
    Then the node property "title" should be "Foundation (changed)"
    When I get a node by path "/sites/neos/foundation" with the following context:
      | Workspace |
      | live      |
    Then I should have 0 nodes
    And the unpublished node count in workspace "staging" should be 0

  @fixtures
  Scenario: Move node in nested workspace which has been modified earlier and has a corresponding change in a separate workspace branch

    # Explanation:
    #
    # One user moves and modifies a page and publishes that to "hotfix", which is based on "live". Another user moves
    # and modifies the same page, but moves it to a different position, and publishes that to "campaign".
    #
    # Test then makes sure that publishing the changes subsequently from campaign to staging and then to live does
    # not affect the node or shadow node in "hotfix".

    # First change the title of a page and publish it to "hotfix":

    When I get a node by path "/sites/neos/foundation" with the following context:
      | Workspace |
      | user-john |
    And I set the node property "title" to "Foundation (hotfix)"
    And I publish the workspace "user-john"
    And I get a node by path "/sites/neos/foundation" with the following context:
      | Workspace |
      | hotfix    |
    Then I should have one node
    And the node property "title" should be "Foundation (hotfix)"

    # Then move that same page and also publish that to "hotfix":

    When I get a node by path "/sites/neos/foundation" with the following context:
      | Workspace |
      | user-john |
    And I move the node into the node with path "/sites/neos/service"
    And I publish the workspace "user-john"
    And I get a node by path "/sites/neos/service/foundation" with the following context:
      | Workspace |
      | hotfix    |
    Then the node property "title" should be "Foundation (hotfix)"
    When I get a node by path "/sites/neos/foundation" with the following context:
      | Workspace |
      | hotfix    |
    Then I should have 0 nodes

    # Now the same in "campaign": First change the title of a page and publish it to "campaign":

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

    # Then move that same page and also publish that to "campaign":

    When I get a node by path "/sites/neos/foundation" with the following context:
      | Workspace  |
      | user-admin |
    And I move the node into the node with path "/sites/neos/about"
    And I publish the workspace "user-admin"
    And I get a node by path "/sites/neos/about/foundation" with the following context:
      | Workspace |
      | campaign  |
    Then the node property "title" should be "Foundation (changed)"
    When I get a node by path "/sites/neos/foundation" with the following context:
      | Workspace |
      | campaign  |
    Then I should have 0 nodes

    # Then publish the changes (move + title change) from "campaign" to "staging" and check that shadow nodes
    # are cleaned up correctly in "campaign" and nodes are untouched in "hotfix":

    When I publish the workspace "campaign"
    And I get a node by path "/sites/neos/about/foundation" with the following context:
      | Workspace |
      | staging   |
    Then the node property "title" should be "Foundation (changed)"
    When I get a node by path "/sites/neos/foundation" with the following context:
      | Workspace |
      | staging   |
    Then I should have 0 nodes
    And the unpublished node count in workspace "campaign" should be 0

    When I get a node by path "/sites/neos/service/foundation" with the following context:
      | Workspace |
      | hotfix    |
    Then the node property "title" should be "Foundation (hotfix)"

    # Now publish "staging" to "live" and check again if "hotfix" is unchanged:

    When I publish the workspace "staging"
    And I get a node by path "/sites/neos/about/foundation" with the following context:
      | Workspace |
      | live      |
    Then the node property "title" should be "Foundation (changed)"
    When I get a node by path "/sites/neos/foundation" with the following context:
      | Workspace |
      | live      |
    Then I should have 0 nodes
    And the unpublished node count in workspace "staging" should be 0

    When I get a node by path "/sites/neos/service/foundation" with the following context:
      | Workspace |
      | hotfix    |
    Then the node property "title" should be "Foundation (hotfix)"

    # Finally publish "hotifx" to "live"! The page should now be moved again and show the right title:

    When I publish the workspace "hotfix"
    And I get a node by path "/sites/neos/service/foundation" with the following context:
      | Workspace |
      | live      |
    Then the node property "title" should be "Foundation (hotfix)"
    When I get a node by path "/sites/neos/about/foundation" with the following context:
      | Workspace |
      | live      |
    Then I should have 0 nodes
    And the unpublished node count in workspace "hotfix" should be 0

  @fixtures
  Scenario: Move node back and forth

    # See issue #1639

    When I get a node by path "/sites/neos/foundation" with the following context:
      | Workspace  |
      | user-admin |
    And I move the node into the node with path "/sites/neos/about"
    And I publish the workspace "user-admin"
    Then the unpublished node count in workspace "campaign" should be 4

    When I get a node by path "/sites/neos/about/foundation" with the following context:
      | Workspace  |
      | user-admin |
    And I set the node property "title" to "Changed"
    And I move the node after the node with path "/sites/neos/about"
    And  I get a node by path "/sites/neos/foundation" with the following context:
      | Workspace |
      | user-admin |
    Then I should have one node


    And I publish the workspace "user-admin"
    And  I get a node by path "/sites/neos/foundation" with the following context:
      | Workspace |
      | campaign  |
    Then I should have one node
    And the unpublished node count in workspace "user-admin" should be 0

    When I get a node by path "/sites/neos/about/foundation" with the following context:
      | Workspace |
      | campaign  |
    Then I should have 0 nodes
