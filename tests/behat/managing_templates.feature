@tool @tool_certificate
Feature: Being able to manage site templates
  In order to ensure managing site templates works as expected
  As an admin
  I need to manage and load site templates

  Background:
    Given I log in as "admin"

  Scenario: Adding a site template
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
      | manager | manager        | System       |           |
    And I set the following system permissions of "Manager" role:
      | capability | permission |
      | tool/certificate:manageforalltenants | Allow |
    And the following tenants exist:
      | name   |
      | Tenant 1 |
      | Tenant 2 |
    And the following users allocations to tenants exist:
      | user | tenant |
      | manager | Tenant 1 |
      | manager | Tenant 2 |
    And I log out
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
      | manager | manager        | System       |           |
    And I set the following system permissions of "Manager" role:
      | capability | permission |
      | tool/certificate:manage | Allow |
    And I log out
    And I log in as "manager"
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I press "Create template"
    And I set the following fields to these values:
      | Name | Certificate 1 |
    And I press "Save changes"
    And I follow "Manage certificate templates"
    Then I should see "Certificate 1"

  Scenario: Deleting a site template
    When the following certificate templates exist:
      | name |
      | Certificate 1 |
    And I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on ".delete-icon" "css_element" in the "Certificate 1" "table_row"
    And I press "Cancel"
    And I should see "Certificate 1"
    And I click on ".delete-icon" "css_element" in the "Certificate 1" "table_row"
    And I press "Continue"
    Then I should not see "Certificate 1"

  Scenario: Duplicating a site template without manageforalltenants
    When the following "users" exist:
      | username | firstname | lastname | email           |
      | manager  | Manager | 1 | manager@example.com |
    And I set the following system permissions of "Manager" role:
      | capability | permission |
      | tool/certificate:manage | Allow |
    And the following "role assigns" exist:
      | user    | role           | contextlevel | reference |
      | manager | manager        | System       |           |
    And the following certificate templates exist:
      | name |
      | Certificate 1 |
    And I log out
    And I log in as "manager"
    When I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on ".duplicate-icon" "css_element" in the "Certificate 1" "table_row"
    And I press "Cancel"
    And I should see "Certificate 1"
    And I should not see "Certificate 1 (duplicate)"
    And I click on ".duplicate-icon" "css_element" in the "Certificate 1" "table_row"
    And I press "Continue"
    Then I should see "Certificate 1"
    And I should see "Certificate 1 (duplicate)"

  Scenario: Duplicating a site template to another tenant
    When the following "users" exist:
      | username | firstname | lastname | email           |
      | manager  | Manager | 1 | manager@example.com |
    And the following "role assigns" exist:
      | user    | role           | contextlevel | reference |
      | manager | manager        | System       |           |
    And I set the following system permissions of "Manager" role:
      | capability | permission |
      | tool/certificate:manageforalltenants | Allow |
    And the following tenants exist:
      | name   |
      | Tenant 1 |
      | Tenant 2 |
    And the following users allocations to tenants exist:
      | user | tenant |
      | manager | Tenant 1 |
      | manager | Tenant 2 |
    And the following certificate templates exist:
      | name |
      | Certificate 1 |
    And I log out
    And I log in as "manager"
    When I navigate to "Certificates > Manage certificate templates" in site administration
    And I click on ".duplicate-icon" "css_element" in the "Certificate 1" "table_row"
    And I press "Cancel"
    And I should see "Certificate 1"
    And I should not see "Certificate 1 (duplicate)"
    And I click on ".duplicate-icon" "css_element" in the "Certificate 1" "table_row"
    And I set the following fields to these values:
      |  Select tenant | Tenant 2 |
    And I press "Select"
    And I press "Continue"
    Then I should see "Certificate 1"
    And I should see "Certificate 1 (duplicate)"
