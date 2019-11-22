@tool @tool_certificate @moodleworkplace
Feature: Being able to download valid certificates
  In order to ensure that a user can download a valid certificate
  As an admin and non-user
  I need to be able to download a certificate

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following certificate templates exist:
      | name |
      | Certificate 1 |
      | Certificate 2 |
    And the following certificate issues exist:
      | template | user |
      | Certificate 1 | student1 |

  Scenario: Download a certificate as admin
    When I log in as "admin"
    And I visit the sites certificates verification url
    And I verify the "Certificate 1" site certificate for the user "student1"
    And I click on "View certificate" "link"

  Scenario: Verify a certificate as a guest using the site-wide URL
    And the following "permission overrides" exist:
      | capability              | permission | role  | contextlevel | reference |
      | tool/certificate:verify | Allow      | guest | System       |           |
    And I log in as "guest"
    And I visit the sites certificates verification url
    And I verify the "Certificate 1" site certificate for the user "student1"
    And I click on "View certificate" "link"
