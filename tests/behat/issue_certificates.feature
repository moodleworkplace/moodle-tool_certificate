@tool @tool_certificate
Feature: Being able to manually issue a certificate to a user
  In order to manually issue a new certificate to a user
  As an admin
  I need to be able to issue a certificate from a list of users

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following certificate templates exist:
      | name |
      | Certificate 1 |
    And I log in as "admin"

  @javascript
  Scenario: Verify a certificate as admin, from the list of templates
    When I navigate to "Courses" in site administration
    And I follow "Manage certificate templates"
    And I click on "Issue new certificate from this template" "link"
    And I set the field "Select users to issue certificate for" to "Teacher"
    And I wait until the page is ready
    And I click on "Teacher 1 teacher1@example.com" "link"
    And I press "Issue new certificates" "submit"
    Then I should see "One issue was created"
