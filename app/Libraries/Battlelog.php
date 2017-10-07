<?php

namespace App\Libraries;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log as Log;

class Battlelog
{
    //40 second timeout
    public static $timeout = 40.0;

    /**
     * Use a GET request to grab battlelog data
     * @param $url url to get battlelog json from
     * @return bool|object
     */
    public static function battlelogGet($url, $log = true, $referer = "")
    {
        try {
            $client = new GuzzleClient([
                'timeout' => self::$timeout,
                'headers' => [
                    'X-AjaxNavigation' => '1',
                    'Accept-Encoding' => 'gzip',
                ],
                'decode_content' => 'gzip'
            ]);
            $response = $client->request('GET', 'http://battlelog.battlefield.com/' . $url);
            return json_decode($response->getBody()->getContents());
        } catch (RequestException $e) {
            return false;
        }
    }

    /**
     * Use a POST request to grab battlelog data
     * @param string $url url to get battlelog json from
     * @param array $params POST parameters
     * @return bool|object
     */
    public static function battlelogPost($url, $params)
    {
        try {
            $client = new GuzzleClient([
                'timeout' => self::$timeout,
                'headers' => [
                    'X-AjaxNavigation' => '1',
                    'Accept-Encoding' => 'gzip'
                ],
                'decode_content' => 'gzip'
            ]);
            $response = $client->request('POST', 'http://battlelog.battlefield.com/' . $url, $params);
            return json_decode($response->getBody()->getContents());
        } catch (RequestException $e) {
            return false;
        }
    }


    /**
     * Get a list of players with associated persona ids from a name
     * @param $name The players name
     * @return array|bool
     */
    public static function searchPlayer($name)
    {
        $args = array(
            'form_params' => [
                'query' => $name,
                'post-check-sum' => '5c207fbde0'
            ],
            'headers' => ['Accept-Encoding' => 'gzip'],
            'decode_content' => true
        );
        $content = Battlelog::battlelogPost('bf4/search/query/', $args);
        if (!$content) {
            return false;
        }
        //filter players for only pc players
        $players = array_filter($content->data, function ($player) {
            if ($player->namespace == "cem_ea_id") {
                return true;
            }
            return false;
        });
        if (!count($players)) {
            return false;
        }
        return $players;
    }

    /**
     * Get a players general stats from their persona id
     * @param int $pid
     * @return bool|mixed
     */
    public static function getPersona($pid)
    {
        $content = Battlelog::battlelogGet('bf4/soldier/nope/stats/' . $pid . '/pc/');
        return $content;
    }

    /**
     * Get a players persona id from their battlelog id
     * @param int $bid The players Battlelog Id
     * @return bool|int
     */
    public static function getPlayerByBid($bid)
    {
        $content = Battlelog::battlelogGet('bf4/user/overviewBoxStats/' . $bid . '/');
        if (!isset($content->type) || $content->type == null  || $content->type != 'success') {
            return false;
        }
        if (isset($content->data->soldiersBox[0])) {
            return $content->data->soldiersBox[0]->persona->personaId;
        }
    }

    /**
     * gets the persona id of the player by name
     * @param int $name
     * @return bool|mixed
     */
    public static function getUser($name)
    {
        $players = Battlelog::searchPlayer($name);
        if (!$players) {
            return false;
        }
        foreach ($players as $player) {
            if ($player->personaName == $name && $player->namespace == "cem_ea_id") {
                return $player;
            }
        }
        $content = Battlelog::battlelogGet('bf4/user/' . $name);
        return $content;
    }

    /**
     * Grab a specific battlereport for a player or a generic battlereport
     * @param int $battleReportId ID of the battlereport to fetch
     * @param int $pid Persona ID of the player to fetch
     * @return bool|mixed
     */
    public static function getBattlereport($battleReportId, $pid = 0)
    {
        $content = Battlelog::battlelogGet('bf4/battlereport/loadgeneralreport/' . $battleReportId . '/1/' . $pid . '/');
        if ($content == null) {
            return false;
        }
        return $content;
    }

    /**
     * Get a list of previous battlereports from a player
     * @param int $pid Persona ID of the player to get the reports for
     * @param int $lastReport timestamp of the nth * 10 last report
     * @return bool|mixed
     */
    public static function getMoreBattlereportList($pid, $lastReport)
    {
        $content = Battlelog::battlelogGet('bf4/warsawbattlereportspopulatemore/' . $pid . '/2048/1/' . $lastReport . '/');
        if ($content->type != 'success' || !isset($content->data->gameReports)) {
            return false;
        }
        return $content;
    }

    /**
     *
     * @param int $pid Persona ID of the player to get a list of battlereports for
     * @return bool|mixed
     */
    public static function getBattlereportList($pid)
    {
        $content = Battlelog::battlelogGet('bf4/warsawbattlereportspopulate/' . $pid . '/2048/1/');
        if (!isset($content->type) || $content->type != 'success' || !isset($content->data->gameReports)) {
            return false;
        }
        return $content;
    }

}
