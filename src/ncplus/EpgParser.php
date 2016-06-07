<?php

namespace ncplus;

//use parser\EpgException;


class EpgParser
{

    protected $config = array(
        'baseUrl' => 'http://ncplus.pl/',
        'curlProxy' => false,//false or ip
        'curlTor' => false,
        'curlTorPort' => null //set if curlTor is true(default 9050)
    );
    protected $curlOptions = array();
    protected $userCurlOptions = array();
    protected $curlError = null;
    protected $curlResult = null;
    protected $curlInfo = array();
    protected $errors = array();
    protected $curlObject = null;


    public function __construct($config = array()) {
        if ($config) {
            foreach ($config as $key => $value) {
                $this->config[$key] = $value;
            }
        }
    }

    /**
     * @param mixed $key
     * @param mixed $val
     * @return $this
     */
    public function setCurlOption($key, $val) {
        $this->userCurlOptions[$key] = $val;
        return $this;
    }

    /**
     *
     * @param string $day as Y-m-d
     * @return array|boolean
     */
    public function loadDay($day) {
        try {
            $dayObject = new \DateTime($day);
        } catch (\Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }

        $url = $this->config['baseUrl'] . "~/epgjson/" . $dayObject->format('Y-m-d') . ".ejson";

        $this->initCurl($url)->runCurl();
        if ($this->curlError) {
            $error = $this->curlError . "\n Url: " . $url . ";";
            if (isset($this->curlOptions[CURLOPT_PROXY])) {
                $error .= "\n Proxy: " . $this->curlOptions[CURLOPT_PROXY];
            }
            $this->setError($error);
            return false;
        }

        if ($this->curlInfo['http_code'] != '200' || strpos($this->curlInfo['content_type'], 'application/json') === false) {
            $this->setError("http code is not OK or content is invalid " . $this->curlInfo['http_code'] . "/" . $this->curlInfo['content_type']);
            return false;
        }

        return json_decode($this->curlResult);
    }

    /**
     * @param string $url
     * @return $this
     */
    protected function initCurl($url) {
        $this->resetCurl();
        $this->curlOptions = $this->userCurlOptions;
        if (!$this->curlOptions) {
            $this->curlOptions[CURLOPT_USERAGENT] = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.106 Safari/537.36";
            $this->curlOptions[CURLOPT_TIMEOUT] = 60;
            $cookie = "epg_cookie.txt";
            $this->curlOptions[CURLOPT_COOKIEJAR] = $cookie;
            $this->curlOptions[CURLOPT_COOKIE] = $cookie;
            $this->curlOptions[CURLOPT_FOLLOWLOCATION] = 1;
            $this->curlOptions[CURLOPT_RETURNTRANSFER] = 1;
        }

        $this->curlOptions[CURLOPT_URL] = $url;
        if ($this->config['curlProxy']) {
            $this->curlOptions[CURLOPT_PROXY] = $this->config['curlProxy'];
        } elseif ($this->config['curlTor']) {
            $this->setCurlTor();
        }

        if (!$this->curlObject) {
            $this->curlObject = curl_init($url);
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function resetCurl() {
        $this->curlObject = null;
        $this->curlOptions = array();
        $this->curlInfo = array();
        $this->curlResult = null;
        return $this;
    }

    /**
     * @return $this
     */
    protected function runCurl() {
        try {
            curl_setopt_array($this->curlObject, $this->curlOptions);
            $this->curlResult = curl_exec($this->curlObject);
            $this->curlError = curl_error($this->curlObject);
            $this->curlInfo = curl_getinfo($this->curlObject);
            curl_close($this->curlObject);
        } catch (\Exception $e) {
            $this->setError($e->getMessage());
            $this->curlError = $e->getMessage();
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function setCurlTor() {
        $this->curlOptions[CURLOPT_AUTOREFERER] = 1;
        $this->curlOptions[CURLOPT_RETURNTRANSFER] = 1;
        $this->curlOptions[CURLOPT_PROXY] = '127.0.0.1:' . ($this->config['curlTorPort'] ? (int)$this->config['curlTorPort'] : 9050);
        $this->curlOptions[CURLOPT_PROXYTYPE] = 7;
        $this->curlOptions[CURLOPT_TIMEOUT] = 120;
        $this->curlOptions[CURLOPT_VERBOSE] = 0;
        $this->curlOptions[CURLOPT_HEADER] = 0;
        return $this;
    }

    /**
     * @param $error
     * @return $this
     */
    public function setError($error) {
        $this->errors[] = $error;
        return $this;
    }

    /**
     * @return array
     */
    public function getCurlInfo() {
        return $this->curlInfo;
    }

    /**
     *
     * @param array $json
     * @return boolean|array
     */
    public function parseCommonData($json = array()) {
        if (!is_array($json)) {
            $this->setError("Invalid input");
            return false;
        }

        $channels = array();
        $programs = array();
        foreach ($json as $id => $chInfo) {
            $channels[$chInfo[0]] = trim($chInfo[1]);
            $programs[$chInfo[0]] = $chInfo[2];
        }
        $programsFinal = array();

        foreach ($programs as $chan => $pr) {
            if (!isset($pr[0])) {
                continue;
            }
            if (!isset($programsFinal[$chan])) {
                $programsFinal[$chan] = array();
            }
            foreach ($pr as $prg) {
                $airTimestamp = $prg[2];
                try {
                    $date = new \DateTime(date('Y-m-d H:i:s', $prg[2]));
                } catch (\Exception $ex) {
                    $this->setError($ex->getMessage());
                    continue;
                }
                $programsFinal[$chan][] = array(
                    'id' => $prg[0],
                    'name' => $prg[1],
                    'airDate' => $date->format('Y-m-d'),
                    'airTime' => $date->format('H:i:s'),
                    'airLength' => $prg[3],
                    'idChannel' => $chan
                );
            }
        }

        return array('channels' => $channels, 'programs' => $programsFinal);
    }

    /**
     *
     * @param int $id
     * @return boolean|array
     */
    public function getProgramInfo($id) {
        $url = $this->config['baseUrl'] . "program-tv?rm=ajax&id={$id}&v=5";
        $this->initCurl($url);
        $this->setCurlOption(CURLOPT_HTTPHEADER, array('X-Requested-With: XMLHttpRequest'));

        $this->runCurl();
        if ($this->curlError) {
            $error = $this->curlError . "\n Url: " . $url . ";";
            if (isset($this->curlOptions[CURLOPT_PROXY])) {
                $error .= "\n Proxy: " . $this->curlOptions[CURLOPT_PROXY];
            }
            $this->setError($error);
            return false;
        }
        if ($this->curlInfo['http_code'] != '200' || strpos($this->curlInfo['content_type'], 'application/json') === false) {
            $this->setError("Http code is not OK or content is invalid " . $this->curlInfo['http_code'] . "/" . $this->curlInfo['content_type'] . " for url " . $url);
            return false;
        }
        return json_decode($this->curlResult);
    }

    /**
     * Handle program data and get rid of unnecessary data
     * @param array $json - result of getProgramInfo
     * @return boolean|array with fields descr,urlNcpluspl,category,country,movieCast,movieDirector
     */
    public function parseProgramData($json = array()) {
        if (!is_array($json)) {
            $this->setError("Invalid input");
            return false;
        }
        $program = array(
            'descr' => (isset($json[0]) && $json[0]) ? $json[0] : null,
            'urlNcpluspl' => (isset($json[1]) && $json[1]) ? $json[1] : null, //eg 11006845-teletoon-gry-3-odc-10-teletoon-hd-20150417-0915 - можно вытащить канал
            'category' => (isset($json[3]) && $json[3]) ? $json[3] : null,
            'country' => (isset($json[4]) && $json[4]) ? $json[4] : null,
            'movieCast' => (isset($json[5]) && $json[5]) ? $json[5] : null, //в ролях
            'movieDirector' => (isset($json[7]) && $json[7]) ? $json[7] : null,
        );
        return $program;
    }

    /**
     * @return array
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * @return mixed|null
     */
    public function getLastError() {
        if ($this->errors) {
            return end($this->errors);
        }
        return null;
    }

}