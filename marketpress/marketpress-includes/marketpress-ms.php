<?php
/*
MarketPress Multisite Features
*/

class MarketPress_MS {

  var $global_list_template;
  var $tag_template;
  var $category_template;
  
	function MarketPress_MS() {
		$this->__construct();
	}
	
  function __construct() {
    global $mp;
    
    //install script
    $this->install();
    
    // Plug admin pages
		add_action( 'admin_menu', array(&$this, 'add_menu_items') );
		add_action( 'network_admin_menu', array(&$this, 'add_menu_items') );

    //index products
    add_action( 'save_post', array(&$this, 'index_product') );
    add_action( 'delete_post', array(&$this, 'delete_product') );
    add_action( 'mp_product_sale', array(&$this, 'record_sale'), 10, 4 );
    
    //handle blog changes
    add_action( 'make_spam_blog', array(&$this, 'remove_blog') );
    add_action( 'archive_blog', array(&$this, 'remove_blog') );
    add_action( 'mature_blog', array(&$this, 'remove_blog') );
    add_action( 'deactivate_blog', array(&$this, 'remove_blog') );
    add_action( 'delete_blog', array(&$this, 'remove_blog') );
    add_action( 'update_option_blog_public', array(&$this, 'public_update'), 16, 2 );

    //Templates and Rewrites
    if ( $this->is_main_site() ) {
  		add_action( 'template_redirect', array(&$this, 'load_marketplace_templates') );
  		add_filter( 'rewrite_rules_array', array(&$this, 'add_rewrite_rules') );
    	add_filter( 'query_vars', array(&$this, 'add_queryvars') );
    }

    //check for main blog limits
    $settings = get_site_option( 'mp_network_settings' );
    if ( ( $settings['main_blog'] && $this->is_main_site() ) || !$settings['main_blog'] ) {
      //shortcodes
      add_shortcode( 'mp_list_global_products', array(&$this, 'mp_list_global_products_sc') );
      add_shortcode( 'mp_global_categories_list', array(&$this, 'mp_global_categories_list_sc') );
      add_shortcode( 'mp_global_tag_cloud', array(&$this, 'mp_global_tag_cloud_sc') );
      
      //widgets
      add_action( 'widgets_init', create_function('', 'return register_widget("MarketPress_Global_Product_List");') );
      add_action( 'widgets_init', create_function('', 'return register_widget("MarketPress_Global_Tag_Cloud_Widget");') );
      add_action( 'widgets_init', create_function('', 'return register_widget("MarketPress_Global_Category_List_Widget");') );
    }
    
	}

  function install() {
    global $wpdb, $current_site, $mp;

    //check if installed
    if ( get_site_option( "mp_network_settings" ) )
      return;

    //create tables
    $table_1 = "CREATE TABLE IF NOT EXISTS `{$wpdb->base_prefix}mp_products` (
                `id` bigint(20) unsigned NOT NULL auto_increment,
                `site_id` bigint(20),
                `blog_id` bigint(20),
                `blog_public` int(2),
                `post_id` bigint(20),
                `post_author` bigint(20) unsigned NOT NULL DEFAULT '0',
                `post_title` text NOT NULL,
                `post_content` longtext NOT NULL,
                `post_permalink` text NOT NULL,
                `post_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                `post_date_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                `post_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                `post_modified_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                `price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00',
                `sales_count` bigint(20) unsigned NOT NULL DEFAULT '0',
                PRIMARY KEY  (`id`)
              ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";
    $table_2 = "CREATE TABLE IF NOT EXISTS `{$wpdb->base_prefix}mp_terms` (
                `term_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `name` varchar(200) NOT NULL DEFAULT '',
                `slug` varchar(200) NOT NULL DEFAULT '',
                `type` varchar(20) NOT NULL DEFAULT 'product_category',
                `count` bigint(10) NOT NULL DEFAULT '0',
                PRIMARY KEY (`term_id`),
                UNIQUE KEY `slug` (`slug`),
                KEY `name` (`name`)
              ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
    $table_3 = "CREATE TABLE IF NOT EXISTS `{$wpdb->base_prefix}mp_term_relationships` (
                `post_id` bigint(20) unsigned NOT NULL,
                `term_id` bigint(20) unsigned NOT NULL,
                PRIMARY KEY ( `post_id` , `term_id` ),
                KEY (`term_id`)
              ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
    $wpdb->query( $table_1 );
		$wpdb->query( $table_2 );
		$wpdb->query( $table_3 );
		
		$default_settings = array(
      'main_blog' => $current_site->blog_id,
      'global_cart' => 0,
      'allowed_gateways' => array(
        'paypal-express' => 'full',
        'paypal-chained' => 'none',
        'paypal-pro' => 'none',
        'authorizenet-aim' => 'none',
        'authorizenet-sim' => 'full',
        'google-checkout' => 'full',
        '2checkout' => 'full',
        'manual-payment' => 'full',
        'moneybookers' => 'full'
      ),
			'global_gateway' => 'paypal-express',
      'allowed_themes' => array(
        'classic' => 'full',
        'modern' => 'full',
        'icons' => 'full'
      ),
      'slugs' => array(
        'marketplace' => 'marketplace',
        'categories' => 'categories',
        'tags' => 'tags'
      )
    );
    
    update_site_option( 'mp_network_settings', $default_settings );
    
    //add action to flush rewrite rules after we've added them for the first time
    add_action( 'init', array(&$mp, 'flush_rewrite'), 999 );

  }

  //wrapper function for checking if global marketpress blog. Define MP_ROOT_BLOG with a blog_id to override
  function is_main_site() {
    global $wpdb;
    if ( defined( 'MP_ROOT_BLOG' ) ) {
      return $wpdb->blogid == MP_ROOT_BLOG;
    } else {
      return is_main_site();
    }
  }

  function add_rewrite_rules($rules){
    $settings = get_site_option('mp_network_settings');

    $new_rules = array();

    //marketplace
    $new_rules[$settings['slugs']['marketplace'] . '/?$'] = 'index.php?pagename=mp_global_products';
    $new_rules[$settings['slugs']['marketplace'] . '/page/?([0-9]{1,})/?$'] = 'index.php?pagename=mp_global_products&paged=$matches[1]';

    //categories
    $new_rules[$settings['slugs']['marketplace'] . '/' . $settings['slugs']['categories'] . '/?$'] = 'index.php?pagename=mp_global_categories';
    $new_rules[$settings['slugs']['marketplace'] . '/' . $settings['slugs']['categories'] . '/([^/]+)/?$'] = 'index.php?pagename=mp_global_categories&global_taxonomy=$matches[1]';
  	$new_rules[$settings['slugs']['marketplace'] . '/' . $settings['slugs']['categories'] . '/([^/]+)/page/?([0-9]{1,})/?$'] = 'index.php?pagename=mp_global_categories&global_taxonomy=$matches[1]&paged=$matches[2]';

    //tags
    $new_rules[$settings['slugs']['marketplace'] . '/' . $settings['slugs']['tags'] . '/?$'] = 'index.php?pagename=mp_global_tags';
    $new_rules[$settings['slugs']['marketplace'] . '/' . $settings['slugs']['tags'] . '/([^/]+)/?$'] = 'index.php?pagename=mp_global_tags&global_taxonomy=$matches[1]';
    $new_rules[$settings['slugs']['marketplace'] . '/' . $settings['slugs']['tags'] . '/([^/]+)/page/?([0-9]{1,})/?$'] = 'index.php?pagename=mp_global_tags&global_taxonomy=$matches[1]&paged=$matches[2]';

  	return array_merge($new_rules, $rules);
  }

  function add_queryvars($vars) {
  	// This function add the queryvars to the list that WordPress is looking for.
  	if(!in_array('global_taxonomy', $vars))
      $vars[] = 'global_taxonomy';

  	return $vars;
  }

  //scans post type at template_redirect to apply custom themeing to products
  function load_marketplace_templates() {
    global $wp_query, $mp;
    $settings = get_option('mp_network_settings');
    $is_shop_page = false;

    //load proper theme for global products page
    if ($wp_query->query_vars['pagename'] == 'mp_global_products') {

      $templates[] = "mp_global_products.php";

      //if custom template exists load it
      if ($this->global_list_template = locate_template($templates)) {
        add_filter( 'template_include', array(&$this, 'custom_product_list_template') );
        add_filter( 'single_post_title', array(&$this, 'page_title_output'), 99 );
      } else {
        //otherwise load the page template and use our own theme
        add_filter( 'single_post_title', array(&$this, 'page_title_output'), 99 );
        add_filter( 'the_title', array(&$this, 'page_title_output'), 99 );
        add_filter( 'the_excerpt', array(&$this, 'product_list_theme'), 99 );
        add_filter( 'the_content', array(&$this, 'product_list_theme'), 99 );
      }

      $is_shop_page = true;
    }

    //load proper theme for order status page
    if ($wp_query->query_vars['pagename'] == 'mp_global_categories') {

      $templates = array();
      
      if ($cat_name = get_query_var('global_taxonomy')) {
        $templates[] = "mp_global_category-$cat_name.php";
      	$templates[] = "mp_global_category.php";
      } else {
        $templates[] = "mp_global_category_list.php";
      }

      //if custom template exists load it
      if ($this->category_template = locate_template($templates)) {
        add_filter( 'template_include', array(&$this, 'custom_category_template') );
        add_filter( 'single_post_title', array(&$this, 'page_title_output'), 99 );
      } else {
        //otherwise load the page template and use our own theme
        add_filter( 'single_post_title', array(&$this, 'page_title_output'), 99 );
        add_filter( 'the_title', array(&$this, 'page_title_output'), 99 );
        add_filter( 'the_content', array(&$this, 'global_categories_theme'), 99 );
      }

      $is_shop_page = true;
    }
    
    //load proper theme for order status page
    if ($wp_query->query_vars['pagename'] == 'mp_global_tags') {

      $templates = array();

      if ($tag_name = get_query_var('global_taxonomy')) {
        $templates[] = "mp_global_tag-$tag_name.php";
      	$templates[] = "mp_global_tag.php";
      } else {
        $templates[] = "mp_global_tag_list.php";
      }

      //if custom template exists load it
      if ($this->tag_template = locate_template($templates)) {
        add_filter( 'template_include', array(&$this, 'custom_tag_template') );
        add_filter( 'single_post_title', array(&$this, 'page_title_output'), 99 );
      } else {
        //otherwise load the page template and use our own theme
        add_filter( 'single_post_title', array(&$this, 'page_title_output'), 99 );
        add_filter( 'the_title', array(&$this, 'page_title_output'), 99 );
        add_filter( 'the_content', array(&$this, 'global_tags_theme'), 99 );
      }

      $is_shop_page = true;
    }

    //load shop specific items
    if ($is_shop_page) {
    
      //prevent query errors on virtual pages
      $wp_query->is_page = 1;
      $wp_query->is_singular = 1;
      $wp_query->is_404 = false;
      $wp_query->post_count = 1;
        
      //fixes a nasty bug in BP theme's functions.php file which always loads the activity stream if not a normal page
      remove_all_filters('page_template');

      //prevents 404 for virtual pages
      status_header( 200 );

      //load theme
      $mp->load_store_theme();
    }
  }


  //filter the template
  function custom_product_list_template($template) {
    return $this->global_list_template;
  }
  
  //filter the template
  function custom_category_template($template) {
    return $this->category_template;
  }
  
  //filter the template
  function custom_tag_template($template) {
    return $this->tag_template;
  }

  //filters the titles for our custom pages
  function page_title_output($title, $id = false) {
    global $wp_query, $wpdb;

    //filter out nav titles
    if (!empty($title) && $id === false)
      return $title;

    if ( $slug = get_query_var('global_taxonomy') ) {
      $slug = $wpdb->escape( $slug );
      $name = $wpdb->get_var( "SELECT name FROM {$wpdb->base_prefix}mp_terms WHERE slug = '$slug'" );
    }

    switch ($wp_query->query_vars['pagename']) {
      case 'mp_global_products':
        return __('Marketplace Products', 'mp');
        break;

      case 'mp_global_categories':
        if ($name)
          return sprintf( __('Product Category: %s', 'mp'), esc_attr($name) );
        else
          return __('Marketplace Product Categories', 'mp');
        break;
        
      case 'mp_global_tags':
        if ($name)
          return sprintf( __('Product Tag: %s', 'mp'), esc_attr($name) );
        else
          return __('Marketplace Product Tags', 'mp');
        break;

      default:
        return $title;
    }
  }
  
/* Leave for a future version

  //adds our links to theme nav menus using wp_list_pages()
  function filter_list_pages($list, $args) {

    if ($args['depth'] == 1)
      return $list;

    $settings = get_option('mp_network_settings');
    $store_link = home_url(trailingslashit($settings['slugs']['marketplace']));
    $cats_link = home_url(trailingslashit($settings['slugs']['marketplace'] . '/' . $settings['slugs']['categories']));
    $tags_link = home_url(trailingslashit($settings['slugs']['marketplace'] . '/' . $settings['slugs']['tags']));
    
    $temp_break = strpos($list, $store_link);

    //if we can't find the page for some reason skip
    if ($temp_break === false)
      return $list;

    $break = strpos($list, '</a>', $temp_break) + 4;

    $nav = substr($list, 0, $break);

    $nav .= '
<ul>
  <li class="page_item"><a href="' . $cats_link . '" title="' . __('Marketplace Product Categories', 'mp') . '">' . __('Marketplace Product Categories', 'mp') . '</a></li>
	<li class="page_item"><a href="' . $tags_link . '" title="' . __('Marketplace Product Tags', 'mp') . '">' . __('Marketplace Product Tags', 'mp') . '</a></li>
</ul>
';

    $nav .= substr($list, $break);

    return $nav;
  }

  //adds our links to custom theme nav menus using wp_nav_menu()
  function filter_nav_menu($list, $args) {

    if ($args->depth == 1)
      return $list;

    $settings = get_option('mp_network_settings');
    $store_link = home_url(trailingslashit($settings['slugs']['marketplace']));
    $cats_link = home_url(trailingslashit($settings['slugs']['marketplace'] . '/' . $settings['slugs']['categories']));
    $tags_link = home_url(trailingslashit($settings['slugs']['marketplace'] . '/' . $settings['slugs']['tags']));

    $temp_break = strpos($list, $store_link);

    //if we can't find the page for some reason skip
    if ($temp_break === false)
      return $list;

    $break = strpos($list, '</a>', $temp_break) + 4;

    $nav = substr($list, 0, $break);

    $nav .= '
<ul>
  <li class="menu-item menu-item-type-post_type menu-item-object-page">' . $args->before . '<a href="' . $cats_link . '" title="' . __('Marketplace Product Categories', 'mp') . '">' . $args->link_before . __('Marketplace Product Categories', 'mp') . $args->link_after . '</a>' . $args->after . '</li>
	<li class="menu-item menu-item-type-post_type menu-item-object-page">' . $args->before . '<a href="' . $tags_link . '" title="' . __('Marketplace Product Tags', 'mp') . '">' . $args->link_before . __('Marketplace Product Tags', 'mp') . $args->link_after . '</a>' . $args->after . '</li>
</ul>
';

    $nav .= substr($list, $break);

    return $nav;
  }
*/

  //this is the default theme added to the global product list page
  function product_list_theme($content) {
		//don't filter outside of the loop
  	if ( !in_the_loop() )
		  return $content;

    $args = array();
    $args['echo'] = false;

    //check for paging
    if (get_query_var('paged'))
      $args['page'] = intval(get_query_var('paged'));

    $content = mp_list_global_products( $args );
    $content .= get_posts_nav_link();

    return $content;
  }
  
  //this is the default theme added to the global categories page
  function global_categories_theme($content) {
    //don't filter outside of the loop
  	if ( !in_the_loop() )
		  return $content;

    if ( $slug = get_query_var('global_taxonomy') ) {
      $args = array();
      $args['echo'] = false;
      $args['category'] = $slug;
      
      //check for paging
      if (get_query_var('paged'))
        $args['page'] = intval(get_query_var('paged'));

      $content = mp_list_global_products( $args );
      $content .= get_posts_nav_link();
      
    } else { //no category set, so show list
      $content .= mp_global_categories_list( array( 'echo' => false ) );
    }
      
    return $content;
  }
  
  //this is the default theme added to the global tags page
  function global_tags_theme($content) {
    //don't filter outside of the loop
  	if ( !in_the_loop() )
		  return $content;

    if ( $slug = get_query_var('global_taxonomy') ) {
      $args = array();
      $args['echo'] = false;
      $args['tag'] = $slug;

      //check for paging
      if (get_query_var('paged'))
        $args['page'] = intval(get_query_var('paged'));

      $content = mp_list_global_products( $args );
      $content .= get_posts_nav_link();

    } else { //no category set, so show list
      $content = mp_global_tag_cloud( false );
    }

    return $content;
  }

  function add_menu_items() {
    global $mp, $wp_version;
    
    if ( version_compare($wp_version, '3.0.9', '>') ) {
      $page = add_submenu_page('settings.php', __('MarketPress Network Options', 'mp'), __('MarketPress', 'mp'), 10, 'marketpress-ms', array(&$this, 'super_admin_page'));
    } else {
      $page = add_submenu_page('ms-admin.php', __('MarketPress Network Options', 'mp'), __('MarketPress', 'mp'), 10, 'marketpress-ms', array(&$this, 'super_admin_page'));
    }
    //add_action( 'admin_print_scripts-' . $page, array(&$this, 'admin_script_settings') );
    //add_action( 'admin_print_styles-' . $page, array(&$this, 'admin_css_settings') );
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
    global $mp, $mp_gateway_plugins;
    
    //double-check rights
    if(!is_super_admin()) {
  		echo "<p>" . __('Nice Try...', 'mp') . "</p>";  //If accessed properly, this message doesn't appear.
  		return;
  	}

    //save settings
    if (isset($_POST['marketplace_network_settings'])) {

      //filter slugs
      $_POST['mp']['slugs'] = array_map('sanitize_title', $_POST['mp']['slugs']);

      update_site_option( 'mp_network_settings', apply_filters('mp_network_settings_save', $_POST['mp']) );
      
      //flush rewrite rules due to product slugs
      $mp->flush_rewrite();
      
      echo '<div class="updated fade"><p>'.__('Settings saved.', 'mp').'</p></div>';
    }
    $settings = get_site_option( 'mp_network_settings' );
    
    if (!isset($settings['global_cart']))
      $settings['global_cart'] = 0;
    ?>
    <div class="wrap">
    <div class="icon32"><img src="<?php echo $mp->plugin_url . 'images/settings.png'; ?>" /></div>
    <h2><?php _e('MarketPress Network Options', 'mp') ?></h2>
    <div id="poststuff" class="metabox-holder mp-settings">
      <form id="mp-main-form" method="post" action="">
        <input type="hidden" name="marketplace_network_settings" value="1" />

        <script type="text/javascript">
      	  jQuery(document).ready(function($) {
            $(".mp_change_submit").change(function() {
              $("#mp-main-form").submit();
        		});
          });
      	</script>
        <div class="postbox">
          <h3 class='hndle'><span><?php _e('General Settings', 'mp') ?></span></h3>
          <div class="inside">
            <table class="form-table">
              <tr>
			      		<th scope="row"><?php _e('Limit Global Widgets/Shortcodes To Main Blog', 'mp'); ?></th>
			      		<td>
									<label><input value="1" name="mp[main_blog]" type="radio"<?php checked($settings['main_blog'], 1) ?> /> <?php _e('Yes', 'mp') ?></label>
					    		<label><input value="0" name="mp[main_blog]" type="radio"<?php checked($settings['main_blog'], 0) ?> /> <?php _e('No', 'mp') ?></label>
			        	</td>
			        </tr>
				      <tr>
			      		<th scope="row"><?php _e('Enable Global shopping cart', 'mp'); ?></th>
			      		<td>
								  <label><input class="mp_change_submit" value="1" name="mp[global_cart]" type="radio"<?php checked($settings['global_cart'], 1) ?> /> <?php _e('Yes', 'mp') ?></label>
								  <label><input class="mp_change_submit" value="0" name="mp[global_cart]" type="radio"<?php checked($settings['global_cart'], 0) ?> /> <?php _e('No', 'mp') ?></label>
			        	</td>
              </tr>
            </table>
          </div>
        </div>

        <div class="postbox"<?php echo ($settings['global_cart']) ? '' : ' style="display:none;"'; ?>>
          <h3 class='hndle'><span><?php _e('Global Gateway', 'mp') ?></span> - <span class="description"><?php _e('With the global cart enabled, you must select only one compatible gateway to be used network wide.', 'mp') ?></span></h3>
          <div class="inside">
            <table class="form-table">
              <tr>
      				<th scope="row"><?php _e('Select a Gateway', 'mp') ?></th>
      				<td><?php
              foreach ((array)$mp_gateway_plugins as $code => $plugin) {
								//skip non global plugins
								if (!$plugin[2])
                  continue;
              ?>
              <label><input value="<?php echo $code; ?>" id="gbl_gw_<?php echo $code; ?>" name="mp[global_gateway]" type="radio"<?php checked($settings['global_gateway'], $code) ?> /> <?php echo $plugin[1]; ?></label><br />
              <?php } ?>
							</td>
              </tr>
            </table>
          </div>
        </div>

        <div class="postbox"<?php echo ($settings['global_cart']) ? ' style="display:none;"' : ''; ?>>
          <h3 class='hndle'><span><?php _e('Gateway Permissions', 'mp') ?></span> - <span class="description"><?php _e('Set payment gateway access permissions for network stores. The main site will maintain access to all gateways.', 'mp') ?></span></h3>
          <div class="inside">
            <table class="form-table">
              <?php
              foreach ((array)$mp_gateway_plugins as $code => $plugin) {
                $allowed = ($settings['allowed_gateways'][$code]) ? $settings['allowed_gateways'][$code] : 'none';
              ?>
              <tr>
      				<th scope="row"><?php echo $plugin[1]; ?></th>
      				<td>
              <label><input value="full" id="gw_full_<?php echo $code; ?>" name="mp[allowed_gateways][<?php echo $code; ?>]" type="radio"<?php checked($allowed, 'full') ?> /> <?php _e('All Can Use', 'mp') ?></label><br />
              <?php if (function_exists('is_supporter')) { ?>
              <label><input value="supporter" id="gw_supporter_<?php echo $code; ?>" name="mp[allowed_gateways][<?php echo $code; ?>]" type="radio"<?php checked($allowed, 'supporter') ?> /> <?php _e('Supporter Sites Only', 'mp') ?></label><br />
              <?php } ?>
              <label><input value="none" id="gw_none_<?php echo $code; ?>" name="mp[allowed_gateways][<?php echo $code; ?>]" type="radio"<?php checked($allowed, 'none') ?> /> <?php _e('No Access', 'mp') ?></label>
              </td>
              </tr>
              <?php
							}
							?>
            </table>
          </div>
        </div>
        
        <?php
        //for adding additional settings via plugins
        do_action('mp_network_gateway_settings', $settings);
        ?>
        
        <div class="postbox">
          <h3 class='hndle'><span><?php _e('Theme Permissions', 'mp') ?></span> - <span class="description"><?php _e('Set theme access permissions for network stores.', 'mp') ?></span></h3>
          <div class="inside">
            <span class="description"><?php _e('For a custom css theme, save your css file with the "MarketPress Theme: NAME" header in the "/marketpress/css/themes/" folder and it will appear in this list so you may select it.', 'mp') ?></span>
            <table class="form-table">
              <?php
              //get theme dir
              $theme_dir = $mp->plugin_dir . 'themes/';

              //scan directory for theme css files
              $theme_list = array();
              if ($handle = @opendir($theme_dir)) {
                while (false !== ($file = readdir($handle))) {
                  if (($pos = strrpos($file, '.css')) !== false) {
                    $value = substr($file, 0, $pos);
                    if (is_readable("$theme_dir/$file")) {
                      $theme_data = get_file_data( "$theme_dir/$file", array('name' => 'MarketPress Theme') );
                      if (is_array($theme_data))
                        $theme_list[$value] = $theme_data['name'];
                    }
                  }
                }

                @closedir($handle);
              }

              //sort the themes
              asort($theme_list);

              foreach ($theme_list as $value => $name) {
                $allowed = ($settings['allowed_themes'][$value]) ? $settings['allowed_themes'][$value] : 'full';
                ?>
                <tr>
        				<th scope="row"><?php echo $name; ?></th>
        				<td>
                <label><input value="full" name="mp[allowed_themes][<?php echo $value; ?>]" type="radio"<?php checked($allowed, 'full') ?> /> <?php _e('All Can Use', 'mp') ?></label><br />
                <?php if (function_exists('is_supporter')) { ?>
                <label><input value="supporter" name="mp[allowed_themes][<?php echo $value; ?>]" type="radio"<?php checked($allowed, 'supporter') ?> /> <?php _e('Supporter Sites Only', 'mp') ?></label><br />
                <?php } ?>
                <label><input value="none" name="mp[allowed_themes][<?php echo $value; ?>]" type="radio"<?php checked($allowed, 'none') ?> /> <?php _e('No Access', 'mp') ?></label>
                </td>
                </tr>
                <?php
              }
              ?>
            </table>
          </div>
        </div>
        
        <div class="postbox">
            <h3 class='hndle'><span><?php _e('Global Marketplace URL Slugs', 'mp') ?></span></h3>
            <div class="inside">
              <span class="description"><?php _e('Customizes the url structure of global category and tag listings.', 'mp') ?></span>
              <table class="form-table">
                <tr valign="top">
                <th scope="row"><?php _e('Marketplace Base', 'mp') ?></th>
                <td>/<input type="text" name="mp[slugs][marketplace]" value="<?php echo esc_attr($settings['slugs']['marketplace']); ?>" size="20" maxlength="50" />/<br />
                </tr>
                <tr valign="top">
                <th scope="row"><?php _e('Product Categories', 'mp') ?></th>
                <td>/<?php echo esc_attr($settings['slugs']['marketplace']); ?>/<input type="text" name="mp[slugs][categories]" value="<?php echo esc_attr($settings['slugs']['categories']); ?>" size="20" maxlength="50" />/</td>
                </tr>
                <tr valign="top">
                <th scope="row"><?php _e('Product Tags', 'mp') ?></th>
                <td>/<?php echo esc_attr($settings['slugs']['marketplace']); ?>/<input type="text" name="mp[slugs][tags]" value="<?php echo esc_attr($settings['slugs']['tags']); ?>" size="20" maxlength="50" />/</td>
                </tr>
              </table>
            </div>
          </div>
        
        <?php
        //for adding additional settings via plugins
        do_action('mp_network_settings', $settings);
        ?>

        <div class="postbox">
          <h3 class='hndle'><span><?php _e('Shortcodes', 'mp') ?></span></h3>
          <div class="inside">
            <p><?php _e('Shortcodes allow you to include dynamic store content in posts and pages on your site. Simply type or paste them into your post or page content where you would like them to appear. Optional attributes can be added in a format like <em>[shortcode attr1="value" attr2="value"]</em>. Note that depending on your preference above, you may only be able to use these on the main blog.', 'mp') ?></p>
            <table class="form-table">
              <tr>
      				<th scope="row"><?php _e('Global Products List', 'mp') ?></th>
      				<td>
                <strong>[mp_list_global_products]</strong> -
                <span class="description"><?php _e('Displays a network-wide list of products according to preference.', 'mp') ?></span>
                <p>
                <strong><?php _e('Optional Attributes:', 'mp') ?></strong>
                <ul class="mp-shortcode-options">
                  <li><?php _e('"paginate" - Whether to paginate the product list. This is useful to only show a subset. Default: 1', 'mp') ?></li>
                  <li><?php _e('"page" - How many products to display in the product list if "paginate" is set to true. Default: 20', 'mp') ?></li>
                  <li><?php _e('"per_page" - How many products to display in the product list if "paginate" is set to 1.', 'mp') ?></li>
                  <li><?php _e('"order_by" - What field to order products by. Can be: date, title, price, sales, rand. Default: date', 'mp') ?></li>
                  <li><?php _e('"order" - Direction to order products by. Can be: DESC, ASC. Default: DESC', 'mp') ?></li>
                  <li><?php _e('"category" - Limits list to a specific product category. Use the category Slug', 'mp') ?></li>
                  <li><?php _e('"tag" - Limits list to a specific product tag. Use the tag Slug', 'mp') ?></li>
                  <li><?php _e('"show_thumbnail" - Whether to show the product thumbnail. Default: 1', 'mp') ?></li>
                  <li><?php _e('"thumbnail_size" - Max thumbnail width/height. Default: 150', 'mp') ?></li>
                  <li><?php _e('"show_price" - Whether to show the product price. Default: 1', 'mp') ?></li>
                  <li><?php _e('"text" - Choose "excerpt", "content", or "none". Default: excerpt', 'mp') ?></li>
                  <li><?php _e('"as_list" - Whether to show as an unordered list. Default: 0', 'mp') ?></li>
                  <li><?php _e('Example:', 'mp') ?> <em>[mp_list_global_products paginate="1" page="0" per_page="10" order_by="price" order="DESC" category="downloads"]</em></li>
                </ul></p>
                <span class="description"><?php _e('You may also use the mp_list_global_products() template function in your theme with the same arguments.', 'mp') ?></span>
              </td>
              </tr>
              <tr>
      				<th scope="row"><?php _e('Global Tag Cloud', 'mp') ?></th>
      				<td>
                <strong>[mp_global_tag_cloud]</strong> -
                <span class="description"><?php _e('Displays global most used product tags in cloud format from network MarketPress stores.', 'mp') ?></span>
                <p>
                <strong><?php _e('Optional Attributes:', 'mp') ?></strong>
                <ul class="mp-shortcode-options">
                  <li><?php _e('"limit" - Maximum amount of tags to display. Default: 45', 'mp') ?></li>
                  <li><?php _e('"seperator" - String to seperate tags by, like a comma, etc.', 'mp') ?></li>
                  <li><?php _e('Example:', 'mp') ?> <em>[mp_global_tag_cloud limit="55" seperator=", "]</em></li>
                </ul></p>
                <span class="description"><?php _e('You may also use the mp_global_tag_cloud() template function in your theme with the same arguments.', 'mp') ?></span>
              </td>
              </tr>
              <tr>
      				<th scope="row"><?php _e('Global Categories List', 'mp') ?></th>
      				<td>
                <strong>[mp_global_categories_list]</strong> -
                <span class="description"><?php _e('Displays a network-wide HTML list of product categories according to preference.', 'mp') ?></span>
                <p>
                <strong><?php _e('Optional Attributes:', 'mp') ?></strong>
                <ul class="mp-shortcode-options">
                  <li><?php _e('"limit" - Text to display for showing all categories. Default: 50', 'mp') ?></li>
                  <li><?php _e('"order_by" - What column to use for ordering the categories. "count" or "name". Default: count', 'mp') ?></li>
                  <li><?php _e('"order" - Direction to order products by. Can be: DESC, ASC. Default: DESC', 'mp') ?></li>
                  <li><?php _e('"show_count" - Whether to show how many posts are in the category. Default: 0', 'mp') ?></li>
                  <li><?php _e('"include" - What to show, "tags", "categories", or "both".', 'mp') ?></li>
                  <li><?php _e('Example:', 'mp') ?> <em>[mp_global_categories_list limit="30" order_by="name" order="ASC" show_count="1" include="both"]</em></li>
                </ul></p>
                <span class="description"><?php _e('You may also use the mp_global_categories_list() template function in your theme with the same arguments.', 'mp') ?></span>
              </td>
              </tr>
            </table>
          </div>
        </div>

        <p class="submit">
          <input type="submit" name="submit_settings" value="<?php _e('Save Changes', 'mp') ?>" />
        </p>
      </form>
    </div>
    </div>
    <?php
  }
  
  
  /*** Product indexing ***/
  
  function index_product($post_id) {
    global $wpdb, $current_site, $mp;

  	$blog_public = get_blog_status( $wpdb->blogid, 'public');
  	$blog_archived = get_blog_status( $wpdb->blogid, 'archived');
  	$blog_mature = get_blog_status( $wpdb->blogid, 'mature');
  	$blog_spam = get_blog_status( $wpdb->blogid, 'spam');
  	$blog_deleted = get_blog_status( $wpdb->blogid, 'deleted');

  	$post = get_post($post_id);

    //skip all cases where we shouldn't index
  	if ( $post->post_type != 'product' )
      return;

    //remove old post if necessary
    if ( $post->post_status != 'publish' || !empty($post->post_password) || empty($post->post_title) || empty($post->post_content) || $blog_archived || $blog_mature || $blog_spam || $blog_deleted ) {
      $this->delete_product($post_id);
      return;
    }

		//update or insert the product
		$global_id = $wpdb->get_var("SELECT id FROM {$wpdb->base_prefix}mp_products WHERE site_id = {$wpdb->siteid} AND blog_id = {$wpdb->blogid} AND post_id = $post_id");
    if ($global_id) {
      $wpdb->update( $wpdb->base_prefix . 'mp_products', array(
                      'blog_public'       => $blog_public,
                      'post_author'       => $post->post_author,
                      'post_title'        => $post->post_title,
                      'post_content'      => strip_shortcodes($post->post_content),
                      'post_permalink'    => get_permalink($post_id),
                      'post_date'         => $post->post_date,
                      'post_date_gmt'     => $post->post_date_gmt,
                      'post_modified'     => $post->post_modified,
                      'post_modified_gmt' => $post->post_modified_gmt,
                      'price'             => $mp->product_price($post_id),
                      'sales_count'       => get_post_meta($post_id, "mp_sales_count", true) ),
                      array( 'id' => $id ) );
      $existed = true;
    } else {
      $wpdb->insert( $wpdb->base_prefix . 'mp_products', array(
                      'site_id'           => $wpdb->siteid,
                      'blog_id'           => $wpdb->blogid,
                      'blog_public'       => $blog_public,
                      'post_id'           => $post_id,
                      'post_author'       => $post->post_author,
                      'post_title'        => $post->post_title,
                      'post_content'      => strip_shortcodes($post->post_content),
                      'post_permalink'    => get_permalink($post_id),
                      'post_date'         => $post->post_date,
                      'post_date_gmt'     => $post->post_date_gmt,
                      'post_modified'     => $post->post_modified,
                      'post_modified_gmt' => $post->post_modified_gmt,
                      'price'             => $mp->product_price($post_id),
                      'sales_count'       => get_post_meta($post_id, "mp_sales_count", true) ) );
      $global_id = $wpdb->insert_id;
      $existed = false;
    }

		//get product terms
		$taxonomies = array( 'product_category', 'product_tag' );
		$new_terms = wp_get_object_terms( array( $post_id ), $taxonomies );
		if ( count($new_terms) ) {
		
      //get existing terms
      foreach ($new_terms as $term)
        $new_slugs[] = $term->slug;
  		$slug_list = implode( "','", $new_slugs );
      $existing_terms = $wpdb->get_results( "SELECT * FROM {$wpdb->base_prefix}mp_terms WHERE slug IN ('$slug_list')" );
      $existing_slugs = array();
      if ( is_array($existing_terms) && count($existing_terms) ) {
        foreach ($existing_terms as $term) {
          $existing_slugs[$term->term_id] = $term->slug;
        }
      }
      
      //if updating
      if ($existed) {
      
        //get existing terms
        $old_terms = $wpdb->get_results( "SELECT * FROM {$wpdb->base_prefix}mp_term_relationships r INNER JOIN {$wpdb->base_prefix}mp_terms t ON r.term_id = t.term_id WHERE r.post_id = $global_id" );
        $old_slugs = array();
        foreach ($old_terms as $term) {
          $old_slugs[$term->term_id] = $term->slug;
        }
        
        //process
        foreach ($new_terms as $term) {
        
          //is it a new term?
          if ( !in_array($term->slug, $old_slugs) ) {
          
            //check if in terms, but not attached
            if ( in_array($term->slug, $existing_slugs) ) {
            
              //add relationship
              $id = array_search($term->slug, $existing_slugs);
              $wpdb->insert( $wpdb->base_prefix . 'mp_term_relationships', array( 'term_id' => $id, 'post_id' => $global_id ) );
              $id_list[] = $id;
            } else { //brand new term
            
              //insert term
              $wpdb->insert( $wpdb->base_prefix . 'mp_terms', array( 'name' => $term->name, 'slug' => $term->slug, 'type' => $term->taxonomy ) );
              $id = $wpdb->insert_id;
              
              //add relationship
              $wpdb->insert( $wpdb->base_prefix . 'mp_term_relationships', array( 'term_id' => $id, 'post_id' => $global_id ) );
              $id_list[] = $id;
            }
            
          } else {
            $id_list[] = array_search($term->slug, $old_slugs);
          }
        }
        
        //remove extra relationships
        $id_whitelist = implode( "','", $id_list );
        $wpdb->query( "DELETE FROM {$wpdb->base_prefix}mp_term_relationships WHERE post_id = $global_id AND term_id NOT IN ('$id_whitelist')" );

      } else { //new post

        //process
        foreach ($new_terms as $term) {

          //check if in terms, but not attached
          if ( in_array($term->slug, $existing_slugs) ) {

            //add relationship
            $id = array_search($term->slug, $existing_slugs);
            $wpdb->insert( $wpdb->base_prefix . 'mp_term_relationships', array( 'term_id' => $id, 'post_id' => $global_id ) );

          } else { //brand new term

            //insert term
            $wpdb->insert( $wpdb->base_prefix . 'mp_terms', array( 'name' => $term->name, 'slug' => $term->slug, 'type' => $term->taxonomy ) );
            $id = $wpdb->insert_id;

            //add relationship
            $wpdb->insert( $wpdb->base_prefix . 'mp_term_relationships', array( 'term_id' => $id, 'post_id' => $global_id ) );

          }

        }

      }
        
    } else { //no terms, so adjust counts of existing

      //delete term relationships
      $wpdb->query( "DELETE FROM {$wpdb->base_prefix}mp_term_relationships WHERE post_id = $global_id" );
    }

  }
  
  function delete_product($post_id) {
    global $wpdb, $current_site, $mp;

    //delete all
    $wpdb->query( "DELETE p.*, r.* FROM {$wpdb->base_prefix}mp_products p LEFT JOIN {$wpdb->base_prefix}mp_term_relationships r ON p.id = r.post_id WHERE p.site_id = {$wpdb->siteid} AND p.blog_id = {$wpdb->blogid} AND p.post_id = $post_id" );
  }
  
  function remove_blog($blog_id) {
    global $wpdb, $current_site, $mp;

    //delete all - note that reinstating the blog will not restore indexed products
    $wpdb->query( "DELETE p.*, r.* FROM {$wpdb->base_prefix}mp_products p LEFT JOIN {$wpdb->base_prefix}mp_term_relationships r ON p.id = r.post_id WHERE p.site_id = {$wpdb->siteid} AND p.blog_id = $blog_id" );
  }
  
  function public_update( $old_value, $value ) {
    global $wpdb;
    $wpdb->update( $wpdb->base_prefix . 'mp_products', array( 'blog_public' => get_blog_status( $wpdb->blogid, 'public' ) ), array( 'site_id' => $wpdb->siteid, 'blog_id' => $wpdb->blogid ) );
  }
  
  //updates the sales count when an order is made on a blog
  function record_sale( $product_id, $variation, $data, $paid ) {
    global $wpdb;
    
    $wpdb->query( "UPDATE {$wpdb->base_prefix}mp_products SET sales_count = (sales_count + {$data['quantity']}) WHERE site_id = {$wpdb->siteid} AND blog_id = {$wpdb->blogid} AND post_id = $product_id" );
  }
  
  
  /*** Shortcodes ***/

  /*
   * Displays a global list of products according to preference.
   *
   * The list of arguments is below:
   *    bool paginate Optional, whether to paginate. Default: true
   *    int page Optional, The page number to display in the product list if $paginate is set to true. Default: 0
   *    int per_page Optional, How many products to display in the product list if $paginate is set to true. Default: 20
   *    string order_by Optional, What field to order products by. Can be: date, title, price, sales, rand. Default: date
   *    string order Optional, Direction to order products by. Can be: DESC, ASC. Default: DESC
   *    string category Optional, limit to a product category, use slug
   *    string tag Optional, limit to a product tag, use slug
   *    bool show_thumbnail Optional, whether to show the product thumbnail. Default: true
   *    int thumbnail_size Optional, max thumbnail width/height. Default: 150
   *    bool show_price Optional, whether to show the product price. Default: true
   *    string text Optional, choose 'excerpt', 'content', or 'none'. Default: excerpt
   *    bool as_list Optional, true to show as unordered list. Default: false
   *
   * @param string|array $attr Optional. Override default arguments.
   */
  function mp_list_global_products_sc($atts) {
    $args = shortcode_atts(array(
      'paginate' => true,
  		'page' => 0,
      'per_page' => 20,
  		'order_by' => 'date',
      'order' => 'DESC',
  		'category' => '',
      'tag' => '',
  		'show_thumbnail' => true,
  		'thumbnail_size' => 50,
  		'show_price' => true,
      'text' => 'excerpt',
  		'as_list' => false
  	), $atts);

    $args['echo'] = false;

    return mp_list_global_products( $args );
  }
  
  /**
   * Display the HTML list of global product categories.
   *
   * The list of arguments is below:
   *     'limit' (string) - Text to display for showing all categories.
   *     'order_by' (string) default is 'count' - What column to use for ordering the
   * categories. 'count' or 'name'.
   *     'order' (string) default is 'DESC' - What direction to order categories.
   *     'show_count' (bool|int) default is 0 - Whether to show how many posts are
   * in the category.
   *     'include' (string) What to show, 'tags', 'categories', or 'both'.
   *
   * @param string|array $attr Optional. Override default arguments.
   */
  function mp_global_categories_list_sc($atts) {
    $args = shortcode_atts($defaults = array(
      'limit' => 50,
  		'order_by' => 'count',
      'order' => 'DESC',
  		'show_count' => 0,
  		'include' => 'categories'
  	), $atts);

    $args['echo'] = false;

    return mp_global_categories_list( $args );
  }
  
  /**
   * Display Global Products tag cloud.
   *
   * @param limit Optional. How many tags to display.
   * @param seperator Optional. String to seperate tags by.
   * @param include Optional. What to show, 'tags', 'categories', or 'both'.
   */
  function mp_global_tag_cloud_sc($atts) {
    extract( shortcode_atts(array(
      'limit' => 45,
  		'seperator' => '',
  		'include' => 'both'
  	), $atts) );

    return mp_global_tag_cloud( false, $limit, $seperator, $include );
  }
  
}
$mp_wpmu = new MarketPress_MS();

function mp_main_site_id() {
  global $current_site;
  if ( defined( 'MP_ROOT_BLOG' ) ) {
    return MP_ROOT_BLOG;
  } else {
    return $current_site->blog_id;
  }
}


/*** Template Tags ***/

/**
 * Display or retrieve the HTML list of global product categories.
 *
 * The list of arguments is below:
 *     'echo' (bool) - Whether to echo or return. default is echo
 *     'limit' (string) - Text to display for showing all categories.
 *     'order_by' (string) default is 'count' - What column to use for ordering the
 * categories. 'count' or 'name'.
 *     'order' (string) default is 'DESC' - What direction to order categories.
 *     'show_count' (bool|int) default is 0 - Whether to show how many posts are
 * in the category.
 *     'include' (string) What to show, 'tags', 'categories', or 'both'.
 *
 * @param string|array $args Optional. Override default arguments.
 */
function mp_global_categories_list( $args = '' ) {
  global $wpdb;
  $settings = get_site_option( 'mp_network_settings' );
  
  $defaults = array(
		'echo' => 1,
    'limit' => 50,
		'order_by' => 'count',
    'order' => 'DESC',
		'show_count' => 0,
		'include' => 'categories'
	);

  $r = wp_parse_args( $args, $defaults );
  extract( $r );

  $order_by = ($order_by == 'name') ? $order_by : 'count';
  $order = ($order == 'ASC') ? $order : 'DESC';
  $limit = intval($limit);

  //include categories as well
  if ($include == 'tags')
    $where = " WHERE t.type = 'product_tag'";
  else if ($include == 'categories')
    $where = " WHERE t.type = 'product_category'";

  $tags = $wpdb->get_results( "SELECT name, slug, type, count(post_id) as count FROM {$wpdb->base_prefix}mp_terms t LEFT JOIN {$wpdb->base_prefix}mp_term_relationships r ON t.term_id = r.term_id$where GROUP BY t.term_id ORDER BY $order_by $order LIMIT $limit", ARRAY_A );

	if ( !$tags )
		return;

  //sort by name
  foreach ($tags as $tag) {
    //skip empty tags
    if ( $tag['count'] == 0 )
      continue;

    if ($tag['type'] == 'product_category')
      $link = get_home_url( mp_main_site_id(), $settings['slugs']['marketplace'] . '/' . $settings['slugs']['categories'] . '/' . $tag['slug'] . '/' );
    else if ($tag['type'] == 'product_tag')
      $link = get_home_url( mp_main_site_id(), $settings['slugs']['marketplace'] . '/' . $settings['slugs']['tags'] . '/' . $tag['slug'] . '/' );

    $list .= '<li><a href="' . $link . '" title="' . sprintf(__( '%d Products', 'mp' ), $tag['count']) . '">' . esc_attr( $tag['name'] );
    if ($show_count)
      $list .= ' - ' . $tag['count'];
    $list .= "</a></li>\n";
  }


	if ( $echo )
		echo '<ul id="mp_category_list">' . $list . '</ul>';

	return '<ul id="mp_category_list">' . $list . '</ul>';
}

/**
 * Display Global Products tag cloud.
 *
 * @param bool $echo Optional. Whether or not to echo.
 * @param int $limit Optional. How many tags to display.
 * @param string $seperator Optional. String to seperate tags by.
 * @param string $include Optional. What to show, 'tags', 'categories', or 'both'.
 */
function mp_global_tag_cloud( $echo = true, $limit = 45, $seperator = ' ', $include = 'both' ) {
  global $wpdb;
  $settings = get_site_option( 'mp_network_settings' );

  //include categories as well
  if ($include == 'tags')
    $where = " WHERE t.type = 'product_tag'";
  else if ($include == 'categories')
    $where = " WHERE t.type = 'product_category'";
  
  $tags = $wpdb->get_results( "SELECT name, slug, type, count(post_id) as count FROM {$wpdb->base_prefix}mp_terms t LEFT JOIN {$wpdb->base_prefix}mp_term_relationships r ON t.term_id = r.term_id$where GROUP BY t.term_id ORDER BY count DESC LIMIT $limit", ARRAY_A );

	if ( !$tags )
		return;

  //sort by name
  foreach ($tags as $tag) {
    //skip empty tags
    if ( $tag['count'] == 0 )
      continue;
      
    if ($tag['type'] == 'product_category')
      $tag['link'] = get_home_url( mp_main_site_id(), $settings['slugs']['marketplace'] . '/' . $settings['slugs']['categories'] . '/' . $tag['slug'] . '/' );
    else if ($tag['type'] == 'product_tag')
      $tag['link'] = get_home_url( mp_main_site_id(), $settings['slugs']['marketplace'] . '/' . $settings['slugs']['tags'] . '/' . $tag['slug'] . '/' );
      
    $sorted_tags[$tag['name']] = $tag;
  }
  
  ksort( $sorted_tags );

  //remove keys
  $tags = array();
  foreach( $sorted_tags as $tag )
    $tags[] = $tag;

  $counts = array();
	$real_counts = array(); // For the alt tag
	foreach ( (array) $tags as $key => $tag ) {
		$real_counts[ $key ] = $tag['count'];
		$counts[ $key ] = $tag['count'];
	}

	$min_count = min( $counts );
	$spread = max( $counts ) - $min_count;
	if ( $spread <= 0 )
		$spread = 1;
	$font_spread = 22 - 8;
	if ( $font_spread < 0 )
		$font_spread = 1;
	$font_step = $font_spread / $spread;

	$a = array();

	foreach ( $tags as $key => $tag ) {
		$count = $counts[ $key ];
		$real_count = $real_counts[ $key ];
		$tag_link = '#' != $tag['link'] ? esc_url( $tag['link'] ) : '#';
		$tag_id = isset($tags[ $key ]['id']) ? $tags[ $key ]['id'] : $key;
		$tag_name = $tags[ $key ]['name'];
		$a[] = "<a href='$tag_link' class='tag-link-$tag_id' title='" . esc_attr( $real_count ) . ' ' . __( 'Products', 'mp' ) . "' style='font-size: " .
			( 8 + ( ( $count - $min_count ) * $font_step ) )
			. "pt;'>$tag_name</a>";
	}

	$return = join( $seperator, $a );

	if ( $echo )
		echo '<div id="mp_tag_cloud">' . $return . '</div>';

	return '<div id="mp_tag_cloud">' . $return . '</div>';
}

/*
 * Displays a global list of products according to preference.
 *
 * The list of arguments is below:
 *    bool echo Optional, whether to echo or return
 *    bool paginate Optional, whether to paginate. Default: true
 *    int page Optional, The page number to display in the product list if $paginate is set to true. Default: 0
 *    int per_page Optional, How many products to display in the product list if $paginate is set to true. Default: 20
 *    string order_by Optional, What field to order products by. Can be: date, title, price, sales, rand. Default: date
 *    string order Optional, Direction to order products by. Can be: DESC, ASC. Default: DESC
 *    string category Optional, limit to a product category, use slug
 *    string tag Optional, limit to a product tag, use slug
 *    bool show_thumbnail Optional, whether to show the product thumbnail. Default: true
 *    int thumbnail_size Optional, max thumbnail width/height. Default: 150
 *    bool show_price Optional, whether to show the product price. Default: true
 *    string text Optional, choose 'excerpt', 'content', or 'none'. Default: excerpt
 *    bool as_list Optional, true to show as unordered list. Default: false
 *
 * @param string|array $args Optional. Override default arguments.
 */
function mp_list_global_products( $args = '' ) {
  global $wpdb, $mp;

  $defaults = array(
		'echo' => true,
    'paginate' => true,
		'page' => 0,
    'per_page' => 20,
		'order_by' => 'date',
    'order' => 'DESC',
		'category' => '',
    'tag' => '',
		'show_thumbnail' => true,
		'thumbnail_size' => 150,
		'context' => 'list',
		'show_price' => true,
		'text' => 'excerpt',
		'as_list' => false
	);

  $r = wp_parse_args( $args, $defaults );
  extract( $r );

  //setup taxonomy if applicable
  if ($category) {
    $category = $wpdb->escape( sanitize_title( $category ) );
    $query = "SELECT blog_id, p.post_id, post_permalink, post_title, post_content FROM {$wpdb->base_prefix}mp_products p INNER JOIN {$wpdb->base_prefix}mp_term_relationships r ON p.id = r.post_id INNER JOIN {$wpdb->base_prefix}mp_terms t ON r.term_id = t.term_id WHERE p.blog_public = 1 AND t.type = 'product_category' AND t.slug = '$category'";
  } else if ($tag) {
    $tag = $wpdb->escape( sanitize_title( $tag ) );
    $query = "SELECT blog_id, p.post_id, post_permalink, post_title, post_content FROM {$wpdb->base_prefix}mp_products p INNER JOIN {$wpdb->base_prefix}mp_term_relationships r ON p.id = r.post_id INNER JOIN {$wpdb->base_prefix}mp_terms t ON r.term_id = t.term_id WHERE p.blog_public = 1 AND t.type = 'product_tag' AND t.slug = '$tag'";
  } else {
    $query = "SELECT blog_id, p.post_id, post_permalink, post_title, post_content FROM {$wpdb->base_prefix}mp_products p WHERE p.blog_public = 1";
  }

  //get order by
  switch ($order_by) {

    case 'title':
      $query .= " ORDER BY p.post_title";
      break;

    case 'price':
      $query .= " ORDER BY p.price";
      break;

    case 'sales':
      $query .= " ORDER BY p.sales_count";
      break;

    case 'rand':
      $query .= " ORDER BY RAND()";
      break;

    case 'date':
    default:
      $query .= " ORDER BY p.post_date";
      break;
  }

  //get order direction
  if ($order == 'ASC') {
    $query .= " ASC";
  } else {
    $query .= " DESC";
  }

  //get page details
  if ($paginate)
    $query .= " LIMIT " . intval($page) . ", " . intval($per_page);

  //The Query
  $results = $wpdb->get_results( $query );

  if ($as_list)
    $content = '<ul id="mp_product_list">';
  else
    $content = '<div id="mp_product_list">';

  if ($results) {
    foreach ($results as $product) {

      if ($as_list)
        $content .= '<li class="product type-product mp_product">';
      else
        $content .= '<div class="product type-product mp_product">';

      global $current_blog;
      switch_to_blog($product->blog_id);

      //grab permalink
      $permalink = get_permalink( $product->post_id );

      //grab thumbnail
      if ($show_thumbnail)
        $thumbnail = mp_product_image( false, $context, $product->post_id, $thumbnail_size );
        
      //price
      if ($show_price) {
        if ($context == 'widget')
          $price = mp_product_price(false, $product->post_id, ''); //no price label in widgets
        else
          $price = mp_product_price(false, $product->post_id);
      }
      
      restore_current_blog();

      $content .= '<h3 class="mp_product_name"><a href="' . $permalink . '">' . esc_attr($product->post_title) . '</a></h3>';
      $content .= '<div class="mp_product_content">';

      $content .= $thumbnail;

      //show content
      if ($text == 'excerpt') {
        $excerpt = str_replace(']]>', ']]&gt;', $product->post_content);
    		$excerpt = strip_tags($excerpt);
    		$excerpt_length = apply_filters('excerpt_length', 55);
    		$words = preg_split("/[\n\r\t ]+/", $excerpt, $excerpt_length + 1, PREG_SPLIT_NO_EMPTY);
    		if ( count($words) > $excerpt_length ) {
    			array_pop($words);
    			$excerpt = implode(' ', $words);
    		} else {
    			$excerpt = implode(' ', $words);
    		}
        $content .= $excerpt;
      } else if ($text == 'content') {
        $content .= $product->post_content;
      }

      $content .= '</div>';

      $content .= '<div class="mp_product_meta">';

      //price
      $content .= $price;

      //button
      $content .= '<a class="mp_link_buynow" href="' . $permalink . '">' .  __('Buy Now &raquo;', 'mp') . '</a>';
      $content .= '</div>';

      if ($as_list)
        $content .= '</li>';
      else
        $content .= '</div>';

    }
  } else {
    if ($as_list)
      $content .= '<li>' . __('No Products', 'mp') . '</li>';
    else
      $content .= __('No Products', 'mp');
  }

  if ($as_list)
    $content .= '</ul>';
  else
    $content .= '</div>';

  if ($echo)
    echo $content;
  else
    return $content;
}


/*** Widgets ***/

//Product listing widget
class MarketPress_Global_Product_List extends WP_Widget {

	function MarketPress_Global_Product_List() {
		$widget_ops = array('classname' => 'mp_global_product_list_widget', 'description' => __('Shows a customizable global list of products from network MarketPress stores.', 'mp') );
		$this->WP_Widget('mp_global_product_list_widget', __('Global Product List', 'mp'), $widget_ops);
	}

	function widget($args, $instance) {
    global $mp;

		extract( $args );

		echo $before_widget;
	  $title = $instance['title'];
		if ( !empty( $title ) ) { echo $before_title . apply_filters('widget_title', $title) . $after_title; };

    if ( !empty($instance['custom_text']) )
      echo '<div id="custom_text">' . $instance['custom_text'] . '</div>';

    $instance['as_list'] = true;
    $instance['context'] = 'widget';
    
    //list global products
    mp_list_global_products( $instance );

    echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = wp_filter_nohtml_kses( $new_instance['title'] );
		$instance['custom_text'] = wp_filter_kses( $new_instance['custom_text'] );

		$instance['per_page'] = intval($new_instance['per_page']);
		$instance['order_by'] = $new_instance['order_by'];
		$instance['order'] = $new_instance['order'];
    $instance['category'] = ($new_instance['category']) ? sanitize_title($new_instance['category']) : '';
    $instance['tag'] = ($new_instance['tag']) ? sanitize_title($new_instance['tag']) : '';

    $instance['show_thumbnail'] = !empty($new_instance['show_thumbnail']) ? 1 : 0;
    $instance['thumbnail_size'] = !empty($new_instance['thumbnail_size']) ? intval($new_instance['thumbnail_size']) : 50;
    $instance['text'] = $new_instance['text'];
    $instance['show_price'] = !empty($new_instance['show_price']) ? 1 : 0;

		return $instance;
	}

	function form( $instance ) {
    $instance = wp_parse_args( (array) $instance, array( 'title' => __('Global Products', 'mp'), 'custom_text' => '', 'per_page' => 10, 'order_by' => 'date', 'order' => 'DESC', 'show_thumbnail' => 1, 'size' => 50, 'text' => 'none' ) );
    extract( $instance );
  ?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'mp') ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></label></p>
		<p><label for="<?php echo $this->get_field_id('custom_text'); ?>"><?php _e('Custom Text:', 'mp') ?><br />
    <textarea class="widefat" id="<?php echo $this->get_field_id('custom_text'); ?>" name="<?php echo $this->get_field_name('custom_text'); ?>"><?php echo esc_attr($custom_text); ?></textarea></label>
    </p>

    <h3><?php _e('List Settings', 'mp'); ?></h3>
    <p>
    <label for="<?php echo $this->get_field_id('per_page'); ?>"><?php _e('Number of Products:', 'mp') ?> <input id="<?php echo $this->get_field_id('per_page'); ?>" name="<?php echo $this->get_field_name('per_page'); ?>" type="text" size="3" value="<?php echo $per_page; ?>" /></label><br />
    </p>
    <p>
    <label for="<?php echo $this->get_field_id('order_by'); ?>"><?php _e('Order Products By:', 'mp') ?><br />
    <select id="<?php echo $this->get_field_id('order_by'); ?>" name="<?php echo $this->get_field_name('order_by'); ?>">
      <option value="date"<?php selected($order_by, 'date') ?>><?php _e('Publish Date', 'mp') ?></option>
      <option value="title"<?php selected($order_by, 'title') ?>><?php _e('Product Name', 'mp') ?></option>
      <option value="sales"<?php selected($order_by, 'sales') ?>><?php _e('Number of Sales', 'mp') ?></option>
      <option value="price"<?php selected($order_by, 'price') ?>><?php _e('Product Price', 'mp') ?></option>
      <option value="rand"<?php selected($order_by, 'rand') ?>><?php _e('Random', 'mp') ?></option>
    </select><br />
    <label><input value="DESC" name="<?php echo $this->get_field_name('order'); ?>" type="radio"<?php checked($order, 'DESC') ?> /> <?php _e('Descending', 'mp') ?></label>
    <label><input value="ASC" name="<?php echo $this->get_field_name('order'); ?>" type="radio"<?php checked($order, 'ASC') ?> /> <?php _e('Ascending', 'mp') ?></label>
    </p>
    <p>
    <label for="<?php echo $this->get_field_id('category'); ?>"><?php _e('Limit To Product Category:', 'mp') ?></label><br />
    <input id="<?php echo $this->get_field_id('category'); ?>" name="<?php echo $this->get_field_name('category'); ?>" type="text" value="<?php echo $category; ?>" title="<?php _e('Enter the Slug', 'mp'); ?>" class="widefat" />
    </p>
    <p>
    <label for="<?php echo $this->get_field_id('tag'); ?>"><?php _e('Limit To Product Tag:', 'mp') ?></label><br />
    <input id="<?php echo $this->get_field_id('tag'); ?>" name="<?php echo $this->get_field_name('tag'); ?>" type="text" value="<?php echo $tag; ?>" title="<?php _e('Enter the Slug', 'mp'); ?>" class="widefat" />
    </p>

    <h3><?php _e('Display Settings', 'mp'); ?></h3>
    <p><input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('show_thumbnail'); ?>" name="<?php echo $this->get_field_name('show_thumbnail'); ?>"<?php checked( $show_thumbnail ); ?> />
		<label for="<?php echo $this->get_field_id('show_thumbnail'); ?>"><?php _e( 'Show Thumbnail', 'mp' ); ?></label><br />
		<label for="<?php echo $this->get_field_id('thumbnail_size'); ?>"><?php _e('Thumbnail Size:', 'mp') ?> <input id="<?php echo $this->get_field_id('thumbnail_size'); ?>" name="<?php echo $this->get_field_name('thumbnail_size'); ?>" type="text" size="3" value="<?php echo $thumbnail_size; ?>" /></label>
    </p>

    <p>
    <label for="<?php echo $this->get_field_id('text'); ?>"><?php _e('Content To Show:', 'mp') ?></label><br />
    <select id="<?php echo $this->get_field_id('text'); ?>" name="<?php echo $this->get_field_name('text'); ?>">
      <option value="none"<?php selected($text, 'none') ?>><?php _e('None', 'mp') ?></option>
      <option value="excerpt"<?php selected($text, 'excerpt') ?>><?php _e('Excerpt', 'mp') ?></option>
      <option value="content"<?php selected($text, 'content') ?>><?php _e('Content', 'mp') ?></option>
    </select>
    </p>

    <p>
    <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('show_price'); ?>" name="<?php echo $this->get_field_name('show_price'); ?>"<?php checked( $show_price ); ?> />
		<label for="<?php echo $this->get_field_id('show_price'); ?>"><?php _e( 'Show Price', 'mp' ); ?></label>
    </p>

	<?php
	}
}

//Product tags cloud
class MarketPress_Global_Tag_Cloud_Widget extends WP_Widget {

	function MarketPress_Global_Tag_Cloud_Widget() {
		$widget_ops = array( 'classname' => 'mp_global_tag_cloud_widget', 'description' => __( "Displays global most used product tags in cloud format from network MarketPress stores.") );
		$this->WP_Widget('mp_global_tag_cloud_widget', __('Global Product Tag Cloud', 'mp'), $widget_ops);
	}

	function widget( $args, $instance ) {
		extract($args);

		if ( !empty($instance['title']) ) {
			$title = $instance['title'];
		} else {
			$title = __( 'Global Product Tags', 'mp' );
		}
		$title = apply_filters('widget_title', $title, $instance, $this->id_base);

		echo $before_widget;
		if ( $title )
			echo $before_title . $title . $after_title;

    mp_global_tag_cloud( true, 45, ' ', $instance['taxonomy'] );

		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance['title'] = strip_tags(stripslashes($new_instance['title']));
		$instance['taxonomy'] = stripslashes($new_instance['taxonomy']);
		return $instance;
	}

	function form( $instance ) {
    $instance = wp_parse_args( (array) $instance, array( 'title' => __('Global Product Tags', 'mp'), 'taxonomy' => 'tags' ) );
?>
	<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:') ?></label>
	<input type="text" class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php if (isset ( $instance['title'])) {echo esc_attr( $instance['title'] );} ?>" /></p>
  <p><label for="<?php echo $this->get_field_id('taxonomy'); ?>"><?php _e('Show:','mp') ?></label>
  <select class="widefat" id="<?php echo $this->get_field_id('taxonomy'); ?>" name="<?php echo $this->get_field_name('taxonomy'); ?>">
  <option value="tags" <?php selected($instance['taxonomy'], 'tags') ?>><?php _e('Product Tags','mp'); ?></option>
  <option value="categories" <?php selected($instance['taxonomy'], 'categories') ?>><?php _e('Product Categories','mp'); ?></option>
  <option value="both" <?php selected($instance['taxonomy'], 'both') ?>><?php _e('Both','mp'); ?></option>
	</select></p>
  <?php
	}
}

//Product categories list
class MarketPress_Global_Category_List_Widget extends WP_Widget {

	function MarketPress_Global_Category_List_Widget() {
		$widget_ops = array( 'classname' => 'mp_global_category_list_widget', 'description' => __( "Displays a network-wide HTML list of product categories from network MarketPress stores.") );
		$this->WP_Widget('mp_global_category_list_widget', __('Global Product Category List', 'mp'), $widget_ops);
	}

	function widget( $args, $instance ) {
		extract($args);

		if ( !empty($instance['title']) ) {
			$title = $instance['title'];
		} else {
			$title = __( 'Global Product Categories', 'mp' );
		}
		$title = apply_filters('widget_title', $title, $instance, $this->id_base);

		echo $before_widget;
		if ( $title )
			echo $before_title . $title . $after_title;

    mp_global_categories_list( $instance );

		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance['title'] = strip_tags(stripslashes($new_instance['title']));
		$instance['include'] = stripslashes($new_instance['include']);
		$instance['limit'] = intval($new_instance['limit']);
		$instance['order_by'] = $new_instance['order_by'];
		$instance['order'] = $new_instance['order'];
    $instance['show_count'] = !empty($new_instance['show_count']) ? 1 : 0;
		return $instance;
	}

	function form( $instance ) {
    $instance = wp_parse_args( (array) $instance, array( 'title' => __('Global Product Categories', 'mp'), 'order_by' => 'name', 'order' => 'ASC', 'limit' => 50, 'show_count' => 0, 'include' => 'categories' ) );
    extract( $instance );
?>
	<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:') ?></label>
	<input type="text" class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php if (isset ( $title )) {echo esc_attr( $title );} ?>" /></p>

  <p>
  <label for="<?php echo $this->get_field_id('order_by'); ?>"><?php _e('Order Categories By:', 'mp') ?><br />
  <select id="<?php echo $this->get_field_id('order_by'); ?>" name="<?php echo $this->get_field_name('order_by'); ?>">
    <option value="name"<?php selected($order_by, 'name') ?>><?php _e('Name', 'mp') ?></option>
    <option value="count"<?php selected($order_by, 'count') ?>><?php _e('Product Count', 'mp') ?></option>
  </select><br />
  <label><input value="DESC" name="<?php echo $this->get_field_name('order'); ?>" type="radio"<?php checked($order, 'DESC') ?> /> <?php _e('Descending', 'mp') ?></label>
  <label><input value="ASC" name="<?php echo $this->get_field_name('order'); ?>" type="radio"<?php checked($order, 'ASC') ?> /> <?php _e('Ascending', 'mp') ?></label>
  </p>

  <p>
  <label for="<?php echo $this->get_field_id('limit'); ?>"><?php _e('Number of Categories:', 'mp') ?>
  <input id="<?php echo $this->get_field_id('limit'); ?>" name="<?php echo $this->get_field_name('limit'); ?>" type="text" size="3" value="<?php echo intval($limit); ?>" /></label><br />
  </p>

  <p>
  <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('show_count'); ?>" name="<?php echo $this->get_field_name('show_count'); ?>"<?php checked( $show_count ); ?> />
	<label for="<?php echo $this->get_field_id('show_count'); ?>"><?php _e( 'Show product counts' ); ?></label>
  </p>
  
  <p><label for="<?php echo $this->get_field_id('include'); ?>"><?php _e('Show:','mp') ?></label>
  <select class="widefat" id="<?php echo $this->get_field_id('include'); ?>" name="<?php echo $this->get_field_name('include'); ?>">
  <option value="tags" <?php selected($include, 'tags') ?>><?php _e('Product Tags','mp'); ?></option>
  <option value="categories" <?php selected($include, 'categories') ?>><?php _e('Product Categories','mp'); ?></option>
  <option value="both" <?php selected($include, 'both') ?>><?php _e('Both','mp'); ?></option>
	</select></p>
  <?php
	}
}
?>