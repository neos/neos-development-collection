Feature: Move node with dimension support
  In order to move nodes
  As an API user of the content repository
  I need support to move nodes and child nodes considering dimensions; moving all nodes across dimensions consistently.

  Background:
    Given I have the following nodes:
      | Path                   | Node Type                      | Properties                    | Workspace | Language |
      | /sites                 | unstructured                   |                               | live      | mul_ZZ   |
      | /sites/typo3cr         | TYPO3.TYPO3CR.Testing:Document | {"title": "Home"}             | live      | en       |
      | /sites/typo3cr/company | TYPO3.TYPO3CR.Testing:Document | {"title": "Company"}          | live      | en       |
      | /sites/typo3cr/service | TYPO3.TYPO3CR.Testing:Document | {"title": "Service"}          | live      | en       |
      | /sites/typo3cr         | TYPO3.TYPO3CR.Testing:Document | {"title": "Startseite"}       | live      | de       |
      | /sites/typo3cr/company | TYPO3.TYPO3CR.Testing:Document | {"title": "Die Firma"}        | live      | de       |
      | /sites/typo3cr/service | TYPO3.TYPO3CR.Testing:Document | {"title": "Dienstleistungen"} | live      | de       |

  @fixtures
  Scenario: Move a node (into) in user workspace should move across all dimensions; making sure the live workspace is unaffected
    When I get a node by path "/sites/typo3cr/service" with the following context:
      | Workspace  | Language |
      | user-admin | en       |
    And I move the node into the node with path "/sites/typo3cr/company"
    And I get a node by path "/sites/typo3cr/company/service" with the following context:
      | Workspace  | Language |
      | user-admin | en       |
    Then I should have one node
    And I get a node by path "/sites/typo3cr/service" with the following context:
      | Workspace  | Language |
      | user-admin | en       |
    Then I should have 0 nodes

    # different dimension
    And I get a node by path "/sites/typo3cr/company/service" with the following context:
      | Workspace  | Language |
      | user-admin | de       |
    Then I should have one node
    And I get a node by path "/sites/typo3cr/service" with the following context:
      | Workspace  | Language |
      | user-admin | de       |
    Then I should have 0 nodes

    # make sure the live workspace is unaffected
    And I get a node by path "/sites/typo3cr/service" with the following context:
      | Workspace  | Language |
      | live       | en       |
    Then I should have one node
    And I get a node by path "/sites/typo3cr/service" with the following context:
      | Workspace  | Language |
      | live       | de       |
    Then I should have one node
    And I get a node by path "/sites/typo3cr/company/service" with the following context:
      | Workspace  | Language |
      | live       | en       |
    Then I should have 0 nodes
    And I get a node by path "/sites/typo3cr/company/service" with the following context:
      | Workspace  | Language |
      | live       | de       |
    Then I should have 0 nodes


  @fixtures
  Scenario: Move a node (into) in user workspace should move across all dimensions after being published to live workspace
    When I get a node by path "/sites/typo3cr/service" with the following context:
      | Workspace  | Language |
      | user-admin | en       |
    And I move the node into the node with path "/sites/typo3cr/company"
    And I publish the workspace "user-admin"

    And I get a node by path "/sites/typo3cr/company/service" with the following context:
      | Workspace | Language |
      | admin     | en       |
    Then I should have one node
    And I get a node by path "/sites/typo3cr/company/service" with the following context:
      | Workspace | Language |
      | admin     | de       |
    Then I should have one node