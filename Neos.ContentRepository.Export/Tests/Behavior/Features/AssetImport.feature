@flowEntities
Feature: As a user of the CR I want to import assets using the AssetRepositoryImportProcessor

  Scenario: Import multiple assets with distinct file contents
    Given using the following Resources:
      | file-name | content    |
      | first.txt | contents 1 |
      | other.txt | contents 2 |
    And using the following Assets:
      """
      {"identifier":"asset-1","type":"DOCUMENT","title":"Asset 1","copyrightNotice":"Copyright 1","caption":"Caption 1","assetSourceIdentifier":"neos","resource":{"filename":"first.txt","collectionName":"persistent","mediaType":"text/plain","sha1":"sha1(first.txt)"}}
      {"identifier":"asset-2","type":"DOCUMENT","title":"Asset 2","copyrightNotice":"","caption":"","assetSourceIdentifier":"neos","resource":{"filename":"other.txt","collectionName":"persistent","mediaType":"text/plain","sha1":"sha1(other.txt)"}}
      """
    When I import the Assets
    Then I expect the following Assets to exist:
      | identifier | title   | copyrightNotice | caption   |
      | asset-1    | Asset 1 | Copyright 1     | Caption 1 |
      | asset-2    | Asset 2 |                 |           |
    And I expect 1 PersistentResource for the Resource "first.txt"
    And I expect 1 PersistentResource for the Resource "other.txt"

  Scenario: Importing the same assets a second time skips them and does not create additional resources
    Given using the following Resources:
      | file-name | content    |
      | first.txt | contents 1 |
      | other.txt | contents 2 |
    And using the following Assets:
      """
      {"identifier":"asset-1","type":"DOCUMENT","title":"Asset 1","copyrightNotice":"","caption":"","assetSourceIdentifier":"neos","resource":{"filename":"first.txt","collectionName":"persistent","mediaType":"text/plain","sha1":"sha1(first.txt)"}}
      {"identifier":"asset-1","type":"DOCUMENT","title":"Asset 1","copyrightNotice":"","caption":"","assetSourceIdentifier":"neos","resource":{"filename":"first.txt","collectionName":"persistent","mediaType":"text/plain","sha1":"sha1(first.txt)"}}
      {"identifier":"asset-2","type":"DOCUMENT","title":"Asset 2","copyrightNotice":"","caption":"","assetSourceIdentifier":"neos","resource":{"filename":"other.txt","collectionName":"persistent","mediaType":"text/plain","sha1":"sha1(other.txt)"}}
      """
    And I import the Assets
    Then I expect the following Assets to exist:
      | identifier | title   |
      | asset-1    | Asset 1 |
      | asset-2    | Asset 2 |
    And I expect 1 PersistentResource for the Resource "first.txt"
    And I expect 1 PersistentResource for the Resource "other.txt"

  Scenario: Import multiple assets that share the same file contents
    Given using the following Resources:
      | file-name  | content            |
      | shared.txt | identical contents |
      | other.txt  | other contents     |
    And using the following Assets:
      """
      {"identifier":"asset-1","type":"DOCUMENT","title":"Asset 1","copyrightNotice":"","caption":"","assetSourceIdentifier":"neos","resource":{"filename":"shared.txt","collectionName":"persistent","mediaType":"text/plain","sha1":"sha1(shared.txt)"}}
      {"identifier":"asset-2","type":"DOCUMENT","title":"Asset 2","copyrightNotice":"","caption":"","assetSourceIdentifier":"neos","resource":{"filename":"shared.txt","collectionName":"persistent","mediaType":"text/plain","sha1":"sha1(shared.txt)"}}
      {"identifier":"asset-3","type":"DOCUMENT","title":"Asset 3","copyrightNotice":"","caption":"","assetSourceIdentifier":"neos","resource":{"filename":"other.txt","collectionName":"persistent","mediaType":"text/plain","sha1":"sha1(other.txt)"}}
      """
    When I import the Assets
    Then I expect the following Assets to exist:
      | identifier | title   |
      | asset-1    | Asset 1 |
      | asset-2    | Asset 2 |
      | asset-3    | Asset 3 |
    And I expect 2 PersistentResources for the Resource "shared.txt"
    And I expect 1 PersistentResource for the Resource "other.txt"
