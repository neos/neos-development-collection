Feature: Privilege to restrict removing of nodes

  Background:
    Given I have the following policies:
      """
      privilegeTargets:

        'Neos\ContentRepository\Security\Authorization\Privilege\Node\RemoveNodePrivilege':

          'Neos.ContentRepository:RemoveServiceNode':
            matcher: 'isDescendantNodeOf("/sites/content-repository/service/")'

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
              privilegeTarget: 'Neos.ContentRepository:RemoveServiceNode'
              permission: GRANT

      """
    And I have the following nodes:
      | Identifier                           | Path                        | Node Type                      | Properties           | Workspace |
      | ecf40ad1-3119-0a43-d02e-55f8b5aa3c70 | /sites                      | unstructured                   |                      | live      |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/content-repository              | Neos.ContentRepository.Testing:Document | {"title": "Home"}    | live      |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/content-repository/company      | Neos.ContentRepository.Testing:Document | {"title": "Company"} | live      |
      | 52540602-b417-11e3-9358-14109fd7a2dd | /sites/content-repository/service      | Neos.ContentRepository.Testing:Document | {"title": "Service"} | live      |

  @Isolated @fixtures
  Scenario: Anonymous users are not granted to remove service node
    Given I am not authenticated
    And I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace  |
      | user-admin |
    Then I should not be granted to remove the node
    And I should get false when asking the node authorization service if removal of the node is granted

  @Isolated @fixtures
  Scenario: Anonymous users are granted to remove company node
    Given I am not authenticated
    And I get a node by path "/sites/content-repository/company" with the following context:
      | Workspace  |
      | user-admin |
    Then I should be granted to remove the node
    And I should get true when asking the node authorization service if removal of the node is granted

  @Isolated @fixtures
  Scenario: Administrators are granted to remove service node
    Given I am authenticated with role "Neos.ContentRepository:Administrator"
    And I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace  |
      | user-admin |
    Then I should be granted to remove the node
    And I should get true when asking the node authorization service if removal of the node is granted
