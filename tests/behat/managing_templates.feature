@tool @tool_certificate @moodleworkplace @javascript
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
    And I wait "2" seconds
    And I follow "New template"
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
    And I follow "New template"
    And I set the following fields to these values:
      | Name | Certificate 1 |
      | Select tenant | Tenant 2 |
    And I press "Save" in the modal form dialogue
    And I follow "Manage certificate templates"
    Then I should see "Certificate 1"

  @javascript
  Scenario: Adding a template without manageforalltenants capability
    When the following "users" exist:
      | username | firstname | lastname | email           |
      | manager  | Manager | 1 | manager@example.com |
    And the following "role assigns" exist:
      | user    | role           | contextlevel | reference |
      | manager | certificatemanager | System       |           |
    And I log in as "manager"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I follow "New template"
    And I set the following fields to these values:
      | Name | Certificate 1 |
    And I press "Save" in the modal form dialogue
    And I follow "Manage certificate templates"
    Then I should see "Certificate 1"

  @javascript
  Scenario: Adding template with invalid width, heigth and margins
    When I log in as "admin"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I follow "New template"
    And I set the following fields to these values:
      | Name | Certificate 1 |
      | Page width   | 0  |
      | Page height  | 0  |
      | Left margin  | -1 |
      | Right margin | -1 |
    And I press "Save" in the modal form dialogue
    Then I should see "The width has to be a valid number greater than 0."
    Then I should see "The height has to be a valid number greater than 0."
    Then I should see "The margin has to be a valid number greater than 0."

  Scenario: Edit details of existing template
    When the following certificate templates exist:
      | name | numberofpages |
      | Certificate 1 | 1    |
    And I log in as "admin"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I follow "Certificate 1"
    And I press "Edit details"
    And I set the field "Name" to "Certificate 2"
    And I press "Save" in the modal form dialogue
    And I should not see "Certificate 1"
    And I should see "Certificate 2"
    And I log out

  Scenario: Deleting a site template
    When the following certificate templates exist:
      | name | numberofpages |
      | Certificate 1 | 1    |
    And I log in as "admin"
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

  Scenario: Duplicating a site template from the same tenant without manageforalltenants
    When the following "users" exist:
      | username | firstname | lastname | email           |
      | manager  | Manager | 1 | manager@example.com |
    And the following "role assigns" exist:
      | user    | role           | contextlevel | reference |
      | manager | certificatemanager | System       |           |
    And the following certificate templates exist:
      | name | numberofpages |
      | Certificate 1 | 1    |
    And I log in as "manager"
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
      | name          | tenant   | numberofpages |
      | Certificate 0 |          | 1             |
      | Certificate 1 | Tenant 1 | 1             |
      | Certificate 2 | Tenant 2 | 1             |
    And I log in as "manager"
    When I navigate to "Certificates > Manage certificate templates" in site administration
    And "Edit content" "link" should exist in the "Certificate 1" "table_row"
    And I should not see "Certificate 2"
    And "Edit content" "link" should not exist in the "Certificate 0" "table_row"
    And I click on "Duplicate" "link" in the "Certificate 0" "table_row"
    And I click on "Duplicate" "button" in the "Confirm" "dialogue"
    Then I should see "Certificate 0"
    And I should see "Certificate 0 (copy)"
    And I log out
    # Now make sure the duplicate was created for Tenant 1.
    And I log in as "admin"
    When I navigate to "Certificates > Manage certificate templates" in site administration
    And "Tenant 1" "text" should exist in the "Certificate 0 (copy)" "table_row"
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
    And I press "Cancel" in the modal form dialogue
    And I should see "Certificate 1"
    And I should not see "Certificate 1 (copy)"
    And I click on "Duplicate" "link" in the "Certificate 1" "table_row"
    And I set the following fields to these values:
      |  Select tenant | Tenant 2 |
    And I press "Duplicate" in the modal form dialogue
    Then I should see "Certificate 1"
    And I should see "Certificate 1 (copy)"
    And I should see "Tenant 2" in the "Certificate 1 (copy)" "table_row"
    And I log out

  Scenario: Edit name of certificate template
    When the following certificate templates exist:
      | name | numberofpages |
      | Certificate 1 | 1    |
    And I log in as "admin"
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
