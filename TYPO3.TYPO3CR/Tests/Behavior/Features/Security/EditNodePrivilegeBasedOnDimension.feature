Feature: Privilege to restrict editing of nodes for a single dimension only

  Background:

    Given I have the following policies:
      """
      privilegeTargets:

        'TYPO3\TYPO3CR\Security\Authorization\Privilege\Node\EditNodePrivilege':

          'TYPO3.TYPO3CR:EditServiceNodes':
            matcher: 'isDescendantNodeOf("/sites/typo3cr/service")'

          # EditEverything is needed, to switch to a "WHITELIST MODE" - i.e. where everything must be allowed explicitely.
          'TYPO3.TYPO3CR:EditEverything':
            matcher: 'TRUE'

          'TYPO3.TYPO3CR:EditGerman':
            matcher: 'isInDimensionPreset("language", "de")'

      roles:
        'TYPO3.Flow:Everybody':
          privileges: []

        'TYPO3.Flow:Anonymous':
          privileges: []

        'TYPO3.Flow:AuthenticatedUser':
          privileges: []

        'TYPO3.TYPO3CR:ServiceManager':
          # can only edit service nodes, but in all languages.
          privileges:
            -
              privilegeTarget: 'TYPO3.TYPO3CR:EditServiceNodes'
              permission: GRANT

        'TYPO3.TYPO3CR:GermanManager':
          # can only edit german nodes
          privileges:
            -
              privilegeTarget: 'TYPO3.TYPO3CR:EditGerman'
              permission: GRANT
      """
    And I have the following content dimensions:
      | Identifier | Default | Presets                          |
      | language   | mul_ZZ  | de=de_ZZ,mul_ZZ; en=en_ZZ,mul_ZZ |

    And I have the following nodes:
      | Identifier                           | Path                   | Node Type                      | Properties                    | Workspace | Language |
      | ecf40ad1-3119-0a43-d02e-55f8b5aa3c70 | /sites                 | unstructured                   |                               | live      | mul_ZZ   |
      | fd5ba6e1-4313-b145-1004-dad2f1173a35 | /sites/typo3cr         | TYPO3.TYPO3CR.Testing:Document | {"title": "Home"}             | live      | mul_ZZ   |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/typo3cr/company | TYPO3.TYPO3CR.Testing:Document | {"title": "Company"}          | live      | en_ZZ    |
      | 68ca0dcd-2afb-ef0e-1106-a5301e65b8a0 | /sites/typo3cr/company | TYPO3.TYPO3CR.Testing:Document | {"title": "Firma"}            | live      | de_ZZ    |
      | 52540602-b417-11e3-9358-14109fd7a2dd | /sites/typo3cr/service | TYPO3.TYPO3CR.Testing:Document | {"title": "Service"}          | live      | en_ZZ    |
      | 52540602-b417-11e3-9358-14109fd7a2dd | /sites/typo3cr/service | TYPO3.TYPO3CR.Testing:Document | {"title": "Dienstleistungen"} | live      | de_ZZ    |

  @Isolated @fixtures
  Scenario: "Everybody"-Authenticated users are not granted to set any property
    Given I am authenticated with role "TYPO3.Flow:AuthenticatedUser"
    And I get a node by path "/sites/typo3cr/company" with the following context:
      | Workspace  | Language      |
      | user-admin | en_ZZ, mul_ZZ |
    Then I should get FALSE when asking the node authorization service if editing this node is granted
    And I should not be granted to set any of the node's attributes

  @Isolated @fixtures
  Scenario: Language-Restricted (German) Managers should be able to edit german nodes
    Given I am authenticated with role "TYPO3.TYPO3CR:GermanManager"
    And I get a node by path "/sites/typo3cr/service" with the following context:
      | Workspace  | Language      |
      | user-admin | de_ZZ, mul_ZZ |
    Then I should be granted to set the "title" property to "Dienstleistungen speziell"
    And I should get TRUE when asking the node authorization service if editing the "title" property is granted
    When I get a node by path "/sites/typo3cr/company" with the following context:
      | Workspace  | Language      |
      | user-admin | de_ZZ, mul_ZZ |
    Then I should be granted to set the "title" property to "Firma speziell"
    And I should get TRUE when asking the node authorization service if editing the "title" property is granted

  @Isolated @fixtures
  Scenario: Language-Restricted (German) Managers should not be able to edit english nodes
    Given I am authenticated with role "TYPO3.TYPO3CR:GermanManager"
    And I get a node by path "/sites/typo3cr/service" with the following context:
      | Workspace  | Language      |
      | user-admin | en_ZZ, mul_ZZ |
    Then I should not be granted to set any of the node's attributes
    And I should get FALSE when asking the node authorization service if editing this node is granted
    When I get a node by path "/sites/typo3cr/company" with the following context:
      | Workspace  | Language      |
      | user-admin | en_ZZ, mul_ZZ |
    Then I should not be granted to set any of the node's attributes
    And I should get FALSE when asking the node authorization service if editing this node is granted

  @Isolated @fixtures
  Scenario: Service Managers should be able to edit both german and non-german nodes
    Given I am authenticated with role "TYPO3.TYPO3CR:ServiceManager"
    When I get a node by path "/sites/typo3cr/service" with the following context:
      | Workspace  | Language      |
      | user-admin | de_ZZ, mul_ZZ |
    Then I should be granted to set the "title" property to "Dienstleistungen speziell"
    And I should get TRUE when asking the node authorization service if editing the "title" property is granted
    Given I get a node by path "/sites/typo3cr/service" with the following context:
      | Workspace  | Language      |
      | user-admin | en_ZZ, mul_ZZ |
    Then I should be granted to set the "title" property to "Dienstleistungen speziell"
    And I should get TRUE when asking the node authorization service if editing the "title" property is granted
    When I get a node by path "/sites/typo3cr/company" with the following context:
      | Workspace  | Language      |
      | user-admin | de_ZZ, mul_ZZ |
    Then I should not be granted to set any of the node's attributes
    And I should get FALSE when asking the node authorization service if editing this node is granted
    When I get a node by path "/sites/typo3cr/company" with the following context:
      | Workspace  | Language      |
      | user-admin | en_ZZ, mul_ZZ |
    Then I should not be granted to set any of the node's attributes
    And I should get FALSE when asking the node authorization service if editing this node is granted