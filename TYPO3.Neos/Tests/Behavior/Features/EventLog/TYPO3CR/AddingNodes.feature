Feature: Adding Nodes
  As an API user of the history
  I expect that adding a node triggers history updates

  Background:
    Given I have the following nodes:
      | Identifier                           | Path           | Node Type                  | Properties        | Workspace |
      | ecf40ad1-3119-0a43-d02e-55f8b5aa3c70 | /sites         | unstructured               |                   | live      |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/typo3cr | TYPO3.TYPO3CR.Testing:Page | {"title": "Home"} | live      |
    And I have an empty history

  @fixtures
  Scenario: Add a new document node to live workspace
    Given I am authenticated with role "Neos.Neos:Editor"
    And I create the following nodes:
      | Identifier                           | Path                    | Node Type                  | Properties            | Workspace |
      | 75a28524-6a48-11e4-bd7d-7831c1d118bc | /sites/typo3cr/features | TYPO3.TYPO3CR.Testing:Page | {"title": "Features"} | live      |
    Then I should have the following history entries:
      | ID | Event Type   | Node Identifier                      | Document Node Identifier             | Workspace | Parent Event | Explanation                             |
      | n  | Node.Added   | 75a28524-6a48-11e4-bd7d-7831c1d118bc | 75a28524-6a48-11e4-bd7d-7831c1d118bc | live      |              | "features" node                         |
      |    | Node.Added   |                                      | 75a28524-6a48-11e4-bd7d-7831c1d118bc | live      | n            | auto-created "features/main"            |
      |    | Node.Updated | 75a28524-6a48-11e4-bd7d-7831c1d118bc | 75a28524-6a48-11e4-bd7d-7831c1d118bc | live      |              | set property "title" on "features" node |


  @fixtures
  Scenario: Add a new document node in user workspace, and publish it
    Given I am authenticated with role "Neos.Neos:Editor"
    And I create the following nodes:
      | Identifier                           | Path                    | Node Type                  | Properties            | Workspace  |
      | 75a28524-6a48-11e4-bd7d-7831c1d118bc | /sites/typo3cr/features | TYPO3.TYPO3CR.Testing:Page | {"title": "Features"} | user-admin |
    And I publish the workspace "user-admin"
    Then I should have the following history entries:
      | ID | Event Type     | Node Identifier                      | Document Node Identifier             | Workspace  | Parent Event | Explanation                             |
      | n  | Node.Added     | 75a28524-6a48-11e4-bd7d-7831c1d118bc | 75a28524-6a48-11e4-bd7d-7831c1d118bc | user-admin | p            | "features" node                         |
      |    | Node.Added     |                                      | 75a28524-6a48-11e4-bd7d-7831c1d118bc | user-admin | n            | auto-created "features/main"            |
      |    | Node.Updated   | 75a28524-6a48-11e4-bd7d-7831c1d118bc | 75a28524-6a48-11e4-bd7d-7831c1d118bc | user-admin | p            | set property "title" on "features" node |
      | p  | Node.Published | 75a28524-6a48-11e4-bd7d-7831c1d118bc | 75a28524-6a48-11e4-bd7d-7831c1d118bc | live       |              | "publish" event of the "features" node  |

  @fixtures
  Scenario: Adding multiple document nodes in user workspace and publishing them yields one publish-event per document
    Given I am authenticated with role "Neos.Neos:Editor"
    And I create the following nodes:
      | Identifier                           | Path                    | Node Type                  | Properties            | Workspace  |
      | 75a28524-6a48-11e4-bd7d-7831c1d118bc | /sites/typo3cr/features | TYPO3.TYPO3CR.Testing:Page | {"title": "Features"} | user-admin |
      | 9c881754-6a51-11e4-9026-7831c1d118bc | /sites/typo3cr/about-us | TYPO3.TYPO3CR.Testing:Page | {"title": "About Us"} | user-admin |
    And I publish the workspace "user-admin"
    Then I should have the following history entries (ignoring order):
      | ID | Event Type     | Node Identifier                      | Document Node Identifier             | Workspace  | Parent Event | Explanation                             |
      | p  | Node.Published | 75a28524-6a48-11e4-bd7d-7831c1d118bc | 75a28524-6a48-11e4-bd7d-7831c1d118bc | live       |              | "publish" event of the "features" node  |
      | n  | Node.Added     | 75a28524-6a48-11e4-bd7d-7831c1d118bc | 75a28524-6a48-11e4-bd7d-7831c1d118bc | user-admin | p            | "features" node                         |
      |    | Node.Added     |                                      | 75a28524-6a48-11e4-bd7d-7831c1d118bc | user-admin | n            | auto-created "features/main"            |
      |    | Node.Updated   | 75a28524-6a48-11e4-bd7d-7831c1d118bc | 75a28524-6a48-11e4-bd7d-7831c1d118bc | user-admin | p            | set property "title" on "features" node |
      | p2 | Node.Published | 9c881754-6a51-11e4-9026-7831c1d118bc | 9c881754-6a51-11e4-9026-7831c1d118bc | live       |              | "publish" event of the "about-us" node  |
      | n2 | Node.Added     | 9c881754-6a51-11e4-9026-7831c1d118bc | 9c881754-6a51-11e4-9026-7831c1d118bc | user-admin | p2           | "about-us" node                         |
      |    | Node.Added     |                                      | 9c881754-6a51-11e4-9026-7831c1d118bc | user-admin | n2           | auto-created "about-us/main"            |
      |    | Node.Updated   | 9c881754-6a51-11e4-9026-7831c1d118bc | 9c881754-6a51-11e4-9026-7831c1d118bc | user-admin | p2           | set property "title" on "about-us" node |
