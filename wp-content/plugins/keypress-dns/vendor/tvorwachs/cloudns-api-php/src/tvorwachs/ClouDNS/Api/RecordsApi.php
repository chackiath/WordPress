<?php

namespace tvorwachs\ClouDNS\Api;

use tvorwachs\ClouDNS\Connection;

/**
 * @copyright 2017 Tobias Vorwachs
 * @author Tobias Vorwachs
 */
class RecordsApi extends Connection
{
    /**
     * List of records in the domain zone
     *
     * @param string $domain
     * @param string $host
     * @param string $type
     * @return array
     */
    public function listRecords($domain, $host = null, $type = null)
    {
        $get = $this->getAuth();

        $get['domain-name'] = $this->getDomain($domain);

        if ($host !== null) $get['host'] = $host;
        if ($type !== null) $get['type'] = $type;

        $get_string = $this->url_encode($get);
        $result = $this->connect($get_string, 'dns/records.json');

        return json_decode($result, true);
    }

    /**
     * Add new record to domain zone.
     * Note: This function is available only for master zones. Works with reverse zones too.
     *
     *
     * @param string $domain
     * @param string $recordType
     * @param string $host
     * @param string $record
     * @param integer $ttl
     * @param array $options
     * More Options ($options-Array):
     * priority - Integer - Priority for MX or SRV record
     * weight - Integer - Weight for SRV record
     * port - Integer - Port for SRV record
     * frame - Integer - 0 or 1 for Web redirects to disable or enable frame
     * frame-title - String - Title if frame is enabled in Web redirects
     * frame-keywords - String - Keywords if frame is enabled in Web redirects
     * frame-description - String - Description if frame is enabled in Web redirects
     * save-path - Integer - 0 or 1 for Web redirects
     * redirect-type - Integer - 301 or 302 for Web redirects if frame is disabled
     * mail - Integer - E-mail address for RP records
     * txt - Integer - Domain name for TXT record used in RP records
     * algorithm - Integer - Algorithm used to create the SSHFP fingerprint. Required for SSHFP records only.
     * fptype - Integer - Type of the SSHFP algorithm. Required for SSHFP records only.
     * status - Integer - Set to 1 to create the record active or to 0 to create it inactive. If omitted the record will be created active.
     * geodns-location - Integer - ID of a GeoDNS location for A, AAAA or CNAME record. The GeoDNS locations can be obtained with List GeoDNS locations
     * caa_flag - Integer - 0 - Non critical or 128 - Critical
     * caa_type - String - Type of CAA record. The available flags are issue, issuewild, iodef.
     * caa_value - String - If caa_type is issue, caa_value can be hostname or ";". If caa_type is issuewild, it can be hostname or ";". If caa_type is iodef, it can be "mailto:someemail@address.tld, http://example.tld or http://example.tld.

     *
     * @return array
     */
    public function addRecord($domain, $recordType, $host, $record, $ttl, $options = [])
    {
        $get = $this->getAuth();

        $get['domain-name'] = $this->getDomain($domain);
        $get['record-type'] = $recordType;
        $get['host'] = $host;
        $get['record'] = $record;
        $get['ttl'] = $ttl;

        $get = array_merge($get, $options);


        $get_string = $this->url_encode($get);
        $result = $this->connect($get_string, 'dns/add-record.json');

        return json_decode($result, true);
    }

    /**
     * Modify record in domain zone.
     * Note: This function is available only for master zones. Works with reverse zones too. With this function you can't modify the record type.
     *
     * @param string $domain
     * @param integer $recordId
     * @param string $host
     * @param string $record
     * @param integer $ttl
     * @param array $options
     * More Options ($options-Array):
     * priority - Integer - Priority for MX or SRV record
     * weight - Integer - Weight for SRV record
     * port - Integer - Port for SRV record
     * frame - Integer - 0 or 1 for Web redirects to disable or enable frame
     * frame-title - String - Title if frame is enabled in Web redirects
     * frame-keywords - String - Keywords if frame is enabled in Web redirects
     * frame-description - String - Description if frame is enabled in Web redirects
     * save-path - Integer - 0 or 1 for Web redirects
     * redirect-type - Integer - 301 or 302 for Web redirects if frame is disabled
     * mail - Integer - E-mail address for RP records
     * txt - Integer - Domain name for TXT record used in RP records
     * algorithm - Integer - Algorithm used to create the SSHFP fingerprint. Required for SSHFP records only.
     * fptype - Integer - Type of the SSHFP algorithm. Required for SSHFP records only.
     * geodns-location - Integer - ID of a GeoDNS location for A, AAAA or CNAME record. The GeoDNS locations can be obtained with List GeoDNS locations
     * caa_flag - Integer - 0 - Non critical or 128 - Critical
     * caa_type - String - Type of CAA record. The available flags are issue, issuewild, iodef.
     * caa_value - String - If caa_type is issue, caa_value can be hostname or ";". If caa_type is issuewild, it can be hostname or ";". If caa_type is iodef, it can be "mailto:someemail@address.tld, http://example.tld or http://example.tld.
     * @return array
     */
    public function modifyRecord($domain, $recordId, $host, $record, $ttl, $options = [])
    {
        $get = $this->getAuth();

        $get['domain-name'] = $this->getDomain($domain);
        $get['record-id'] = $recordId;
        $get['host'] = $host;
        $get['record'] = $record;
        $get['ttl'] = $ttl;

        $get = array_merge($get, $options);

        $get_string = $this->url_encode($get);
        $result = $this->connect($get_string, 'dns/mod-record.json');

        return json_decode($result, true);
    }

    /**
     * Delete record of your domain zone.
     *
     * @param string $domain
     * @param string $recordId
     * @return array
     */
    public function deleteRecord($domain, $recordId)
    {
        $get = $this->getAuth();

        $get['domain-name'] = $this->getDomain($domain);
        $get['record-id'] = $recordId;

        $get_string = $this->url_encode($get);
        $result = $this->connect($get_string, 'dns/delete-record.json');

        return json_decode($result, true);
    }

    /**
     * Copies all the records from a specified zone.
     * Note: This function is available only for master zones.
     *
     * @param string $domain
     * @param string $fromDomain
     * @param int $deleteCurrent
     * @return array
     */
    public function copyRecords($domain, $fromDomain, $deleteCurrent = null)
    {
        $get = $this->getAuth();

        $get['domain-name'] = $this->getDomain($domain);
        $get['from-domain'] = $this->getDomain($fromDomain);

        if ($deleteCurrent !== null && $deleteCurrent === 1) $get['delete-current-records'] = 1;

        $get_string = $this->url_encode($get);
        $result = $this->connect($get_string, 'dns/copy-records.json');

        return json_decode($result, true);
    }

    /**
     * Import records, defined in parameter.
     * Note: This function is available only for master zones. It is not available for GeoDNS zones.
     *
     * @param string $domain
     * @param string $format
     * @param string $content
     * @param int $deleteExisting
     * @return array
     */
    public function importRecords($domain, $format, $content, $deleteExisting = null)
    {
        $get = $this->getAuth();

        $get['domain-name'] = $this->getDomain($domain);
        $get['format'] = $format;
        $get['content'] = $content;

        if ($deleteExisting !== null && $deleteExisting === 1) $get['delete-existing-records'] = 1;

        $get_string = $this->url_encode($get);
        $result = $this->connect($get_string, 'dns/records-import.json');

        return json_decode($result, true);
    }

    /**
     * Export the zone records in bind format.
     * Note: This function is available only for master zones. It is not available for GeoDNS zones.
     *
     * @param string $domain
     * @return array
     */
    public function exportRecords($domain)
    {
        $get = $this->getAuth();

        $get['domain-name'] = $this->getDomain($domain);

        $get_string = $this->url_encode($get);
        $result = $this->connect($get_string, 'dns/records-export.json');

        return json_decode($result, true);
    }

    /**
     * Get the available record types you can set up.
     * 
     * @param string $zoneType The type of the zone: domain, reverse or parked
     * @return array
     */
    public function getAvailableRecordTypes($zoneType)
    {
        $get = $this->getAuth();

        $get['zone-type'] = $this->getDomain($zoneType);

        $get_string = $this->url_encode($get);
        $result = $this->connect($get_string, 'dns/get-available-record-types.json');

        return json_decode($result, true);
    }

    /**
     * Get the available TTL you can set up for the DNS records.
     *
     * @return array
     */
    public function getAvailableTTLs()
    {
        $get = $this->getAuth();

        $get_string = $this->url_encode($get);
        $result = $this->connect($get_string, 'dns/get-available-ttl.json');

        return json_decode($result, true);
    }

    /**
     * Getting SOA details.
     * Note: This function is available only for master zones. Works with reverse zones too.
     *
     * @param string $domain Domain name or reverse zone name whose SOA details you want to see
     * @return array
     */
    public function getSOADetails($domain)
    {
        $get = $this->getAuth();

        $get['domain-name'] = $this->getDomain($domain);

        $get_string = $this->url_encode($get);
        $result = $this->connect($get_string, 'dns/soa-details.json');

        return json_decode($result, true);
    }

    /**
     * Modify SOA details.
     * Note: This function is available only for master zones. Works with reverse zones too.
     *
     * @param string $domain Domain name or reverse zone name whose SOA details you want to modify
     * @param string $primaryNS Hostname of primary nameserver.
     * @param string $adminMail DNS admin's e-mail
     * @param int $refresh Refresh rate from 1200 to 43200 second
     * @param int $retry Retry rate from 180 to 2419200 seconds
     * @param int $expire Expire time from 1209600 to 2419200 seconds
     * @param int $defaultTtl Default TTL from 60 to 2419200 seconds
     * @return array
     */
    public function modifySOADetails($domain, $primaryNS, $adminMail, $refresh, $retry, $expire, $defaultTtl)
    {
        $get = $this->getAuth();

        $get['domain-name'] = $this->getDomain($domain);
        $get['primary-ns'] = $primaryNS;
        $get['admin-mail'] = $adminMail;
        $get['refresh'] = $refresh;
        $get['retry'] = $retry;
        $get['expire'] = $expire;
        $get['default-ttl'] = $defaultTtl;

        $get_string = $this->url_encode($get);
        $result = $this->connect($get_string, 'dns/modify-soa.json');

        return json_decode($result, true);
    }

    /**
     * Imports records from another server via zone transfer. Deletes all existing records
     *
     * @param string $domain Domain name you want to transfer the records to. It must be the same at the other server as it is in our system.
     * @param string $server Hostname or IP address of the server the record
     * @return array
     */
    public function importViaTransfer($domain, $server)
    {
        $get = $this->getAuth();

        $get['domain-name'] = $this->getDomain($domain);
        $get['server'] = $server;

        $get_string = $this->url_encode($get);
        $result = $this->connect($get_string, 'dns/axfr-import.json');

        return json_decode($result, true);
    }

    /**
     * Changes the status of the record to active or inactive
     *
     * @param string $domain Domain name to change the status of
     * @param string $recordId Record ID. You can see this ID with the method List records
     * @param integer $status Set to 1, to activate or to 0 to deactivate the record. If omitted the status will be toggled.
     * @return array
     */
    public function changeRecordStatus($domain, $recordId, $status = null)
    {
        $get = $this->getAuth();

        $get['domain-name'] = $this->getDomain($domain);
        $get['record-id'] = $recordId;

        if ($status !== null && in_array($status, [0, 1])) $get['status'] = $status;

        $get_string = $this->url_encode($get);
        $result = $this->connect($get_string, 'dns/change-record-status.json');

        return json_decode($result, true);
    }
}