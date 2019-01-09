<?php
/**
 * Common Template - tpl_main_page.php
 *
 * Governs the overall layout of an entire page<br />
 * Normally consisting of a header, left side column. center column. right side column and footer<br />
 * For customizing, this file can be copied to /templates/your_template_dir/pagename<br />
 * example: to override the privacy page<br />
 * - make a directory /templates/my_template/privacy<br />
 * - copy /templates/templates_defaults/common/tpl_main_page.php to /templates/my_template/privacy/tpl_main_page.php<br />
 * <br />
 * to override the global settings and turn off columns un-comment the lines below for the correct column to turn off<br />
 * to turn off the header and/or footer uncomment the lines below<br />
 * Note: header can be disabled in the tpl_header.php<br />
 * Note: footer can be disabled in the tpl_footer.php<br />
 * <br />
 * $flag_disable_header = true;<br />
 * $flag_disable_left = true;<br />
 * $flag_disable_right = true;<br />
 * $flag_disable_footer = true;<br />
 * <br />
 * // example to not display right column on main page when Always Show Categories is OFF<br />
 * <br />
 * if ($current_page_base == 'index' and $cPath == '') {<br />
 *  $flag_disable_right = true;<br />
 * }<br />
 * <br />
 * example to not display right column on main page when Always Show Categories is ON and set to categories_id 3<br />
 * <br />
 * if ($current_page_base == 'index' and $cPath == '' or $cPath == '3') {<br />
 *  $flag_disable_right = true;<br />
 * }<br />
 *
 * @package templateSystem
 * @copyright Copyright 2003-2016 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: Author: DrByte  Fri Jan 8 14:09:25 2016 -0500 Modified in v1.5.5 $
 */

/** bof DESIGNER TESTING ONLY: */
// $messageStack->add('header', 'this is a sample error message', 'error');
// $messageStack->add('header', 'this is a sample caution message', 'caution');
// $messageStack->add('header', 'this is a sample success message', 'success');
// $messageStack->add('main', 'this is a sample error message', 'error');
// $messageStack->add('main', 'this is a sample caution message', 'caution');
// $messageStack->add('main', 'this is a sample success message', 'success');
/** eof DESIGNER TESTING ONLY */



// the following IF statement can be duplicated/modified as needed to set additional flags
/*
if(isset($_GET['main_page']) and $_GET['main_page']=='products_all'){
?>
<style>
.slider{display: none;}
</style>
<?php
}*/



if ($this_is_home_page) {
     $flag_disable_left = true;
  }
  if (in_array($current_page_base,explode(",",'list_pages_to_skip_all_right_sideboxes_on_here,separated_by_commas,and_no_spaces')) ) {
    $flag_disable_right = true;

  }


  $header_template = 'tpl_header.php';
  $footer_template = 'tpl_footer.php';
  $left_column_file = 'column_left.php';
  $right_column_file = 'column_right.php';
  $body_id = ($this_is_home_page) ? 'indexHome' : str_replace('_', '', $_GET['main_page']);
?>
<body id="<?php echo $body_id . 'Body'; ?>"<?php if($zv_onload !='') echo ' onload="'.$zv_onload.'"'; ?>>
<?php
  if (SHOW_BANNERS_GROUP_SET1 != '' && $banner = zen_banner_exists('dynamic', SHOW_BANNERS_GROUP_SET1)) {
    if ($banner->RecordCount() > 0) {
?>
<div id="bannerOne" class="banners"><?php echo zen_display_banner('static', $banner); ?></div>
<?php
    }
  }
?>

<div id="mainWrapper">
<?php
 /**
  * prepares and displays header output
  *
  */
  if (CUSTOMERS_APPROVAL_AUTHORIZATION == 1 && CUSTOMERS_AUTHORIZATION_HEADER_OFF == 'true' and ($_SESSION['customers_authorization'] != 0 or $_SESSION['customer_id'] == '')) {
    $flag_disable_header = true;
  }
  require($template->get_template_dir('tpl_header.php',DIR_WS_TEMPLATE, $current_page_base,'common'). '/tpl_header.php');?>
<?php 
if(isset($_COOKIE['user']) && !empty($_COOKIE['user'])){
	$product= $_COOKIE['user'];
}else{
	$product= "";
}


$sql_selected = $db->Execute("select * from zen_slider where slider_value = '$product'");
$products = $sql_selected->fields['selected_products'];
if(trim(strlen($products)) == 0){
	$get_products = $db->Execute("select products_id from zen_products where products_status = 1 order by (zen_products.products_id) desc limit 10 ");
	$product = $get_products->fields;
	while(!$get_products->EOF) {
		$products = $products.",".$get_products->fields['products_id'];
		$get_products->MoveNext();		
	}
	$products = trim($products,',');
}	

$sql_products = $db->Execute("select * from zen_products left join zen_categories on zen_products.master_categories_id = zen_categories.categories_id left join zen_products_description ON zen_products.products_id = zen_products_description.products_id where zen_products.products_id in ($products)");

$items = array();
while(!$sql_products->EOF) {
	$rows_products[] = $sql_products->fields;
	$sql_products->MoveNext();
}
?>

<div class="slider">
  <div id="da-slider" class="da-slider">
	  <?php foreach($rows_products as $sliders){ ?>
		<div class="da-slide bg">
			<a href="index.php?main_page=product_info&cPath=<?php echo @$sliders['parent_id']; ?>_<?php echo @$sliders['master_categories_id']; ?>&products_id=<?php echo @$sliders['products_id']; ?>"> 
				<div class="da-img">
					<img style='width:290px;height:290px;' src="images/<?php echo @$sliders['products_image']; ?>" alt="<?php echo @$sliders['products_name']; ?>" />
				</div>
			</a>
			<h2><?php echo substr($sliders['products_name'],0,30); if(strlen($sliders['products_name']) >= 30){ "..."; } ?></h2>
			<p class="price">$<?php echo number_format($sliders['products_price'],2, '.' , ' '); ?> </p>

			


			<a href="index.php?main_page=product_info&cPath=<?php echo @$sliders['parent_id']; ?>_<?php echo @$sliders['master_categories_id']; ?>&products_id=<?php echo @$sliders['products_id']; ?>" class="da-link">Shop Now</a> 
		</div>	 
	  <?php } ?> 
	<nav class="da-arrows"> <span class="da-arrows-prev"></span> <span class="da-arrows-next"></span> </nav>
  </div>
</div>

<!--================= slider for main page=========-->

<!--======================= button html ====================-->



<section class="content-area">
  <div class="container">
    <div class="row">
      <div class="col-md-3 col-sm-3 col-xs-6">
        <form method="post" action="action.php" name="christmas" id="christmas-design" >
          <input type="hidden" name="template_dir" value="christmas">
          <!--<div class="chr-btn theem" id="christmas-button" class="chris-button"><a href="#">Christmas</a></div>-->
          <button type="submit" name="submit"  class="chr-btn theem" id="christmas-button" class="chris-button" value=""><div class="border_chr">Christmas</div></button>
        </form>
      </div>
      <div class="col-md-3 col-sm-3 col-xs-6">
        <form method="post" action="action.php" name="halloween" id="halloween-design">
          <input type="hidden" name="template_dir" value="halloween">
          <!--<div class="chr-btn theem" id="christmas-button" class="chris-button"><a href="#">Christmas</a></div>-->
          <button type="submit" name="submit_helloween"  class="hall-btn theem" id="christmas-button" class="chris-button" value=""><div class="border_chr">Halloween</div></button>
        </form>
        <!--<div class="hall-btn theem"><a href="#">Halloween</a></div>-->
      </div>
      <div class="col-md-3 col-sm-3 col-xs-6">
        <form method="post" action="action.php" name="othemes" id="othemes-design">
          <input type="hidden" name="template_dir" value="othemes">
          <!--<div class="chr-btn theem" id="christmas-button" class="chris-button"><a href="#">Christmas</a></div>-->
          <button type="submit" name="submit_themes"  class="oth-btn theem" id="christmas-button" class="chris-button" value=""><div class="border_chr">Other Themes</div></button>
        </form>
        <!--<div class="oth-btn theem"><a href="#">Other Themes</a></div>-->
      </div>
      <div class="col-md-3 col-sm-3 col-xs-6">
        <form method="post" action="action.php" name="oprojects" id="oprojects-design">
          <input type="hidden" name="template_dir" value="oprojects">
          <!--<div class="chr-btn theem" id="christmas-button" class="chris-button"><a href="#">Christmas</a></div>-->
          <button type="submit" name="submit_projects"  class="oth-p-btn theem" id="christmas-button" class="chris-button" value="Other Projects"><div class="border_chr">Other Projects</div></button>
        </form>
       <!-- <div class="oth-p-btn theem"><a href="#">Other Projects</a></div>-->
      </div>
    </div>
</secton>
<!--======================= button end====================-->




<table width="100%" border="0" cellspacing="0" cellpadding="0" id="contentMainWrapper">
  <tr>
<?php
if (COLUMN_LEFT_STATUS == 0 || (CUSTOMERS_APPROVAL == '1' and $_SESSION['customer_id'] == '') || (CUSTOMERS_APPROVAL_AUTHORIZATION == 1 && CUSTOMERS_AUTHORIZATION_COLUMN_LEFT_OFF == 'true' and ($_SESSION['customers_authorization'] != 0 or $_SESSION['customer_id'] == ''))) {
  // global disable of column_left
  $flag_disable_left = true;
}
if (!isset($flag_disable_left) || !$flag_disable_left) {
?>

 <td id="navColumnOne" class="columnLeft" style="width: <?php echo COLUMN_WIDTH_LEFT; ?>">
<?php
 /**
  * prepares and displays left column sideboxes
  *
  */
?>
<div id="navColumnOneWrapper" style="width: <?php echo BOX_WIDTH_LEFT; ?>"><?php require(DIR_WS_MODULES . zen_get_module_directory('column_left.php')); ?></div></td>
<?php
}
?>
    <td valign="top">
<!-- bof  breadcrumb -->
<?php if (DEFINE_BREADCRUMB_STATUS == '1' || (DEFINE_BREADCRUMB_STATUS == '2' && !$this_is_home_page) ) { ?>
    <div id="navBreadCrumb"><?php echo $breadcrumb->trail(BREAD_CRUMBS_SEPARATOR); ?></div>
<?php } ?>
<!-- eof breadcrumb -->

<?php
  if (SHOW_BANNERS_GROUP_SET3 != '' && $banner = zen_banner_exists('dynamic', SHOW_BANNERS_GROUP_SET3)) {
    if ($banner->RecordCount() > 0) {
?>
<div id="bannerThree" class="banners"><?php echo zen_display_banner('static', $banner); ?></div>
<?php
    }
  }
?>

<!-- bof upload alerts -->
<?php if ($messageStack->size('upload') > 0) echo $messageStack->output('upload'); ?>
<!-- eof upload alerts -->

<?php
 /**
  * prepares and displays center column
  *
  */
 require($body_code); ?>

<?php
  if (SHOW_BANNERS_GROUP_SET4 != '' && $banner = zen_banner_exists('dynamic', SHOW_BANNERS_GROUP_SET4)) {
    if ($banner->RecordCount() > 0) {
?>
<div id="bannerFour" class="banners"><?php echo zen_display_banner('static', $banner); ?></div>
<?php
    }
  }
?></td>

<?php
//if (COLUMN_RIGHT_STATUS == 0 || (CUSTOMERS_APPROVAL == '1' and $_SESSION['customer_id'] == '') || (CUSTOMERS_APPROVAL_AUTHORIZATION == 1 && CUSTOMERS_AUTHORIZATION_COLUMN_RIGHT_OFF == 'true' && $_SESSION['customers_authorization'] != 0)) {
if (COLUMN_RIGHT_STATUS == 0 || (CUSTOMERS_APPROVAL == '1' and $_SESSION['customer_id'] == '') || (CUSTOMERS_APPROVAL_AUTHORIZATION == 1 && CUSTOMERS_AUTHORIZATION_COLUMN_RIGHT_OFF == 'true' and ($_SESSION['customers_authorization'] != 0 or $_SESSION['customer_id'] == ''))) {
  // global disable of column_right
  $flag_disable_right = true;
}
if (!isset($flag_disable_right) || !$flag_disable_right) {
?>
<td id="navColumnTwo" class="columnRight" style="width: <?php echo COLUMN_WIDTH_RIGHT; ?>">
<?php
 /**
  * prepares and displays right column sideboxes
  *
  */
?>
<div id="navColumnTwoWrapper" style="width: <?php echo BOX_WIDTH_RIGHT; ?>"><?php require(DIR_WS_MODULES . zen_get_module_directory('column_right.php')); ?></div></td>
<?php
}
?>
  </tr>
</table>

<?php
 /**
  * prepares and displays footer output
  *
  */
  if (CUSTOMERS_APPROVAL_AUTHORIZATION == 1 && CUSTOMERS_AUTHORIZATION_FOOTER_OFF == 'true' and ($_SESSION['customers_authorization'] != 0 or $_SESSION['customer_id'] == '')) {
    $flag_disable_footer = true;
  }
  require($template->get_template_dir('tpl_footer.php',DIR_WS_TEMPLATE, $current_page_base,'common'). '/tpl_footer.php');
?>

</div>
<!--bof- parse time display -->
<?php
  if (DISPLAY_PAGE_PARSE_TIME == 'true') {
?>
<div class="smallText center">Parse Time: <?php echo $parse_time; ?> - Number of Queries: <?php echo $db->queryCount(); ?> - Query Time: <?php echo $db->queryTime(); ?></div>
<?php
  }
?>
<!--eof- parse time display -->
<!--bof- banner #6 display -->
<?php
  if (SHOW_BANNERS_GROUP_SET6 != '' && $banner = zen_banner_exists('dynamic', SHOW_BANNERS_GROUP_SET6)) {
    if ($banner->RecordCount() > 0) {
?>
<div id="bannerSix" class="banners"><?php echo zen_display_banner('static', $banner); ?></div>
<?php
    }
  }
?>
<!--eof- banner #6 display -->

<?php /* add any end-of-page code via an observer class */
  $zco_notifier->notify('NOTIFY_FOOTER_END', $current_page);

?>
</body>
