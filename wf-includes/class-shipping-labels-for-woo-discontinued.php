<?php
/**
 * Review request
 *  
 *
 * @package  Wf_WooCommerce_Packing_List_Woo  
 */
if (!defined('ABSPATH')) {
    exit;
}

if ( 'Wf_WooCommerce_Packing_List_Woo_Discontinued' ) {
class Wf_WooCommerce_Packing_List_Woo_Discontinued {
    private $banner_css_class   = '';
    private $plugin_prefix      = "wt_pkl_sh";
    private $ajax_action_name   = '';
    private $banner_state_option_name='';
    private $current_banner_state=1;
    private $new_plugin_url     = 'https://wordpress.org/plugins/print-invoices-packing-slip-labels-for-woocommerce';

    public function __construct() {
        
       	$this->set_vars();
        if( $this->check_condition() ) { /* checks the banner is active now */
            add_action('admin_notices', array($this, 'show_banner'));
            add_action('admin_print_footer_scripts', array($this, 'add_banner_scripts')); /* add banner scripts */
            add_action('wp_ajax_'.$this->ajax_action_name, array($this, 'process_user_action')); /* process banner user action */
        }
    }

    public function set_vars() {
        $this->banner_css_class         = 'wt_main_discontinue_banner';
        $this->ajax_action_name         = $this->plugin_prefix.'_process_discontinue_action';
        $this->banner_state_option_name = $this->plugin_prefix."_main_discontinue_banner"; 
        $banner_state                   = absint(get_option($this->banner_state_option_name));
        $this->current_banner_state     = ( 0 === $banner_state ? $this->current_banner_state : $banner_state );
    }

    public function check_condition() {

        if ( isset( $_GET['page'] ) ) {
           if ( 'wf_woocommerce_packing_list' === sanitize_text_field( $_GET['page'] ) ) {
             return false;
           }
        }

        if( 1 === $this->current_banner_state ) /* currently showing then return true */
		{
			return true;
		}
        return false;
    }
    /**
	*	Prints the banner 
	*/
	public function show_banner()
	{
		$this->update_banner_state(1); /* update banner active state */
		?>
		<div class="<?php echo esc_attr($this->banner_css_class);?> notice-error notice is-dismissible" style="font-style: normal;font-weight: 500;font-size: 14px;line-height: 153%;letter-spacing: 0.005em;color: #5F3B3B;background: #FFECEC;padding: 15px;border-left: 5px solid #FF8C8C;">
            <h2 style="margin: 0;font-style: normal;font-weight: 600;font-size: 14px;letter-spacing: 0.005em;color: #B00000;"><?php echo __('Important Update!','wf-woocommerce-packing-list'); ?></h2>
            <p style="margin:0; line-height:1.7;padding:0;">
            <?php echo __('This plugin will be discontinued from 19 April onwards.','wf-woocommerce-packing-list') ?>
            <br>
            <?php echo __('Please download the WooCommerce PDF Invoices, Packing Slips, Delivery Notes and Shipping Labels plugin for more features, regular updates, and support.','wf-woocommerce-packing-list'); ?>
            <br>
            <br>
            <?php echo __('Key features:','wf-woocommerce-packing-list'); ?>
            </p>
            <ul style="margin: 0;list-style: disc;padding: 0 13px;font-size: 13px;line-height: 1.5;">
                <li> <?php echo __('Create and manage multiple invoices and shipping documents','wf-woocommerce-packing-list'); ?> </li>
                <li> <?php echo __('Free templates & customization options','wf-woocommerce-packing-list'); ?> </li>
                <li> <?php echo __('Bulk print documents','wf-woocommerce-packing-list'); ?> </li>
                <li> <?php echo __('Send PDF copies via email','wf-woocommerce-packing-list'); ?> </li>
                <li> <?php echo __('Custom invoice numbers & More','wf-woocommerce-packing-list'); ?></li>
            </ul>
            <a class="btn btn-primary wt_shl_new_plugin_download" style=" background-color: #337ab7; border-color: #2e6da4; margin-bottom: 5px; margin-right: 5px; color: #fff; display: inline-block; border-radius: 3px; padding: 6px 12px; text-decoration: none; margin-bottom: 0; font-size: 14px; font-weight: 400; line-height: 1.42857143; text-align: center; white-space: nowrap; vertical-align: middle; -ms-touch-action: manipulation; touch-action: manipulation; cursor: pointer; -webkit-user-select: none; -moz-user-select: none; -ms-user-select: none; user-select: none; background-image: none; border: 1px solid transparent;"><?php echo __('Download','wf-woocommerce-packing-list'); ?></a>
		</div>
		<?php
	}

    public function add_banner_scripts()
	{
        $ajax_url=admin_url('admin-ajax.php');
		$nonce=wp_create_nonce($this->plugin_prefix);
        ?>
        <script type="text/javascript">
            (function($){
                "use strict";

                /* prepare data object */
                var data_obj={
	            	_wpnonce: '<?php echo esc_html($nonce);?>',
            		action: '<?php echo esc_html($this->ajax_action_name);?>',
            		wt_notice_action_type: 'close_discontiue_banner'
	            };
                $(document).on('click', '.<?php echo esc_html($this->banner_css_class);?> a.wt_shl_new_plugin_download', function(e)
		        {
                    e.preventDefault();
                    var elm=$(this);
                    window.open('<?php echo esc_url($this->new_plugin_url);?>');
                    elm.parents('.<?php echo esc_attr($this->banner_css_class);?>').hide();
                    $.ajax({
		            	url:'<?php echo esc_url($ajax_url); ?>',
		            	data:data_obj,
		            	type: 'POST',
		            });
                });
                $(document).on('click', '.<?php echo esc_attr($this->banner_css_class);?> .notice-dismiss', function(e) {
                    e.preventDefault();
		            $.ajax({
		            	url:'<?php echo esc_url($ajax_url); ?>',
		            	data:data_obj,
		            	type: 'POST',
		            });
                });
            })(jQuery);
        </script>
        <?php
    }

    public function process_user_action()
	{
		check_ajax_referer($this->plugin_prefix);
        if( isset( $_POST['wt_notice_action_type'] ) ) {
			
            $action_type    = sanitize_text_field( $_POST['wt_notice_action_type'] );
            if( "close_discontiue_banner" === $action_type ) {
                $this->update_banner_state(3);
            }
		}

		exit();
	}

    private function update_banner_state($val)
	{
		update_option($this->banner_state_option_name, $val);
	}
}
new Wf_WooCommerce_Packing_List_Woo_Discontinued();
}