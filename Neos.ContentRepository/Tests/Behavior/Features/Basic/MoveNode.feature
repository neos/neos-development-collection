Feature: Move node
  In order to move nodes
  As an API user of the content repository
  I need support to move nodes and child nodes considering workspaces

  Background:
    Given I have the following nodes:
      | Identifier                           | Path                              | Node Type                           | Properties           | Workspace |
      | ecf40ad1-3119-0a43-d02e-55f8b5aa3c70 | /sites                            | unstructured                        |                      | live      |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/content-repository         | Neos.ContentRepository.Testing:Page | {"title": "Home"}    | live      |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/content-repository/company | Neos.ContentRepository.Testing:Page | {"title": "Company"} | live      |
      | 52540602-b417-11e3-9358-14109fd7a2dd | /sites/content-repository/service | Neos.ContentRepository.Testing:Page | {"title": "Service"} | live      |
      | dc48851c-f653-ebd5-4d35-3feac69a3e09 | /sites/content-repository/about   | Neos.ContentRepository.Testing:Page | {"title": "About"}   | live      |
    And I am authenticated with role "Neos.Neos:Editor"

  @fixtures
  Scenario: Move a node (into) in user workspace and get by path
    When I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace  |
      | user-admin |
    And I move the node into the node with path "/sites/content-repository/company"
    And I get a node by path "/sites/content-repository/company/service" with the following context:
      | Workspace  |
      | user-admin |
    Then I should have one node
    And I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace  |
      | user-admin |
    Then I should have 0 nodes

  @fixtures
  Scenario: Move an unpublished node (into) in user workspace and get by identifier
    Given I have the following nodes:
      | Identifier                           | Path                                | Node Type                           | Properties             | Workspace  |
      | c0fe0360-399b-11e8-b7ef-b7f4bc6ded24 | /sites/content-repository/service-1 | Neos.ContentRepository.Testing:Page | {"title": "Service 1"} | user-admin |
    When I get a node by path "/sites/content-repository/service-1" with the following context:
      | Workspace  |
      | user-admin |
    And I move the node into the node with path "/sites/content-repository/service"
    And I get a node by identifier "c0fe0360-399b-11e8-b7ef-b7f4bc6ded24" with the following context:
      | Workspace  |
      | user-admin |
    Then I should have the following nodes:
      | Path                                        |
      | /sites/content-repository/service/service-1 |

  @fixtures
  Scenario: Move a node (into) in user workspace and get nodes on path
    When I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace  |
      | user-admin |
    And I move the node into the node with path "/sites/content-repository/company"
    And I get the nodes on path "/sites/content-repository" to "/sites/content-repository/company/service" with the following context:
      | Workspace  |
      | user-admin |
    Then I should have 3 nodes
    And I get the nodes on path "/sites/content-repository" to "/sites/content-repository/service" with the following context:
      | Workspace  |
      | user-admin |
    Then I should have one node

  @fixtures
  Scenario: Move a node (into) in user workspace and get child nodes
    When I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace  |
      | user-admin |
    And I move the node into the node with path "/sites/content-repository/company"
    And I get the child nodes of "/sites/content-repository" with filter "Neos.ContentRepository.Testing:Document" and the following context:
      | Workspace  |
      | user-admin |
    Then I should have the following nodes:
      | Path                   |
      | /sites/content-repository/company |
      | /sites/content-repository/about   |
    And I get the child nodes of "/sites/content-repository/company" with filter "Neos.ContentRepository.Testing:Document" and the following context:
      | Workspace  |
      | user-admin |
    Then I should have the following nodes:
      | Path                           |
      | /sites/content-repository/company/service |

  @fixtures
  Scenario: Move a node (into) in user workspace and publish single node
    When I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace  |
      | user-admin |
    And I move the node into the node with path "/sites/content-repository/company"
    When I get a node by path "/sites/content-repository/company/service" with the following context:
      | Workspace  |
      | user-admin |
    And I publish the node
    And I get a node by path "/sites/content-repository/company/service" with the following context:
      | Workspace |
      | live      |
    Then I should have one node
    # TODO Publish a single node should publish auto-created child-nodes
    # And the unpublished node count in workspace "user-admin" should be 0

  @fixtures
  Scenario: Move a node (into) in user workspace node with content collection and publish all
    Given I have the following nodes:
      | Identifier                           | Path                                          | Node Type                                | Workspace  |
      | 829d0d76-47fc-11e4-886a-14109fd7a2dd | /sites/content-repository/company/main/text1  | Neos.ContentRepository.Testing:Text      | live       |
      | 07fcc3b2-47fd-11e4-bf41-14109fd7a2dd | /sites/content-repository/company/main/twocol | Neos.ContentRepository.Testing:TwoColumn | user-admin |
    When I get a node by path "/sites/content-repository/company/main/text1" with the following context:
      | Workspace  |
      | user-admin |
    And I move the node into the node with path "/sites/content-repository/company/main/twocol/column0"
    And I publish the workspace "user-admin"
    And I get a node by path "/sites/content-repository/company/main/twocol/column0/text1" with the following context:
      | Workspace |
      | live      |
    Then I should have one node

  @fixtures
  Scenario: Move a node (into) and move it back
    When I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace  |
      | user-admin |
    And I move the node into the node with path "/sites/content-repository/company"
    When I get a node by path "/sites/content-repository/company/service" with the following context:
      | Workspace  |
      | user-admin |
    And I move the node into the node with path "/sites/content-repository"

    When I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace  |
      | user-admin |
    Then I should have one node

    When I get a node by path "/sites/content-repository/company/service" with the following context:
      | Workspace  |
      | user-admin |
    Then I should have 0 nodes

    When I publish the workspace "user-admin"
    And the unpublished node count in workspace "user-admin" should be 0

  @fixtures
  Scenario: Move a node (before) in user workspace and get by path
    Given I have the following nodes:
      | Identifier                           | Path                                    | Node Type                           | Properties         | Workspace |
      | a282e974-2dd2-11e4-ae5a-14109fd7a2dd | /sites/content-repository/company/about | Neos.ContentRepository.Testing:Page | {"title": "About"} | live      |
    When I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace  |
      | user-admin |
    And I move the node before the node with path "/sites/content-repository/company/about"
    And I get the child nodes of "/sites/content-repository/company" with filter "Neos.ContentRepository.Testing:Document" and the following context:
      | Workspace  |
      | user-admin |
    Then I should have the following nodes:
      | Path                           |
      | /sites/content-repository/company/service |
      | /sites/content-repository/company/about   |
    And I get a node by path "/sites/content-repository/company/service" with the following context:
      | Workspace  |
      | user-admin |
    Then I should have one node
    And I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace  |
      | user-admin |
    Then I should have 0 nodes

  @fixtures
  Scenario: Move a node (after) in user workspace and get by path
    Given I have the following nodes:
      | Identifier                           | Path                                    | Node Type                           | Properties         | Workspace |
      | a282e974-2dd2-11e4-ae5a-14109fd7a2dd | /sites/content-repository/company/about | Neos.ContentRepository.Testing:Page | {"title": "About"} | live      |
    When I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace  |
      | user-admin |
    And I move the node after the node with path "/sites/content-repository/company/about"
    And I get the child nodes of "/sites/content-repository/company" with filter "Neos.ContentRepository.Testing:Document" and the following context:
      | Workspace  |
      | user-admin |
    Then I should have the following nodes:
      | Path                           |
      | /sites/content-repository/company/about   |
      | /sites/content-repository/company/service |
    And I get a node by path "/sites/content-repository/company/service" with the following context:
      | Workspace  |
      | user-admin |
    Then I should have one node
    And I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace  |
      | user-admin |
    Then I should have 0 nodes

  @fixtures
  Scenario: Edit moved node in separate workspace and publish edited node after moving was published
    When I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace |
      | user-john |
    And I set the node property "title" to "Our services"

    When I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace |
      | user-jack |
    And I move the node into the node with path "/sites/content-repository/company"
    And I publish the workspace "user-jack"
    And I publish the workspace "user-john"

    And I get a node by path "/sites/content-repository/company/service" with the following context:
      | Workspace |
      | live      |
    Then I should have one node
    And the node property "title" should be "Our services"
    And the unpublished node count in workspace "user-jack" should be 0
    And the unpublished node count in workspace "user-john" should be 0

  @fixtures
  Scenario: Reordering nodes only applies in user workspace
    When I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace  |
      | user-admin |
    And I move the node before the node with path "/sites/content-repository/company"
    When I get the child nodes of "/sites/content-repository" with filter "Neos.ContentRepository.Testing:Document" and the following context:
      | Workspace  |
      | user-admin |
    Then I should have the following nodes:
      | Path                   | Properties           |
      | /sites/content-repository/service | {"title": "Service"} |
      | /sites/content-repository/company | {"title": "Company"} |
      | /sites/content-repository/about   | {"title": "About"}   |
    When I get the child nodes of "/sites/content-repository" with filter "Neos.ContentRepository.Testing:Document" and the following context:
      | Workspace |
      | live      |
    Then I should have the following nodes:
      | Path                   | Properties           |
      | /sites/content-repository/company | {"title": "Company"} |
      | /sites/content-repository/service | {"title": "Service"} |
      | /sites/content-repository/about   | {"title": "About"}   |

  @fixtures
  Scenario: Reordering nodes can be published
    When I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace  |
      | user-admin |
    And I move the node before the node with path "/sites/content-repository/company"
    And I publish the workspace "user-admin"
    And I get the child nodes of "/sites/content-repository" with filter "Neos.ContentRepository.Testing:Document" and the following context:
      | Workspace |
      | live      |
    Then I should have the following nodes:
      | Path                   | Properties           |
      | /sites/content-repository/service | {"title": "Service"} |
      | /sites/content-repository/company | {"title": "Company"} |
      | /sites/content-repository/about   | {"title": "About"}   |
    And the unpublished node count in workspace "user-admin" should be 0

  @fixtures
  Scenario: Move a node with updated property (materialized node data) in user workspace
    When I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace  |
      | user-admin |
    And I set the node property "title" to "Our service"
    And I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace  |
      | user-admin |
    And I move the node into the node with path "/sites/content-repository/company"
    And I get a node by path "/sites/content-repository/company/service" with the following context:
      | Workspace  |
      | user-admin |
    Then I should have one node
    And I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace  |
      | user-admin |
    Then I should have 0 nodes

  @fixtures
  Scenario: Move a new node in user workspace
    Given I have the following nodes:
      | Identifier                           | Path                                          | Node Type                                | Workspace  |
      | 829d0d76-47fc-11e4-886a-14109fd7a2dd | /sites/content-repository/company/main/text1  | Neos.ContentRepository.Testing:Text      | user-admin |
    When I get a node by path "/sites/content-repository/company/main/text1" with the following context:
      | Workspace  |
      | user-admin |
    And I move the node into the node with path "/sites/content-repository/service/main"

    When I get a node by path "/sites/content-repository/service/main/text1" with the following context:
      | Workspace  |
      | user-admin |
    Then I should have one node

    When I get a node by path "/sites/content-repository/company/main/text1" with the following context:
      | Workspace  |
      | user-admin |
    Then I should have 0 nodes

  @fixtures
  Scenario: Moving a node on the same level in user workspace
    When I get a node by path "/sites/content-repository/company" with the following context:
      | Workspace  |
      | user-admin |
    And I move the node after the node with path "/sites/content-repository/about"
    And I get the child nodes of "/sites/content-repository" with filter "Neos.ContentRepository.Testing:Document" and the following context:
      | Workspace  |
      | user-admin |
    Then I should have 3 nodes
    Then I should have the following nodes:
      | Path                           |
      | /sites/content-repository/service |
      | /sites/content-repository/about   |
      | /sites/content-repository/company |

    When I get a node by path "/sites/content-repository/about" with the following context:
      | Workspace  |
      | user-admin |
    Then I should have one node
    And I move the node before the node with path "/sites/content-repository/service"
    And I get the child nodes of "/sites/content-repository" with filter "Neos.ContentRepository.Testing:Document" and the following context:
      | Workspace  |
      | user-admin |
    Then I should have 3 nodes
    Then I should have the following nodes:
      | Path                           |
      | /sites/content-repository/about   |
      | /sites/content-repository/service |
      | /sites/content-repository/company |

  @fixtures
  Scenario: Move a new document node in user workspace and publish
    Given I have the following nodes:
      | Identifier                           | Path                                            | Node Type                              | Workspace  |
      | 646ea354-c421-11e4-9f08-14109fd7a2dd | /sites/content-repository/service/breaking-news | Neos.ContentRepository.Testing:Chapter | user-admin |
    When I get a node by path "/sites/content-repository/service/breaking-news" with the following context:
      | Workspace  |
      | user-admin |
    And I move the node into the node with path "/sites/content-repository/company"

    When I get a node by path "/sites/content-repository/company/breaking-news" with the following context:
      | Workspace  |
      | user-admin |
    Then I should have one node

    When I get a node by path "/sites/content-repository/service/breaking-news" with the following context:
      | Workspace  |
      | user-admin |
    Then I should have 0 nodes

    When I publish the workspace "user-admin"

    And I get a node by identifier "646ea354-c421-11e4-9f08-14109fd7a2dd" with the following context:
      | Workspace  |
      | user-admin |
    Then I should have one node

  @fixtures
  Scenario: Move a published node twice and publish
    Given I have the following nodes:
      | Identifier                           | Path                                 | Node Type                           | Workspace |
      | cf96e226-6fdb-11e4-aa3f-14109fd7a2dd | /sites/content-repository/main/text1 | Neos.ContentRepository.Testing:Text | live      |
    When I get a node by path "/sites/content-repository/main/text1" with the following context:
      | Workspace  |
      | user-admin |
    And I move the node into the node with path "/sites/content-repository/company/main"
    And I get a node by path "/sites/content-repository/company/main/text1" with the following context:
      | Workspace  |
      | user-admin |
    And I move the node into the node with path "/sites/content-repository/service/main"
    And I publish the workspace "user-admin"
    And the unpublished node count in workspace "user-admin" should be 0

  @fixtures
  Scenario: Move a published node twice in columns and publish
    Given I have the following nodes:
      | Identifier                           | Path                                   | Node Type                                | Workspace |
      | cf96e226-6fdb-11e4-aa3f-14109fd7a2dd | /sites/content-repository/main/text1   | Neos.ContentRepository.Testing:Text      | live      |
      | be085b0e-73da-11e4-994f-14109fd7a2dd | /sites/content-repository/main/two-col | Neos.ContentRepository.Testing:TwoColumn | live      |
    When I get a node by path "/sites/content-repository/main/text1" with the following context:
      | Workspace  |
      | user-admin |
    And I move the node into the node with path "/sites/content-repository/main/two-col/column0"
    And I get a node by path "/sites/content-repository/main/two-col/column0/text1" with the following context:
      | Workspace  |
      | user-admin |
    And I move the node into the node with path "/sites/content-repository/main/two-col/column1"
    And I get a node by path "/sites/content-repository/main/two-col/column1/text1" with the following context:
      | Workspace  |
      | user-admin |
    Then I should have one node

    When I publish the workspace "user-admin"
    Then the unpublished node count in workspace "user-admin" should be 0
    When I get a node by path "/sites/content-repository/main/two-col/column1/text1" with the following context:
      | Workspace  |
      | user-admin |
    Then I should have one node
    When I get a node by path "/sites/content-repository/main/two-col/column0/text1" with the following context:
      | Workspace  |
      | user-admin |
    Then I should have 0 nodes
    When I get a node by path "/sites/content-repository/main/text1" with the following context:
      | Workspace  |
      | user-admin |
    Then I should have 0 nodes

  @fixtures
  Scenario: Move a node one level up and discard workspace
    Given I have the following nodes:
      | Identifier                           | Path                                       | Node Type                           | Workspace |
      | d2294ce8-73de-11e4-a420-14109fd7a2dd | /sites/content-repository/company/about-us | Neos.ContentRepository.Testing:Page | live      |
    When I get a node by path "/sites/content-repository/company/about-us" with the following context:
      | Workspace  |
      | user-admin |
    And I move the node into the node with path "/sites/content-repository"
    And I discard all changes in the workspace "user-admin"

    When I get a node by path "/sites/content-repository/company/about-us" with the following context:
      | Workspace  |
      | user-admin |
    Then I should have one node
    When I get a node by path "/sites/content-repository/about-us" with the following context:
      | Workspace  |
      | user-admin |
    Then I should have 0 nodes

  @fixtures
  Scenario: Move a node to another parent and back again
    Given I have the following nodes:
      | Identifier                           | Path                                          | Node Type                           | Workspace |
      | d2294ce8-73de-11e4-a420-14109fd7a2dd | /sites/content-repository/company/about-us    | Neos.ContentRepository.Testing:Page | live      |
      | c41d35bf-27e5-5645-a290-6a8b35c5935a | /sites/content-repository/company/departments | Neos.ContentRepository.Testing:Page | live      |

    When I get a node by path "/sites/content-repository/company/about-us" with the following context:
      | Workspace  |
      | user-admin |
    And I move the node into the node with path "/sites/content-repository/service"

    When I get a node by path "/sites/content-repository/service/about-us" with the following context:
      | Workspace  |
      | user-admin |
    Then I should have one node

    When I get a node by path "/sites/content-repository/company/about-us" with the following context:
      | Workspace  |
      | user-admin |
    Then I should have 0 nodes

    When I get a node by path "/sites/content-repository/service/about-us" with the following context:
      | Workspace  |
      | user-admin |
    Then I should have one node
    And I move the node into the node with path "/sites/content-repository/company"

    When I get a node by path "/sites/content-repository/company/about-us" with the following context:
      | Workspace  |
      | user-admin |
    Then I should have one node

    When I get a node by path "/sites/content-repository/service/about-us" with the following context:
      | Workspace  |
      | user-admin |
    Then I should have 0 nodes

    When I get the child nodes of "/sites/content-repository/company" with filter "Neos.ContentRepository.Testing:Document" and the following context:
      | Workspace  |
      | user-admin |
    Then I should have the following nodes:
      | Path                               |
      | /sites/content-repository/company/departments |
      | /sites/content-repository/company/about-us    |


  @fixtures
  Scenario: Move a node that was changed in another workspace and finally publish.
    Given I have the following nodes:
      | Identifier                           | Path                                               | Node Type                           | Properties            | Workspace   |
      | 0990ad05-cce6-4241-af8f-f0c77cbd9583 | /sites/content-repository/company/history          | Neos.ContentRepository.Testing:Page | {"title": "history"}  | user-editor |
    And I get a node by path "/sites/content-repository/company" with the following context:
      | Workspace   |
      | user-editor |
    And I set the node property "title" to "We"
    When I get a node by path "/sites/content-repository/company" with the following context:
      | Workspace  |
      | user-admin |
    And I move the node into the node with path "/sites/content-repository/about"
    And I publish the workspace "user-admin"
    And I publish the workspace "user-editor"
    And I get a node by path "/sites/content-repository/about/company" with the following context:
      | Workspace |
      | live      |
    Then I should have one node
    And the node property "title" should be "We"
    When I get a node by path "/sites/content-repository/about/company/history" with the following context:
      | Workspace |
      | live      |
    Then I should have one node

