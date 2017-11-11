<?php
/**
 * Created by PhpStorm.
 * User: maxsh
 * Date: 12.10.2017
 * Time: 11:50
 */

namespace apiTests;
require "vendor/autoload.php";

class apiTest extends \PHPUnit\Framework\TestCase
{
    private $url = 'https://api.ethplorer.io/';

    protected function sendRequest($method, $object = '', array $parameters = array()){
        $url = $this->url . $method;
        if($object){
            $url = $url . '/' . $object;
        }
        if(!empty($parameters)){
            $url = $url . '?' . http_build_query($parameters);
        }
        $json = file_get_contents($url);
        $aResult = json_decode($json, TRUE);
        return $aResult;
    }

    protected function logToConsole($text)
    {
       printf($text."\r\n");
    }

    public function testGetTokenInfo_Success(){
        $this->logToConsole('=== TESTING GetTokenInfo_Success ===');
        $method = 'getTokenInfo';
        $testAddress = '0xB97048628DB6B661D4C2aA833e95Dbe1A905B280';

        $this->logToConsole('sending request...');
        $result = $this->sendRequest($method, $testAddress, array('apiKey' => 'freekey'));
        $this->logToConsole('check returned answer...');
        $this->assertTrue(
            is_array($result),
            sprintf(
                "Invalid response received:\n%s",
                var_export($result, TRUE)
            )
        );

        $this->logToConsole('check array contains field "address"...');
        $this->assertTrue(isset($result['address']),'Result array doesn\'t contain field "address"');

        $this->logToConsole('check array contains field "totalSupply"...');
        $this->assertTrue(isset($result['totalSupply']),'Result array doesn\'t contain field "totalSupply"');

        $this->logToConsole('check array contains field "holdersCount"...');
        $this->assertTrue(isset($result['holdersCount']),'Result array doesn\'t contain field "holdersCount"');

        $this->logToConsole('checks if json contains error key...');
        // checks if json contains error key which is not correct
        $this->assertFalse(
            is_array($result) && isset($result['error']) && is_array($result['error']),
            sprintf(
                "You should return JSON without error object:\nReturned array:\n%s",
                var_export($result, TRUE)
            )
        );

        foreach (array_keys($result) as $key)
        {
            switch ($key)
            {
                case "address":{
                    $this->logToConsole('check "address" parameter equals...');
                    $this->assertEquals(strtolower('0xB97048628DB6B661D4C2aA833e95Dbe1A905B280'), $result[$key], "Testing address $testAddress and returned address $result[$key] is not equals.");
                    break;
                }
            }
        }
    }

    public function testGetTokenInfo_Error(){
        $method = 'getTokenInfo';
        $this->logToConsole("\r\n");

        $this->logToConsole('=== TESTING GetTokenInfo_Error ===');

        //testing for invalid address format
        $code = 104;
        $testAddress = '0xB97048628DB6B661D4C2aA833e95Dbe1A905B28';
        $this->logToConsole('testing for invalid address format...');
        $this->logToConsole('sending request...');
        $result = $this->sendRequest($method, $testAddress, array('apiKey' => 'freekey'));
        $this->logToConsole('check returned answer...');
        $this->assertTrue(
            is_array($result) && isset($result['error']) && is_array($result['error']),
            sprintf(
                "Invalid response received:\n%s",
                var_export($result, TRUE)
            )
        );
        $this->logToConsole('check if answer contains code 104...');
        $returned_code = $result['error']['code'];
        $this->assertEquals($code, $result['error']['code'], "Returned JSON contains code $returned_code but should contain $code");
        $this->logToConsole("\r\n");
        $this->logToConsole('testing for wrong address type..');
        //testing for wrong address type
        $code = 150;
        $testAddress = '0xB97048628DB6B661D4C2aA833e95Dbe1A905B281';
        $this->logToConsole('sending request...');
        $result = $this->sendRequest($method, $testAddress, array('apiKey' => 'freekey'));
        $this->logToConsole('check returned answer...');
        $this->assertTrue(
            is_array($result) && isset($result['error']) && is_array($result['error']),
            sprintf(
                "Invalid response received:\n%s",
                var_export($result, TRUE)
            )
        );
        $returned_code = $result['error']['code'];
        $this->logToConsole('check if answer contains code 150...');
        $this->assertEquals($code, $result['error']['code'],"Returned JSON contains code $returned_code but should contain $code");
        $this->logToConsole("\r\n");
        $this->logToConsole('testing for missing API key...');
        //testing for missing API key
        $code = 1;
        $testAddress = '0xB97048628DB6B661D4C2aA833e95Dbe1A905B281';
        $apiParams = array('freekei', ' ', '');
        foreach ($apiParams as $param)
        {
            $this->logToConsole('sending request...');
            $result = $this->sendRequest($method, $testAddress, array('apiKey' => $param));
            $this->logToConsole('check returned answer...');
            $this->assertTrue(
                is_array($result) && isset($result['error']) && is_array($result['error']),
                sprintf(
                    "Invalid response received:\n%s",
                    var_export($result, TRUE)
                )
            );
            $this->logToConsole('check if answer contains code 1..');
            $returned_code = $result['error']['code'];
            $this->assertEquals($code, $result['error']['code'],"Returned JSON contains code $returned_code but should contain $code");
        }
    }


    public function testGetAddressInfo_Success(){
        $this->logToConsole("\r\n");
        $this->logToConsole('=== TESTING GetAddressInfo_Success ===');

        $method = 'getAddressInfo';
        $testAddress = '0xd26114cd6EE289AccF82350c8d8487fedB8A0C07';

        //check without parameters
        $this->logToConsole('/check without parameters...');
        $this->logToConsole('sending request...');
        $result = $this->sendRequest($method, $testAddress, array('apiKey' => 'freekey'));
        $this->logToConsole('check returned answer...');
        $this->assertTrue(
            is_array($result),
            sprintf(
                "Invalid response received:\n%s",
                var_export($result, TRUE)
            )
        );

        $this->logToConsole('check array contains field "address"...');
        $this->assertTrue(isset($result['address']),'Result array doesn\'t contain field "address"');

        $this->logToConsole('check array contains field "ETH"...');
        $this->assertTrue(isset($result['ETH']),'Result array doesn\'t contain field "ETH"');

        $this->logToConsole('check array contains field "countTxs"...');
        $this->assertTrue(isset($result['countTxs']),'Result array doesn\'t contain field "countTxs"');

        foreach (array_keys($result) as $key)
        {
            switch ($key)
            {
                case "address":
                {
                    $this->logToConsole('check "address" parameter equals...');
                    $this->assertEquals(strtolower($testAddress), $result[$key]);
                    break;
                }
                case "contractInfo":
                {
                    $this->logToConsole('check "contractInfo" parameter equals...');
                    $this->assertEquals(strtolower('0x140427a7d27144a4cda83bd6b9052a63b0c5b589'), $result[$key]['creatorAddress']);
                    break;
                }
                case "tokenInfo":
                {
                    $this->logToConsole('check "tokenInfo" parameter equals...');
                    $this->assertEquals(strtolower('0xd26114cd6ee289accf82350c8d8487fedb8a0c07'), $result[$key]['address']);
                    break;
                }
            }
        }

        $this->logToConsole("\r\n");
        $this->logToConsole('/check with parameter "token"...');
        //check with parameters
        //token=0x49aec0752e68d0282db544c677f6ba407ba17ed7
        $this->logToConsole('sending request...');
        $result = $this->sendRequest($method, $testAddress, array('apiKey' => 'freekey', 'token' => '0x49aec0752e68d0282db544c677f6ba407ba17ed7'));
        $this->logToConsole('check returned answer...');
        $this->assertTrue(
            is_array($result),
            sprintf(
                "Invalid response received:\n%s",
                var_export($result, TRUE)
            )
        );

        foreach (array_keys($result) as $key)
        {
            switch ($key)
            {
                case "address":
                {
                    $this->logToConsole('check "address" parameter equals...');
                    $this->assertEquals(strtolower($testAddress), $result[$key], "Testing address $testAddress and returned address $result[$key] is not equals.");
                    break;
                }
                case "contractInfo":
                {
                    $this->logToConsole('check "contractInfo" parameter equals...');
                    $this->assertEquals(strtolower('0x140427a7d27144a4cda83bd6b9052a63b0c5b589'), $result[$key]['creatorAddress'], "Testing contractInfo  is not equal to returned contractInfo.");
                    break;
                }
            }
        }
    }

    public function testGetAddressInfo_Error(){
        $method = 'getAddressInfo';
        $this->logToConsole("\r\n");

        $this->logToConsole('=== TESTING GetAddressInfo_Error ===');
        //testing for invalid address format
        $this->logToConsole('testing for invalid address format...');
        $this->logToConsole('sending request...');
        $code = 104;
        $testAddress = '0xB97048628DB6B661D4C2aA833e95Dbe1A905B28';
        $result = $this->sendRequest($method, $testAddress, array('apiKey' => 'freekey'));
        $this->logToConsole('check returned answer...');
        $this->assertTrue(
            is_array($result) && isset($result['error']) && is_array($result['error']),
            sprintf(
                "Invalid response received:\n%s",
                var_export($result, TRUE)
            )
        );
        $returned_code = $result['error']['code'];
        $this->logToConsole('check if answer contains code 104...');
        $this->assertEquals($code, $result['error']['code'], "Returned JSON contains code $returned_code but should contain $code");

        $this->logToConsole("\r\n");
        $this->logToConsole('testing for wrong token type..');
        //testing for wrong token type
        $testAddress = '0xd26114cd6EE289AccF82350c8d8487fedB8A0C07';
        $this->logToConsole('sending request...');
        $result = $this->sendRequest($method, $testAddress, array('apiKey' => 'freekey', 'token' => 'ec0752e68d0282db544c677f6ba407ba17ed7'));
        $this->logToConsole('check returned answer...');
        $this->assertTrue(
            is_array($result) && isset($result['error']) && is_array($result['error']),
            sprintf(
                "Invalid response received:\n%s",
                var_export($result, TRUE)
            )
        );
        $this->logToConsole('check if answer contains code 104...');
        $returned_code = $result['error']['code'];
        $this->assertEquals($code, $result['error']['code'], "Returned JSON contains code $returned_code but should contain $code");
        $this->logToConsole("\r\n");
        $this->logToConsole('testing for missing API key...');
        //testing for missing API key
        $code = 1;
        $testAddress = '0xB97048628DB6B661D4C2aA833e95Dbe1A905B281';
        $apiParams = array('freekei', ' ', '');
        foreach ($apiParams as $param)
        {
            $this->logToConsole('sending request...');
            $result = $this->sendRequest($method, $testAddress, array('apiKey' => $param));
            $this->logToConsole('check returned answer...');
            $this->assertTrue(
                is_array($result) && isset($result['error']) && is_array($result['error']),
                sprintf(
                    "Invalid response received:\n%s",
                    var_export($result, TRUE)
                )
            );
            $this->logToConsole('check if answer contains code 1..');
            $returned_code = $result['error']['code'];
            $this->assertEquals($code, $result['error']['code'],"Returned JSON contains code $returned_code but should contain $code");
        }
    }

    public function testGetTxInfo_Success()
    {
        $this->logToConsole("\r\n");
        $this->logToConsole('=== TESTING GetTxInfo_Success ===');

        $method = 'getTxInfo';
        $testHash = '0x0bd079304c36ff6741382125e1ba4bd02cdd29dd30f7a08b8ccd9e801cbc2be3';

        $this->logToConsole('sending request...');
        $result = $this->sendRequest($method, $testHash, array('apiKey' => 'freekey'));
        $this->logToConsole('check returned answer...');
        $this->assertTrue(
            is_array($result),
            sprintf("Invalid response received:\n%s",var_export($result, TRUE))
        );

        $this->logToConsole('check array contains field "hash"...');
        $this->assertTrue(isset($result['hash']),'Result array doesn\'t contain field "hash"');

        $this->logToConsole('check array contains field "timestamp"...');
        $this->assertTrue(isset($result['timestamp']),'Result array doesn\'t contain field "timestamp"');

        $this->logToConsole('check array contains field "blockNumber"...');
        $this->assertTrue(isset($result['blockNumber']),'Result array doesn\'t contain field "blockNumber"');

        $this->logToConsole('check array contains field "from"...');
        $this->assertTrue(isset($result['from']),'Result array doesn\'t contain field "from"');

        $this->logToConsole('check array contains field "value"...');
        $this->assertTrue(isset($result['value']),'Result array doesn\'t contain field "value"');

        $this->logToConsole('check array contains field "gasLimit"...');
        $this->assertTrue(isset($result['gasLimit']),'Result array doesn\'t contain field "gasLimit"');

        $this->logToConsole('check array contains field "gasUsed"...');
        $this->assertTrue(isset($result['gasUsed']),'Result array doesn\'t contain field "gasUsed"');

        foreach (array_keys($result) as $key)
        {
            switch ($key)
            {
                case "hash":
                {
                    $this->logToConsole('check "hash" parameter equals...');
                    $this->assertEquals(strtolower('0x0bd079304c36ff6741382125e1ba4bd02cdd29dd30f7a08b8ccd9e801cbc2be3'), $result[$key],"Testing hash and returned hash $result[$key] is not equals.");
                    break;
                } //
                case "gasUsed":
                {
                    $this->logToConsole('check "gasUsed" parameter equals...');
                    $this->assertEquals('40843', $result[$key],"Testing gasUsed and returned gasUsed $result[$key] is not equals.");
                    break;
                }
            }
        }
    }

    public function testGetTxInfo_Error(){
        $this->logToConsole("\r\n");
        $this->logToConsole('=== TESTING GetTxInfo_Error ===');

        $method = 'getTxInfo';
        $testHash = '0x0bd079304c36ff6741382125e1ba4bd02cdd29dd30f7a08b8ccd9e801cbc2be2';

        $this->logToConsole('check for missing transaction');
        $this->logToConsole('sending request...');
        $result = $this->sendRequest($method, $testHash, array('apiKey' => 'freekey'));
        $this->logToConsole('check returned answer...');
        $this->assertTrue(
            is_array($result),
            sprintf("Invalid response received:\n%s",var_export($result, TRUE))
        );

        $this->logToConsole('check array contains field "error"...');
        $this->assertTrue(isset($result['error']),'Result array doesn\'t contain field "error"');

        $code=404;
        $returned_code = $result['error']['code'];

        $this->logToConsole('check for code 404...');
        $this->assertEquals($code, $result['error']['code'], "Returned JSON contains code $returned_code but should contain $code");

        $testHash = 'xx0bd079304c36ff6741382125e1ba4bd02cdd29dd30f7a08b8ccd9e801cbc2be2';
        $this->logToConsole("\r\n");
        $this->logToConsole('check for invalid hash');
        $this->logToConsole('sending request...');
        $result = $this->sendRequest($method, $testHash, array('apiKey' => 'freekey'));
        $this->logToConsole('check returned answer...');
        $this->assertTrue(
            is_array($result),
            sprintf("Invalid response received:\n%s",var_export($result, TRUE))
        );

        $this->logToConsole('check array contains field "error"...');
        $this->assertTrue(isset($result['error']),'Result array doesn\'t contain field "error"');

        $code = 102;
        $returned_code = $result['error']['code'];
        $this->logToConsole('check for code 102...');
        $this->assertEquals($code, $result['error']['code'], "Returned JSON contains code $returned_code but should contain $code");

        $this->logToConsole("\r\n");
        $this->logToConsole('testing for missing API key...');
        //testing for missing API key
        $code = 1;
        $testHash = '0x0bd079304c36ff6741382125e1ba4bd02cdd29dd30f7a08b8ccd9e801cbc2be2';
        $apiParams = array('freekei', ' ', '');
        foreach ($apiParams as $param)
        {
            $this->logToConsole('sending request...');
            $result = $this->sendRequest($method, $testHash, array('apiKey' => $param));
            $this->logToConsole('check returned answer...');
            $this->assertTrue(
                is_array($result) && isset($result['error']) && is_array($result['error']),
                sprintf(
                    "Invalid response received:\n%s",
                    var_export($result, TRUE)
                )
            );
            $this->logToConsole('check if answer contains code 1..');
            $returned_code = $result['error']['code'];
            $this->assertEquals($code, $result['error']['code'],"Returned JSON contains code $returned_code but should contain $code");
        }
    }

    public function testGetTokenHistory_Success()
    {
        $this->logToConsole("\r\n");
        $this->logToConsole('=== TESTING GetTokenHistory_Success ===');

        $method = 'getTokenHistory';
        $testAddress = '0xd26114cd6EE289AccF82350c8d8487fedB8A0C07';

        $this->logToConsole('check request without parameters');

        $this->logToConsole('sending request...');
        $result = $this->sendRequest($method, $testAddress, array('apiKey' => 'freekey'));

        $this->logToConsole('check returned answer...');
        $this->assertTrue(
            is_array($result),
            sprintf("Invalid response received:\n%s",var_export($result, TRUE))
        );

        $this->logToConsole('check array contains "operations" field...');
        $this->assertTrue(isset($result['operations']), 'Array does not contain "operations" field');
        $this->assertTrue(!empty($result['operations']),sprintf("Returned array is empty:\n%s",var_export($result, TRUE)));

        $this->logToConsole("\r\n");
        $this->logToConsole('check request with "type" parameter');
        $this->logToConsole('sending request with type "approve"...');
        $result = $this->sendRequest($method, $testAddress, array('apiKey' => 'freekey', 'type'=>'approve'));
        $this->logToConsole('check returned answer...');
        $this->assertTrue(
            is_array($result),
            sprintf("Invalid response received:\n%s",var_export($result, TRUE))
        );
        $this->logToConsole('check array contains "operations" field...');
        $this->assertTrue(isset($result['operations']), 'Array does not contain "operations" field');
        $this->assertTrue(!empty($result['operations']),sprintf("Returned array is empty:\n%s",var_export($result, TRUE)));

        $this->logToConsole("\r\n");
        $this->logToConsole('sending request with type "transfer"...');
        $result = $this->sendRequest($method, $testAddress, array('apiKey' => 'freekey', 'type'=>'transfer'));
        $this->logToConsole('check returned answer...');
        $this->assertTrue(
            is_array($result),
            sprintf("Invalid response received:\n%s",var_export($result, TRUE))
        );
        $this->logToConsole('check array contains "operations" field...');
        $this->assertTrue(isset($result['operations']), 'Array does not contain "operations" field');
        $this->assertTrue(!empty($result['operations']),sprintf("Returned array is empty:\n%s",var_export($result, TRUE)));

        $this->logToConsole("\r\n");
        $this->logToConsole('check request with "limit" parameter');

        $this->logToConsole('sending request with limit 5...');
        $result = $this->sendRequest($method, $testAddress, array('apiKey' => 'freekey', 'limit'=>'5'));
        $this->logToConsole('check returned answer...');
        $this->assertTrue(
            is_array($result),
            sprintf("Invalid response received:\n%s",var_export($result, TRUE))
        );
        $this->logToConsole('check array contains "operations" field...');
        $this->assertTrue(isset($result['operations']), 'Array does not contain "operations" field');
        $this->assertTrue(!empty($result['operations']) && count($result['operations']) <= 5,
            sprintf("Returned array is empty or count > 5:\n%s",var_export($result, TRUE)));

        $this->logToConsole('check if limit=0 return correct amount of objects...');
        $result = $this->sendRequest($method, $testAddress, array('apiKey' => 'freekey', 'limit'=>'0'));
        $this->assertTrue(!empty($result['operations']) && count($result['operations']) <= 50 && count($result['operations']) >= 1,
            sprintf("Returned array is empty or count not in 1-50:\n%s",var_export($result, TRUE)));
        $result = $this->sendRequest($method, $testAddress, array('apiKey' => 'freekey', 'limit'=>'51'));
        $this->assertTrue(!empty($result['operations']) && count($result['operations']) <= 50 && count($result['operations']) >= 1,
            sprintf("Returned array is empty or count not in 1-50:\n%s",var_export($result, TRUE)));
    }

    public function testGetTokenHistory_Error()
    {
        $method = 'getTokenHistory';
        $this->logToConsole("\r\n");

        $this->logToConsole('=== TESTING GetTokenHistory_Error ===');

        //testing for invalid address format
        $code = 104;
        $testAddress = 'xxB97048628DB6B661D4C2aA833e95Dbe1A905B28';
        $this->logToConsole('testing for invalid address format...');
        $this->logToConsole('sending request...');
        $result = $this->sendRequest($method, $testAddress, array('apiKey' => 'freekey'));
        $this->logToConsole('check returned answer...');
        $this->assertTrue(
            is_array($result) && isset($result['error']) && is_array($result['error']),
            sprintf(
                "Invalid response received:\n%s",
                var_export($result, TRUE)
            )
        );
        $this->logToConsole('check if answer contains code 104...');
        $returned_code = $result['error']['code'];
        $this->assertEquals($code, $result['error']['code'], "Returned JSON contains code $returned_code but should contain $code");

        $this->logToConsole("\r\n");
        $this->logToConsole('testing for empty result..');
        //testing for wrong address type

        $testAddress = '0xB97048628DB6B661D4C2aA833e95Dbe1A905B281';
        $this->logToConsole('sending request...');
        $result = $this->sendRequest($method, $testAddress, array('apiKey' => 'freekey'));
        $this->logToConsole('check returned answer is empty...');
        $this->assertTrue(
            is_array($result) && isset($result['operations']) && empty($result['operations']),
            sprintf(
                "Invalid response received:\n%s",
                var_export($result, TRUE)
            )
        );

        $this->logToConsole("\r\n");
        $this->logToConsole('check request with wrong "limit" parameter contains only 1 object');
        $testAddress = "0xd26114cd6EE289AccF82350c8d8487fedB8A0C07";
        $result = $this->sendRequest($method, $testAddress, array('apiKey' => 'freekey', "limit" => "asd"));
        $this->assertTrue(
            is_array($result) && isset($result['operations']) && count($result['operations']) == 1,
            sprintf(
                "Invalid response received:\n%s",
                var_export($result, TRUE)
            )
        );

        $this->logToConsole("\r\n");
        $this->logToConsole('testing for missing API key...');
        //testing for missing API key
        $code = 1;
        $testAddress = '0xB97048628DB6B661D4C2aA833e95Dbe1A905B281';
        $apiParams = array('freekei', ' ', '');
        foreach ($apiParams as $param)
        {
            $this->logToConsole('sending request...');
            $result = $this->sendRequest($method, $testAddress, array('apiKey' => $param));
            $this->logToConsole('check returned answer...');
            $this->assertTrue(
                is_array($result) && isset($result['error']) && is_array($result['error']),
                sprintf(
                    "Invalid response received:\n%s",
                    var_export($result, TRUE)
                )
            );
            $this->logToConsole('check if answer contains code 1..');
            $returned_code = $result['error']['code'];
            $this->assertEquals($code, $result['error']['code'],"Returned JSON contains code $returned_code but should contain $code");
        }
    }

    public function testGetAddressHistory_Success()
    {
        $this->logToConsole("\r\n");
        $this->logToConsole('=== TESTING GetAddressHistory_Success ===');

        $method = 'getAddressHistory';
        $testAddress = '0xd26114cd6EE289AccF82350c8d8487fedB8A0C07';

        $this->logToConsole('check request without parameters');

        $this->logToConsole('sending request...');
        $result = $this->sendRequest($method, $testAddress, array('apiKey' => 'freekey'));

        $this->logToConsole('check returned answer...');
        $this->assertTrue(
            is_array($result),
            sprintf("Invalid response received:\n%s",var_export($result, TRUE))
        );

        $this->logToConsole('check array contains "operations" field...');
        $this->assertTrue(isset($result['operations']), 'Array does not contain "operations" field');
        $this->assertTrue(!empty($result['operations']),sprintf("Returned array is empty:\n%s",var_export($result, TRUE)));

        $this->logToConsole("\r\n");
        $this->logToConsole('sending request with type "transfer"...');
        $result = $this->sendRequest($method, $testAddress, array('apiKey' => 'freekey', 'type'=>'transfer'));
        $this->logToConsole('check returned answer...');
        $this->assertTrue(
            is_array($result),
            sprintf("Invalid response received:\n%s",var_export($result, TRUE))
        );
        $this->logToConsole('check array contains "operations" field...');
        $this->assertTrue(isset($result['operations']), 'Array does not contain "operations" field');
        $this->assertTrue(!empty($result['operations']),sprintf("Returned array is empty:\n%s",var_export($result, TRUE)));

        $this->logToConsole("\r\n");
        $this->logToConsole('check request with "limit" parameter');

        $this->logToConsole('sending request with limit 5...');
        $result = $this->sendRequest($method, $testAddress, array('apiKey' => 'freekey', 'limit'=>'5'));
        $this->logToConsole('check returned answer...');
        $this->assertTrue(
            is_array($result),
            sprintf("Invalid response received:\n%s",var_export($result, TRUE))
        );
        $this->logToConsole('check array contains "operations" field...');
        $this->assertTrue(isset($result['operations']), 'Array does not contain "operations" field');
        $this->assertTrue(!empty($result['operations']) && count($result['operations']) <= 5,
            sprintf("Returned array is empty or count > 5:\n%s",var_export($result, TRUE)));
        //0xb9b4cfe4194d7e8511aa9b9f1260bc7b9634249e

        $this->logToConsole("\r\n");
        $this->logToConsole('check request with "token" parameter');

        $this->logToConsole('sending request with token 0xb9b4cfe4194d7e8511aa9b9f1260bc7b9634249e...');
        $result = $this->sendRequest($method, $testAddress, array('apiKey' => 'freekey', 'token'=>'0xb9b4cfe4194d7e8511aa9b9f1260bc7b9634249e'));
        $this->logToConsole('check returned answer...');
        $this->assertTrue(
            is_array($result),
            sprintf("Invalid response received:\n%s",var_export($result, TRUE))
        );
        $this->logToConsole('check array contains "operations" field...');
        $this->assertTrue(isset($result['operations']), 'Array does not contain "operations" field');
        $this->assertTrue(!empty($result['operations']),
            sprintf("Returned array is empty:\n%s",var_export($result, TRUE)));

        $this->logToConsole('check if limit=0 return correct amount of objects...');
        $result = $this->sendRequest($method, $testAddress, array('apiKey' => 'freekey', 'limit'=>'0'));
        $this->assertTrue(!empty($result['operations']) && count($result['operations']) <= 50 && count($result['operations']) >= 1,
            sprintf("Returned array is empty or count not in 1-50:\n%s",var_export($result, TRUE)));
        $this->logToConsole('check if limit=51 return correct amount of objects...');
        $result = $this->sendRequest($method, $testAddress, array('apiKey' => 'freekey', 'limit'=>'51'));
        $this->assertTrue(!empty($result['operations']) && count($result['operations']) <= 50 && count($result['operations']) >= 1,
            sprintf("Returned array is empty or count not in 1-50:\n%s",var_export($result, TRUE)));
    }

    public function testGetAddressHistory_Error(){
        $method = 'getAddressHistory';
        $this->logToConsole("\r\n");
        $this->logToConsole('=== TESTING GetAddressHistory_Error ===');

        //testing for invalid address format
        $code = 104;
        $testAddress = '0sd26114cd6EE289AccF82350c8d8487fedB8A0C07';
        $this->logToConsole('testing for invalid address format...');
        $this->logToConsole('sending request...');
        $result = $this->sendRequest($method, $testAddress, array('apiKey' => 'freekey'));
        $this->logToConsole('check returned answer...');
        $this->assertTrue(
            is_array($result) && isset($result['error']) && is_array($result['error']),
            sprintf(
                "Invalid response received:\n%s",
                var_export($result, TRUE)
            )
        );
        $this->logToConsole('check if answer contains code 104...');
        $returned_code = $result['error']['code'];
        $this->assertEquals($code, $result['error']['code'], "Returned JSON contains code $returned_code but should contain $code");

        $this->logToConsole("\r\n");
        $this->logToConsole('testing for empty result..');
        //testing for wrong address type

        $testAddress = '0xB97048628DB6B661D4C2aA833e95Dbe1A905B281';
        $this->logToConsole('sending request...');
        $result = $this->sendRequest($method, $testAddress, array('apiKey' => 'freekey'));
        $this->logToConsole('check returned answer is empty...');
        $this->assertTrue(
            is_array($result) && isset($result['operations']) && empty($result['operations']),
            sprintf(
                "Invalid response received:\n%s",
                var_export($result, TRUE)
            )
        );

        $this->logToConsole("\r\n");
        $this->logToConsole('check request with wrong "limit" parameter contains only 1 object');
        $testAddress = "0xd26114cd6EE289AccF82350c8d8487fedB8A0C07";
        $result = $this->sendRequest($method, $testAddress, array('apiKey' => 'freekey', "limit" => "asd"));
        $this->assertTrue(
            is_array($result) && isset($result['operations']) && count($result['operations']) == 1,
            sprintf(
                "Invalid response received:\n%s",
                var_export($result, TRUE)
            )
        );

        $this->logToConsole("\r\n");
        $this->logToConsole('testing for missing API key...');
        //testing for missing API key
        $code = 1;
        $testAddress = '0xB97048628DB6B661D4C2aA833e95Dbe1A905B281';
        $apiParams = array('freekei', ' ', '');
        foreach ($apiParams as $param)
        {
            $this->logToConsole('sending request...');
            $result = $this->sendRequest($method, $testAddress, array('apiKey' => $param));
            $this->logToConsole('check returned answer...');
            $this->assertTrue(
                is_array($result) && isset($result['error']) && is_array($result['error']),
                sprintf(
                    "Invalid response received:\n%s",
                    var_export($result, TRUE)
                )
            );
            $this->logToConsole('check if answer contains code 1..');
            $returned_code = $result['error']['code'];
            $this->assertEquals($code, $result['error']['code'],"Returned JSON contains code $returned_code but should contain $code");
        }
    }

    public function testGetAddressTransactions_Success()
    {
        $this->logToConsole("\r\n");
        $this->logToConsole('=== TESTING GetAddressTransactions_Success ===');

        $method = 'getAddressTransactions';
        $testAddress = '0xd26114cd6EE289AccF82350c8d8487fedB8A0C07';

        $this->logToConsole('check request without parameters');

        $this->logToConsole('sending request...');
        $result = $this->sendRequest($method, $testAddress, array('apiKey' => 'freekey'));

        $this->logToConsole('check returned answer...');
        $this->assertTrue(
            is_array($result),
            sprintf("Invalid response received:\n%s",var_export($result, TRUE))
        );

        $this->logToConsole('check array is not empty...');
        $this->assertFalse(empty($result), 'Returned array is empty');

        $trObject = $result[0];
        $this->logToConsole('check transaction object contains specific fields...');
        $this->logToConsole('timestamp...');
        $this->assertTrue(isset($trObject['timestamp']),'Transaction object does not contain "timestamp" field');
        $this->logToConsole('from... ');
        $this->assertTrue(isset($trObject['from']),'Transaction object does not contain "from" field');
        $this->logToConsole('to... ');
        $this->assertTrue(isset($trObject['to']),'Transaction object does not contain "to" field');
        $this->logToConsole('hash... ');
        $this->assertTrue(isset($trObject['hash']),'Transaction object does not contain "hash" field');
        $this->logToConsole('value... ');
        $this->assertTrue(isset($trObject['value']),'Transaction object does not contain "value" field');
        $this->logToConsole('input... ');
        $this->assertTrue(isset($trObject['input']),'Transaction object does not contain "input" field');
        $this->logToConsole('success...');
        $this->assertTrue(isset($trObject['success']),'Transaction object does not contain "success" field');

        $this->logToConsole("\r\n");
        $this->logToConsole('check request with "limit" parameter');

        $this->logToConsole('sending request with limit 5...');
        $result = $this->sendRequest($method, $testAddress, array('apiKey' => 'freekey', 'limit'=>'5'));
        $this->logToConsole('check returned answer...');
        $this->assertTrue(
            is_array($result),
            sprintf("Invalid response received:\n%s",var_export($result, TRUE))
        );
        $this->assertTrue(!empty($result) && count($result) <= 5,
            sprintf("Returned array is empty or count > 5:\n%s",var_export($result, TRUE)));

        $this->logToConsole("\r\n");
        $this->logToConsole('check request with "showZeroValues" parameter');

        $this->logToConsole('sending request...');
        $result = $this->sendRequest($method, $testAddress, array('apiKey' => 'freekey', 'showZeroValues'=>'true'));
        $this->logToConsole('check returned answer...');
        $this->assertTrue(
            is_array($result),
            sprintf("Invalid response received:\n%s",var_export($result, TRUE))
        );

        $this->logToConsole('check "value" equals 0...');
        $this->assertTrue($result[0]['value'] == 0, 'Value not equal 0');

        $this->logToConsole('check if limit=0 return correct amount of objects...');
        $result = $this->sendRequest($method, $testAddress, array('apiKey' => 'freekey', 'limit'=>'0'));
        $this->assertTrue(!empty($result) && count($result) <= 50 && count($result) >= 1,
            sprintf("Returned array is empty or count not in 1-50:\n%s",var_export($result, TRUE)));
        $this->logToConsole('check if limit=51 return correct amount of objects...');
        $result = $this->sendRequest($method, $testAddress, array('apiKey' => 'freekey', 'limit'=>'51'));
        $this->assertTrue(!empty($result) && count($result) <= 50 && count($result) >= 1,
            sprintf("Returned array is empty or count not in 1-50:\n%s",var_export($result, TRUE)));
    }

    public function testGetAddressTransactions_Error()
    {
        $this->logToConsole("\r\n");
        $this->logToConsole('=== TESTING GetAddressTransactions_Error ===');

        $method = 'getAddressTransactions';
        $testAddress = 'xxd26114cd6EE289AccF82350c8d8487fedB8A0C07';

        //testing for invalid address format
        $code = 104;
        $this->logToConsole('testing for invalid address format...');
        $this->logToConsole('sending request...');
        $result = $this->sendRequest($method, $testAddress, array('apiKey' => 'freekey'));
        $this->logToConsole('check returned answer...');
        $this->assertTrue(
            is_array($result) && isset($result['error']) && is_array($result['error']),
            sprintf(
                "Invalid response received:\n%s",
                var_export($result, TRUE)
            )
        );
        $this->logToConsole('check if answer contains code 104...');
        $returned_code = $result['error']['code'];
        $this->assertEquals($code, $result['error']['code'], "Returned JSON contains code $returned_code but should contain $code");

        $this->logToConsole("\r\n");
        $this->logToConsole('testing for empty result..');
        //testing for wrong address type

        $testAddress = '0xB97048628DB6B661D4C2aA833e95Dbe1A905B281';
        $this->logToConsole('sending request...');
        $result = $this->sendRequest($method, $testAddress, array('apiKey' => 'freekey'));
        $this->logToConsole('check returned answer is empty...');
        $this->assertTrue(
            is_array($result) && empty($result),
            sprintf(
                "Invalid response received:\n%s",
                var_export($result, TRUE)
            )
        );

        $this->logToConsole("\r\n");
        $this->logToConsole('check request with wrong "limit" parameter contains only 1 object');
        $testAddress = "0xd26114cd6EE289AccF82350c8d8487fedB8A0C07";
        $result = $this->sendRequest($method, $testAddress, array('apiKey' => 'freekey', "limit" => "asd"));
        $this->assertTrue(
            is_array($result) && isset($result) && count($result) == 1,
            sprintf(
                "Invalid response received:\n%s",
                var_export($result, TRUE)
            )
        );

        $this->logToConsole("\r\n");
        $this->logToConsole('testing for missing API key...');
        //testing for missing API key
        $code = 1;
        $testAddress = '0xB97048628DB6B661D4C2aA833e95Dbe1A905B281';
        $apiParams = array('freekei', ' ', '');
        foreach ($apiParams as $param)
        {
            $this->logToConsole('sending request...');
            $result = $this->sendRequest($method, $testAddress, array('apiKey' => $param));
            $this->logToConsole('check returned answer...');
            $this->assertTrue(
                is_array($result) && isset($result['error']) && is_array($result['error']),
                sprintf(
                    "Invalid response received:\n%s",
                    var_export($result, TRUE)
                )
            );
            $this->logToConsole('check if answer contains code 1..');
            $returned_code = $result['error']['code'];
            $this->assertEquals($code, $result['error']['code'],"Returned JSON contains code $returned_code but should contain $code");
        }
    }

    public function testGetTopTokens_Success()
    {
        $this->logToConsole("\r\n");
        $this->logToConsole('=== TESTING GetTopTokens_Success ===');

        $method = 'getTopTokens';

        $this->logToConsole('check request without parameters');

        $this->logToConsole('sending request...');
        $result = $this->sendRequest($method, '', array('apiKey' => 'freekey'));

        $this->logToConsole('check returned answer...');
        $this->assertTrue(
            is_array($result),
            sprintf("Invalid response received:\n%s",var_export($result, TRUE))
        );

        $this->logToConsole('check array contains "tokens" field...');
        $this->assertTrue(isset($result['tokens']), 'Array does not contain "operations" field');
        $this->assertTrue(!empty($result['tokens']),sprintf("Returned array is empty:\n%s",var_export($result, TRUE)));

        $this->logToConsole("\r\n");
        $this->logToConsole('check request with "period" parameter');
        $result = $this->sendRequest($method,'', array('apiKey' => 'freekey', 'period'=>'5'));
        $this->logToConsole('check returned answer...');
        $this->assertTrue(
            is_array($result),
            sprintf("Invalid response received:\n%s",var_export($result, TRUE))
        );
        $this->logToConsole('check array contains "tokens" field...');
        $this->assertTrue(isset($result['tokens']), 'Array does not contain "tokens" field');
        $this->assertTrue(!empty($result['tokens']),sprintf("Returned array is empty:\n%s",var_export($result, TRUE)));

        $this->logToConsole("\r\n");
        $this->logToConsole('check request with "limit" parameter');

        $this->logToConsole('sending request with limit 3...');
        $result = $this->sendRequest($method, '', array('apiKey' => 'freekey', 'limit'=>'3'));
        $this->logToConsole('check returned answer...');
        $this->assertTrue(
            is_array($result),
            sprintf("Invalid response received:\n%s",var_export($result, TRUE))
        );
        $this->logToConsole('check array contains "tokens" field...');
        $this->assertTrue(isset($result['tokens']), 'Array does not contain "tokens" field');
        $this->assertTrue(!empty($result['tokens']) && count($result['tokens']) <= 3,
            sprintf("Returned array is empty or count > 3\n%s",var_export($result, TRUE)));

        $this->logToConsole('check if limit=0 return correct amount of objects...');
        $result = $this->sendRequest($method, '', array('apiKey' => 'freekey', 'limit'=>'0'));
        $this->assertTrue(!empty($result['tokens']) && count($result['tokens']) <= 50 && count($result['tokens']) >= 1,
            sprintf("Returned array is empty or count not in 1-50:\n%s",var_export($result, TRUE)));
        $this->logToConsole('check if limit=51 return correct amount of objects...');
        $result = $this->sendRequest($method, '', array('apiKey' => 'freekey', 'limit'=>'51'));
        $this->assertTrue(!empty($result['tokens']) && count($result['tokens']) <= 50 && count($result) >= 1,
            sprintf("Returned array is empty or count not in 1-50:\n%s",var_export($result, TRUE)));
    }

    public function testGetTopTokens_Error()
    {
        $method = 'getTopTokens';
        $this->logToConsole("\r\n");
        $this->logToConsole('=== TESTING GetTopTokens_Error ===');

        //testing for invalid address format

        $testAddress = '';
        $this->logToConsole('check if period equals 0...');
        $this->logToConsole('sending request...');
        $result = $this->sendRequest($method, $testAddress, array('apiKey' => 'freekey', 'period'=>'0'));
        $this->logToConsole('check returned answer is empty...');
        $this->assertTrue(
            is_array($result) && isset($result['tokens']) && !empty($result['tokens']),
            sprintf(
                "Invalid response received:\n%s",
                var_export($result, TRUE)
            )
        );

        $this->logToConsole("\r\n");
        $this->logToConsole('check request with wrong "limit" parameter contains only 1 object');
        $testAddress = "0xd26114cd6EE289AccF82350c8d8487fedB8A0C07";
        $result = $this->sendRequest($method, $testAddress, array('apiKey' => 'freekey', "limit" => "asd"));
        $this->assertTrue(
            is_array($result) && isset($result) && count($result) == 1,
            sprintf(
                "Invalid response received:\n%s",
                var_export($result, TRUE)
            )
        );

        $this->logToConsole("\r\n");
        $this->logToConsole('testing for missing API key...');
        //testing for missing API key
        $code = 1;
        $testAddress = '0xB97048628DB6B661D4C2aA833e95Dbe1A905B281';
        $apiParams = array('freekei', ' ', '');
        foreach ($apiParams as $param)
        {
            $this->logToConsole('sending request...');
            $result = $this->sendRequest($method, $testAddress, array('apiKey' => $param));
            $this->logToConsole('check returned answer...');
            $this->assertTrue(
                is_array($result) && isset($result['error']) && is_array($result['error']),
                sprintf(
                    "Invalid response received:\n%s",
                    var_export($result, TRUE)
                )
            );
            $this->logToConsole('check if answer contains code 1..');
            $returned_code = $result['error']['code'];
            $this->assertEquals($code, $result['error']['code'],"Returned JSON contains code $returned_code but should contain $code");
        }
    }

    public function testGetTokenHistoryGrouped_Success()
    {
        $this->logToConsole("\r\n");
        $this->logToConsole('=== TESTING GetTokenHistoryGrouped_Success ===');

        $method = 'getTokenHistoryGrouped';
        $testAddress = '0xd26114cd6EE289AccF82350c8d8487fedB8A0C07';

        $this->logToConsole('check request without parameters');

        $this->logToConsole('sending request...');
        $result = $this->sendRequest($method, $testAddress, array('apiKey' => 'freekey'));

        $this->logToConsole('check returned answer...');
        $this->assertTrue(
            is_array($result),
            sprintf("Invalid response received:\n%s",var_export($result, TRUE))
        );

        $this->logToConsole('check array contains "countTxs" field...');
        $this->assertTrue(isset($result['countTxs']), 'Array does not contain "countTxs" field');
        $this->assertTrue(!empty($result['countTxs']),sprintf("Returned array is empty:\n%s",var_export($result, TRUE)));

        $this->logToConsole("\r\n");
        $this->logToConsole('sending request with "period" field...');
        $result = $this->sendRequest($method, $testAddress, array('apiKey' => 'freekey', 'period'=>'1'));
        $this->logToConsole('check returned answer...');
        $this->assertTrue(
            is_array($result),
            sprintf("Invalid response received:\n%s",var_export($result, TRUE))
        );
        $this->logToConsole('check array contains "countTxs" field...');
        $this->assertTrue(isset($result['countTxs']), 'Array does not contain "countTxs" field');
        $this->assertTrue(!empty($result['countTxs']),sprintf("Returned array is empty:\n%s",var_export($result, TRUE)));

    }

    public function testGetTokenHistoryGrouped_Error()
    {
        $this->logToConsole("\r\n");
        $this->logToConsole('=== TESTING GetTokenHistoryGrouped_Error ===');

        $method = 'getTokenHistoryGrouped';
        $testAddress = 'xxd26114cd6EE289AccF82350c8d8487fedB8A0C07';

        //testing for invalid address format
        $code = 104;
        $this->logToConsole('testing for invalid address format...');
        $this->logToConsole('sending request...');
        $result = $this->sendRequest($method, $testAddress, array('apiKey' => 'freekey'));
        $this->logToConsole('check returned answer...');
        $this->assertTrue(
            is_array($result) && isset($result['error']) && is_array($result['error']),
            sprintf(
                "Invalid response received:\n%s",
                var_export($result, TRUE)
            )
        );
        $this->logToConsole('check if answer contains code 104...');
        $returned_code = $result['error']['code'];
        $this->assertEquals($code, $result['error']['code'], "Returned JSON contains code $returned_code but should contain $code");

        $this->logToConsole("\r\n");
        $this->logToConsole('testing for empty result..');
        //testing for wrong address type

        $testAddress = '0xB97048628DB6B661D4C2aA833e95Dbe1A905B281';
        $this->logToConsole('sending request...');
        $result = $this->sendRequest($method, $testAddress, array('apiKey' => 'freekey'));
        $this->logToConsole('check returned answer is empty...');
        $this->assertTrue(
            is_array($result) && isset($result['countTxs']) && empty($result['countTxs']),
            sprintf(
                "Invalid response received:\n%s",
                var_export($result, TRUE)
            )
        );

        $this->logToConsole("\r\n");
        $this->logToConsole('testing for missing API key...');
        //testing for missing API key
        $code = 1;
        $testAddress = '0xB97048628DB6B661D4C2aA833e95Dbe1A905B281';
        $apiParams = array('freekei', ' ', '');
        foreach ($apiParams as $param)
        {
            $this->logToConsole('sending request...');
            $result = $this->sendRequest($method, $testAddress, array('apiKey' => $param));
            $this->logToConsole('check returned answer...');
            $this->assertTrue(
                is_array($result) && isset($result['error']) && is_array($result['error']),
                sprintf(
                    "Invalid response received:\n%s",
                    var_export($result, TRUE)
                )
            );
            $this->logToConsole('check if answer contains code 1..');
            $returned_code = $result['error']['code'];
            $this->assertEquals($code, $result['error']['code'],"Returned JSON contains code $returned_code but should contain $code");
        }
    }

    public function testGetTokenPriceHistoryGrouped_Success()
    {
        $this->logToConsole("\r\n");
        $this->logToConsole('=== TESTING GetTokenPriceHistoryGrouped_Success ===');

        $method = 'getTokenPriceHistoryGrouped';
        $testAddress = '0xd26114cd6EE289AccF82350c8d8487fedB8A0C07';

        $this->logToConsole('check request without parameters');

        $this->logToConsole('sending request...');
        $result = $this->sendRequest($method, $testAddress, array('apiKey' => 'freekey'));

        $this->logToConsole('check returned answer...');
        $this->assertTrue(
            is_array($result),
            sprintf("Invalid response received:\n%s",var_export($result, TRUE))
        );

        $this->logToConsole('check array contains "history" field...');
        $this->assertTrue(isset($result['history']), 'Array does not contain "history" field');
        $this->assertTrue(!empty($result['history']),sprintf("Returned array is empty:\n%s",var_export($result, TRUE)));

        $history = $result['history'];
        $this->logToConsole('check history object contains "countTxs" and "prices" fields...');
        $this->assertTrue(isset($history['countTxs']), 'Array does not contain "countTxs" field');
        $this->assertTrue(!empty($history['countTxs']),sprintf("Returned array is empty:\n%s",var_export($history, TRUE)));
        $this->assertTrue(isset($history['prices']), 'Array does not contain "prices" field');
        $this->assertTrue(!empty($history['prices']),sprintf("Returned array is empty:\n%s",var_export($history, TRUE)));

        $this->logToConsole("\r\n");
        $this->logToConsole('sending request with "period" field...');
        $result = $this->sendRequest($method, $testAddress, array('apiKey' => 'freekey', 'period'=>'1'));
        $this->logToConsole('check returned answer...');
        $this->assertTrue(
            is_array($result),
            sprintf("Invalid response received:\n%s",var_export($result, TRUE))
        );

        $this->logToConsole('check array contains "history" field...');
        $this->assertTrue(isset($result['history']), 'Array does not contain "history" field');
        $this->assertTrue(!empty($result['history']),sprintf("Returned array is empty:\n%s",var_export($result, TRUE)));

        $history = $result['history'];
        $this->logToConsole('check history object contains "countTxs" and "prices" fields...');
        $this->assertTrue(isset($history['countTxs']), 'Array does not contain "countTxs" field');
        $this->assertTrue(!empty($history['countTxs']),sprintf("Returned array is empty:\n%s",var_export($history, TRUE)));
        $this->assertTrue(isset($history['prices']), 'Array does not contain "prices" field');
        $this->assertTrue(!empty($history['prices']),sprintf("Returned array is empty:\n%s",var_export($history, TRUE)));
    }

    public function testGetTokenPriceHistoryGrouped_Error()
    {
        $this->logToConsole("\r\n");
        $this->logToConsole('=== TESTING GetTokenPriceHistoryGrouped_Error ===');

        $method = 'getTokenPriceHistoryGrouped';
        $testAddress = 'xxd26114cd6EE289AccF82350c8d8487fedB8A0C07';

        //testing for invalid address format
        $code = 104;
        $this->logToConsole('testing for invalid address format...');
        $this->logToConsole('sending request...');
        $result = $this->sendRequest($method, $testAddress, array('apiKey' => 'freekey'));
        $this->logToConsole('check returned answer...');
        $this->assertTrue(
            is_array($result) && isset($result['error']) && is_array($result['error']),
            sprintf(
                "Invalid response received:\n%s",
                var_export($result, TRUE)
            )
        );
        $this->logToConsole('check if answer contains code 104...');
        $returned_code = $result['error']['code'];
        $this->assertEquals($code, $result['error']['code'], "Returned JSON contains code $returned_code but should contain $code");

        $this->logToConsole("\r\n");
        $this->logToConsole('testing for empty result..');
        //testing for wrong address type

        $testAddress = '0xB97048628DB6B661D4C2aA833e95Dbe1A905B281';
        $this->logToConsole('sending request...');
        $result = $this->sendRequest($method, $testAddress, array('apiKey' => 'freekey'));
        $this->logToConsole('check returned answer is empty...');

        $history = $result['history'];
        $this->assertTrue(
            is_array($history) && isset($history['countTxs']) && empty($history['countTxs']),
            sprintf(
                "Invalid response received:\n%s",
                var_export($result, TRUE)
            )
        );

        $this->logToConsole("\r\n");
        $this->logToConsole('testing for missing API key...');
        //testing for missing API key
        $code = 1;
        $testAddress = '0xB97048628DB6B661D4C2aA833e95Dbe1A905B281';
        $apiParams = array('freekei', ' ', '');
        foreach ($apiParams as $param)
        {
            $this->logToConsole('sending request...');
            $result = $this->sendRequest($method, $testAddress, array('apiKey' => $param));
            $this->logToConsole('check returned answer...');
            $this->assertTrue(
                is_array($result) && isset($result['error']) && is_array($result['error']),
                sprintf(
                    "Invalid response received:\n%s",
                    var_export($result, TRUE)
                )
            );
            $this->logToConsole('check if answer contains code 1..');
            $returned_code = $result['error']['code'];
            $this->assertEquals($code, $result['error']['code'],"Returned JSON contains code $returned_code but should contain $code");
        }
    }
}