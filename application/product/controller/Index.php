<?php
namespace app\product\controller;

use think\Controller;

class Index extends Controller
{
    public function index()
    {
		$product_id = 1;

		$product = new \product;
		$product = $product->get($product_id);

		$product_description = new \product_description_en;
		$product_description = $product_description->get($product_id);

		$this->assign('product', $product);
		$this->assign('product_description', $product_description);

		return $this->fetch();
    }
}
