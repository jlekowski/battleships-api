- BattleshipsApiComponent
  * move to a separate repo as a component
  * make it pretty (ApiRequest)
  * move to client component too (and check how to register as a command in API) (E2eCommand)

- CoordsInfo and CoordsInfoCollection refactoring:
  * rename get*Position to provide CoordsInfo
  * maybe return CoordsInfoCollection, not array
  * maybe always return object with isEmpty() === true instead of null?
  * cache all coords? But what if appended?

- CoordsInfo::$axisY - replace with a constant (and composer update to require 5.6)

- exception when accessing not existing game currently is "AppBundle\\Entity\\Game object not found"
- test caching games, events, available games etc.
- check strictly for URI, e.g. /v1/games/1b

- Event types are log related (name_update, start/join as game state), and flow related (chat/shot).
    Maybe games/{id} with /chats /shots and game (bit) status and initial get would be to get lastIdEvents? :/

- maybe go with subrequest to create event (patchGameAction/postGameAction with ships)?
- patchGameAction - do we need both events if both parameters (joinGame, playerShips) set in one request?

- Think about multiple patching (207 response status) http://williamdurand.fr/2014/02/14/please-do-not-patch-like-an-idiot/

- headers for specific version? # in the future
- exclusions on object properties depends on version # in the future
- ApiDoc ? http://welcometothebundle.com/web-api-rest-with-symfony2-the-best-way-the-post-method/ # in the future

- in EventRepository check difference for this->matching() and game->getEvents() (if game->getEvents()->toArray() called beforehand?)

- trait or abstract for entity timestamp # it doesn't look well
- maybe add Accept-Encoding too? # doesn't seem to be an issue
- maybe rename user to player? (then player/other vs. user) # sounds like a hassle to change + getPlayer/getOther (and event getPlayer)
- maybe go with batch requests (to get game for example) https://parse.com/docs/rest/guide

- what URI for shot? It's an update of game/{id/hash}|game/{id/hash}/shots resource and I need a result
- what URI for ships? It's an update of game/{id/hash} resource, or adding multiple game/{id/hash}/ships resources?

- MongoDB
