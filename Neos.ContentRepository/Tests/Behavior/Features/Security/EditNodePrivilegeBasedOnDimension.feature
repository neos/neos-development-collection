Feature: Privilege to restrict editing of nodes for a single dimension only

  Background:

    Given I have the following policies:
      """
      privilegeTargets:

        'Neos\ContentRepository\Security\Authorization\Privilege\Node\EditNodePrivilege':

          'Neos.ContentRepository:EditServiceNodes':
            matcher: 'isDescendantNodeOf("/sites/content-repository/service")'

          # EditEverything is needed, to switch to an "EXPLICIT ALLOW MODE"
          'Neos.ContentRepository:EditEverything':
            matcher: 'true'

          'Neos.ContentRepository:EditGerman':
            matcher: 'isInDimensionPreset("language", "de")'

      roles:
        'Neos.Flow:Everybody':
          privileges: []

        'Neos.Flow:Anonymous':
          privileges: []

        'Neos.Flow:AuthenticatedUser':
          privileges: []

        'Neos.ContentRepository:ServiceManager':
          # can only edit service nodes, but in all languages.
          privileges:
            -
              privilegeTarget: 'Neos.ContentRepository:EditServiceNodes'
              permission: GRANT

        'Neos.ContentRepository:GermanManager':
          # can only edit german nodes
          privileges:
            -
              privilegeTarget: 'Neos.ContentRepository:EditGerman'
              permission: GRANT
      """
    And I have the following content dimensions:
      | Identifier | Default | Presets                          |
      | language   | mul_ZZ  | de=de_ZZ,mul_ZZ; en=en_ZZ,mul_ZZ |

    And I have the following nodes:
      | Identifier                           | Path                              | Node Type                      | Properties                    | Workspace | Language |
      | ecf40ad1-3119-0a43-d02e-55f8b5aa3c70 | /sites                            | unstructured                   |                               | live      | mul_ZZ   |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/content-repository         | Neos.ContentRepository.Testing:Document | {"title": "Home"}             | live      | mul_ZZ   |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/content-repository/company | Neos.ContentRepository.Testing:Document | {"title": "Company"}          | live      | en_ZZ    |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/content-repository/company | Neos.ContentRepository.Testing:Document | {"title": "Firma"}            | live      | de_ZZ    |
      | 52540602-b417-11e3-9358-14109fd7a2dd | /sites/content-repository/service | Neos.ContentRepository.Testing:Document | {"title": "Service"}          | live      | en_ZZ    |
      | 52540602-b417-11e3-9358-14109fd7a2dd | /sites/content-repository/service | Neos.ContentRepository.Testing:Document | {"title": "Dienstleistungen"} | live      | de_ZZ    |

  @Isolated @fixtures
  Scenario: "Everybody"-Authenticated users are not granted to set any property
    Given I am authenticated with role "Neos.Flow:AuthenticatedUser"
    And I get a node by path "/sites/content-repository/company" with the following context:
      | Workspace  | Language      |
      | user-admin | en_ZZ, mul_ZZ |
    Then I should get false when asking the node authorization service if editing this node is granted
    And I should not be granted to set any of the node's attributes

  @Isolated @fixtures
  Scenario: Language-Restricted (German) Managers should be able to edit german nodes
    Given I am authenticated with role "Neos.ContentRepository:GermanManager"
    And I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace  | Language      |
      | user-admin | de_ZZ, mul_ZZ |
    Then I should be granted to set the "title" property to "Dienstleistungen speziell"
    And I should get true when asking the node authorization service if editing the "title" property is granted
    When I get a node by path "/sites/content-repository/company" with the following context:
      | Workspace  | Language      |
      | user-admin | de_ZZ, mul_ZZ |
    Then I should be granted to set the "title" property to "Firma speziell"
    And I should get true when asking the node authorization service if editing the "title" property is granted

  @Isolated @fixtures
  Scenario: Language-Restricted (German) Managers should not be able to edit english nodes
    Given I am authenticated with role "Neos.ContentRepository:GermanManager"
    And I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace  | Language      |
      | user-admin | en_ZZ, mul_ZZ |
    Then I should not be granted to set any of the node's attributes
    And I should get false when asking the node authorization service if editing this node is granted
    When I get a node by path "/sites/content-repository/company" with the following context:
      | Workspace  | Language      |
      | user-admin | en_ZZ, mul_ZZ |
    Then I should not be granted to set any of the node's attributes
    And I should get false when asking the node authorization service if editing this node is granted

  @Isolated @fixtures
  Scenario: Service Managers should be able to edit both german and non-german nodes
    Given I am authenticated with role "Neos.ContentRepository:ServiceManager"
    When I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace  | Language      |
      | user-admin | de_ZZ, mul_ZZ |
    Then I should be granted to set the "title" property to "Dienstleistungen speziell"
    And I should get true when asking the node authorization service if editing the "title" property is granted
    Given I get a node by path "/sites/content-repository/service" with the following context:
      | Workspace  | Language      |
      | user-admin | en_ZZ, mul_ZZ |
    Then I should be granted to set the "title" property to "Dienstleistungen speziell"
    And I should get true when asking the node authorization service if editing the "title" property is granted
    When I get a node by path "/sites/content-repository/company" with the following context:
      | Workspace  | Language      |
      | user-admin | de_ZZ, mul_ZZ |
    Then I should not be granted to set any of the node's attributes
    And I should get false when asking the node authorization service if editing this node is granted
    When I get a node by path "/sites/content-repository/company" with the following context:
      | Workspace  | Language      |
      | user-admin | en_ZZ, mul_ZZ |
    Then I should not be granted to set any of the node's attributes
    And I should get false when asking the node authorization service if editing this node is granted
