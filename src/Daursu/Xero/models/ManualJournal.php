<?php namespace Daursu\Xero\Models;

class ManualJournal extends BaseModel {

	use \Daursu\Xero\LineAmountTraits;

	/**
	 * The name of the primary column.
	 *
	 * @var string
	 */
	protected $primary_column = 'ManualJournalID';
}