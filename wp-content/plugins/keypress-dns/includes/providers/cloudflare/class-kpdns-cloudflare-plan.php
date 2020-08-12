<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'KPDNS_Cloudflare_Plan' ) ) {

    /**
     * Class KPDNS_Cloudflare_Plan
     *
     * @see https://api.cloudflare.com/#zone-properties
     *
     */
	class KPDNS_Cloudflare_Plan {

	    public $id;

	    public $name;

        public $price;

        public $currency;

        public $frequency;

        public $legacy_id;

        public $is_subscribed;

        public $can_subscribe;

		public function __construct( string $id, string $name, int $price, string $currency, string $frequency, string $legacy_id, bool $is_subscribed, bool $can_subscribe ) {
            $this->id            = $id;
            $this->name          = $name;
            $this->price         = $price;
            $this->currency      = $currency;
            $this->frequency     = $frequency;
            $this->legacy_id     = $legacy_id;
            $this->is_subscribed = $is_subscribed;
            $this->can_subscribe = $can_subscribe;
		}

	}
}
