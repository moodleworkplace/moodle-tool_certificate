@tool @tool_certificate
Feature: Being able to manage site templates
  In order to ensure managing site templates works as expected
  As an admin
  I need to manage and load site templates

  Background:
    Given I log in as "admin"
    And the following "roles" exist:
      | shortname             | name                        | archetype |
      | certificatemanager    | Certificate manager         |           |
      | certificatemanagerall | Certificate manager for all |           |
    And I set the following system permissions of "Certificate manager for all" role:
      | capability                           | permission |
      | tool/certificate:manageforalltenants | Allow      |
      | moodle/site:configview               | Allow      |
    And I set the following system permissions of "Certificate manager" role:
      | capability | permission |
      | tool/certificate:manage | Allow |
      | moodle/site:configview  | Allow |
    And the following tenants exist:
      | name     |
      | Tenant 1 |
      | Tenant 2 |
    And I log out

  Scenario: Adding a site template
    When I log in as "admin"
    When I navigate to "Certificates > Manage certificate templates" in site administration
    And I press "Create template"
    And I set the field "Name" to "Certificate 1"
    And I press "Save changes"
    And I add the element "Border" to page "1" of the "Certificate 1" certificate template
    And I set the following fields to these values:
      | Width  | 5 |
      | Colour | #045ECD |
    And I press "Save changes"
    And I follow "Manage certificate templates"
    Then I should see "Certificate 1"

  Scenario: Adding a template to another tenant
    When the following "users" exist:
      | username | firstname | lastname | email           |
      | manager  | Manager | 1 | manager@example.com |
    And the following "role assigns" exist:
      | user    | role           | contextlevel | reference |
      | manager | certificatemanagerall | System       |           |
    And the following users allocations to tenants exist:
      | user | tenant |
      | manager | Tenant 2 |
    And I log in as "manager"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I press "Create template"
    And I set the following fields to these values:
      | Name | Certificate 1 |
      | Select tenant | Tenant 2 |
    And I press "Save changes"
    And I follow "Manage certificate templates"
    Then I should see "Certificate 1"

  Scenario: Adding a template without manageforalltenants capability
    When the following "users" exist:
      | username | firstname | lastname | email           |
      | manager  | Manager | 1 | manager@example.com |
    And the following "role assigns" exist:
      | user    | role           | contextlevel | reference |
      | manager | certificatemanager | System       |           |
    And I log in as "manager"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I press "Create template"
    And I set the following fields to these values:
      | Name | Certificate 1 |
    And I press "Save changes"
    And I follow "Manage certificate templates"
    Then I should see "Certificate 1"

  Scenario: Adding template with name too long
    When I log in as "admin"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I press "Create template"
    And I set the following fields to these values:
      | Name | Certificate 1234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456 |
    And I press "Save changes"
    Then I should see "You have exceeded the maximum length allowed for the name"

  Scenario: Deleting a site template
    When the following certificate templates exist:
      | name |
      | Certificate 1 |
    And I log in as "admin"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on ".delete-icon" "css_element" in the "Certificate 1" "table_row"
    And I press "Cancel"
    And I should see "Certificate 1"
    And I click on "Delete" "link" in the "Certificate 1" "table_row"
    And I press "Continue"
    Then I should not see "Certificate 1"

  Scenario: Duplicating a site template from the same tenant without manageforalltenants
    When the following "users" exist:
      | username | firstname | lastname | email           |
      | manager  | Manager | 1 | manager@example.com |
    And the following "role assigns" exist:
      | user    | role           | contextlevel | reference |
      | manager | certificatemanager | System       |           |
    And the following certificate templates exist:
      | name |
      | Certificate 1 |
    And I log in as "manager"
    When I navigate to "Certificates > Manage certificate templates" in site administration
    And "Edit" "link" should exist in the "Certificate 1" "table_row"
    And I click on "Duplicate" "link" in the "Certificate 1" "table_row"
    And I press "Cancel"
    And I should see "Certificate 1"
    And I should not see "Certificate 1 (duplicate)"
    And I click on "Duplicate" "link" in the "Certificate 1" "table_row"
    And I press "Continue"
    Then I should see "Certificate 1"
    And I should see "Certificate 1 (duplicate)"
    And I log out

  Scenario: Duplicating a shared site template without manageforalltenants
    When the following "users" exist:
      | username | firstname | lastname | email           |
      | manager  | Manager | 1 | manager@example.com |
    And the following "role assigns" exist:
      | user    | role               | contextlevel | reference |
      | manager | certificatemanager | System       |           |
    And the following users allocations to tenants exist:
      | user     | tenant   |
      | manager  | Tenant 1 |
    And the following certificate templates exist:
      | name          | tenant   |
      | Certificate 0 |          |
      | Certificate 1 | Tenant 1 |
      | Certificate 2 | Tenant 2 |
    And I log in as "manager"
    When I navigate to "Certificates > Manage certificate templates" in site administration
    And "Edit" "link" should exist in the "Certificate 1" "table_row"
    And I should not see "Certificate 2"
    And "Edit" "link" should not exist in the "Certificate 0" "table_row"
    And I click on "Duplicate" "link" in the "Certificate 0" "table_row"
    And I press "Continue"
    Then I should see "Certificate 0"
    And I should see "Certificate 0 (duplicate)"
    And I log out
    # Now make sure the duplicate was created for Tenant 1.
    And I log in as "admin"
    When I navigate to "Certificates > Manage certificate templates" in site administration
    And "Tenant 1" "text" should exist in the "Certificate 0 (duplicate)" "table_row"
    And I log out

  Scenario: Duplicating a site template to another tenant
    When the following "users" exist:
      | username | firstname | lastname | email           |
      | manager  | Manager | 1 | manager@example.com |
    And the following "role assigns" exist:
      | user    | role           | contextlevel | reference |
      | manager | certificatemanagerall | System       |           |
    And the following certificate templates exist:
      | name |
      | Certificate 1 |
    And I log in as "manager"
    When I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on "Duplicate" "link" in the "Certificate 1" "table_row"
    And I press "Cancel"
    And I should see "Certificate 1"
    And I should not see "Certificate 1 (duplicate)"
    And I click on "Duplicate" "link" in the "Certificate 1" "table_row"
    And I set the following fields to these values:
      |  Select tenant | Tenant 2 |
    And I press "Select"
    # TODO remove (no need in extra confirmation):
    And I press "Continue"
    Then I should see "Certificate 1"
    And I should see "Certificate 1 (duplicate)"
