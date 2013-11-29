<?php

namespace OAuth2Demo\Cron\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Guzzle\Http\Client as GuzzleHttpClient;

class CollectEggsCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('eggs:collect')
            ->setDescription('Collect eggs for the specified client')
            ->addArgument(
                'client_id',
                InputArgument::REQUIRED,
                'Your Client ID'
            )
            ->addArgument(
                'client_secret',
                InputArgument::REQUIRED,
                'Your Client Secret'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // define our base parameters
        $endpoint = 'localhost:9000';

        // create our http client
        $http = new GuzzleHttpClient();

        /* 1. Get the Access Token */

        // get the parameters from the command input
        $parameters = array(
            'client_id'     => $input->getArgument('client_id'),
            'client_secret' => $input->getArgument('client_secret'),
            'grant_type'    => 'client_credentials',
        );

        // get the token url from parameters.json
        $token_url = sprintf('http://%s/token', $endpoint);

        // make a request to the token url
        $response = $http->post($token_url, null, $parameters)->send();
        $token = json_decode((string) $response->getBody(), true);

        $output->writeln('Received access token: '.$token['access_token']);

        /* 2. Call the APIs with the Access Token */

        // create OAuth2 Authorization header using the Access Token
        $headers = array('Authorization' => sprintf('Bearer %s', $token['access_token']));

        // get the resource url from parameters.json
        $resource_url = sprintf('http://%s/api/eggs-collect', $endpoint);

        // make the request
        $response = $http->post($resource_url, $headers)->send();
        $api_response = json_decode((string) $response->getBody(), true);

        $output->writeln(sprintf('<info>%s</info>', $api_response['message']));
    }
}
