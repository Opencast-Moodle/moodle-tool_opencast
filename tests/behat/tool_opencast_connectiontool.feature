@tool @tool_opencast
Feature: Check the connection to Opencast instances
  In order to setup a correct connection to OC instances
  As an admin
  I need to be able to check the connection

  @javascript
  Scenario: When the admin is on a configuration section page and all connections configs are correct, the connection check should succeed
    Given I log in as "admin"
    And I navigate to "Plugins > Admin tools > Opencast API > Configuration" in site administration
    And I click on "Connection Test Tool" "button"
    And I wait "3" seconds
    Then I should see "Opencast API URL test successful."
    And I should see "Opencast API User Credentials test successful."

  @javascript
  Scenario: When the admin is on a configuration section page and the apiurl is incorrect, the connection check should fail
    Given I log in as "admin"
    And I navigate to "Plugins > Admin tools > Opencast API > Configuration" in site administration
    And I set the following fields to these values:
      | Opencast API URL | http://notexistent.not |
    And I click on "Connection Test Tool" "button"
    And I wait "3" seconds
    Then I should see "Opencast API URL test failed"
    And I should see "Opencast API User Credentials test failed"

  @javascript
  Scenario: When the admin is on a configuration section page and the apiusername is incorrect, the connection check with credentials should fail
    Given I log in as "admin"
    And I navigate to "Plugins > Admin tools > Opencast API > Configuration" in site administration
    And I set the following fields to these values:
      | Username of Opencast API user | wrongapiuser |
    And I click on "Connection Test Tool" "button"
    And I wait "3" seconds
    Then I should see "Opencast API URL test successful."
    And I should see "Opencast API User Credentials test failed"

  @javascript
  Scenario: When two instances are given and the admin is on the second instance's configuration page, the connection check for the second instance with invalid data should fail
    Given I log in as "admin"
    And I navigate to "Plugins > Admin tools > Opencast API > Opencast Instances" in site administration
    And I click on "Add instance" "button"
    And I click on "//div[@id='instancestable']//div[@class='tabulator-table']/div[2]/div[@tabulator-field='name']" "xpath"
    And I type "Second instance"
    And I wait "5" seconds
    And I click on "Save changes" "button"
    And I wait "5" seconds
    And I navigate to "Plugins > Admin tools > Opencast API > Configuration: Second instance" in site administration
    And I set the field "id_s_tool_opencast_apiurl_2" to "http://notexistent.not"
    And I set the field "id_s_tool_opencast_apitimeout_2" to "2000"
    And I set the field "id_s_tool_opencast_apiconnecttimeout_2" to "1000"
    And I click on "button[data-instanceid='2']" "css_element"
    And I wait "3" seconds
    Then I should see "Opencast API URL test failed"
    And I should see "Opencast API User Credentials test failed"
