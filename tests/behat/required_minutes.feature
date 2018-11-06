@tool @tool_certificate
Feature: Being able to set the required minutes in a course before viewing the certificate
  In order to ensure the required minutes in a course setting works as expected
  As a teacher
  I need to ensure students can not view a certificate until the required minutes have passed

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "certificate templates" exist:
      | name |
      | Test template 1 |

  Scenario: Check the user can not access the certificate before the required time
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Certificate 1"
    And I should see "You must spend at least a minimum of"
    And I should not see "View certificate"
    And I press "Continue"
    And I should see "Certificate 1"

  Scenario: Check the user can access the certificate after the required time
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I wait "60" seconds
    And I am on "Course 1" course homepage
    And I follow "Certificate 1"
    And I should not see "You must spend at least a minimum of"
    And I should see "View certificate"
