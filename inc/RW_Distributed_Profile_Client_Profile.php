<?php


class RW_Distributed_Profile_Client_Profile {

    /**
     * get profile list from remote server
     *
     * @since   0.1
     * @access  public
     * @static
     * @return null
     */
    public static function get_profile_list() {
        if ( RW_Distributed_Profile_Client_Options::get_endpoint() == '' ) {
            return false;
        }
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

    /**
     * Copy Profildata on first login
     *
     * @since   0.1
     * @access  public
     * @static
     * @param   $user_login
     * @param   $user
     * @return  null
     */
    public static function copy_profile( $user_login, $user ) {
        $first_run = get_user_option( 'rw_distributed_profile_client_copy', $user->ID );
        if ( $first_run != true ) {
            update_user_option( $user->ID, 'rw_distributed_profile_client_copy', true );
            $request = array(   'cmd' => 'get_profile', 'data' => array( 'user_name' => $user->user_nicename ) );
            $json = urlencode( json_encode( $request ) );
            $response = wp_remote_get( RW_Distributed_Profile_Client_Options::get_endpoint() . $json , array ( 'sslverify' => false ) );
            try {
                $json = json_decode( $response['body'] );
            } catch ( Exception $ex ) {
                return null;
            }
            foreach( $json->message->wordpress as $key => $value ) {
                update_user_meta( $user->ID, $key,$value );
            }
            if ( function_exists( xprofile_get_field_id_from_name ) ) {
                foreach( $json->message->buddypress as $group ) {
                    foreach( $group as $key => $value ) {
                        $field = new BP_XProfile_Field( xprofile_get_field_id_from_name( $key ) );
                        $fieldvalue = apply_filters( 'rw_distributed_profile_client_field_value', $value->value );
                        $fieldvalue = apply_filters( 'rw_distributed_profile_client_field_'. $field->type . '_value', $fieldvalue );
                        xprofile_set_field_data( $key, $user->ID, maybe_unserialize( $fieldvalue ) );
                    }
                }
            }
        }
    }

}