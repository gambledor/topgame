<?php

use account\AccountManager;
use dao\platform\TopGamePlayerDao;

require __DIR__ . '/../../account/AccountManager.php';
require __DIR__ . '/../../dao/platform/TopGamePlayerDao.php';
require 'definesTopGame.php';

/**
 *
 */
class ProtocolTOPGAME
{
    const THIRD_PART_NAME = 'TopGame';
    /**
     * @var AccountManager $accountManager
     */
    private $accountManager = null;

    /**
     * @var TopGamePlayerDao $topGamePlayerDao
     */
    private $topGamePlayerDao = null;


    public function __construct()
    {
        $this->accountManager = AccountManager::getInstance();
        $this->topGamePlayerDao = TopGamePlayerDao::getInstance();
    }

    /**
     * This method is used by the Topgame system in order to identify
     * the current balance for the specific user.
     *
     * @return void
     */
    public function getBalance(stdClass $request)
    {
        file_put_contents('log.log', __FUNCTION__ .' '.print_r($request, true), FILE_APPEND);
        $errorCode = 0;
        $errorMsg  = '';

        $res = $this->topGamePlayerDao->findByTopgameId($request->request->playerID);
        $balance = $this->accountManager->findUserBalance($res['player_id'], self::THIRD_PART_NAME);
        if ($balance === -1) {
            $errorCode = BALANCE_KO_CODE;
            $errorMsg  = BALANCE_KO_MSG;
        }

        return array(
            'GetBalanceResult' => array(
                'balance'     => ['cash' => $balance, 'bonus' => 0.00 ],
                'error'       => $errorCode,
                'description' => $errorMsg
            )
        );
    }
    /**
     * This method is used by the Topgame system in order to retrieve the list
     * of all End Users that are currently online and their balance.
     *
     * @return array
     */
    public function getBulkBalance(stdClass $request)
    {
        file_put_contents('log.log', __FUNCTION__ .' '.print_r($request, true), FILE_APPEND);
        $self = $this;
        $callback = function ($playerId) use ($self) {
            //file_put_contents('log.log', print_r($playerId, true), FILE_APPEND);
            $res = $self->topGamePlayerDao->findByTopgameId($playerId);
            $balance = $self->accountManager->findUserBalance($res['player_id'], $self::THIRD_PART_NAME);
            //file_put_contents('log.log', print_r($balance, true), FILE_APPEND);
            return [
                'playerID' => $playerId,
                'balance'  => ['cash' => $balance, 'bonus' => 0]
            ];
        };

        $playerIds = $request->request->playerID;
        if (count($playerIds) <= 1) {
            $playerIds = [$playerIds];
        }
        $response['GetBulkBalanceResult']['PlayerList'] = array_map($callback, $playerIds);
        $response['GetBulkBalanceResult']['error']          = 0;
        $response['GetBulkBalanceResult']['description']    = '';

        file_put_contents('log.log', print_r($response, true), FILE_APPEND);
        return $response;
    }
    /**
     * This method is used by Topgame system in order to request an approval
     * for an upcoming bet by the player
     *
     * @param BetRequest $request
     * @return array
     */
    public function betRequest(stdClass $request)
    {
        file_put_contents('log.log', __FUNCTION__ .' '.print_r($request, true), FILE_APPEND);
        $error = 0;
        $description = '';
        try {
            $res = $this->topGamePlayerDao->findByTopgameId($request->request->playerID);
            $transactionId = date('Y-m-d H:s:i').'|'
                .$request->request->playSessionID.'|'
                .$request->request->gameID;

            $this->accountManager->makeBet(
                $res['player_id'],
                self::THIRD_PART_NAME,
                $request->request->betAmount,
                $transactionId,
                json_encode($request)
            );
            $balance = $this->accountManager->findUserBalance($res['player_id'], self::THIRD_PART_NAME);

        } catch (Exception $e) {
            $balance        = 0.00;
            $error          = $e->getCode();
            $description    = $e->getMessage();
        }
        $response = [
            'BetRequestResult' => [
                'balance' => [
                    'cash'  => $balance,
                    'bonus' => 0.00
                ],
                'error' => $error,
                'description' => $description
            ]
        ];
        file_put_contents('log.log', print_r($response, true), FILE_APPEND);

        return $response;
    }
    /**
     * This method is used by Topgame system in order to update the operator
     * on the result of the bet made by the player.
     *
     * @param BetResult $request
     * @return array
     */
    public function betResult(stdClass $request)
    {
        file_put_contents('log.log', __FUNCTION__ .' '.print_r($request, true), FILE_APPEND);
        $error = 0;
        $description = '';
        $balance = 0;
        try {
            $res = $this->topGamePlayerDao->findByTopgameId($request->request->playerID);
            $transactionId = date('Y-m-d H:s:i').'|'
                .$request->request->playSessionID;
            $this->accountManager->makeWin(
                $res['player_id'],
                self::THIRD_PART_NAME,
                $request->request->winAmount,
                $transactionId,
                json_encode($request)
            );
            $balance = $this->accountManager->findUserBalance($res['player_id']);
        } catch (Exception $e) {
            $error = $e->getCode();
            $description = $e->getMessage();
        }

        $response = array(
            'BetResultResult' => array(
                'balance'     => array('cash' => $balance, 'bonus' => 0.00),
                'error'       => $error,
                'description' => $description
            )
        );
        file_put_contents('log.log', print_r($response, true), FILE_APPEND);

        return $response;
    }
    /**
     * This method is used by the topgame system in order to retrieve the current
     * version of the API.
     *
     * @return array
     */
    public function getVersion()
    {
        return ['GetVersionResult' => ['version' => API_VERSION]];
    }
}
/* End of file ProtocolTOPGAME.php */
