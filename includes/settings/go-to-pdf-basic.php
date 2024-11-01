<?php
  $wf_admin_img_path=WT_PKL_SH_PLUGIN_URL . 'assets/images/';
  $dowload_arr = wp_remote_get("https://api.wordpress.org/plugins/info/1.0/print-invoices-packing-slip-labels-for-woocommerce.json");
  $dowload_button = "";
  if(!is_wp_error($dowload_arr) && (wp_remote_retrieve_response_code($dowload_arr)==200))
  {
    $response=json_decode(wp_remote_retrieve_body($dowload_arr));
    if(is_object($response))
    {
      $arr = json_decode(wp_remote_retrieve_body($dowload_arr), true);
      if(array_key_exists('download_link', $arr)){
        $dowload_button = $arr['download_link'];
      }
    }
  }
?>
<style type="text/css">
.wt-pdfpro-header{background: #EFF4FF;border-radius: 7px;padding: 8px;margin: 0;}
.wt-pdfpro-name{border-radius: 3px;margin: 0;padding: 16px;display: flex;align-items: center;}
.wt-pdfpro-name img{width: 70px;height: auto;box-shadow: 4px 4px 4px rgba(92, 98, 215, 0.23);border-radius:7px;}
.wt-pdfpro-name h4{font-style: normal;font-weight: 600;font-size: 14px;line-height: 20px;margin: 0 0 0 12px;color: #000;}
.wt-pdfpro-features{margin: 5px 15px;padding: 20px 20px 10px 10px;background: #fff;border-radius: 13px;}
.wt-pdfpro-features li{font-style: normal;font-size: 13px;line-height: 20px;color: #000;list-style: none;position: relative;padding-left: 49px;margin: 0 0 15px 0;display: flex;align-items: center;}
.wt-pdfpro-allfeat li:before{content: '';position: absolute;height: 18px;width: 18px;background-image: url(<?php echo esc_url($wf_admin_img_path.'tick.svg'); ?>);background-size: contain;background-repeat: no-repeat;background-position: center;left: 10px;}
.wt-pdfpro-btn-wrapper{display: block;margin: 20px auto 20px;text-align: center;}
.wt-pdfpro-outline-btn{border: 1px solid #007FFF;background: #3E34D6;border-radius: 5px;padding: 10px 15px;display: inline-block;font-style: normal;font-weight: normal;font-size: 14px;line-height: 18px;color: #fff;text-decoration: none;transition: all .2s ease;position: relative;}
.wt-pdfpro-outline-btn-link{padding: 10px 15px;display: inline-block;font-style: normal;font-weight: normal;font-size: 14px;line-height: 18px;color: #3E34D6;text-decoration: none;transition: all .2s ease;position: relative;}
.wt-pdfpro-outline-btn:hover{text-decoration: none;transform: translateY(2px);transition: all .2s ease;color: #fff;}
.review_count{font-weight: 600;font-size: 15px;line-height: normal;}
.review_star{font-size: 17px;color: #F5DF1A;}
</style>
<div class="wt-pdfpro-sidebar wf_gopro_block">
  <div class="wt-pdfpro-header">
    <div class="wt-pdfpro-name">
      <div>
          <img src="<?php echo esc_url($wf_admin_img_path.'thumbnail.svg'); ?>" alt="featured img" width="36" height="36">
      </div>
      <div>
        <h4 class="wt-product-name" style="width:100%;"><?php echo __('WooCommerce PDF Invoices, Packing Slips, Delivery Notes and Shipping Labels','wf-woocommerce-packing-list'); ?></h4>
        <p style="margin: 5px 0 0 12px;background: #fff;padding: 5px 10px;width: 45%;float: left;">
          <span class="review_count">4.8 </span><span class="review_star dashicons dashicons-star-filled"></span><span class="review_star dashicons dashicons-star-filled"></span><span class="review_star dashicons dashicons-star-filled"></span><span class="review_star dashicons dashicons-star-filled"></span><span class="review_star dashicons dashicons-star-filled"></span></p>
        <p style="width: auto;float: left;line-height: normal;margin: 9px 12px;color: #3E34D6;"><a href="https://wordpress.org/support/plugin/print-invoices-packing-slip-labels-for-woocommerce/reviews/" target="_blank" style="color: #3E34D6;">(View all reviews)</a></p>
      </div>
    </div>
    <div class="wt-pdfpro-features">
      <ul class="wt-pdfpro-allfeat">
        <li><?php echo __('Customize shipping labels by adding barcode, tracking number, and more.','wf-woocommerce-packing-list'); ?></li>
        <li><?php echo __('Helps you automatically generate invoices, packing lists, shipping labels, delivery notes, & dispatch label in PDF format automatically','wf-woocommerce-packing-list'); ?></li>
        <li><?php echo __('Easily customize the invoice and add your store branding','wf-woocommerce-packing-list'); ?></li>
        <li><?php echo __('Send invoice to customers via email and also help them print invoices from their account','wf-woocommerce-packing-list'); ?></li>
        <li><?php echo __('Bulk print all documents from orders page','wf-woocommerce-packing-list'); ?></li>
        <li><?php echo __('Add custom return policy','wf-woocommerce-packing-list'); ?></li>
        <li><?php echo __('Support RTL and Unicode languages','wf-woocommerce-packing-list'); ?></li>
        <li><?php echo __('Compatible with third-party plugins & more...','wf-woocommerce-packing-list'); ?></li>
      </ul>
      <div class="wt-pdfpro-btn-wrapper">
        <?php if($dowload_button != ""){
          ?>
          <a href="<?php echo esc_url($dowload_button); ?>" class="wt-pdfpro-outline-btn" target="_blank"><?php echo __('Download free','wf-woocommerce-packing-list'); ?> <span class="dashicons dashicons-arrow-down-alt"></span></a>
          <?php
        }?>
        <a href="https://wordpress.org/plugins/print-invoices-packing-slip-labels-for-woocommerce/" class="wt-pdfpro-outline-btn-link" target="_blank"><?php echo __('Visit plugin page','wf-woocommerce-packing-list'); ?> <span class="dashicons dashicons-arrow-right-alt"></span></a>
      </div>
    </div>
  </div>
</div>