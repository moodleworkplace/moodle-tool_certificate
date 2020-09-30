@tool @tool_certificate @moodleworkplace @javascript
Feature: Being able to manage site templates
  In order to ensure managing site templates works as expected
  As an admin
  I need to manage and load site templates

  Background:
    Given "3" tenants exist with "5" users and "0" courses in each
    And the following "roles" exist:
      | shortname             | name                        | archetype |
      | certificatemanager    | Certificate manager         |           |
      | configviewer          | Config viewer               |           |
    And the following "permission overrides" exist:
      | capability                     | permission | role               | contextlevel | reference |
      | moodle/site:configview         | Allow      | configviewer       | System       |           |
      | moodle/site:configview         | Allow      | certificatemanager | System       |           |
      | moodle/category:viewcourselist | Allow      | certificatemanager | System       |           |
      | tool/certificate:manage        | Allow      | certificatemanager | System       |           |

  Scenario: Adding a site template
    When I log in as "admin"
    When I navigate to "Certificates > Manage certificate templates" in site administration
    And I wait "2" seconds
    And I follow "New certificate template"
    And I wait "3" seconds
    And I set the field "Name" to "Certificate 1"
    And I press "Save" in the modal form dialogue
    And I add the element "Border" to page "1" of the "Certificate 1" site certificate template
    And I set the following fields to these values:
      | Width  | 5 |
      | Colour | #045ECD |
    And I press "Save" in the modal form dialogue
    And I follow "Manage certificate templates"
    Then I should see "Certificate 1"

  Scenario: Adding a template when user can manage templates anywhere
    When the following "users" exist:
      | username | firstname | lastname | email           |
      | manager  | Manager | 1 | manager@example.com |
    And the following "role assigns" exist:
      | user    | role           | contextlevel | reference |
      | manager | certificatemanager | System       |           |
    And the following users allocations to tenants exist:
      | user | tenant |
      | manager | Tenant2 |
    And I log in as "manager"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I wait "2" seconds
    And I follow "New certificate template"
    And I set the following fields to these values:
      | Name | Certificate 1 |
      | Course category | Category2 |
      | shared          | 1         |
    And I press "Save" in the modal form dialogue
    And I follow "Manage certificate templates"
    Then I should see "Certificate 1"
    And I should see "Shared" in the "Certificate 1" "table_row"
    And I click on "Edit details" "link" in the "Certificate 1" "table_row"
    And I set the following fields to these values:
      | shared          | 0         |
    And I press "Save" in the modal form dialogue
    And I should not see "Shared" in the "Certificate 1" "table_row"

  Scenario: Adding a template when user can manage templates in one category
    When the following "role assigns" exist:
      | user   | role               | contextlevel | reference |
      | user21 | certificatemanager | Category     | CAT2      |
      | user21 | configviewer       | System       |           |
    And I log in as "user21"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I follow "New certificate template"
    And I set the following fields to these values:
      | Name | Certificate 1 |
    And I press "Save" in the modal form dialogue
    And I follow "Manage certificate templates"
    Then I should see "Certificate 1"
    And I log out
    And I log in as "tenantadmin1"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I should not see "Certificate 1"
    And I log out
    And I log in as "tenantadmin2"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I should see "Certificate 1"
    And I log out

  Scenario: Adding template with invalid width, heigth and margins
    When I log in as "admin"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I follow "New certificate template"
    And I set the following fields to these values:
      | Name         | Certificate 1 |
      | Page width   | 0             |
      | Page height  | 0             |
      | Left margin  | -1            |
      | Right margin | -1            |
    And I press "Save" in the modal form dialogue
    Then I should see "The width has to be a valid number greater than 0."
    Then I should see "The height has to be a valid number greater than 0."
    Then I should see "The margin has to be a valid number greater than 0."

  Scenario: Edit details of existing template
    When the following certificate templates exist:
      | name          | numberofpages | category  |
      | Certificate 1 | 1             | Category1 |
    And I log in as "tenantadmin1"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I follow "Certificate 1"
    And I wait "1" seconds
    And I press "Edit details"
    And I set the field "Name" to "Certificate 2"
    And I press "Save" in the modal form dialogue
    And I should not see "Certificate 1"
    And I should see "Certificate 2"
    And I log out

  Scenario: Deleting a site template
    When the following certificate templates exist:
      | name          | numberofpages | category  |
      | Certificate 1 | 1             | Category1 |
    And I log in as "tenantadmin1"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on "Edit content" "link" in the "Certificate 1" "table_row"
    And I add the element "User field" to page "1" of the "Certificate 1" site certificate template
    And I press "Save" in the modal form dialogue
    When I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on "Delete" "link" in the "Certificate 1" "table_row"
    And I click on "Cancel" "button" in the "Confirm" "dialogue"
    And I should see "Certificate 1"
    And I click on "Delete" "link" in the "Certificate 1" "table_row"
    And I click on "Delete" "button" in the "Confirm" "dialogue"
    Then I should not see "Certificate 1"

  Scenario: Duplicating a site template from the same tenant without system-level capabilities
    When the following certificate templates exist:
      | name          | numberofpages | category  |
      | Certificate 1 | 1             | Category1 |
    And I log in as "tenantadmin1"
    When I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on "Edit content" "link" in the "Certificate 1" "table_row"
    And I wait "2" seconds
    And I add the element "User field" to page "1" of the "Certificate 1" site certificate template
    And I press "Save" in the modal form dialogue
    When I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on "Duplicate" "link" in the "Certificate 1" "table_row"
    And I click on "Cancel" "button" in the "Confirm" "dialogue"
    And I should see "Certificate 1"
    And I should not see "Certificate 1 (copy)"
    And I click on "Duplicate" "link" in the "Certificate 1" "table_row"
    And I click on "Duplicate" "button" in the "Confirm" "dialogue"
    Then I should see "Certificate 1"
    And I should see "Certificate 1 (copy)"
    And I log out

  Scenario: Duplicating a shared site template without system-level capabilities
    When the following certificate templates exist:
      | name          | category  | numberofpages |
      | Certificate 0 |           | 1             |
      | Certificate 1 | Category1 | 1             |
      | Certificate 2 | Category2 | 1             |
    And I log in as "tenantadmin1"
    When I navigate to "Certificates > Manage certificate templates" in site administration
    And "Edit content" "link" should exist in the "Certificate 1" "table_row"
    And I should not see "Certificate 2"
    And "Edit content" "link" should not exist in the "Certificate 0" "table_row"
    And "Duplicate" "link" should not exist in the "Certificate 0" "table_row"
    And "Duplicate" "link" should exist in the "Certificate 1" "table_row"
    And I click on "Duplicate" "link" in the "Certificate 1" "table_row"
    And I click on "Duplicate" "button" in the "Confirm" "dialogue"
    Then I should see "Certificate 0"
    And I should see "Certificate 1 (copy)"
    And I log out
    # Now make sure the duplicate was created for Category1.
    And I log in as "admin"
    When I navigate to "Certificates > Manage certificate templates" in site administration
    And "Category1" "text" should exist in the "Certificate 1 (copy)" "table_row"
    And "Duplicate" "link" should exist in the "Certificate 0" "table_row"
    And "Duplicate" "link" should exist in the "Certificate 1" "table_row"
    And I log out

  Scenario: Duplicating a site template to another category
    When the following "users" exist:
      | username | firstname | lastname | email           |
      | manager  | Manager | 1 | manager@example.com |
    And the following "role assigns" exist:
      | user    | role               | contextlevel | reference |
      | manager | certificatemanager | System       |           |
    And the following certificate templates exist:
      | name          | category  |
      | Certificate 1 |           |
      | Certificate 2 | Category2 |
    And I log in as "manager"
    When I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on "Duplicate" "link" in the "Certificate 1" "table_row"
    And I press "Cancel" in the modal form dialogue
    And I should see "Certificate 1"
    And I should not see "Certificate 1 (copy)"
    And I click on "Duplicate" "link" in the "Certificate 1" "table_row"
    And I set the following fields to these values:
      | Course category | Category2 |
    And I press "Duplicate" in the modal form dialogue
    Then I should see "Certificate 1"
    And I should see "Certificate 1 (copy)"
    And I should see "Category2" in the "Certificate 1 (copy)" "table_row"
    And I log out

  Scenario: Edit name of certificate template
    When the following certificate templates exist:
      | name          | numberofpages | category  |
      | Certificate 1 | 1             | Category1 |
    And I log in as "tenantadmin1"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on "Edit template name" "link" in the "Certificate 1" "table_row"
    And I set the field "New value for Certificate 1" to "Certificate 2"
    And I press key "13" in the field "New value for Certificate 1"
    And I should not see "Certificate 1"
    And I should see "Certificate 2"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I should not see "Certificate 1"
    And I should see "Certificate 2"
    And I log out
