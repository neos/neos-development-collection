Feature: Localization in workspaces
  In order to have a publish workflow for translated content
  As an API user of the content repository
  I need support for workspaces and node localization

  Background:
    Given I have the following nodes:
      | Path           | Node Type                  | Properties        | Language |
      | /sites         | unstructured               |                   | mul_ZZ   |
      | /sites/typo3cr | TYPO3.TYPO3CR.Testing:Page | {"title": "Home"} | mul_ZZ   |
    And I am authenticated with role "TYPO3.Neos:Editor"

  @fixtures
  Scenario: Translate existing node in user workspace, get by path
    Given I have the following nodes:
      | Identifier                           | Path                   | Node Type                  | Properties              | Workspace | Language |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/typo3cr/subpage | TYPO3.TYPO3CR.Testing:Page | {"title": "Subpage"}    | live      | mul_ZZ   |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/typo3cr/subpage | TYPO3.TYPO3CR.Testing:Page | {"title": "Unterseite"} | user-demo | de_DE    |
    When I get a node by path "/sites/typo3cr/subpage" with the following context:
      | Language      | Workspace |
      | de_DE, mul_ZZ | user-demo |
    Then I should have one node
    And the node property "title" should be "Unterseite"
    When I get a node by path "/sites/typo3cr/subpage" with the following context:
      | Language      | Workspace |
      | de_DE, mul_ZZ | live      |
    Then I should have one node
    And the node property "title" should be "Subpage"
    When I get a node by path "/sites/typo3cr/subpage" with the following context:
      | Language      | Workspace |
      | en_US, mul_ZZ | user-demo |
    Then I should have one node
    And the node property "title" should be "Subpage"

  @fixtures
  Scenario: Translate existing node in user workspace, get child nodes
    Given I have the following nodes:
      | Identifier                           | Path                   | Node Type                  | Properties              | Workspace | Language |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/typo3cr/subpage | TYPO3.TYPO3CR.Testing:Page | {"title": "Subpage"}    | live      | mul_ZZ   |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/typo3cr/subpage | TYPO3.TYPO3CR.Testing:Page | {"title": "Unterseite"} | user-demo | de_DE    |
    When I get the child nodes of "/sites/typo3cr" with filter "TYPO3.TYPO3CR.Testing:Document" and the following context:
      | Language      | Workspace |
      | en_US, mul_ZZ | user-demo |
    Then I should have one node
    And the node property "title" should be "Subpage"
    When I get the child nodes of "/sites/typo3cr" with filter "TYPO3.TYPO3CR.Testing:Document" and the following context:
      | Language      | Workspace |
      | de_DE, mul_ZZ | user-demo |
    Then I should have one node
    And the node property "title" should be "Unterseite"

  @fixtures
  Scenario: Create localized node in user workspace
    Given I have the following nodes:
      | Path                   | Node Type                  | Properties              | Workspace | Language |
      | /sites/typo3cr/subpage | TYPO3.TYPO3CR.Testing:Page | {"title": "Unterseite"} | user-demo | de_DE    |
    When I get a node by path "/sites/typo3cr/subpage" with the following context:
      | Language      | Workspace |
      | de_DE, mul_ZZ | user-demo |
    Then I should have one node
    And the node property "title" should be "Unterseite"
    When I get a node by path "/sites/typo3cr/subpage" with the following context:
      | Language      | Workspace |
      | de_DE, mul_ZZ | live      |
    Then I should have 0 nodes

  @fixtures
  Scenario: Language fallback is evaluated before considering Workspace hierarchy
    Given I have the following nodes:
      | Identifier                           | Path                   | Node Type                  | Properties                  | Workspace | Language |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/typo3cr/subpage | TYPO3.TYPO3CR.Testing:Page | {"title": "Subpage"}        | user-demo | en_ZZ    |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/typo3cr/subpage | TYPO3.TYPO3CR.Testing:Page | {"title": "Subpage for US"} | live      | en_US    |

    When I get a node by path "/sites/typo3cr/subpage" with the following context:
      | Language             | Workspace |
      | en_US, en_ZZ, mul_ZZ | user-demo |
    Then I should have one node
    And the node property "title" should be "Subpage for US"

    When I get a node by path "/sites/typo3cr/subpage" with the following context:
      | Language      | Workspace |
      | en_ZZ, mul_ZZ | user-demo |
    Then I should have one node
    And the node property "title" should be "Subpage"

    When I get a node by path "/sites/typo3cr/subpage" with the following context:
      | Language             | Workspace |
      | en_US, en_ZZ, mul_ZZ | live      |
    Then I should have one node
    And the node property "title" should be "Subpage for US"

    When I get the child nodes of "/sites/typo3cr" with filter "TYPO3.TYPO3CR.Testing:Document" and the following context:
      | Language             | Workspace |
      | en_US, en_ZZ, mul_ZZ | user-demo |
    Then I should have one node
    And the node property "title" should be "Subpage for US"

    When I get the child nodes of "/sites/typo3cr" with filter "TYPO3.TYPO3CR.Testing:Document" and the following context:
      | Language      | Workspace |
      | en_ZZ, mul_ZZ | user-demo |
    Then I should have one node
    And the node property "title" should be "Subpage"

    When I get the child nodes of "/sites/typo3cr" with filter "TYPO3.TYPO3CR.Testing:Document" and the following context:
      | Language             | Workspace |
      | en_US, en_ZZ, mul_ZZ | live      |
    Then I should have one node
    And the node property "title" should be "Subpage for US"

  @fixtures
  Scenario: Language fallback is also evaluated for content elements before considering Workspace hierarchy
    Given I have the following nodes:
      | Identifier                           | Path                           | Node Type                      | Properties                    | Workspace | Language |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/typo3cr/subpage         | TYPO3.TYPO3CR.Testing:Page     | {"title": "Subpage"}          | live      | en       |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/typo3cr/subpage         | TYPO3.TYPO3CR.Testing:Page     | {"title": "Unterseite"}       | live      | de       |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/typo3cr/subpage/main/c1 | TYPO3.TYPO3CR.Testing:Headline | {"title": "English modified"} | user-demo | en       |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/typo3cr/subpage/main/c1 | TYPO3.TYPO3CR.Testing:Headline | {"title": "Deutsch"}          | live      | de       |
    When I get a node by path "/sites/typo3cr/subpage/main/c1" with the following context:
      | Language | Workspace |
      | de, en   | user-demo |
    Then I should have one node
    And the node property "title" should be "Deutsch"
    When I get a node by path "/sites/typo3cr/subpage/main/c1" with the following context:
      | Language | Workspace |
      | en       | user-demo |
    Then I should have one node
    And the node property "title" should be "English modified"
    When I get the child nodes of "/sites/typo3cr/subpage/main" with filter "TYPO3.TYPO3CR.Testing:Headline" and the following context:
      | Language | Workspace |
      | de, en   | user-demo |
    Then I should have one node
    And the node property "title" should be "Deutsch"

  @fixtures
  Scenario: On publishing all changes, nodes are published to their correct language
    Given I have the following nodes:
      | Identifier                           | Path                           | Node Type                      | Properties                    | Workspace | Language |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/typo3cr/subpage         | TYPO3.TYPO3CR.Testing:Page     | {"title": "Subpage"}          | live      | en       |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/typo3cr/subpage         | TYPO3.TYPO3CR.Testing:Page     | {"title": "Unterseite"}       | live      | de       |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/typo3cr/subpage/main/c1 | TYPO3.TYPO3CR.Testing:Headline | {"title": "English modified"} | user-demo | en       |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/typo3cr/subpage/main/c1 | TYPO3.TYPO3CR.Testing:Headline | {"title": "Deutsch"}          | live      | de       |
    When I get a node by path "/sites/typo3cr/subpage/main/c1" with the following context:
      | Language | Workspace |
      | de, en   | live      |
    Then I should have one node
    And the node property "title" should be "Deutsch"
    When I get the child nodes of "/sites/typo3cr/subpage/main" with filter "TYPO3.TYPO3CR.Testing:Headline" and the following context:
      | Language | Workspace |
      | de, en   | live      |
    Then I should have one node
    And the node property "title" should be "Deutsch"

    When I use the publishing service to publish nodes in the workspace "user-demo" with the following context:
      | Language | Workspace |
      | de, en   | user-demo |

    When I get the child nodes of "/sites/typo3cr/subpage/main" with filter "TYPO3.TYPO3CR.Testing:Headline" and the following context:
      | Language | Workspace |
      | en       | live      |
    Then I should have one node
    And the node property "title" should be "English modified"
    When I get the child nodes of "/sites/typo3cr/subpage/main" with filter "TYPO3.TYPO3CR.Testing:Headline" and the following context:
      | Language | Workspace |
      | de, en   | live      |
    Then I should have one node
    And the node property "title" should be "Deutsch"

  @fixtures
  Scenario: Translate existing node in user workspace, publish to live
    Given I have the following nodes:
      | Identifier                           | Path                   | Node Type                  | Properties              | Workspace | Language |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/typo3cr/subpage | TYPO3.TYPO3CR.Testing:Page | {"title": "Subpage"}    | live      | mul_ZZ   |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/typo3cr/subpage | TYPO3.TYPO3CR.Testing:Page | {"title": "Unterseite"} | user-demo | de_DE    |
    And I get a node by path "/sites/typo3cr/subpage" with the following context:
      | Language      | Workspace |
      | de_DE, mul_ZZ | user-demo |
    When I publish the node

    When I get a node by path "/sites/typo3cr/subpage" with the following context:
      | Language      | Workspace |
      | de_DE, mul_ZZ | live      |
    Then I should have one node
    And the node property "title" should be "Unterseite"

    When I get a node by path "/sites/typo3cr/subpage" with the following context:
      | Language      | Workspace |
      | en_US, mul_ZZ | live      |
    Then I should have one node
    And the node property "title" should be "Subpage"

  @fixtures
  Scenario: Update existing live node variant in user workspace, publish to live
    Given I have the following nodes:
      | Identifier                           | Path                   | Node Type                  | Properties               | Workspace | Language |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/typo3cr/subpage | TYPO3.TYPO3CR.Testing:Page | {"title": "Subpage"}     | live      | mul_ZZ   |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/typo3cr/subpage | TYPO3.TYPO3CR.Testing:Page | {"title": "Unternehmen"} | live      | de_DE    |
    And I get a node by path "/sites/typo3cr/subpage" with the following context:
      | Language      | Workspace |
      | de_DE, mul_ZZ | user-demo |
    And I set the node property "title" to "Firma"
    # FIXME We have to get the node again to have a clean context, after persistAll there will be duplicate Workspace instances otherwise
    And I get a node by path "/sites/typo3cr/subpage" with the following context:
      | Language      | Workspace |
      | de_DE, mul_ZZ | user-demo |
    When I publish the node

    When I get a node by path "/sites/typo3cr/subpage" with the following context:
      | Language      | Workspace |
      | de_DE, mul_ZZ | live      |
    Then I should have one node
    And the node property "title" should be "Firma"

    When I get a node by path "/sites/typo3cr/subpage" with the following context:
      | Language      | Workspace |
      | en_US, mul_ZZ | live      |
    Then I should have one node
    And the node property "title" should be "Subpage"

  @fixtures
  Scenario: Translate existing node with multiple live variants in user workspace, publish to live
    Given I have the following nodes:
      | Identifier                           | Path                   | Node Type                  | Properties               | Workspace | Language |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/typo3cr/subpage | TYPO3.TYPO3CR.Testing:Page | {"title": "Subpage de"}  | live      | de_ZZ    |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/typo3cr/subpage | TYPO3.TYPO3CR.Testing:Page | {"title": "Subpage mul"} | live      | mul_ZZ   |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/typo3cr/subpage | TYPO3.TYPO3CR.Testing:Page | {"title": "Unterseite"}  | user-demo | de_DE    |
    And I get a node by path "/sites/typo3cr/subpage" with the following context:
      | Language             | Workspace |
      | de_DE, de_ZZ, mul_ZZ | user-demo |
    When I publish the node

    When I get a node by path "/sites/typo3cr/subpage" with the following context:
      | Language             | Workspace |
      | de_DE, de_ZZ, mul_ZZ | live      |
    Then I should have one node
    And the node property "title" should be "Unterseite"

    When I get a node by path "/sites/typo3cr/subpage" with the following context:
      | Language      | Workspace |
      | de_ZZ, mul_ZZ | live      |
    Then I should have one node
    And the node property "title" should be "Subpage de"

    When I get a node by path "/sites/typo3cr/subpage" with the following context:
      | Language      | Workspace |
      | en_ZZ, mul_ZZ | live      |
    Then I should have one node
    And the node property "title" should be "Subpage mul"

  @fixtures
  Scenario: Update existing live node variant in user workspace, publish to live
    Given I have the following nodes:
      | Identifier                           | Path                            | Node Type                  | Properties              | Workspace | Language |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/typo3cr/mainpage         | TYPO3.TYPO3CR.Testing:Page | {"title": "Mainpage"}   | live      | mul_ZZ   |
      | 88745891-222b-e9c9-6144-4b3a5d80d482 | /sites/typo3cr/mainpage/subpage | TYPO3.TYPO3CR.Testing:Page | {"title": "Subpage"}    | live      | mul_ZZ   |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/typo3cr/mainpage         | TYPO3.TYPO3CR.Testing:Page | {"title": "mainpage"}   | live      | de_DE    |
      | 88745891-222b-e9c9-6144-4b3a5d80d482 | /sites/typo3cr/mainpage/subpage | TYPO3.TYPO3CR.Testing:Page | {"title": "Unterseite"} | live      | de_DE    |

    And I get a node by path "/sites/typo3cr/mainpage" with the following context:
      | Language      | Workspace |
      | de_DE, mul_ZZ | user-demo |
    And I set the node name to "hauptseite"
    And I publish the workspace "user-demo"

    When I get a node by path "/sites/typo3cr/hauptseite/subpage" with the following context:
      | Language      | Workspace |
      | de_DE, mul_ZZ | user-demo |
    And I set the node property "title" to "bar"
    And I publish the workspace "user-demo"

    When I get a node by path "/sites/typo3cr/hauptseite/subpage" with the following context:
      | Language      | Workspace |
      | de_DE, mul_ZZ | user-demo |
    And I set the node name to "unterseite"
    And I publish the workspace "user-demo"
    Then the node property "title" should be "bar"

    When I get a node by path "/sites/typo3cr/mainpage/subpage" with the following context:
      | Language | Workspace |
      | de_DE    | user-demo |
    Then I should have 0 nodes

  @fixtures
  Scenario: Update existing live node variant in user workspace with other live translation
    Given I have the following nodes:
      | Identifier                           | Path                    | Node Type                  | Properties              | Workspace | Language |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/typo3cr/mainpage | TYPO3.TYPO3CR.Testing:Page | {"title": "Mainpage"}   | live      | en_US    |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/typo3cr/mainpage | TYPO3.TYPO3CR.Testing:Page | {"title": "Hauptseite"} | live      | de_DE    |

    And I get a node by path "/sites/typo3cr/mainpage" with the following context:
      | Language      | Workspace |
      | en_US, mul_ZZ | user-demo |
    And I set the node property "title" to "Mainpage update"

    When I get a node by path "/sites/typo3cr/mainpage" with the following context:
      | Language             | Workspace |
      | de_DE, en_US, mul_ZZ | user-demo |
    Then the node property "title" should be "Hauptseite"
