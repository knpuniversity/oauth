<?php

use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;
use Silex\Application;
use Behat\MinkExtension\Context\MinkContext;

use Behat\Behat\Context\Step\Given;
use Behat\Behat\Context\Step\When;
use OAuth2Demo\Server\Storage\FixturesManager;

//
// Require 3rd-party libraries here:
//
require_once __DIR__.'/../../vendor/phpunit/phpunit/PHPUnit/Autoload.php';
require_once __DIR__.'/../../vendor/phpunit/phpunit/PHPUnit/Framework/Assert/Functions.php';

/**
 * Features context.
 */
class FeatureContext extends MinkContext
{
    /**
     * @var Application
     */
    private static $app;

    /**
     * Initializes context.
     * Every scenario gets its own context object.
     *
     * @param array $parameters context parameters (set them up through behat.yml)
     */
    public function __construct(array $parameters)
    {
        // Initialize your context here
    }

    /**
     * Deletes the database between each scenario, which causes the tables
     * to be re-created and populated with basic fixtures
     *
     * @BeforeScenario
     */
    public function reloadDatabase()
    {
        /** @var FixturesManager $fixturesManager */
        $fixturesManager = self::$app['fixtures_manager'];

        $fixturesManager->clearTables();
        $fixturesManager->populateSqliteDb();
    }

    /**
     * @BeforeSuite
     */
    public static function bootstrapApp()
    {
        self::$app = require __DIR__.'/../../bootstrap.php';
    }

    /**
     * @Given /^I click "([^"]*)"$/
     */
    public function iClick($linkName)
    {
        return new Given(sprintf('I follow "%s"', $linkName));
    }

    /**
     * @Then /^I should see the following scopes listed:$/
     *
     * Verify that certain scopes are listed on the app view page
     */
    public function iShouldSeeTheFollowingScopesListed(TableNode $table)
    {
        $tbl = $this->getSession()->getPage()->find('css', '.app-details-table');
        assertNotNull($tbl, 'Cannot find the app details table!');

        $ul = $tbl->find('css', 'ul.app-details-scopes');
        assertNotNull($ul, 'Cannot find the scopes ul!');

        $lis = $ul->findAll('css', 'li');
        $actualScopes = array();
        foreach ($lis as $li) {
            $actualScopes[] = trim($li->getText());
        }

        // get the expected rows - just the first column of each table
        $expectedScopes = array();
        foreach ($table->getRows() as $row) {
            $expectedScopes[] = $row[0];
        }

        assertEquals($expectedScopes, $actualScopes);
    }

    /**
     * @Given /^there is a user "([^"]*)" with password "([^"]*)"$/
     */
    public function thereIsAUserWithPassword($email, $plainPassword)
    {
        $this->createUser($email, $plainPassword);
    }

    /**
     * @Given /^I am logged in$/
     */
    public function iAmLoggedIn()
    {
        $this->createUser('ryan@knplabs.com', 'foo');

        return array(
            new Given('I am on "/login"'),
            new Given('I fill in "Email" with "ryan@knplabs.com"'),
            new Given('I fill in "Password" with "foo"'),
            new Given('I press "Login!"'),
        );
    }

    private function createUser($email, $plainPassword)
    {
        /** @var \OAuth2Demo\Server\Storage\Pdo $storage */
        $storage = self::$app['storage'];

        return $storage->setUser($email, $plainPassword, 'John'.rand(1, 999), 'Doe'.rand(1, 999));
    }
}
