<?php namespace Daursu\Xero\Models;

class BankTransaction extends BaseModel {

	use \Daursu\Xero\LineItemTraits, \Daursu\Xero\LineAmountTraits;

	/**
	 * The name of the primary column.
	 *
	 * @var string
	 */
	protected $primary_column = 'BankTransactionID';
}