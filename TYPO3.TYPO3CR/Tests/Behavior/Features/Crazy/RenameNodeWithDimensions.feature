Feature: Rename node with dimension support
  In order to rename nodes
  As an API user of the content repository
  I need support to rename nodes and child nodes considering workspaces and consistent renaming across dimensions

  Background:
    Given I have the following nodes:
      | Identifier                           | Path                            | Node Type                  | Properties                   | Workspace | Language |
      | ecf40ad1-3119-0a43-d02e-55f8b5aa3c70 | /sites                          | unstructured               |                              | live      | mul_ZZ   |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/typo3cr                  | TYPO3.TYPO3CR.Testing:Page | {"title": "Home"}            | live      | en       |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/typo3cr                  | TYPO3.TYPO3CR.Testing:Page | {"title": "Startseite"}      | live      | de       |
      | 52540602-b417-11e3-9358-14109fd7a2dd | /sites/typo3cr/service          | TYPO3.TYPO3CR.Testing:Page | {"title": "Service"}         | live      | en       |
      | c41d35bf-27e5-5645-a290-6a8b35c5935a | /sites/typo3cr/company          | TYPO3.TYPO3CR.Testing:Page | {"title": "Company"}         | live      | en       |
      | c41d35bf-27e5-5645-a290-6a8b35c5935a | /sites/typo3cr/company          | TYPO3.TYPO3CR.Testing:Page | {"title": "Unternehmen"}     | live      | de       |
      | 4f19cb3c-6148-11e4-a977-14109fd7a2dd | /sites/typo3cr/contact          | TYPO3.TYPO3CR.Testing:Page | {"title": "Kontakt"}         | live      | de       |

  @fixtures
  Scenario: Rename a node to a name conflicting with an existing node
    When I get a node by path "/sites/typo3cr/service" with the following context:
      | Workspace  | Language |
      | user-admin | en       |
    Then I should not be able to rename the node to "company"

  @fixtures
  Scenario: Rename a node to a name conflicting with an existing node in an invisible dimension
    When I get a node by path "/sites/typo3cr/service" with the following context:
      | Workspace  | Language |
      | user-admin | en       |
    Then I should not be able to rename the node to "contact"
