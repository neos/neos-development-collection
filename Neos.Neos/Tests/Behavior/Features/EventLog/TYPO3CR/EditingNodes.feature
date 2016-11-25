Feature: Editing Nodes
  As an API user of the history
  I expect that changing a node triggers history updates

  Background:
    Given I have the following nodes:
      | Identifier                           | Path                         | Node Type                      | Properties                      | Workspace |
      | ecf40ad1-3119-0a43-d02e-55f8b5aa3c70 | /sites                       | unstructured                   |                                 | live      |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/typo3cr               | TYPO3.TYPO3CR.Testing:Page     | {"title": "Home"}               | live      |
      | 49f324f2-6a65-11e4-a901-7831c1d118bc | /sites/typo3cr/main/headline | TYPO3.TYPO3CR.Testing:Headline | {"title": "Welcome"}            | live      |
      | be87d1dc-6a65-11e4-884b-7831c1d118bc | /sites/typo3cr/main/text     | TYPO3.TYPO3CR.Testing:Text     | {"text": "... to this website"} | live      |
    And I have an empty history

  @fixtures
  Scenario: Change a Document node property in the live workspace (e.g. like an API)
    Given I am authenticated with role "Neos.Neos:Editor"
    When I get a node by path "/sites/typo3cr" with the following context:
      | Workspace |
      | live      |
    And I set the node property "title" to "Homepage"
    Then I should have the following history entries:
      | Event Type   | Node Identifier                      | Document Node Identifier             | Workspace |
      | Node.Updated | fd5ba6e1-4313-b145-1004-dad2f1173a35 | fd5ba6e1-4313-b145-1004-dad2f1173a35 | live      |

  @fixtures
  Scenario: Change a Document node property inside a workspace
    Given I am authenticated with role "Neos.Neos:Editor"
    When I get a node by path "/sites/typo3cr" with the following context:
      | Workspace  |
      | user-admin |
    And I set the node property "title" to "Homepage"
    Then I should have the following history entries:
      | Event Type   | Node Identifier                      | Document Node Identifier             | Workspace  |
      | Node.Updated | fd5ba6e1-4313-b145-1004-dad2f1173a35 | fd5ba6e1-4313-b145-1004-dad2f1173a35 | user-admin |

  @fixtures
  Scenario: Change a Document node property inside a workspace, and publishing afterwards adds the update event to the publish event.
    Given I am authenticated with role "Neos.Neos:Editor"
    When I get a node by path "/sites/typo3cr" with the following context:
      | Workspace  |
      | user-admin |
    And I set the node property "title" to "Homepage"
    And I publish the workspace "user-admin"
    Then I should have the following history entries:
      | ID | Event Type     | Node Identifier                      | Document Node Identifier             | Workspace  | Parent Event |
      |    | Node.Updated   | fd5ba6e1-4313-b145-1004-dad2f1173a35 | fd5ba6e1-4313-b145-1004-dad2f1173a35 | user-admin | p            |
      | p  | Node.Published | fd5ba6e1-4313-b145-1004-dad2f1173a35 | fd5ba6e1-4313-b145-1004-dad2f1173a35 | live       |              |


  @fixtures
  Scenario: Change a Content node property inside a workspace, and publishing afterwards adds the update event to the publish event.
    Given I am authenticated with role "Neos.Neos:Editor"
    When I get a node by path "/sites/typo3cr/main/headline" with the following context:
      | Workspace  |
      | user-admin |
    And I set the node property "title" to "Homepage"
    And I get a node by path "/sites/typo3cr/main/text" with the following context:
      | Workspace  |
      | user-admin |
    And I set the node property "text" to "AWESOME"
    And I publish the workspace "user-admin"
    Then I should have the following history entries:
      | ID | Event Type     | Node Identifier                      | Document Node Identifier             | Workspace  | Parent Event |
      |    | Node.Updated   | 49f324f2-6a65-11e4-a901-7831c1d118bc | fd5ba6e1-4313-b145-1004-dad2f1173a35 | user-admin | p            |
      |    | Node.Updated   | be87d1dc-6a65-11e4-884b-7831c1d118bc | fd5ba6e1-4313-b145-1004-dad2f1173a35 | user-admin | p            |
      | p  | Node.Published | fd5ba6e1-4313-b145-1004-dad2f1173a35 | fd5ba6e1-4313-b145-1004-dad2f1173a35 | live       |              |
