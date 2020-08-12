<?php


interface KPDNS_Custom_NS_API_Imp {
	public function get_name_server( string $name_server_id, array $args );
	public function add_name_server( string $domain, array $name_servers, array $args );
	public function edit_name_server( KPDNS_Name_Server $name_server, array $args );
	public function delete_name_server( string $name_server_id , array $args );
    public function delete_name_servers( array $name_server_ids , array $args );
    public function list_name_servers( array $args );
}
