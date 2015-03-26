Feature: Privilege to restrict reading of nodes

  Background:
    Given I have the following policies:
    """
    privilegeTargets:

      'TYPO3\TYPO3CR\Security\Authorization\Privilege\Node\ReadNodePrivilege':

        'TYPO3.TYPO3CR:Service':
          matcher: 'isDescendantNodeOf("/sites/typo3cr/service/") && nodeIsOfType("TYPO3.TYPO3CR.Testing:Document")'

        'TYPO3.TYPO3CR:Company':
          matcher: 'isDescendantNodeOf("68ca0dcd-2afb-ef0e-1106-a5301e65b8a0")'

    roles:
      'TYPO3.Flow:Everybody':
        privileges: []

      'TYPO3.Flow:Anonymous':
        privileges: []

      'TYPO3.Flow:AuthenticatedUser':
        privileges: []

      'TYPO3.TYPO3CR:Administrator':
        privileges:
          -
            privilegeTarget: 'TYPO3.TYPO3CR:Service'
            permission: GRANT
          -
            privilegeTarget: 'TYPO3.TYPO3CR:Company'
            permission: GRANT

    """
    And I have the following nodes:
      | Identifier                           | Path                   | Node Type                      | Properties           | Workspace |
      | ecf40ad1-3119-0a43-d02e-55f8b5aa3c70 | /sites                 | unstructured                   |                      | live      |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/typo3cr         | TYPO3.TYPO3CR.Testing:Document | {"title": "Home"}    | live      |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/typo3cr/company | TYPO3.TYPO3CR.Testing:Document | {"title": "Company"} | live      |
      | 52540602-b417-11e3-9358-14109fd7a2dd | /sites/typo3cr/service | TYPO3.TYPO3CR.Testing:Document | {"title": "Service"} | live      |

  @Isolated @fixtures
  Scenario: Restrict node visibility by node path
    Given I am not authenticated
    And I get a node by path "/sites/typo3cr/service" with the following context:
      | Workspace  |
      | user-admin |
    Then I should have 0 nodes

  @Isolated @fixtures
  Scenario: Do not restrict node visibility by node path for administrator role
    Given I am authenticated with role "TYPO3.TYPO3CR:Administrator"
    And I get a node by path "/sites/typo3cr/service" with the following context:
      | Workspace  |
      | user-admin |
    Then I should have 1 nodes

  @Isolated @fixtures
  Scenario: Restrict node visibility by node identifier
    Given I am not authenticated
    And I get a node by path "/sites/typo3cr/company" with the following context:
      | Workspace  |
      | user-admin |
    Then I should have 0 nodes

  @Isolated @fixtures
  Scenario: Do not restrict node visibility by node identifier for administrator role
    Given I am authenticated with role "TYPO3.TYPO3CR:Administrator"
    And I get a node by path "/sites/typo3cr/company" with the following context:
      | Workspace  |
      | user-admin |
    Then I should have 1 nodes