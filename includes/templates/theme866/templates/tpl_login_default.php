<?php
/**
 * Page Template
 *
 * @package templateSystem
 * @copyright Copyright 2003-2014 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version GIT: $Id: Author: DrByte  Wed Dec 18 14:20:36 2013 -0500 Modified in v1.5.3 $
 */
?>

<div class="centerColumn" id="loginDefault">

<div class="heading"><h1><?php echo HEADING_TITLE; ?></h1></div>

<div class="tie">
	<div class="tie-indent">
	



    <?php if ( USE_SPLIT_LOGIN_MODE == 'True' || $ec_button_enabled) { ?>
    <!--BOF PPEC split login- DO NOT REMOVE-->
    <fieldset class="floatingBox back">
        <legend><?php echo HEADING_NEW_CUSTOMER_SPLIT; ?></legend>
        <?php // ** BEGIN PAYPAL EXPRESS CHECKOUT ** ?>
        <?php if ($ec_button_enabled) { ?>
        <div class="information"><?php echo TEXT_NEW_CUSTOMER_INTRODUCTION_SPLIT; ?></div>
        
          <div class="center"><?php require(DIR_FS_CATALOG . DIR_WS_MODULES . 'payment/paypal/tpl_ec_button.php'); ?></div>
        <hr />
        <?php echo TEXT_NEW_CUSTOMER_POST_INTRODUCTION_DIVIDER; ?>
        <?php } ?>
        <?php // ** END PAYPAL EXPRESS CHECKOUT ** ?>
        <div class="information"><?php echo TEXT_NEW_CUSTOMER_POST_INTRODUCTION_SPLIT; ?></div>
        
        <?php echo zen_draw_form('create', zen_href_link(FILENAME_CREATE_ACCOUNT, '', 'SSL')); ?>
        <div class="buttonRow forward"><?php echo zen_image_submit(BUTTON_IMAGE_CREATE_ACCOUNT, BUTTON_CREATE_ACCOUNT_ALT); ?></div>
        </form>
    </fieldset>
    <fieldset class="floatingBox forward">
        <legend><?php echo HEADING_RETURNING_CUSTOMER_SPLIT; ?></legend>
        <div class="information"><?php echo TEXT_RETURNING_CUSTOMER_SPLIT; ?></div>

        <?php echo zen_draw_form('login', zen_href_link(FILENAME_LOGIN, 'action=process', 'SSL'), 'post', 'id="loginForm"'); ?>
        <label class="inputLabel" for="login-email-address"><?php echo ENTRY_EMAIL_ADDRESS; ?></label>
        <?php echo zen_draw_input_field('email_address', '', 'size="18" id="login-email-address"'); ?>
        <br class="clearBoth" />
    
        <label class="inputLabel" for="login-password"><?php echo ENTRY_PASSWORD; ?></label>
        <?php echo zen_draw_password_field('password', '', 'size="18" id="login-password"'); ?>

        <br class="clearBoth" />
    
        <div class="buttonRow forward"><?php echo zen_image_submit(BUTTON_IMAGE_LOGIN, BUTTON_LOGIN_ALT); ?></div>
        <div class="buttonRow back important"><?php echo '<a href="' . zen_href_link(FILENAME_PASSWORD_FORGOTTEN, '', 'SSL') . '">' . TEXT_PASSWORD_FORGOTTEN . '</a>'; ?></div>
        </form>
    </fieldset>
    <br class="clearBoth" />
<!--EOF PPEC split login- DO NOT REMOVE-->
<?php } else { ?>
<!--BOF normal login-->
<?php
  if ($_SESSION['cart']->count_contents() > 0) {
?>
<div class="advisory"><?php echo TEXT_VISITORS_CART; ?></div>
<?php
  }
?>
<div class="form-control-block">
    <?php echo zen_draw_form('login', zen_href_link(FILENAME_LOGIN, 'action=process', 'SSL')); ?>
    <fieldset class="first">
        <legend><?php echo HEADING_RETURNING_CUSTOMER; ?></legend>
    </fieldset>
    <div class="form-group">
        <label class="inputLabel" for="login-email-address"><?php echo ENTRY_EMAIL_ADDRESS; ?></label>
        <?php echo zen_draw_input_field('email_address', '', zen_set_field_length(TABLE_CUSTOMERS, 'customers_email_address', '40') . ' id="login-email-address" class="form-control"'); ?>
    </div>
    <div class="form-group">
    <label class="inputLabel" for="login-password"><?php echo ENTRY_PASSWORD; ?></label>
    <?php echo zen_draw_password_field('password', '', zen_set_field_length(TABLE_CUSTOMERS, 'customers_password') . ' id="login-password" class="form-control"'); ?>
    </div>
    <?php echo zen_draw_hidden_field('securityToken', $_SESSION['securityToken']); ?>
    <div class="form-group">
        <div class="buttonRow back asd1"><?php echo zen_image_submit('', BUTTON_LOGIN_ALT); ?></div>
        <div class="buttonRow back"><?php echo '<a class="btn" href="' . zen_href_link(FILENAME_PASSWORD_FORGOTTEN, '', 'SSL') . '">' . TEXT_PASSWORD_FORGOTTEN . '</a>'; ?></div>
    </div>
    </form>
</div>
<br class="clearBoth" />

    <?php echo zen_draw_form('create_account', zen_href_link(FILENAME_CREATE_ACCOUNT, '', 'SSL'), 'post', 'onsubmit="return check_form(create_account);"') . zen_draw_hidden_field('action', 'process') . zen_draw_hidden_field('email_pref_html', 'email_format'); ?>
    <fieldset class="second">
    <legend><?php echo HEADING_NEW_CUSTOMER; ?></legend>
    </fieldset>
    <div class="information"><?php echo TEXT_NEW_CUSTOMER_INTRODUCTION; ?></div>
    
    <?php require($template->get_template_dir('tpl_modules_create_account.php',DIR_WS_TEMPLATE, $current_page_base,'templates'). '/tpl_modules_create_account.php'); ?>
    
    <div class="buttonRow"><?php echo zen_image_submit('', BUTTON_SUBMIT_ALT); ?></div>
    </form>

<!--EOF normal login-->
<?php } ?>
<div class="clear"></div>
	</div>
</div>

</div>