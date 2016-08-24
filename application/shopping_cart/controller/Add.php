<?php

namespace app\shopping_cart\controller;

use \think\Controller;

class Add extends Controller
{
	/**
	 * @author chenliujin <liujin.chen@qq.com>
	 * @since 2016-08-23
	 */
	public function Index()
	{
		$customer_id = 1;
		$product_id = intval($_POST['product_id']);
		$quantity	= intval($_POST['quantity']);

		$shopping_cart = new \shopping_cart;
		$shopping_cart->customer_id = $customer_id;
		$shopping_cart->product_id 	= $product_id;
		$shopping_cart->quantity	= $quantity; 
		$rs = $shopping_cart->insert();

		var_dump($rs);
	}
}
