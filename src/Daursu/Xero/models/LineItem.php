<?php namespace Daursu\Xero\Models;

class LineItem extends BaseModel {

	/**
	 * The name of the primary column.
	 *
	 * @var string
	 */
	protected $primary_column = 'LineItemID';

	/**
	 * Simple factory function to instantiate a new line item
	 *
	 */
	static public function make($description, $amount, $fields = [])
	{
		if (!isset($fields['Quantity'])) $fields['Quantity'] = 1.00;
		return new LineItem(array_merge($fields, ['Description' => $description, 'LineAmount' => number_format(round($amount,2),2)]));
	}
}