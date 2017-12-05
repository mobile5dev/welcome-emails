<?php
/*
Plugin Name: Welcome Emails
Plugin URI: http://example.com
Description: Sends custom welcome emails to public and private media users. Email titles and copies are found in General Settings.
Version: 1.0
Author: David Yip <david.yip@mobile-5.com>
Author URI: http://example.com
License: A "Slug" license name e.g. GPL2
*/
if( ! function_exists( 'wp_new_user_notification' ) ) {

    function wp_new_user_notification( $user_id, $deprecated = null, $notify = '' ) {

        if ( $deprecated !== null ) {

            _deprecated_argument( __FUNCTION__, '4.3.1' );
        }

        global $wpdb, $wp_hasher;
        $user = get_userdata( $user_id );

        // The blogname option is escaped with esc_html on the way into the database in sanitize_option
        // we want to reverse this for the plain text arena of emails.
        $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

        // Message that gets sent to admin if new user registers
        $message  = "A new user has signed up on MP & Silva with the following details:\r\n\r\n";
        $message .= sprintf(__('Username: %s'), $user->user_login) . "\r\n\r\n";
        $message .= sprintf(__('Email: %s'), $user->user_email) . "\r\n";

        @wp_mail(get_field('admin_email', 'option'), sprintf(__('[%s] New User Registration'), $blogname), $message);

        // `$deprecated was pre-4.3 `$plaintext_pass`. An empty `$plaintext_pass` didn't sent a user notifcation.
        if ( 'admin' === $notify || ( empty( $deprecated ) && empty( $notify ) ) ) {
            return;
        }

        ///////////////////////////////////////////////////////////////////
        //  Your code starts here, rest of it is WordPress default code  //
        ///////////////////////////////////////////////////////////////////

        // First role
        if( in_array("subscriber", $user->roles) ) {
          // Generate something random for a password reset key.
          $key = wp_generate_password( 20, false );

          /** This action is documented in wp-login.php */
          do_action( 'retrieve_password_key', $user->user_login, $key );

          // Now insert the key, hashed, into the DB.
          if ( empty( $wp_hasher ) ) {
              require_once ABSPATH . WPINC . '/class-phpass.php';
              $wp_hasher = new PasswordHash( 8, true );
          }
          $hashed = time() . ':' . $wp_hasher->HashPassword( $key );
          $wpdb->update( $wpdb->users, array( 'user_activation_key' => $hashed ), array( 'user_login' => $user->user_login ) );

          $email_subject = get_field('private_welcome_email_title', 'option');

          $message = get_field('private_welcome_email_copy', 'option');

          $pass_link = '<a href="' . network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user->user_login), 'login') . '">here</a>';

          $message = str_replace("[reset_pass_link]", $pass_link, $message);
        }
        // Second role
        else if( in_array("public_media_libary", $user->roles) ) {
          $email_subject = get_field('public_welcome_email_title', 'option');

          $message = get_field('public_welcome_email_copy', 'option');
        }

        $headers = array('Content-Type: text/html; charset=UTF-8');

        // Send email
        wp_mail( $user->user_email, $email_subject, $message, $headers );

    }
}
