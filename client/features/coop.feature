Feature: Coop Authorization
  In order to be able to have my eggs counted
  As a user
  I need to be able to authorize TopCluck to count my Coop eggs

  @NotImplementedYet
  Scenario: Authorizing
    Given I am logged in
    And I am on "/"
    When I click "Authorize"
    And I log into COOP
    And I click "Yes, I Authorize This Request"
    Then I should see "Your Basket of Eggs"

  @NotImplementedYet
  Scenario: Logging in with Coop
    Given I am on "/"
    And I click "Login"
    And I click "Login with COOP"
    And I log into COOP
    And I click "Yes, I Authorize This Request"
    Then I should see "Your Basket of Eggs"
    # the name of the fixture user, now showing on the leaderboard
    And I should see "Farmer Scott"

  @NotImplementedYet
  Scenario: Updating your egg count
    Given I am authorized with Coop
    And I am on "/"
    When I click "Count Eggs"
    # not a great test, but unless we failed, we're redirected back to the homepage
    Then I should see "Your Basket of Eggs"
