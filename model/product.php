<?php

class product extends Model
{
	/**
	 * @author chenliujin <liujin.chen@qq.com>
	 * @since 2016-08-22
	 */
	static public function GetTableName()
	{
		return 'product';
	}

	/**
	 * @author chenliujin <liujin.chen@qq.com>
	 * @since 2016-08-22
	 */
	public function getPrimaryKey()
	{
		return ['product_id'];
	}
}
