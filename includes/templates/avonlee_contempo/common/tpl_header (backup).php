<?php
/**
 * Common Template - tpl_header.php
 *
 * this file can be copied to /templates/your_template_dir/pagename<br />
 * example: to override the privacy page<br />
 * make a directory /templates/my_template/privacy<br />
 * copy /templates/templates_defaults/common/tpl_footer.php to /templates/my_template/privacy/tpl_header.php<br />
 * to override the global settings and turn off the footer un-comment the following line:<br />
 * <br />
 * $flag_disable_header = true;<br />
 *
 * @package templateSystem
 * @copyright Copyright 2003-2006 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: tpl_header.php 4813 2006-10-23 02:13:53Z drbyte $
 */
?>

<?php
  // Display all header alerts via messageStack:
  if ($messageStack->size('header') > 0) {
    echo $messageStack->output('header');
  }
  if (isset($_GET['error_message']) && zen_not_null($_GET['error_message'])) {
  echo htmlspecialchars(urldecode($_GET['error_message']));
  }
  if (isset($_GET['info_message']) && zen_not_null($_GET['info_message'])) {
   echo htmlspecialchars($_GET['info_message']);
} else {

}
?>


<!--bof-header logo and navigation display-->
<?php
if (!isset($flag_disable_header) || !$flag_disable_header) {
?>
<!--=========== new header================-->

<header class="header">
  <div class="container">
  <div class="inset">
    <div class="col-md-3 col-sm-3 col-xs-5"><a href="#"><img src="images/Logo.png" alt="Boscoyo" title="Boscoyo" class="img-responsive logo"></a></div>
    <div class="col-md-6 col-sm-9 col-xs-7">
      <div id="mo-header" class="cd-main-header">
        <ul class="cd-header-buttons">
          <li><a class="cd-nav-trigger" href="#cd-primary-nav"><span></span></a></li>
        </ul>
      </div>
      <main class="cd-main-content"> </main>
      <div class="cd-overlay"></div>
      <nav class="cd-nav" style="float:left;">
        <ul id="cd-primary-nav" class="nav cd-primary-nav is-fixed">
          <li><a href="#" class="active">Home</a></li>
          <li><a href="#">Gallery</a></li>
          <li><a href="#">Contact Us </a></li>
          <li><a href="http://localhost/zen/products/index.php?main_page=products_all">Products</a></li>
        </ul>
      </nav>
      <?php if ($_SESSION['customer_id']) { ?>
          <a href="<?php echo zen_href_link(FILENAME_LOGOFF, '', 'SSL'); ?>"><?php echo HEADER_TITLE_LOGOFF; ?></a> 
          <a href="<?php echo zen_href_link(FILENAME_ACCOUNT, '', 'SSL'); ?>"><?php echo HEADER_TITLE_MY_ACCOUNT; ?></a> 
        <?php
          } else {
            if (STORE_STATUS == '0') { ?> 
      <div class="cart-sec">
         <a href="<?php echo zen_href_link(FILENAME_LOGIN, '', 'SSL'); ?>" class="admin"><i class="fa fa-user" aria-hidden="true"></i></a>
        <?php } } ?>
       <a href="<?php echo zen_href_link(FILENAME_SHOPPING_CART, '', 'NONSSL'); ?>" class="cart"><i class="fa fa-shopping-cart" aria-hidden="true"></i> <span class="items"> <?php echo $_SESSION['cart']->count_contents();?> </span></a> </div>     </div>



       
    </div>   
  </div>
</header> <!--========= new header end=============-->

<!--Custom header BoscoyoStudio.com-->

		<div class="sixteen columns">
     </div>
        <div class="sixteen columns">
            <div class="NAV">
            <a href="#">Home</a> <span class="Divider">|</span>
            <a href="#">Gallery</a> <span class="Divider">|</span>
            <a href="#">Contact Us</a> <span class="Divider">|</span>
            <?php //echo '<a href="' . HTTP_SERVER . DIR_WS_CATALOG . 'index.php?main_page=products_all">'; ?><!--Products</a> <span class="Divider">|</span>-->
             <?php  echo '<a href="http://localhost/zen/products/index.php?main_page=products_all">'; ?>Products</a> <span class="Divider">|</span>
              
  			<?php if ($_SESSION['customer_id']) { ?>
  				<a href="<?php echo zen_href_link(FILENAME_LOGOFF, '', 'SSL'); ?>"><?php echo HEADER_TITLE_LOGOFF; ?></a> <span class="Divider">|</span>
  				<a href="<?php echo zen_href_link(FILENAME_ACCOUNT, '', 'SSL'); ?>"><?php echo HEADER_TITLE_MY_ACCOUNT; ?></a> <span class="Divider">|</span>
  			<?php
  				} else {
  					if (STORE_STATUS == '0') { ?>
  						<a href="<?php echo zen_href_link(FILENAME_LOGIN, '', 'SSL'); ?>"><?php echo HEADER_TITLE_LOGIN; ?></a> <span class="Divider">|</span>
  			<?php } } ?>
			<a href="<?php echo zen_href_link(FILENAME_SHOPPING_CART, '', 'NONSSL'); ?>"><img src="<?php  echo $template->get_template_dir('',DIR_WS_TEMPLATE, $current_page_base,'images').'/'.HEADER_ICON_CART ?>" alt="cart icon" class="cart-icon" /><?php echo $_SESSION['cart']->count_contents();?> item(s) - <?php echo $currencies->format($_SESSION['cart']->show_total());?></a> <span class="Divider">|</span>			
            <hr />
			</div>
        </div>
		
<!--bof-branding display-->

<?php if (HEADER_SALES_TEXT != '' || (SHOW_BANNERS_GROUP_SET2 != '' && $banner = zen_banner_exists('dynamic', SHOW_BANNERS_GROUP_SET2))) { ?>

</div>

<?php
              if (SHOW_BANNERS_GROUP_SET2 != '' && $banner = zen_banner_exists('dynamic', SHOW_BANNERS_GROUP_SET2)) {
                if ($banner->RecordCount() > 0) {
?>
      <div id="bannerTwo" class="banners"><?php echo zen_display_banner('static', $banner);?></div>
<?php
                }
              }
?>
    
<?php } // no HEADER_SALES_TEXT or SHOW_BANNERS_GROUP_SET2 ?>


<!--eof-branding display-->

<!--eof-header logo and navigation display-->

<!--bof-navigation display-->

<!--eof-navigation display-->



<!--bof-optional categories tabs navigation display-->
<?php require($template->get_template_dir('tpl_modules_categories_tabs.php',DIR_WS_TEMPLATE, $current_page_base,'templates'). '/tpl_modules_categories_tabs.php'); ?>
<!--eof-optional categories tabs navigation display-->

<!--bof-header ezpage links-->
<?php if (EZPAGES_STATUS_HEADER == '1' or (EZPAGES_STATUS_HEADER == '2' and (strstr(EXCLUDE_ADMIN_IP_FOR_MAINTENANCE, $_SERVER['REMOTE_ADDR'])))) { ?>
<?php require($template->get_template_dir('tpl_ezpages_bar_header.php',DIR_WS_TEMPLATE, $current_page_base,'templates'). '/tpl_ezpages_bar_header.php'); ?>
<?php } ?>
<!--eof-header ezpage links-->
</div>
<?php } ?>