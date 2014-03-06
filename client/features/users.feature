Feature: User Management
  In order to use the site as me and save data
  As a user
  I need to be able to register and login

  Scenario: Registration
    When I go to "/"
    And I click "Register"
    And I fill in "Email" with "brent@topcluck.com"
    And I fill in "Password" with "foo"
    And I fill in "First Name" with "Brent"
    And I fill in "Last Name" with "Shaffer"
    And I press "Do it!"
    Then I should see "Logout"

  Scenario: Logging in
    Given there is a user "coolguy@baz.com" with password "bar"
    When I go to "/"
    And I click "Login"
    And I fill in "Email" with "coolguy@baz.com"
    And I fill in "Password" with "bar"
    And I press "Login!"
    And I go to "/"
    Then I should see "Logout"
