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

class evxProfiler {

    private static $aPoints = array();
    private static $aLog = array();
    private static $startTs;
    private static $currentDiff;
    private static $level = 0;

    public static function checkpoint($name, $position, $message = false){
        if(!in_array($position, array('START', 'FINISH'))){
            return false;
        }
        $now = microtime(true);
        self::$currentDiff = self::getTotalTime();
        switch($position){
            case 'START':
                self::$aPoints[$name] = array('start' => $now);
                self::$aLog[] = self::_getLogMessage($name, $position, $message);
                self::$level++;
                break;
            case 'FINISH':
                if(!isset(self::$aPoints[$name])) break;
                self::$level--;
                $diff = $now - self::$aPoints[$name]['start'];
                self::$aLog[] = self::_getLogMessage($name, $position . ' | ' . $diff . 's.', $message);
                break;
        }
    }

    protected static function _getLogMessage($name, $position, $message = ''){
        if(self::$level){
            $name = str_pad($name, strlen($name) + self::$level, ' ', STR_PAD_LEFT);
        }
        return '[' . date('Y-m-d H:i:s') . ' | ' . number_format(self::$currentDiff, 2) . 's.] ' . $name . ' ' . $position . ( $message ? (' (' . $message . ')') : '');
    }

    public static function get(){
        return self::$aPoints;
    }

    public static function getTotalTime(){
        $now = microtime(true);
        if(!self::$startTs){
            self::$startTs = $now;
        }
        return $now - self::$startTs;
    }

    public static function log($filename){
        if(count(self::$aLog)){
            for($i = 0; $i < count(self::$aLog); $i++){
                file_put_contents($filename, self::$aLog[$i] . "\n", FILE_APPEND);
            }
            file_put_contents($filename, "============================================================================================\n\n", FILE_APPEND);
        }
    }
}
