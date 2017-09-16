<?php

namespace SlackSongShare\Command;

use \PDO;
use GuzzleHttp;
use Noodlehaus\Config;
use SlackSongShare\Action\ShareAction;
use SpotifyWebApi\SpotifyWebApi;

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
        $api = new SpotifyWebApi();

        $api->setAccessToken($this->getToken());

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

    private function getToken()
    {
        $vault_url = $this->config->get('vault_url');
        $spotify = $this->config->get('spotify');

        $client = new GuzzleHttp\Client();

        $request = $client->request(
            'GET',
            $vault_url.$spotify['client_id'].'/'.$spotify['api_user']
        );

        $response = json_decode($request->getBody(), true);

        if (empty($response['error']) === false) {
            throw new Exception("Error getting token");
        }

        return $response['token'];
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
