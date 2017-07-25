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
 * Cache class.
 */
class evxCache {
    /**
     * Seconds in 1 hour
     */
    const HOUR = 3600;

    /**
     * Seconds in 30 days
     */
    const MONTH = 30 * 24 * 3600;

    /**
     * Cache storage.
     *
     * @var array
     */
    protected $aData = array();

    /**
     * Cache files path.
     *
     * @var string
     */
    protected $path;

    /**
     * Cache driver name [file, memcached]
     *
     * @var string
     */
    protected $driver = 'file';

    /**
     * Cache driver object
     *
     * @var object
     */
    protected $oDriver = null;

    /**
     * Cache lifetime array
     *
     * @var array
     */
    protected $aLifetime = array();

    /**
     * Constructor.
     *
     * @param string  $path  Cache files path
     * @todo params to config
     */
    public function __construct($path = __DIR__, $driver = FALSE){
        $path = realpath($path);
        if(file_exists($path) && is_dir($path)){
            $this->path = $path;
        }
        if(FALSE !== $driver){
            $this->driver = $driver;
        }

        if('memcached' === $this->driver){
            if(class_exists('Memcached')){
                $mc = new Memcached('ethplorer');
                $mc->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
                if (!count($mc->getServerList())) {
                    // @todo: servers to config
                    $mc->addServers(array(array('127.0.0.1', 11211)));
                }
                $this->oDriver = $mc;
            }else{
                die('Memcached calss not found, use filecache instead');
                $this->driver = 'file';
            }
        }
    }

    /**
     * Stores data to memory.
     *
     * @param string  $entryName  Cache entry name
     * @param mixed   $data       Data to store
     */
    public function store($entryName, $data){
        $this->aData[$entryName] = $data;
    }

    /**
     * Saves data to file.
     *
     * @param string  $entryName  Cache entry name
     * @param mixed   $data       Data to store
     */
    public function save($entryName, $data){
        $this->store($entryName, $data);
        switch($this->driver){
            case 'memcached':
                $lifetime = isset($this->aLifetime[$entryName]) ? (int)$this->aLifetime[$entryName] : 0;
                if($lifetime > evxCache::MONTH){
                    $lifetime = time() + $cacheLifetime;
                }
                if(!$lifetime){
                    // 365 days if cache lifetime is not set
                    $lifetime = time() + 12 * evxCache::MONTH + 5;
                }
                $saveRes = $this->oDriver->set($entryName, $data, $lifetime);
                if(!in_array($entryName, array('tokens', 'rates'))){
                    break;
                }
            case 'file':
                $filename = $this->path . '/' . $entryName . ".tmp";
                $json = json_encode($data, JSON_PRETTY_PRINT);
                file_put_contents($filename, $json);
                break;
        }
    }

    /**
     * Returns true if cached data entry exists.
     *
     * @param string  $entryName  Cache entry name
     * @return boolean
     */
    public function exists($entryName){
        return isset($this->aData[$entryName]);
    }

    /**
     * Returns cached data by entry name.
     *
     * @param string   $entryName
     * @param mixed    $default
     * @param boolean  $loadIfNeeded
     * @return mixed
     */
    public function get($entryName, $default = NULL, $loadIfNeeded = FALSE, $cacheLifetime = FALSE){
        $result = $default;
        $file = ('file' === $this->driver);
        if(FALSE !== $cacheLifetime){
            $this->aLifetime[$entryName] = $cacheLifetime;
        }
        if($this->exists($entryName)){
            $result = $this->aData[$entryName];
        }elseif($loadIfNeeded){
            if('memcached' === $this->driver){
                $result = $this->oDriver->get($entryName);
                // @todo: move hardcode to controller
                if(!$result && in_array($entryName, array('tokens', 'rates'))){
                    $file = TRUE;
                }
            }
            if($file){
                $filename = $this->path . '/' . $entryName . ".tmp";
                if(file_exists($filename)){
                    if(FALSE !== $cacheLifetime){
                        $fileTime = filemtime($filename);
                        $gmtZero = gmmktime(0, 0, 0);
                        if((($gmtZero > $fileTime) && ($cacheLifetime > evxCache::HOUR)) || ((time() - $fileTime) > $cacheLifetime)){
                            @unlink($filename);
                            return $result;
                        }
                    }
                    $contents = @file_get_contents($filename);
                    $result = json_decode($contents, TRUE);
                }
            }
        }
        return $result;
    }
}