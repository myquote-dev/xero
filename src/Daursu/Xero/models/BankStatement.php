<?php
/**
 * Created by PhpStorm.
 * User: marcel
 * Date: 1/11/16
 * Time: 3:20 PM
 */

namespace Daursu\Xero\Models;


class BankStatement extends Report
{

	/**
	 * Get the report and convert it into a more usable format
	 *
	 * @param  string  $id
	 * @param  mixed   $from
	 * @param  mixed   $to
	 * @return array   $result
	 */
	public static function getArray($id, $from = null, $to = null)
	{
		$params = ['BankAccountID' => $id];
		if (!is_null($from)) $params['fromDate'] = $from;
		if (!is_null($to))   $params['toDate']   = $to;

		$object = static::get($params);
		if (is_null($object)) return [];

		return $object->attributes;
	}

}