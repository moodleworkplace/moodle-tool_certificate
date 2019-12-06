@tool @tool_certificate @moodleworkplace @javascript
Feature: Check datasources for certificate
  In order to check datasources
  As a manager
  I need be able to create reports with them

  Background:
    Given "2" tenants exist with "3" users and "0" courses in each
    And the following certificate templates exist:
      | name          | category   |
      | Certificate 1 | Category1 |
      | Certificate 2 | Category2 |
    And the following certificate issues exist:
      | template      | user   |
      | Certificate 1 | user11 |
      | Certificate 1 | user12 |
      | Certificate 2 | user21 |

  Scenario: Create a report about certificates templates
    Given the following custom reports exist:
      | name    | tenant  | source                                                       |
      | Report1 | Tenant1 | tool_certificate\tool_reportbuilder\datasources\certificates |
      | Report2 | Tenant2 | tool_certificate\tool_reportbuilder\datasources\certificates |
    When I log in as "tenantadmin1"
    And I navigate to "Report builder" in workplace launcher
    And I click on "Edit content" "link" in the "Report1" "table_row"
    Then I should see "Certificate"
    And I should see "Certificate template" in the "#entity_tool_certificate_template" "css_element"
    And I should see "Number of pages" in the "#entity_tool_certificate_template" "css_element"
    And I should see "Time created" in the "#entity_tool_certificate_template" "css_element"
    And I should see "Certificate: Certificate template"
    And I should see "Certificate: Time created"
    And I should see "Certificate 1"
    And I should not see "Certificate 2"
    And I log out
    When I log in as "tenantadmin2"
    And I navigate to "Report builder" in workplace launcher
    And I click on "Edit content" "link" in the "Report2" "table_row"
    And I should see "Certificate 2"
    And I should not see "Certificate 1"

  Scenario: Create a report about certificates issues
    Given the following custom reports exist:
      | name    | tenant  | source                                                 |
      | Report1 | Tenant1 | tool_certificate\tool_reportbuilder\datasources\issues |
    When I log in as "tenantadmin1"
    And I navigate to "Report builder" in workplace launcher
    And I click on "Edit content" "link" in the "Report1" "table_row"
    Then I should see "Certificate issue"
    And I should see "Code" in the "#entity_tool_certificate_issue" "css_element"
    And I should see "Time created" in the "#entity_tool_certificate_issue" "css_element"
    And I should see "Expires on" in the "#entity_tool_certificate_issue" "css_element"
    And I should see "Certificate: Certificate template"
    And I should see "User: Full name with profile link"
    And I should see "Certificate issue: Time created"
    And I should see "Certificate issue: Expires on"
    And I should see "Certificate issue: Code"
    And I should see "Certificate 1"
    And I should not see "Certificate 2"
    And I should see "User 11"
    And I should see "User 12"
    And I should not see "User 21"
    And I click on "Show/hide filters sidebar" "button"
    And I set the field "addconditonselect" to "Expires on"
    And I set the field "tool_certificate_issue:expires_op" to "Is not empty"
    And I should not see "User 11"
    And I should not see "User 12"
