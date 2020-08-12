<?php

namespace tvorwachs\ClouDNS;

use tvorwachs\ClouDNS\Api;
/**
 * @copyright 2014 Techreanimate
 * @author Luis Rodriguez
 * @author Tobias Vorwachs
 */
class ClouDNS extends Connection
{
    /** @var Api\ZonesApi */
    protected $zones;

    function __construct()
    {
        parent::__construct(null);
    }

    /**
     * @return Api\ZonesApi
     */
    public function zones()
    {
        if(!$this->zones) $this->zones = new Api\ZonesApi($this);
        return $this->zones;
    }



    /**
     * determine our IP address
     * @return string our public IP address, as seen by icanhazip.com
     */
    public function detectIp()
    {
        $ch = curl_init('http://icanhazip.com');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $result = rtrim(curl_exec($ch));
        curl_close($ch);
        return $result;
    }



    /**
     * Get a list with available domain name servers.
     * @return array
     */
    public function listNameServers()
    {
        $get_string = $this->url_encode($this->getAuth());
        $result = $this->connect($get_string, 'dns/available-name-servers.json');
        return json_decode($result, true);
    }


}
