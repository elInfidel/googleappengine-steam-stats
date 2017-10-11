<?php
    class Steam
    {
        private $apiKey;
        private $steamID;

        function __construct($steamID) 
        {
            $this->apiKey = getenv('STEAM_API_KEY');

            // We want to convert vanity URLs to their int64 equivalent
            if(!ctype_digit($steamID))
                $this->steamID = $this->getSteamIDFromVanityURL($steamID);
            else
                $this->steamID = $steamID;
        }

        public function getSteamIDFromVanityURL($vanityURL)
        {
            $obj = new stdClass();
            $obj->vanityurl = $vanityURL;
            $params = json_encode($obj);

            $result = $this->requestStandard("https://api.steampowered.com/ISteamUser/ResolveVanityURL/v1/", $params);
            return $result->response->steamid;
        }

        public function getFriendList()
        {
            $obj = new stdClass();
            $obj->steamid = $this->steamID;
            $params = json_encode($obj);

            $result = $this->requestStandard("https://api.steampowered.com/ISteamUser/GetFriendList/v1/", $params);
            return $result->friendslist->friends;
        }

        public function getSteamLevel()
        {
            $obj = new stdClass();
            $obj->steamid = $this->steamID;
            $params = json_encode($obj);

            $result = $this->requestService("https://api.steampowered.com/IPlayerService/GetSteamLevel/v1/", $params);
            return $result->response->player_level;
        }

        public function getPlayerSummary()
        {
            $obj = new stdClass();
            $obj->steamids = $this->steamID;
            $params = json_encode($obj);

            $result = $this->requestStandard("https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v2/", $params);
            return $result->response->players[0];
        }

        public function getPlayerBans()
        {
            $obj = new stdClass();
            $obj->steamid = $this->steamID;
            $params = json_encode($obj);

            $result = $this->requestStandard("https://api.steampowered.com/ISteamUser/GetPlayerBans/v1/?key=", $params);
            return $result->response->bans[0];
        }

        public function getOwnedGames()
        {
            $params = new stdClass();
            $params->steamid = $this->steamID;
            $params->include_appinfo = false;
            $params->include_played_free_games = false;
            $params->appids_filter = null;
            $inputJSON = json_encode($params);

            $result = $this->requestService("https://api.steampowered.com/IPlayerService/GetOwnedGames/v1/", $inputJSON);
            return $result->response;
        }

        // This api call is heavily limited due to issues with slow downs. 
        // 200 calls per 5 mins and only a single app can be requested at a single time.
        // This is unfortunate because it contains most of the interesting info to my cloud app.
        public function getGameInfo($appID)
        {
            // We call web request directly here because the url structure is different to
            // other calls. This API is technically unofficial. It's currently used by
            // Steam Big Picture to access store data.
            $result = $this->webRequest("http://store.steampowered.com/api/appdetails?appids=".$this->appID);
            return $result->response;
        }

        private function requestStandard($url, $params)
        {
            // All web api calls use the key argument
            $finalURL = $url."?"."key=".$this->apiKey."&";

            // Add parameters to call
            $array = json_decode($params, true);
            foreach ($array as $key => $val) 
            {
                $finalURL .= $key."=".$val."&";
            }

            // If we have an & at the end of url we remove. 
            // & indicates the start of the next parameter, There is none so the call may fail.
            if (substr($finalURL, -1, 1) == '&') $finalURL = substr($finalURL, 0, -1);

            // Send the call to steams server and return the result.
            $result = $this->webRequest($finalURL);
            return $result;
        }

        private function requestService($url, $inputJSON)
        {
            // Service calls directly take the json object so we can just append it and send.
            $request = $url."?key=".$this->apiKey."&input_json=".$inputJSON;
            return $this->webRequest($request);
        }

        private function webRequest($url)
        {
            return json_decode(file_get_contents($url));
        }
    }
?>