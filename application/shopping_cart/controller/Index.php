<?php

namespace app\shopping_cart\controller;

use \think\Controller;

class Index extends Controller
{
	/**
	 * @author chenliujin <liujin.chen@qq.com>
	 * @since 2016-08-22
	 */
	public function index()
	{
		$params = [
			'customer_id' => 1,
		];

		$shopping_cart = new \shopping_cart;
		$shopping_cart_list = $shopping_cart->findAll($params);

		$this->assign('shopping_cart_list', $shopping_cart_list);

		return $this->fetch();
	}
}
