<?php

/**
 * Class RW_Distributed_Profile_Client_Options
 *
 * Contains some helper code for plugin options
 *
 */

class RW_Distributed_Profile_Client_Options {


    /**
     * Register all settings
     *
     * Register all the settings, the plugin uses.
     *
     * @since   0.1
     * @access  public
     * @static
     * @return  void
     */
    static public function register_settings() {
        register_setting( 'rw_distributed_profile_client_options', 'rw_distributed_profile_client_options_endpoint_url' );
        register_setting( 'rw_distributed_profile_client_options', 'rw_distributed_profile_client_options_enable_remote_update' );

        // Settings for profile fields
        $remote_profiles = RW_Distributed_Profile_Client_Profile::get_profile_list();
        if ( is_object( $remote_profiles)  && is_object( $remote_profiles->wordpress ) ) {
            foreach ( $remote_profiles->wordpress as $wpfields ) {
                register_setting( 'rw_distributed_profile_client_options', 'rw_distributed_profile_client_options_wordpress_' . $wpfields );
            }
        }
        if ( is_object( $remote_profiles)  && is_object( $remote_profiles->buddypress ) ) {
            foreach ( $remote_profiles->buddypress as $bpgroup ) {
                foreach ( $bpgroup->fields as $field ) {
                    register_setting( 'rw_distributed_profile_client_options', 'rw_distributed_profile_client_options_bp_' . $bpgroup->name . '_' . $field->name );

                }
            }
        }
    }

    /**
     * Add a settings link to the  pluginlist
     *
     * @since   0.1
     * @access  public
     * @static
     * @param   string array links under the pluginlist
     * @return  array
     */
    static public function plugin_settings_link( $links ) {
        $settings_link = '<a href="options-general.php?page=' . RW_Distributed_Profile_Client::$plugin_base_name . '">' . __( 'Settings' )  . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Get the API Endpoint
     *
     * @since   0.1
     * @access  public
     * @static
     * @return  string
     */
    static public function get_endpoint() {
        return get_option( 'rw_distributed_profile_client_options_endpoint_url' );
    }

    /**
     * Generate the options menu page
     *
     * Generate the options page under the options menu
     *
     * @since   0.1
     * @access  public
     * @static
     * @return  void
     */
    static public function options_menu() {
        add_options_page( 'Distributed Profile Client',  __('Distributed Profile Client', RW_Distributed_Profile_Client::$textdomain ), 'manage_options',
            RW_Distributed_Profile_Client::$plugin_base_name, array( 'RW_Distributed_Profile_Client_Options', 'create_options' ) );
    }

    /**
     * Check if profile group exists
     *
     * @since   0.1
     * @access  public
     * @static
     * @param   $groupname
     * @return  bool    true - if group exists, false - if group not exists
     */
    static public function bp_profile_group_exists( $groupname ) {
        $local_groups = bp_xprofile_get_groups( array (
            'fetch_fields'  => false,
        ) );
        foreach ( $local_groups as $bpgroup ) {
            if ( $bpgroup->name == $groupname ) {
                return $bpgroup->id;
            }
        }
        return false;
    }

    /**
     * Check if profile field exists
     *
     * @since   0.1
     * @access  public
     * @static
     * @param   $fieldname
     * @return  bool    true - if field exists, false - if field not exists
     */
    static public function bp_profile_field_exists( $fieldname ) {
        $local_groups = bp_xprofile_get_groups( array (
            'fetch_fields'  => true,
        ) );
        foreach ( $local_groups as $bpgroup ) {
            foreach ($bpgroup->fields as $fieldObj) {
                if ($fieldObj->name == $fieldname) {
                    return $fieldObj->id;
                }
            }
        }
        return false;
    }

    /**
     * Generate the options page for the plugin
     *
     * @todo    Umbau der Speicherroutine, wp_options name ist varchar(64), BuddyPress Fieldname ist varchar(150)
     *
     * @since   0.1
     * @access  public
     * @static
     *
     * @return  void
     */
    static public function create_options() {
        if ( !current_user_can( 'manage_options' ) )  {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }
        $creategroupe = false;
        $remote_profiles = RW_Distributed_Profile_Client_Profile::get_profile_list();
        if ( ( isset ( $_REQUEST['settings-updated'] ) &&  $_REQUEST['settings-updated'] == 'true' ) && function_exists( 'bp_xprofile_get_groups' ) && is_object( $remote_profiles)  && is_object( $remote_profiles->buddypress ) ) {
            $local_groups = bp_xprofile_get_groups( array (
                'fetch_fields'  => false,
                'fetch_visibility_level' => true,
            ) );
            foreach ( $remote_profiles->buddypress as $bpgroup ) {
                $creategroupe = false;
                $groupid = self::bp_profile_group_exists( $bpgroup->name);
                if ( $groupid === false  ) {
                    $creategroupe = $bpgroup;
                }
                foreach ( $bpgroup->fields as $field ) {
                    $fieldname = 'rw_distributed_profile_client_options_bp_' . $bpgroup->name . '_' . $field->name;
                    if ( get_option( $fieldname ) == 1 ) {
                        if ( self::bp_profile_field_exists( $field->name) === false ) {
                            if ( $creategroupe !== false ) {
                                $groupid = xprofile_insert_field_group(
                                    array(
                                        'name'           => $bpgroup->name,
                                        'description'    => $bpgroup->description,
                                        'can_delete'     => $bpgroup->can_delete )
                                );
                                $creategroupe = false;
                            }
                            $fieldid = xprofile_insert_field(
                                array(
                                    'field_id'          => null,
                                    'field_group_id'    => $groupid,
                                    'parent_id'         => null,
                                    'type'              => $field->type,
                                    'name'              => $field->name,
                                    'description'       => $field->description,
                                    'is_required'       => $field->is_required,
                                    'can_delete'        => true,
                                    'order_by'          => '',
                                    'is_default_option' => false,
                                    'option_order'      => null,
                                    'field_order'       => null,
                                )
                            );
                            // save field options
                            if ( $fieldid !== false ) {
                                foreach( $field->options as $option ) {
                                    xprofile_insert_field(
                                        array(
                                            'field_id'          => null,
                                            'field_group_id'    => $groupid,
                                            'parent_id'         => $fieldid,
                                            'type'              => $option->type,
                                            'name'              => $option->name,
                                            'description'       => $option->description,
                                            'is_required'       => $option->is_required,
                                            'can_delete'        => $option->can_delete,
                                            'order_by'          => '',
                                            'is_default_option' => $option->is_default_option,
                                            'option_order'      => null,
                                            'field_order'       => null,
                                        )
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }

        $endpoint_url = get_option( 'rw_distributed_profile_client_options_endpoint_url' );
        ?>
        <div class="wrap"  id="rwdistributedprofileclientoptions">
            <h2><?php _e( 'Distributed Profile Client Options', RW_Distributed_Profile_Client::$textdomain ); ?></h2>
            <p><?php _e( 'Settings for Distributed Profile Client', RW_Distributed_Profile_Client::$textdomain ); ?></p>
            <form method="POST" action="options.php"><fieldset class="widefat">
                    <?php
                    settings_fields( 'rw_distributed_profile_client_options' );
                    ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="rw_distributed_profile_client_options_endpoint_url"><?php _e( 'API Endpoint URL', RW_Distributed_Profile_Client::$textdomain ); ?></label>
                            </th>
                            <td>
                                <input id="rw_distributed_profile_client_options_endpoint_url" class="regular-text" type="text" value="<?php echo $endpoint_url; ?>" aria-describedby="endpoint_url-description" name="rw_distributed_profile_client_options_endpoint_url" >
                                <p id="endpoint_url-description" class="description"><?php _e( 'Endpoint URL for API request.', RW_Distributed_Profile_Client::$textdomain); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="rw_distributed_profile_client_options_enable_remote_update"><?php _e( 'Enable remote update', RW_Distributed_Profile_Client::$textdomain ); ?></label>
                            </th>
                            <td>
                                <label for="rw_distributed_profile_client_options_enable_remote_update">
                                    <input id="rw_distributed_profile_client_options_enable_remote_update" type="checkbox" value="1" <?php if ( get_option( 'rw_distributed_profile_client_options_enable_remote_update')  == 1 ) echo " checked "; ?>   name="rw_distributed_profile_client_options_enable_remote_update">
                                    <?php _e( 'Send profile update to profile server.', RW_Distributed_Profile_Client::$textdomain); ?></label>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                               <?php _e( 'Select profile fields to copy from remote server', RW_Distributed_Profile_Client::$textdomain ); ?>
                            </th>
                            <td>
                        <?php
                        // Settings for profile fields

                        if ( is_object( $remote_profiles)  && is_object( $remote_profiles->wordpress ) ) {
                            echo "<h3>";
                            _e( 'WordPress fields', RW_Distributed_Profile_Client::get_textdomain() );
                            echo "</h3>";
                            foreach ( $remote_profiles->wordpress as $wpfields ) {
                                $fieldname = 'rw_distributed_profile_client_options_wordpress_' . $wpfields;
                                ?>
                                <label for="<?php echo $fieldname; ?>">
                                    <input id="<?php echo $fieldname; ?>" type="checkbox" value="1" <?php if ( get_option( $fieldname )  == 1 ) echo " checked "; ?>   name="<?php echo $fieldname; ?>">
                                    <?php echo $wpfields . "<br/>"?></label>
                                <?php
                            }
                        }
                        if ( is_object( $remote_profiles)  && is_object( $remote_profiles->buddypress ) && function_exists( 'bp_xprofile_get_groups' ) ) {
                            echo "<h3>";
                            _e( 'BuddyPress groups', RW_Distributed_Profile_Client::get_textdomain() );
                            echo "</h3>";
                            foreach ( $remote_profiles->buddypress as $bpgroup ) {
                                echo "<h4>";
                                echo $bpgroup->name;
                                echo "</h4>";
                                foreach ( $bpgroup->fields as $field ) {
                                    $fieldname = 'rw_distributed_profile_client_options_bp_' . $bpgroup->name . '_' . $field->name;
                                    ?>
                                    <label for="<?php echo $fieldname; ?>">
                                        <input id="<?php echo $fieldname; ?>" type="checkbox" value="1" <?php if ( get_option( $fieldname )  == 1 ) echo " checked "; ?>   name="<?php echo $fieldname; ?>">
                                        <?php echo $field->name . "<br/>"?></label>
                                    <?php
                                }
                            }
                        }
                        ?>
                            </td>
                        </tr>
                    </table>

                    <br/>

                    <input type="submit" class="button-primary" value="<?php _e('Save Changes' )?>" />
            </form>
        </div>
    <?php
    }
}