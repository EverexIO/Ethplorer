<?php

class Metrics {

    const STATSD_REDIS_PREFIX = 'redis';

    const STATSD_API_METHOD_PREFIX = 'api-method';

    const STATSD_PHP_PREFIX = 'script-php';

    const STATSD_TOTAL_SERVER_NAME = 'total-servers';

    static protected $metric = false;

    static protected $timings = [];

    static protected $startTimings = [];

    static protected $apiMethodName = '-';

    static protected $statsd = false;

    static protected $statsdOptions = false;

    private function __construct() {}

    static protected $meticPrefixWithCountPerRequest = [
        self::STATSD_REDIS_PREFIX
    ];

    /**
     * @var array
     */
    static protected $redisPrefixesWithDash = [
        'highloaded-address',
        'top_tokens-by-period-volume',
        'top_tokens-by-current-volume',
        'block-txs',
        'rates-history',
        'cap-history',
        'tokens_count',
        'tokens-simple',
        'tokens',
        'lastBlock',
        'top_tokens_totals'
    ];

    static public function setApiMethodName($name) {
        self::$apiMethodName = $name;
    }

    static protected function setTiming($prefix, $value) {
        if(empty(self::$timings[$prefix])) {
            self::$timings[$prefix] = [];
        }
        self::$timings[$prefix][] = $value;
    }

    protected function startTiming($prefix) {
        self::$startTimings[$prefix] = microtime(true);
    }

    protected function stopTiming($prefix) {
        if (!empty(self::$startTimings[$prefix])) {
            self::setTiming($prefix, microtime(true) - self::$startTimings[$prefix]);
            unset(self::$startTimings[$prefix]);
        }
    }

    static public function initMetric(array $statsdOptions) {
        self::$metric = true;
        self::$statsdOptions = $statsdOptions;
        self::$statsd = false;
        register_shutdown_function(function() {
            if (!(empty(self::$metric))) {
                $time = microtime(true);
                self::sendMetrics();
                $statsd = self::getConnections();
                if (!empty($statsd)) {
                    $statsd->timing('statsd.metric-send', microtime(true) - $time);
                }
            }
        });
    }

    static protected function getConnections() {
        if (empty(self::$statsd) && !empty(self::$statsdOptions)) {
            $time = microtime(true);
            $connection = new \Domnikl\Statsd\Connection\UdpSocket(
                self::$statsdOptions['host'],
                self::$statsdOptions['port'],
                isset(self::$statsdOptions['timeout']) ? self::$statsdOptions['timeout'] : 0.5,
                isset(self::$statsdOptions['persist']) ? self::$statsdOptions['persist'] : true
            );
            self::$statsd = new \Domnikl\Statsd\Client($connection, self::$statsdOptions['prefix']);
            self::setTiming('statsd.connection-time', microtime(true) - $time);
        }
        return self::$statsd;
    }

    static public function sendMetrics() {
        if (!(empty(self::$metric))) {
            $time = microtime(true);
            $endTime = false;
            self::sendAllTimingWithPrefix();
            if (
                isset(self::$statsdOptions['duplicate_metrics_with_hostname']) &&
                self::$statsdOptions['duplicate_metrics_with_hostname']
            ) {
                $hostname = explode('.', gethostname())[0];
                if (!empty($hostname)) {
                    self::sendAllTimingWithPrefix($hostname);
                    $endTime = microtime(true) - $time;
                    self::$statsd->timing(
                        $hostname . '.statsd.metric-send',
                        $endTime
                    );
                }
            }
            if (!$endTime) {
                $endTime = microtime(true) - $time;
            }
            self::$statsd->timing(
                self::STATSD_TOTAL_SERVER_NAME . '.statsd.metric-send',
                $endTime
            );
            self::$timings = [];
        }
    }

    static public function sendAllTimingWithPrefix($serverPrefix = self::STATSD_TOTAL_SERVER_NAME) {
        $timings = self::$timings;
        $statsd = self::getConnections();
        if (empty($statsd)) {
            return;
        }
        $statsd->startBatch();
        foreach ($timings as $prefix => $metricValues) {
            if (count($metricValues)) {
                foreach (self::$meticPrefixWithCountPerRequest as $prefixForCounting) {
                    if (
                        strpos($prefix, $prefixForCounting) === 0 &&
                        strpos($prefix, '.times.') !== -1
                    ) {
                        $statsd->timing(
                            str_replace(
                                '.times.',
                                '.cnt-per-req.' . self::$apiMethodName . '.',
                                $serverPrefix . '.' . $prefix
                            ),
                            count($metricValues)
                        );
                    }
                }
            }
            foreach ($metricValues as $value) {
                $statsd->timing($serverPrefix . '.' . $prefix, $value);
            }
        }
        $statsd->endBatch();
    }

    static protected function getRedisKeyPrefix($redisKey) {
        foreach (self::$redisPrefixesWithDash as $prefix) {
            if (strpos($redisKey, $prefix) === 0) {
                return $prefix;
            }
        }
        return explode('-', $redisKey)[0];
    }

    static public function startRedisTiming($method, $redisKey) {
        if (self::$metric) {
            $redisKeyPrefix = self::getRedisKeyPrefix($redisKey);
            self::startTiming(sprintf(
                '%s.%s.times.%s',
                self::STATSD_REDIS_PREFIX,
                $method,
                $redisKeyPrefix
            ));
        }
    }

    static public function getPhpPrefix() {
        return str_replace('.', '-', basename($_SERVER['SCRIPT_FILENAME']));
    }

    static public function getFirstUrlSegment() {
        $segments = explode('/', $_SERVER['REQUEST_URI']);
        if (!isset($segments[1])) {
            return '_';
        }
        return str_replace('.', '-', $segments[1]);
    }

    static public function startPhpTiming() {
        if (self::$metric) {
            self::startTiming(sprintf(
                '%s.%s.times.%s',
                self::STATSD_PHP_PREFIX,
                self::getPhpPrefix(),
                self::getFirstUrlSegment()
            ));
        }
    }

    static public function stopPhpTiming() {
        if (self::$metric) {
            $phpPrefix = self::getPhpPrefix();
            $uriSegment = self::getFirstUrlSegment();
            self::stopTiming(sprintf(
                '%s.%s.times.%s',
                self::STATSD_PHP_PREFIX,
                $phpPrefix,
                $uriSegment
            ));
            self::setTiming(
                sprintf(
                    '%s.%s.mem.%s',
                    self::STATSD_PHP_PREFIX,
                    $phpPrefix,
                    $uriSegment
                ),
                memory_get_usage(true)
            );
            self::setTiming(
                sprintf(
                    '%s.%s.mem-peak.%s',
                    self::STATSD_PHP_PREFIX,
                    $phpPrefix,
                    $uriSegment
                ),
                memory_get_peak_usage(true)
            );
        }
    }


    static public function writeRedisTiming($method, $redisKey, $size = null) {
        if (self::$metric) {
            $redisKeyPrefix = self::getRedisKeyPrefix($redisKey);
            self::stopTiming(
                sprintf(
                    '%s.%s.times.%s',
                    self::STATSD_REDIS_PREFIX,
                    $method,
                    $redisKeyPrefix
                ));
            if ($size) {
                self::setTiming(
                    sprintf(
                        '%s.%s.size.%s',
                        self::STATSD_REDIS_PREFIX,
                        $method,
                        $redisKeyPrefix
                    ), $size);
            }
        }
    }

    static public function writeApiMethodTiming($method, $time, $memUsage, $peakMemUsage, $resLength) {
        if (self::$metric) {
            self::setTiming(sprintf('%s.times.%s', self::STATSD_API_METHOD_PREFIX, $method), $time);
            self::setTiming(sprintf('%s.mem.%s', self::STATSD_API_METHOD_PREFIX, $method), $memUsage);
            self::setTiming(sprintf('%s.mem-peak.%s', self::STATSD_API_METHOD_PREFIX, $method), $peakMemUsage);
            self::setTiming(sprintf('%s.res-len.%s', self::STATSD_API_METHOD_PREFIX, $method), $resLength);
        }
    }
}
