<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'KPDNS_Record' ) ) {

    /**
     * Class KPDNS_Record
     *
     * @see https://tools.ietf.org/html/rfc3597
     * @see https://tools.ietf.org/html/rfc1035
     * @see https://en.wikipedia.org/wiki/Domain_Name_System#Resource_records
     * @see https://en.wikipedia.org/wiki/List_of_DNS_record_types
     */
	class KPDNS_Record implements KPDNS_Arrayable {

	    const DEFAULT_TTL_VALUE = 60; //Seconds

        const CLASS_INTERNET = 'IN';

        const TYPE_A      = 'A';
        const TYPE_AAAA   = 'AAAA';
        const TYPE_CAA    = 'CAA';
        const TYPE_CNAME  = 'CNAME';
        const TYPE_MX     = 'MX';
        const TYPE_NS     = 'NS';
        const TYPE_PTR    = 'PTR';
        const TYPE_SOA    = 'SOA';
        const TYPE_SPF    = 'SPF';
        const TYPE_SRV    = 'SRV';
        const TYPE_TXT    = 'TXT';
        const TYPE_LOC    = 'LOC';
        const TYPE_CERT   = 'CERT';
        const TYPE_DNSKEY = 'DNSKEY';
        const TYPE_DS     = 'DS';
        const TYPE_NAPTR  = 'NAPTR';
        const TYPE_SMIMEA = 'SMIMEA';
        const TYPE_SSHFP  = 'SSHFP';
        const TYPE_TLSA   = 'TLSA';
        const TYPE_URI    = 'URI';

        const RDATA_KEY_VALUE         = 'value';
        const RDATA_KEY_PRIORITY      = 'priority';
        const RDATA_KEY_MAIL_SERVER   = 'mail-server';
        const RDATA_KEY_FLAG          = 'flag';
        const RDATA_KEY_TAG           = 'tag';
        const RDATA_KEY_NAME_SERVER   = 'name-server';
        const RDATA_KEY_EMAIL         = 'email';
        const RDATA_KEY_SERIAL_NUMBER = 'serial-number';
        const RDATA_KEY_REFRESH       = 'refresh';
        const RDATA_KEY_RETRY         = 'retry';
        const RDATA_KEY_TIME_TRANSFER = 'time-transfer';
        const RDATA_KEY_WEIGHT        = 'weight';
        const RDATA_KEY_PORT          = 'port';
        const RDATA_KEY_HOST          = 'host';
        const RDATA_KEY_SERVICE       = 'service';
        const RDATA_KEY_PROTOCOL      = 'protocol';


        /**
         * @var string
         */
        private $class = self::CLASS_INTERNET;

        /**
         * @var string
         */
        public $name;

        /**
         * @var string
         */
        public $type;

        /**
         * @var int
         */
        public $ttl;

        /**
         * Resource Record-specific data.
         *
         * @var array
         *
         * A,
         * AAAA,
         * CNAME,
         * NS,
         * PTR,
         * SPF,
         * TXT: $rdata['value'] = value
         *
         * CAA: $rdata['tag]
         *      $rdata['flag]
         *      $rdata['value]
         *
         * MX:  $rdata['priority]
         *      $rdata['mail-server']
         *
         * SOA: $rdata['name-server']
         *      $rdata['email']
         *      $rdata['serial-number']
         *      $rdata['refresh']
         *      $rdata['retry']
         *      $rdata['time-transfer']
         *
         * SRV: $rdata['service']
         *      $rdata['protocol']
         *      $rdata['priority']
         *      $rdata['weight']
         *      $rdata['port']
         *      $rdata['host']
         *
         */
        public $rdata;

        public $meta;

        protected $readonly = false;

        /**
         * KPDNS_Record constructor.
         * @param string $type
         * @param string $name
         * @param array $rdata
         * @param int $ttl
         */
		public function __construct( string $type, string $name, array $rdata, int $ttl, array $meta = array() ) {
            $this->type  = $type;
            $this->name  = $name;
            $this->rdata = $rdata;
            $this->ttl   = $ttl;
            $this->meta  = $meta;
		}

        /**
         * @return string
         */
        public function get_name(): string {
            return $this->name;
        }

        /**
         * @param string $name
         */
        public function set_name(string $name): void {
            $this->name = $name;
        }

        /**
         * @return string
         */
        public function get_type(): string {
            return $this->type;
        }

        /**
         * @param string $type
         */
        public function set_type( string $type ) {
            $this->type = $type;
        }

        /**
         * @return array
         */
        public function get_rdata(): array {
            return $this->rdata;
        }

        /**
         * @param array $rdata
         */
        public function set_rdata ( array $rdata ): void {
            $this->rdata = $rdata;
        }

        /**
         * @return array
         */
        public function get_meta(): array {
            return $this->meta;
        }

        /**
         * @param array $meta
         */
        public function set_meta ( array $meta ): void {
            $this->meta = $meta;
        }

        /**
         * @return int
         */
        public function get_ttl(): int {
            return $this->ttl;
        }

        /**
         * @param int $ttl
         */
        public function set_ttl( int $ttl ): void {
            $this->ttl = $ttl;
        }

        /**
         * @return string
         */
        public function get_class() {
            return $this->class;
        }

        public function is_readonly() {
            return $this->readonly;
        }

        public function set_readonly( bool $readonly ) {
            $this->readonly = $readonly;
        }

        /**
         * @return array
         */
        public function to_array(): array {
            return array(
                'type'     => $this->type,
                'name'     => $this->name,
                'rdata'    => $this->rdata,
                'ttl'      => $this->ttl,
                'meta'     => $this->meta,
                'readonly' => $this->readonly,
            );
        }

        public function set_rdata_from_string( string $value ): array {
            $this->rdata = preg_split('/\s+/', $value, -1, PREG_SPLIT_NO_EMPTY );
        }

        public function get_rdata_string(): string {
            return implode( ' ', $this->get_rdata() );
        }


        // TODO Not the best place. Move. Rethink.
        public static function build_rdata( array $record ) {
            $rdata = array();

            switch( $record['type'] ) {

                case self::TYPE_A:
                case self::TYPE_AAAA:
                case self::TYPE_CNAME:
                case self::TYPE_NS:
                case self::TYPE_PTR:
                case self::TYPE_SPF:
                case self::TYPE_TXT:
                    $rdata[ self::RDATA_KEY_VALUE ] = $record[ self::RDATA_KEY_VALUE ];
                    break;

                case self::TYPE_CAA:
                    $rdata[ self::RDATA_KEY_FLAG ]  = $record[ self::RDATA_KEY_FLAG ];
                    $rdata[ self::RDATA_KEY_TAG ]   = $record[ self::RDATA_KEY_TAG ];
                    $rdata[ self::RDATA_KEY_VALUE ] = $record[ self::RDATA_KEY_VALUE ];
                    break;

                case self::TYPE_MX:
                    $rdata[ self::RDATA_KEY_PRIORITY ]    = $record[ self::RDATA_KEY_PRIORITY ];
                    $rdata[ self::RDATA_KEY_MAIL_SERVER ] = $record[ self::RDATA_KEY_MAIL_SERVER ];
                    break;

                case self::TYPE_SOA:
                    $rdata[ self::RDATA_KEY_NAME_SERVER ]   = $record[ self::RDATA_KEY_NAME_SERVER ];
                    $rdata[ self::RDATA_KEY_EMAIL ]         = $record[ self::RDATA_KEY_EMAIL ];
                    $rdata[ self::RDATA_KEY_SERIAL_NUMBER ] = $record[ self::RDATA_KEY_SERIAL_NUMBER ];
                    $rdata[ self::RDATA_KEY_REFRESH ]       = $record[ self::RDATA_KEY_REFRESH ];
                    $rdata[ self::RDATA_KEY_RETRY ]         = $record[ self::RDATA_KEY_RETRY ];
                    $rdata[ self::RDATA_KEY_TIME_TRANSFER ] = $record[ self::RDATA_KEY_TIME_TRANSFER ];
                    break;

                case self::TYPE_SRV:
                    $rdata[ self::RDATA_KEY_SERVICE ]  = $record[ self::RDATA_KEY_SERVICE ];
                    $rdata[ self::RDATA_KEY_PROTOCOL ] = $record[ self::RDATA_KEY_PROTOCOL ];
                    $rdata[ self::RDATA_KEY_PRIORITY ] = $record[ self::RDATA_KEY_PRIORITY ];
                    $rdata[ self::RDATA_KEY_WEIGHT ]   = $record[ self::RDATA_KEY_WEIGHT ];
                    $rdata[ self::RDATA_KEY_PORT ]     = $record[ self::RDATA_KEY_PORT ];
                    $rdata[ self::RDATA_KEY_HOST ]     = $record[ self::RDATA_KEY_HOST ];
                    break;
            }

            return $rdata;
        }


        /*
        public static function get_rdata_config( $type ) {
            switch( $type ) {
                case self::TYPE_A:
                case self::TYPE_AAAA:
                case self::TYPE_CNAME:
                case self::TYPE_NS:
                case self::TYPE_PTR:
                case self::TYPE_SPF:
                case self::TYPE_TXT:
                    return array(
                        self::RDATA_KEY_VALUE
                    );

                case self::TYPE_CAA:
                    return array(
                        self::RDATA_KEY_TAG,
                        self::RDATA_KEY_FLAG,
                        self::RDATA_KEY_VALUE
                    );

                case self::TYPE_MX:
                    return array(
                        self::RDATA_KEY_PRIORITY,
                        self::RDATA_KEY_MAIL_SERVER
                    );

                case self::TYPE_SOA:
                    return array(
                        self::RDATA_KEY_NAME_SERVER,
                        self::RDATA_KEY_EMAIL,
                        self::RDATA_KEY_SERIAL_NUMBER,
                        self::RDATA_KEY_REFRESH,
                        self::RDATA_KEY_RETRY,
                        self::RDATA_KEY_TIME_TRANSFER
                    );

                case self::TYPE_SRV:
                    return array(
                        self::RDATA_KEY_SERVICE,
                        self::RDATA_KEY_PROTOCOL,
                        self::RDATA_KEY_PRIORITY,
                        self::RDATA_KEY_WEIGHT,
                        self::RDATA_KEY_PORT,
                        self::RDATA_KEY_HOST
                    );

                default: // Unknown record type.
                    return null;
            }
        }
        */
	}
}
