<?php

namespace SlackSongShare\Action;

use Maknz\Slack\Client;

class ShareAction
{
    private $config;
    private $client;

    public function __construct($config)
    {
        $this->config = $config;
        $this->client = $client = new Client($this->config->get('slack')['hook_url']);
    }

    public function shareTrack($track)
    {
        $this->client->send(sprintf(
            '%s shared a song: %s',
            $track['added_by'],
            $this->config->get('spotify')['share_url'].$track['id']
        ));
    }
}
