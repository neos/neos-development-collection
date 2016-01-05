Feature: Accounts / User Entity Monitoring
  As an API user of the history
  I expect that adding/updating/deleting an account or party triggers history updates

  Background:
    Given I have an empty history
    Given I have the following "monitorEntities" configuration:
    """
    'TYPO3\Flow\Security\Account':
      events:
        created: ACCOUNT_CREATED
      data:
        accountIdentifier: '${entity.accountIdentifier}'
        authenticationProviderName: '${entity.authenticationProviderName}'
        expirationDate: '${entity.expirationDate}'
        party: '${entity.party.name.fullName}'
    'TYPO3\Neos\Domain\Model\User':
      events:
        created: PERSON_CREATED
      data:
        name: '${entity.name.fullName}'
        primaryElectronicAddress: '${entity.primaryElectronicAddress}'
    """

    # TODO: subclasses in monitorEntities
  @fixtures
  Scenario: Creating an account is monitored
    When I create the following accounts:
      | User  | Password | First Name | Last Name | Roles                    |
      | admin | password | Sebastian  | Kurfuerst | TYPO3.Neos:Administrator |
    Then I should have the following history entries:
      | Event Type      |
      | PERSON_CREATED  |
      | ACCOUNT_CREATED |