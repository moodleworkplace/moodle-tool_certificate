@tool @tool_certificate
Feature: Being able to verify that a certificate is valid or not
  In order to ensure that a user can verify a certificate is valid
  As a teacher and non-user
  I need to be able to verify a certificate

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "certificate templates" exist:
      | name |
      | Test template 1 |
      | Test template 2 |
    And the following "certificate issues" exist:
      | template | user |
      | Test template 1 | student1 |

  Scenario: Verify a certificate as a teacher
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Certificate 1"
    And I press "View certificate"
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Certificate 1"
    And I navigate to "Verify certificate" in current page administration
    And I set the field "Code" to "NOTAVALIDCODE"
    And I press "Verify"
    And I should see "Not verified"
    And I verify the "Certificate 1" certificate for the user "student1"

  Scenario: Attempt to verify a certificate as a non-user
    And I visit the verification url for the "Certificate 1" certificate
    # User should get redirected to log in as we do not allow non-users to verify.
    And I should see "Remember username"

  Scenario: Verify a certificate as a non-user
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Custom certificate 2"
    And I press "View certificate"
    And I log out
    And I visit the verification url for the "Custom certificate 2" certificate
    And I set the field "Code" to "NOTAVALIDCODE"
    And I press "Verify"
    And I should see "Not verified"
    And I verify the "Custom certificate 2" certificate for the user "student1"

  Scenario: Attempt to verify a certificate as a non-user using the site-wide URL
    And the following config values are set as admin:
      | verifyallcertificates | 0 | customcert |
    And I visit the verification url for the site
    # User should see an error message as we do not allow non-users to verify all certificates on the site.
    And I should see "You do not have the permission to verify all certificates on the site"

  Scenario: Attempt to verify a certificate as a teacher using the site-wide URL
    And the following config values are set as admin:
      | verifyallcertificates | 0 | customcert |
    And I log in as "teacher1"
    And I visit the verification url for the site
    # User should see an error message as we do not allow teachers to verify all certificates on the site.
    And I should see "You do not have the permission to verify all certificates on the site"

  Scenario: Verify a certificate as an admin using the site-wide URL
    And the following config values are set as admin:
      | verifyallcertificates | 0 | customcert |
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Certificate 1"
    And I press "View certificate"
    And I am on "Course 1" course homepage
    And I follow "Custom certificate 2"
    And I press "View certificate"
    And I log out
    And I log in as "admin"
    # The admin (or anyone with the capability 'tool/certificate:verifyallcertificates') can visit the URL regardless of the setting.
    And I visit the verification url for the site
    And I set the field "Code" to "NOTAVALIDCODE"
    And I press "Verify"
    And I should see "Not verified"
    # The admin (or anyone with the capability 'tool/certificate:verifyallcertificates') can verify any certificate regardless of the 'verifyany' setting.
    And I verify the "Certificate 1" certificate for the user "student1"
    And I verify the "Custom certificate 2" certificate for the user "student1"

  Scenario: Verify a certificate as a non-user using the site-wide URL
    And the following config values are set as admin:
      | verifyallcertificates | 1 | customcert |
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Certificate 1"
    And I press "View certificate"
    And I am on "Course 1" course homepage
    And I follow "Custom certificate 2"
    And I press "View certificate"
    And I log out
    And I visit the verification url for the site
    And I set the field "Code" to "NOTAVALIDCODE"
    And I press "Verify"
    And I should see "Not verified"
    And I can not verify the "Certificate 1" certificate for the user "student1"
    And I verify the "Custom certificate 2" certificate for the user "student1"
