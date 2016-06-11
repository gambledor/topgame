<?php

use dao\platform\ReportPlayersAnagraphicDao;
use dao\platform\PlayersRegistrationDao;
use dao\platform\TopGamePlayerDao;

require 'definesTopGame.php';
require __DIR__ . '/../../dao/platform/ReportPlayersAnagraphicDao.php';
require __DIR__ . '/../../dao/platform/PlayersRegistrationDao.php';
require __DIR__ . '/../../dao/platform/TopGamePlayerDao.php';

/**
 *
 */
class CasinoServices
{
    /**
     * @var SoapClinet $soapClient
     */
    private $soapClient = null;

    /**
     * @var ReportPlayersAnagraphic $repPlayersAnagr
     */
    private $repPlayersAnagrDao = null;

    /**
     * @var PlayersRegistrationDao $playersRegistrationDao
     */
    private $playersRegistrationDao = null;

    /**
     * @var TopGamePlayerDao $topGamePlayerDao
     */
    private $topGamePlayerDao = null;

    public function __construct()
    {
        $this->repPlayersAnagrDao       = ReportPlayersAnagraphicDao::getInstance();
        $this->playersRegistrationDao   = PlayersRegistrationDao::getInstance();
        $this->topGamePlayerDao         = TopGamePlayerDao::getInstance();

        $options = array(
            'soap_version' => SOAP_1_1,
            'trace'        => true,
            'exceptions'   => true,
            'cache_wsdl'   => WSDL_CACHE_NONE,
            'style'        => SOAP_DOCUMENT,
            'use'          => SOAP_LITERAL,
            //'compression'  => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP,
            'location'     => WS_SERVICE_URL,
            'uri'          => 'http://gametechclubs.biz/casino/game/external/schemas'
        );
        $this->soapClient = new SoapClient(WSDL_URI, $options);
    }
    /**
     * This method is used by the operator in order to register a player
     * within the Topgame system.
     *
     * @param integer $playerId
     * @throws \Exception
     * @return array
     */
    public function createPlayer($playerId)
    {
        return $this->createUpdatePlayer($playerId, 'create');
    }
    /**
     * This method provides the Operator with the option to update
     * the player records within the topgame system.
     *
     * @throws\Exception
     * @return boolean
     */
    public function updatePlayer($playerId)
    {
        return $this->createUpdatePlayer($playerId, 'update');
    }
    /**
     * This method is used by the Operator in order to retrieve
     * additional information regarding the player.
     *
     * @return integer The ban status
     */
    public function getPlayer($playerId)
    {
        throw new \Exception(__FUNCTION__ . ' Not implemented yet. ');
    }
    /**
     * This method is used bt the Operator to receive a valid game URL
     * for the requested game.
     *
     * @return string
     */
    public function gameRequest(array $params)
    {
        $request = new stdClass();
        $request->secureLogin    = SECURE_LOGIN;
        $request->securePassword = SECURE_PASSWORD;
        $request->gameID         = $params['gameId'];
        $request->playerID       = $params['playerId'];
        $request->technology     = $params['technology'];
        $request->clientPlatform = $params['clientPlatform'];
        $request->cashierUrl     = $params['cashierUrl'];
        $request->lobbyURL       = $params['lobbyUrl'];

        $resp = $this->soapClient->GameRequest($request);

        if ($resp->error != 0) {
            throw new \Exception($resp->description, $resp->error);
        }

        return $resp->url;
    }
    /**
     * @param integer $playerId The player identifier.
     * @param string $opt The option to activete {'create', 'update'}
     * @throws \Exception
     * @return boolean
     */
    private function createUpdatePlayer($playerId, $opt)
    {
        $anagraphic = $this->repPlayersAnagrDao->findByPK($playerId);
        $registration = $this->playersRegistrationDao->findByPK($playerId);
        // build up the request
        $request = new stdClass();
        $request->secureLogin    = SECURE_LOGIN;
        $request->securePassword = SECURE_PASSWORD;
        $request->nickname       = $registration['username'];
        $request->email          = $registration['username'].'@casino.com';
        $request->currency       = $anagraphic['currency'];
        $request->language       = $anagraphic['lang'];
        $request->firstName      = '';
        $request->lastName       = '';
        $request->accountID      = $playerId;
        $request->banStatus      = 1;
        // To send the request
        switch ($opt) {
            case 'create':
                $resp = $this->soapClient->CreatePlayer($request);
                //$resp =(object) $this->soapClient->__soapCall('CreatePlayer', $request);
                break;
            case 'update':
                $resp = $this->soapClient->UpdatePlayer($request);
                break;
            default:
                throw new \Exception('Unknown operation '.$opt);
                break;
        }
        if ($resp->error > 0) {
            throw new \Exception($resp->description, $resp->error);
        }
         /*print($this->soapClient->__getLastResponse());
        print($this->soapClient->__getLastResponseHeaders());*/
        // to insert topgame player id mapping
        if ($opt == 'create' && !empty($resp)) {
            $this->topGamePlayerDao->addPlayer($playerId, $resp->playerID);
        }

        return $resp->playerID;
    }
}
/* End of file CasinoServices.php */
