<?php

namespace app\express_checkout\controller;

use \think\Controller;

class Index extends Controller 
{
	/**
	 * @author chenliujin <liujin.chen@qq.com>
	 * @since 2016-08-24
	 */
	public function Index()
	{
		$customer = new \customer;
		$customer = $customer->get($_SESSION['customer_id']);

		$customer_address = new \customer_address;
		$customer_address = $customer_address->get($customer->customer_id);

		$zone = new \zone;
		$zone = $zone->get($customer_address->zone_id);

		$order = new \order;
		$order->customer_id				= $customer->customer_id;
		$order->customer_firstname		= $customer->customer_firstname;
		$order->customer_lastname		= $customer->customer_lastname;
		$order->customer_telephone		= $customer->customer_telephone;
		$order->customer_postcode		= $customer->customer_postcode;
		$order->customer_email_address	= $customer->customer_email_address;
		$order->customer_company		= $customer_address->customer_company;
		$order->customer_street_address	= $customer_address->customer_street_address;
		$order->customer_suburb			= $customer_address->customer_suburb;
		$order->customer_city			= $customer_address->customer_city;
		$order->customer_postcode		= $customer_address->customer_postcode;
		$order->customer_state			= $customer_address->customer_state ? $customer_addess->customer_state : $zone->zone_name;

		$order->order_status			= ORDER_STATUS_PENDING;


		$_SESSION['comments']=$_POST['comments'];
		if(strripos($_POST['payment'], 'collect'))
		{
			$payment_global = explode('_',$_POST['payment']);
			$_SESSION['payment'] =  $payment_global[0];
			$_SESSION['global_collect_type'] = $payment_global[1];
			$_SESSION['payment_time'] = 1;
		}elseif(stripos($_POST['payment'], 'alipay') !== false){
			$payment_alipay = explode('_', $_POST['payment']);
			$_SESSION['payment'] = $payment_alipay[0];
			$_SESSION['payment_alipay_type'] = $payment_alipay[1];
		}elseif($_POST['payment']) {
			$_SESSION['payment'] = $_POST['payment'];
		}

		$products = $_SESSION['cart']->get_products();
		//	for ($i=0, $n=sizeof($products); $i<$n; $i++) {
		//		if ($_SESSION['cart']->contents[$products[$i]['id']]['orders_tage']!=$_SESSION['orders_tag'])
		//		{
		//			//$sql_data_array = array('orders_tag' => zen_db_prepare_input($_SESSION['orders_tag']),'log_content'=>zen_db_prepare_input($_SESSION['items']),'add_date'=>'now()');
		//			//zen_db_perform(TABLE_ORDERS_LOGS, $sql_data_array);
		//			//break;
		//		}
		//	}
		// load selected payment module
		require(DIR_WS_CLASSES . 'payment.php');
		$payment_modules = new payment($_SESSION['payment']);
		$payment_modules_special = new $_SESSION['payment']();

		// correct the payment currency
		$final_payment_currency = $currencies->get_valid_currency($_SESSION['currency'], $payment_modules_special->valid_currencies, $payment_modules_special->default_currency);
		$original_currency = $_SESSION['currency'];
		if(array_key_exists($final_payment_currency, $currencies->currencies)){
			$_SESSION['currency'] = $final_payment_currency;
		}

		$order = new order;
		$send_store = $_SESSION['receive_data']['send_store'] != '' ? $_SESSION['receive_data']['send_store'] : ($_POST['send_store'] == 1 ? 1 : 0);
		$order->info['send_stored'] = $send_store;
		$telephone_code = $_SESSION['receive_data']['telephone_code'] != '' ? $_SESSION['receive_data']['telephone_code'] : $_POST['telephone_code'];

		if(isset($telephone_code) && zen_not_null($telephone_code)) {
			$order->delivery['telephone'] = trim($telephone_code);
		}

		$post_code = $_SESSION['receive_data']['post_code'] != '' ? $_SESSION['receive_data']['post_code'] : $_POST['post_code'];
		if(isset($post_code) && zen_not_null($post_code)) {
			$order->delivery['postcode'] = trim($post_code);
		}

		$cpf_code = $_SESSION['receive_data']['cpf_code'] != '' ? $_SESSION['receive_data']['cpf_code'] : $_POST['cpf_code'];
		if(isset($cpf_code) && zen_not_null($cpf_code)) {
			$order->delivery['cpf'] = trim($cpf_code);
		}

		$birth_date = $_SESSION['receive_data']['txt_birth_date'] != '' ? $_SESSION['receive_data']['txt_birth_date'] :$_POST['txt_birth_date']; 
		if(isset($birth_date) && !empty($birth_date)){
			$_SESSION['customer_birth'] = date('d/m/Y', strtotime($birth_date));
			$update_birth_sql = "UPDATE ". TABLE_CUSTOMERS ." SET customers_birth='". date('Y-m-d', strtotime($birth_date))."'
				WHERE customers_id=".(int)$_SESSION['customer_id'];
			$db->Execute($update_birth_sql);
		}
		$customers_points = new customers_points;
		require(DIR_WS_CLASSES . 'customers_packages.php');
		$customers_packages = new customers_packages;
		$zco_notifier->notify('NOTIFY_CHECKOUT_PROCESS_BEFORE_ORDER_TOTALS_PRE_CONFIRMATION_CHECK');
		$normal_products = $order_discount->delete_specials_products();
		$order_discount ->collect_posts();
		$order_totals = $order_discount->get_products_detail(false,$normal_products);

		$zco_notifier->notify('NOTIFY_CHECKOUT_PROCESS_BEFORE_ORDER_TOTALS_PROCESS');
		$zco_notifier->notify('NOTIFY_CHECKOUT_PROCESS_AFTER_ORDER_TOTALS_PROCESS');
		//Modified by sam.chen
		//If an order with the same orders_tag exists, do not create again, just use it
		$exists_order_id = 0;
		$insert_id = 0;
		if (isset($_SESSION['orders_tag'])) {
			$exists_order_id = $order->get_orders_id_by_orders_tag($_SESSION['orders_tag']);
		}
		if ($exists_order_id > 0) {
			zen_redirect(zen_href_link(FILENAME_SHOPPING_CART,'','NONSSL'));
		} else {
			// create the order record
			$insert_id = $order->create($order_totals, 2);
			if($insert_id > 0)
			{
				//reduct points.
				if((isset($_SESSION['totoldropprice'])&&$_SESSION['totoldropprice']>0) ||
					(isset($_SESSION['special_points']['total'])&& $_SESSION['special_points']['total']>0) )
				{
					$reuducted_points = $customers_points->reduction_customer_points($_SESSION['customer_id'],
						$_SESSION['totoldropprice']+$_SESSION['special_points']['total'],$insert_id,'',$_SESSION['special_points']['total']);
				}
				//get order total.
				$credits_applied = 0;
				for ($i=0, $n=sizeof($order_totals); $i<$n; $i++) {
					if ($order_totals[$i]['code'] == 'ot_subtotal') $order_subtotal = $order_totals[$i]['value'];
					if ($$order_totals[$i]['code']->credit_class == true) $credits_applied += $order_totals[$i]['value'];
					if ($order_totals[$i]['code'] == 'ot_total') $ototal = $order_totals[$i]['value'];
				}
				//get affiliate points.
				if (isset($_SESSION['affiliateid'])&&$_SESSION['affiliateid']!='') {
					require(DIR_WS_CLASSES . 'customers_affiliate.php');
					$customers_affiliate = new customers_affiliate;
					$customers_affiliate->calucate_affiliate_points($order_subtotal,$order,$insert_id,$customers_points,$order_discount->percent_five_points);
				}
				//get coupon point.
				if(isset($_SESSION['chrismas_coupon'])&&$_SESSION['chrismas_coupon']>0)
				{
					$customers_points->add_customers_points((int)$_SESSION['customer_id'],17,$insert_id,$_SESSION['chrismas_coupon'],3,sprintf(TEXT_GET_EXTRA_POINTS,$_SESSION['chrismas_coupon']),'orders');
					$order->store_orders_status_history($insert_id,1,0,sprintf(TEXT_GET_EXTRA_POINTS,$_SESSION['chrismas_coupon']));
				}
				//get extor points.
				if($_SESSION['payment'] == 'westernunion')
				{
					$customers_points->add_customers_points((int)$_SESSION['customer_id'],17,$insert_id,$currencies->value_usd(($ototal)/10,true,'USD'),3,sprintf(TEXT_BOUGHT_POINT,$currencies->format($ototal),$currencies->value_usd(($ototal)/10,true,'USD')).TEXT_DOUBLE_POINT_EXTRA,'orders');
					$order->store_orders_status_history($insert_id,1,0,sprintf(TEXT_BOUGHT_POINT_HISTORY,$currencies->format($ototal),$currencies->value_usd(($ototal)/10,true,'USD')).TEXT_DOUBLE_POINT_EXTRA);
				}
				//coupon code points and gifts.
				$which_gifts = $_SESSION['receive_data']['whichgifts'] != '' ? $_SESSION['receive_data']['whichgifts'] : $_POST['whichgifts'];
				if (isset($which_gifts) && $which_gifts != '')
				{
					$which_gifts = trim($which_gifts);
					if(strpos($which_gifts,':')>0)
					{
						$customers_points->add_customers_points((int)$_SESSION['customer_id'],17,$insert_id,substr($which_gifts,7),3,'use coupon code to get '.$which_gifts,'orders');
						$order->store_orders_status_history($insert_id,1,0,' use coupon code to get '.$which_gifts);
					}
					else
					{
						$which_package = $_SESSION['receive_data']['whichpackage'] != '' ? $_SESSION['receive_data']['whichpackage'] : $_POST['whichpackage'];
						$products_id = $which_gifts;
						$products_prid = $products_id;
						$gift_product = get_product($products_id);
						$pm_id = $gift_product['pm_id'];                                       
						$order->add_gift_products($insert_id,$products_id,$products_prid,$pm_id,1);
						if(isset($which_package) && zen_not_null($which_package)){
							$customers_packages->select_package_for_gift($which_package,$which_gifts,$pm_id,0,1);
						}
						$order->store_orders_status_history($insert_id,1,0,zen_get_products_name($which_gifts).TEXT_GIFT);
					}
				}
				//update customer package.
				$customers_packages->update_package_status(1,$insert_id,1,(int)$_SESSION['customer_id']);
				$customers_packages->update_package_id($insert_id);
				//update discount info
				$customers_packages->update_package_discount_info($insert_id);
				// store the product info to the order
				$_SESSION['order_number_created'] = $insert_id;
				if(isset($_SESSION['coupon_from_random'])&&$_SESSION['coupon_from_random']!='')
				{
					$gift_product_query = "update ".TABLE_COUPONS_FROM_RANDOM." set status=1 where status=0 and random_code='".  addslashes($_SESSION['coupon_from_random'])."'";
					$gift_product_result = $db->Execute($gift_product_query);
				}
				// load the before_process function from the payment modules
				$payment_details = $payment_modules_special->before_process($insert_id);
				unset($_SESSION['paypal_echeck_cc']);
				unset($_SESSION['affiliateid']);
				unset($_SESSION['affiliatehistory']);
				unset($_SESSION['coupon_from_random']);
				unset($_SESSION['packingvalue']);
				unset($_SESSION['checkoutpackage']);
				unset($_SESSION['orders_tag']);
				unset($_SESSION['transportation']);
				unset($_SESSION['transportation_id']);
				unset($_SESSION['popup_shipping']);
				unset($_SESSION['ec_usepwdsig']);
				unset($_SESSION['chrismas_coupon']);
				unset($_SESSION['auto_coupon']);
				unset($_SESSION['receive_data']);
				// load the after_process function from the payment modules
				$payment_modules->after_process();
				$_SESSION['cart']->reset(true);
				// unregister session variables used during checkout
				unset($_SESSION['sendto']);
				unset($_SESSION['billto']);
				unset($_SESSION['shipping']);
				unset($_SESSION['comments']);
				$_SESSION['currency'] = $original_currency;
				if (isset($_SESSION['payment']) && zen_not_null($_SESSION['payment']))
				{
					$_SESSION['orders_id'] = $insert_id;
?>
<div style="width: 100%; margin: 0 auto; text-align: center;">
<?php echo zen_image(DIR_WS_TEMPLATE_IMAGES.'ajax-loader.gif')?>
<p><?php echo TEXT_WAIT_FOR_A_MINUTE;?></p>
</div>
<?php
					if($payment_modules->customer_sumbit == false)
					{
						unset($_SESSION['payment']);
						zen_redirect($payment_modules_special->paynow_action_url);
					}
					else
					{
						echo zen_draw_form("checkout_payment",$payment_modules->paynow_action_url,'POST');
						//$json["actiontext"] = utf8_encode($payment_modules->paynow_action_url);
						echo $payment_modules->paynow_button($insert_id);
						echo zen_draw_input_field('selected_module', $payment_modules->selected_module, '', 'hidden');
						echo '</form>';
						echo '<script>window.onload = function(){document.checkout_payment.submit();}</script>';
					}
					unset($_SESSION['payment']);
					unset($_SESSION['orders_tag']); //Added by sam.chen
					unset($_SESSION['trans_id']);
					unset($_SESSION['items']);
					exit();
				}
			}
		}
	}
}
