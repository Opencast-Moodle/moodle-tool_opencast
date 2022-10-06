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
  Scenario: When the admin is on the tool_opencast category settings page and two instances are given, the connection check should target both instances individually
    Given I log in as "admin"
    And the following config values are set as admin:
      | config          | value                    | plugin         |
      | ocinstances          | [{"id":1,"name":"OC demo server","isvisible":true,"isdefault":true},{"id":2,"isvisible":1,"isdefault":false,"name":"Invalid server"}] | tool_opencast  |
      | apiurl_2             | http://notexistent.not  | tool_opencast  |
    And I navigate to "Plugins > Admin tools > Opencast API" in site administration
    And I click on "button[data-instanceid='']" "css_element"
    And I wait "3" seconds
    Then I should see "Opencast API URL test successful."
    And I should see "Opencast API User Credentials test successful."
    And I click on "Cancel" "button" in the "Connection Test Tool" "dialogue"
    And I click on "button[data-instanceid='2']" "css_element"
    And I wait "3" seconds
    Then I should see "Opencast API URL test failed"
    And I should see "Opencast API User Credentials test failed"
