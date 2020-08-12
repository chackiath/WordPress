<?php

namespace tvorwachs\ClouDNS;
use IPTools\IP;

/**
 * @copyright 2014 Techreanimate
 * @author Luis Rodriguez
 * @author Tobias Vorwachs
 */
abstract class Connection
{

    /**
     * API credential information required to execute requests
     */
    protected $apiUrl = 'https://api.cloudns.net/';
    protected $authId;
    protected $authPassword;
    protected $authType = "auth-id";

    /**
     * Verify SSL connection
     */
    protected $sslCheck = true;

    /**
     * storage for API responses
     */
    public $Response;

    /**
     * Connection constructor.
     * @param Connection|null $class
     */
    public function __construct(Connection $class = null)
    {
        if(!$class) return;
        
        $this->apiUrl = $class->apiUrl;
        $this->authId = $class->authId;
        $this->authPassword = $class->authPassword;
        $this->sslCheck = $class->sslCheck;
    }

    /**
     * Pass in options to set as an array
     * @param $options
     * @return array
     */
    public function setOptions(Array $options = array())
    {
        $this->apiUrl = isset($options['apiUrl']) ? $options['apiUrl'] : $this->apiUrl;
        $this->authId = isset($options['authId']) ? $options['authId'] : $this->authId;
        $this->authType = isset($options['authType']) && in_array($options['authType'], ['auth-id', 'sub-auth-id', 'sub-auth-user'])? $options['authType'] : $this->authType;
        $this->sslCheck = isset($options['sslCheck']) ? $options['sslCheck'] : $this->sslCheck;
        $this->authPassword = isset($options['authPassword']) ? $options['authPassword'] : $this->authPassword;

        /* Check if login still works */
        $status = $this->login();
        if ($status['status'] != 'Success') return $status;


        return array('status' => 'Success');
    }

    /**
     * Test login details
     */
    protected function login()
    {
        $get = $this->getAuth();

        /* Clean options for GET */
        $get_string = $this->url_encode($get);

        /* Connect */
        $result = $this->connect($get_string, 'dns/login.json');

        /* Return an array result */
        return json_decode($result, true);
    }


    /**
     * Runs the final connection with all the data needed
     * @param $get_string
     * @param $directory (optional) - The directory of the api you are calling dns/ or domains/
     * @return mixed
     */
    protected function connect($get_string, $directory = 'dns/')
    {
        $request = curl_init($this->apiUrl . $directory . '?' . $get_string); // initiate curl object
        curl_setopt($request, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
        curl_setopt($request, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
        curl_setopt($request, CURLOPT_SSL_VERIFYPEER, $this->sslCheck); // uncomment this line if you get no gateway response.
        $this->Response = curl_exec($request); // execute curl post and store results in $post_response
        curl_close($request); // close curl object
        return $this->Response;
    }

    /**
     * This section takes the input fields and converts them to the proper format
     * for an http post.  For example: "auth_id=username&auth_password=a1B2c3D4"
     *
     * @param $get_values array
     * @return string
     */
    protected function url_encode(Array $get_values = array())
    {
        $get_string = "";
        foreach ($get_values as $key => $value) {
            if ( is_array( $value ) ) {
                foreach ( $value as $val ) {
                    $get_string .= "{$key}[]=" . urlencode($val) . "&";
                }
            } else {
                $get_string .= "$key=" . urlencode($value) . "&";
            }
        }
        return rtrim($get_string, "& ");
    }

    /**
     * Returns the GET array to be used for authentication
     */
    protected function getAuth()
    {
        return array($this->authType => $this->authId, 'auth-password' => $this->authPassword);
    }

    protected function getDomain($domain)
    {
        if (filter_var($domain, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6))
            return IP::parse($domain)->reversePointer;

        return $domain;
    }
}