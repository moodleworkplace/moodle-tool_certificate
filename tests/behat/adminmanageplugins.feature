@tool @tool_certificate @moodleworkplace
Feature: Manage certificate elements plugins
  In order to manage certificate elements plugins
  As a manager
  I need to be able to view, hide/show, reorder and unistall element plugins

  Scenario: View default element plugins
    When I log in as "admin"
    And I navigate to "Plugins > Admin tools > Manage certificate element plugins" in site administration
    Then I should see "Border"
    And I should see "Code"
    And I should see "Date"
    And I should see "Digital signature"
    And I should see "Image"
    And I should see "Dynamic fields"
    And I should see "Text"
    And I should see "User field"
    And I should see "User picture"

  Scenario: Uninstall an element plugin
    When I log in as "admin"
    And I navigate to "Plugins > Admin tools > Manage certificate element plugins" in site administration
    And I click on "Uninstall" "link" in the "User field" "table_row"
    And I press "Continue"
    Then I should see "Success"

  Scenario: Disable and enable an element plugin
    When I log in as "admin"
    And I navigate to "Plugins > Admin tools > Manage certificate element plugins" in site administration
    And I click on "Disable" "link" in the "User field" "table_row"
    Then "Enable" "link" should exist in the "User field" "table_row"
    And I click on "Enable" "link" in the "User field" "table_row"
    Then "Disable" "link" should exist in the "User field" "table_row"
