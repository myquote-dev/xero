<?php namespace Daursu\Xero\Models;

class JournalLine extends BaseModel {
	/**
	 * Simple factory function to instantiate a new line
	 *
	 */
	static public function make($description, $amount, $account_code, $fields = [])
	{
		return new JournalLine(array_merge($fields,
			[
				'Description' => $description,
				'LineAmount' => number_format(round($amount,2),2, '.', ''),
				'AccountCode' => $account_code
			]));
	}
}
