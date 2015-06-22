<?php

/**
 * Class RW_Distributed_Profile_Client_Installation
 *
 * Contains some helper code for plugin installation
 *
 */

class RW_Distributed_Profile_Client_Installation {
    /**
     * Check some thinks on plugin activation
     *
     * @since   0.1
     * @access  public
     * @static
     * @return  void
     */
    public static function on_activate() {

        // check WordPress version
        if ( ! version_compare( $GLOBALS[ 'wp_version' ], '3.0', '>=' ) ) {
            deactivate_plugins( RW_Distributed_Profile_Client::$plugin_filename );
            die(
            wp_sprintf(
                '<strong>%s:</strong> ' .
                __( 'This plugin requires WordPress 3.0 or newer to work', RW_Distributed_Profile_Client::get_textdomain() )
                , RW_Distributed_Profile_Client::get_plugin_data( 'Name' )
            )
            );
        }


        // check php version
        if ( version_compare( PHP_VERSION, '5.2.0', '<' ) ) {
            deactivate_plugins( RW_Distributed_Profile_Client::$plugin_filename );
            die(
            wp_sprintf(
                '<strong>%1s:</strong> ' .
                __( 'This plugin requires PHP 5.2 or newer to work. Your current PHP version is %1s, please update.', RW_Distributed_Profile_Client::get_textdomain() )
                , RW_Distributed_Profile_Client::get_plugin_data( 'Name' ), PHP_VERSION
            )
            );
        }
    }

    /**
     * Clean up after deactivation
     *
     * Clean up after deactivation the plugin
     * Refresh rewriterules
     *
     * @since   0.1
     * @access  public
     * @static
     * @return  void
     */
    public static function on_deactivation() {

    }

    /**
     * Clean up after uninstall
     *
     * Clean up after uninstall the plugin.
     * Delete options and other stuff.
     *
     * @since   0.1
     * @access  public
     * @static
     * @return  void
     *
     */
    public static function on_uninstall() {

    }
}