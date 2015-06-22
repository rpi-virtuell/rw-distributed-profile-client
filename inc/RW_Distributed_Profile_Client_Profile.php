<?php


class RW_Distributed_Profile_Client_Profile {

    public static function get_profile_list() {
        $request = array(   'cmd' => 'list_profile' );
        $json = urlencode( json_encode( $request ) );
        $response = wp_remote_get( RW_Distributed_Profile_Client_Options::get_endpoint() . $json , array ( 'sslverify' => false ) );
        try {
            $json = json_decode( $response['body'] );
        } catch ( Exception $ex ) {
            return null;
        }
        return $json->message;
    }

    public static function copy_profile( $user_login, $user ) {
        $first_run = get_user_option( 'rw_distributed_profile_client_copy', $user->ID );
        if ( $first_run !== true ) {
            update_user_option( $user->ID, 'rw_distributed_profile_client_copy', true );
        }
    }
}