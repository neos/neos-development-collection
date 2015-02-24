Feature: Privilege to restrict editing of nodes

  Background:
    Given I have the following policies:
      """
      privilegeTargets:

        'TYPO3\TYPO3CR\Security\Authorization\Privilege\Node\EditNodePrivilege':

          'TYPO3.TYPO3CR:EditServiceNodes':
            matcher: 'isDescendantNodeOf("/sites/typo3cr/service") && nodeIsOfType("TYPO3.TYPO3CR.Testing:Document")'

          'TYPO3.TYPO3CR:EditEventNodes':
            matcher: 'isDescendantNodeOf("/sites/typo3cr/events")'

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
              privilegeTarget: 'TYPO3.TYPO3CR:EditServiceNodes'
              permission: GRANT
            -
              privilegeTarget: 'TYPO3.TYPO3CR:EditEventNodes'
              permission: GRANT
      """

    And I have the following nodes:
      | Identifier                           | Path                        | Node Type                      | Properties           | Workspace |
      | ecf40ad1-3119-0a43-d02e-55f8b5aa3c70 | /sites                      | unstructured                   |                      | live      |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/typo3cr              | TYPO3.TYPO3CR.Testing:Document | {"title": "Home"}    | live      |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/typo3cr/company      | TYPO3.TYPO3CR.Testing:Document | {"title": "Company"} | live      |
      | 52540602-b417-11e3-9358-14109fd7a2dd | /sites/typo3cr/service      | TYPO3.TYPO3CR.Testing:Document | {"title": "Service"} | live      |
      | 11d3aded-fb1a-70e7-1412-0b465b11fcd8 | /sites/typo3cr/events       | TYPO3.TYPO3CR.Testing:Document | {"title": "Events", "description": "Some cool event"}  | live      |

  @Isolated @fixtures
  Scenario: Anonymous users are granted to set properties on company node
    Given I am not authenticated
    And I get a node by path "/sites/typo3cr/company" with the following context:
      | Workspace  |
      | user-admin |
    Then I should be granted to set the "title" property to "MyNewCompany"
    And I should get TRUE when asking the node authorization service if editing the "title" property is granted

  @Isolated @fixtures
  Scenario: Anonymous users are not granted to set properties on service node
    Given I am not authenticated
    And I get a node by path "/sites/typo3cr/service" with the following context:
      | Workspace  |
      | user-admin |
    Then I should not be granted to set the "title" property to "ServiceDessertGermany"
    And I should get FALSE when asking the node authorization service if editing the "title" property is granted

  @Isolated @fixtures
  Scenario: Anonymous users are not granted to set attributes on the service node
    Given I am not authenticated
    And I get a node by path "/sites/typo3cr/service" with the following context:
      | Workspace  |
      | user-admin |
    Then I should not be granted to set any of the node's attributes
    And I should get FALSE when asking the node authorization service if editing this node is granted

  @Isolated @fixtures
  Scenario: Anonymous users are not granted to edit the events node's description property
    Given I am not authenticated
    And I get a node by path "/sites/typo3cr/events" with the following context:
      | Workspace  |
      | user-admin |
    Then I should not be granted to set the "description" property to "Even cooler event!"
    And I should get FALSE when asking the node authorization service if editing the "description" property is granted

  @Isolated @fixtures
  Scenario: Administrators are granted to set properties on service node
    Given I am authenticated with role "TYPO3.TYPO3CR:Administrator"
    And I get a node by path "/sites/typo3cr/service" with the following context:
      | Workspace  |
      | user-admin |
    Then I should be granted to set the "title" property to "ServiceDessertGermany"
    And I should get TRUE when asking the node authorization service if editing the "title" property is granted

  @Isolated @fixtures
  Scenario: Administrators are granted to set attributes on the service node
    Given I am authenticated with role "TYPO3.TYPO3CR:Administrator"
    And I get a node by path "/sites/typo3cr/service" with the following context:
      | Workspace  |
      | user-admin |
    Then I should be granted to set any of the node's attributes
    And I should get TRUE when asking the node authorization service if editing this node is granted

  @Isolated @fixtures
  Scenario: Administrators are granted to edit the events node's description property
    Given I am authenticated with role "TYPO3.TYPO3CR:Administrator"
    And I get a node by path "/sites/typo3cr/events" with the following context:
      | Workspace  |
      | user-admin |
    Then I should be granted to set the "description" property to "Even cooler event!"
    And I should get TRUE when asking the node authorization service if editing the "description" property is granted