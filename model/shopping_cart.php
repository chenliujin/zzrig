<?php

class shopping_cart extends Model
{
	/**
	 * @author chenliujin <liujin.chen@qq.com>
	 * @since 2016-08-23
	 */
	static public function GetTableName()
	{
		return 'shopping_cart';
	}

	/**
	 * @author chenliujin <liujin.chen@qq.com>
	 * @since 2016-08-23
	 */
	public function getPrimaryKey()
	{
		return ['id'];
	}

}
