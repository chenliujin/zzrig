<?php
namespace app\product\controller;

use think\Controller;

class Index extends Controller
{
    public function index()
    {
		$product = new \stdClass();
		$product->title = 'Candance 2 Laser 5 LED Cycling Bicycle Bike Flash Taillight';
		$product->price = '13';

		$this->assign('product', $product);

		return $this->fetch();
    }
}
