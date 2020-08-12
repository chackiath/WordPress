<?php

interface KPDNS_Paginable {
    public function get_current_page(): int;

    public function set_current_page( int $current_page ): void;

    public function get_pages_count(): int;

    public function set_pages_count(int $pages_count): void;

    public function get_total_items(): int;

    public function set_total_items( int $total_items ): void;
}
