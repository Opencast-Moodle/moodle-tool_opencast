@tool @tool_opencast
Feature: Configure and check maintenance
  In order to configure and check the maintenance period
  As an admin
  I need to be able to set and configure the maintenance for each instance
  And check if the maintenance is properly set and displayed.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                | idnumber |
      | teacher1 | Teacher   | 1        | teacher1@example.com | T1       |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following config values are set as admin:
      | config                      | value                                                         | plugin         |
      | apiurl_1                    | https://stable.opencast.org                                   | tool_opencast  |
      | apipassword_1               | opencast                                                      | tool_opencast  |
      | apiusername_1               | admin                                                         | tool_opencast  |
      | ocinstances                 | [{"id":1,"name":"Default","isvisible":true,"isdefault":true}] | tool_opencast  |
      | limituploadjobs_1           | 0                                                             | block_opencast |
      | group_creation_1            | 0                                                             | block_opencast |
      | group_name_1                | Moodle_course_[COURSEID]                                      | block_opencast |
      | series_name_1               | Course_Series_[COURSEID]                                      | block_opencast |
      | enablechunkupload_1         | 0                                                             | block_opencast |
      | uploadworkflow_1            | schedule-and-upload                                           | block_opencast |
      | enableuploadwfconfigpanel_1 | 1                                                             | block_opencast |
      | alloweduploadwfconfigs_1    | straightToPublishing                                          | block_opencast |

  @javascript
  Scenario: As an admin I should be able to configure the maintenance for an instance
    Given I log in as "admin"
    When I navigate to "Plugins > Admin tools > Opencast API > Configuration" in site administration
    Then "Enable" "option" should exist in the "#id_s_tool_opencast_maintenancemode_1" "css_element"
    And "Read Only" "option" should exist in the "#id_s_tool_opencast_maintenancemode_1" "css_element"
    And "Disable" "option" should exist in the "#id_s_tool_opencast_maintenancemode_1" "css_element"
    And I set the field "Maintenance mode" to "Enable"
    And I should see "Notification Level"
    And "Warning" "option" should exist in the "#id_s_tool_opencast_maintenancemode_notification_level_1" "css_element"
    And "Error" "option" should exist in the "#id_s_tool_opencast_maintenancemode_notification_level_1" "css_element"
    And "Information" "option" should exist in the "#id_s_tool_opencast_maintenancemode_notification_level_1" "css_element"
    And "Success" "option" should exist in the "#id_s_tool_opencast_maintenancemode_notification_level_1" "css_element"
    And I set the field "Notification Level" to "Error"
    And I set the field "Maintenance Message" to "<strong>Opencast Maintenance Notification</strong>"
    And I click on "#id_s_tool_opencast_maintenancemode_startdate_1_enabled" "css_element"
    And I select "00" from the "s_tool_opencast_maintenancemode_startdate_1[hour]" singleselect
    And I select "00" from the "s_tool_opencast_maintenancemode_startdate_1[minute]" singleselect
    And I click on "#id_s_tool_opencast_maintenancemode_enddate_1_enabled" "css_element"
    And I select "23" from the "s_tool_opencast_maintenancemode_enddate_1[hour]" singleselect
    And I select "55" from the "s_tool_opencast_maintenancemode_enddate_1[minute]" singleselect
    When I press "Save changes"
    Then I should see "Changes saved"

  @javascript
  Scenario: As an admin I should not be able to configure the maintenance in the past
    Given I log in as "admin"
    When I navigate to "Plugins > Admin tools > Opencast API > Configuration" in site administration
    Then I set the field "Maintenance mode" to "Enable"
    And I click on "#id_s_tool_opencast_maintenancemode_enddate_1_enabled" "css_element"
    And I select "00" from the "s_tool_opencast_maintenancemode_enddate_1[hour]" singleselect
    And I select "00" from the "s_tool_opencast_maintenancemode_enddate_1[minute]" singleselect
    When I press "Save changes"
    Then I should not see "Changes saved"
    And I should see "This field should not be in the past!" in the "#admin-maintenancemode_enddate_1" "css_element"

  @javascript
  Scenario: As an admin I should not be able to configure the false maintenance start date and end date
    Given I log in as "admin"
    When I navigate to "Plugins > Admin tools > Opencast API > Configuration" in site administration
    Then I set the field "Maintenance mode" to "Enable"
    And I click on "#id_s_tool_opencast_maintenancemode_enddate_1_enabled" "css_element"
    And I select "23" from the "s_tool_opencast_maintenancemode_enddate_1[hour]" singleselect
    And I select "55" from the "s_tool_opencast_maintenancemode_enddate_1[minute]" singleselect
    And I click on "#id_s_tool_opencast_maintenancemode_startdate_1_enabled" "css_element"
    And I select "23" from the "s_tool_opencast_maintenancemode_startdate_1[hour]" singleselect
    And I select "55" from the "s_tool_opencast_maintenancemode_startdate_1[minute]" singleselect
    When I press "Save changes"
    Then I should not see "Changes saved"
    And I should see "This field should be before \"Maintenance ends at\"" in the "#admin-maintenancemode_startdate_1" "css_element"
    And I should see "This field should be after \"Maintenance starts at\"" in the "#admin-maintenancemode_enddate_1" "css_element"

  @javascript
  Scenario: Teachers should not be able to access the Opencast plugin during maintenance period
    Given I log in as "teacher1"
    And I setup block plugin
    And I make sure the block drawer keeps opened
    And I am on "Course 1" course homepage with editing mode on
    And I add the "Opencast Videos" block
    And I wait "2" seconds
    And I reload the page
    And I should see "Opencast Videos"
    And the following config values are set as admin:
    | maintenancemode_1                     | 2                                 | tool_opencast |
    | maintenancemode_notification_level_1  | error                             | tool_opencast |
    | maintenancemode_message_1             | Opencast Maintenance Notification | tool_opencast |
    | maintenancemode_startdate_1           | {"enabled":false}                 | tool_opencast |
    | maintenancemode_enddate_1             | {"enabled":false}                 | tool_opencast |
    When I reload the page
    And I wait "2" seconds
    And I click on "Add video" "button"
    Then I should see "Opencast Maintenance Notification" in the "#user-notifications" "css_element"
    And I should not see "Videos available in this course" in the "#region-main" "css_element"
    And the following config values are set as admin:
    | maintenancemode_1                     | 0                                 | tool_opencast |
    And I reload the page
    When I click on "Add video" "button"
    Then I should not see "Opencast Maintenance Notification" in the "#user-notifications" "css_element"
    And I should see "Opencast Videos" in the "#page-header" "css_element"
