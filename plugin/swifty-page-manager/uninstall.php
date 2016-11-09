<?php
/**
 * Plugin Uninstall Procedure
 */

//exit();
//
//// Make sure that we are uninstalling
//if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
//    exit();
//
//// Site options to delete
//$site_option_names = array();
//
//function swifty_content_creator_remove_options() {
//    global $wpdb;
//
//    // Option to delete, names can contain % as wildcard
//    $option_names = array( 'spm_plugin_options' );
//
//    // remove post meta data
//    $post_meta_names = array( 'spm_page_title_seo', 'spm_url', 'spm_show_in_menu', 'spm_show_as_first',
//        'spm_alt_menu_text', 'spm_old_url_%' );
//
//    // posttypes to delete
//    $post_types = array();
//
//    // start removing options
//    foreach( $option_names as $option_name ) {
//        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '{$option_name}'" );
//    }
//
//    // remove all post meta data
//    foreach( $post_meta_names as $post_meta_name ) {
//        $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '{$post_meta_name}'" );
//    }
//
//    // remove post types
//    foreach( $post_types as $post_type ) {
//        $post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts post_type = '%s'", $post_type ) );
//        foreach( $post_ids as $post_id ) {
//            wp_delete_post( $post_id, true );
//        }
//    }
//}
//
//// start removing options
//
//foreach( $site_option_names as $site_option_name ) {
//    delete_site_option( $site_option_name );
//}
//
//global $wpdb;
//
//if ( !is_multisite() ) {
//    swifty_content_creator_remove_options();
//} else {
//    global $wpdb;
//
//    $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
//
//    foreach( $blog_ids as $blog_id ) {
//        switch_to_blog( $blog_id );
//
//        swifty_content_creator_remove_options();
//    }
//
//    restore_current_blog();
//}