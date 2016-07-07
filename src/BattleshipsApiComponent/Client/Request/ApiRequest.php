<?php

namespace BattleshipsApiComponent\Client\Request;

use AppBundle\Http\Headers;
use BattleshipsApiComponent\Client\Exception\E2eException;
use BattleshipsApiComponent\Client\Response\ApiResponse;

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

    /**
     * @param string $name
     * @return \stdClass
     * @throws E2eException
     */
    public function createUser($name)
    {
        $data = new \stdClass();
        $data->name = $name;

        $requestDetails = new RequestDetails('/users', 'POST', $data, 201);
        $response = $this->call($requestDetails);

        $user = new \stdClass();
        $user->name = $name;
        $user->id = $this->getNewId($response);
        $user->apiKey = $response->getHeader(Headers::API_KEY);

        return $user;
    }

    /**
     * @return int
     * @throws E2eException
     */
    public function createGame()
    {
        $data = new \stdClass();

        $requestDetails = new RequestDetails('/games', 'POST', $data, 201);
        $response = $this->call($requestDetails);

        return $this->getNewId($response);
    }

    /**
     * @param int $gameId
     * @return ApiResponse
     * @throws E2eException
     */
    public function getGame($gameId)
    {
        $requestDetails = new RequestDetails(sprintf('/games/%s', $gameId), 'GET', null, 200);
//        $this->validateGameDetails($gameData, $game);

        return $this->call($requestDetails);
    }

    /**
     * @param int $userId
     * @param string $name
     * @return ApiResponse
     * @throws E2eException
     */
    public function updateName($userId, $name)
    {
        $data = new \stdClass();
        $data->name = $name;

        $requestDetails = new RequestDetails(sprintf('/users/%s', $userId), 'PATCH', $data, 204);

        return $this->call($requestDetails);
//        $this->validateNullResult($response, __FUNCTION__);
    }

    /**
     * @param int $userId
     * @return ApiResponse
     * @throws E2eException
     */
    public function getUser($userId)
    {
        $requestDetails = new RequestDetails(sprintf('/users/%s', $userId), 'GET', null, 200);

        return $this->call($requestDetails);
    }

    /**
     * @param int $gameId
     * @param array $ships
     * @param bool $joinGame
     * @return ApiResponse
     * @throws E2eException
     */
    public function updateGame($gameId, array $ships, $joinGame = false)
    {
        $data = new \stdClass();
        if ($ships) {
            $data->playerShips = $ships;
        }
        if ($joinGame) {
            $data->joinGame = true;
        }

        $requestDetails = new RequestDetails(sprintf('/games/%s', $gameId), 'PATCH', $data, 204);

        return $this->call($requestDetails);
    }

    /**
     * @param int $gameId
     * @param string $eventType
     * @param string|int|bool $eventValue
     * @return ApiResponse
     * @throws E2eException
     */
    public function createEvent($gameId, $eventType, $eventValue = true)
    {
        $data = new \stdClass();
        $data->type = $eventType;
        $data->value = $eventValue;

        $requestDetails = new RequestDetails(sprintf('/games/%s/events', $gameId), 'POST', $data, 201);

        return $this->call($requestDetails);
    }

    /**
     * @return ApiResponse
     * @throws E2eException
     */
    public function getGamesAvailable()
    {
        $requestDetails = new RequestDetails('/games?available=true', 'GET', null, 200);

        return $this->call($requestDetails);
    }

    /**
     * @param $gameId
     * @param int $gt
     * @param string $type
     * @return ApiResponse
     * @throws E2eException
     */
    public function getEvents($gameId, $gt = null, $type = null)
    {
        $params = [];
        if ($gt !== null) {
            $params[] = 'gt=' . $gt;
        }
        if ($type !== null) {
            $params[] = 'type=' . $type;
        }
        $paramString = $params ? ('?' . implode('&', $params)) : '';

        $requestDetails = new RequestDetails(sprintf('/games/%s/events%s', $gameId, $paramString), 'GET', null, 200);

        return $this->call($requestDetails);
    }

    /**
     * @param ApiResponse $response
     * @return int
     */
    private function getNewId(ApiResponse $response)
    {
        $location = $response->getHeader('Location');
        preg_match('/\/(\d+)$/', $location, $match);

        return $match[1];
    }

//    public function initGame()
//    {
//        $nameData = new \stdClass();
//        $nameData->name = "New Test Player";
//        $oRequestDetails = new RequestDetails("/games", "POST", $nameData, 201);
//        $game = $this->call($oRequestDetails);
//        $this->validateGame($game);
//
//        return $game;
//    }

    private function validateGame(\stdClass $game)
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

//    public function getGame(stdClass &$game, $withError = false)
//    {
//        $oRequestDetails = new RequestDetails("/games/" . $game->playerHash, "GET", null, ($withError ? 404 : 200));
//        $gameData = $this->call($oRequestDetails);
//        if (!$withError) {
//            $this->validateGameDetails($gameData, $game);
//            $game = $gameData;
//        }
//    }

    private function validateGameDetails(\stdClass $gameData, \stdClass $game)
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

        $emptyBattle = new \stdClass();
        $emptyBattle->playerGround = new \stdClass();
        $emptyBattle->otherGround = new \stdClass();
        if ($gameData->battle != $emptyBattle) {
            throw new E2eException("Incorrect battle: " . print_r($gameData->battle, true));
        }

        if ($gameData->chats !== array()) {
            throw new E2eException("Incorrect chats: " . print_r($gameData->chats, true));
        }
    }

//    public function updateName(stdClass $game)
//    {
//        $nameData = new \stdClass();
//        $nameData->name = "Updated Name";
//        $oRequestDetails = new RequestDetails("/games/" . $game->playerHash, "PUT", $nameData);
//        $result = $this->call($oRequestDetails);
//        $this->validateNullResult($result, __FUNCTION__);
//        $game->playerName = $nameData->name;
//
//        return $result;
//    }

//    public function addShots(\stdClass $game)
//    {
//        $shotData = new \stdClass();
//        $shots = array('A1' => "sunk", 'C2' => "hit", 'D2' => "sunk", 'J10' => "miss");
//
//        foreach ($shots as $shot => $expectedResult) {
//            $shotData->shot = $shot;
//            $oRequestDetails = new RequestDetails("/games/" . $game->playerHash . "/shots", "POST", $shotData, 201);
//            $result = $this->call($oRequestDetails)->getJson();
//            $this->validateAddShots($result->shotResult, $expectedResult);
//        }
//    }

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

    private function validateOtherGetUpdates(\stdClass $result, \stdClass $game)
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
