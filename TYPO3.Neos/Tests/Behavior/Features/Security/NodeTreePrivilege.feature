Feature: Privilege to restrict nodes shown in the node tree

  Background:
    Given I have the following policies:
      """
      privilegeTargets:

        'TYPO3\Neos\Security\Authorization\Privilege\NodeTreePrivilege':
          'TYPO3.TYPO3CR:CompanySubtree':
            matcher: 'isDescendantNodeOf("/sites/typo3cr/company")'
          'TYPO3.TYPO3CR:ServiceSubtree':
            matcher: 'isDescendantNodeOf("/sites/typo3cr/service")'

          'TYPO3.TYPO3CR:NeosSite':
            matcher: 'isDescendantNodeOf("/sites/neos")'
          'TYPO3.TYPO3CR:NeosTeams':
            matcher: 'isAncestorOrDescendantNodeOf("/sites/neos/community/teams")'

        'TYPO3\TYPO3CR\Security\Authorization\Privilege\Node\EditNodePrivilege':
          'TYPO3.TYPO3CR:EditNeosTeamsPath':
            matcher: 'isAncestorNodeOf("/sites/neos/community/teams")'

      roles:
        'TYPO3.Flow:Everybody':
          privileges: []

        'TYPO3.Flow:Anonymous':
          privileges: []

        'TYPO3.Flow:AuthenticatedUser':
          privileges: []

        'TYPO3.Neos:Editor':
          privileges:
            -
              privilegeTarget: 'TYPO3.TYPO3CR:CompanySubtree'
              permission: GRANT

        'TYPO3.Neos:Administrator':
          parentRoles: ['TYPO3.Neos:Editor']
          privileges:
            -
              privilegeTarget: 'TYPO3.TYPO3CR:ServiceSubtree'
              permission: GRANT
            -
              privilegeTarget: 'TYPO3.TYPO3CR:NeosTeams'
              permission: GRANT
            -
              privilegeTarget: 'TYPO3.TYPO3CR:EditNeosTeamsPath'
              permission: DENY

      """

    And I have the following nodes:
      | Identifier                           | Path                              | Node Type                      | Properties              | Workspace |
      | ecf40ad1-3119-0a43-d02e-55f8b5aa3c70 | /sites                            | unstructured                   |                         | live      |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/typo3cr                    | TYPO3.TYPO3CR.Testing:Document | {"title": "Home"}       | live      |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/typo3cr/company            | TYPO3.TYPO3CR.Testing:Document | {"title": "Company"}    | live      |
      | 52540602-b417-11e3-9358-14109fd7a2dd | /sites/typo3cr/service            | TYPO3.TYPO3CR.Testing:Document | {"title": "Service"}    | live      |
      | 3223481d-e11c-4db7-95de-b371411a2431 | /sites/typo3cr/service/newsletter | TYPO3.TYPO3CR.Testing:Document | {"title": "Newsletter"} | live      |
      | 544e14a3-b21d-429a-9fdd-cbeccc8d2b0f | /sites/typo3cr/about-us           | TYPO3.TYPO3CR.Testing:Document | {"title": "About us"}   | live      |
      | 56217c92-07e9-4554-ac35-03f86d278870 | /sites/neos                       | TYPO3.TYPO3CR.Testing:Document | {"title": "Neos"}       | live      |
      | 4be072fe-0738-4892-8a27-342a6ac96075 | /sites/neos/community             | TYPO3.TYPO3CR.Testing:Document | {"title": "Community"}  | live      |
      | c56d66e7-9c55-4eef-a2b1-c263b3261996 | /sites/neos/community/teams       | TYPO3.TYPO3CR.Testing:Document | {"title": "Teams"}      | live      |
      | 07902b2e-61d9-4ce4-9b90-1cf338830d2f | /sites/neos/community/teams/member| TYPO3.TYPO3CR.Testing:Document | {"title": "Johannes"}   | live      |

  @Isolated @fixtures
  Scenario: Editors are granted to set properties on company node
    Given I am authenticated with role "TYPO3.Neos:Editor"
    And I get a node by path "/sites/typo3cr/company" with the following context:
      | Workspace  |
      | user-admin |
    Then I should be granted to set the "title" property to "The company"
    And I should get TRUE when asking the node authorization service if editing the "title" property is granted

  @Isolated @fixtures
  Scenario: Editors are not granted to set properties on service node
    Given I am authenticated with role "TYPO3.Neos:Editor"
    And I get a node by path "/sites/typo3cr/service" with the following context:
      | Workspace  |
      | user-admin |
    Then I should not be granted to set the "title" property to "Our services"
    And I should get FALSE when asking the node authorization service if editing the "title" property is granted

  @Isolated @fixtures
  Scenario: Editors are not granted to set properties on service sub node
    Given I am authenticated with role "TYPO3.Neos:Editor"
    And I get a node by path "/sites/typo3cr/service/newsletter" with the following context:
      | Workspace  |
      | user-admin |
    Then I should not be granted to set the "title" property to "Our newsletter"
    And I should get FALSE when asking the node authorization service if editing the "title" property is granted

  @Isolated @fixtures
  Scenario: Administrators are granted to set properties on company node
    Given I am authenticated with role "TYPO3.Neos:Administrator"
    And I get a node by path "/sites/typo3cr/company" with the following context:
      | Workspace  |
      | user-admin |
    Then I should be granted to set the "title" property to "The company"
    And I should get TRUE when asking the node authorization service if editing the "title" property is granted

  @Isolated @fixtures
  Scenario: Administrators are granted to set properties on service node
    Given I am authenticated with role "TYPO3.Neos:Administrator"
    And I get a node by path "/sites/typo3cr/service" with the following context:
      | Workspace  |
      | user-admin |
    Then I should be granted to set the "title" property to "Our services"
    And I should get TRUE when asking the node authorization service if editing the "title" property is granted

  @Isolated @fixtures
  Scenario: Administrators are granted to set properties on service sub node
    Given I am authenticated with role "TYPO3.Neos:Administrator"
    And I get a node by path "/sites/typo3cr/service/newsletter" with the following context:
      | Workspace  |
      | user-admin |
    Then I should be granted to set the "title" property to "Our newsletter"
    And I should get TRUE when asking the node authorization service if editing the "title" property is granted

  @Isolated @fixtures
  Scenario: Editors are not granted to set properties on a neos sub node
    Given I am authenticated with role "TYPO3.Neos:Editor"
    And I get a node by path "/sites/neos/community/teams" with the following context:
      | Workspace  |
      | user-admin |
    Then I should not be granted to set the "title" property to "The Teams"
    And I should get FALSE when asking the node authorization service if editing the "title" property is granted

  @Isolated @fixtures
  Scenario: Administrators are granted to set properties on a neos sub node
    Given I am authenticated with role "TYPO3.Neos:Administrator"
    And I get a node by path "/sites/neos/community/teams/member" with the following context:
      | Workspace  |
      | user-admin |
    Then I should be granted to set the "title" property to "Basti"
    And I should get TRUE when asking the node authorization service if editing the "title" property is granted

  @Isolated @fixtures
  Scenario: Administrators are not granted to set properties on a ancestor node of teams
    Given I am authenticated with role "TYPO3.Neos:Administrator"
    And I get a node by path "/sites/neos/community" with the following context:
      | Workspace  |
      | user-admin |
    Then I should not be granted to set the "title" property to "The Community"
    And I should get FALSE when asking the node authorization service if editing the "title" property is granted