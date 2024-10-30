<?php

/*
Plugin Name: Business Directory Plugin Mobile App Manager
Description: Mobile App Manager settings for Business Directory
Version: 1.1
Author: Tiny Screen Labs
Author URI: http://tinyscreenlabs.com
License: GPL2+ or Later
Text Domain: business-directory-mobile-app-manager
*/

include_once 'tsl-install-manager.php';
include_once 'class-tgm-plugin-activation.php';

add_action('plugins_loaded', array('business_directory_plugin_mobile_app_manager', 'init'));

class business_directory_plugin_mobile_app_manager {

    private $mam_fields = array();
    private $bd_fields = array();
    private $is_bd_installed = false;
    private $is_mam_installed = false;

    public static function init() {
        $class = __CLASS__;
        new $class;
    }

    function __construct() {

        if ( !function_exists('get_plugins') ){
            require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
        }

        $this->mam_fields = array( 'street' , 'city' , 'state' , 'zip', 'phone', 'website'  );
        $this->set_is_bd_installed();

        add_action( 'admin_menu', array( $this, 'add_submenu' ) , 99 );

        $plugin = plugin_basename(__FILE__);
        add_filter("plugin_action_links_$plugin", array($this, 'plugins_settings_link' ));
        add_action( 'admin_menu', array($this,'admin_menu' ));

    }


    public function admin_menu() {
		add_options_page(
			'Business Directory Mobile App Manager',
			'BD Mobile App Manager',
			'manage_options',
			'business-directory-mobile-app-manager',
			array(
				$this,
				'settings_page'
			)
		);
	}

	function  settings_page() {
		$this->display_mam_settings();
	}

    function plugins_settings_link($links) {
        if($this->is_bd_installed){
            $mylinks = array( '<a href="' . admin_url( 'admin.php?page=WPBD_Mobile_App_Manager' ) . '">Settings</a>', );
        }else {
            $mylinks = array('<a href="' . admin_url('options-general.php?page=business-directory-mobile-app-manager') . '">Settings</a>',);
        }
        return array_merge( $mylinks , $links );
    }

    function add_submenu(){

        add_submenu_page('wpbdp_admin',
                     _x('Business Directory Mobile App Manager settings', 'admin menu', 'WPBDM'),
                     _x('Mobile App Settings', 'admin menu', 'WPBDM'),
                     'administrator',
                     'WPBD_Mobile_App_Manager',
                     array($this, 'display_mam_settings'));

    }

    function set_is_bd_installed(){

        if( function_exists('wpbdp_get_form_fields') ) {
            $this->is_bd_installed = true;
        }else{
            $this->is_bd_installed = false;
        }
        if( ! is_plugin_active( 'wp-local-app/wp-local-app.php' ) ) {
            $this->is_mam_installed = false;
        }else{
            $this->is_mam_installed = true;
        }
    }

    function set_bd_fields(){

        $this->set_is_bd_installed();

        if($this->is_bd_installed) {
            $all_fields = wpbdp_get_form_fields();

            for ($f = 0; $f < sizeof($all_fields); $f++) {
                $this_obj = $all_fields[$f];
                $this->bd_fields[] = array('id' => $this_obj->get_id(), 'label' => $this_obj->get_label(), 'shortname' => $this_obj->get_shortname());
            }
        }
    }

    function display_mam_settings(){

        if($this->is_bd_installed) {
            $this->display_settings();
        }else{
            $this->display_mam_required();
        }
        $html_line = $this->feedback();
        if($this->is_bd_installed) {
            $html_line .= $this->mobile_app_manager();
        }
        echo $html_line;

    }

    public function display_mam_required(){

        $html_line = '<br><div class="tsl_section" style="max-width:65em;"><h2>' . __('Mobile App Manager', 'business-directory-mobile-app-manager') . '</h2>';
        $html_line .= '<p><span>' . __('The Business Directory Plugin Mobile App Manager is designed to connect your Business Directory Plugin listings to TSL Mobile App Manager. TSL Mobile App Manager is a WordPress plugin and cloud based service that enables WordPress Admins to design a mobile app and complete the submission process right inside the WordPress dashboard.', 'business-directory-mobile-app-manager') . '</span></p>';

        $html_line .= '<p><span>' . __('For more information go to the ', 'business-directory-mobile-app-manager') . '<a href="https://tinyscreenlabs.com/?tslref=tslaffiliate" target="_blank">' . __('Tiny Screen Labs', 'business-directory-mobile-app-manager') . '</a> (TSL) '.__('website or download the ', 'business-directory-mobile-app-manager').' <a href="https://tinyscreenlabs.com/wp-content/plugins/tsl-traffic-manager/updates/affiliatetsl/wp-local-app.zip">'.__('Mobile App Manager plugin', 'business-directory-mobile-app-manager').'</a>. </span></p>';
        $html_line .= '</div>';

        echo $html_line;

    }

    public function display_settings(){

        if(isset($_REQUEST['page']) && $_REQUEST['page'] == 'WPBD_Mobile_App_Manager' ){
            foreach($this->mam_fields as $index => $value ){
                if(isset($_REQUEST['tsl_'.$value])){
                    update_option( 'tsl_'.$value , $_REQUEST['tsl_'.$value] );
                }
            }

            if(function_exists('local_app_update_cursor')){
                local_app_update_cursor(false);
                local_app_update_cursor(true);
            }
        }

        $this->set_bd_fields();

        echo '<div class="wrap">';
        echo '<h2>'.__('Business Directory Mobile App Manager Settings', 'business-directory-mobile-app-manager').'</h2>';
        echo '<div class="section"><h2>'.__('Field Mapping', 'business-directory-mobile-app-manager').'</h2></div>';
        echo '<form method="get">';
        echo '<input type="hidden" value="WPBD_Mobile_App_Manager" name="page">';
        echo '<p style="font-weight: bold;">'.__('Match the Business Directory Fields to the Mobile App Manager Fields', 'business-directory-mobile-app-manager').'&nbsp;&nbsp;</p>';

        echo '<table style="width:100%;max-width:70em;">';

        echo '<tr><td style="font-weight: bold;">Mobile App Manager Field</td><td style="font-weight: bold;">Business Directory Field</td></tr>';

        foreach($this->mam_fields as $index => $value ){
            echo '<tr><td>'.$value.'</td><td>'.$this->create_mam_dropdown( $value , get_option('tsl_'.$value)).'</td></tr>';
        }

        echo '</table>';

        echo '<p>'. submit_button().'</p>';

        echo '</form>';
        echo '</div>';


    }

    public function create_mam_dropdown( $mam_field_name , $selected ){

        $this_selected = '';

        if(! $selected){
            $selected = 0;
            $this_selected = 'selected';
        }

        $html_line = '<select id="tsl_'.$mam_field_name.'" name="tsl_'.$mam_field_name.'">';

        $html_line .= '<option value="0"' .$this_selected.'>'.__('Select Field', 'business-directory-mobile-app-manager').'</option>';

        foreach($this->bd_fields as $index => $value ){
            if($value['id'] == $selected ){
                $this_selected = 'selected';
            }else{
                $this_selected = '';
            }

            $html_line .= '<option value="'.$value['id'].'" '.$this_selected.'>'.$value['label'].'</option>';
        }

        $html_line .= '<select>';

        return $html_line;

    }

    public  function feedback(){

        $html_line = '<div class="tsl_section" style="max-width:65em;"><h2>' . __('Feedback and Support', 'business-directory-mobile-app-manager') . '</h2>';
        $html_line .= '<p>If you need support, want to provide some feedback or have an idea for a new feature for Business Directory Mobile App Manager, drop us an email at <a href="mailto:info@tinyscreenlabs.com">info@tinyscreenlabs.com</a></p>';
        $html_line .= '</div>';
        return $html_line;

    }

    public  function mobile_app_manager(){

        $html_line = '';

        if(!$this->is_mam_installed){

            $installer = new tsl_install_manager_for_wpbdmam();
            $is_on_internet = $installer->is_connected_to_internet();
            $can_user_install = current_user_can('install_plugins');
            $button = '';

            $html_line .= '<br><div class="tsl_section" style="max-width:65em;">';
            if($is_on_internet) {
                if($can_user_install) $button = '<input id="tsl-install-plugin" class="button button-primary" value="Install from tinyscreenlabs.com" type="submit">';
                $html_line .= '<form method="post" action="https://tinyscreenlabs.com/install-plugins/">';
                $html_line .= '<input type="hidden" name="tslplugin" value="wpbdpmam">';
                $html_line .= '<table style="width:100%"><tr><td><h2>' . __('Mobile App Manager (Premium)', 'business-directory-offers') . '</h2></td><td align="right">' . $button . '</td></tr></table>';
                $html_line .= '</form>';
            }else{
                if($can_user_install) $button = '<input id="tsl-install-plugin" class="button button-primary" value="Download from tinyscreenlabs.com" type="submit" >';
                $html_line .= '<form target="_blank" action="https://tinyscreenlabs.com/">';
                $html_line .= '<table style="width:100%"><tr><td><h2>' . __('Mobile App Manager (Premium)', 'business-directory-offers') . '</h2></td><td align="right">' . $button . '</td></tr></table>';
                $html_line .= '</form>';
            }
            $html_line .= '<p><span>' . __('This plugin works with TSL Mobile App Manager to enable you to publish your listings, events and special offers on your own mobile app.  TSL Mobile App Manager is a WordPress plugin and cloud based service that enables WordPress Admins to design a mobile app and complete the submission process right inside the WordPress dashboard.', 'business-directory-offers') . '</span></p>';
            $html_line .= '<ul style="list-style-type:disc;margin-left:2em;">';
            $html_line .= '<li>' . __('WordPress administrators have the ability to manage content for their website and mobile apps in one place<', 'business-directory-offers') . '/li>';
            $html_line .= '<li>' . __('Business Directory Offers are displayed on your mobile app', 'business-directory-offers') . '</li>';
            $html_line .= '<li>' . __('App Setup is a drag and drop interface where you design your mobile app before you purchase a TSL Pro Plan', 'business-directory-offers') . '</li>';
            $html_line .= '<li>' . __('The TSL Local App Previewer is a WYSIWYG viewer that connects to your website', 'business-directory-offers') . '</li>';
            $html_line .= '<li>' . __('Updates to app page content are automatically pushed to the mobile app whenever you update pages and posts in WordPress', 'business-directory-offers') . '</li>';
            $html_line .= '<li>' . __('TSL publishes your app to iTunes and Google Play when you purchase the TSL Pro Plan', 'business-directory-offers') . '</li>';
            $html_line .= '</ul>';

            $html_line .= '<p><span>' . __('For more information go to the ', 'business-directory-offers') . '<a href="https://tinyscreenlabs.com/?tslref=tslaffiliate" target="_blank">' . __('Tiny Screen Labs', 'business-directory-offers') . '</a> (TSL) '.__('website', 'business-directory-offers').'. </span></p>';

            $html_line .= '</div>';

        }

        return $html_line;
    }
}