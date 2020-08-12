<?php

function kpdns_get_api() {
    $provider = KPDNS()->provider;

    if ( is_wp_error( $provider ) ) {
        return $provider;
    }

    if ( ! isset( $provider ) || ! $provider instanceof KPDNS_Provider ) {
        return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Invalid provider', 'keypress-dns' ) );
    }

    $api = $provider->api;

    if ( is_wp_error( $api ) ) {
        return $api;
    }

    if ( ! isset( $api ) || ! $api instanceof KPDNS_API ) {
        return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Invalid API', 'keypress-dns' ) );
    }

    return $api;
}

function kpdns_is_default_custom_ns( KPDNS_Name_Server $name_server ) {

    $default_custom_ns    = KPDNS_Model::get_default_ns();
    $is_default_custom_ns = $default_custom_ns && is_array( $default_custom_ns ) && isset( $default_custom_ns['id'] ) && $name_server->get_id() == $default_custom_ns['id'];

    /**
     * Filters $is_default_ns value.
     *
     * @since 1.3
     * @param bool $is_default_custom_ns
     * @param KPDNS_Name_Server $name_server
     */
    $is_default_custom_ns = apply_filters( 'kpdns_is_default_custom_ns', $is_default_custom_ns, $name_server );

    return $is_default_custom_ns;
}

function kpdns_is_primary_zone( KPDNS_Zone $zone ) {
    $primary_zone = KPDNS_Model::get_primary_zone();
    if ( ! isset( $primary_zone ) || empty( $primary_zone ) || ! isset( $primary_zone['id'] ) ) {
        return false;
    }
    return strval( $primary_zone['id'] ) === $zone->get_id();
}