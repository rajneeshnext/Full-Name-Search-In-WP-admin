<?php
/*
Plugin Name: Full Name Search In WP-admin
Plugin URI: http://www.boldertechno.com/products/search-by-full-name-in-wordpress-admin/
Description:  Makes you search any user by typing full name.
Version: 1
Author: Rajneesh Saini
Author URI: http://www.boldertechno.com/
*/


/* version check */
global $wp_version;

if(version_compare($wp_version,"3.5","<")) {
	exit(__('Full Name Search In WP-admin requires WordPress version 3.5 or higher. 
				<a href="http://codex.wordpress.org/Upgrading_Wordpress">Please update!</a>', 'fullname-user-search-in-WP-admin'));
}

// Plugin is build to be used only in wpadmin
if(is_admin()) {
	

	// Action to inject custom search queries
    add_action('pre_user_query', 'empowering_user_search');	

   // Main function responsible for enhancing the search
    function empowering_user_search($wp_user_query) {
        if(false === strpos($wp_user_query->query_where, '@') && !empty($_GET["s"])) {

            global $wpdb;

            $uids=array();			

			$flsiwa_add = "";
			// Escaped query string
			$qstr = mysql_real_escape_string($_GET["s"]);

			if(preg_match('/\s/',$qstr)){
				$pieces = explode(" ", $qstr);
				$user_ids_collector = $wpdb->get_results("SELECT DISTINCT user_id FROM $wpdb->usermeta WHERE (meta_key='first_name' AND LOWER(meta_value) LIKE '%".$pieces[0]."%')");

	            foreach($user_ids_collector as $maf) {
	                if(strtolower(get_user_meta($maf->user_id, 'last_name', true)) == strtolower($pieces[1])){
						array_push($uids,$maf->user_id);
	                }
	            }

			}else{				

				$user_ids_collector = $wpdb->get_results("SELECT DISTINCT user_id FROM $wpdb->usermeta WHERE (meta_key='first_name' OR meta_key='last_name'".$flsiwa_add.") AND LOWER(meta_value) LIKE '%".$qstr."%'");
					foreach($user_ids_collector as $maf) {
	                array_push($uids,$maf->user_id);
	            }
			}

            $users_ids_collector = $wpdb->get_results("SELECT DISTINCT ID FROM $wpdb->users WHERE LOWER(user_nicename) LIKE '%".$qstr."%' OR LOWER(user_email) LIKE '%".$qstr."%'");

            foreach($users_ids_collector as $maf) {
                if(!in_array($maf->ID,$uids)) {
                    array_push($uids,$maf->ID);
                }
            }

            $id_string = implode(",",$uids);
			if (!empty($id_string))
			{
	            $wp_user_query->query_where = str_replace("user_nicename LIKE '%".$qstr."%'", "ID IN(".$id_string.")", $wp_user_query->query_where);
			}
        }
        return $wp_user_query;
    }
}

register_activation_hook(__FILE__,"fullname_user_search_in_wpadmin_activate");

function fullname_user_search_in_wpadmin_activate() {
	register_uninstall_hook(__FILE__,"fullname_user_search_in_wpadmin_uninstall");
}

function fullname_user_search_in_wpadmin_uninstall() {
	delete_option('flsiwa_meta_fields');
}

?>