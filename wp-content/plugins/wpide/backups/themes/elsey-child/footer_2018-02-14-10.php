<?php /* start WPide restore code */
                                    if ($_POST["restorewpnonce"] === "0cf5802c16ef023b2ce7ab56ab263cadeffc140797"){
                                        if ( file_put_contents ( "/home/carome/carome.xsrv.jp/public_html/wp-content/themes/elsey-child/footer.php" ,  preg_replace("#<\?php /\* start WPide(.*)end WPide restore code \*/ \?>#s", "", file_get_contents("/home/carome/carome.xsrv.jp/public_html/wp-content/plugins/wpide/backups/themes/elsey-child/footer_2018-02-14-10.php") )  ) ){
                                            echo "Your file has been restored, overwritting the recently edited file! \n\n The active editor still contains the broken or unwanted code. If you no longer need that content then close the tab and start fresh with the restored file.";
                                        }
                                    }else{
                                        echo "-1";
                                    }
                                    die();
                            /* end WPide restore code */ ?><?php
/*
 * The template for displaying the footer.
 * Author & Copyright: VictorThemes
 * URL: http://themeforest.net/user/VictorThemes
 */

global $post;
$elsey_id    = ( isset( $post ) ) ? $post->ID : false;
$elsey_id    = ( is_home() ) ? get_option( 'page_for_posts' ) : $elsey_id;
$elsey_id    = ( is_woocommerce_shop() ) ? wc_get_page_id( 'shop' ) : $elsey_id;
$elsey_meta  = get_post_meta( $elsey_id, 'page_type_metabox', true );

if ($elsey_meta) {
  $elsey_hide_footer     = $elsey_meta['hide_footer'];
  $elsey_menubar_options = $elsey_meta['menubar_options'];

  if ($elsey_menubar_options === 'hide') {
    $elsey_menubar_rightmenu = false;
  } elseif ($elsey_menubar_options === 'custom') {
    $elsey_menubar_rightmenu = $elsey_meta['menubar_rightmenu'];
  } else {
    $elsey_menubar_rightmenu = cs_get_option('menubar_rightmenu');
  }
} else {
  $elsey_hide_footer  = false;
  $elsey_menubar_rightmenu  = cs_get_option('menubar_rightmenu');
} ?>
<?php if ( !is_product() ){ echo '</div>'; } ?>
<!-- Content Background End -->
</div>
<!-- max-width--site End -->
</div>
<!-- Wrapper End -->

<?php
$elsey_footer_widget  = cs_get_option('footer_widget_block');
$elsey_need_copyright = cs_get_option('need_copyright');

if (!$elsey_hide_footer) {
  if ($elsey_footer_widget || $elsey_need_copyright) { ?>
    <!-- Footer Start -->
    <footer class="els-footer">
		<div class="footer__container max-width--site">
      <?php if (isset($elsey_footer_widget)) {
        // Footer Widget Block
        get_template_part( 'layouts/footer/footer', 'widgets' );
      }
      if (isset($elsey_need_copyright)) {
        // Copyright Block
        get_template_part( 'layouts/footer/footer', 'copyright' );
      } ?>
		</div>
    </footer>
    <!-- Footer End-->
<?php
  }
} ?>
<div class="focus-overlay focus-overlay--body"></div>
</div><!-- Wrap End -->

<?php
if ($elsey_menubar_rightmenu) {
  echo '<a href="javascript:void(0)" id="els-sidebar-menu-footer-close" class="els-sidebar-menu-footer-close"><i class="fa fa-times" aria-hidden="true"></i></a>';
}

if (function_exists('elsey_preloader_option')) { echo elsey_preloader_option(); } else { echo ''; }
wp_footer(); ?>
		<?php if (is_product()) { ?><div class="remodal remodalSource" data-remodal-id="waitlistmodal"></div><?php } ?>

<!-- If User Not Logged In Then Signup Popup-->		
<div id="sisfySignPopup" class="modal fade" role="dialog">
  <div class="modal-dialog">
	<!-- Modal content-->
    <div class="modal-content">
      <div class="modal-body">
		<p><?=_e("You need to login if u will use favorite list.","elsey");?></p>
        <p><a class="slogin" href="<?php echo home_url()."/my-account/favorite-list/"; ?>"><?=_e("Login","elsey");?></a><a class="sclose" data-dismiss="modal"><?=_e("Close");?></a></p>
      </div>
    </div>
  </div>
</div>
<script>
//Custom Js

jQuery(document).ready(function(){
	//Cart
	jQuery(".els-icon li").on("click",".woocommerce-mini-cart .remove",function(){
		jQuery('#els-shopping-cart-content-sticky .widget_shopping_cart_content').toggleClass('els-cart-popup-open');
		jQuery('#els-shopping-cart-content').toggleClass('toggle--active');
		jQuery('#els-shopping-cart-content-sticky').toggleClass('toggle--active');
		setTimeout(function(){ jQuery("#els-cart-trigger").click();jQuery('.focus-overlay').toggleClass('set--active'); }, 900);
	});
	//Favourite List
	<?php if (!is_user_logged_in()): ?>
		jQuery(".product-template-default .els-product-summary-col").on("click",".yith-wcwl-add-to-wishlist .button",function(){
			jQuery("#sisfySignPopup").modal();
			if(jQuery('.modal-backdrop').length > 0) {
				jQuery('.modal-backdrop').addClass('sisfy-modal-backdrop');
			}
			return false;
		});
		jQuery("#sisfySignPopup").on("hidden.bs.modal", function () {
			jQuery('.modal-backdrop').removeClass('sisfy-modal-backdrop');
		});
	<?php endif; ?>
	<?php
	if(is_product()){
		global $product;
		if($product->get_stock_status() == "outofstock"){
		?>	
				//Nothing
		<?php
		}else{
			?>
				if(jQuery(".woocommerce-variation-add-to-cart-disabled.soldout_disabled").length == 0){
					jQuery("#woocommerce_waitlist_wraper a.woocommerce_waitlist_new").text("<?=_e("Need notification for out of stock product ?","elsey");?>").addClass("cta link_waitlist_cta");
				}
				jQuery(".els-product-summary-col").on("change",".variations select",function () {
					if(jQuery(".woocommerce-variation-add-to-cart-disabled.soldout_disabled").length == 0){
						jQuery("#woocommerce_waitlist_wraper a.woocommerce_waitlist_new").text("<?=_e("Need notification for out of stock product ?","elsey");?>").addClass("cta link_waitlist_cta");
					}else{
						jQuery("#woocommerce_waitlist_wraper a.woocommerce_waitlist_new").text("<?=_e("Join waitlist","elsey");?>").removeClass("cta link_waitlist_cta");
					}
				});
			<?php
		}
	}
	?>
});
</script>
<style>
.woocommerce-checkout .woocommerce-account-fields{
   display: none;    
}
.add{
   display: block !important;    
}
.cus input {
    float: none;
    width: auto;
    position: static;
    margin: 0 6px 0 0;
    padding: 0;
    vertical-align: middle;
    display: inline-block !important;
    max-width: 21px;
    -webkit-appearance: checkbox;
}
</style>

</body>
</html>