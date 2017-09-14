<?php

namespace SlackSongShare\Action;

use Maknz\Slack\Client;

class ShareAction
{
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function perform()
    {
        $client = new Client($this->config->get('slack')['hook_url']);

        $client->send('Hello world!');
    }
}
