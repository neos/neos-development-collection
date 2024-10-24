@contentrepository
Feature: Simple migrations without content dimensions

  Background:
    Given using no content dimensions
    And using the following node types:
    """yaml
    'Neos.Neos:Site': {}
    'Some.Package:Homepage':
      superTypes:
        'Neos.Neos:Site': true
      properties:
        'text':
          type: string
          defaultValue: 'My default text'
    """
    And using identifier "default", I define a content repository
    And I am in content repository "default"

  Scenario: Site records without domains
    When I have the following site data rows:
      | persistence_object_identifier   | name        | nodename      | siteresourcespackagekey | state | domains | primarydomain |
      | "site1"                         | "Site 1"    | "site_1_node" | "Site1.Package"         | 1     | null    | null          |
      | "site2"                         | "Site 2"    | "site_2_node" | "Site2.Package"         | 2     | null    | null          |
    And I run the site migration
    Then I expect the following sites to be exported
      | name      | nodeName      | siteResourcesPackageKey | online | domains |
      | "Site 1"  | "site_1_node" | "Site1.Package"         | true   | []      |
      | "Site 2"  | "site_2_node" | "Site2.Package"         | false  | []      |

  Scenario: Site records with domains
    When I have the following site data rows:
      | persistence_object_identifier   | name      | nodename      | siteresourcespackagekey | state | domains | primarydomain |
      | "site1"                         | "Site 1"  | "site_1_node" | "Site1.Package"         | 1     | null    | "domain2"     |
      | "site2"                         | "Site 2"  | "site_2_node" | "Site2.Package"         | 1     | null    | null          |
    When I have the following domain data rows:
      | persistence_object_identifier | hostname       | scheme    | port | active | site    |
      | "domain1"                     | "domain_1.tld" | "https"   | 123  | true   | "site1" |
      | "domain2"                     | "domain_2.tld" | "http"    | null | true   | "site1" |
      | "domain3"                     | "domain_3.tld" | null      | null | true   | "site2" |
      | "domain4"                     | "domain_4.tld" | null      | null | false  | "site2" |
    And I run the site migration
    Then I expect the following sites to be exported
      | name      | nodeName      | siteResourcesPackageKey | online | domains                                                                                                                                                                            |
      | "Site 1"  | "site_1_node" | "Site1.Package"         | true   | [{"hostname": "domain_1.tld", "scheme": "https", "port": 123, "active": true, "primary": false},{"hostname": "domain_2.tld", "scheme": "http", "port": null, "active": true, "primary": true}] |
      | "Site 2"  | "site_2_node" | "Site2.Package"         | true   | [{"hostname": "domain_3.tld", "scheme": null, "port": null, "active": true, "primary": false},{"hostname": "domain_4.tld", "scheme": null, "port": null, "active": false, "primary": false}]   |
