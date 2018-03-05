<?php
namespace apiTests;
require "vendor/autoload.php";

use EverexIO\PHPUnitIterator\TestCase;

class everexTest extends TestCase
{
    protected $everex_url = 'http://rates.everex.io';
    const APIKey = 'freekey';


    /**
     * @dataProvider everexProvider
     */
    public function testAPI($test)
    {
        $this->_iterateTest($test);
    }

    public function everexProvider()
    {
        return [
            // ===================
            // historical tests
            [[
                'type' => 'everex',
                'method' => 'getCurrencyHistory',
                'description' => '= Comparing historical currencies with USD =',
                'compareFrom' => 'USD',
                'compareTo' => ['THB', 'MMK', 'EUR', 'CNY', 'GBP', 'JPY', 'RUB'],
                'compareSource' => 'quandl',
                'compareSourceParam' => 'CURRFX'
            ]],
            [[
                'type' => 'everex',
                'method' => 'getCurrencyHistory',
                'description' => '= Comparing historical BTC to USD =',
                'compareFrom' => 'USD',
                'compareTo' => ['BTC'],
                'compareReplace' => 'MKPRU',
                'compareSource' => 'quandl-reverse',
                'compareSourceParam' => 'BCHAIN'
            ]]        
        ];
    }

}