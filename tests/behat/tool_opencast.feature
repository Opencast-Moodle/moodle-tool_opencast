@tool @tool_opencast
Feature: Setup Opencast instances
  In order to setup Opencast instances
  As an admin
  I need to be able to add, edit and delete instances

  @javascript
  Scenario: Users should be able to add a new OC instance
    Given I log in as "admin"
    And I navigate to "Plugins > Admin tools > Opencast API > Opencast Instances" in site administration
    And I click on "Add instance" "button"
    And I set the field with xpath "//div[@id='instancestable']//div[@class='tabulator-table']/div[2]/div[@tabulator-field='name']" to "Second instance"
    And I wait "2" seconds
    And I click on "Save changes" "button"
    And I navigate to "Plugins > Admin tools > Opencast API" in site administration
    Then I should see "Configuration: Default"
    And I should see "apiurl"
    And I should see "Configuration: Second instance"
    And I should see "apiurl_2"

  @javascript
  Scenario: Users should not be able to delete the default OC instance
    Given I log in as "admin"
    And I navigate to "Plugins > Admin tools > Opencast API > Opencast Instances" in site administration
    And I click on "//div[@id='instancestable']//i[contains(@class, 'fa-trash')]" "xpath_element"
    And I click on "Delete" "button" in the "Delete instance" "dialogue"
    And I click on "Save changes" "button"
    Then I should see "There must be exactly one default Opencast instance."
    And I should see "Some settings were not changed due to an error."

  @javascript
  Scenario: Users should able to delete the old OC instance after selecting a new one
    Given I log in as "admin"
    And the following config values are set as admin:
      | config          | value                    | plugin         |
      | ocinstances          | [{"id":1,"name":"OC demo server","isvisible":true,"isdefault":true},{"id":2,"isvisible":1,"isdefault":false,"name":"Invalid server"}] | tool_opencast  |
      | apiurl_2             | http://notexistent.not  | tool_opencast  |
    And I navigate to "Plugins > Admin tools > Opencast API > Opencast Instances" in site administration
    And I click on "//div[@id='instancestable']//div[@class='tabulator-table']/div[1]/div[@tabulator-field='isdefault']" "xpath_element"
    And I click on "//div[@id='instancestable']//div[@class='tabulator-table']/div[2]/div[@tabulator-field='isdefault']" "xpath_element"
    And I click on "//div[@id='instancestable']//i[contains(@class, 'fa-trash')]" "xpath_element"
    And I click on "Delete" "button" in the "Delete instance" "dialogue"
    And I click on "Save changes" "button"
    Then I should see "Changes saved"
