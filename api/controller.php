<?php
/*!
 * Copyright 2016 Everex https://everex.io
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

class ethplorerController {
    protected $db;
    protected $command;
    protected $params = array();
    protected $apiCommands = array('getTxInfo', 'getTokenHistory', 'getAddressTransactions', 'getAddressInfo', 'getTokenInfo', 'getAddressHistory', 'getTopTokens', 'getTop', 'getTokenHistoryGrouped', 'getPriceHistoryGrouped', 'getTokenPriceHistoryGrouped', 'getAddressPriceHistoryGrouped', 'getBlockTransactions', 'getLastBlock');
    protected $defaults;
    protected $startTime;
    protected $cacheState = '';

    public function __construct($es){
        if(!($es instanceof Ethplorer)){
            $this->sendError(3, 'Database connection failed');
        }
        $this->startTime = microtime(TRUE);
        $this->db = $es;
        $command = isset($_GET["cmd"]) ? $_GET["cmd"] : FALSE;
        if(!$command){
            $home = str_replace('/index.php', '', $_SERVER["SCRIPT_NAME"]);
            $uri = $_SERVER["REQUEST_URI"];
            if(FALSE !== strpos($uri, "?")){
                $uri = substr($uri, 0, strpos($uri, "?"));
            }
            $commandStr = preg_replace("/^\//", "", substr($uri, strlen($home)));
            $aParts = explode("/", $commandStr);
            $command = $aParts[0];
            if(count($aParts) > 1){
                for($i=1; $i<count($aParts); $i++){
                    $this->params[] = $aParts[$i];
                }
            }
        }
        $this->command = $command;
    }

    public function __destruct(){
        $ms = round(microtime(TRUE) - $this->startTime, 4);
        $date = date("Y-m-d H:i");
        $key = $this->getRequest('apiKey', "-");
        $source = $this->getRequest('domain', FALSE);
        if($source){
            file_put_contents(__DIR__ . '/../service/log/widget-request.log', "[$date] Widget: {$this->command}, source: {$source}\n", FILE_APPEND);
        }
        file_put_contents(__DIR__ . '/../service/log/api-request.log', "[$date] Call: {$this->command}, Key: {$key} URI: {$_SERVER["REQUEST_URI"]}, IP: {$_SERVER['REMOTE_ADDR']}, {$ms} s." . $this->cacheState . "\n", FILE_APPEND);
    }

    public function getCommand(){
        return $this->command;
    }

    public function getParam($number, $default = NULL){
        return isset($this->params[$number]) ? $this->params[$number] : $default;
    }

    public function getRequest($name, $default = NULL){
        $result = filter_input(INPUT_GET, $name);
        return (FALSE !== $result) && (!is_null($result)) ? $result : $default;
    }

    public function sendResult(array $result){
        echo json_encode($result, JSON_UNESCAPED_SLASHES);
        die();
    }

    public function sendError($code, $message){
        $result = array(
            'error' => array(
                'code' => $code,
                'message' => $message
            )
        );
        $this->sendResult($result);
    }

    /**
     * Checks API key and runs command
     *
     * @return void
     */
    public function run(){
        $result = FALSE;
        $command = $this->getCommand();
        if($command && in_array($command, $this->apiCommands) && method_exists($this, $command)){
            $key = $this->getRequest('apiKey', FALSE);
            if(!$key || !$this->db->checkAPIkey($key)){
                $this->sendError(1, 'Invalid API key');
            }
            $this->defaults = $this->db->getAPIKeyDefaults($key, $command);

            $timestamp = $this->getRequest('timestamp', FALSE);

            if(FALSE !== $timestamp){
                $cacheId = 'API-' . $command  . '-' . md5($_SERVER["REQUEST_URI"]);
                $oCache = $this->db->getCache();
                $result = $oCache->get($cacheId, FALSE, TRUE, 15);
            }
            if(!$result){
                $result = call_user_func(array($this, $command));
                if((FALSE !== $timestamp) && $cacheId && (FALSE !== $result)){
                    $oCache->save($cacheId, $result);
                }
            }
        }
        return $result;
    }

    /**
     * /getTokenInfo method implementation.
     *
     * @return array
     */
    public function getTokenInfo(){
        $address = $this->getParam(0, '');
        $address = strtolower($address);
        if((FALSE === $address)){
            $this->sendError(103, 'Missing address');
        }
        if(!$this->db->isValidAddress($address)){
            $this->sendError(104, 'Invalid address format');
        }
        $result = $this->db->getToken($address);
        if($result && is_array($result)){
            unset($result['checked']);
            unset($result['txsCount']);
            // unset($result['transfersCount']);

            // @todo: check what's wrong with cache
            $result['countOps'] = $this->db->countOperations($address);
            $result['transfersCount'] = (int)$result['countOps'];
            if(isset($result['issuancesCount']) && $result['issuancesCount']){
                $result['transfersCount'] = $result['transfersCount'] - (int)$result['issuancesCount'];
            }
            $result['holdersCount'] = $this->db->getTokenHoldersCount($address);
        }else{
            $this->sendError(150, 'Address is not a token contract');
        }
        $this->sendResult($result);
    }

    /**
     * /getAddressInfo method implementation.
     *
     * @return array
     */
    public function getAddressInfo(){
        $address = $this->getParam(0, '');
        $address = strtolower($address);
        $onlyToken = $this->getRequest('token', FALSE);
        if((FALSE === $address)){
            $this->sendError(103, 'Missing address');
        }
        $address = strtolower($address);
        if(!$this->db->isValidAddress($address) || ($onlyToken && !$this->db->isValidAddress($onlyToken))){
            $this->sendError(104, 'Invalid address format');
        }
        $result = array(
            'address' => $address,
            'ETH' => array(
                'balance'   => $this->db->getBalance($address),
                'totalIn'   => 0,
                'totalOut'  => 0,
            ),
            'countTxs' => $this->db->countTransactions($address)
        );
        if($result['countTxs'] && ($result['countTxs'] < 10000)){
            $out = $this->db->getEtherTotalOut($address);
            $result['ETH']['totalIn'] = $result['ETH']['balance'] + $out;
            $result['ETH']['totalOut'] = $out;
        }
        if($contract = $this->db->getContract($address)){
            $result['contractInfo'] = array(
                'creatorAddress' => $contract['creator'],
                'transactionHash' => $contract['hash'],
                'timestamp' => $contract['timestamp']
            );
            if($token = $this->db->getToken($address)){
                unset($token['checked']);
                unset($token['txsCount']);
                unset($token['transfersCount']);
                $result['tokenInfo'] = $token;
            }
        }
        $balances = $this->db->getAddressBalances($address, FALSE);
        if(is_array($balances) && !empty($balances)){
            $result['tokens'] = array();
            foreach($balances as $balance){
                if($onlyToken){
                    if($balance['contract'] !== strtolower($onlyToken)){
                        continue;
                    }
                }
                $token = $this->db->getToken($balance['contract']);
                if($token){
                    unset($token['checked']);
                    unset($token['txsCount']);
                    unset($token['transfersCount']);
                    $result['tokens'][] = array(
                        'tokenInfo' => $token,
                        'balance' => $balance['balance'],
                        'totalIn' => isset($balance['totalIn']) ? $balance['totalIn'] : 0,
                        'totalOut' => isset($balance['totalOut']) ? $balance['totalOut'] : 0
                    );
                }
            }
        }
        $this->sendResult($result);
    }

    /**
     * /getTxInfo method implementation.
     *
     * @return array
     */
    public function getTxInfo(){
        $txHash = $this->getParam(0, '');
        $txHash = strtolower($txHash);
        if((FALSE === $txHash)){
            $this->sendError(101, 'Missing transaction hash');
        }
        $txHash = strtolower($txHash);
        if(!$this->db->isValidTransactionHash($txHash)){
            $this->sendError(102, 'Invalid transaction hash format');
        }
        $tx = $this->db->getTransactionDetails($txHash);
        if(!is_array($tx) || (FALSE === $tx['tx'])){
            $this->sendError(404, 'Transaction not found');
        }
        $result = array(
            'hash'          => $txHash,
            'timestamp'     => $tx['tx']['timestamp'],
            'blockNumber'   => $tx['tx']['blockNumber'],
            'confirmations' => $this->db->getLastBlock() - $tx['tx']['blockNumber'] + 1,
            'success'       => $tx['tx']['success'],
            'from'          => $tx['tx']['from'],
            'to'            => $tx['tx']['to'],
            'value'         => $this->_bn2float($tx['tx']['value']),
            'input'         => $tx['tx']['input'],
            'gasLimit'      => $tx['tx']['gasLimit'],
            'gasUsed'       => $tx['tx']['gasUsed'],
            'logs'          => array(),
        );
        if(isset($tx['tx']) && !empty($tx['tx']['receipt']) && !empty($tx['tx']['receipt']['logs'])){
            foreach($tx['tx']['receipt']['logs'] as $log){
                $result['logs'][] = array(
                    'address'   => $log['address'],
                    'topics'    => $log['topics'],
                    'data'      => $log['data'],
                );
            }
        }
        $operations = $this->db->getOperations($txHash);
        if(is_array($operations) && !empty($operations)){
            foreach($operations as $i => $operation){
                $token = $this->db->getToken($operation['contract']);
                if($token && is_array($token)){
                    unset($token['checked']);
                    unset($token['txsCount']);
                    unset($token['transfersCount']);
                    $operations[$i]['tokenInfo'] = $token;
                }
                unset($operations[$i]['blockNumber']);
                unset($operations[$i]['success']);
                unset($operations[$i]['contract']);
            }
            $result['operations'] = $operations;
        }
        $this->sendResult($result);
    }

    /**
     * /getTokenHistory method implementation.
     *
     * @return array
     */
    public function getTokenHistory(){
        return $this->_getHistory();
    }

    /**
     * /getAddressHistory method implementation.
     *
     * @return array
     */
    public function getAddressHistory(){
        return $this->_getHistory(TRUE);
    }

    /**
     * /getAddressTransactions method implementation.
     *
     * @return array
     */
    public function getAddressTransactions(){
        $address = $this->getParam(0, '');
        $address = strtolower($address);
        $onlyToken = $this->getRequest('token', FALSE);
        if((FALSE === $address)){
            $this->sendError(103, 'Missing address');
        }
        $address = strtolower($address);
        if(!$this->db->isValidAddress($address)){
            $this->sendError(104, 'Invalid address format');
        }

        $maxLimit = is_array($this->defaults) && isset($this->defaults['limit']) ? $this->defaults['limit'] : 50;
        $limit = max(min(abs((int)$this->getRequest('limit', 10)), $maxLimit), 1);
        $showZeroValues = !!$this->getRequest('showZeroValues', FALSE);
        $result = $this->db->getTransactions($address, $limit, $showZeroValues);

        $this->sendResult($result);
    }

    /**
     * /getTop method implementation.
     *
     * @undocumented
     * @return array
     */
    public function getTop(){
        $maxLimit = is_array($this->defaults) && isset($this->defaults['limit']) ? $this->defaults['limit'] : 100;
        $limit = max(min(abs((int)$this->getRequest('limit', 50)), $maxLimit), 1);
        $criteria = $this->getRequest('criteria', 'trade');
        $result = array('tokens' => $this->db->getTokensTop($limit, $criteria));
        $this->sendResult($result);
    }

    /**
     * /getTopTokens method implementation.
     *
     * @undocumented
     * @return array
     */
    public function getTopTokens(){
        $maxLimit = is_array($this->defaults) && isset($this->defaults['limit']) ? $this->defaults['limit'] : 50;
        $maxPeriod = is_array($this->defaults) && isset($this->defaults['maxPeriod']) ? $this->defaults['maxPeriod'] : 90;
        $limit = max(min(abs((int)$this->getRequest('limit', 10)), $maxLimit), 1);
        $period = max(min(abs((int)$this->getRequest('period', 10)), $maxPeriod), 1);
        $criteria = $this->getRequest('criteria', 'opCount');
        $result = false;
        switch($criteria){
            case 'currentVolume':
                $result = $this->_getTopByCurrentVolume($limit);
                break;
            case 'periodVolume':
                $result = $this->_getTopByPeriodVolume($limit, $period);
                break;
            case 'opCount':
            default:
                $result = $this->_getTopByOperationsCount($limit, $period);
        }
        return $result;
    }

    protected function _getTopByOperationsCount($limit, $period){
        $result = array('tokens' => $this->db->getTopTokens($limit, $period));
        $this->sendResult($result);
    }

    protected function _getTopByCurrentVolume($limit){
        $result = array('tokens' => $this->db->getTopTokensByCurrentVolume($limit));
        $this->sendResult($result);
    }

    protected function _getTopByPeriodVolume($limit, $period){
        $result = array('tokens' => $this->db->getTopTokensByPeriodVolume($limit, $period));
        $this->sendResult($result);
    }

    /**
     * /getTokenHistoryGrouped method implementation.
     *
     * @undocumented
     * @return array
     */
    public function getTokenHistoryGrouped(){
        $period = min(abs((int)$this->getRequest('period', 30)), 90);
        $address = $this->getParam(0, FALSE);
        if($address){
            $address = strtolower($address);
            if(!$this->db->isValidAddress($address)){
                $this->sendError(104, 'Invalid token address format');
            }
        }
        $result = array('countTxs' => $this->db->getTokenHistoryGrouped($period, $address));
        $this->sendResult($result);
    }

    /**
     * /getPriceHistoryGrouped method implementation.
     *
     * @undocumented
     * @return array
     */
    public function getPriceHistoryGrouped(){
        $result = array('history' => array());
        $address = $this->getParam(0, FALSE);
        if($address){
            $address = strtolower($address);
            if(!$this->db->isValidAddress($address)){
                $this->sendError(104, 'Invalid address format');
            }
        }else{
            $this->sendResult($result);
            return;
        }
        if($token = $this->db->getToken($address)){
            $this->getTokenPriceHistoryGrouped();
        }else{
            $this->getAddressPriceHistoryGrouped();
        }
    }

    /**
     * /getTokenPriceHistoryGrouped method implementation.
     *
     * @undocumented
     * @return array
     */
    public function getTokenPriceHistoryGrouped(){
        $period = min(abs((int)$this->getRequest('period', 365)), 365);
        if($period <= 0) $period = 365;
        $address = $this->getParam(0, FALSE);
        if($address){
            $address = strtolower($address);
            if(!$this->db->isValidAddress($address)){
                $this->sendError(104, 'Invalid token address format');
            }
        }
        $result = array('history' => $this->db->getTokenPriceHistoryGrouped($address, $period));
        $this->sendResult($result);
    }

    /**
     * /getAddressPriceHistoryGrouped method implementation.
     *
     * @undocumented
     * @return array
     */
    public function getAddressPriceHistoryGrouped(){
        $address = $this->getParam(0, FALSE);
        if($address){
            $address = strtolower($address);
            if(!$this->db->isValidAddress($address)){
                $this->sendError(104, 'Invalid address format');
            }
        }
        $result = array('history' => $this->db->getAddressPriceHistoryGrouped($address));
        if(isset($result['history']['cache'])) $this->cacheState = $result['history']['cache'];
        else $this->cacheState = '';
        $this->sendResult($result);
    }

    public function getBlockTransactions(){
        $block = (int)$this->getRequest('block');
        $result = $this->db->getBlockTransactions($block);
        $this->sendResult($result);
    }


    public function getLastBlock(){
        $result = array('lastBlock' => $this->db->getLastBlock());
        $this->sendResult($result);
    }

    /**
     *
     * Common method to get token and address operation history.
     *
     * @param bool $addressHistoryMode
     * @return array
     */
    protected function _getHistory($addressHistoryMode = FALSE){
        $result = array(
            'operations' => array()
        );
        $address = $this->getParam(0, FALSE);
        if($address){
            $address = strtolower($address);
        }
        if((!$address && $addressHistoryMode) || ((FALSE !== $address) && (!$this->db->isValidAddress($address)))){
            $this->sendError(104, 'Invalid address format');
        }
        $maxLimit = is_array($this->defaults) && isset($this->defaults['limit']) ? $this->defaults['limit'] : 10;
        $options = array(
            'type'      => $this->getRequest('type', FALSE),
            'limit'     => max(min(abs((int)$this->getRequest('limit', 10)), $maxLimit), 1),
        );
        if(FALSE !== $address){
            $options['address'] = $address;
        }
        if(FALSE !== $this->getRequest('timestamp', FALSE)){
            $options['timestamp'] = (int)$this->getRequest('timestamp');
        }
        if($addressHistoryMode){
            $token = $this->getRequest('token', FALSE);
            if(FALSE !== $token){
                $token = strtolower($token);
                if(!$this->db->isValidAddress($token)){
                    $this->sendError(104, 'Invalid token address format');
                }
                $options['token'] = $token;
            }
            $options['history'] = TRUE;
        }
        $operations = $this->db->getLastTransfers($options);
        if(is_array($operations) && count($operations)){
            for($i = 0; $i < count($operations); $i++){
                $operation = $operations[$i];
                $res = array(
                    'timestamp'         => $operation['timestamp'],
                    'transactionHash'   => $operation['transactionHash'],
                    'tokenInfo'         => $operation['token'],
                    'type'              => $operation['type'],
                    'value'             => $operation['value'],
                );
                if(isset($operation['address'])){
                    $res['address'] = $operation['address'];
                }
                if(isset($operation['from'])){
                    $res['from'] = $operation['from'];
                    $res['to'] = $operation['to'];
                }
                $result['operations'][] = $res;
            }
        }
        return $result;
    }

    /**
     * Converts JavaScript bignumber format to a float.
     *
     * @param array $aNumber
     * @return float
     */
    protected function _bn2float($aNumber){
        $res = is_array($aNumber) ? 0 : $aNumber;
        if(isset($aNumber['c']) && !empty($aNumber['c'])){
            $str = '';
            for($i=0; $i<count($aNumber['c']); $i++){
                $str .= (string)$aNumber['c'][$i];
            }
            $res = floatval($str) / pow(10, 18);
        }
        return $res;
    }
}