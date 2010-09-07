<?php
/*
MarketPress Multisite Features
Version: 1.0
Plugin URI: http://premium.wpmudev.org/project/marketpress
Description: Community eCommerce for WordPress, WPMU, and BuddyPress
Author: Aaron Edwards (Incsub)
Author URI: http://uglyrobot.com

Copyright 2009-2010 Incsub (http://incsub.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

class MarketPress_MS {

	function MarketPress_MS() {
		$this->__construct();
	}
	
  function __construct() {
  
    // Plug admin pages
		add_action( 'admin_menu', array(&$this, 'add_menu_items') );

	}

  function install() {

  }

  function add_menu_items() {
    global $mp;
    
    if ($mp->sitewide) {
      $page = add_submenu_page('ms-admin.php', __('MarketPress Network Options', 'mp'), __('MarketPress Options', 'mp'), 10, 'marketpress-ms', array(&$this, 'super_admin_page'));
    }
    
    add_action( 'admin_print_scripts-' . $page, array(&$this, 'admin_script_settings') );
    add_action( 'admin_print_styles-' . $page, array(&$this, 'admin_css_settings') );
  }

  //enqeue css on settings screen
  function admin_css_settings() {
    global $mp_version;
    //wp_enqueue_style( 'jquery-datepicker-css', $this->plugin_url . '/marketpress/datepicker/css/ui-lightness/jquery-ui-1.7.2.custom.css', false, $mp_version);
  }

  //enqeue js on settings screen
  function admin_script_settings() {
    global $mp_version;
    //wp_enqueue_script( 'jquery-datepicker', $this->plugin_url . '/marketpress/datepicker/js/jquery-ui-1.7.2.custom.min.js', array('jquery'), $mp_version);
  }

  function super_admin_page() {
    //double-check rights
    if(!is_super_admin()) {
  		echo "<p>" . __('Nice Try...', 'mp') . "</p>";  //If accessed properly, this message doesn't appear.
  		return;
  	}
    ?>
    <div class="wrap">
    <h2><?php _e('MarketPress Network Options', 'mp') ?></h2>

    </div>
    <?php
  }
}
$mp_wpmu = &new MarketPress_MS();

?>