<?php

class PleskApiClient
{
    private $_host;
    private $_port;
    private $_protocol;
    private $_login;
    private $_password;
    private $_secretKey;

    public function __construct($host, $port = 8443, $protocol = 'https')
    {
        $this->_host = $host;
        $this->_port = $port;
        $this->_protocol = $protocol;
    }

    public function setCredentials($login, $password)
    {
        $this->_login = $login;
        $this->_password = $password;
    }

    public function setSecretKey($secretKey)
    {
        $this->_secretKey = $secretKey;
    }

    public function request($request)
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, "$this->_protocol://$this->_host:$this->_port/enterprise/control/agent.php");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->_getHeaders());
        curl_setopt($curl, CURLOPT_POSTFIELDS, $request);

        $result = curl_exec($curl);

        curl_close($curl);

        return $result;
    }

    private function _getHeaders()
    {
        $headers = array(
            "Content-Type: text/xml",
            "HTTP_PRETTY_PRINT: TRUE",
        );

        if ($this->_secretKey) {
            $headers[] = "KEY: $this->_secretKey";
        } else {
            $headers[] = "HTTP_AUTH_LOGIN: $this->_login";
            $headers[] = "HTTP_AUTH_PASSWD: $this->_password";
        }

        return $headers;
    }

}