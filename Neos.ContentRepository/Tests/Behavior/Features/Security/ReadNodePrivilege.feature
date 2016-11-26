Feature: Privilege to restrict reading of nodes

  Background:
    Given I have the following policies:
    """
    privilegeTargets:

      'Neos\ContentRepository\Security\Authorization\Privilege\Node\ReadNodePrivilege':

        'Neos.ContentRepository:Service':
          matcher: 'isDescendantNodeOf("/sites/content-repository/service/") && nodeIsOfType("Neos.ContentRepository.Testing:Document")'

        'Neos.ContentRepository:Company':
          matcher: 'isDescendantNodeOf("68ca0dcd-2afb-ef0e-1106-a5301e65b8a0")'

        'Dummy':
          # fee74676-c42f-89da-208e-1741a66520000 is a non-existing node, this privilege should be ignored
          matcher: 'isDescendantNodeOf("fee74676-c42f-89da-208e-1741a66520000")'

    roles:
      'Neos.Flow:Everybody':
        privileges: []

      'Neos.Flow:Anonymous':
        privileges: []

      'Neos.Flow:AuthenticatedUser':
        privileges: []

      'Neos.ContentRepository:Administrator':
        privileges:
          -
            privilegeTarget: 'Neos.ContentRepository:Service'
            permission: GRANT
          -
            privilegeTarget: 'Neos.ContentRepository:Company'
            permission: GRANT

    """
    And I have the following nodes:
      | Identifier                           | Path                   | Node Type                      | Properties           | Workspace |
      | ecf40ad1-3119-0a43-d02e-55f8b5aa3c70 | /sites                 | unstructured                   |                      | live      |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/content-repository         | Neos.ContentRepository.Testing:Document | {"title": "Home"}    | live      |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/content-repository/company | Neos.ContentRepository.Testing:Document | {"title": "Company"} | live      |
      | 52540602-b417-11e3-9358-14109fd7a2dd | /sites/content-repository/service | Neos.ContentRepository.Testing:Document | {"title": "Service"} | live      |

  @Isolated @fixtures
  Scenario: Restrict node visibility by node path
    Given I am not authenticated
    And I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace  |
      | user-admin |
    Then I should have 0 nodes

  @Isolated @fixtures
  Scenario: Do not restrict node visibility by node path for administrator role
    Given I am authenticated with role "Neos.ContentRepository:Administrator"
    And I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace  |
      | user-admin |
    Then I should have 1 nodes

  @Isolated @fixtures
  Scenario: Restrict node visibility by node identifier
    Given I am not authenticated
    And I get a node by path "/sites/content-repository/company" with the following context:
      | Workspace  |
      | user-admin |
    Then I should have 0 nodes

  @Isolated @fixtures
  Scenario: Do not restrict node visibility by node identifier for administrator role
    Given I am authenticated with role "Neos.ContentRepository:Administrator"
    And I get a node by path "/sites/content-repository/company" with the following context:
      | Workspace  |
      | user-admin |
    Then I should have 1 nodes
