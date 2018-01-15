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

require_once __DIR__ . '/cache.php';
require_once __DIR__ . '/mongo.php';
require_once __DIR__ . '/profiler.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use \Litipk\BigNumbers\Decimal as Decimal;

class Ethplorer {

    /**
     * Chainy contract address
     */
    const ADDRESS_CHAINY = '0xf3763c30dd6986b53402d41a8552b8f7f6a6089b';

    /**
     * Settings
     *
     * @var array
     */
    protected $aSettings = array();

    /**
     * MongoDB.
     *
     * @var evxMongo
     */
    protected $oMongo;

    /**
     * Singleton instance.
     *
     * @var Ethplorer
     */
    protected static $oInstance;

    /**
     * Cache storage.
     *
     * @var evxCache
     */
    protected $oCache;

    /**
     *
     * @var int
     */
    protected $pageSize = 0;

    /**
     *
     * @var array
     */
    protected $aPager = array();

    /**
     *
     * @var string
     */
    protected $filter = FALSE;

    /**
     * Cache for getTokens
     *
     * @var array
     */
    protected $aTokens = FALSE;
    /**
     * Constructor.
     *
     * @throws Exception
     */
    protected function __construct(array $aConfig){
        $uri = isset($_SERVER) && isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : FALSE;
        evxProfiler::checkpoint('Ethplorer', 'START', $uri);
        $this->aSettings = $aConfig;
        $this->aSettings += array(
            "cacheDir" => dirname(__FILE__) . "/../cache/",
            "logsDir" => dirname(__FILE__) . "/../log/",
        );
        $cacheDriver = isset($this->aSettings['cacheDriver']) ? $this->aSettings['cacheDriver'] : 'file';
        $this->oCache = new evxCache($this->aSettings['cacheDir'], $cacheDriver);
        if(isset($this->aSettings['mongo']) && is_array($this->aSettings['mongo'])){
            evxMongo::init($this->aSettings['mongo']);
            $this->oMongo = evxMongo::getInstance();
        }
    }

    public function __destruct(){
        // Todo: profiler config
        evxProfiler::checkpoint('Ethplorer', 'FINISH');
        $total = evxProfiler::getTotalTime();
        if(isset($this->aSettings['debugId']) && $this->aSettings['debugId']){
            evxProfiler::log($this->aSettings['logsDir'] . 'profiler-' . /* time() . '-' . */ md5($this->aSettings['debugId']) . '.log');
        }
        $slowQueryTime = isset($this->aSettings['slowQueryTime']) ? (int)$this->aSettings['slowQueryTime'] : 10;
        if(($total > $slowQueryTime) && (php_sapi_name() !== 'cli')){
            evxProfiler::log($this->aSettings['logsDir'] . 'profiler-long-queries.log');
        }
    }

    /**
     * Returns cache object
     *
     * @return evxCache
     */
    public function getCache(){
        return $this->oCache;
    }

    /**
     * Singleton getter.
     *
     * @return Ethereum
     */
    public static function db(array $aConfig = array()){
        if(is_null(self::$oInstance)){
            self::$oInstance = new Ethplorer($aConfig);
        }
        return self::$oInstance;
    }

    /**
     * Sets new page size.
     *
     * @param int $pageSize
     */
    public function setPageSize($pageSize){
        $this->pageSize = $pageSize;
    }

    /**
     * Sets current page offset for section
     *
     * @param string $section
     * @param int $page
     */
    public function setPager($section, $page = 1){
        $this->aPager[$section] = $page;
    }

    /**
     * Return page offset for section
     *
     * @param string $section
     * @return int
     */
    public function getPager($section){
        return isset($this->aPager[$section]) ? $this->aPager[$section] : 1;
    }

    /**
     * Set filter value
     *
     * @param string $filter
     */
    public function setFilter($filter){
        $this->filter = $filter;
    }

    /**
     * Returns item offset for section.
     *
     * @param string $section
     * @int type
     */
    public function getOffset($section){
        $limit = $this->pageSize;
        return (1 === $this->getPager($section)) ? FALSE : ($this->getPager($section) - 1) * $limit;
    }

    /**
     * Returns true if provided string is a valid ethereum address.
     *
     * @param string $address  Address to check
     * @return bool
     */
    public function isValidAddress($address){
        return (is_string($address)) ? preg_match("/^0x[0-9a-f]{40}$/", $address) : false;
    }

    /**
     * Returns true if provided string is a valid ethereum tx hash.
     *
     * @param string  $hash  Hash to check
     * @return bool
     */
    public function isValidTransactionHash($hash){
        return (is_string($hash)) ? preg_match("/^0x[0-9a-f]{64}$/", $hash) : false;
    }

    /**
     * Returns true if provided string is a chainy contract address.
     *
     * @param type $address
     * @return bool
     */
    public function isChainyAddress($address){
        return ($address === self::ADDRESS_CHAINY);
    }

    /**
     * Returns advanced address details.
     *
     * @param string $address
     * @return array
     */
    public function getAddressDetails($address, $limit = 50){
        evxProfiler::checkpoint('getAddressDetails', 'START', 'address=' . $address . ', limit=' . $limit);
        $result = array(
            "isContract"    => FALSE,
            "transfers"     => array()
        );
        if($this->pageSize){
            $limit = $this->pageSize;
        }
        $refresh = isset($this->aPager['refresh']) ? $this->aPager['refresh'] : FALSE;
        $contract = $this->getContract($address);
        $token = FALSE;
        if($contract){
            $result['isContract'] = TRUE;
            $result['contract'] = $contract;
            if($token = $this->getToken($address)){
                $result["token"] = $token;
            }elseif($this->isChainyAddress($address)){
                $result['chainy'] = $this->getChainyTransactions($limit, $this->getOffset('chainy'));
                $count = $this->countChainy();
                $result['pager']['chainy'] = array(
                    'page' => $this->getPager('chainy'),
                    'records' => $count,
                    'total' => $this->filter ? $this->countChainy(FALSE) : $count
                );
            }
        }else{
            $token = $this->getToken($address);
            if(is_array($token)){
                $result['isContract'] = TRUE;
                // @todo
                $result['contract'] = array();
                $result["token"] = $token;
            }
        }
        if($result['isContract'] && isset($result['token'])){
            $result['pager'] = array('pageSize' => $limit);
            foreach(array('transfers', 'issuances', 'holders') as $type){
                if(!$refresh || ($type === $refresh)){
                    $page = $this->getPager($type);
                    $offset = $this->getOffset($type);
                    switch($type){
                        case 'transfers':
                            $count = $this->getContractOperationCount('transfer', $address);
                            $total = $this->filter ? $this->getContractOperationCount('transfer', $address, FALSE) : $count;
                            $cmd = 'getContractTransfers';
                            break;
                        case 'issuances':
                            $count = $this->getContractOperationCount(array('$in' => array('issuance', 'burn', 'mint')), $address);
                            $total = $this->filter ? $this->getContractOperationCount(array('$in' => array('issuance', 'burn', 'mint')), $address, FALSE) : $count;
                            $cmd = 'getContractIssuances';
                            break;
                        case 'holders':
                            $count = $this->getTokenHoldersCount($address);
                            $total = $this->filter ? $this->getTokenHoldersCount($address, FALSE) : $count;
                            $cmd = 'getTokenHolders';
                            break;
                    }
                    if($offset && ($offset > $count)){
                        $offset = 0;
                        $page = 1;
                    }
                    $result[$type] = $this->{$cmd}($address, $limit, $offset);;
                    $result['pager'][$type] = array(
                        'page' => $page,
                        'records' => $count,
                        'total' => $total
                    );
                }
            }
            // @todo: move to extension
            $ck = '0x06012c8cf97bead5deae237070f9587f8e7a266d';
            if($address == $ck){
                $result['cryptokitties'] = true;
            }
        }else{
            // @todo: move to extension
            $ck = '0x06012c8cf97bead5deae237070f9587f8e7a266d';
            $cursor = $this->oMongo->find('transactions', array('from' => $address, 'to' => $ck));
            if($cursor){
                foreach($cursor as $token){
                    $result['cryptokitties'] = true;
                    break;
                }
            }            
        }
        if(!isset($result['token']) && !isset($result['pager'])){
            // Get balances
            $result["tokens"] = array();
            $result["balances"] = $this->getAddressBalances($address);
            $aBalances = array();
            evxProfiler::checkpoint('getTokenLoop', 'START');
            foreach($result["balances"] as $balance){
                $balanceToken = $this->getToken($balance["contract"]);
                if($balanceToken){
                    $result["tokens"][$balance["contract"]] = $balanceToken;
                    $aBalances[] = $balance;
                }
            }
            evxProfiler::checkpoint('getTokenLoop', 'FINISH');
            $result["balances"] = $aBalances;
            $result["transfers"] = $this->getAddressOperations($address, $limit, $this->getOffset('transfers'), array('transfer'));
            $countOperations = $this->countOperations($address);
            $totalOperations = $this->filter ? $this->countOperations($address, FALSE) : $countOperations;
            $result['pager']['transfers'] = array(
                'page' => $this->getPager('transfers'),
                'records' => $countOperations,
                'total' => $totalOperations,
            );
        }
        if(!$refresh){
            $result['balance'] = $this->getBalance($address);
            $result['balanceOut'] = 0;
            $result['balanceIn'] = 0;
            if(!$this->isHighloadedAddress($address)){
                $result['balanceOut'] = $this->getEtherTotalOut($address);
                $result['balanceIn'] = $result['balanceOut'] + $result['balance'];
            }
        }
        evxProfiler::checkpoint('getAddressDetails', 'FINISH');
        return $result;
    }

    public function getTokenTotalInOut($address){
        evxProfiler::checkpoint('getTokenTotalInOut', 'START', 'address=' . $address);
        $result = array('totalIn' => 0, 'totalOut' => 0);
        // temporary off
        if(false && $this->isValidAddress($address)){
            $aResult = $this->oMongo->aggregate('balances', array(
                array('$match' => array("contract" => $address)),
                array(
                    '$group' => array(
                        "_id" => '$contract',
                        'totalIn' => array('$sum' => '$totalIn'),
                        'totalOut' => array('$sum' => '$totalOut')
                    )
                ),
            ));
            if(is_array($aResult) && isset($aResult['result']) && count($aResult['result'])){
                $result['totalIn'] = $aResult['result'][0]['totalIn'];
                $result['totalOut'] = $aResult['result'][0]['totalOut'];
            }
        }
        evxProfiler::checkpoint('getTokenTotalInOut', 'FINISH');
        return $result;
    }

    /**
     * Returns ETH address total out.
     * Total in can be calculated as total out + current balance.
     *
     * @param string $address
     * @param bool $updateCache
     * @return float
     */
    public function getEtherTotalOut($address, $updateCache = FALSE){
        $cache = 'ethOut-' . $address;
        $result = $this->oCache->get($cache, FALSE, TRUE, 3600);
        if($updateCache || (FALSE === $result)){
            evxProfiler::checkpoint('getEtherTotalOut', 'START', 'address=' . $address);
            if($this->isValidAddress($address)){
                $aResult = $this->oMongo->aggregate('transactions', array(
                    array('$match' => array("from" => $address)),
                    array(
                        '$group' => array(
                            "_id" => '$from',
                            'out' => array('$sum' => '$value')
                        )
                    ),
                ));
                $result = 0;
                if(is_array($aResult) && isset($aResult['result'])){
                    foreach($aResult['result'] as $record){
                        $result += floatval($record['out']);
                    }
                }
                $this->oCache->save($cache, $result);
            }
            evxProfiler::checkpoint('getEtherTotalOut', 'FINISH');
        }
        return (float)$result;
    }

    /**
     * Returns transactions list for a specific address.
     *
     * @param string  $address
     * @param int     $limit
     * @return array
     */
    public function getTransactions($address, $limit = 10, $showZero = FALSE){
        $result = array();
        $search = array('$or' => array(array("from" => $address), array("to" => $address)));
        if(!$showZero){
            $search = array('$and' => array($search, array('value' => array('$gt' => 0))));
        }
        $cursor = $this->oMongo->find('transactions', $search, array("timestamp" => -1), $limit);
        foreach($cursor as $tx){
            $receipt = isset($tx['receipt']) ? $tx['receipt'] : false;
            $tx['gasLimit'] = $tx['gas'];
            $tx['gasUsed'] = isset($tx['gasUsed']) ? $tx['gasUsed'] : ($receipt ? $receipt['gasUsed'] : 0);
            // @todo: research
            // $toContract = !!$tx['input'];
            // $toContract = !!$this->getContract($tx['to']); // <-- too slow

            $success = ((21000 == $tx['gasUsed']) || /*!$toContract ||*/ ($tx['gasUsed'] < $tx['gasLimit']) || ($receipt && !empty($receipt['logs'])));
            $success = isset($tx['status']) ? !!$tx['status'] : $success;

            $result[] = array(
                'timestamp' => $tx['timestamp'],
                'from' => $tx['from'],
                'to' => $tx['to'],
                'hash' => $tx['hash'],
                'value' => $tx['value'],
                'input' => $tx['input'],
                'success' => $success
            );
        }
        return $result;
    }

    /**
     * Returns advanced transaction data.
     *
     * @param string  $hash  Transaction hash
     * @return array
     */
    public function getTransactionDetails($hash){
        evxProfiler::checkpoint('getTransactionDetails', 'START', 'hash=' . $hash);
        $cache = 'tx-' . $hash;
        $result = $this->oCache->get($cache, false, true);
        if(isset($_GET['update_cache']) && $_GET['update_cache']) $result = FALSE;
        if(false === $result){
            $tx = $this->getTransaction($hash);
            $result = array(
                "tx" => $tx,
                "contracts" => array()
            );
            $tokenAddr = false;
            if(isset($tx["creates"]) && $tx["creates"]){
                $result["contracts"][] = $tx["creates"];
                $tokenAddr = $tx["creates"];
            }
            $fromContract = $this->getContract($tx["from"]);
            if($fromContract){
                $result["contracts"][] = $tx["from"];
            }
            if(isset($tx["to"]) && $tx["to"]){
                if($this->getContract($tx["to"])){
                    $result["contracts"][] = $tx["to"];
                    $tokenAddr = $tx["to"];
                }
            }
            $result["contracts"] = array_values(array_unique($result["contracts"]));
            if($tokenAddr){
                if($token = $this->getToken($tokenAddr)){
                    $result['token'] = $token;
                }
            }
            $result["operations"] = $this->getOperations($hash);
            if(is_array($result["operations"]) && count($result["operations"])){
                foreach($result["operations"] as $idx => $operation){
                    if($result["operations"][$idx]['contract'] !== $tx["to"]){
                        $result["contracts"][] = $operation['contract'];
                    }
                    if($token = $this->getToken($operation['contract'])){
                        $result['token'] = $token;
                        $result["operations"][$idx]['type'] = ucfirst($operation['type']);
                        $result["operations"][$idx]['token'] = $token;
                    }
                }
            }
            if($result['tx']){
                $this->oCache->save($cache, $result);
            }
        }
        if(is_array($result) && is_array($result['tx'])){

            $confirmations = 1;
            $lastblock = $this->getLastBlock();
            if($lastblock){
                $confirmations = $lastblock - $result['tx']['blockNumber'] + 1;
                if($confirmations < 1){
                    $confirmations = 1;
                }
            }
            $result['tx']['confirmations'] = $confirmations;

            // Temporary
            $methodsFile = dirname(__FILE__) . "/../methods.sha3.php";
            if(file_exists($methodsFile)){
                if($result['tx']['input']){
                    $methods = require($methodsFile);
                    $cmd = substr($result['tx']['input'], 2, 8);
                    if(isset($methods[$cmd])){
                        $result['tx']['method'] = $methods[$cmd];
                    }
                }
            }
        }
        if(is_array($result) && isset($result['token']) && is_array($result['token'])){
            $result['token'] = $this->getToken($result['token']['address']);
        }
        evxProfiler::checkpoint('getTransactionDetails', 'FINISH');
        return $result;
    }

    /**
     * Return address ETH balance.
     *
     * @param string  $address  Address
     * @return double
     */
    public function getBalance($address){
        evxProfiler::checkpoint('getBalance', 'START', 'address=' . $address);
        $time = microtime(true);
        $cacheId = 'ethBalance-' . $address;
        $balance = $this->oCache->get($cacheId, false, true, 30);
        if(false === $balance){
            $balance = $this->_callRPC('eth_getBalance', array($address, 'latest'));
            if(false !== $balance){
                $balance = hexdec(str_replace('0x', '', $balance)) / pow(10, 18);
                $this->oCache->save($cacheId, $balance);
            }else{
                file_put_contents(__DIR__ . '/../log/parity.log', '[' . date('Y-m-d H:i:s') . '] - get balance for ' . $address . " failed\n", FILE_APPEND);
                $this->oCache->save($cacheId, 0);
            }
        }
        $qTime = microtime(true) - $time;
        if($qTime > 0.5){
            file_put_contents(__DIR__ . '/../log/parity.log', '[' . date('Y-m-d H:i:s') . '] - (' . $qTime . 's) get ETH balance of ' . $address . "\n", FILE_APPEND);
        }
        evxProfiler::checkpoint('getBalance', 'FINISH');
        return $balance;
    }

    /**
     * Return transaction data by transaction hash.
     *
     * @param string  $tx  Transaction hash
     * @return array
     */
    public function getTransaction($tx){
        evxProfiler::checkpoint('getTransaction', 'START', 'hash=' . $tx);
        $cursor = $this->oMongo->find('transactions', array("hash" => $tx));
        $result = false;
        foreach($cursor as $result) break;        
        if($result){
            $receipt = isset($result['receipt']) ? $result['receipt'] : false;
            $result['gasLimit'] = $result['gas'];
            unset($result["_id"]);
            unset($result["gas"]);
            $result['gasUsed'] = isset($result['gasUsed']) ? $result['gasUsed'] : ($receipt ? $receipt['gasUsed'] : 0);

            $success = ((21000 == $result['gasUsed']) || ($result['gasUsed'] < $result['gasLimit']) || ($receipt && !empty($receipt['logs'])));
            $result['success'] = isset($result['status']) ? !!$result['status'] : $success;

            $methodsFile = dirname(__FILE__) . "/../methods.sha3.php";
            if(file_exists($methodsFile)){
                if($result['input']){
                    $methods = require($methodsFile);
                    $cmd = substr($result['input'], 2, 8);
                    if(isset($methods[$cmd])){
                        $result['method'] = $methods[$cmd];
                    }
                }
            }

        }
        evxProfiler::checkpoint('getTransaction', 'FINISH');
        return $result;
    }


    /**
     * Returns list of transfers in specified transaction.
     *
     * @param string  $tx  Transaction hash
     * @return array
     */
    public function getOperations($tx, $type = FALSE){
        evxProfiler::checkpoint('getOperations', 'START', 'hash=' . $tx);
        $search = array("transactionHash" => $tx);
        if($type){
            $search['type'] = $type;
        }
        $cursor = $this->oMongo->find('operations', $search, array('priority' => 1));
        $result = array();
        foreach($cursor as $res){
            unset($res["_id"]);
            $res["success"] = true;
            $result[] = $res;
        }
        evxProfiler::checkpoint('getOperations', 'FINISH');
        return $result;
    }

    /**
     * Returns list of transfers in specified transaction.
     *
     * @param string  $tx  Transaction hash
     * @return array
     */
    public function getTransfers($tx){
        return $this->getOperations($tx, 'transfer');
    }

    /**
     * Returns list of issuances in specified transaction.
     *
     * @param string  $tx  Transaction hash
     * @return array
     */
    public function getIssuances($tx){
        return $this->getOperations($tx, array('$in' => array('issuance', 'burn', 'mint')));
    }

    /**
     * Returns list of known tokens.
     *
     * @param bool  $updateCache  Update cache from DB if true
     * @return array
     */
    public function getTokens($updateCache = false){
        if(FALSE !== $this->aTokens){
            return $this->aTokens;
        }
        $aResult = $this->oCache->get('tokens', false, true);
        if($updateCache || (false === $aResult)){
            evxProfiler::checkpoint('getTokens', 'START');
            if($updateCache){
                $aPrevTokens = $this->oCache->get('tokens', false, true);
                if(!is_array($aPrevTokens)){
                    $aPrevTokens = array();
                }
            }
            $this->_cliDebug("prevTokens count = " . count($aPrevTokens));
            $cursor = $this->oMongo->find('tokens', array(), array("transfersCount" => -1));
            $aResult = array();
            foreach($cursor as $index => $aToken){
                $address = $aToken["address"];
                $this->_cliDebug("Token #" . $index . " / " . $address);
                unset($aToken["_id"]);
                $aResult[$address] = $aToken;
                if(!isset($aPrevTokens[$address]) || ($aPrevTokens[$address]['transfersCount'] < $aToken['transfersCount'])){
                    if(defined('ETHPLORER_SHOW_OUTPUT')){
                        echo $address . " was recently updated (transfers count = " . $aToken['transfersCount'] . ")\n";
                    }
                    $aResult[$address]['issuancesCount'] = $this->getContractOperationCount(array('$in' => array('issuance', 'burn', 'mint')), $address, FALSE);
                    $aResult[$address]['holdersCount'] = $this->getTokenHoldersCount($address);
                }else if(!isset($aPrevTokens[$address]) || !isset($aPrevTokens[$address]['issuancesCount'])){
                    $aResult[$address]['issuancesCount'] = $this->getContractOperationCount(array('$in' => array('issuance', 'burn', 'mint')), $address, FALSE);
                }else{
                    $aResult[$address]['issuancesCount'] = isset($aPrevTokens[$address]['issuancesCount']) ? $aPrevTokens[$address]['issuancesCount'] : 0;
                    $aResult[$address]['holdersCount'] = isset($aPrevTokens[$address]['holdersCount']) ? $aPrevTokens[$address]['holdersCount'] : 0;
                }
                if(isset($this->aSettings['client']) && isset($this->aSettings['client']['tokens'])){
                    $aClientTokens = $this->aSettings['client']['tokens'];
                    if(isset($aClientTokens[$address])){
                        $aResult[$address] = array_merge($aResult[$address], $aClientTokens[$address]);
                    }
                }
                if(isset($aResult[$address]['name'])){
                    $aResult[$address]['name'] = htmlspecialchars($aResult[$address]['name']);
                }
                if(isset($aResult[$address]['symbol'])){
                    $aResult[$address]['symbol'] = htmlspecialchars($aResult[$address]['symbol']);
                }

                $cursor = $this->oMongo->find('addressCache', array("address" => $address));
                $aCachedData = false;
                foreach($cursor as $aCachedData) break;
                if(false !== $aCachedData){
                    $aResult[$address]['txsCount'] = $aCachedData['txsCount'];
                }
            }
            if(isset($aResult['0x0000000000000000000000000000000000000000'])){
                unset($aResult['0x0000000000000000000000000000000000000000']);
            }
            $this->oCache->save('tokens', $aResult);
            evxProfiler::checkpoint('getTokens', 'FINISH');
        }
        $this->aTokens = $aResult;
        return $aResult;
    }


    public function getTokenHoldersCount($address, $useFilter = TRUE){
        evxProfiler::checkpoint('getTokenHoldersCount', 'START', 'address=' . $address);
        $cache = 'getTokenHoldersCount-' . $address;
        if($useFilter && $this->filter){
            $cache .= $this->filter;
        }
        $result = $this->oCache->get($cache, false, true, 120);
        if(FALSE === $result){
            $search = array('contract' => $address, 'balance' => array('$gt' => 0));
            if($useFilter && $this->filter){
                $search = array(
                    '$and' => array(
                        $search,
                        array('address' => array('$regex' => $this->filter)),
                    )
                );
            }
            $result = $this->oMongo->count('balances', $search);
            $this->oCache->save($cache, $result);
        }
        evxProfiler::checkpoint('getTokenHoldersCount', 'FINISH');
        return $result;
    }

    /**
     * Returns list of token holders.
     *
     * @param string $address
     * @param int $limit
     * @return array
     */
    public function getTokenHolders($address, $limit = FALSE, $offset = FALSE){
        evxProfiler::checkpoint('getTokenHolders', 'START', 'address=' . $address . ', limit=' . $limit . ', offset=' . $offset);
        $result = array();
        $token = $this->getToken($address);
        if($token){
            $search = array('contract' => $address, 'balance' => array('$gt' => 0));
            if($this->filter){
                $search = array(
                    '$and' => array(
                        $search,
                        array('address' => array('$regex' => $this->filter)),
                    )
                );
            }
            $cursor = $this->oMongo->find('balances', $search, array('balance' => -1), $limit, $offset);
            if($cursor){
                $total = 0;
                $aBalances = [];
                foreach($cursor as $balance){
                    $aBalances[] = $balance;
                }                
                foreach($aBalances as $balance){
                    $total += floatval($balance['balance']);
                }
                if($total > 0){
                    if(isset($token['totalSupply']) && ($total < $token['totalSupply'])){
                        $total = $token['totalSupply'];
                    }
                    foreach($aBalances as $balance){
                        $result[] = array(
                            'address' => $balance['address'],
                            'balance' => floatval($balance['balance']),
                            'share' => round((floatval($balance['balance']) / $total) * 100, 2)
                        );
                    }
                }
            }
        }
        evxProfiler::checkpoint('getTokenHolders', 'FINISH');
        return $result;
    }

    /**
     * Returns token data by contract address.
     *
     * @param string  $address  Token contract address
     * @return array
     */
    public function getToken($address){
        // evxProfiler::checkpoint('getToken', 'START', 'address=' . $address);
        $cache = 'token-' . $address;
        $result = $this->oCache->get($cache, false, true, 30);
        if(FALSE === $result){
            $aTokens = $this->getTokens();
            $result = isset($aTokens[$address]) ? $aTokens[$address] : false;
            if($result){
                unset($result["_id"]);
                $result += array('txsCount' => 0, 'transfersCount' => 0, 'issuancesCount' => 0, 'holdersCount' => 0, "symbol" => "");
                if(!isset($result['decimals']) || !intval($result['decimals'])){
                    $result['decimals'] = 0;
                    if(isset($result['totalSupply']) && ((float)$result['totalSupply'] > 1e+18)){
                        $result['decimals'] = 18;
                        $result['estimatedDecimals'] = true;
                    }
                }

                // Ask DB for fresh counts
                $cursor = $this->oMongo->find('tokens', array('address' => $address), array(), false, false, array('txsCount', 'transfersCount'));
                $token = false;
                if($cursor){
                    foreach($cursor as $token){
                        break;
                    }
                }
                if($token){
                    $result['txsCount'] = $token['txsCount'];
                    $result['transfersCount'] = $token['transfersCount'];
                }
                
                $result['txsCount'] = (int)$result['txsCount'] + 1; // Contract creation tx
                
                if(isset($this->aSettings['client']) && isset($this->aSettings['client']['tokens'])){
                    $aClientTokens = $this->aSettings['client']['tokens'];
                    if(isset($aClientTokens[$address])){
                        $aClientToken = $aClientTokens[$address];
                        if(isset($aClientToken['name'])){
                            $result['name'] = $aClientToken['name'];
                        }
                        if(isset($aClientToken['symbol'])){
                            $result['symbol'] = $aClientToken['symbol'];
                        }
                    }
                }
                $this->oCache->save($cache, $result);
            }
        }
        if(is_array($result)){
            $price = $this->getTokenPrice($address);
            if(is_array($price)){
                $price['currency'] = 'USD';
            }
            $result['price'] = $price ? $price : false;
        }
        // evxProfiler::checkpoint('getToken', 'FINISH');
        return $result;
    }

    /**
     * Returns contract data by contract address.
     *
     * @param string $address
     * @return array
     */
    public function getContract($address, $calculateTransactions = TRUE){
        evxProfiler::checkpoint('getContract', 'START', 'address=' . $address);
        $cursor = $this->oMongo->find('contracts', array("address" => $address));
        $result = false;
        foreach($cursor as $result) break;
        if($result && $calculateTransactions){
            unset($result["_id"]);
            unset($result["code"]);
            if($calculateTransactions){
                evxProfiler::checkpoint('getContract CalculateTransactions', 'START', 'address=' . $address);
                $cache = 'contractTransactionsCount-' . $address;
                $count = $this->oCache->get($cache, false, true, 600);
                if(FALSE === $count){
                    $token = false;
                    if($token = $this->getToken($address)){
                        $count = isset($token['txsCount']) ? $token['txsCount'] : 0;
                    }
                    if(!$token || !$count){
                        $count = $this->countTransactions($address);
                    }
                    $this->oCache->save($cache, $count);
                }
                $result['txsCount'] =  $count;
                evxProfiler::checkpoint('getContract CalculateTransactions', 'FINISH', $count . ' transactions');
            }
            if($this->isChainyAddress($address)){
                $result['isChainy'] = true;
            }
        }
        evxProfiler::checkpoint('getContract', 'FINISH');
        return $result;
    }

    /**
     * Returns total number of token transfers for the address.
     *
     * @param string $address  Contract address
     * @return int
     */
    public function countOperations($address, $useFilter = TRUE){        
        evxProfiler::checkpoint('countOperations', 'START', 'address=' . $address . ', useFilter = ' . ($useFilter ? 'ON' : 'OFF'));
        $cache = 'countOperations-' . $address;
        $result = $this->oCache->get($cache, false, true, 30);
        if(FALSE === $result){
            $result = 0;
            if($token = $this->getToken($address)){
                $result = $this->getContractOperationCount('transfer', $address, $useFilter);
            }else{
                $cursor = $this->oMongo->find('addressCache', array("address" => $address));
                $aCachedData = false;
                foreach($cursor as $aCachedData) break;                
                if(false !== $aCachedData){
                    evxProfiler::checkpoint('countTransfersFromCache', 'START', 'address=' . $address);
                    $result = $aCachedData['transfersCount'];
                    evxProfiler::checkpoint('countTransfersFromCache', 'FINISH', 'count=' . $result);
                }else{
                    $aSearchFields = array('from', 'to', 'address');
                    foreach($aSearchFields as $searchField){
                        $search = array($searchField => $address);
                        if($useFilter && $this->filter){
                            $search = array(
                                '$and' => array(
                                    $search,
                                    array(
                                        '$or' => array(
                                            array('from'                => array('$regex' => $this->filter)),
                                            array('to'                  => array('$regex' => $this->filter)),
                                            array('address'             => array('$regex' => $this->filter)),
                                            array('transactionHash'     => array('$regex' => $this->filter)),
                                        )
                                    )
                                )
                            );
                        }
                        $result += $this->oMongo->count('operations', $search);

                        $search['type'] = array('$eq' => array('approve'));
                        $approves = $this->oMongo->count('operations', $search);
                        if($approves){
                            $result -= $approves;
                        }
                    }
                }
            }
            $this->oCache->save($cache, $result);
        }
        evxProfiler::checkpoint('countOperations', 'FINISH');
        return $result;
    }

    public function isHighloadedAddress($address){
        $cache = 'highloaded-address-' . $address;
        $result = $this->oCache->get($cache, false, true);
        if(FALSE === $result){
            // ).limit(10000).count({ applySkipLimit: true })
            $count = $this->countTransactions($address, 10000);
            if($count >= 10000){
                $result = true;
                $this->oCache->save($cache, $result);
            }
        }
        return $result;
    }


    /**
     * Returns total number of transactions for the address (incoming, outoming, contract creation).
     *
     * @param string $address  Contract address
     * @return int
     */
    public function countTransactions($address, $limit = FALSE){
        $cache = 'address-' . $address . '-txcnt';
        $result = $this->oCache->get($cache, false, true, 30);
        if(FALSE === $result){
            evxProfiler::checkpoint('countTransactions', 'START', 'address=' . $address);
            $result = 0;
            if($token = $this->getToken($address)){
                $result = $token['txsCount'];
                $result++; // One for contract creation
            } else { 
                $cursor = $this->oMongo->find('addressCache', array("address" => $address));
                $aCachedData = false;
                foreach($cursor as $aCachedData) break;
                if(false !== $aCachedData){
                    evxProfiler::checkpoint('countTransactionsFromCache', 'START', 'address=' . $address);
                    $result = $aCachedData['txsCount'];
                    evxProfiler::checkpoint('countTransactionsFromCache', 'FINISH', 'count=' . $result);
                }else{
                    foreach(array('from', 'to') as $where){
                        $search = array($where => $address);
                        $result += $this->oMongo->count('transactions', $search);
                    }
                }
            }
            $this->oCache->save($cache, $result);
            evxProfiler::checkpoint('countTransactions', 'FINISH', $result . ' transactions');
        }
        return $result;
    }

    /**
     * Returns list of contract transfers.
     *
     * @param string $address  Contract address
     * @param int $limit       Maximum number of records
     * @return array
     */
    public function getContractTransfers($address, $limit = 10, $offset = FALSE){
        return $this->getContractOperation('transfer', $address, $limit, $offset);
    }

    /**
     * Returns list of contract issuances.
     *
     * @param string $address  Contract address
     * @param int $limit       Maximum number of records
     * @return array
     */
    public function getContractIssuances($address, $limit = 10, $offset = FALSE){
        return $this->getContractOperation(array('$in' => array('issuance', 'burn', 'mint')), $address, $limit, $offset);
    }

    /**
     * Returns last known mined block number.
     *
     * @return int
     */
    public function getLastBlock($updateCache = FALSE){
        evxProfiler::checkpoint('getLastBlock', 'START');
        $lastblock = $this->oCache->get('lastBlock', false, true, 300);
        if($updateCache || (false === $lastblock)){
            $cursor = $this->oMongo->find('blocks', array(), array('number' => -1), 1, false, array('number'));
            $block = false;
            foreach($cursor as $block) break;            
            $lastblock = $block && isset($block['number']) ? $block['number'] : false;
            $this->oCache->save('lastBlock', $lastblock);
        }
        evxProfiler::checkpoint('getLastBlock', 'FINISH');
        return $lastblock;
    }

    /**
     * Returns address token balances.
     *
     * @param string $address  Address
     * @param bool $withZero   Returns zero balances if true
     * @return array
     */
    public function getAddressBalances($address, $withZero = true){
        evxProfiler::checkpoint('getAddressBalances', 'START', 'address=' . $address);
        $cache = 'getAddressBalances-' . $address;
        $result = $this->oCache->get($cache, false, true, 60);
        if(FALSE === $result){
            $search = array("address" => $address);
            if(!$withZero){
                $search['balance'] = array('$gt' => 0);
            }
            // $search['totalIn'] = array('$gt' => 0);
            $cursor = $this->oMongo->find('balances', $search, array(), false, false, array('contract', 'balance', 'totalIn', 'totalOut'));
            $result = array();
            foreach($cursor as $balance){
                unset($balance["_id"]);
                $result[] = $balance;
            }
            $this->oCache->save($cache, $result);
        }
        evxProfiler::checkpoint('getAddressBalances', 'FINISH');
        return $result;
    }

    /**
     * Returns list of transfers made by specified address.
     *
     * @param string $address  Address
     * @param int $limit       Maximum number of records
     * @return array
     */
    public function getLastTransfers(array $options = array()){
        $search = array();
        if(!isset($options['type'])){
            $search['type'] = 'transfer';
        }else{
            if(FALSE !== $options['type']){
                $search['type'] = $options['type'];
            }
        }
        if(isset($options['address']) && !isset($options['history'])){
            $search['contract'] = $options['address'];
        }
        if(isset($options['address']) && isset($options['history'])){
            $search['addresses'] = $options['address'];
        }

        if(isset($options['token']) && isset($options['history'])){
            $search['contract'] = $options['token'];
        }

        $sort = array("timestamp" => -1);

        if(isset($options['timestamp']) && ($options['timestamp'] > 0)){
            $search['timestamp'] = array('$gt' => $options['timestamp']);
        }
        $limit = isset($options['limit']) ? (int)$options['limit'] : false;
        $cursor = $this->oMongo->find('operations', $search, $sort, $limit);

        $result = array();
        foreach($cursor as $transfer){
            $transfer['token'] = $this->getToken($transfer['contract']);
            unset($transfer["_id"]);
            $result[] = $transfer;
        }
        return $result;
    }

    /**
     * Returns list of transfers made by specified address.
     *
     * @param string $address  Address
     * @param int $limit       Maximum number of records
     * @return array
     */
    public function getAddressOperations($address, $limit = 10, $offset = false, array $aTypes = NULL){
        evxProfiler::checkpoint('getAddressOperations', 'START', 'address=' . $address . ', limit=' . $limit . ', offset=' . (int)$offset);

        $result = array();
        $search = array('addresses' => $address);

        // @todo: remove $or, use special field with from-to-address-txHash concatination maybe
        if($this->filter){
            $search = array(
                '$and' => array(
                    $search,
                    array(
                        '$or' => array(
                            array('addresses'           => array('$regex' => $this->filter)),
                            array('transactionHash'     => array('$regex' => $this->filter)),
                        )
                    )
                )
            );
        }

        if($aTypes && is_array($aTypes) && count($aTypes)){
            $search['type'] = array('$in' => $aTypes);
        }

        $cursor = $this->oMongo->find('operations', $search, array("timestamp" => -1), $limit, $offset);
        foreach($cursor as $transfer){
            if(is_null($aTypes) || in_array($transfer['type'], $aTypes)){
                unset($transfer["_id"]);
                $result[] = $transfer;
            }
        }
        evxProfiler::checkpoint('getAddressOperations', 'FINISH');
        return $result;
    }

    /**
     * Returns data of operations made by specified address for downloading in CSV format.
     *
     * @param string $address  Address
     * @param string $type     Operations type
     * @return array
     */
    public function getAddressOperationsCSV($address, $type = 'transfer'){
        $limit = 1000;

        $cache = 'address_operations_csv-' . $address . '-' . $limit;
        $result = $this->oCache->get($cache, false, true, 600);
        if(FALSE === $result){
            $cr = "\r\n";
            $spl = ";";
            $result = 'date;txhash;from;to;token-name;token-address;value;symbol' . $cr;

            $options = array(
                'address' => $address,
                'type' => $type,
                'limit' => $limit
            );
            $aTokens = array();
            $addTokenInfo = true;
            $isContract = $this->getContract($address);
            if($isContract){
                $addTokenInfo = false;
            }
            $ten = Decimal::create(10);
            $dec = false;
            $tokenName = '';
            $tokenSymbol = '';
            $isToken = $this->getToken($address);
            if($isToken){
                $operations = $this->getLastTransfers($options);
                $dec = Decimal::create($isToken['decimals']);
                $tokenName = isset($isToken['name']) ? $isToken['name'] : '';
                $tokenSymbol = isset($isToken['symbol']) ? $isToken['symbol'] : '';
            }else{
                $operations = $this->getAddressOperations($address, $limit, FALSE, array('transfer'));
            }
            $aTokenInfo = array();
            foreach($operations as $record){
                $date = date("Y-m-d H:i:s", $record['timestamp']);
                $hash = $record['transactionHash'];
                $from = isset($record['from']) ? $record['from'] : '';
                $to = isset($record['to']) ? $record['to'] : '';
                $tokenAddress = '';
                if($addTokenInfo && isset($record['contract'])){
                    $tokenName = '';
                    $tokenSymbol = '';
                    $contract = $record['contract'];
                    $token = isset($aTokenInfo[$contract]) ? $aTokenInfo[$contract] : $this->getToken($contract);
                    if($token){
                        $tokenName = isset($token['name']) ? $token['name'] : '';
                        $tokenSymbol = isset($token['symbol']) ? $token['symbol'] : '';
                        $tokenAddress = isset($token['address']) ? $token['address'] : '';
                        if(isset($token['decimals'])) $dec = Decimal::create($token['decimals']);
                        if(!isset($aTokenInfo[$contract])) $aTokenInfo[$contract] = $token;
                    }
                }
                $value = $record['value'];
                if($dec){
                    $value = Decimal::create($record['value']);
                    $value = $value->div($ten->pow($dec), 4);
                }
                $result .= $date . $spl . $hash . $spl . $from . $spl . $to . $spl . $tokenName . $spl . $tokenAddress . $spl . $value . $spl . $tokenSymbol . $cr;
            }
            $this->oCache->save($cache, $result);
        }
        return $result;
    }

    /**
     * Returns top tokens list.
     *
     * @todo: count number of transactions with "transfer" operation
     * @param int $limit         Maximum records number
     * @param int $period        Days from now
     * @param bool $updateCache  Force unexpired cache update
     * @return array
     */
    public function getTopTokens($limit = 10, $period = 30, $updateCache = false){
        $cache = 'top_tokens-' . $period . '-' . $limit;
        $result = $this->oCache->get($cache, false, true, 24 * 3600);
        if($updateCache || (FALSE === $result)){
            $result = array();
            $prevData = $this->oMongo->aggregate(
                'operations',
                array(
                    array('$match' => array("timestamp" => array('$gt' => time() - $period * 2 * 24 * 3600, '$lte' => time() - $period * 24 * 3600))),
                    array(
                        '$group' => array(
                            "_id" => '$contract',
                            'cnt' => array('$sum' => 1)
                        )
                    ),
                    array('$sort' => array('cnt' => -1)),
                    array('$limit' => $limit)
                )
            );
            $dbData = $this->oMongo->aggregate(
                'operations',
                array(
                    array('$match' => array("timestamp" => array('$gt' => time() - $period * 24 * 3600))),
                    array(
                        '$group' => array(
                            "_id" => '$contract',
                            'cnt' => array('$sum' => 1)
                        )
                    ),
                    array('$sort' => array('cnt' => -1)),
                    array('$limit' => $limit)
                )
            );
            if(is_array($dbData) && !empty($dbData['result'])){
                foreach($dbData['result'] as $token){
                    $oToken = $this->getToken($token['_id']);
                    $oToken['opCount'] = $token['cnt'];
                    unset($oToken['checked']);
                    $result[] = $oToken;
                }
                $this->oCache->save($cache, $result);
            }
        }
        return $result;
    }

    /**
     * Returns top tokens list (new).
     *
     * @param int $limit         Maximum records number
     * @param string $criteria   Sort criteria
     * @param bool $updateCache  Force unexpired cache update
     * @return array
     */
    public function getTokensTop($limit = 50, $criteria = 'trade', $updateCache = false){
        $topLimit = 100;
        if($limit > $topLimit) $limit = $topLimit;
        $cache = 'top_tokens_' . $criteria;
        $result = $this->oCache->get($cache, false, true);
        if($updateCache || (FALSE === $result)){
            $aTokens = $this->getTokens();
            $result = array();
            $total = 0;
            $aPeriods = array(
                array('period' => 1),
                array('period' => 7),
                array('period' => 30)
            );
            foreach($aPeriods as $idx => $aPeriod){
                $period = $aPeriod['period'];
                $aPeriods[$idx]['currentPeriodStart'] = date("Y-m-d", time() - $period * 24 * 3600);
                $aPeriods[$idx]['previousPeriodStart'] = date("Y-m-d", time() - $period * 48 * 3600);
            }

            $aTokensCount = array();
            if($criteria == 'count'){
                $aTokensCountRes = $this->getTokensCountForLastDay($topLimit);
                foreach($aTokensCountRes as $aTokensCountRecord){
                    $aTokensCount[$aTokensCountRecord['_id']] = $aTokensCountRecord['cnt'];
                }
            }

            foreach($aTokens as $aToken){
                $address = $aToken['address'];
                $curHour = (int)date('H');

                if($criteria == 'count'){
                    if(isset($aTokensCount[$address])){
                        $aToken['txsCount24'] = $aTokensCount[$address];
                        foreach($aPeriods as $aPeriod){
                            $period = $aPeriod['period'];
                            $aToken['txsCount-' . $period . 'd-current'] = 0;
                            $aToken['txsCount-' . $period . 'd-previous'] = 0;
                        }
                        //get tx's 1d trends
                        $aHistoryCount = $this->getTokenHistoryGrouped(2, $address, 'hourly', 3600);
                        if(is_array($aHistoryCount)){
                            foreach($aHistoryCount as $aRecord){
                                $aPeriod = $aPeriods[0];
                                $aRecordDate = date("Y-m-d", $aRecord['ts']);
                                $inCurrentPeriod = ($aRecordDate > $aPeriod['currentPeriodStart']) || (($aRecordDate == $aPeriod['currentPeriodStart']) && ($aRecord['_id']->hour >= $curHour));
                                $inPreviousPeriod = !$inCurrentPeriod && (($aRecordDate > $aPeriod['previousPeriodStart']) || (($aRecordDate == $aPeriod['previousPeriodStart']) && ($aRecord['_id']->hour >= $curHour)));
                                if($inCurrentPeriod){
                                    $aToken['txsCount-1d-current'] += $aRecord['cnt'];
                                }else if($inPreviousPeriod){
                                    $aToken['txsCount-1d-previous'] += $aRecord['cnt'];
                                }
                            }
                        }
                        if(!$aToken['name']) $aToken['name'] = 'N/A';
                        if(!$aToken['symbol']) $aToken['symbol'] = 'N/A';
                        $result[] = $aToken;
                    }
                    continue;
                }

                $aPrice = $this->getTokenPrice($address);
                if($aPrice && $aToken['totalSupply']){
                    $aToken['volume'] = 0;
                    $aToken['cap'] = 0;
                    $aToken['availableSupply'] = 0;
                    $aToken['price'] = $aPrice;
                    if(isset($aPrice['marketCapUsd'])){
                        $aToken['cap'] = $aPrice['marketCapUsd'];
                    }
                    if(isset($aPrice['availableSupply'])){
                        $aToken['availableSupply'] = $aPrice['availableSupply'];
                    }
                    foreach($aPeriods as $aPeriod){
                        $period = $aPeriod['period'];
                        $aToken['volume-' . $period . 'd-current'] = 0;
                        $aToken['volume-' . $period . 'd-previous'] = 0;
                        $aToken['cap-' . $period . 'd-current'] = $aPrice['rate'];
                        $aToken['cap-' . $period . 'd-previous'] = 0;
                        $aToken['cap-' . $period . 'd-previous-ts'] = 0;
                    }
                    $aHistory = $this->getTokenPriceHistory($address, 60, 'hourly');
                    if(is_array($aHistory)){

                        foreach($aHistory as $aRecord){
                            foreach($aPeriods as $aPeriod){
                                $period = $aPeriod['period'];
                                $inCurrentPeriod = ($aRecord['date'] > $aPeriod['currentPeriodStart']) || (($aRecord['date'] == $aPeriod['currentPeriodStart']) && ($aRecord['hour'] >= $curHour ));
                                $inPreviousPeriod = !$inCurrentPeriod && (($aRecord['date'] > $aPeriod['previousPeriodStart']) || (($aRecord['date'] == $aPeriod['previousPeriodStart']) && ($aRecord['hour'] >= $curHour )));
                                if($inCurrentPeriod){
                                    $aToken['volume-' . $period . 'd-current'] += $aRecord['volumeConverted'];
                                    if(1 == $period){
                                        $aToken['volume'] += $aRecord['volumeConverted'];
                                    }
                                }else if($inPreviousPeriod){
                                    $aToken['volume-' . $period . 'd-previous'] += $aRecord['volumeConverted'];

                                    // if no data from coinmarketcap
                                    if(!$aToken['cap-' . $period . 'd-previous-ts'] || $aToken['cap-' . $period . 'd-previous-ts'] < $aRecord['ts']){
                                        if($aRecord['volumeConverted'] > 0 && $aRecord['volume'] > 0){
                                            $aToken['cap-' . $period . 'd-previous-ts'] = $aRecord['ts'];
                                            $aToken['cap-' . $period . 'd-previous'] = $aRecord['volumeConverted'] / $aRecord['volume'];
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if(isset($aPrice['volume24h']) && $aPrice['volume24h'] > 0){
                        $aToken['volume'] = $aToken['volume-1d-current'] = $aPrice['volume24h'];
                    }
                    $result[] = $aToken;
                }
            }
            $sortMethod = '_sortByVolume';
            if($criteria == 'cap') $sortMethod = '_sortByCap';
            if($criteria == 'count') $sortMethod = '_sortByTxCount';
            usort($result, array($this, $sortMethod));

            $res = [];
            foreach($result as $i => $item){
                if($i < $topLimit){
                    // $item['percentage'] = round(($item['volume'] / $total) * 100);

                    // get tx's other trends
                    if($criteria == 'count'){
                        unset($aPeriods[0]);
                        $aHistoryCount = $this->getTokenHistoryGrouped(60, $item['address'], 'daily', 3600);
                        if(is_array($aHistoryCount)){
                            foreach($aHistoryCount as $aRecord){
                                foreach($aPeriods as $aPeriod){
                                    $period = $aPeriod['period'];
                                    $aRecordDate = date("Y-m-d", $aRecord['ts']);
                                    $inCurrentPeriod = ($aRecordDate >= $aPeriod['currentPeriodStart']);
                                    $inPreviousPeriod = !$inCurrentPeriod && ($aRecordDate >= $aPeriod['previousPeriodStart']);
                                    if($inCurrentPeriod){
                                        $item['txsCount-' . $period . 'd-current'] += $aRecord['cnt'];
                                    }else if($inPreviousPeriod){
                                        $item['txsCount-' . $period . 'd-previous'] += $aRecord['cnt'];
                                    }
                                }
                            }
                        }
                    }

                    $res[] = $item;
                }
            }
            $result = $res;
            $this->oCache->save($cache, $result);
        }

        $res = [];
        if($limit < $topLimit){
            foreach($result as $i => $item){
                if($i < $limit){
                    $res[] = $item;
                }else{
                    break;
                }
            }
            $result = $res;
        }
        return $result;
    }

    /**
     * Returns top tokens list by current volume.
     *
     * @param int $limit   Maximum records number
     * @param int $period        Days from now
     * @param bool $updateCache  Force unexpired cache update
     * @return array
     */
    public function getTopTokensByPeriodVolume($limit = 10, $period = 30, $updateCache = false){
        set_time_limit(0);
        $cache = 'top_tokens-by-period-volume-' . $limit . '-' . $period;
        $result = $this->oCache->get($cache, false, true, 3600);
        $today = date("Y-m-d");
        $firstDay = date("Y-m-d", time() - $period * 24 * 3600);
        if($updateCache || (FALSE === $result)){
            $aTokens = $this->getTokens();
            $result = array();
            $total = 0;
            foreach($aTokens as $aToken){
                $address = $aToken['address'];
                $aPrice = $this->getTokenPrice($address);
                if($aPrice && $aToken['totalSupply']){
                    $aToken['volume'] = 0;
                    $aToken['previousPeriodVolume'] = 0;
                    $aHistory = $this->getTokenPriceHistory($address, $period * 2, 'daily');
                    if(is_array($aHistory)){
                        foreach($aHistory as $aRecord){
                            $aToken[($aRecord['date'] >= $firstDay) ? 'volume' : 'previousPeriodVolume'] += $aRecord['volumeConverted'];
                        }
                    }
                    $total += $aToken['volume'];
                    $result[] = $aToken;
                }
                usort($result, array($this, '_sortByVolume'));

                $res = [];
                foreach($result as $i => $item){
                    if($i < $limit){
                        $item['percentage'] = round(($item['volume'] / $total) * 100);
                        $res[] = $item;
                    }
                }
                $result = $res;
            }
            $this->oCache->save($cache, $result);
        }
        return $result;
    }

    /**
     * Returns top tokens list by current volume.
     *
     * @todo: count number of transactions with "transfer" operation
     * @param int $limit   Maximum records number
     * @return array
     */
    public function getTopTokensByCurrentVolume($limit = 10){
        $cache = 'top_tokens-by-current-volume-' . $limit;
        $result = $this->oCache->get($cache, false, true, 600);
        if(FALSE === $result){
            $aTokens = $this->getTokens();
            $result = array();
            foreach($aTokens as $aToken){
                $aPrice = $this->getTokenPrice($aToken['address']);
                if($aPrice && $aToken['totalSupply']){
                    // @todo: volume != totalSuply, volume is circulated supply (@see coinmarketcap)
                    $aToken['volume'] = $aPrice['rate'] * $aToken['totalSupply'] / pow(10, $aToken['decimals']);
                    $result[] = $aToken;
                }
                usort($result, array($this, '_sortByVolume'));
                $res = [];
                foreach($result as $i => $item){
                    if($i < $limit){
                        $res[] = $item;
                    }
                }
                $result = $res;
            }
            $this->oCache->save($cache, $result);
        }
        return $result;
    }

    protected function _sortByVolume($a, $b){
        return ($a['volume'] == $b['volume']) ? 0 : (($a['volume'] > $b['volume']) ? -1 : 1);
    }

    protected function _sortByCap($a, $b){
        return ($a['cap'] == $b['cap']) ? 0 : (($a['cap'] > $b['cap']) ? -1 : 1);
    }

    protected function _sortByTxCount($a, $b){
        return ($a['txsCount24'] == $b['txsCount24']) ? 0 : (($a['txsCount24'] > $b['txsCount24']) ? -1 : 1);
    }

    /**
     * Returns transactions grouped by days.
     *
     * @param int $period      Days from now
     * @param string $address  Address
     * @return array
     */
    public function getTokenHistoryGrouped($period = 30, $address = FALSE, $type = 'daily', $cacheLifetime = 600){
        $cache = 'token_history_grouped-' . ($address ? ($address . '-') : '') . $period . (($type == 'hourly') ? '-hourly' : '');
        $result = $this->oCache->get($cache, false, true, $cacheLifetime);
        if(FALSE === $result){
            // Chainy
            if($address && ($address == self::ADDRESS_CHAINY)){
                return $this->getChainyTokenHistoryGrouped($period);
            }

            $tsStart = gmmktime(0, 0, 0, date('n'), date('j') - $period, date('Y'));
            $aMatch = array("timestamp" => array('$gt' => $tsStart));
            if($address) $aMatch["contract"] = $address;
            $result = array();
            $_id = array(
                "year"  => array('$year' => array('$add' => array($this->oMongo->toDate(0), array('$multiply' => array('$timestamp', 1000))))),
                "month"  => array('$month' => array('$add' => array($this->oMongo->toDate(0), array('$multiply' => array('$timestamp', 1000))))),
                "day"  => array('$dayOfMonth' => array('$add' => array($this->oMongo->toDate(0), array('$multiply' => array('$timestamp', 1000))))),
            );
            if($type == 'hourly'){
                $_id['hour'] = array('$hour' => array('$add' => array($this->oMongo->toDate(0), array('$multiply' => array('$timestamp', 1000)))));
            }
            $dbData = $this->oMongo->aggregate(
                'operations',
                array(
                    array('$match' => $aMatch),
                    array(
                        '$group' => array(
                            "_id" => $_id,
                            'ts' =>  array('$first' => '$timestamp'),
                            'cnt' => array('$sum' => 1)
                        )
                    ),
                    array('$sort' => array('ts' => -1)),
                    //array('$limit' => 10)
                )
            );
            if(is_array($dbData) && !empty($dbData['result'])){
                $result = $dbData['result'];
                $this->oCache->save($cache, $result);
            }
        }
        return $result;
    }

    /**
     * Returns count transactions for last day grouped by tokens.
     *
     * @param int $limit  Number of tokens
     * @return array
     */
    public function getTokensCountForLastDay($limit = 30){
        $cache = 'tokens_count-' . $limit;
        $result = $this->oCache->get($cache, false, true, 3600);
        if(FALSE === $result){
            $tsStart = gmmktime((int)date('G'), 0, 0, date('n'), date('j') - 1, date('Y'));
            $aMatch = array("timestamp" => array('$gte' => $tsStart));
            $result = array();
            $dbData = $this->oMongo->aggregate(
                'operations',
                array(
                    array('$match' => $aMatch),
                    array(
                        '$group' => array(
                            "_id" => '$contract',
                            'cnt' => array('$sum' => 1)
                        )
                    ),
                    array('$sort' => array('cnt' => -1)),
                    array('$limit' => $limit)
                )
            );
            if(is_array($dbData) && !empty($dbData['result'])){
                $result = $dbData['result'];
                $this->oCache->save($cache, $result);
            }
        }
        return $result;
    }

    public function checkAPIKey($key){
        return isset($this->aSettings['apiKeys']) && isset($this->aSettings['apiKeys'][$key]);
    }

    public function getAPIKeyDefaults($key, $option = FALSE){
        $res = FALSE;
        if($this->checkAPIKey($key)){
            if(is_array($this->aSettings['apiKeys'][$key])){
                if(FALSE === $option){
                    $res = $this->aSettings['apiKeys'][$key];
                }else if(isset($this->aSettings['apiKeys'][$key][$option])){
                    $res = $this->aSettings['apiKeys'][$key][$option];
                }
            }
        }
        return $res;
    }

    /**
     * Returns contract operation data.
     *
     * @param string $type     Operation type
     * @param string $address  Contract address
     * @return array
     */
    protected function getContractOperationCount($type, $address, $useFilter = TRUE){
        evxProfiler::checkpoint('getContractOperationCount', 'START', 'address=' . $address . ', type=' . (is_array($type) ? var_export($type, TRUE) : $type) . ', useFilter=' . (int)$useFilter);
        $search = array("contract" => $address, 'type' => $type);
        $result = 0;
        if($useFilter && $this->filter){
            foreach(array('from', 'to', 'address', 'transactionHash') as $field){
                $result += $this->oMongo->count('operations', array_merge($search, array($field => array('$regex' => $this->filter))));
            }
        }else{
            if(('transfer' === $type) && ($aToken = $this->getToken($address))){
                $result = isset($aToken['transfersCount']) ? $aToken['transfersCount'] : 0;
            } else {            
                $result = $this->oMongo->count('operations', $search);
            }
        }
        evxProfiler::checkpoint('getContractOperationCount', 'FINISH');
        return $result;
    }

    /**
     * Returns contract operation data.
     *
     * @param string $type     Operation type
     * @param string $address  Contract address
     * @param string $limit    Maximum number of records
     * @return array
     */
    protected function getContractOperation($type, $address, $limit, $offset = FALSE){
        evxProfiler::checkpoint('getContractOperation', 'START', 'type=' . (is_array($type) ? var_export($type, TRUE) : $type) . ', address=' . $address . ', limit=' . $limit . ', offset=' . (int)$offset);
        $search = array("contract" => $address, 'type' => $type);
        if($this->filter){
            $search['$or'] = array(
                array('from'                => array('$regex' => $this->filter)),
                array('to'                  => array('$regex' => $this->filter)),
                array('address'             => array('$regex' => $this->filter)),
                array('transactionHash'     => array('$regex' => $this->filter))
            );
        }
        $cursor = $this->oMongo->find('operations', $search, array("timestamp" => -1), $limit, $offset);

        $result = array();
        $fetches = 0;
        foreach($cursor as $transfer){
            unset($transfer["_id"]);
            $result[] = $transfer;
            $fetches++;
        }
        evxProfiler::checkpoint('getContractOperation', 'FINISH');
        return $result;
    }

    public function getAllHolders(){
        $result = array();
        $dbHolders = $this->oMongo->aggregate(
            'balances',
            array(
                array('$group' => array("_id" => '$address')),
                array('$sort' => array('ts' => -1))
            )
        );
        if(is_array($dbHolders) && !empty($dbHolders['result'])){
            $result = $dbHolders['result'];
        }
        return $result;
    }


    /**
     * Returns last Chainy transactions.
     *
     * @param  int $limit  Maximum number of records
     * @return array
     */
    protected function getChainyTransactions($limit = 10, $offset = FALSE){
        $result = array();
        $search = array('to' => self::ADDRESS_CHAINY);
        if($this->filter){
            $search = array(
                '$and' => array(
                    $search,
                    array('hash' => array('$regex' => $this->filter)),
                )
            );
        }
        $cursor = $this->oMongo->find('transactions', $search, array("timestamp" => -1), $limit, $offset);
        foreach($cursor as $tx){
            if(!empty($tx['receipt']['logs'])){
                $link = substr($tx['receipt']['logs'][0]['data'], 194);
                $link = preg_replace("/0+$/", "", $link);
                if((strlen($link) % 2) !== 0){
                    $link = $link . '0';
                }
                $result[] = array(
                    'hash' => $tx['hash'],
                    'timestamp' => $tx['timestamp'],
                    'input' => $tx['input'],
                    'link' => $link,
                );
            }
        }
        return $result;
    }

    /**
     * Returns Chainy transactions grouped by days.
     *
     * @param  int $period  Number of days
     * @return array
     */
    protected function getChainyTokenHistoryGrouped($period = 30){
        $result = array();
        $aMatch = array(
            "timestamp" => array('$gt' => time() - $period * 24 * 3600),
            "to" => self::ADDRESS_CHAINY
        );
        $dbData = $this->oMongo->aggregate(
            'transactions',
            array(
                array('$match' => $aMatch),
                array(
                    '$group' => array(
                        "_id" => array(
                            "year"  => array('$year' => array('$add' => array($this->oMongo->toDate(0), array('$multiply' => array('$timestamp', 1000))))),
                            "month"  => array('$month' => array('$add' => array($this->oMongo->toDate(0), array('$multiply' => array('$timestamp', 1000))))),
                            "day"  => array( '$dayOfMonth' => array('$add' => array($this->oMongo->toDate(0), array('$multiply' => array('$timestamp', 1000))))),
                        ),
                        'ts' =>  array('$first' => '$timestamp'),
                        'cnt' => array('$sum' => 1)
                    )
                ),
                array('$sort' => array('ts' => -1))
            )
        );
        if(is_array($dbData) && !empty($dbData['result'])){
            $result = $dbData['result'];
        }
        return $result;
    }

    /**
     * Returns total number of Chainy operations for the address.
     *
     * @return int
     */
    public function countChainy($useFilter = TRUE){
        $search = array('to' => self::ADDRESS_CHAINY);
        if($useFilter && $this->filter){
            $search = array(
                '$and' => array(
                    $search,
                    array('hash' => array('$regex' => $this->filter)),
                )
            );
        }
        $result = $this->oMongo->count('transactions', $search);
        return $result;
    }

    public function getETHPrice(){
        evxProfiler::checkpoint('getETHPrice', 'START');
        $result = false;
        $eth = $this->getTokenPrice('0x0000000000000000000000000000000000000000');
        if(false !== $eth){
            $result = $eth;
        }
        evxProfiler::checkpoint('getETHPrice', 'FINISH');
        return $result;
    }

    public function getBlockTransactions($block, $showZero = false){
        $cache = 'block-txs-' . $block;
        $transactions = $this->oCache->get($cache, false, true);
        if(!$transactions){
            $transactions = array();
            $search = array('blockNumber' => $block);
            if(!$showZero){
                $search = array('$and' => array($search, array('value' => array('$gt' => 0))));
            }
            $cursor = $this->oMongo->find('transactions', $search, array("timestamp" => 1)/*, $limit*/);
            foreach($cursor as $tx){
                $receipt = isset($tx['receipt']) ? $tx['receipt'] : false;
                $tx['gasLimit'] = $tx['gas'];
                $tx['gasUsed'] = $receipt ? $receipt['gasUsed'] : 0;
                $transactions[] = array(
                    'timestamp' => $tx['timestamp'],
                    'from' => $tx['from'],
                    'to' => $tx['to'],
                    'hash' => $tx['hash'],
                    'value' => $tx['value'],
                    'success' => (($tx['gasUsed'] < $tx['gasLimit']) || ($receipt && !empty($receipt['logs'])))
                );
            }
            $this->oCache->save($cache, $transactions);
        }
        return $transactions;
    }

    public function getTokenPrice($address, $updateCache = FALSE){
        // evxProfiler::checkpoint('getTokenPrice', 'START', 'address=' . $address . ', updateCache=' . ($updateCache ? 'TRUE' : 'FALSE'));
        $result = FALSE;
        if(isset($this->aSettings['priceSource']) && isset($this->aSettings['priceSource'][$address])){
            $address = $this->aSettings['priceSource'][$address];
        }
        $isHidden = isset($this->aSettings['hidePrice']) && in_array($address, $this->aSettings['hidePrice']);
        $knownPrice = isset($this->aSettings['updateRates']) && in_array($address, $this->aSettings['updateRates']);

        if(!$isHidden && $knownPrice){
            $cache = 'rates';
            $rates = $this->oCache->get($cache, false, true);
            if($updateCache){
                if(!is_array($rates)){
                    $rates = array();
                }
                if(isset($this->aSettings['currency'])){
                    $method = 'getCurrencyCurrent';
                    $params = array($address, 'USD');
                    $result = $this->_jsonrpcall($this->aSettings['currency'], $method, $params);
                    if($result){
                        unset($result['code_from']);
                        unset($result['code_to']);
                        unset($result['bid']);
                        // THBEX price bug workaround
                        if('0xff71cb760666ab06aa73f34995b42dd4b85ea07b' === $address){
                            if($result['rate'] > 1){
                                $result = 1 / (float)$result['rate'];
                            }
                        }
                        $rates[$address] = $result;
                        $this->oCache->save($cache, $rates);
                    }
                }
            }
            if(is_array($rates) && isset($rates[$address])){
                $result = $rates[$address];
            }
        }
        // evxProfiler::checkpoint('getTokenPrice', 'FINISH');
        return $result;
    }

    public function getTokenPriceHistory($address, $period = 0, $type = 'hourly', $updateCache = FALSE){
        if(isset($this->aSettings['priceSource']) && isset($this->aSettings['priceSource'][$address])){
            $address = $this->aSettings['priceSource'][$address];
        }
        $isHidden = isset($this->aSettings['hidePrice']) && in_array($address, $this->aSettings['hidePrice']);
        $knownPrice = isset($this->aSettings['updateRates']) && in_array($address, $this->aSettings['updateRates']);
        if($isHidden || !$knownPrice){
            return FALSE;
        }
        evxProfiler::checkpoint('getTokenPriceHistory', 'START', 'address=' . $address . ', period=' . $period . ', type=' . $type);
        $rates = array();
        $cache = 'rates-history-' . /*($period > 0 ? ('period-' . $period . '-') : '' ) . ($type != 'hourly' ? $type . '-' : '') .*/ $address;
        $result = $this->oCache->get($cache, false, true);
        if($updateCache || (FALSE === $result)){
            if(isset($this->aSettings['currency'])){
                $method = 'getCurrencyHistory';
                $params = array($address, 'USD');
                $result = $this->_jsonrpcall($this->aSettings['currency'], $method, $params);
                if($result){
                    $aToken = $this->getToken($address);
                    $tokenStartAt = false;
                    if($aToken){
                        $patchFile = dirname(__FILE__) . '/../patches/price-' . $address . '.patch';
                        $aPatch = array();
                        if(file_exists($patchFile)){
                            $data = file_get_contents($patchFile);
                            $aData = json_decode($data, TRUE);
                            if($aData && count($aData)){
                                foreach($aData as $rec){
                                    $aPatch['ts-' . $rec['time']] = array(
                                        'high' => $rec['high'],
                                        'low' => $rec['low'],
                                        'open' => $rec['open'],
                                        'close' => $rec['close'],
                                        'volume' => $rec['volumefrom'],
                                        'volumeConverted' => $rec['volumeto']
                                    );
                                }
                            }
                        }
                        if(isset($aToken['createdAt'])){
                            $tokenStartAt = $aToken['createdAt'];
                        }
                        if(isset($this->aSettings['customTokenHistoryStart']) && isset($this->aSettings['customTokenHistoryStart'][$address])){
                            $tokenStartAt = $this->aSettings['customTokenHistoryStart'][$address];
                        }
                        for($i = 0; $i < count($result); $i++){
                            $zero = array('high' => 0, 'low' => 0, 'open' => 0, 'close' => 0, 'volume' => 0, 'volumeConverted' => 0);
                            if($result[$i]['ts'] < $tokenStartAt){
                                $result[$i] = array_merge($result[$i], $zero);
                            }
                            if(isset($aPatch['ts-' . $result[$i]['ts']])){
                                $result[$i] = array_merge($result[$i], $aPatch['ts-' . $result[$i]['ts']]);
                            }
                            // @temporary: EVX invalid history values fix
                            if('0xf3db5fa2c66b7af3eb0c0b782510816cbe4813b8' == $address){
                                if($result[$i]['high'] > 10){
                                   $result[$i]['high'] = $result[$i]['low'] * 1.2;
                                }
                            }
                        }
                    }
                }
                $this->oCache->save($cache, $result);
            }
        }
        if($result){
            $aPriceHistory = array();
            if($period){
                $tsStart = gmmktime(0, 0, 0, date('n'), date('j') - $period, date('Y'));
                for($i = 0; $i < count($result); $i++){
                    if($result[$i]['ts'] < $tsStart){
                        continue;
                    }
                    $aPriceHistory[] = $result[$i];
                }
            }else{
                $aPriceHistory = $result;
            }
            if($type == 'daily'){
                $aPriceHistoryDaily = array();
                $aDailyRecord = array();
                $curDate = '';
                $prevVol = 0;
                $prevVolC = 0;
                for($i = 0; $i < count($aPriceHistory); $i++){
                    $firstRecord = false;
                    $lastRecord = false;
                    if(!$curDate || ($curDate != $aPriceHistory[$i]['date'])){
                        $aDailyRecord = $aPriceHistory[$i];
                        $firstRecord = true;
                    }else{
                        if(($i == (count($aPriceHistory) - 1)) || ($aPriceHistory[$i]['date'] != $aPriceHistory[$i + 1]['date'])){
                            $lastRecord = true;
                        }
                        if($lastRecord){
                            $aDailyRecord['close'] = $aPriceHistory[$i]['close'];
                        }
                    }
                    if(!$firstRecord){
                        if($aPriceHistory[$i]['high'] > $aDailyRecord['high']){
                            $aDailyRecord['high'] = $aPriceHistory[$i]['high'];
                        }
                        if($aPriceHistory[$i]['low'] < $aDailyRecord['low']){
                            $aDailyRecord['low'] = $aPriceHistory[$i]['low'];
                        }
                        $aDailyRecord['volume'] += $aPriceHistory[$i]['volume'];
                        $aDailyRecord['volumeConverted'] += $aPriceHistory[$i]['volumeConverted'];
                        // $aDailyRecord['average'] = $aDailyRecord['volume'] ? ($aDailyRecord['volumeConverted'] / $aDailyRecord['volume']) : 0;
                    }
                    if($lastRecord){
                        // If volume goes up more than 10 mln times, we suppose it was a bug
                        if($prevVol && (($aDailyRecord['volume'] / $prevVol) > 1000000)){
                            $aDailyRecord['volume'] = $prevVol;
                        }
                        if($prevVolC && (($aDailyRecord['volumeConverted'] / $prevVolC) > 1000000)){
                            $aDailyRecord['volumeConverted'] = $prevVolC;
                        }
                        $aDailyRecord['average'] = $aDailyRecord['volume'] ? ($aDailyRecord['volumeConverted'] / $aDailyRecord['volume']) : 0;
                        $aPriceHistoryDaily[] = $aDailyRecord;
                        $prevVol = $aDailyRecord['volume'];
                        $prevVolC = $aDailyRecord['volumeConverted'];                        
                    }
                    $curDate = $aPriceHistory[$i]['date'];
                }
            }
            $rates[$address] = ($type == 'daily' ? $aPriceHistoryDaily : $aPriceHistory);
        }
        if(is_array($rates) && isset($rates[$address])){
            $result = $rates[$address];
        }
        evxProfiler::checkpoint('getTokenPriceHistory', 'FINISH');
        return $result;
    }

    protected function getTokenPriceCurrent($address){
        $this->_getRateByDate($address, date("Y-m-d"));
    }

    public function getTokenPriceHistoryGrouped($address, $period = 365, $type = 'daily', $updateCache = FALSE){
        $aResult = array();

        $aCurrent = $this->getTokenPrice($address);
        $aResult['current'] = $aCurrent;
        unset($aCurrent);

        $aHistoryCount = $this->getTokenHistoryGrouped($period, $address);
        $aResult['countTxs'] = $aHistoryCount;
        unset($aHistoryCount);

        $aHistoryPrices = $this->getTokenPriceHistory($address, $period, $type);
        $aResult['prices'] = $aHistoryPrices;
        unset($aHistory);

        return $aResult;
    }

    public function getAddressPriceHistoryGrouped($address, $updateCache = FALSE){
        evxProfiler::checkpoint('getAddressPriceHistoryGrouped', 'START', 'address=' . $address);

        $cache = 'address_operations_history-' . $address;
        $result = $this->oCache->get($cache, false, true);
        $updateCache = false;
        if($result && isset($result['timestamp'])){
            if(time() > ($result['timestamp'] + 3600)) $updateCache = true;
        }
        if(!isset($result['updCache'])){
            $result = false;
            $updateCache = false;
        }

        if(FALSE === $result || $updateCache){
            $aSearch = array('from', 'to', 'address');
            $aTypes = array('transfer', 'issuance', 'burn', 'mint');
            $aResult = array();
            $minTs = false;

            if(FALSE === $result){
                $result = array();
                //$result['cache'] = 'noCacheData';
            }else if($updateCache){
                $result['cache'] = 'cacheUpdated';
            }

            foreach($aSearch as $cond){
                $search = array($cond => $address);
                if($updateCache){
                    $search = array('$and' => array($search, array('timestamp' => array('$gt' => $result['timestamp']))));
                }

                $cursor = $this->oMongo->find('operations', $search, false, false, false, array('timestamp', 'value', 'contract', 'from', 'type'));
                foreach($cursor as $record){
                    $date = gmdate("Y-m-d", $record['timestamp']);
                    if(!isset($result['txs'][$date])){
                        $result['txs'][$date] = 0;
                    }
                    if($record['type'] == 'transfer'){
                        $result['txs'][$date] += 1;
                        if(!$updateCache && (!$minTs || ($record['timestamp'] < $minTs))){
                            $minTs = $record['timestamp'];
                            $result['firstDate'] = $date;
                        }
                    }

                    if((FALSE === array_search($record['contract'], $this->aSettings['updateRates'])) || !in_array($record['type'], $aTypes)){
                        continue;
                    }

                    $add = 0;
                    if(!$updateCache && (!$minTs || ($record['timestamp'] < $minTs))){
                        $minTs = $record['timestamp'];
                        $result['firstDate'] = $date;
                    }
                    if(($record['from'] == $address) || ($record['type'] == 'burn')){
                        $add = 1;
                    }
                    if(!isset($aResult[$record['timestamp']])) $aResult[$record['timestamp']] = array();
                    $aResult[$record['timestamp']][] = array($record['contract'], $record['value'], $add);
                }
            }
            krsort($aResult, SORT_NUMERIC);

            $aAddressBalances = $this->getAddressBalances($address);
            //file_put_contents(__DIR__ . '/../log/lexa.log', print_r($aAddressBalances, true) . "\n", FILE_APPEND);

            $ten = Decimal::create(10);

            if(isset($result['tokens'])) $aTokenInfo = $result['tokens'];
            else{
                $result['tokens'] = array();
                $aTokenInfo = array();
            }

            $curDate = false;
            unset($result['timestamp']);
            foreach($aResult as $ts => $aRecords){
                foreach($aRecords as $record){
                    $date = gmdate("Y-m-d", $ts);
                    $nextDate = false;
                    if($curDate && ($curDate != $date)){
                        $nextDate = true;
                    }

                    $contract = $record[0];
                    if(!isset($result['timestamp'])) $result['timestamp'] = $ts;

                    $token = isset($aTokenInfo[$contract]) ? $aTokenInfo[$contract] : $this->getToken($contract);
                    if($token){
                        if(!isset($aTokenInfo[$contract])){
                            $result['tokens'][$contract] = $token;
                            $aTokenInfo[$contract] = $token;
                        }

                        $dec = false;
                        if(isset($token['decimals'])) $dec = Decimal::create($token['decimals']);

                        $balance = Decimal::create(0);
                        if(!isset($aTokenInfo[$contract]['balance'])){
                            foreach($aAddressBalances as $addressBalance){
                                if($addressBalance["contract"] == $contract){
                                    $balance = Decimal::create($addressBalance["balance"]);
                                    if($dec){
                                        $balance = $balance->div($ten->pow($dec));
                                    }
                                    break;
                                }
                            }
                            $result['balances'][$date][$token['address']] = '' . $balance;
                        }else{
                            if($nextDate) $result['balances'][$date][$token['address']] = $aTokenInfo[$contract]['balance'];
                            $balance = Decimal::create($aTokenInfo[$contract]['balance']);
                        }

                        if($dec){
                            // operation value
                            $value = Decimal::create($record[1]);
                            $value = $value->div($ten->pow($dec));

                            // get volume
                            $curDateVolume = Decimal::create(0);
                            if(isset($result['volume'][$date][$token['address']])){
                                $curDateVolume = Decimal::create($result['volume'][$date][$token['address']]);
                            }
                            $curDateVolume = $curDateVolume->add($value);
                            $result['volume'][$date][$token['address']] = '' . $curDateVolume;

                            // get old balance
                            if(1 == $record[2]){
                                $oldBalance = $balance->add($value);
                            }else{
                                $oldBalance = $balance->sub($value);
                            }

                            $aTokenInfo[$contract]['balance'] = '' . $oldBalance;
                        }
                    }
                    $curDate = $date;
                }
            }
            if(!empty($result)){
                $result['updCache'] = 1;
                if(!isset($result['timestamp'])) $result['timestamp'] = time();
                $this->oCache->save($cache, $result);
            }
        }else{
            $result['cache'] = 'fromCache';
        }

        // get prices
        $aPrices = array();
        $result['tokenPrices'] = array();
        $maxTs = 0;
        foreach($result['tokens'] as $token => $data){
            $aPrices[$token] = $this->getTokenPriceHistory($token, 365, 'daily');
            if(!is_array($aPrices[$token]) || !count($aPrices[$token])){
                unset($aPrices[$token]);
                continue;
            }
            $result['tokenPrices'][$token] = $this->getTokenPrice($token);
            if(isset($result['tokenPrices'][$token]['ts']) && ($result['tokenPrices'][$token]['ts'] > $maxTs)){
                $maxTs = $result['tokenPrices'][$token]['ts'];
            }
        }
        if($maxTs) $result['updated'] = gmdate("Y-m-d H:i:s e", $maxTs);
        $result['prices'] = $aPrices;

        evxProfiler::checkpoint('getAddressPriceHistoryGrouped', 'FINISH');
        return $result;
    }

    protected function _getRateByTimestamp($address, $timestamp){
        $result = 0;
        $aHistory = $this->getTokenPriceHistory($address);
        if(is_array($aHistory)){
            foreach($aHistory as $aRecord){
                if(isset($aRecord['volume']) && $aRecord['volume']){
                    $ts = $aRecord['ts'];
                    if($ts <= $timestamp){
                        $result = $aRecord['volumeConverted'] / $aRecord['volume'];
                    }else{
                        break;
                    }
                }
            }
        }
        return $result;
    }

    protected function _getRateByDate($address, $date){
        $result = 0;
        $aHistory = $this->getTokenPriceHistory($address);
        $aHistoryByDate = array();
        if(is_array($aHistory)){
            foreach($aHistory as $aRecord){
                if(isset($aRecord['open'])){
                    $date = $aRecord['date'];
                    if(isset($aHistoryByDate[$date])){
                        continue;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * JSON RPC request implementation.
     *
     * @param string $method  Method name
     * @param array $params   Parameters
     * @return array
     */
    protected function _callRPC($method, $params = array()){
        if(!isset($this->aSettings['ethereum'])){
            throw new Exception("Ethereum configuration not found");
        }
        return $this->_jsonrpcall($this->aSettings['ethereum'], $method, $params);
    }

    protected function _jsonrpcall($service, $method, $params = array()){
        $data = array(
            'jsonrpc' => "2.0",
            'id'      => time(),
            'method'  => $method,
            'params'  => $params
        );
        $result = false;
        $json = json_encode($data);
        $ch = curl_init($service);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($json))
        );
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json );
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $rjson = curl_exec($ch);
        if($rjson && (is_string($rjson)) && ('{' === $rjson[0])){
            $json = json_decode($rjson, JSON_OBJECT_AS_ARRAY);
            if(isset($json["result"])){
                $result = $json["result"];
            }
        }
        return $result;
    }

    public function searchToken($token){
        $result = array('results' => array(), 'total' => 0);
        $found = array();
        $aTokens = $this->getTokens();
        $aTokens['0xf3763c30dd6986b53402d41a8552b8f7f6a6089b'] = array(
            'name' => 'Chainy',
            'symbol' => false,
            'txsCount' => 99999
        );
        if(isset($this->aSettings['client']) && isset($this->aSettings['client']['tokens'])){
            $aClientTokens = $this->aSettings['client']['tokens'];
            foreach($aClientTokens as $address => $aClientToken){
                if(isset($aTokens[$address])){
                    if(isset($aClientToken['name'])){
                        $aTokens[$address]['name'] = $aClientToken['name'];
                    }
                    if(isset($aClientToken['symbol'])){
                        $aTokens[$address]['symbol'] = $aClientToken['symbol'];
                    }
                }
            }
        }
        foreach($aTokens as $address => $aToken){
            $search = strtolower($token);
            if((strpos($address, $search) !== FALSE) || (!empty($aToken['name']) && (strpos(strtolower($aToken['name']), $search) !== FALSE)) || (!empty($aToken['symbol']) && (strpos(strtolower($aToken['symbol']), $search) !== FALSE))){
                $aToken['address'] = $address;
                $found[] = $aToken;
            }
        }
        uasort($found, array($this, 'sortTokensByTxsCount'));
        $i = 0;
        foreach($found as $aToken){
            if($i < 6){
                $aToken += array('name' => '', 'symbol' => '');
                $result['results'][] = array($aToken['name'], $aToken['symbol'], $aToken['address']);
            }
            $i++;
        }
        $result['total'] = $i;
        $result['search'] = $token;
        return $result;
    }

    public function sortTokensByTxsCount($a, $b) {
        if(!isset($a['txsCount'])){
            $a['txsCount'] = 0;
        }
        if(!isset($b['txsCount'])){
            $b['txsCount'] = 0;
        }
        if($a['txsCount'] == $b['txsCount']){
            return 0;
        }
        return ($a['txsCount'] < $b['txsCount']) ? 1 : -1;
    }

    public function getActiveNotes(){
        $result = array();
        if(isset($this->aSettings['adv'])){
            $all = $this->aSettings['adv'];
            foreach($all as $one){
                if(isset($one['activeTill'])){
                    if($one['activeTill'] <= time()){
                        continue;
                    }
                }
                $one['link'] = urlencode($one['link']);
                $one['hasNext'] = (count($all) > 1);
                $result[] = $one;
            }
        }
        return $result;
    }

    protected function _cliDebug($message){
        if(isset($this->aSettings['cliDebug']) && $this->aSettings['cliDebug'] && (php_sapi_name() === 'cli')){
            echo '[' . date("Y-m-d H:i:s") . '] ' . $message . "\n";
        }
    }
}
