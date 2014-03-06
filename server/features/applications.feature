Feature: Applications
  In order to control my client application and scopes
  As a user
  I need to be able to create, edit and view my applications

  Background:
    Given I am logged in

  Scenario: Create an application
    When I go to "/api"
    And I click "Create your Application"
    And I fill in "Application Name" with "Foo app"
    And I check "Unlock the Barn"
    And I check "Feed Your Chickens"
    And I press "Submit"
    Then I should see "Congratulations"
    And I should see the following scopes listed:
      | barn-unlock   |
      | chickens-feed |

  Scenario: Edit an application
    Given an application called "Existing app" exists
    When I go to "/api"
    And I click "Existing app"
    And I click "Edit"
    And I fill in "Redirect URI" with "http://knpuniversity.com"
    And I press "Submit"
    Then the "Redirect URI" value in the table should be "http://knpuniversity.com"

  Scenario: Cannot create an application with the same name
    Given an application called "Existing app2" exists
    When I go to "/api"
    And I click "Create another Application"
    And I fill in "Application Name" with "Existing app2"
    And I press "Submit"
    Then I should see "This application name is already taken"

  Scenario: Only list my applications

  Scenario: Can only view applications I own
