Feature: Rename node
  In order to rename nodes
  As an API user of the content repository
  I need support to rename nodes and child nodes considering workspaces

  Background:
    Given I have the following nodes:
      | Identifier                           | Path                                       | Node Type                           | Properties                   | Workspace |
      | ecf40ad1-3119-0a43-d02e-55f8b5aa3c70 | /sites                                     | unstructured                        |                              | live      |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/content-repository                  | Neos.ContentRepository.Testing:Page | {"title": "Home"}            | live      |
      | 52540602-b417-11e3-9358-14109fd7a2dd | /sites/content-repository/service          | Neos.ContentRepository.Testing:Page | {"title": "Service"}         | live      |
      | bdbc9add-800b-6613-8a86-263858cc7964 | /sites/content-repository/service/request  | Neos.ContentRepository.Testing:Page | {"title": "Service Request"} | live      |
      | c41d35bf-27e5-5645-a290-6a8b35c5935a | /sites/content-repository/company          | Neos.ContentRepository.Testing:Page | {"title": "Company"}         | live      |
      | 23ebd69e-4e0e-27e6-a41a-42dd14df615f | /sites/content-repository/company/about-us | Neos.ContentRepository.Testing:Page | {"title": "About us"}        | live      |
    And I am authenticated with role "Neos.Neos:Editor"

  @fixtures
  Scenario: Rename a non-materialized node
    When I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace  |
      | user-admin |
    And I rename the node to "services"
    And I get a node by path "/sites/content-repository/services" with the following context:
      | Workspace  |
      | user-admin |
    Then I should have one node
    When I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace  |
      | user-admin |
    Then I should have 0 nodes

  @fixtures
  Scenario: Rename a materialized node
    When I get a node by path "/sites/content-repository" with the following context:
      | Workspace  |
      | user-admin |
    And I set some property and rename the node to "content-repository-test"
    And I get a node by path "/sites/content-repository" with the following context:
      | Workspace  |
      | user-admin |
    Then I should have 0 nodes
    When I get a node by path "/sites/content-repository-test" with the following context:
      | Workspace  |
      | user-admin |
    Then I should have one node
    When I get a node by path "/sites/content-repository-test/service" with the following context:
      | Workspace  |
      | user-admin |
    Then I should have one node

  @fixtures
  Scenario: Rename a node to a name conflicting with an existing node
    When I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace  |
      | user-admin |
    Then I should not be able to rename the node to "company"

  @fixtures
  Scenario: Rename a node to the name of a previously renamed node
    When I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace  |
      | user-admin |
    And I rename the node to "services2"
    And I get a node by path "/sites/content-repository/company" with the following context:
      | Workspace  |
      | user-admin |
    And I rename the node to "service"
    And I get a node by path "/sites/content-repository/company" with the following context:
      | Workspace  |
      | user-admin |
    Then I should have 0 nodes

  @fixtures
  Scenario: Rename a node with updated property (materialized node data) to a previously moved path in user workspace
    When I get a node by path "/sites/content-repository/company" with the following context:
      | Workspace  |
      | user-admin |
    And I rename the node to "enterprise"
    And I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace  |
      | user-admin |
    And I set the node property "title" to "Our service"
    And I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace  |
      | user-admin |
    And I rename the node to "company"
    And I get a node by path "/sites/content-repository/company" with the following context:
      | Workspace  |
      | user-admin |
    Then I should have one node
    And I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace  |
      | user-admin |
    Then I should have 0 nodes

    When I publish the workspace "user-admin"
    Then the unpublished node count in workspace "user-admin" should be 0
