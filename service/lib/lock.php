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

class evxProcessLock {

    /**
     * Path to lock
     *
     * @var string
     */
    protected $path;

    /**
     * Process Id
     *
     * @var string
     */
    protected $pid;

    /**
     * @param  string $path         Path to lock
     * @param  string $ttl          Time to live for previous lock (in seconds)
     * @param  bool   $destroyPrev  Flag specifying to destroy previous lock if expired
     * @param  string $pid          Process Id
     */
    public function __construct($path, $ttl, $destroyPrev = FALSE, $pid = ''){
        $this->path = (string)$path;
        $this->pid  =
            '' === $pid
                ? mt_rand() . '.' . microtime(TRUE)
                : (string)$pid;

        if(file_exists($this->path)){
            if((time() - filemtime($this->path)) < $ttl){
                throw new \Exception(sprintf("Previous lock '%s' is still valid", $this->path));
            }
            if($destroyPrev){
                if(!@unlink($this->path)){
                    throw new \Exception(sprintf("Cannot destroy previous lock '%s'", $this->path));
                }
            }else{
                throw new \Exception(sprintf("Lock '%s' already exists", $this->path));
            }
        }
        if(!@file_put_contents($this->path, $this->pid)){
            throw new \Exception(sprintf("Cannot create lock '%s'", $this->path));
        }
        @chmod($this->path, 0666);
    }

    public function __destruct(){
        $this->validate();
        if(!@unlink($this->path)){
            throw new \Exception(sprintf("Cannot delete lock '%s'", $this->path));
        }
    }

    /**
     * Validates lock presense and pid.
     *
     * @return void
     */
    public function validate(){
        if(!file_exists($this->path)){
            throw new \Exception(sprintf("Lock '%s' destroyed", $this->path));
        }
        if(@file_get_contents($this->path) !== $this->pid){
            throw new \Exception(sprintf("Lock '%s' contains other pid"));
        }
    }

    /**
     * Update lock modification time.
     *
     * @return void
     */
    public function update(){
        $this->validate();
        if(!@touch($this->path)){
            throw new \Exception(sprintf("Cannot update lock '%s' time"));
        }
    }
}
