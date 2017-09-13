<?php

namespace SlackSongShare\Command;

use Noodlehaus\Config;
use \PDO;
use SlackSongShare\Helper\SpotifyApiHelper;

class UpdateCommand
{
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function getNewTracks()
    {
        $dbConfig = $this->config->get('database');
        $dsn = "mysql:host=".$dbConfig['host'].";dbname=".$dbConfig['name'].";charset=".$dbConfig['charset'];
        $db = new PDO($dsn, $dbConfig['user'], $dbConfig['password']);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $spotify = $this->config->get('spotify');
        $api = (new SpotifyApiHelper(
            $db,
            $spotify['client_id'],
            $spotify['client_secret'],
            $spotify['redirect_URI']
        ))->getApiWrapper();

        $playlistTracks = $api->getUserPlaylistTracks($spotify['user_id'], $spotify['playlist_id']);

        $trackStatement = $db->prepare(
            'INSERT INTO tracks(id, name, added_by)
            VALUES(:id, :name, :added_by)
            ON DUPLICATE KEY UPDATE
            name= :name,
            added_by = :added_by'
        );



        foreach ($playlistTracks->items as $track) {
            $trackStatement->execute([
                'id' => $track->track->id,
                'name' => $track->track->name,
                'added_by' => $track->added_by->id
            ]);
        }
        // TODO: Get songs

        // TODO: Save songs to DB

        // TODO: See if anyone is new.
    }

    public function run()
    {
        $this->getNewTracks();
    }
}
