<?php

namespace SlackSongShare\Command;

use \PDO;
use Boivie\SpotifyApiHelper\SpotifyApiHelper;
use Noodlehaus\Config;
use SlackSongShare\Action\ShareAction;

class UpdateCommand
{
    private $config;
    private $db;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->setupDB();
    }

    public function getTracks()
    {
        $spotify = $this->config->get('spotify');
        $api = (new SpotifyApiHelper(
            $this->db,
            $spotify['client_id'],
            $spotify['client_secret'],
            $spotify['redirect_URI']
        ))->getApiWrapper();

        $playlistTracks = $api->getUserPlaylistTracks($spotify['user_id'], $spotify['playlist_id']);

        $trackStatement = $this->db->prepare(
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

        // TODO: See if anyone is new.
    }

    public function shareNewTracks()
    {
        $shareAction = new ShareAction($this->config);

        $unsharedTracks = $this->db->query('SELECT id, added_by FROM tracks WHERE shared = 0');

        foreach ($unsharedTracks as $track) {
            $shareAction->shareTrack($track);

            $shareStatement = $this->db->prepare('UPDATE tracks SET shared = 1 WHERE id = :id');

            $shareStatement->execute(['id' => $track['id']]);
        }
    }

    public function run()
    {
        $this->getTracks();

        $this->shareNewTracks();

        return true;
    }

    private function setupDB()
    {
        $dbConfig = $this->config->get('database');
        $dsn = "mysql:host=".$dbConfig['host'].";dbname=".$dbConfig['name'].";charset=".$dbConfig['charset'];
        $this->db = new PDO($dsn, $dbConfig['user'], $dbConfig['password']);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }
}
