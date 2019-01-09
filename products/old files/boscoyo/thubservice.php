<?php

  #-------------------------------------------#
  #                                           #
  #       PHP QuickBooks Service for Zen Cart #
  #       Copyright (c) 2006                  #
  #       Atandra LLC.                        #
  #       www.atandra.com                     #
  #-------------------------------------------#

  // include_once('_error_handler.inc.php');
  // error_reporting(E_ALL);

  chdir('../');

  header("Content-type: application/xml");
  error_reporting(E_ALL);

  if (!isset($request)) {
    if (isset($_POST))
      $request = $_POST['request'];
    else
      $request = $HTTP_POST_VARS['request'];
  }

  DEFINE('__DEBUG', 0);
  DEFINE('__LANGUAGE_ID', 1);
  DEFINE('__DELIVERED_STATUS_ID', 5);
  DEFINE('__DELIVERED_STATUS_NAME', 'Shipped');
  DEFINE('__BACKORDER_STATUS_ID', 1);
  DEFINE('__BACKORDER_STATUS_NAME', 'Back Order');

  DEFINE('__PROCESSING_STATUS_ID', 2);
  DEFINE('__PROCESSING_STATUS_NAME', 'Processing');

  DEFINE('__ENCODE_RESPONSE', true);
  DEFINE('IS_ADMIN_FLAG', 1);
  DEFINE('REL_PATH', "");
  require(REL_PATH.'includes/configure.php');

  if (isset($HTTP_SERVER_VARS))
    $PHP_SELF = (isset($HTTP_SERVER_VARS['PHP_SELF']) ? $HTTP_SERVER_VARS['PHP_SELF'] : $HTTP_SERVER_VARS['SCRIPT_NAME']);
  else
    $PHP_SELF = (isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME']);
  DEFINE('LOCAL_EXE_GZIP', '/usr/bin/gzip');
  DEFINE('LOCAL_EXE_GUNZIP', '/usr/bin/gunzip');
  DEFINE('LOCAL_EXE_ZIP', '/usr/local/bin/zip');
  DEFINE('LOCAL_EXE_UNZIP', '/usr/local/bin/unzip');

  require(REL_PATH.DIR_WS_FUNCTIONS . 'functions_general.php');
  // require("admin/".REL_PATH.DIR_WS_FUNCTIONS . 'html_output.php');

  if (file_exists(REL_PATH.DIR_WS_FUNCTIONS . 'password_funcs.php')) require(REL_PATH.DIR_WS_FUNCTIONS . 'password_funcs.php');

  require(REL_PATH.DIR_WS_INCLUDES . 'filenames.php');
  require(REL_PATH.DIR_WS_INCLUDES . 'database_tables.php');

  require(REL_PATH.'thubxml.php');
  require(REL_PATH.'includes/classes/class.base.php');
  require(REL_PATH.'includes/classes/db/' .DB_TYPE . '/query_factory.php');




  $db = new queryFactory();
  if ( !$db->connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE, USE_PCONNECT, false))
  {
    print(xmlErrorResponse('DB', '9999', 'can`t connect to db', STORE_NAME, ''));flush();exit();
  }

  $configuration_query = $db->Execute('select configuration_key as cfgKey, configuration_value as cfgValue from ' . TABLE_CONFIGURATION);
  while (!$configuration_query->EOF)
  {
    define($configuration_query->fields['cfgKey'], $configuration_query->fields['cfgValue']);
    $configuration_query->MoveNext();
  }

  require(REL_PATH.DIR_WS_CLASSES . 'split_page_results.php');
  include(REL_PATH.DIR_WS_CLASSES . 'language.php');
  $lng = new language(DEFAULT_LANGUAGE);
  $languages_id = $lng->language['id'];
  $languages_name = $lng->language['directory'];

  $lang_orders_path = "admin/" . DIR_WS_LANGUAGES . $languages_name . '/orders.php';
  if (file_exists($lang_orders_path)) require($lang_orders_path);



//print($lang_orders_path);
  //  *****************************************************
  //  AUTO INSTALLATION


 // if (!isTHUBInstalled())
 //   if ($iresult = THUBInstall()) {
 //     print(xmlErrorResponse('unknown', '9999', 'T-HUB Installation failed.('.$iresult.')', STORE_NAME, ''));
 //     flush; exit;
 //   };
  //  *****************************************************


  if (empty($request)) exit;

  // parse XML Request
  $request = trim(stripcslashes($request));
  // print($request);
  $xmlRequest = new xml_doc($request);
  $xmlRequest->parse();

  $xmlRequest->getTag(0, $_tagName, $_tagAttributes, $_tagContents, $_tagTags);
  //$xmlRequest->getChildByName(0, "REQUEST")
  // print($_tagName);
  // print_r($_tagTags);

  if (strtoupper(trim($_tagName)) != 'REQUEST') {
    print(xmlErrorResponse('unknown', '9999', 'Unknown request', STORE_NAME, ''));flush();exit();
  }
  if (count($_tagTags) == 0) {
    print(xmlErrorResponse('unknown', '9999', 'REQUEST tag doesnt have necessry parameters', STORE_NAME, ''));flush();exit();
  }

  $RequestParams = Array();
  foreach ($_tagTags as $k=>$v){
    $xmlRequest->getTag($v, $tN, $tA, $tC, $tT);
    $RequestParams[strtoupper($tN)] = trim($tC);
  }

  if (!isset($RequestParams['COMMAND'])) {
    print(xmlErrorResponse('unknown', '9999', 'Command is not set', STORE_NAME, '')); exit();
  }
  $RequestParams['COMMAND'] = strtoupper($RequestParams['COMMAND']);

  // print($RequestParams['COMMAND']);
  if (($RequestParams['COMMAND']!='GETORDERS')
  		&& ($RequestParams['COMMAND']!='UPDATEORDERS')
			&& ($RequestParams['COMMAND'] != ('UPDATE'.'ORDERS'.'SHIPPING'.'STATUS'))
            && ($RequestParams['COMMAND'] != ('UPDATE'.'INVENTORY'))
  		) {
    print(xmlErrorResponse('unknown', '9999', 'Unknown Command', STORE_NAME, ''));exit;
  }
  if (isset($RequestParams['REQUESTID'])) $request_id = $RequestParams['REQUESTID'];

  // check for installed admin auth contribution and do authentification.
  if (!isset($RequestParams['USERID']))     {print(xmlErrorResponse($RequestParams['COMMAND'], '9000', 'UserID field is not set', STORE_NAME, ''));exit;}
  if (!isset($RequestParams['PASSWORD']))   {print(xmlErrorResponse($RequestParams['COMMAND'], '9000', 'Password field is not set', STORE_NAME, ''));exit;}

  $auth = false;


  $sql_str = "SELECT * FROM ".TABLE_ADMIN." WHERE  admin_name = '".zen_db_input($RequestParams['USERID'])."'";
  $customers_query = $db->Execute($sql_str);

  if (!$customers_query->EOF)
  {
    $auth = zen_validate_password($RequestParams['PASSWORD'], $customers_query->fields['admin_pass']);
  }
//    if (!zen_validate_password($RequestParams['PASSWORD'], $customers_query->fields['admin_pass'])) {

  if (!$auth){
     print(xmlErrorResponse($RequestParams['COMMAND'], '9000', 'Order download service authentication failure - Login/Password supplied did not match', STORE_NAME, ''));
     exit;
  }


  // if (isset($RequestParams['SECURITYKEY'])) {
    // print(xmlErrorResponse($RequestParams['COMMAND'], '9999', 'Sequrity key is not supported in this version', STORE_NAME, ''));flush;exit;
  // }

  if (isset($RequestParams['LIMITORDERCOUNT']))
    define("QB_ORDERS_PER_RESPONSE", $RequestParams['LIMITORDERCOUNT']);
  else
    define("QB_ORDERS_PER_RESPONSE", 25);

  if (isset($RequestParams['NUMBEROFDAYS']))
    define('QB_NUMBER_OF_DAYS', $RequestParams['NUMBEROFDAYS']);
  else
    define('QB_NUMBER_OF_DAYS', 5);

  if (isset($RequestParams['ORDERSTARTNUMBER']))
    define('QB_ORDER_START_NUMBER', $RequestParams['ORDERSTARTNUMBER']);
  else
    define('QB_ORDER_START_NUMBER', 0);


  if (isset($RequestParams['EXCLUDE-ORDERS'])) {
    $exclude_orders = trim($RequestParams['EXCLUDE-ORDERS']);
    if (empty($exclude_orders)) unset($exclude_orders);
  }

  /* $xmlResponse = new xml_doc('<?xml version="1.0" encoding="UTF-8"?>'); */
  $xmlResponse = new xml_doc();
  $xmlResponse->version='1.0';
  $xmlResponse->encoding='ISO-8859-1';
  //$xmlResponse->encoding='UTF-8';

  $root = $xmlResponse->createTag("RESPONSE", array('Version'=>'1.1'));
  $envelope = $xmlResponse->createTag("Envelope", array(), '', $root);
  $xmlResponse->createTag("Command", array(), $RequestParams['COMMAND'], $envelope);

  switch ($RequestParams['COMMAND']){
    case 'GETORDERS' : {
		  if (isset($request_id)) $xmlResponse->createTag("RequestID", array(), $request_id, $envelope);
		  $xmlResponse->createTag("StoreID",   array(), STORE_NAME, $envelope, __ENCODE_RESPONSE);
		  $xmlResponse->createTag("StoreName", array(), STORE_NAME, $envelope, __ENCODE_RESPONSE);

//     $sql_str = "
//        SELECT
//          COUNT(*) cnt
//        FROM " . TABLE_ORDERS . "
//        WHERE orders_id >".QB_ORDER_START_NUMBER."
//         AND to_days(Now()) - TO_DAYS(date_purchased) < ".QB_NUMBER_OF_DAYS."
//        ".(isset($exclude_orders)?" AND orders_id NOT IN ($exclude_orders) ":"")."
//        ".(QB_ORDERS_PER_RESPONSE>0?"LIMIT 0, ".QB_ORDERS_PER_RESPONSE:'');

		 	$str_date_filter = "";
		 	if (QB_ORDER_START_NUMBER == 0) {
		 		$str_date_filter = " AND to_days(Now()) - TO_DAYS(o.date_purchased) <= ".QB_NUMBER_OF_DAYS;
			}
           $sql_str = "
              SELECT
                COUNT(*) cnt
              FROM " . TABLE_ORDERS . " o
              WHERE o.orders_id >".QB_ORDER_START_NUMBER."
               ".$str_date_filter."
              ".(QB_ORDERS_PER_RESPONSE>0?"LIMIT 0, ".QB_ORDERS_PER_RESPONSE:'');


      $orders_query = $db->Execute($sql_str);
      $no_orders = false;
      if ($orders_query->fields['cnt']==0) {
        $no_orders = true;
      }

      $xmlResponse->createTag("StatusCode", array(), $no_orders?"1000":"0", $envelope);
      $xmlResponse->createTag("StatusMessage", array(), $no_orders?"No Orders returned":"All Ok", $envelope);

      if ($no_orders){
        print($xmlResponse->generate()); flush(); exit();
      }
      $ordersNode = $xmlResponse->createTag("Orders", array(), '', $root);

      // include(DIR_WS_CLASSES . 'order.php');

      $orders_query_raw = "
        SELECT
          o.*,
          osh.comments,
          SUM(ot.value) AS orders_total
        FROM ".TABLE_ORDERS." o
        LEFT JOIN ".TABLE_ORDERS_STATUS_HISTORY." osh ON o.orders_status = osh.orders_status_id AND o.orders_id = osh.orders_id
        LEFT JOIN ".TABLE_ORDERS_TOTAL." ot ON o.orders_id = ot.orders_id
        WHERE o.orders_id >".QB_ORDER_START_NUMBER."
         ".$str_date_filter."
        GROUP BY o.orders_id
        ORDER BY o.orders_id
        ".(QB_ORDERS_PER_RESPONSE>0?" LIMIT 0, ".QB_ORDERS_PER_RESPONSE:'');


      $orders_query = $db->Execute($orders_query_raw);
      while (!$orders_query->EOF) {
        $orders = $orders_query->fields;
        $orders_query->MoveNext();

        $oInfo = new objectInfo($orders);
        // print_r($oInfo);
        $oInfo = parseSpecChars($oInfo);

        $orderNode  = $xmlResponse->createTag("Order",  array(), '', $ordersNode, __ENCODE_RESPONSE);
        $itemsNode  = $xmlResponse->createTag("Items",  array(), '', $orderNode, __ENCODE_RESPONSE);
        $billNode  = $xmlResponse->createTag("Bill",    array(), '', $orderNode, __ENCODE_RESPONSE);
        $shipNode  = $xmlResponse->createTag("Ship",    array(), '', $orderNode, __ENCODE_RESPONSE);
        $chargesNode  = $xmlResponse->createTag("Charges", array(), '', $orderNode, __ENCODE_RESPONSE);

        // Orders/Order info
        $xmlResponse->createTag("OrderID",  array(), $oInfo->orders_id,     $orderNode);
        $xmlResponse->createTag("Date",     array(), date("Y-m-d",strtotime($oInfo->date_purchased)), $orderNode); //date
        $xmlResponse->createTag("Time",     array(), date("H-i-s",strtotime($oInfo->date_purchased)), $orderNode); //time
        $xmlResponse->createTag("TimeZone", array(), 'PST',                 $orderNode);
        $xmlResponse->createTag("StoreId",  array(), STORE_NAME,            $orderNode, __ENCODE_RESPONSE);
        $xmlResponse->createTag("StoreName",array(), STORE_NAME,            $orderNode, __ENCODE_RESPONSE);
        $xmlResponse->createTag("Currency", array(), $oInfo->currency,      $orderNode, __ENCODE_RESPONSE);
        if (!empty($oInfo->comments)) $xmlResponse->createTag("Comment",  array(), $oInfo->comments,      $orderNode, __ENCODE_RESPONSE);

        // Orders/Bill info
        $xmlResponse->createTag("PayMethod",array(), $oInfo->payment_method,   $billNode, __ENCODE_RESPONSE);
        $xmlResponse->createTag("FirstName",array(), strtok($oInfo->billing_name, " "),   $billNode, __ENCODE_RESPONSE);
				$xmlResponse->createTag("LastName", array(), strtok(" ")." ".strtok(" ")." ".strtok(" "),   $billNode, __ENCODE_RESPONSE);

        $xmlResponse->createTag("CompanyName",array(), $oInfo->billing_company,   $billNode, __ENCODE_RESPONSE);

        $xmlResponse->createTag("Address1", array(), $oInfo->billing_street_address,   $billNode, __ENCODE_RESPONSE);
        $xmlResponse->createTag("Address2", array(), $oInfo->billing_suburb,   $billNode, __ENCODE_RESPONSE);
        $xmlResponse->createTag("City",     array(), $oInfo->billing_city,      $billNode, __ENCODE_RESPONSE);
        $xmlResponse->createTag("State",    array(), $oInfo->billing_state,     $billNode, __ENCODE_RESPONSE);
        $xmlResponse->createTag("Zip",      array(), $oInfo->billing_postcode,  $billNode, __ENCODE_RESPONSE);
        $xmlResponse->createTag("Country",  array(), $oInfo->billing_country,   $billNode, __ENCODE_RESPONSE);
        $xmlResponse->createTag("Email",    array(), $oInfo->customers_email_address,     $billNode, __ENCODE_RESPONSE);
//        $xmlResponse->createTag("Phone",    array(), $oInfo->customers_telephone,     $billNode, __ENCODE_RESPONSE);
        if (!empty($oInfo->cc_type) || (!empty($oInfo->cc_number))){
          $creditCard = $xmlResponse->createTag("CreditCard",  array(), '',   $billNode, __ENCODE_RESPONSE);

          // Orders/Bill/CreditCard info
          $xmlResponse->createTag("CreditCardType",     array(), $oInfo->cc_type,       $creditCard, __ENCODE_RESPONSE);
          $xmlResponse->createTag("CreditCardCharge",   array(), $oInfo->orders_total,  $creditCard, __ENCODE_RESPONSE);
          $xmlResponse->createTag("ExpirationDate",     array(), $oInfo->cc_expires,    $creditCard, __ENCODE_RESPONSE);
          $xmlResponse->createTag("CreditCardName",     array(), $oInfo->cc_owner,       $creditCard, __ENCODE_RESPONSE);
          $xmlResponse->createTag("CreditCardNumber",   array(), $oInfo->cc_number,     $creditCard, __ENCODE_RESPONSE);

					$auth_query_raw = "SELECT * FROM " . TABLE_AUTHORIZENET . " WHERE order_id =".$oInfo->orders_id ;
					
	        $auth_query = $db->Execute($auth_query_raw);
	        while (!$auth_query->EOF) {
	          $auth = $auth_query->fields;
	          $auth_query->MoveNext();
	
	          $authInfo = new objectInfo($auth);
	          $authInfo = parseSpecChars($authInfo);
          	$xmlResponse->createTag("AuthDetails",   array(), $authInfo->transaction_id,     $creditCard, __ENCODE_RESPONSE);
	          
					}

          //print(xmlErrorResponse('CC', '1000', $oInfo->cc_number, STORE_NAME, ''));
          //exit;

        }

        // Orders/Ship info
        $shipping_method_query = $db->Execute("SELECT title FROM " . TABLE_ORDERS_TOTAL . " WHERE orders_id = '".$oInfo->orders_id."' AND class = 'ot_shipping'");

        $shipping_method = $shipping_method_query->fields;

        $ship_method = ((substr($shipping_method['title'], -1) == ':') ? substr(strip_tags($shipping_method['title']), 0, -1) : strip_tags($shipping_method['title']));
        $ship_method = htmlspecialchars($ship_method, ENT_NOQUOTES);

        $xmlResponse->createTag("ShipMethod",array(), $ship_method,   $shipNode, __ENCODE_RESPONSE);
      $xmlResponse->createTag("FirstName",array(), strtok($oInfo->delivery_name, " "),   $shipNode, __ENCODE_RESPONSE);
      //$xmlResponse->createTag("LastName", array(), strtok(" "),   $shipNode, __ENCODE_RESPONSE);
			$xmlResponse->createTag("LastName", array(), strtok(" ")." ".strtok(" ")." ".strtok(" "),   $shipNode, __ENCODE_RESPONSE);

      $xmlResponse->createTag("CompanyName",array(), $oInfo->delivery_company,   $shipNode, __ENCODE_RESPONSE);

      $xmlResponse->createTag("Address1", array(), $oInfo->delivery_street_address,   $shipNode, __ENCODE_RESPONSE);
      $xmlResponse->createTag("Address2", array(), $oInfo->delivery_suburb,   $shipNode, __ENCODE_RESPONSE);
      $xmlResponse->createTag("City",     array(), $oInfo->delivery_city,     $shipNode, __ENCODE_RESPONSE);
      $xmlResponse->createTag("State",    array(), $oInfo->delivery_state,    $shipNode, __ENCODE_RESPONSE);
      $xmlResponse->createTag("Zip",      array(), $oInfo->delivery_postcode, $shipNode, __ENCODE_RESPONSE);
      $xmlResponse->createTag("Country",  array(), $oInfo->delivery_country,  $shipNode, __ENCODE_RESPONSE);
      $xmlResponse->createTag("Email",    array(), $oInfo->customers_email_address,    $shipNode, __ENCODE_RESPONSE);
      $xmlResponse->createTag("Phone",    array(), $oInfo->customers_telephone,    $shipNode, __ENCODE_RESPONSE);
//
        // get items of order
        // --------------------
        //$items_query_raw = "SELECT * FROM ".TABLE_ORDERS_PRODUCTS." WHERE orders_id = ".$oInfo->orders_id;
				//$items_query_raw = "SELECT o.* , p.products_weight FROM ".TABLE_ORDERS_PRODUCTS." o, ".TABLE_PRODUCTS." p WHERE o.orders_id =".$oInfo->orders_id ." AND o.products_id = p.products_id";
$items_query_raw = "SELECT o.* , p.products_weight FROM ".TABLE_ORDERS_PRODUCTS." o LEFT JOIN ".TABLE_PRODUCTS." p ON o.products_id = p.products_id WHERE o.orders_id =".$oInfo->orders_id;

        $items_query = $db->Execute($items_query_raw);
        while (!$items_query->EOF) {
          $items = $items_query->fields;
          $items_query->MoveNext();

          $iInfo = new objectInfo($items);
          $iInfo = parseSpecChars($iInfo);
          // print_r($iInfo);
          $itemNode = $xmlResponse->createTag("Item",    array(), '',    $itemsNode, __ENCODE_RESPONSE);
          $iInfo->products_model = trim($iInfo->products_model);
          $xmlResponse->createTag("ItemCode",       array(), empty($iInfo->products_model)?$iInfo->products_name:$iInfo->products_model, $itemNode, __ENCODE_RESPONSE);
          $xmlResponse->createTag("ItemDescription",array(), $iInfo->products_name,      $itemNode, __ENCODE_RESPONSE);
          $xmlResponse->createTag("Quantity",       array(), $iInfo->products_quantity,  $itemNode);
          $xmlResponse->createTag("UnitPrice",      array(), $iInfo->final_price,        $itemNode, __ENCODE_RESPONSE);
          $xmlResponse->createTag("ItemUnitWeight",      array(), $iInfo->products_weight,  $itemNode);

          $itemsa_query_raw = "SELECT * FROM ".TABLE_ORDERS_PRODUCTS_ATTRIBUTES." WHERE orders_id='".$oInfo->orders_id."' AND orders_products_id = '".$iInfo->orders_products_id."'";
          $itemsa_query = $db->Execute($itemsa_query_raw);

          while (!$itemsa_query->EOF) {
            $itemsa = $itemsa_query->fields;
            $itemsa_query->MoveNext();

            $iaInfo = new objectInfo($itemsa);
            $iaInfo = parseSpecChars($iaInfo);
            // print_r($iaInfo);
            if (!isset($itemOptionsNode)) $itemOptionsNode  = $xmlResponse->createTag("ItemOptions",    array(), '',     $itemNode, __ENCODE_RESPONSE);
              $xmlResponse->createTag("ItemOption", array('Name'=>$iaInfo->products_options,'Value'=>$iaInfo->products_options_values),'',$itemOptionsNode, __ENCODE_RESPONSE);
          }
          unset($itemOptionsNode);
        } // end items


        $charges_query_raw = "
          SELECT
            SUM(value) as value_sum,
            class as value_class
          FROM ".TABLE_ORDERS_TOTAL."
          WHERE orders_id = ".$oInfo->orders_id."
          GROUP BY class";

        $charges_query = $db->Execute($charges_query_raw);
        $is_tax = false;
        $is_shipping = false;
        $is_total = false;
        // $xmlResponse->createTag("Handling", array(), "0",        $chargesNode);

// --------- MB 08/10/2008 start --------

//        $xmlResponse->createTag("Discount", array(), $oInfo->coupon_amount,        $chargesNode, __ENCODE_RESPONSE);

// --------- MB 08/10/2008 end ----------
        while (!$charges_query->EOF) {
          $charges = $charges_query->fields;
          $charges_query->MoveNext();

          $chInfo = new objectInfo($charges);
          $chInfo = parseSpecChars($chInfo);

          if ($chInfo->value_class == "ot_tax") {
            $xmlResponse->createTag("Tax", array(), $chInfo->value_sum,      $chargesNode, __ENCODE_RESPONSE);
            $is_tax = true;
          }
          if ($chInfo->value_class == "ot_shipping") {
            $xmlResponse->createTag("Shipping", array(), $chInfo->value_sum, $chargesNode, __ENCODE_RESPONSE);
            $is_shipping = true;
          }
          if ($chInfo->value_class=="ot_total") {
            $xmlResponse->createTag("Total", array(), $chInfo->value_sum,        $chargesNode, __ENCODE_RESPONSE);
            $is_total = true;
          }
        }
        if (!$is_tax)      $xmlResponse->createTag("Tax",      array(), "0",      $chargesNode, __ENCODE_RESPONSE);
        if (!$is_shipping) $xmlResponse->createTag("Shipping", array(), "0",      $chargesNode, __ENCODE_RESPONSE);
        if (!$is_total)    $xmlResponse->createTag("Total",    array(), "0",      $chargesNode, __ENCODE_RESPONSE);

      } //orders

    } break;

    case 'UPDATEORDERS' : {
      $ordersTag = $xmlRequest->getChildByName(0, "ORDERS");
      $xmlRequest->getTag($ordersTag, $_tagName, $_tagAttributes, $_tagContents, $_tagTags);
      if (count($_tagTags) == 0) $no_orders = true; else $no_orders = false;
      $xmlResponse->createTag("StatusCode", array(), $no_orders?"1000":"0", $envelope);
      $xmlResponse->createTag("StatusMessage", array(), $no_orders?"No Orders returned":"All Ok", $envelope);
      if ($no_orders){
        print($xmlResponse->generate()); exit;
      }
      $ordersNode = $xmlResponse->createTag("Orders", array(), '', $root);
      foreach($_tagTags as $k=>$v){
        $xmlRequest->getTag($v, $_tagName, $_tagAttributes, $_tagContents, $_orderTags);
        $orderNode = $xmlResponse->createTag("Order",  array(), '',     $ordersNode);
        foreach($_orderTags as $k1=>$v1){
          $xmlRequest->getTag($v1, $_tagName, $_tagAttributes, $_tagContents, $_tempTags);
          $order[strtoupper($_tagName)] = $_tagContents;
          if (strtoupper($_tagName)!='LOCALSTATUS')
            $xmlResponse->createTag($_tagName,  array(), $_tagContents,     $orderNode);
        }
        $query = $db->Execute("SELECT count(*) cnt FROM ".TABLE_ORDERS." WHERE orders_id = '".$order['HOSTORDERID']."'");
        $result = 'Success';
        if ($query->fields['cnt']==0) $result = 'Order not found';
        //$updateQuery = "
        //  UPDATE " . TABLE_ORDERS . "
        //  SET
        //    thub_posted_to_accounting = '1',
        //    last_modified = now(),
        //    thub_posted_date = now(),
        //    thub_accounting_ref = '".$order['LOCALORDERREF']."'
        //  WHERE orders_id = '".$order['HOSTORDERID']."'";
        $updateQuery = "UPDATE " . TABLE_ORDERS . " SET last_modified = now() WHERE orders_id = '".$order['HOSTORDERID']."'";
        @$db->Execute($updateQuery);
        $xmlResponse->createTag('HostStatus',  array(), $result, $orderNode);
      }
    } break;


    //***************************************************
    //
    //      update  Orders Shipping Status Service
    //
    //***************************************************

    case 'UPDATEORDERSSHIPPINGSTATUS' : {
    	$notifyCustomers = 0;
      $ordersTag = $xmlRequest->getChildByName(0, "ORDERS");
      $xmlRequest->getTag($ordersTag, $_tagName, $_tagAttributes, $_tagContents, $_tagTags);
      if (count($_tagTags) == 0) $no_orders = true; else $no_orders = false;
      $xmlResponse->createTag("StatusCode", array(), $no_orders?"1000":"0", $envelope);
      $xmlResponse->createTag("StatusMessage", array(), $no_orders?"No Orders returned":"All Ok", $envelope);
      if ($no_orders){
        print($xmlResponse->generate()); exit;
      }
      $ordersNode = $xmlResponse->createTag("Orders", array(), '', $root);
      foreach($_tagTags as $k=>$v){
        $order = Array();
        $xmlRequest->getTag($v, $_tagName, $_tagAttributes, $_tagContents, $_orderTags);
        $orderNode = $xmlResponse->createTag("Order",  array(), '',     $ordersNode);
       // $ShipStatus = strtoupper($order['SHIPPEDSTATUS']);
        $ShipStatus = 'SHIPPED';
        foreach($_orderTags as $k1=>$v1){
          $xmlRequest->getTag($v1, $_tagName, $_tagAttributes, $_tagContents, $_tempTags);
          $order[strtoupper($_tagName)] = $_tagContents;
          if (strtoupper($_tagName)=='HOSTORDERID')
            $xmlResponse->createTag('HostOrderID',  array(), $_tagContents,     $orderNode);
          if (strtoupper($_tagName)=='LOCALORDERID')
            $xmlResponse->createTag('LocalOrderID',  array(), $_tagContents,     $orderNode);
        }

        $result = 'Failed';
        $sqlstr = "select customers_name, customers_email_address, orders_status, date_purchased from " . TABLE_ORDERS . " where orders_id = '" . $order['HOSTORDERID'] . "'";
        // print($sqlstr);
        $check_status = $db->Execute($sqlstr);
        // $check_status_query = mysql_query($sqlstr);
        // print("<br>status:".$check_status_query);
        // if (!$check_status_query) print mysql_errno()." - ".mysql_error();

        // $check_status = tep_db_fetch_array($check_status_query);

        // print_r("Status:".$check_status);

        while (!$check_status->EOF) {
          if (($check_status->fields['orders_status'] != __DELIVERED_STATUS_ID)  || ($check_status->fields['orders_status'] != __BACKORDER_STATUS_ID) ) {
          //if (($check_status->fields['orders_status'] != __DELIVERED_STATUS_ID)  ) {

          	if ($ShipStatus == 'SHIPPED')
            	$db->Execute("update " . TABLE_ORDERS . " set orders_status = '" . __DELIVERED_STATUS_ID . "', last_modified = now() where orders_id = '" . $order['HOSTORDERID'] . "'");
          	if ($ShipStatus == 'BACKORDER')
               $db->Execute("update " . TABLE_ORDERS . " set orders_status = '" . __BACKORDER_STATUS_ID . "', last_modified = now() where orders_id = '" . $order['HOSTORDERID'] . "'");


            $customer_notified = '0';
          	if ($ShipStatus == 'SHIPPED')
            	$comments = "\nOrder shipped on ".$order['SHIPPEDON']." via ".$order['SHIPPEDVIA']." using ".$order['SERVICEUSED']." service\n Tracking Number:".$order['TRACKINGNUMBER'];
            	//$comments = "---".$order['SHIPPEDSTATUS'];
          	if ($ShipStatus == 'BACKORDER')
            	$comments = "\nThis order is on Back Order";
            if ($notifyCustomers == 1 && (strtoupper($order['NOTIFYCUSTOMER']) == 'YES') || ($order['NOTIFYCUSTOMER'] == '1')){
              $notify_comments = sprintf(EMAIL_TEXT_COMMENTS_UPDATE, $comments);

               $email = STORE_NAME . "\n"
                . EMAIL_SEPARATOR . "\n"
                . EMAIL_TEXT_ORDER_NUMBER . ' '
                . $order['HOSTORDERID'] . "\n"
                . EMAIL_TEXT_INVOICE_URL . ' '
                . zen_catalog_href_link(FILENAME_CATALOG_ACCOUNT_HISTORY_INFO, 'order_id=' . $order['HOSTORDERID'], 'SSL') . "\n"
                . EMAIL_TEXT_DATE_ORDERED . ' '
                . zen_date_long($check_status->fields['date_purchased']) . "\n\n"
                . $notify_comments . sprintf(EMAIL_TEXT_STATUS_UPDATE, __DELIVERED_STATUS_NAME);



              // tep_mail($check_status['customers_name'], $check_status['customers_email_address'], EMAIL_TEXT_SUBJECT, $email, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);

              $customer_notified = '1';
            } else {
              $customer_notified = '0';
            }
            // print("asdasd");
          	if ($ShipStatus == 'SHIPPED'){
            	$sqlstr = "INSERT INTO " . TABLE_ORDERS_STATUS_HISTORY . "(orders_id, orders_status_id, date_added, customer_notified, comments) VALUES ('" . $order['HOSTORDERID'] . "', '" . __DELIVERED_STATUS_ID . "', now(), '" . $customer_notified . "', '" . $comments  . "')";
            	$res = $db->Execute($sqlstr);
            }
          	if ($ShipStatus == 'BACKORDER'){
            	$sqlstr = "INSERT INTO " . TABLE_ORDERS_STATUS_HISTORY . "(orders_id, orders_status_id, date_added, customer_notified, comments) VALUES ('" . $order['HOSTORDERID'] . "', '" . __BACKORDER_STATUS_ID . "', now(), '" . $customer_notified . "', '" . $comments  . "')";
            	$res = $db->Execute($sqlstr);
            }
            // $xmlResponse->createTag('INSERT_SQL',  array(), "result : ".$res."; SQL : ".$sqlstr, $orderNode);
            $result = 'Success';
          }
          $check_status->MoveNext();
        }

        $xmlResponse->createTag('HostStatus',  array(), $result, $orderNode);
      }


    } break;  // end of update Orders Shipping Status Service
//*****************************************************************
//
//               case 'UPDATEINVENTORY'
//
//*****************************************************************
	    case 'UPDATEINVENTORY' : {


	        $addProdTag = $xmlRequest->getChildByName(0, "ADDPRODUCTS");
	        $xmlRequest->getTag($addProdTag, $_tagName, $_tagAttributes, $addProd, $_orderTags);

	        $addCategiryTag  = $xmlRequest->getChildByName(0, "ADDCATEGORY");
	        $xmlRequest->getTag($addCategiryTag, $_tagName, $_tagAttributes, $addCategory, $_orderTags);

	        $addManufactTag  = $xmlRequest->getChildByName(0, "ADDMANUFACTURER");
	        $xmlRequest->getTag($addManufactTag, $_tagName, $_tagAttributes, $addManufacturer, $_orderTags);

	        $updDescrTag = $xmlRequest->getChildByName(0, "UPDATEDESCRIPTION");
	        $xmlRequest->getTag(  $updDescrTag, $_tagName, $_tagAttributes, $updDescritpion, $_orderTags);

	        $updPriceTag  = $xmlRequest->getChildByName(0, "UPDATEPRICE");
	        $xmlRequest->getTag($updPriceTag, $_tagName, $_tagAttributes, $updPrice, $_orderTags);

	        $updInvTag      = $xmlRequest->getChildByName(0, "UPDATEINVENTORY");
	        $xmlRequest->getTag($updInvTag, $_tagName, $_tagAttributes, $updInventory, $_orderTags);

	        $itemsTag = $xmlRequest->getChildByName(0, "ITEMS");
	        $xmlRequest->getTag($itemsTag, $_tagName, $_tagAttributes, $_tagContents, $_tagTags);


	        if (isset($request_id)) $xmlResponse->createTag("RequestID", array(), $request_id, $envelope);
//	        $xmlResponse->createTag("StoreID",   array(), STORE_NAME, $envelope, __ENCODE_RESPONSE);
//	        $xmlResponse->createTag("StoreName", array(), STORE_NAME, $envelope, __ENCODE_RESPONSE);

	        $items = $xmlResponse->createTag("Items", array(), '', $root);

	        $itemsCount = 0;
	        $itemsProcessed = 0;
         foreach($_tagTags as $k=>$itemTag) {   // look thruoght items
            $itemsCount++;
            $_err_message_arr = array();
            $error_stat = 0;

   				// get item fields
   				// ItemCodeParent
   				$itemCodeTag = $xmlRequest->getChildByName($itemTag,        "ITEMCODE");           $xmlRequest->getTag($itemCodeTag,  $_tagName, $_tagAttributes,       $itemCode, $_orderTags);
   				$itemCodeTagParent = $xmlRequest->getChildByName($itemTag,  "ITEMCODEPARENT");     $xmlRequest->getTag($itemCodeTagParent,  $_tagName, $_tagAttributes, $itemCodeParent, $_orderTags);
   				$itemNameTag = $xmlRequest->getChildByName($itemTag,        "ITEMNAME");           $xmlRequest->getTag($itemNameTag, $_tagName, $_tagAttributes,        $itemName, $_orderTags);
   				$itemDescrTag = $xmlRequest->getChildByName($itemTag,       "ITEMDESCRIPTION");    $xmlRequest->getTag($itemDescrTag, $_tagName, $_tagAttributes,       $itemDescr, $_orderTags);
   				$itemPriceTag = $xmlRequest->getChildByName($itemTag,       "PRICE");              $xmlRequest->getTag($itemPriceTag, $_tagName, $_tagAttributes,       $itemPrice, $_orderTags);
   				$itemSalePriceTag = $xmlRequest->getChildByName($itemTag,   "SALEPRICE");          $xmlRequest->getTag($itemSalePriceTag, $_tagName, $_tagAttributes,   $itemSalePrice, $_orderTags);
   				$itemUnitWeightTag = $xmlRequest->getChildByName($itemTag,  "UNITWEIGHT");         $xmlRequest->getTag($itemUnitWeightTag, $_tagName, $_tagAttributes,  $itemUnitWeight, $_orderTags);
   				$itemStockTag = $xmlRequest->getChildByName($itemTag,       "QUANTITYINSTOCK");    $xmlRequest->getTag($itemStockTag, $_tagName, $_tagAttributes,       $itemStock,        $_orderTags);
   				$itemManufTag = $xmlRequest->getChildByName($itemTag,       "MANUFACTURER");       $xmlRequest->getTag($itemManufTag, $_tagName, $_tagAttributes,       $itemManufacturer, $_orderTags);
   				$itemCategTag = $xmlRequest->getChildByName($itemTag,       "CATEGORY");           $xmlRequest->getTag($itemCategTag, $_tagName, $_tagAttributes,       $itemCategory,     $_orderTags);
   				//	              $itemOptionTag = $xmlRequest->getChildByName($itemTag,      "ITEMOPTION");         $xmlRequest->getTag($itemCategTag, $_tagName, $itemOptionAttr,       $itemOption,     $_orderTags);

   				// parse options for certain product.
   				$xmlRequest->getTag($itemTag, $_tagName, $_tagAttributes, $_tagContents, $_optionsTags);
   				$opt_name_array = array();
   				$opt_value_array = array();
   				foreach($_optionsTags as $i=>$optionTag){
   				    $xmlRequest->getTag($optionTag, $_tagName, $_tagAttributes, $_tagContents, $sub_optionsTags);
   				    if($_tagName != "ITEMOPTION") continue;
   				    $opt_name_array[] = $_tagAttributes['NAME'];
   				    $opt_value_array[] = $_tagContents;
   //				    if (__DEBUG==1)  $xmlResponse->createTag ("__DEBUG", array(), 'tag_content:'.$_tagContents.", tag attr:".$_tagAttributes['NAME'], $envelope);
                   }

               if ($updPrice){
                  $flAddSalePrice = false;
                  if (isset($itemCode)){
                     $query = $db->Execute("SELECT products_id,products_price,products_model,products_quantity FROM ".TABLE_PRODUCTS." where products_model='".$itemCode."'");
                     if (isset($query->fields['products_model'])){
                        $currentPrice =$query->fields['products_price'];
                        if (isset($itemPrice)){
                           $currentPrice =$itemPrice;
                           $query_answer=$db->Execute("update  ". TABLE_PRODUCTS." set products_price='".$itemPrice."', products_last_modified=now() where products_model='".$itemCode."'");
                           if ( !$query_answer ) {$error_stat = 1;$_err_message_arr[].="Not update price to ".TABLE_PRODUCTS;}

                           if (isset($itemSalePrice)){

                           	if ($itemSalePrice>$currentPrice){
                                 $error_stat = 1;$_err_message_arr[].="Not update SpecialPrice!";
                           	}else{
                                 $special_query = $db->Execute("SELECT specials_id,products_id,specials_new_products_price FROM ".TABLE_SPECIALS." where products_id='".$query->fields['products_id']."'");
                                 if (isset($special_query->fields['specials_id'])){
                                    $query_answer=$db->Execute("update  ". TABLE_SPECIALS." set specials_new_products_price='".$itemSalePrice."', specials_last_modified=now() where specials_id='".$special_query->fields['specials_id']."'");
                                    if ( !$query_answer ) {$error_stat = 1;$_err_message_arr[].="Not update saleprice to ".TABLE_SPECIALS;}
                                 }else{
                                    $flAddSalePrice = true;
                                 }
                              }
                           }
                        }elseif( isset($itemSalePrice)){
                           	if ($itemSalePrice>$currentPrice){
                                 $error_stat = 1;$_err_message_arr[].="Not update SpecialPrice!";
                           	}else{
                                 $special_query = $db->Execute("SELECT specials_id,products_id,specials_new_products_price FROM ".TABLE_SPECIALS." where products_id='".$query->fields['products_id']."'");
                                 if (isset($special_query->fields['specials_id'])){
                                    $query_answer=$db->Execute("update  ". TABLE_SPECIALS." set specials_new_products_price='".$itemSalePrice."', specials_last_modified=now() where specials_id='".$special_query->fields['specials_id']."'");
                                    if ( !$query_answer ) {$error_stat = 1;$_err_message_arr[].="Not update saleprice to ".TABLE_SPECIALS;}
                                 }else{
                                    $flAddSalePrice = true;
                                 }
                              }
                        }
                        if ($flAddSalePrice){
                           $query_answer=$db->Execute("insert  ". TABLE_SPECIALS."
                                                     (specials_id,products_id,specials_new_products_price,specials_date_added,specials_last_modified,status)
                                                     values ('', '" . $query->fields['products_id'] . "', '" . $itemSalePrice . "', now(),now(),'1')");
                           if ( !$query_answer ) {$error_stat = 1;$_err_message_arr[].="Not insert saleprice to ".TABLE_SPECIALS;}
                        }
                     }else{
                     	$error_stat = 1;$_err_message_arr[].="Not found product UpdatePrice!";
                     }
                  }

               }//              End section update Price
               if ($updInventory){
                  if (isset($itemStock)){
                     $query = $db->Execute("SELECT products_model,products_quantity FROM ".TABLE_PRODUCTS." where products_model='".$itemCode."'");
                     if (isset($query->fields['products_model'])){
                        if (isset($query->fields['products_quantity'])) {
                        	$QuantityInStockWEB = $query->fields['products_quantity'];
                        }else{
                           $QuantityInStockWEB = 0;
                        }

                        $query_answer=$db->Execute("update  ". TABLE_PRODUCTS." set products_quantity='".$itemStock."', products_last_modified=now() where products_model='".$itemCode."'");
                        if ( !$query_answer ) {$error_stat = 1;$_err_message_arr[].="Not update quantity to ".TABLE_PRODUCTS;}

                     }else{
                     	$error_stat = 1;$_err_message_arr[].="Not found product UpdateStock!";
                     }
                  }

               }//$addInventory



              $item = $xmlResponse->createTag("Item", array(), '', $items);
              $xmlResponse->createTag("ItemCode", array(), $itemCode, $item, __ENCODE_RESPONSE);
              $xmlResponse->createTag("InventoryUpdateStatus", array(), "$error_stat", $item, __ENCODE_RESPONSE);
              if (isset($QuantityInStockWEB)){
                 $xmlResponse->createTag("QuantityInStockWEB", array(), "$QuantityInStockWEB", $item, __ENCODE_RESPONSE);
              }
               foreach($_err_message_arr as $mesage){
                  $xmlResponse->createTag("Message",   array(), $mesage, $item, __ENCODE_RESPONSE);
               }

         } // foreach($_tagTags as $k=>$itemTag)  look thruoght items

        if ( $itemsProcessed == $itemsCount ) {
        	$code = "0";
        	$message = "All Ok";
        } elseif( $itemsProcessed == 0 ){
            $code = "0";
            $message = "Warning: No items processed";
        } else {
        	$code = "0";
        	$message = "Warning: ".$itemsProcessed."/".$itemsCount." items processed";
        }

        $xmlResponse->createTag("StatusCode", array(), $code, $envelope);
        $xmlResponse->createTag("StatusMessage", array(), $message, $envelope);
        $xmlResponse->createTag("Provider", array(), "GENERIC", $envelope);
        }
//      case 'UPDATEINVENTORY' :

  }


  print($xmlResponse->generate());

  // print_r($RequestParams);
  // print_r($xmlRequest);




function xmlErrorResponse($command, $code, $message, $provider, $request_id=''){
  $xmlResponse = new xml_doc('<?xml version="1.0" encoding="UTF-8"?>');
  //$xmlResponse = new xml_doc();
  //$xmlResponse->version='';
 // $xmlResponse->encoding='UTF-8';
  $root = $xmlResponse->createTag("RESPONSE", array('Version'=>'1.1'));
  //print("i'm here");
  $envelope = $xmlResponse->createTag("Envelope", array(), '', $root);
  $xmlResponse->createTag("Command", array(), $command, $envelope);
  $xmlResponse->createTag("StatusCode", array(), $code, $envelope);
  $xmlResponse->createTag("StatusMessage", array(), $message, $envelope);
  //if ($request_id) $xmlResponse->createTag("RequestID", array(), $request_id, $envelope);
  //$xmlResponse->createTag("Provider", array(), $provider, $envelope);
  return $xmlResponse->generate();
}

function parseTagName($str){
  return preg_replace("/[-=+\s!@#\$\^\&%*\(\)\{\}\[\]':`~\.]/is", "", $str);
}

function parseSpecChars($obj){
  foreach($obj as $k=>$v){
    $obj->$k = htmlspecialchars($v, ENT_NOQUOTES);
  }
  return $obj;
}

class objectInfo
{
// class constructor
    function objectInfo($object_array) {
      reset($object_array);
      while (list($key, $value) = each($object_array)) {
        $this->$key = _prepare_input($value);
      }
    }
}

function _prepare_input($string) {
    if (is_string($string)) {
      return trim(stripslashes($string));
    } elseif (is_array($string)) {
      reset($string);
      while (list($key, $value) = each($string)) {
        $string[$key] = _prepare_input($value);
      }
      return $string;
    } else {
      return $string;
    }
}

function isTHUBInstalled() {
  global $db;
  $result = false;
  $sql_str = "SHOW COLUMNS FROM ".TABLE_ORDERS." LIKE 'thub_posted_to_accounting'";
  $orders_query = $db->Execute($sql_str);
  if (!$orders_query->EOF) $result = true;
  return $result;
}

function THUBInstall(){
  global $db;
  $result3 = $db->Execute("ALTER TABLE `".TABLE_ORDERS."`
  ADD `thub_posted_to_accounting` VARCHAR( 1 ) ,
  ADD `thub_posted_date` DATETIME,
  ADD `thub_accounting_ref` VARCHAR( 25 )");
  if ($result3) {
    //$result4 = tep_db_query("UPDATE `orders` set  thub_posted_to_accounting  = 'X', thub_accounting_ref = 'HISTORY'");
    $result4 = $db->Execute("UPDATE `".TABLE_ORDERS."` set  thub_posted_to_accounting  = 'X', thub_accounting_ref = 'HISTORY'  WHERE to_days(Now()) - TO_DAYS(date_purchased) > 5");


    if (!$result4) {
          return "Update of historical orders FAILED<br>";
    }

    /**   for update inventory list routine
    *
    *     $result31 = tep_db_query("ALTER TABLE `products`
    *     ADD `InventoryBatch` int");
    *     if (!$result31) {
    *         return "ALTER of products table FAILED<br>";
    *     }
    *
    */

  } else {
    return "ALTER order table FAILED<br>";
  }
  return '';
}


?>

