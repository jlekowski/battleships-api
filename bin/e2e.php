<?php

/**
 * Simple End-to-End API test
 */

try {
    $apiRequest = new \ApiRequest('http://battleships-api.private/app_dev.php/v1');

    /*
        /games POST
        /games/{id/hash} GET
        /games/{id/hash} PATCH
        /games/{id/hash}/ships POST
        /games/{id/hash}/chats POST
        /games/{id/hash}/shots POST
        /games/{id/hash}/events?gt={id_last_event}
     */

    $data = new \stdClass();
    $data->playerName = 'Player 13';
    $requestDetails = new \RequestDetails('/games', 'POST', $data, 201);
    $response = $apiRequest->call($requestDetails);
    $location = $response->getHeader('Location');
    preg_match('/\/(\d+)$/', $location, $match);
    $gameId = $match[1];
    $gameToken = $response->getHeader('Game-Token');
    $apiRequest->setAuthToken($gameToken);
    echo "Game Id: $gameId\n";
    echo "Game Token: $gameToken\n";

    $requestDetails = new \RequestDetails(sprintf('/games/%s', $gameId), 'GET', null, 200);
    $response = $apiRequest->call($requestDetails);
    echo "Game for player\n";
    $otherToken = $response->getJson()->otherHash;
    print_r($response->getJson());

    $data = new \stdClass();
    $data->playerName = 'Player 132';
    $requestDetails = new \RequestDetails(sprintf('/games/%s', $gameId), 'PATCH', $data, 204);
    $response = $apiRequest->call($requestDetails);
    echo "Game Patched (player name)\n";
    var_dump($response->getJson());

    echo "Game to be Patched (player ships)\n";
    $shipsData = new \stdClass();
    $shipsData->playerShips = ['A1','C2','D2','F2','H2','J2','F5','F6','I6','J6','A7','B7','C7','F7','F8','I9','J9','E10','F10','G10'];
    $requestDetails = new \RequestDetails(sprintf('/games/%s', $gameId), 'PATCH', $shipsData, 204);
    $response = $apiRequest->call($requestDetails);
    echo "Game Patched (player ships)\n";
    var_dump($response->getJson());

    echo "Game to be Patched (chat)\n";
    $data = new \stdClass();
    $data->type = 'chat';
    $data->value = 'Test chat';
    $requestDetails = new \RequestDetails(sprintf('/games/%s/events', $gameId), 'POST', $data, 201);
    $response = $apiRequest->call($requestDetails);
    echo "Chat added\n";
    print_r($response->getJson());


    $requestDetails = new \RequestDetails(sprintf('/games/%s', $gameId), 'GET', null, 200);
    $apiRequest->setAuthToken($otherToken);
    $response = $apiRequest->call($requestDetails);
    echo "Game for other\n";
    print_r($response->getJson());

    echo "Game to be Patched (other ships)\n";
    $shipsData = new \stdClass();
    $shipsData->playerShips = ['A10','C2','D2','F2','H2','J2','F5','F6','I6','J6','A7','B7','C7','F7','F8','I9','J9','E10','F10','G10'];
    $requestDetails = new \RequestDetails(sprintf('/games/%s', $gameId), 'PATCH', $shipsData, 204);
    $response = $apiRequest->call($requestDetails);
    echo "Game Patched (other ships)\n";
    var_dump($response->getJson());

    $data = new \stdClass();
    $data->type = 'start_game';
    $requestDetails = new \RequestDetails(sprintf('/games/%s/events', $gameId), 'POST', $data, 201);
    $response = $apiRequest->call($requestDetails);
    echo "Game started\n";
    var_dump($response->getJson());


    $data = new \stdClass();
    $data->type = 'shot';
    $data->value = 'A10';
    $requestDetails = new \RequestDetails(sprintf('/games/%s/events', $gameId), 'POST', $data, 201);
    $apiRequest->setAuthToken($gameToken);
    $response = $apiRequest->call($requestDetails);
    echo "Shot added\n";
    print_r($response->getJson());

    $requestDetails = new \RequestDetails(sprintf('/games/%s/events?gt=0', $gameId), 'GET', null, 200);
    $response = $apiRequest->call($requestDetails);
    print_r($response->getJson());

    $requestDetails = new \RequestDetails(sprintf('/games/%s/events?gt=0&type=shot', $gameId), 'GET', null, 200);
    $response = $apiRequest->call($requestDetails);
    print_r($response->getJson());

    $requestDetails = new \RequestDetails(sprintf('/games/%s', $gameId), 'GET', null, 200);
    $response = $apiRequest->call($requestDetails);
    echo "Game for player\n";
    print_r($response->getJson());

    exit;


    // initiate game
//    $game = $apiRequest->initGame();
//    // get game
//    $apiRequest->getGame($game);
//    // update name
//    $apiRequest->updateName($game);
//    // add ships
//    $apiRequest->addShips($game);
//    // add chats
//    $apiRequest->addChats($game);
//    // get updates
//    $apiRequest->getUpdates($game);
//    // get other game
//    $otherGame = $apiRequest->getOtherGame($game);
//    // add other ships
//    $apiRequest->addShips($otherGame);
//    // add shot
//    $apiRequest->addShots($game);
//    // get other updates
//    $apiRequest->getOtherUpdates($otherGame);
//    // get incorrect game
//    $game->playerHash .= 'a';
//    $apiRequest->getGame($game, true);

} catch (\Exception $e) {
    if (isset($game)) {
        print_r($game);
    }
    printf("ERROR: %s (type: %s)\n", $e->getMessage(), get_class($e));
    exit;
}

exit("OK\n");

class ApiRequest
{
    private $baseUrl;
    private $ch;
    private $authToken;

    public function __construct($baseUrl)
    {
        $this->baseUrl = $baseUrl;
        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->ch, CURLOPT_HEADER, 1);
    }

    public function __destruct()
    {
        curl_close($this->ch);
    }

    public function initGame()
    {
        $nameData = new stdClass();
        $nameData->name = "New Test Player";
        $oRequestDetails = new RequestDetails("/games", "POST", $nameData, 201);
        $game = $this->call($oRequestDetails);
        $this->validateGame($game);

        return $game;
    }

    private function validateGame(stdClass $game)
    {
        if (empty($game->playerHash)) {
            throw new E2eException("No player hash");
        }

        if (empty($game->otherHash)) {
            throw new E2eException("No other hash");
        }

        if (empty($game->playerName)) {
            throw new E2eException("No player name");
        }

        if (empty($game->otherName)) {
            throw new E2eException("No other name");
        }

        if ($game->playerNumber !== 1) {
            throw new E2eException("Incorrect player number: " . $game->playerNumber);
        }

        if ($game->otherNumber !== 2) {
            throw new E2eException("Incorrect other number: " . $game->otherNumber);
        }

        if ($game->playerStarted !== false) {
            throw new E2eException("Incorrect player started: " . $game->playerStarted);
        }

        if ($game->lastIdEvents !== 0) {
            throw new E2eException("Incorrect last id event: " . $game->lastIdEvents);
        }

        if ($game->whoseTurn !== 1) {
            throw new E2eException("Incorrect whose turn: " . $game->whoseTurn);
        }
    }

    public function getGame(stdClass &$game, $withError = false)
    {
        $oRequestDetails = new RequestDetails("/games/" . $game->playerHash, "GET", null, ($withError ? 404 : 200));
        $gameData = $this->call($oRequestDetails);
        if (!$withError) {
            $this->validateGameDetails($gameData, $game);
            $game = $gameData;
        }
    }

    private function validateGameDetails(stdClass $gameData, stdClass $game)
    {
        foreach ($game as $key => $value) {
            if (!isset($gameData->$key) || $gameData->$key !== $value) {
                throw new E2eException("Incorrect property value: (" . $key . ": " . $value . " - " . $gameData->$key . ")");
            }
        }

        if ($gameData->playerShips !== array()) {
            throw new E2eException("Incorrect player ships: " . print_r($gameData->playerShips, true));
        }

        if (isset($gameData->otherShips)) {
            throw new E2eException("Other 2 ships should not be set: " . print_r($gameData->otherShips, true));
        }

        if ($gameData->otherJoined !== false) {
            throw new E2eException("Incorrect other joined: " . $gameData->otherJoined);
        }

        if ($gameData->playerStarted !== false) {
            throw new E2eException("Incorrect player started: " . $gameData->playerStarted);
        }

        if ($gameData->otherStarted !== false) {
            throw new E2eException("Incorrect other started: " . $gameData->otherStarted);
        }

        $emptyBattle = new stdClass();
        $emptyBattle->playerGround = new stdClass();
        $emptyBattle->otherGround = new stdClass();
        if ($gameData->battle != $emptyBattle) {
            throw new E2eException("Incorrect battle: " . print_r($gameData->battle, true));
        }

        if ($gameData->chats !== array()) {
            throw new E2eException("Incorrect chats: " . print_r($gameData->chats, true));
        }
    }

    public function getOtherGame(stdClass $game)
    {
        $oRequestDetails = new RequestDetails("/games/" . $game->otherHash, "GET");
        $gameData = $this->call($oRequestDetails);
        $this->validateOtherGameDetails($gameData, $game);

        return $gameData;
    }

    private function validateOtherGameDetails(stdClass $gameData, stdClass $game)
    {
        if ($gameData->playerName !== $game->otherName) {
            throw new E2eException(sprintf("Incorrect player 2 name: %s instead of %s", $gameData->playerName, $game->otherName));
        }

        if ($gameData->otherName !== $game->playerName) {
            throw new E2eException(sprintf("Incorrect other 2 name: %s instead of %s", $gameData->otherName, $game->playerName));
        }

        if ($gameData->playerHash !== $game->otherHash) {
            throw new E2eException(sprintf("Incorrect player 2 hash: %s instead of %s", $gameData->playerHash, $game->otherHash));
        }

        if ($gameData->otherHash !== "") {
            throw new E2eException(sprintf("Other 2 hash should be empty: %s instead", $gameData->otherHash));
        }

        if ($gameData->playerNumber !== 2) {
            throw new E2eException(sprintf("Incorrect player 2 number: %s instead of 2", $gameData->playerNumber));
        }

        if ($gameData->otherNumber !== 1) {
            throw new E2eException(sprintf("Incorrect other 2 number: %s instead of 1", $gameData->otherNumber));
        }

        if ($gameData->whoseTurn !== 1) {
            throw new E2eException(sprintf("Incorrect player 2 turn: %s instead of 1", $gameData->whoseTurn));
        }

        if ($gameData->playerShips !== array()) {
            throw new E2eException("Incorrect player 2 ships: " . print_r($gameData->playerShips, true));
        }

        if (isset($gameData->otherShips)) {
            throw new E2eException("Other 2 ships should not be set: " . print_r($gameData->otherShips, true));
        }

        if ($gameData->otherJoined !== true) {
            throw new E2eException("Incorrect other 2 joined: " . $gameData->otherJoined);
        }

        if ($gameData->otherStarted !== true) {
            throw new E2eException("Incorrect other 2 started: " . $gameData->otherStarted);
        }

        $emptyBattle = new stdClass();
        $emptyBattle->playerGround = new stdClass();
        $emptyBattle->otherGround = new stdClass();
        if ($gameData->battle != $emptyBattle) {
            throw new E2eException("Incorrect 2 battle: " . print_r($gameData->battle, true));
        }

        if (count($gameData->chats) !== 1) {
            throw new E2eException("Incorrect 2 number of chats: " . print_r(array($gameData->chats, $game->chats), true));
        }

        if (($gameData->chats[0]->player !== $game->chats[0]->player) || ($gameData->chats[0]->text !== $game->chats[0]->text)) {
            throw new E2eException("Incorrect 2 chats: " . print_r(array($gameData->chats, $game->chats), true));
        }
    }

    public function updateName(stdClass $game)
    {
        $nameData = new stdClass();
        $nameData->name = "Updated Name";
        $oRequestDetails = new RequestDetails("/games/" . $game->playerHash, "PUT", $nameData);
        $result = $this->call($oRequestDetails);
        $this->validateNullResult($result, __FUNCTION__);
        $game->playerName = $nameData->name;

        return $result;
    }

    public function addShips(stdClass $game)
    {
        $shipsData = new stdClass();
        $shipsData->ships = array("A1","C2","D2","F2","H2","J2","F5","F6","I6","J6","A7","B7","C7","F7","F8","I9","J9","E10","F10","G10");
        $oRequestDetails = new RequestDetails("/games/" . $game->playerHash . "/ships", "POST", $shipsData, 201);
        $result = $this->call($oRequestDetails);
        $this->validateNullResult($result, __FUNCTION__);
        $game->playerShips = $shipsData->ships;

        return $result;
    }

    public function addChats(stdClass $game)
    {
        $chatData = new stdClass();
        $chatData->text = "Test chat";
        $oRequestDetails = new RequestDetails("/games/" . $game->playerHash . "/chats", "POST", $chatData, 201);
        $result = $this->call($oRequestDetails);
        $this->validateTimestamp($result->timestamp);
        $chatData->player = $game->playerNumber;
        $chatData->timestamp = $result;
        $game->chats[] = $chatData;

        return $result;
    }

    public function addShots(stdClass $game)
    {
        $shotData = new stdClass();
        $shots = array('A1' => "sunk", 'C2' => "hit", 'D2' => "sunk", 'J10' => "miss");

        foreach ($shots as $shot => $expectedResult) {
            $shotData->shot = $shot;
            $oRequestDetails = new RequestDetails("/games/" . $game->playerHash . "/shots", "POST", $shotData, 201);
            $result = $this->call($oRequestDetails);
            $this->validateAddShots($result->shotResult, $expectedResult);
        }
    }

    private function validateAddShots($shotResult, $expected)
    {
        if ($shotResult !== $expected) {
            throw new E2eException(sprintf("Incorrect shot result: %s instead of %s", $shotResult, $expected));
        }
    }

    public function validateTimestamp($timestamp)
    {
        if (!preg_match("/^\d{10}$/", $timestamp)) {
            throw new E2eException("Incorrect chat timestamp: " . $timestamp);
        }
    }

    public function getUpdates(stdClass $game)
    {
        $oRequestDetails = new RequestDetails("/games/" . $game->playerHash . "/updates/" . $game->lastIdEvents, "GET");
        $result = $this->call($oRequestDetails);
        $this->validateEmptyArray($result);
    }

    public function getOtherUpdates(stdClass $game)
    {
        $oRequestDetails = new RequestDetails("/games/" . $game->playerHash . "/updates/" . $game->lastIdEvents, "GET");
        $result = $this->call($oRequestDetails);
        $this->validateOtherGetUpdates($result, $game);
    }

    private function validateOtherGetUpdates(stdClass $result, stdClass $game)
    {
        if ($result->shot !== array("A1", "C2", "D2", "J10")) {
            throw new E2eException("Incorrect shot updates: " . print_r($result->shots, true));
        }

        if ($result->lastIdEvents[0] - $game->lastIdEvents !== 6) {
            throw new E2eException(sprintf("Incorrect number of events added: %s - %s", $result->lastIdEvents[0], $game->lastIdEvents));
        }
    }

    private function validateEmptyArray($array)
    {
        if ($array !== array()) {
            throw new E2eException("Incorrect update info: " . print_r($array, true));
        }
    }

    private function validateNullResult($result, $methodName)
    {
        if ($result !== null) {
            throw new E2eException("Incorrect " . $methodName . " response: " . $result);
        }
    }

    public function call(RequestDetails $oRequestDetails)
    {
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, $oRequestDetails->getMethod());
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $oRequestDetails->getData());
        curl_setopt($this->ch, CURLOPT_URL, $this->baseUrl . $oRequestDetails->getRequest());
        $requestHeaders = ['Content-Type: application/json'];
        if ($this->authToken !== null) {
            $requestHeaders[] = sprintf('Authorization: Bearer %s', $this->authToken);
        }
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $requestHeaders);

        $curlResponse = curl_exec($this->ch);
        if ($curlResponse === false) {
            throw new E2eException(curl_error($this->ch));
        }

        $headerSize = curl_getinfo($this->ch, CURLINFO_HEADER_SIZE);
        $header = substr($curlResponse, 0, $headerSize);
        $body = (string)substr($curlResponse, $headerSize);

        $headers = [];
        $headerLines = explode(PHP_EOL, $header);
        foreach ($headerLines as $headerLine) {
            if (preg_match('/^(\S+): (\S+)$/', trim($headerLine), $match)) {
                $headers[$match[1]] = $match[2];
            }
        }


        $curlInfo = curl_getinfo($this->ch);
        $contentType = isset($curlInfo['content_type']) ? $curlInfo['content_type'] : "";
        if ($curlInfo['http_code'] !== 204 && $contentType != "application/json") {
            throw new E2eException(
                sprintf(
                    "Incorrect content type returned: %s (method: %s, path: %s, response: %s)",
                    $contentType,
                    $oRequestDetails->getMethod(),
                    $oRequestDetails->getRequest(),
                    $curlResponse
                )
            );
        }

        $expectedHttpCode = $oRequestDetails->getExpectedHttpCode();
        if ($curlInfo['http_code'] != $expectedHttpCode) {
            throw new E2eException(
                sprintf(
                    "Incorrect http code: %s instead of %s for method %s and path %s (body: %s)",
                    $curlInfo['http_code'],
                    $expectedHttpCode,
                    $oRequestDetails->getMethod(),
                    $oRequestDetails->getRequest(),
                    print_r(json_decode($body), true)
                )
            );
        }

        return new ApiResponse($body, $headers);
    }

    /**
     * @param string $authToken
     */
    public function setAuthToken($authToken)
    {
        $this->authToken = $authToken;
    }
}

class RequestDetails
{
    private $request;
    private $method;
    private $data;
    private $expectedHttpCode;

    public function __construct($request, $method, $data = null, $expectedHttpCode = 200)
    {
        $this->request = $request;
        $this->method = strtoupper($method);
        $this->data = $data;
        $this->expectedHttpCode = $expectedHttpCode;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getData()
    {
        return json_encode($this->data);
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function getExpectedHttpCode()
    {
        return $this->expectedHttpCode;
    }
}

class ApiResponse
{
    /**
     * @var string
     */
    private $body;

    /**
     * @var array
     */
    private $headers;

    /**
     * @param string $body
     * @param array $headers
     */
    public function __construct($body, array $headers)
    {
        $this->body = $body;
        $this->headers = $headers;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param string $header
     * @return string|null
     */
    public function getHeader($header)
    {
        return isset($this->headers[$header]) ? $this->headers[$header] : null;
    }

    /**
     * @return stdClass|mixed
     */
    public function getJson()
    {
        return json_decode($this->body);
    }
}

class E2eException extends Exception {}
