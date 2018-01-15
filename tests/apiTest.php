<?php
namespace apiTests;
require "vendor/autoload.php";

use EverexIO\PHPUnitIterator\TestCase;

class apiTest extends TestCase
{
    protected $url = 'https://api.ethplorer.io/';
    const APIKey = 'freekey';

    /**
     * @dataProvider provider
     */
    public function testAPI($test)
    {
        $this->_iterateTest($test);
    }

    public function provider()
    {
        return [
            // ========================== getTokenInfo =========================================================
            // Success
            [[
                'method' => 'getTokenInfo',
                'description' => '= Success =',
                'URL_params' => '0xB97048628DB6B661D4C2aA833e95Dbe1A905B280',
                'GET_params' =>  ['apiKey' => apiTest::APIKey],
                'asserts' => [
                    // Must be converted to ->assertTrue(isset($result[field]))
                    ['type' => 'isset',    'fields' => ['address', 'totalSupply', 'holdersCount']],
                    // If type name starts with "!" - check with assertFalse instead of assertTrue should be used
                    ['type' => '!isset',   'fields' => ['error']],
                    ['fields' => 'address', 'equals' => '0xB97048628DB6B661D4C2aA833e95Dbe1A905B280'],
                ]
            ]],
            // Error: invalid address format
            [[
                'method' => 'getTokenInfo',
                'description' => '= Error: invalid address format =',
                'URL_params' => '0xB97048628DB6B661D4C2aA833e95Dbe1A905B28',
                'GET_params' =>  ['apiKey' => apiTest::APIKey],
                'asserts' => [
                    ['type' => 'isset',   'fields' => ['error']],
                    ['fields' => 'error:code', 'equals' => 104],
                ]
            ]],
            // Error: address is not a token
            [[
                'method' => 'getTokenInfo',
                'description' => '= Error: address is not a token =',
                'URL_params' => '0xB97048628DB6B661D4C2aA833e95Dbe1A905B281',
                'GET_params' =>  ['apiKey' => apiTest::APIKey],
                'asserts' => [
                    ['type' => 'isset',   'fields' => ['error']],
                    ['fields' => 'error:code', 'equals' => 150],
                ]
            ]],
            // Error: invalid API key (using iteration)
            [[
                '_iterate' => [
                    'GET_params:apiKey' => ['Invalid Key', ' ', '', 0],
                ],
                'method' => 'getTokenInfo',
                'description' => '= Error: invalid API key (using iteration) =',
                'URL_params' => '0xB97048628DB6B661D4C2aA833e95Dbe1A905B281',
                'GET_params' =>  ['apiKey' => null],
                'asserts' => [
                    ['type' => 'isset',   'fields' => 'error'],
                    ['fields' => 'error:code', 'equals' => 1],
                ]
            ]],
            // ========================== getAddressInfo =========================================================
            // Success
            [[
                'method' => 'getAddressInfo',
                'description' => '= Success =',
                'URL_params' => '0xd26114cd6EE289AccF82350c8d8487fedB8A0C07',
                'GET_params' =>  ['apiKey' => apiTest::APIKey],
                'asserts' => [
                    ['type' => 'isset',    'fields' => ['address', 'ETH', 'countTxs']],
                    ['type' => '!isset',   'fields' => ['error']],
                    ['fields' => 'address', 			'equals' => strtolower('0xd26114cd6EE289AccF82350c8d8487fedB8A0C07')],
                    ['fields' => 'contractInfo:creatorAddress', 	'equals' => '0x140427a7d27144a4cda83bd6b9052a63b0c5b589'],
                    ['fields' => 'tokenInfo:address', 		'equals' => '0xd26114cd6ee289accf82350c8d8487fedb8a0c07'],
                ]
            ]],
            // Success with token parameter
            [[
                'method' => 'getAddressInfo',
                'description' => '= Success with token parameter =',
                'URL_params' => '0xd26114cd6EE289AccF82350c8d8487fedB8A0C07',
                'GET_params' =>  ['apiKey' => apiTest::APIKey, 'token' => '0x49aec0752e68d0282db544c677f6ba407ba17ed7'],
                'asserts' => [
                    ['type' => 'isset',    'fields' => ['address', 'ETH', 'countTxs']],
                    ['fields' => 'address', 				'equals' => strtolower('0xd26114cd6EE289AccF82350c8d8487fedB8A0C07')],
                    ['fields' => 'contractInfo:creatorAddress', 	'equals' => '0x140427a7d27144a4cda83bd6b9052a63b0c5b589'],
                    ['fields' => 'tokenInfo:address', 		'equals' => '0xd26114cd6ee289accf82350c8d8487fedb8a0c07'],

                    ['fields' => 'tokens', 'type' => 'count',		'equals' => 1],
                    ['fields' => 'tokens:int(0):tokenInfo:address', 	'equals' => '0x49aec0752e68d0282db544c677f6ba407ba17ed7'],
                ]
            ]],
            // Error: invalid address format
            [[
                'method' => 'getAddressInfo',
                'description' => '= Error: invalid address format =',
                'URL_params' => '0xB97048628DB6B661D4C2aA833e95Dbe1A905B28',
                'GET_params' =>  ['apiKey' => apiTest::APIKey],
                'asserts' => [
                    ['type' => 'isset',   'fields' => ['error']],
                    ['fields' => 'error:code', 'equals' => 104],
                ]
            ]],
            // Error: wrong token type
            [[
                'method' => 'getAddressInfo',
                'description' => '= wrong token type =',
                'URL_params' => '0xB97048628DB6B661D4C2aA833e95Dbe1A905B28',
                'GET_params' => [
                    'apiKey' => apiTest::APIKey,
                    'token' => 'ec0752e68d0282db544c677f6ba407ba17ed7'
                    ],
                'asserts' => [
                    ['type' => 'isset',   'fields' => ['error']],
                    ['fields' => 'error:code', 'equals' => 104],
                ]
            ]],
            // Error: invalid API key (using iteration)
            [[
                '_iterate' => [
                    'GET_params:apiKey' => ['Invalid Key', ' ', '', 0],
                ],
                'description' => '= Error: invalid API key (using iteration) =',
                'method' => 'getAddressInfo',
                'URL_params' => '0xB97048628DB6B661D4C2aA833e95Dbe1A905B281',
                'GET_params' =>  ['apiKey' => null],
                'asserts' => [
                    ['type' => 'isset',   'fields' => 'error'],
                    ['fields' => 'error:code', 'equals' => 1],
                ]
            ]],
            // ========================== getTokenHistory =========================================================
            // Check limit
            [[
                'method' => 'getTokenHistory',
                'description' => '= Check limit =',
                'URL_params' => '0xd26114cd6EE289AccF82350c8d8487fedB8A0C07',
                'GET_params' =>  ['apiKey' => apiTest::APIKey, 'limit' => 5],
                'asserts' => [
                    ['type' => 'isset',    'fields' => ['operations']],
                    ['type' => '!empty',   'fields' => ['operations']],
                    ['type' => '!isset',     'fields' => ['error']],
                    // Check that count($result['operations']) is in 1 - 5 range:
                    ['fields' => ['operations'], 'type' => 'count', 'gt' => 0], // > 0
                    ['fields' => ['operations'], 'type' => 'count', 'lt' => 6], // < 6
                    // Same check in a single string
                    ['fields' => ['operations'], 'type' => 'count', 'range' => [1, 5]] // 1 <= value <= 5
                ]
            ]],
            // Incorrect limit value
            [[
                'method' => 'getTokenHistory',
                'description' => 'Check with incorrect limit value',
                'URL_params' => '0xd26114cd6EE289AccF82350c8d8487fedB8A0C07',
                'GET_params' =>  ['apiKey' => apiTest::APIKey, 'limit' => 'incorrect'],
                'asserts' => [
                    ['type' => 'isset',    'fields' => ['operations']],
                    ['type' => '!empty',   'fields' => ['operations']],
                    ['type' => '!isset',     'fields' => ['error']],
                    ['fields' => ['operations'], 'type' => 'count', 'range' => [1, 50]]
                ]
            ]],
            // Limit value below the range
            [[
                'method' => 'getTokenHistory',
                'description' => 'Check with limit = 0',
                'URL_params' => '0xd26114cd6EE289AccF82350c8d8487fedB8A0C07',
                'GET_params' =>  ['apiKey' => apiTest::APIKey, 'limit' => 0],
                'asserts' => [
                    ['type' => 'isset',    'fields' => ['operations']],
                    ['type' => '!empty',   'fields' => ['operations']],
                    ['type' => '!isset',     'fields' => ['error']],
                    ['fields' => ['operations'], 'type' => 'count', 'range' => [1, 50]],
                ]
            ]],
            // Limit value above the range
            [[
                'method' => 'getTokenHistory',
                'description' => 'Check with limit = 51',
                'URL_params' => '0xd26114cd6EE289AccF82350c8d8487fedB8A0C07',
                'GET_params' =>  ['apiKey' => apiTest::APIKey, 'limit' => 51],
                'asserts' => [
                    ['type' => 'isset',    'fields' => ['operations']],
                    ['type' => '!empty',   'fields' => ['operations']],
                    ['type' => '!isset',     'fields' => ['error']],
                    ['fields' => ['operations'], 'type' => 'count', 'range' => [1, 50]],
                ]
            ]],

            // ========================== getTxInfo =========================================================
            // Success
            [[
                'method' => 'getTxInfo',
                'URL_params' => '0x0bd079304c36ff6741382125e1ba4bd02cdd29dd30f7a08b8ccd9e801cbc2be3',
                'GET_params' =>  ['apiKey' => apiTest::APIKey],
                'asserts' => [
                    ['type' => 'isset',    'fields' => ['hash', 'timestamp', 'blockNumber', 'from', 'value', 'gasLimit', 'gasUsed']],
                    ['fields' => 'hash',    'equals' => '0x0bd079304c36ff6741382125e1ba4bd02cdd29dd30f7a08b8ccd9e801cbc2be3'],
                    ['fields' => 'gasUsed', 'equals' => 40843]
                ]
            ]],
            // Error: invalid tx
            [[
                'method' => 'getTxInfo',
                'URL_params' => '0x0bd079304c36ff6741382125e1ba4bd02cdd29dd30f7a08b8ccd9e801cbc2be2',
                'GET_params' =>  ['apiKey' => apiTest::APIKey],
                'asserts' => [
                    ['type' => 'isset', 'fields' => 'error'],
                    ['fields' => 'error:code', 'equals' => 404],
                ]
            ]],
            // Error: invalid tx format
            [[
                'method' => 'getTxInfo',
                'description' => 'Checks tx format',
                'URL_params' => 'xx0bd079304c36ff6741382125e1ba4bd02cdd29dd30f7a08b8ccd9e801cbc2be2',
                'GET_params' =>  ['apiKey' => apiTest::APIKey],
                'asserts' => [
                    ['type' => 'isset', 'fields' => 'error'],
                    ['fields' => 'error:code', 'equals' => 102],
                ]
            ]],
            // Error: invalid API key (using iteration)
            [[
                '_iterate' => [
                    'GET_params:apiKey' => ['Invalid Key', ' ', '', 0],
                ],
                'method' => 'getTxInfo',
                'URL_params' => '0x0bd079304c36ff6741382125e1ba4bd02cdd29dd30f7a08b8ccd9e801cbc2be3',
                'GET_params' =>  ['apiKey' => null],
                'asserts' => [
                    ['type' => 'isset', 'fields' => ['error']],
                    ['fields' => ['error:code'], 'equals' => 1],
                ]
            ]],
            // ========================== getAddressHistory =========================================================
            // Success
            // Check limit 'token'=>'0xb9b4cfe4194d7e8511aa9b9f1260bc7b9634249e'
            [[
                'method' => 'getAddressHistory',
                'description' => '= Check limit =',
                'URL_params' => '0xd26114cd6EE289AccF82350c8d8487fedB8A0C07',
                'GET_params' =>  ['apiKey' => apiTest::APIKey, 'limit' => 5, ],
                'asserts' => [
                    ['type' => 'isset',    'fields' => ['operations']],
                    ['type' => '!empty',   'fields' => ['operations']],
                    ['type' => '!isset',     'fields' => ['error']],
                    ['fields' => ['operations'], 'type' => 'count', 'gt' => 0], // > 0
                    ['fields' => ['operations'], 'type' => 'count', 'lt' => 6], // < 6
                    ['fields' => ['operations'], 'type' => 'count', 'range' => [1, 5]] // 1 <= value <= 5
                ]
            ]],
            // Check limit with token parameter
            [[
                'method' => 'getAddressHistory',
                'description' => '= Check limit with token parameter =',
                'URL_params' => '0xd26114cd6EE289AccF82350c8d8487fedB8A0C07',
                'GET_params' =>  ['apiKey' => apiTest::APIKey, 'limit' => 5, 'token'=>'0xb9b4cfe4194d7e8511aa9b9f1260bc7b9634249e'],
                'asserts' => [
                    ['type' => 'isset',    'fields' => ['operations']],
                    ['type' => '!empty',   'fields' => ['operations']],
                    ['type' => '!isset',     'fields' => ['error']],
                    ['fields' => ['operations'], 'type' => 'count', 'gt' => 0], // > 0
                    ['fields' => ['operations'], 'type' => 'count', 'lt' => 6], // < 6
                    ['fields' => ['operations'], 'type' => 'count', 'range' => [1, 5]] // 1 <= value <= 5
                ]
            ]],
            // Incorrect limit value
            [[
                'method' => 'getAddressHistory',
                'description' => 'Check with incorrect limit value',
                'URL_params' => '0xd26114cd6EE289AccF82350c8d8487fedB8A0C07',
                'GET_params' =>  ['apiKey' => apiTest::APIKey, 'limit' => 'incorrect'],
                'asserts' => [
                    ['type' => 'isset',    'fields' => ['operations']],
                    ['type' => '!empty',   'fields' => ['operations']],
                    ['type' => '!isset',     'fields' => ['error']],
                    ['fields' => ['operations'], 'type' => 'count', 'range' => [1, 50]]
                ]
            ]],
            // Limit value below the range
            [[
                'method' => 'getAddressHistory',
                'description' => 'Check with limit = 0',
                'URL_params' => '0xd26114cd6EE289AccF82350c8d8487fedB8A0C07',
                'GET_params' =>  ['apiKey' => apiTest::APIKey, 'limit' => 0],
                'asserts' => [
                    ['type' => 'isset',    'fields' => ['operations']],
                    ['type' => '!empty',   'fields' => ['operations']],
                    ['type' => '!isset',     'fields' => ['error']],
                    ['fields' => ['operations'], 'type' => 'count', 'range' => [1, 50]],
                ]
            ]],
            // Limit value above the range
            [[
                'method' => 'getAddressHistory',
                'description' => 'Check with limit = 51',
                'URL_params' => '0xd26114cd6EE289AccF82350c8d8487fedB8A0C07',
                'GET_params' =>  ['apiKey' => apiTest::APIKey, 'limit' => 51],
                'asserts' => [
                    ['type' => 'isset',    'fields' => ['operations']],
                    ['type' => '!empty',   'fields' => ['operations']],
                    ['type' => '!isset',     'fields' => ['error']],
                    ['fields' => ['operations'], 'type' => 'count', 'range' => [1, 50]],
                ]
            ]],
            // Error: invalid address format
            [[
                'method' => 'getAddressHistory',
                'description' => '= Error: invalid address format =',
                'URL_params' => '0xd26114cd6EE289AccF82350c8d8487fedB8A0C0',
                'GET_params' =>  ['apiKey' => apiTest::APIKey],
                'asserts' => [
                    ['type' => 'isset',   'fields' => ['error']],
                    ['fields' => 'error:code', 'equals' => 104],
                ]
            ]],
            // Error: invalid API key (using iteration)
            [[
                '_iterate' => [
                    'GET_params:apiKey' => ['Invalid Key', ' ', '', 0],
                ],
                'method' => 'getAddressHistory',
                'URL_params' => '0xd26114cd6EE289AccF82350c8d8487fedB8A0C0',
                'GET_params' =>  ['apiKey' => null],
                'asserts' => [
                    ['type' => 'isset', 'fields' => ['error']],
                    ['fields' => ['error:code'], 'equals' => 1],
                ]
            ]],
            // ========================== getAddressTransactions =========================================================
            // Success
            [[
                'method' => 'getAddressTransactions',
                'description' => '= check request without parameters =',
                'URL_params' => '0xd26114cd6EE289AccF82350c8d8487fedB8A0C07',
                'GET_params' =>  ['apiKey' => apiTest::APIKey, ],
                'asserts' => [
                    ['type' => 'isset', 'array' => 'true', 'fields' => ['timestamp', 'from', 'to', 'hash', 'value', 'input', 'success']],
                    ['type' => '!isset',     'fields' => ['error']],
                ]
            ]],
            //check request with "limit" parameter
            [[
                'method' => 'getAddressTransactions',
                'description' => '= check request with "limit" parameter =',
                'URL_params' => '0xd26114cd6EE289AccF82350c8d8487fedB8A0C07',
                'GET_params' =>  ['apiKey' => apiTest::APIKey, 'limit'=>'5'],
                'asserts' => [
                    ['type' => 'isset', 'array' => 'true', 'fields' => ['timestamp', 'from', 'to', 'hash', 'value', 'input', 'success']],
                    ['type' => '!isset',     'fields' => ['error']],
                    ['fields' => [''], 'array' => 'true', 'type' => 'count', 'lt' => 6],
                ]
            ]],
            //check request with "showZeroValues" parameter
            [[
                'method' => 'getAddressTransactions',
                'description' => '= check request with "showZeroValues" parameter =',
                'URL_params' => '0xd26114cd6EE289AccF82350c8d8487fedB8A0C07',
                'GET_params' =>  ['apiKey' => apiTest::APIKey, 'showZeroValues'=>'true'],
                'asserts' => [
                    ['type' => 'isset', 'array' => 'true', 'fields' => ['timestamp', 'from', 'to', 'hash', 'value', 'input', 'success']],
                    ['type' => '!isset',     'fields' => ['error']],
                    ['fields' => ['value'], 'array' => 'true', 'equals' => '0'],
                ]
            ]],
            //check if limit=0 return correct amount of objects
            [[
                'method' => 'getAddressTransactions',
                'description' => '= check if limit=0 return correct amount of objects =',
                'URL_params' => '0xd26114cd6EE289AccF82350c8d8487fedB8A0C07',
                'GET_params' =>  ['apiKey' => apiTest::APIKey, 'showZeroValues'=>'true'],
                'asserts' => [
                    ['type' => 'isset', 'array' => 'true', 'fields' => ['timestamp', 'from', 'to', 'hash', 'value', 'input', 'success']],
                    ['type' => '!isset',  'fields' => ['error']],
                    ['fields' => ['value'], 'array' => 'true',  'type' => 'count', 'range' => [1, 50]],
                ]
            ]],
            //Error: invalid address format
            [[
                'method' => 'getAddressTransactions',
                'description' => '= Error: invalid address format =',
                'URL_params' => '0xd26114cd6EE289AccF82350c8d8487fedB8A0C0',
                'GET_params' =>  ['apiKey' => apiTest::APIKey],
                'asserts' => [
                    ['type' => 'isset',   'fields' => ['error']],
                    ['fields' => 'error:code', 'equals' => 104],
                ]
            ]],
            //Error: empty result
            [[
                'method' => 'getAddressTransactions',
                'description' => '= Error: empty result =',
                'URL_params' => '0xB97048628DB6B661D4C2aA833e95Dbe1A905B281',
                'GET_params' =>  ['apiKey' => apiTest::APIKey],
                'asserts' => [
                    ['type' => 'empty',   'fields' => [' ']],
                ]
            ]],
            //Error: request with wrong "limit" parameter contains only 1 object
            [[
                'method' => 'getAddressTransactions',
                'description' => '= request with wrong "limit" parameter contains only 1 object =',
                'URL_params' => '0xd26114cd6EE289AccF82350c8d8487fedB8A0C07',
                'GET_params' =>  ['apiKey' => apiTest::APIKey, 'limit' => 'asd'],
                'asserts' => [
                    ['fields' => [''], 'array' => 'true', 'type' => 'count', 'range' => [1,1]],
                ]
            ]],
            // Error: invalid API key (using iteration)
            [[
                '_iterate' => [
                    'GET_params:apiKey' => ['Invalid Key', ' ', '', 0],
                ],
                'method' => 'getAddressTransactions',
                'URL_params' => '0xB97048628DB6B661D4C2aA833e95Dbe1A905B281',
                'GET_params' =>  ['apiKey' => null],
                'asserts' => [
                    ['type' => 'isset', 'fields' => ['error']],
                    ['fields' => ['error:code'], 'equals' => 1],
                ]
            ]],
            // ========================== getTopTokens =========================================================
            // Success
            [[
                'method' => 'getTopTokens',
                'description' => '= request without parameters =',
                'URL_params' => '',
                'GET_params' =>  ['apiKey' => apiTest::APIKey],
                'asserts' => [
                    ['type' => 'isset',    'fields' => ['tokens']],
                    ['type' => '!empty',   'fields' => ['tokens']],
                    ['type' => '!isset',     'fields' => ['error']],
                    ['fields' => ['tokens'], 'type' => 'count', 'gt' => 0], // > 0
                ]
            ]],
            // request with "period" parameter
            [[
                'method' => 'getTopTokens',
                'description' => '= request with "period" parameter =',
                'URL_params' => '',
                'GET_params' =>  ['apiKey' => apiTest::APIKey, 'period'=>'5'],
                'asserts' => [
                    ['type' => 'isset',    'fields' => ['tokens']],
                    ['type' => '!empty',   'fields' => ['tokens']],
                    ['type' => '!isset',     'fields' => ['error']],
                    ['fields' => ['tokens'], 'type' => 'count', 'gt' => 0], // > 0
                ]
            ]],
            // request with "limit" parameter
            [[
                'method' => 'getTopTokens',
                'description' => '= request with "limit" parameter =',
                'URL_params' => '',
                'GET_params' =>  ['apiKey' => apiTest::APIKey, 'limit'=>'5'],
                'asserts' => [
                    ['type' => 'isset',    'fields' => ['tokens']],
                    ['type' => '!empty',   'fields' => ['tokens']],
                    ['type' => '!isset',     'fields' => ['error']],
                    ['fields' => ['tokens'], 'type' => 'count', 'gt' => 0], // > 0
                    ['fields' => ['tokens'], 'type' => 'count', 'lt' => 6], // > 0
                ]
            ]],
            // request with "limit" parameter equals 0
            [[
                'method' => 'getTopTokens',
                'description' => '= request with "limit" parameter equals 0 =',
                'URL_params' => '',
                'GET_params' =>  ['apiKey' => apiTest::APIKey, 'limit'=>'5'],
                'asserts' => [
                    ['type' => 'isset',    'fields' => ['tokens']],
                    ['type' => '!empty',   'fields' => ['tokens']],
                    ['type' => '!isset',     'fields' => ['error']],
                    ['fields' => ['tokens'], 'type' => 'count', 'range' => [1, 50]]
                ]
            ]],
            // request with "limit" parameter equals 51
            [[
                'method' => 'getTopTokens',
                'description' => '= request with "limit" parameter equals 51 =',
                'URL_params' => '',
                'GET_params' =>  ['apiKey' => apiTest::APIKey, 'limit'=>'51'],
                'asserts' => [
                    ['type' => 'isset',    'fields' => ['tokens']],
                    ['type' => '!empty',   'fields' => ['tokens']],
                    ['type' => '!isset',     'fields' => ['error']],
                    ['fields' => ['tokens'], 'type' => 'count', 'range' => [1, 50]]
                ]
            ]],
            //Errors
            //Error: period equals 0
            [[
                'method' => 'getTopTokens',
                'description' => '= Error: period equals 0 =',
                'URL_params' => '',
                'GET_params' =>  ['apiKey' => apiTest::APIKey, 'period'=>'0'],
                'asserts' => [
                    ['type' => 'isset',    'fields' => ['tokens']],
                    ['type' => '!empty',   'fields' => ['tokens']],
                    ['type' => '!isset',     'fields' => ['error']]
                ]
            ]],
            // request with wrong "limit" parameter
            [[
                'method' => 'getTopTokens',
                'description' => '= request with wrong "limit" parameter  =',
                'URL_params' => '',
                'GET_params' =>  ['apiKey' => apiTest::APIKey, 'limit'=>'asd'],
                'asserts' => [
                    ['type' => 'isset',    'fields' => ['tokens']],
                    ['type' => '!empty',   'fields' => ['tokens']],
                    ['type' => '!isset',     'fields' => ['error']],
                    ['fields' => ['tokens'], 'type' => 'count', 'lt' => 2],
                ]
            ]],
            // Error: invalid API key (using iteration)
            [[
                '_iterate' => [
                    'GET_params:apiKey' => ['Invalid Key', ' ', '', 0],
                ],
                'method' => 'getTopTokens',
                'URL_params' => '0xB97048628DB6B661D4C2aA833e95Dbe1A905B281',
                'GET_params' =>  ['apiKey' => null],
                'asserts' => [
                    ['type' => 'isset', 'fields' => ['error']],
                    ['fields' => ['error:code'], 'equals' => 1],
                ]
            ]],
            // ========================== getTokenHistoryGrouped =========================================================
            // Success
            [[
                'method' => 'getTokenHistoryGrouped',
                'description' => '= request without parameters =',
                'URL_params' => '0xd26114cd6EE289AccF82350c8d8487fedB8A0C07',
                'GET_params' =>  ['apiKey' => apiTest::APIKey],
                'asserts' => [
                    ['type' => 'isset',    'fields' => ['countTxs']],
                    ['type' => '!empty',   'fields' => ['countTxs']],
                    ['type' => '!isset',     'fields' => ['error']],
                ]
            ]],
            // request with "period" field
            [[
                'method' => 'getTokenHistoryGrouped',
                'description' => '= request with "period" field =',
                'URL_params' => '0xd26114cd6EE289AccF82350c8d8487fedB8A0C07',
                'GET_params' =>  ['apiKey' => apiTest::APIKey, 'period'=>'1'],
                'asserts' => [
                    ['type' => 'isset',    'fields' => ['countTxs']],
                    ['type' => '!isset',     'fields' => ['error']],
                ]
            ]],
            //Errors
            //Error: invalid address format
            [[
                'method' => 'getTokenHistoryGrouped',
                'description' => '= Error: invalid address format =',
                'URL_params' => 'xxd26114cd6EE289AccF82350c8d8487fedB8A0C07',
                'GET_params' =>  ['apiKey' => apiTest::APIKey],
                'asserts' => [
                    ['type' => 'isset', 'fields' => ['error']],
                    ['fields' => ['error:code'], 'equals' => 104],
                ]
            ]],
            //Error: empty result
            [[
                'method' => 'getTokenHistoryGrouped',
                'description' => '= Error: empty result =',
                'URL_params' => '0xB97048628DB6B661D4C2aA833e95Dbe1A905B281',
                'GET_params' =>  ['apiKey' => apiTest::APIKey],
                'asserts' => [
                    ['type' => 'isset',    'fields' => ['countTxs']],
                    ['type' => 'empty',   'fields' => ['countTxs']],
                    ['type' => '!isset',     'fields' => ['error']],
                ]
            ]],
            // Error: invalid API key (using iteration)
            [[
                '_iterate' => [
                    'GET_params:apiKey' => ['Invalid Key', ' ', '', 0],
                ],
                'method' => 'getTokenHistoryGrouped',
                'URL_params' => '0xd26114cd6EE289AccF82350c8d8487fedB8A0C07',
                'GET_params' =>  ['apiKey' => null],
                'asserts' => [
                    ['type' => 'isset', 'fields' => ['error']],
                    ['fields' => ['error:code'], 'equals' => 1],
                ]
            ]],
            // ========================== getTokenHistoryGrouped =========================================================
            // Success
            [[
                'method' => 'getTokenPriceHistoryGrouped',
                'description' => '= request without parameters =',
                'URL_params' => '0xd26114cd6EE289AccF82350c8d8487fedB8A0C07',
                'GET_params' =>  ['apiKey' => apiTest::APIKey],
                'asserts' => [
                    ['type' => 'isset',    'fields' => ['history', 'history:countTxs', 'history:prices']],
                    ['type' => '!empty',   'fields' => ['history']],
                    ['type' => '!isset',     'fields' => ['error']],
                ]
            ]],
            // request with "period" field
            [[
                'method' => 'getTokenPriceHistoryGrouped',
                'description' => '= request with "period" field =',
                'URL_params' => '0xd26114cd6EE289AccF82350c8d8487fedB8A0C07',
                'GET_params' =>  ['apiKey' => apiTest::APIKey, 'period'=>'1'],
                'asserts' => [
                    ['type' => 'isset',    'fields' => ['history', 'history:countTxs', 'history:prices']],
                    ['type' => '!empty',   'fields' => ['history']],
                    ['type' => '!isset',     'fields' => ['error']],
                ]
            ]],
            //Errors
            //Error: invalid address format
            [[
                'method' => 'getTokenPriceHistoryGrouped',
                'description' => '= Error: invalid address format =',
                'URL_params' => 'xxd26114cd6EE289AccF82350c8d8487fedB8A0C07',
                'GET_params' =>  ['apiKey' => apiTest::APIKey],
                'asserts' => [
                    ['type' => 'isset', 'fields' => ['error']],
                    ['fields' => ['error:code'], 'equals' => 104],
                ]
            ]],
            //Error: empty result
            [[
                'method' => 'getTokenPriceHistoryGrouped',
                'description' => '= Error: empty result =',
                'URL_params' => '0xB97048628DB6B661D4C2aA833e95Dbe1A905B281',
                'GET_params' =>  ['apiKey' => apiTest::APIKey],
                'asserts' => [
                    ['type' => 'empty',   'fields' => ['history:countTxs']],
                    ['type' => '!isset',     'fields' => ['error']],
                ]
            ]],
            // Error: invalid API key (using iteration)
            [[
                '_iterate' => [
                    'GET_params:apiKey' => ['Invalid Key', ' ', '', 0],
                ],
                'method' => 'getTokenPriceHistoryGrouped',
                'URL_params' => '0xd26114cd6EE289AccF82350c8d8487fedB8A0C07',
                'GET_params' =>  ['apiKey' => null],
                'asserts' => [
                    ['type' => 'isset', 'fields' => ['error']],
                    ['fields' => ['error:code'], 'equals' => 1],
                ]
            ]]
        ];
    }
}