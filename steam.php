<?php
    class Steam
    {
        private $steamAuthKey;
        private $steamID;

        function __construct($steamID) 
        {
            $steamAuthKey = getenv('STEAM_AUTH_KEY');
            
            // We want to convert vanity URLs to their int64 equivalent
            if(!ctype_digit($steamID))
                $this->steamID = $this->getSteamIDFromVanityURL($steamID);
            else
                $this->steamID = $steamID;
        }

        public function getSteamIDFromVanityURL($vanityURL)
        {
            $result = $this->webRequest("https://api.steampowered.com/ISteamUser/ResolveVanityURL/v1/?key=".Self::$steamAuthKey."&vanityurl=".$vanityURL);
            return $result->response->steamid;
        }

        public function getFriendList()
        {
            $result = $this->webRequest("https://api.steampowered.com/ISteamUser/GetFriendList/v1/?key=".Self::$steamAuthKey."&steamid=".$this->steamID);
            return $result->friendslist->friends;
        }

        public function getSteamLevel()
        {
            $params = new stdClass();
            $params->steamid = $this->steamID;
            $inputJSON = json_encode($params);

            $result = $this->requestService("https://api.steampowered.com/IPlayerService/GetSteamLevel/v1/", $inputJSON);
            return $result->response->player_level;
        }

        public function getPlayerSummary()
        {
            $result = $this->webRequest("https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v2/?key=".Self::$steamAuthKey."&steamids=".$this->steamID);
            return $result->response->players[0];
        }

        public function getPlayerBans()
        {
            $result = $this->webRequest("https://api.steampowered.com/ISteamUser/GetPlayerBans/v1/?key=".Self::$steamAuthKey."&steamids=".$this->steamID);
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
        // This is unfortunate because it contains the most interest info to my cloud app. 
        public function getGameInfo($appID)
        {
            $result = $this->webRequest("http://store.steampowered.com/api/appdetails?appids=".$this->appID);
            return $result->response;
        }

        private function requestStandard($url, $inputJSON)
        {
            //TODO: Implement
        }

        private function requestService($url, $inputJSON)
        {
            $request = $url."?key=".Self::$steamAuthKey."&input_json=".$inputJSON;
            return $this->webRequest($request);
        }

        private function webRequest($url)
        {
            return json_decode(file_get_contents($url));
        }
    }
?>