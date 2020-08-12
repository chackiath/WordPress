<?php


interface KPDNS_API_Imp {
	public function add_zone( KPDNS_Zone $zone, array $args );
	public function delete_zone( string $zone_id, array $args );
	public function delete_zones( array $zone_ids, array $args );
	public function edit_zone( KPDNS_Zone $zone, array $args );
	public function get_zone( string $zone_id, array $args );
    public function get_zone_by_domain( string $domain, array $args );
	public function list_zones( array $args );

	public function add_record( KPDNS_Record $record, string $zone_id, array $args );
    public function add_records( KPDNS_Records_List $records, string $zone_id, array $args );
	public function delete_record( KPDNS_Record $record, string $zone_id, array $args );
	public function edit_record( KPDNS_Record $record, KPDNS_Record $new_record, string $zone_id, array $args );
    public function list_records( string $zone_id, array $args );

    public function build_zone( array $zone ): ?KPDNS_Zone;
    public function build_record( array $record ): ?KPDNS_Record;
}
