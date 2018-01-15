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

/**
 * Mongo class.
 */
class evxMongo {

    /**
     * Instance object.
     *
     * @var evxMongo
     */
    protected static $oInstance;

    /**
     * Mongo driver.
     *
     * @var string
     */
    protected $driver;

    /**
     * MongoDB connection object.
     *
     * @var mixed
     */
    protected $oMongo;

    /**
     * Database name.
     *
     * @var string
     */
    protected $dbName;

    /**
     * Database collections set.
     *
     * @var array
     */
    protected $aDBs = array();

    /**
     * Logfile path.
     *
     * @var string
     */
    protected $logFile = __DIR__ . '/../log/mongo-profile.log';

    /**
     * Initialization.
     *
     * @param array $aSettings
     */
    public static function init(array $aSettings = array()){
        self::$oInstance = new evxMongo($aSettings);
    }

    /**
     * Constructor.
     *
     * @param array $aSettings
     * @throws \Exception
     */
    protected function __construct(array $aSettings){
        // Default config
        $aSettings += array(
            'driver' => 'mongodb',
            'server' => 'mongodb://127.0.0.1:27017',
            'dbName' => 'ethplorer',
            'prefix' => 'everex.'
        );
        $db = $this->dbName = $aSettings['dbName'];
        $prefix = $aSettings['prefix'];
        $start = microtime(true);
        switch($aSettings['driver']){
            // Fake mongo driver to run without real mongo instance
            case 'fake':
                // @todo: implement
                break;
            // php version <= 5.5
            case 'mongo':
                $this->oMongo = new MongoClient($aSettings['server']);
                $oDB = $this->oMongo->{$db};
                $this->aDBs = array(
                    'transactions' => $oDB->{$prefix . "eth.transactions"},
                    'blocks'       => $oDB->{$prefix . "eth.blocks"},
                    'contracts'    => $oDB->{$prefix . "eth.contracts"},
                    'tokens'       => $oDB->{$prefix . "erc20.contracts"},
                    'operations'   => $oDB->{$prefix . "erc20.operations"},
                    'balances'     => $oDB->{$prefix . "erc20.balances"}
                );
                break;
            // php version 5.6, 7.x use mongodb extension
            case 'mongodb':
                $this->oMongo = new MongoDB\Driver\Manager($aSettings['server']);
                if(isset($aSettings['newFormat']) && $aSettings['newFormat']){
                    $this->aDBs = array(
                        'transactions' => "transactions",
                        'blocks'       => "blocks",
                        'contracts'    => "contracts",
                        'tokens'       => "tokens",
                        'operations'   => "tokenOperations",
                        'balances'     => "tokenBalances",
                        'addressCache' => "cacheAddressData"
                    );
                }else{
                    $this->aDBs = array(
                        'transactions' => $prefix . "eth.transactions",
                        'blocks'       => $prefix . "eth.blocks",
                        'contracts'    => $prefix . "eth.contracts",
                        'tokens'       => $prefix . "erc20.contracts",
                        'operations'   => $prefix . "erc20.operations",
                        'balances'     => $prefix . "erc20.balances"
                    );
                }
                break;                
            default:
                throw new \Exception('Unknown mongodb driver ' . $dbDriver);
        }
        $finish = microtime(true);
        $qTime = $finish - $start;
        if($qTime > 0.1){
            $this->log('(' . ($qTime) . 's) Connection to ' . $aSettings['server']);
        }
        $this->driver = $aSettings['driver'];
    }

    /**
     * Converts timestamp to Mongo driver object.
     *
     * @param int $timestamp
     * @return \MongoDate|\MongoDB\BSON\UTCDateTime
     */
    public function toDate($timestamp = 0){
        $result = false;
        switch($this->driver){
            case 'fake':
                $result = $timestamp;
                break;

            case 'mongo':
                return new MongoDate($timestamp);
                break;

            case 'mongodb':
                return new MongoDB\BSON\UTCDateTime($timestamp);
                break;
        }
        return $result;
    }


    /**
     * MongoDB "find" method implementation.
     *
     * @param string $collection
     * @param array $aSearch
     * @param array $sort
     * @param int $limit
     * @param int $skip
     * @return array
     */
    public function find($collection, array $aSearch = array(), $sort = false, $limit = false, $skip = false, $fields = false){
        $aResult = false;
        $start = microtime(true);
        switch($this->driver){
            case 'fake':
                $aResult = array();
                break;

            case 'mongo':
                $cursor = is_array($fields) ? $this->aDBs[$collection]->find($aSearch, $fields) : $this->aDBs[$collection]->find($aSearch);
                if(is_array($sort)){
                    $cursor = $cursor->sort($sort);
                }
                if(false !== $skip){
                    $cursor = $cursor->skip($skip);
                }
                if(false !== $limit){
                    $cursor = $cursor->limit($limit);
                }
                $aResult = $cursor;
                break;

            case 'mongodb':
                $aOptions = array();
                if(is_array($sort)){
                    $aOptions['sort'] = $sort;
                }
                if(false !== $skip){
                    $aOptions['skip'] = $skip;
                }
                if(false !== $limit){
                    $aOptions['limit'] = $limit;
                }
                if((false !== $fields) && is_array($fields)){
                    $aOptions['projection'] = array();
                    foreach($fields as $field){
                        $aOptions['projection'][$field] = 1;
                    }
                }
                $query = new MongoDB\Driver\Query($aSearch, $aOptions);
                $cursor = $this->oMongo->executeQuery($this->dbName . '.' . $this->aDBs[$collection], $query);

                $cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);
                $aResult = new \IteratorIterator($cursor);
                $aResult->rewind();
                /*
                $cursor = MongoDB\BSON\fromPHP($cursor->toArray());
                $cursor = json_decode(MongoDB\BSON\toJSON($cursor), true);
                $aResult = $cursor;
                 */
                break;
        }
        $finish = microtime(true);
        $qTime = $finish - $start;
        if($qTime > 1){
            $this->log('(' . ($qTime) . 's) Find ' . $this->dbName . '.' . $this->aDBs[$collection] . ' > ' . json_encode($aSearch));
        }
        return $aResult;
    }

    /**
     * MongoDB "count" method implementation.
     *
     * @param string $collection
     * @param array $aSearch
     * @return int
     */
    public function count($collection, array $aSearch = array(), $limit = FALSE){
        $result = false;
        $start = microtime(true);
        $aOptions = array();
        if(FALSE !== $limit){
            $aOptions['limit'] = (int)$limit;
        }
        switch($this->driver){
            case 'fake':
                $result = 0;
                break;
            case 'mongo':
                $result = $this->aDBs[$collection]->count($aSearch, $aOptions);
                break;

            case 'mongodb':
                $query = new MongoDB\Driver\Query($aSearch, $aOptions);
                $cursor = $this->oMongo->executeQuery($this->dbName . '.' . $this->aDBs[$collection], $query);
                $result = iterator_count($cursor);
                /*
                $command = new MongoDB\Driver\Command(array("count" => $this->aDBs[$collection], "query" => $aSearch));
                $count = $this->oMongo->executeCommand($this->dbName, $command);
                $res = current($count->toArray());
                $result = $res->n;
                */
                /*
                $aOptions = array();
                $query = new MongoDB\Driver\Query($aSearch, $aOptions);
                $cursor = $this->oMongo->executeQuery($this->aDBs[$collection], $query);
                $result = iterator_count($cursor);
                */
                break;
        }
        $finish = microtime(true);
        $qTime = $finish - $start;
        if($qTime > 1){
            $this->log('(' . ($qTime) . 's) Count ' . $this->dbName . '.' . $this->aDBs[$collection] . ' > ' . json_encode($aSearch));
        }
        return $result;
    }

    /**
     * MongoDB "aggregate" method implementation.
     *
     * @param string $collection
     * @param array $aSearch
     * @param array $sort
     * @param int $limit
     * @param int $skip
     * @return array
     */
    public function aggregate($collection, array $aSearch = array()){
        $aResult = false;
        $start = microtime(true);
        switch($this->driver){
            case 'fake':
                $aResult = array();
                break;

            case 'mongo':
                $aResult = $this->aDBs[$collection]->aggregate($aSearch);
                break;

            case 'mongodb':
                $aResult = array();
                $command = new MongoDB\Driver\Command(array(
                    'aggregate' => $this->aDBs[$collection],
                    'pipeline' => $aSearch,
                    'cursor' => new stdClass,
                ));
                $cursor = $this->oMongo->executeCommand($this->dbName, $command);
                if(count($cursor) > 0){
                    $aResult['result'] = array();
                    $cursor = new IteratorIterator($cursor);
                    foreach($cursor as $record){
                        $aResult['result'][] = (array)$record;
                    }
                }
                break;
        }
        $finish = microtime(true);
        $qTime = $finish - $start;
        if($qTime > 1){
            $this->log('(' . ($qTime) . 's) Aggregate ' . $this->dbName . '.' . $this->aDBs[$collection] . ' > ' . json_encode($aSearch));
        }
        return $aResult;
    }

    /**
     * Singleton implementation.
     *
     * @param array $aSettings
     * @return type
     * @throws \Exception
     */
    public static function getInstance(array $aSettings = array()){
        if(is_null(self::$oInstance)){
            throw new \Exception('Mongo class was not initialized.');
        }
        return self::$oInstance;
    }

    protected function log($message){
        $logString = '[' . date('Y-m-d H:i:s') . '] - ' . $message . "\n";
        file_put_contents($this->logFile, $logString, FILE_APPEND);
    }
}
