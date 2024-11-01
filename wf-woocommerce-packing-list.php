<?php
/*
  Plugin Name: WooCommerce Shipping Label (BASIC)
  Plugin URI: https://www.webtoffee.com/product/woocommerce-pdf-invoices-packing-slips/
  Description: Print PDF Shipping Label.
  Version: 2.3.9
  Author: WebToffee
  Author URI: https://www.webtoffee.com/
  WC tested up to: 4.9
  Requires PHP: 5.6
  License: GPLv3
  License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

// to check wether accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// for Required functions
if (!function_exists('wf_is_woocommerce_active')) {
    require_once ('wf-includes/wf-functions.php');

}

// to check woocommerce is active
if (!(wf_is_woocommerce_active())) {
    return;
}
if(!defined('WT_PKL_SH_VERSION')) //check plugin file already included
{
    define ( 'WT_PKL_SH_PLUGIN_DEVELOPMENT_MODE', false );
    define ( 'WT_PKL_SH_PLUGIN_BASENAME', plugin_basename(__FILE__) );
    define ( 'WT_PKL_SH_PLUGIN_PATH', plugin_dir_path(__FILE__) );
    define ( 'WT_PKL_SH_PLUGIN_URL', plugin_dir_url(__FILE__));
    define ( 'WT_PKL_SH_PLUGIN_FILENAME',__FILE__);

    /**
     * Currently plugin version.
     */
    define( 'WT_PKL_SH_VERSION', '2.3.9' );
}

function wf_pklist_label_basic_activation_check() {
    //check if basic version is there
    if (is_plugin_active('print-invoices-packing-slip-labels-for-woocommerce/wf-woocommerce-packing-list.php')) {
        deactivate_plugins(basename(__FILE__));
        wp_die(__("Oops! Print Invoices Packing Slip Labels for Woocommerce plugin is a part of this plugin. If you want deactive this and active that plugin", "wf-woocommerce-packing-list"), "", array('back_link' => 1));
    }

    do_action("wt_pkl_sh_activate");
    set_transient('wf_print_invoice_welcome_screen_activation_redirect', true, 30);
}

register_activation_hook(__FILE__, 'wf_pklist_label_basic_activation_check');


function wf_pklist_label_basic_deactivation_check()
{
    do_action("wt_pkl_sh_deactivate");
}
register_deactivation_hook(__FILE__, 'wf_pklist_label_basic_deactivation_check');


// class for Invoice and Packing List Printing
class Wf_WooCommerce_Packing_List_Woo {

    public $wf_package_type_options;
    public $create_package_documents;
    public $wf_package_type;
    public $weight_unit;
    public $dimension_unit;
    public $wf_enable_contact_number;
    public $woocommerce_wf_packinglist_enable_cyrillic;
        
    // initializing the class
    function __construct() {

        add_action('admin_init', array($this, 'wf_print_invoice_welcome'));
        add_action('admin_menu', array($this, 'wf_print_invoice_welcome_screen'));
        add_action('admin_head', array($this, 'wf_print_invoice_welcome_screen_remove_menus'));

        add_action('init', array($this, 'init'));
        $this->wf_pklist_init_fields(); //function to init values of the fields
        add_action('woocommerce_admin_order_actions_end', array($this, 'wf_packinglist_alter_order_actions')); //to add print option at the end of each orders in orders page
        add_action('admin_init', array($this, 'wf_packinglist_print_window')); //to print the invoice and packinglist
        add_action('admin_menu', array($this, 'wf_packinglist_admin_menu')); //to add shipment label settings menu to main menu of woocommerce
        add_action('add_meta_boxes', array($this, 'wf_packinglist_add_box')); //to add meta box in every single detailed order page
        add_action('admin_print_scripts-edit.php', array($this, 'wf_packinglist_scripts')); //to load the js for label for client
        add_action('admin_print_scripts-post.php', array($this, 'wf_packinglist_scripts')); //to load the js for label for client
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'wf_packinglist_action_links')); //to add settings, doc, etc options to plugins base
        add_filter('woocommerce_subscriptions_renewal_order_meta_query', array($this, 'wf_packinglist_remove_subscription_renewal_order_meta'), 10, 4);
        add_action('admin_enqueue_scripts', array($this, 'wf_packinglist_admin_scripts')); //to load the js for admin
        add_action('admin_print_styles', array($this, 'admin_scripts'));
        add_action('init', array($this, 'load_plugin_textdomain'));


        add_filter('wt_'.WF_SHLBL_PLUGIN_NAME.'_alter_role_access', array($this, 'alter_user_roles'));
    }

    /**
    * @since 2.3.3
    * Adding store manager role
    */
    public function alter_user_roles($roles)
    {
      $roles[]='manage_woocommerce';
      return $roles;
    }

    /**
    * @since 2.3.3
    * Introduced security helper class
    */
    public function init() {
        if (!class_exists('wf_order')) {
            include_once('class-wf-legacy.php');
        }
        include_once plugin_dir_path(__FILE__)."wf-includes/class-wt-security-helper.php";
        include_once plugin_dir_path(__FILE__)."wf-includes/class-shipping-labels-for-woo-review_request.php";
        include_once plugin_dir_path(__FILE__)."wf-includes/class-shipping-labels-for-woo-discontinued.php";
    }

    public function wf_print_invoice_welcome() {
        if (!get_transient('wf_print_invoice_welcome_screen_activation_redirect')) {
            return;
        }
        delete_transient('wf_print_invoice_welcome_screen_activation_redirect');
        wp_safe_redirect(add_query_arg(array('page' => 'Invoice-Welcome'), admin_url('index.php')));
    }

    public function wf_print_invoice_welcome_screen() {
        add_dashboard_page('Welcome To Invoice', 'Welcome To Invoice', 'read', 'Invoice-Welcome', array($this, 'wf_print_invoice_screen_content'));
    }

    public function wf_print_invoice_screen_content() {
        include 'includes/wf_print_invoice_welcome.php';
    }

    public function wf_print_invoice_welcome_screen_remove_menus() {
        remove_submenu_page('index.php', 'Invoice-Welcome');
    }

    public function load_plugin_textdomain() {
        load_plugin_textdomain('wf-woocommerce-packing-list', false, dirname(plugin_basename(__FILE__)) . '/lang');
    }

    //function for initializing fields included from packaging type
    public function wf_pklist_init_fields() {
        $this->wf_package_type_options = array(
            'single_packing' => __('Single Package Per Order', 'wf-woocommerce-packing-list')
        );
        $this->create_package_documents = array(
            'print_shipment_label',
            'download_shipment_label',
        );
        $this->wf_package_type = get_option('woocommerce_wf_packinglist_package_type') != '' ? get_option('woocommerce_wf_packinglist_package_type') : 'single_packing';
        $this->weight_unit = get_option('woocommerce_weight_unit');
        $this->dimension_unit = get_option('woocommerce_dimension_unit');
        $this->wf_enable_contact_number = get_option('woocommerce_wf_packinglist_contact_number') != '' ? get_option('woocommerce_wf_packinglist_contact_number') : 'Yes';
        $this->woocommerce_wf_packinglist_enable_cyrillic = get_option('woocommerce_wf_packinglist_enable_cyrillic') != '' ? get_option('woocommerce_wf_packinglist_enable_cyrillic') : 'Yes';
    }

    // function to add print invoice packinglist button in admin orders page

    function wf_packinglist_alter_order_actions($order) {
        $order = ( WC()->version < '2.7.0' ) ? new WC_Order($order) : new wf_order($order);
        ?>
        <a disabled class="button tips wf-packing-list-link" target="_blank" data-tip="<?php esc_attr_e('Print Shipping Label', 'wf-woocommerce-packing-list'); ?>" href="<?php echo wp_nonce_url(admin_url('?print_packinglist=true&post=' . $order->id . '&type=print_shipment_label'), 'print-packinglist'); ?>"><img style="padding:5px;" src="<?php echo esc_url($this->wf_packinglist_get_plugin_url() . '/assets/images/Label-print-icon.png'); ?>" alt="<?php esc_attr_e('Print Shipping Label', 'wf-woocommerce-packing-list'); ?>" width="14"></a>

        <a disabled class="button tips " target="_blank" data-tip="<?php esc_attr_e('Download Shipping Label', 'wf-woocommerce-packing-list'); ?>" href="<?php echo wp_nonce_url(admin_url('?print_packinglist=true&post=' . $order->id . '&type=download_shipment_label'), 'print-packinglist'); ?>"><img style="padding:5px;"  src="<?php echo esc_url($this->wf_packinglist_get_plugin_url() . '/assets/images/pdf-icon.png'); ?>" alt="<?php esc_attr_e('Print Shipping Label', 'wf-woocommerce-packing-list'); ?>" width="14"></a><?php
       }

       // function to add settings link to invoice packing-list-print plugin view
       function wf_packinglist_action_links($links) {
           $plugin_links = array(
                '<a href="' . admin_url('admin.php?page=wf_woocommerce_packing_list') . '">' . __('Settings', 'wf-woocommerce-packing-list') . '</a>',
                '<a href="https://www.webtoffee.com/product/woocommerce-pdf-invoices-packing-slips/" target="_blank">' . __('Premium upgrade', 'wf-woocommerce-packing-list') . '</a>',
                '<a href="https://wordpress.org/support/plugin/shipping-labels-for-woo" target="_blank">' . __('Support', 'wf-woocommerce-packing-list') . '</a>',
                '<a href="https://wordpress.org/support/plugin/shipping-labels-for-woo/reviews/?rate=5#new-post" target="_blank">' . __('Review', 'wf-woocommerce-packing-list') . '</a>',
                
            );
           return array_merge($plugin_links, $links);
       }

       // function to get plugin url
       function wf_packinglist_get_plugin_url() {
           return untrailingslashit(plugins_url('/', __FILE__));
       }

       // functio to get pulgin directory
       function wf_packinglist_get_plugin_path() {
           return untrailingslashit(plugin_dir_path(__FILE__));
       }

       // function to start invoice and packinglist printing window
       function wf_packinglist_print_window() {
           if (isset($_GET['print_packinglist'])) 
           {
               $client = false;
               
               if(!Wt_Pkl_Sh::check_write_access(WF_SHLBL_PLUGIN_NAME, 'print-packinglist'))
               {
                  die(_e('You are not allowed to view this page.', 'wf-woocommerce-packing-list'));
               }

               remove_action('wp_footer', 'wp_admin_bar_render', 1000);
               // to get the orders number
               $orders = (isset($_GET['post']) ? explode(',', sanitize_text_field($_GET['post'])) : array());
               $orders = Wt_Pkl_Sh::sanitize_item($orders, 'int_arr');
               $action = Wt_Pkl_Sh::sanitize_item($_GET['type']);

               $number_of_orders = count($orders);
               $order_loop = 0;
               $is_shipping_from_address_available = 0;
               // function to check that the shipping from address is added or not
               if ($this->wf_packinglist_check_from_address()) {
                   $is_shipping_from_address_available = 1;
               }
               if ($action == 'print_shipment_label') {
                   // building shipment label headers
                   ob_start();
                   $content = '';
                   require_once $this->wf_packinglist_template('dir', 'wf-4-6-template-header-label.php') . 'wf-4-6-template-header-label.php';
                   $content.= ob_get_clean();
                   // function to check that the shipping from address is added or not
                   if ($is_shipping_from_address_available == 1) {
                       $content.= __("Please fill the shipping from address in plugin settings.", 'wf-woocommerce-packing-list');
                   } else {
                       // building shipment label body
                       $content1 = '';
                       foreach ($orders as $order_id) {
                           $order_loop++;
                           $order = new WC_Order($order_id);
                           $order_additional_information = array(
                               'order' => $order
                           );
                           $order_additional_information = apply_filters('wf_pklist_label_add_additional_information', $order_additional_information);
                           ob_start();
                           $create_order_packages;
                           if (in_array($action, $this->create_package_documents)) {
                               $create_order_packages = $this->wf_pklist_create_order_package($order);
                           }
                           $order_package_loop = 0;
                           $number_of_order_package = count($create_order_packages);
                           foreach ($create_order_packages as $order_package_id => $order_package) {
                               $order_package_loop++;
                               ob_start();
                               include $this->wf_packinglist_template('dir', 'wf-4-6-template-body-label.php') . 'wf-4-6-template-body-label.php';
                               $content1.= ob_get_clean();
                               if ($number_of_order_package > 1 && $order_package_loop < $number_of_order_package) {
                                   $content1.= "<p class=\"pagebreak\"></p><br/>";
                               } else {
                                   $content1.= "<p class=\"no-page-break\"></p>";
                               }
                           }
                           if ($number_of_orders > 1 && $order_loop < $number_of_orders) {
                               $content1.= "<p class=\"pagebreak\"></p><br/>";
                           } else {
                               $content1.= "<p class=\"no-page-break\"></p>";
                           }
                       }
                       $content.= $content1;
                   }
                   // building shipment label footer
                   ob_start();
                   include $this->wf_packinglist_template('dir', 'wf-4-6-template-footer-label.php') . 'wf-4-6-template-footer-label.php';
                   $content.= ob_get_clean();
                   // outputing content to client window
                   echo $content;
                   exit;
               } else if ($action == 'download_shipment_label') {
                   include $this->wf_packinglist_template('dir', 'wf-4-6-pdf-template.php') . 'wf-4-6-pdf-template.php';
                   $pdf = new PDF4x6();
                   foreach ($orders as $order_id) {
                       $order_loop++;
                       $order = new WC_Order($order_id);
                       $order_additional_information = array(
                           'order' => $order
                       );
                       $order_additional_information = apply_filters('wf_pklist_label_add_additional_information', $order_additional_information);
                       $create_order_packages;
                       if (in_array($action, $this->create_package_documents)) {
                           $create_order_packages = $this->wf_pklist_create_order_package($order);
                       }
                       $order_package_loop = 0;
                       $number_of_order_package = count($create_order_packages);
                       foreach ($create_order_packages as $order_package_id => $order_package) {
                           $order_package_loop++;
                           $pdf->init($this->wf_shipment_label_get_label_size());
                           if ($is_shipping_from_address_available == 1) {
                               _e("Please fill the shipping from address in the plugin settings.", "wf-woocommerce-packing-list");
                               exit;
                           }
                           if ($this->wf_packinglist_get_logo() != '') {
                               $dimensions = $this->wf_pklist_get_new_dimensions($this->wf_packinglist_get_logo(), 50, 200);
                               $pdf->addImage($this->wf_packinglist_get_logo(), $dimensions);
                           } else {
                               if ($this->wf_packinglist_get_companyname() != '') {
                                   $pdf->addCompanyname($this->wf_packinglist_get_companyname());
                               }
                           }
                           $pdf->addShippingFromAddress($this->wf_shipment_label_get_from_address(), $this->wf_packinglist_get_table_content($order, $order_package));
                           $pdf->addShippingToAddress($this->wf_shipment_label_get_to_address($order), $this->wf_enable_contact_number);
                           if ($this->wf_packinglist_get_return_policy() != '') {
                               $pdf->addPolicies($this->wf_packinglist_get_return_policy());
                           }
                           if ($this->wf_packinglist_get_footer() != '') {
                               $pdf->addFooter($this->wf_packinglist_get_footer());
                           }
                       }
                       if ($number_of_orders > 1 && $order_loop < $number_of_orders) {
                           
                       } else {
                           $pdf->Output("#" . $order->get_order_number() . "-Shipping-Label.pdf", "I");
                           exit;
                       }
                   }
               }
           }
       }

       function wf_packinglist_template($type, $template) {
           $templates = array();
           if (file_exists(trailingslashit(get_stylesheet_directory()) . 'woocommerce/wf-template/' . $template)) {
               $templates['uri'] = trailingslashit(get_stylesheet_directory_uri()) . 'woocommerce/wf-template/';
               $templates['dir'] = trailingslashit(get_stylesheet_directory()) . 'woocommerce/wf-template/';
           } else {
               $templates['uri'] = $this->wf_packinglist_get_plugin_url() . '/wf-template/';
               $templates['dir'] = $this->wf_packinglist_get_plugin_path() . '/wf-template/';
           }
           return $templates[$type];
       }

       // to check preview is enabled for packinglist
       function wf_packinglist_preview() {
           return 2;
       }

       // function to get logo for printing
       function wf_packinglist_get_logo() {
           if (get_option('woocommerce_wf_packinglist_logo') != '')
           {
              // Remote file url
              $remoteFile = get_option('woocommerce_wf_packinglist_logo');

              // Open file
              $handle = @fopen($remoteFile, 'r');

              // Check if file exists
              if($handle)
              {
                return $remoteFile;
              }
           }
       }

       // function to add company name
       function wf_packinglist_get_companyname() {
           if (get_option('woocommerce_wf_packinglist_companyname') != '') {
               return esc_html(get_option('woocommerce_wf_packinglist_companyname'));
           }
       }

       // function to get template body table body content
       function wf_packinglist_get_table_content($order, $order_package, $show_price = false) {
           $return = "";
           $weight = 0;
           if (key_exists('Value', $order_package)) {
               $weight = ($order_package['Value'] != '') ? $order_package['Value'] : 0;
           } else {
               foreach ($order_package as $order_package_individual_item) {
                   $weight += (!empty($order_package_individual_item['weight'])) ? $order_package_individual_item['weight'] * $order_package_individual_item['quantity'] : 0;
               }
           }
           $orderdetails = array(
               'order_id' => $order->get_order_number(),
               'weight' => ($weight != '') ? $weight . ' ' . get_option('woocommerce_weight_unit') : __('n/a', 'wf-woocommerce-packing-list')
           );
           return $orderdetails;
       }

       // function to add return policy
       function wf_packinglist_get_return_policy() {
           if (get_option('woocommerce_wf_packinglist_return_policy') != '') {
               return wp_kses_post(nl2br(stripslashes(get_option('woocommerce_wf_packinglist_return_policy'))));
           }
       }

       // fucntion to add footer
       function wf_packinglist_get_footer() {
           if (get_option('woocommerce_wf_packinglist_footer') != '') {
               return wp_kses_post(nl2br(stripslashes(get_option('woocommerce_wf_packinglist_footer'))));
           }
       }

       // fucntion to load client scripts
       function wf_packinglist_client_scripts() {
           $version = '2.4.2';
           wp_register_script('woocommerce-packinglist-client-js', untrailingslashit(plugins_url('/', __FILE__)) . '/js/woocommerce-packinglist-client.js', array(
               'jquery'
                   ), $version, true);
           if (is_page(get_option('woocommerce_view_order_page_id'))) {
               wp_enqueue_script('woocommerce-packinglist-client-js');
           }
       }

       // function to add menu in woocommerce
       function wf_packinglist_admin_menu() {
           global $packinglist_settings_page;
           $packinglist_settings_page = add_submenu_page('woocommerce', __('Print Options', 'wf-woocommerce-packing-list'), __('Print Options', 'wf-woocommerce-packing-list'), 'manage_woocommerce', 'wf_woocommerce_packing_list', array(
               $this,
               'wf_woocommerce_packinglist_printing_page'
           ));
       }

       // function to add settings options in settings menu
       function wf_woocommerce_packinglist_printing_page() {
           // check user access limit
           if (!current_user_can('manage_woocommerce')) {
               die("You are not authorized to view this page");
           }
           // functions to upload media
           wp_enqueue_media();
           //include_once('market.php');
           ?>
        <div class="wrap">

            <style>
                .wf-banner img { float: right; margin-left: 1em; padding: 15px 0; }
                .wt_warn_box{ background:#fff3cd; color:#8c6407; border:solid 1px #faebcc; padding:10px; width:98%; margin:0px 1%; margin-top:15px; box-sizing:border-box; }
                .wt_shl_warning {font-style: normal;font-weight: 500;font-size: 14px;line-height: 153%;letter-spacing: 0.005em;color: #5F3B3B;background: #FFECEC;padding: 15px;border-left: 5px solid #FF8C8C;}
                .wt_shl_warning .notice-heading{ font-style: normal; font-weight: 600; font-size: 14px; letter-spacing: 0.005em; color: #B00000; }
                .wt_shl_warning b{ font-weight: 600; }
                .wt_shl_warning ul{ list-style: disc; padding: 0 13px; }
                .wt_shl_warning ul li{ font-size: 13px; line-height: 1.5; margin-bottom: 5px; }
            </style>
            <div id="icon-options-general" class="icon32"><br/></div>
            <div class="wt_shl_warning">
                <p class="notice-heading"><?php echo __('Important Update!','wf-woocommerce-packing-list'); ?></p>
                <p style="margin:0; line-height:1.7;">
                <?php echo __('This plugin will be discontinued from 19 April onwards.','wf-woocommerce-packing-list') ?>
                <br>
                <?php echo __('Please download the WooCommerce PDF Invoices, Packing Slips, Delivery Notes and Shipping Labels plugin for more features, regular updates, and support.','wf-woocommerce-packing-list'); ?>
                <br>
                <br>
                <?php echo __('Key features:','wf-woocommerce-packing-list'); ?>
                </p>
                <ul>
                    <li> <?php echo __('Create and manage multiple invoices and shipping documents','wf-woocommerce-packing-list'); ?> </li>
                    <li> <?php echo __('Free templates & customization options','wf-woocommerce-packing-list'); ?> </li>
                    <li> <?php echo __('Bulk print documents','wf-woocommerce-packing-list'); ?> </li>
                    <li> <?php echo __('Send PDF copies via email','wf-woocommerce-packing-list'); ?> </li>
                    <li> <?php echo __('Custom invoice numbers & More','wf-woocommerce-packing-list'); ?></li>
                </ul>
                <a class="btn btn-primary" href="https://wordpress.org/plugins/print-invoices-packing-slip-labels-for-woocommerce" target="_blank"><?php echo __('Download','wf-woocommerce-packing-list'); ?></a>
            </div>


            <h2><?php _e('WooCommerce Shipping Label (BASIC)', 'wf-woocommerce-packing-list'); ?></h2><?php
        if(isset($_POST['wf_packinglist_fields_submitted']) && sanitize_text_field($_POST['wf_packinglist_fields_submitted']) == 'submitted') 
        {
            if(!Wt_Pkl_Sh::check_write_access(WF_SHLBL_PLUGIN_NAME))
            {
              ?>
              <div id="message" class="error" style="color:red;"><p><strong><?php _e('Unable to save settings.', 'wf-woocommerce-packing-list'); ?></strong></p></div>
              <?php
              exit();
            }else
            {

              $default_settings=array(
                'woocommerce_wf_packinglist_companyname'=>'',
                'woocommerce_wf_packinglist_logo'=>'',
                'woocommerce_wf_packinglist_return_policy'=>'',
                'woocommerce_wf_packinglist_footer'=>'',
                'woocommerce_wf_packinglist_package_type'=>'',
                'woocommerce_wf_packinglist_sender_name'=>'',
                'woocommerce_wf_packinglist_sender_address_line1'=>'',
                'woocommerce_wf_packinglist_sender_address_line2'=>'',
                'woocommerce_wf_packinglist_sender_city'=>'',
                'woocommerce_wf_packinglist_sender_country'=>'',
                'woocommerce_wf_packinglist_sender_postalcode'=>'',
                'woocommerce_wf_packinglist_contact_number'=>'Yes',
                'woocommerce_wf_packinglist_enable_cyrillic'=>'Yes',
              );

              $validation_rule=array(
                  'woocommerce_wf_packinglist_logo'=>array('type'=>'url'),
                  'woocommerce_wf_packinglist_return_policy'=>array('type'=>'textarea'),
                  'woocommerce_wf_packinglist_footer'=>array('type'=>'textarea'),
              );
              
              foreach ($_POST as $key => $value) 
              {
                  if(isset($default_settings[$key])) //setting exists
                  {
                      $value=Wt_Pkl_Sh::sanitize_data($_POST[$key], $key, $validation_rule);
                      update_option($key, $value);
                  }
              }
              ?>
                <div id="message" class="updated"><p><strong><?php _e('Your settings have been saved.', 'wf-woocommerce-packing-list'); ?></strong></p></div>
                <?php
                $this->wf_pklist_init_fields();
            }
        }
        ?>
            <div id="content">		
                <style>
                    .tab_active{ background-color:white;}
                    .settings_headings {
                        font-size: 20px;
                        padding: 8px 12px;
                        margin: 0;
                        line-height: 1.4;
                        border-bottom: 1px dashed #ccc;
                        padding-left:0px;
                    }
                    .wt_pkl_sh_tab_container{ box-shadow: 0px 2px 2px #bbb; padding-top:0px;}
                    .wt_pkl_sh_tab_footer{ background:#f1f1f1; border-top: 1px solid #ddd; padding:15px; }
                    .tabcontent{ padding:10px; }
                    .nav-tabs{ margin-left:-1px; }
                    .nav-tabs a:focus{ box-shadow:none; }
                </style>
                <script type="text/javascript">

                    $(document).ready(function () {

                        $('a[data-toggle="tab"]').on('show.bs.tab', function (e) {

                            localStorage.setItem('activeTab', $(e.target).attr('href'));

                        });

                        var activeTab = localStorage.getItem('activeTab');

                        if (activeTab) {

                            $('#myTab a[href="' + activeTab + '"]').tab('show');

                        }

                    });


                </script>

                <form method="post" action="" id="packinglist_settings">
                    <?php
                    // Set nonce:
                    if (function_exists('wp_nonce_field'))
                    {
                        wp_nonce_field(WF_SHLBL_PLUGIN_NAME);
                    }
                    ?>
                    <input type="hidden" name="wf_packinglist_fields_submitted" value="submitted">
                    <nav class="nav-tab-wrapper woo-nav-tab-wrapper">
                        <ul class="nav nav-tabs" id="myTab">
                            <li class="active"><a data-toggle="tab" href="#sectionA"><span class="dashicons dashicons-menu"></span> <b>General</b></a></li>
                        </ul>

                        <div class="tab-content wt_pkl_sh_tab_container">

                            <div id="sectionA" class="tab-pane fade in tab_active active">
                                <?php include_once('includes/settings/generic_settings.php'); ?>
                                <div class="wt_pkl_sh_tab_footer">
                                    <input type="submit" name="Submit" class="button-primary" value="<?php _e('Save Changes', 'wf-woocommerce-packing-list'); ?>" />
                                </div>
                            </div>
                        </div>
                    </nav>	


                </form>
            </div>

        </div><?php
                            }

                            // function to add admin meta box
                            function wf_packinglist_add_box() {
                                add_meta_box('woocommerce-packinglist-box', __('Print Actions', 'wf-woocommerce-packing-list'), array(
                                    $this,
                                    'woocommerce_packinglist_create_box_content'
                                        ), 'shop_order', 'side', 'default');
                            }

                            // function to add content to meta boxes
                            function woocommerce_packinglist_create_box_content() {
                                global $post;
                                $order = ( WC()->version < '2.7.0' ) ? new WC_Order($post->ID) : new wf_order($post->ID);
                                ?>
        <table class="form-table">
            <tr>
                <td><a class="button tips wf-packing-list-link" target="_blank" data-tip="<?php esc_attr_e('Print Shipping Label', 'wf-woocommerce-packing-list'); ?>" href="<?php echo wp_nonce_url(admin_url('?print_packinglist=true&post=' . $order->id . '&type=print_shipment_label'), 'print-packinglist'); ?>">
                  <img src="<?php echo esc_url($this->wf_packinglist_get_plugin_url() . '/assets/images/Label-print-icon.png'); ?>" alt="<?php esc_attr_e('Print Shipping Label', 'wf-woocommerce-packing-list'); ?>" width="14"><?php _e('Print Shipping Label', 'wf-woocommerce-packing-list'); ?></a>
                </td>
            </tr>
            <tr>
                <td><a class="button tips wf-link" data-tip="<?php esc_attr_e('Download Shipping Label', 'wf-woocommerce-packing-list'); ?>" href="<?php echo wp_nonce_url(admin_url('?print_packinglist=true&post=' . esc_html($order->id) . '&type=download_shipment_label'), 'print-packinglist'); ?>">
                  <img src="<?php echo esc_url($this->wf_packinglist_get_plugin_url() . '/assets/images/pdf-icon.png'); ?>" alt="<?php esc_attr_e('Download Shipping Label', 'wf-woocommerce-packing-list'); ?>" width="14"><?php _e('Download Shipping Label', 'wf-woocommerce-packing-list'); ?></a>
                </td>
            </tr>
        </table><?php
    }

    // function to add required javascript files
    function wf_packinglist_scripts() {
        // Version number for scripts
        wp_register_script('woocommerce-packinglist-js', untrailingslashit(plugins_url('/', __FILE__)) . '/resources/js/woocommerce-packinglist.js', array('jquery'), WT_PKL_SH_VERSION);
        wp_register_script('woocommerce-shipment-js', untrailingslashit(plugins_url('/', __FILE__)) . '/resources/js/woocommerce-shipment.js', array('jquery'), WT_PKL_SH_VERSION);
        wp_enqueue_script('woocommerce-shipment-js');
        wp_enqueue_script('woocommerce-packinglist-js');
    }

    function admin_scripts() {
        wp_enqueue_script('wc-enhanced-select');
        wp_enqueue_style('woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), WT_PKL_SH_VERSION);
        wp_enqueue_style('woocommerce_admin_styles', WC()->plugin_url() . '/resources/css/box_packing.css', array(), WT_PKL_SH_VERSION);
        if ((isset($_GET['page']) && $_GET['page'] === 'wf_woocommerce_packing_list')) {

            wp_enqueue_style('wf_invoice_customization_bootstrap_css', untrailingslashit(plugins_url('/', __FILE__)) . '/assets/new_invoice_css_js/dist/css/bootstrap.min.css', array(), WT_PKL_SH_VERSION);
            wp_enqueue_style('wf_invoice_customization_font_awsome', untrailingslashit(plugins_url('/', __FILE__)) . '/assets/new_invoice_css_js/font-awesome/css/font-awesome.min.css', array(), WT_PKL_SH_VERSION);
            wp_enqueue_style('wf_invoice_customization_custom_css', untrailingslashit(plugins_url('/', __FILE__)) . '/assets/new_invoice_css_js/css/custom.css', array(), WT_PKL_SH_VERSION);
            wp_enqueue_script('wf_invoice_customization_jquery', untrailingslashit(plugins_url('/', __FILE__)) . '/assets/new_invoice_css_js/dist/jquery.min.js', array(), WT_PKL_SH_VERSION);
            wp_enqueue_script('wf_invoice_customization_bootstrap', untrailingslashit(plugins_url('/', __FILE__)) . '/assets/new_invoice_css_js/dist/js/bootstrap.min.js', array(), WT_PKL_SH_VERSION);
            wp_enqueue_script('wf_invoice_customization_jscolor', untrailingslashit(plugins_url('/', __FILE__)) . '/assets/new_invoice_css_js/dist/js/jscolor.min.js', array(), WT_PKL_SH_VERSION);
            wp_enqueue_script('wf_invoice_customization', untrailingslashit(plugins_url('/', __FILE__)) . '/assets/new_invoice_css_js/js/New_invoice_custom.js', array(), WT_PKL_SH_VERSION);
        }
    }

    // function to load scripts required for admin
    function wf_packinglist_admin_scripts($hook) {
        global $packinglist_settings_page;
        if ($hook != $packinglist_settings_page) {
            return;
        }
        wp_register_script('wf-packinglist-admin-js', untrailingslashit(plugins_url('/', __FILE__)) . '/resources/js/woocommerce-packinglist-admin.js', array('jquery'), WT_PKL_SH_VERSION);
        wp_register_script('wf-packinglist-validate', untrailingslashit(plugins_url('/', __FILE__)) . '/resources/js/jquery.validate.min.js', array('jquery'), WT_PKL_SH_VERSION);
        wp_register_script('wf-shipment-admin-js', untrailingslashit(plugins_url('/', __FILE__)) . '/resources/js/woocommerce-shipment-admin.js', array('jquery'), WT_PKL_SH_VERSION);
        wp_register_script('wf-shipment-validate', untrailingslashit(plugins_url('/', __FILE__)) . '/resources/js/jquery.validate.min.js', array('jquery'), WT_PKL_SH_VERSION);
        wp_register_script('wf_common', untrailingslashit(plugins_url('/', __FILE__)) . '/resources/js/wf_common.js', array('jquery'), WT_PKL_SH_VERSION);
        wp_enqueue_script('wf-shipment-admin-js');
        wp_enqueue_script('wf-shipment-validate');
        wp_enqueue_script('wf-packinglist-admin-js');
        wp_enqueue_script('wf-packinglist-validate');
        wp_enqueue_script('wf_common');
    }


    // function to check wheter the user has added shipping from address
    function wf_packinglist_check_from_address() {
        if (!(get_option('woocommerce_wf_packinglist_sender_name') != '' && get_option('woocommerce_wf_packinglist_sender_address_line1') != '' && get_option('woocommerce_wf_packinglist_sender_city') != '' && get_option('woocommerce_wf_packinglist_sender_country') != '' && get_option('woocommerce_wf_packinglist_sender_postalcode') != '')) {
            return true;
        } else {
            return false;
        }
    }

    // function to determine the size of the label
    function wf_shipment_label_get_label_size() {
        if (get_option('woocommerce_wf_packinglist_label_size') != '') {
            //$var = get_option('woocommerce_wf_packinglist_label_size');
            //return $var;
        }
    }

    // function to get shipping to address
    function wf_shipment_label_get_to_address($order) {
        $order = ( WC()->version < '2.7.0' ) ? new WC_Order($order) : new wf_order($order);
        $shipping_address = array();
        $countries = new WC_Countries;
        $shipping_country = get_post_meta((WC()->version < '2.7.0') ? $order->id : $order->get_id(), '_shipping_country', true);
        $shipping_state = get_post_meta((WC()->version < '2.7.0') ? $order->id : $order->get_id(), '_shipping_state', true);
        $shipping_state_full = ( $shipping_country && $shipping_state && isset($countries->states[$shipping_country][$shipping_state]) ) ? $countries->states[$shipping_country][$shipping_state] : $shipping_state;
        $shipping_country_full = ( $shipping_country && isset($countries->countries[$shipping_country]) ) ? $countries->countries[$shipping_country] : $shipping_country;
        $shipping_address = apply_filters('wt_woocommerce_shipping_lable_shipping_address', array(
            'first_name' => $order->shipping_first_name,
            'last_name' => $order->shipping_last_name,
            'company' => $order->shipping_company,
            'address_1' => $order->shipping_address_1,
            'address_2' => $order->shipping_address_2,
            'city' => $order->shipping_city,
            'state' => $shipping_state_full,
            'postcode' => $order->shipping_postcode,
            'country' => $shipping_country_full,
            'phone' => $order->billing_phone
        ));
        // clear the $countries object when we're done to free up memory
        unset($countries);
        if ($_GET['type'] == 'print_shipment_label')
        {    
            echo wp_kses_post(__($shipping_address['first_name'].' '.$shipping_address['last_name'],'wf-woocommerce-packing-list'));
            if($shipping_address['company'] != '')
            {
              echo wp_kses_post('<br />'.__($shipping_address['company'],'wf-woocommerce-packing-list'));
            }
            echo wp_kses_post('<br />'.__($shipping_address['address_1'],'wf-woocommerce-packing-list'));
            if($shipping_address['address_2']!='')
            {
              echo wp_kses_post('<br />'.__($shipping_address['address_2'],'wf-woocommerce-packing-list'));
            }
            echo wp_kses_post('<br />'.__($shipping_address['city'].' - '.$shipping_address['postcode'],'wf-woocommerce-packing-list'));
            
            if($shipping_address['state']!='')
            {
                echo wp_kses_post('<br />'.__(str_replace('&rsquo;', "'",$shipping_address['state']).', '.$shipping_address['country'],'wf-woocommerce-packing-list'));
            }
            else
            {
                echo wp_kses_post('<br />'.__($shipping_address['country'],'wf-woocommerce-packing-list'));
            }
            if ($order->billing_phone && $this->wf_enable_contact_number == 'Yes') {
                echo "<p style = 'font-size:28px; margin-top:10px;'>";
                _e('Ph No : ', 'wf-woocommerce-shipment-label-printing');
                echo wp_kses_post($order->billing_phone) . '</p>';
            }
        }else 
        {            
            return wp_kses_post($shipping_address);
        }
    }

    // function to get shipping from address
    function wf_shipment_label_get_from_address() {
        $fromaddress = array();
        if (get_option('woocommerce_wf_packinglist_sender_name') != '') {
            $fromaddress['sender_name'] = wp_kses_post(stripslashes(get_option('woocommerce_wf_packinglist_sender_name')));
        }
        if (get_option('woocommerce_wf_packinglist_sender_address_line1') != '') {
            $fromaddress['sender_address_line1'] = wp_kses_post(stripslashes(get_option('woocommerce_wf_packinglist_sender_address_line1')));
        }
        if (get_option('woocommerce_wf_packinglist_sender_address_line2') != '') {
            $fromaddress['sender_address_line2'] = wp_kses_post(stripslashes(get_option('woocommerce_wf_packinglist_sender_address_line2')));
        } else {
            $fromaddress['sender_address_line2'] = '';
        }
        if (get_option('woocommerce_wf_packinglist_sender_city') != '') {
            $fromaddress['sender_city'] = wp_kses_post(stripslashes(get_option('woocommerce_wf_packinglist_sender_city')));
        }
        if (get_option('woocommerce_wf_packinglist_sender_country') != '') {
            $fromaddress['sender_country'] = wp_kses_post(stripslashes(get_option('woocommerce_wf_packinglist_sender_country')));
        }
        if (get_option('woocommerce_wf_packinglist_sender_postalcode') != '') {
            $fromaddress['sender_postalcode'] = wp_kses_post(get_option('woocommerce_wf_packinglist_sender_postalcode'));
        }
        return $fromaddress;
    }

    // function to get logo size
    function wf_packinglist_get_logosize() {
        return @getimagesize($this->wf_packinglist_get_logo());
    }

    //function to determine the packaging type
    public function wf_pklist_create_order_package($order) {
        return $this->wf_pklist_create_order_single_package($order);
    }

    //function to create packaging list and shipping lables package
    private function wf_pklist_create_order_single_package($order) {
        $order = ( WC()->version < '2.7.0' ) ? new WC_Order($order) : new wf_order($order);
        $order_items = $order->get_items();
        $packinglist_package = array();
        foreach ($order_items as $id => $item) {
            $product =( WC()->version < '4.4.0' ) ? $order->get_product_from_item($item) : $item->get_product();
            $sku = $variation = '';
            if ($product)
                $sku = $product->get_sku();
            $item_meta = (WC()->version < '3.1.0') ? new WC_Order_Item_Meta($item) : new WC_Order_Item_Product;
            $variation = (WC()->version < '3.1.0') ? $item_meta->display(true, true) : $item_meta->get_product();

            if (WC()->version < '2.7.0') {
                $product_variation_data = $product->variation_data;
            } else {
                $product_variation_data = $product->is_type('variation') ? wc_get_product_variation_attributes($product->get_id()) : '';
            }

            if (!$variation && $product && isset($product_variation_data)) {
                $variation = wc_get_formatted_variation($product_variation_data, true);
            }
            $variation_details = $product->get_type() == 'variation' ? wc_get_formatted_variation($product_variation_data, true) : '';
            $packinglist_package[0][] = array(
                'sku' => $product->get_sku(),
                'name' => $product->get_title(),
                'type' => $product->get_type(),
                'weight' => $product->get_weight(),
                'id' => (WC()->version < '2,7,0') ? $product->id : $product->get_id(),
                'price' => $product->get_price(),
                'variation_data' => $variation_details,
                'quantity' => $item['qty']
            );
        }
        return $packinglist_package;
    }

    //function to get new dimensions
    public function wf_pklist_get_new_dimensions($image_url, $target_height, $target_width) {
        $new_dimensions = array();
        $image_info = @getimagesize($image_url);
        if (($image_info[1] <= $target_height) && ($image_info[0] <= $target_width)) {
            $new_dimensions['width'] = $image_info[0];
            $new_dimensions['height'] = $image_info[1];
        } else {
            $new_dimensions = $this->wf_pklist_get_calculate_new_dimensions($image_info[1], $image_info[0], $target_height, $target_width);
        }
        return $new_dimensions;
    }

    //function to resize image with aspect ratio
    public function wf_pklist_get_calculate_new_dimensions($current_height, $current_width, $target_height, $target_width) {
        $new_dimensions = array(
            'height' => $current_height,
            'width' => $current_width
        );
        $calculate_dimensions = true;
        if ($current_height > $current_width) {
            $aspect_ratio = $target_height / $current_height;
        } else {
            $aspect_ratio = $target_width / $current_width;
        }
        while ($calculate_dimensions) {
            $new_dimensions['height'] = floor($aspect_ratio * $new_dimensions['height']);
            $new_dimensions['width'] = floor($aspect_ratio * $new_dimensions['width']);
            if (($new_dimensions['height']) > $target_height) {
                $aspect_ratio = $target_height / $new_dimensions['height'];
            } else if (($new_dimensions['width']) > $target_width) {
                $aspect_ratio = $target_width / $new_dimensions['width'];
            } else {
                $calculate_dimensions = false;
            }
        }
        return $new_dimensions;
    }

}

define ('WF_SHLBL_PLUGIN_NAME', 'shipping_labels_for_woo');

new Wf_WooCommerce_Packing_List_Woo();
