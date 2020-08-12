<?php

namespace tvorwachs\ClouDNS\Api;

use tvorwachs\ClouDNS\Connection;

/**
 * @copyright 2014 Techreanimate
 * @author Luis Rodriguez
 * @author Tobias Vorwachs
 */
class ZonesApi extends Connection
{

    /** @var RecordsApi */
    protected $records;

    /**
     * @return RecordsApi
     */
    public function records()
    {
        if(!$this->records) $this->records = new RecordsApi($this);
        return $this->records;
    }

    /**
     * Gets a list with zones you have or zone names matching a keyword. The method works with pagination. Reverse zones are included.
     * @param $page - Current page your zone list is on
     * @param $rows - Results per page. Can be 10, 20, 30, 50 or 100.
     * @param $search - Domain name, reverse zone name or keyword to search for in the zone names
     * @return array('Pages','Data' => Array)
     */
    public function listZones($page = 1, $rows = 10, $search = null)
    {
        $get = $this->getAuth();

        /* Validate the params, default if fail */
        $get['page'] = intval($page) > 0 ? intval($page) : 1;
        $get['rows-per-page'] = in_array(intval($rows), array(10, 20, 30, 50, 100)) ? intval($rows) : 10;
        if ($search != null) $get['search'] = $search;

        /* Run the connection and get result for page count */
        $get_string = $this->url_encode($get);
        $pg_result = $this->connect($get_string, 'dns/get-pages-count.json');

        /* Run the connection and get result */
        $get_string = $this->url_encode($get);
        $result = $this->connect($get_string, 'dns/list-zones.json');

        return array('Pages' => json_decode($pg_result, true), 'Data' => json_decode($result, true));
    }

    /**
     * Gets the number of the zones you have and the zone limit of your customer plan. Reverse zones are included.
     * @return array
     */
    public function listZoneStats()
    {
        $get_string = $this->url_encode($this->getAuth());
        $result = $this->connect($get_string, 'dns/get-zones-stats.json');
        return json_decode($result, true);
    }

    /**
     * This function is available only for slave zones, master zones and cloud/bulk domains. Works with reverse zones too.
     * @param $domain
     * @return array
     */
    public function deleteZone($domain)
    {
        $get = $this->getAuth();

        $get['domain-name'] = $this->getDomain($domain);

        $get_string = $this->url_encode($get);
        $result = $this->connect($get_string, 'dns/delete.json');
        return json_decode($result, true);
    }

    /**
     * This function registers a domain zone.
     * @param string $domain
     * @param string $zone_type
     * @param array $ns
     * @param string $master_ip
     * @return array
     */
    public function registerZone($domain, $zone_type, $ns = array(), $master_ip = "")
    {
        $get = $this->getAuth();

        $get['domain-name'] = $this->getDomain($domain);

        $get['zone-type'] = $zone_type;

        if ($ns) $get['ns'] = $ns;
        if ($master_ip) $get['master-ip'] = $master_ip;

        $get_string = $this->url_encode($get);
        $result = $this->connect($get_string, 'dns/register.json');
        return json_decode($result, true);
    }

    /**
     * Get the count of the mail servers you have and the mail forwards limit.
     * @return array
     */
    public function listMailForwardStats()
    {
        $get_string = $this->url_encode($this->getAuth());
        $result = $this->connect($get_string, 'dns/get-mail-forwards-stats.json');
        return json_decode($result, true);
    }

    /**
     * Get zone information, like status, is it master or slave, is it forward or reverse, is it a cloud domain and which zone is its master
     * @param string $domain
     * @return array
     */
    public function getZoneInfo($domain)
    {
        $get = $this->getAuth();

        $get['domain-name'] = $this->getDomain($domain);

        $get_string = $this->url_encode($get);
        $result = $this->connect($get_string, 'dns/get-zone-info.json');
        return json_decode($result, true);
    }

    /**
     * Update zone
     * @param string $domain
     * @return array
     */
    public function updateZone($domain)
    {
        $get = $this->getAuth();

        $get['domain-name'] = $this->getDomain($domain);

        $get_string = $this->url_encode($get);
        $result = $this->connect($get_string, 'dns/update-zone.json');
        return json_decode($result, true);
    }

    /**
     * Get a list with name servers and information for update status of the domain name. Works with reverse zones too.
     * @param string $domain
     * @return array
     */
    public function getUpdateStatusZone($domain)
    {
        $get = $this->getAuth();

        $get['domain-name'] = $this->getDomain($domain);

        $get_string = $this->url_encode($get);
        $result = $this->connect($get_string, 'dns/update-status.json');
        return json_decode($result, true);
    }

    /**
     * Get a list with name servers and information for update status of the domain name. Works with reverse zones too.
     * @param string $domain
     * @return array
     */
    public function isZoneUpdated($domain)
    {
        $get = $this->getAuth();

        $get['domain-name'] = $this->getDomain($domain);

        $get_string = $this->url_encode($get);
        $result = $this->connect($get_string, 'dns/is-updated.json');
        return json_decode($result, true);
    }

    /**
     * Get a list with name servers and information for update status of the domain name. Works with reverse zones too.
     * @param string $domain
     * @param integer $status
     * @return array
     */
    public function setZoneStatus($domain, $status = null)
    {
        $get = $this->getAuth();

        $get['domain-name'] = $this->getDomain($domain);

        if ($status !== null && in_array($status, [0, 1])) $get['status'] = $status;

        $get_string = $this->url_encode($get);
        $result = $this->connect($get_string, 'dns/change-status.json');
        return json_decode($result, true);
    }
}