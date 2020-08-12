<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'KPDNS_Record_List' ) ) {

	abstract class KPDNS_List implements \Countable, \Iterator, KPDNS_Paginable {

        protected $objects = [];
        private $position = 0;

        protected $current_page = 1;

        protected $pages_count = 1;

        /**
         * Used whith pagination to know the total amount of items, not the items in the current page.
         */
        protected $total_items = 0;

        public function all(): array {
            return $this->objects;
        }

        /**
         * Implementation of method declared in \Countable.
         * Provides support for count()
         */
        public function count() {
            return count( $this->objects );
        }

        /**
         * Implementation of method declared in \Iterator
         * Resets the internal cursor to the beginning of the array
         */
        public function rewind() {
            $this->position = 0;
        }

        /**
         * Implementation of method declared in \Iterator
         * Used to get the current key (as for instance in a foreach()-structure
         */
        public function key() {
            return $this->position;
        }

        /**
         * Implementation of method declared in \Iterator
         * Used to get the value at the current cursor position
         */
        public function current() {
            return $this->objects[ $this->position ];
        }

        /**
         * Implementation of method declared in \Iterator
         * Used to move the cursor to the next position
         */
        public function next() {
            $this->position++;
        }

        /**
         * Implementation of method declared in \Iterator
         * Checks if the current cursor position is valid
         */
        public function valid() {
            return isset( $this->objects[ $this->position ] );
        }

        public function set_all( array $objects ) {
            $this->objects = $objects;
        }

        public function get_current_page(): int {
            return $this->current_page;
        }

        public function set_current_page( int $current_page ): void {
            $this->current_page = $current_page;
        }

        public function get_pages_count(): int {
            return $this->pages_count;
        }

        public function set_pages_count(int $pages_count): void{
            $this->pages_count = $pages_count;
        }

        public function get_total_items(): int {
            return $this->total_items;
        }

        public function set_total_items( int $total_items ): void {
            $this->total_items = $total_items;
        }

        public function maybe_paginate( $current_page = 1 ) {

            if ( $current_page === 'all' ) {
                return;
            }

            $items_per_page    = KPDNS_Utils::get_items_per_page();
            $total_items       = $this->count();

            // $this->total_items is the total amount of items before pagination.
            $this->total_items = $total_items;

            // Not enough items to paginate
            if ( $total_items <= $items_per_page  ) {
                return;
            }

            $pages_count  = ( ( $total_items % $items_per_page ) == 0 ) ? $total_items / $items_per_page : floor($total_items / $items_per_page) + 1;
            $offset       = ( $current_page * $items_per_page ) - $items_per_page;
            $length       = $current_page === $pages_count ? $total_items : $items_per_page;

            $paginated_list = array_slice( $this->all(), $offset, $length );

            $this->current_page = $current_page;
            $this->pages_count  = $pages_count;

            $this->set_all( $paginated_list );
        }
	}
}
