<?php namespace Daursu\Xero;

trait LineAmountTraits
{
	/**
	 * Some quick and easy setters
	 *
	 */
	public function taxInclusive() { $this->LineAmountTypes = 'Inclusive'; }
	public function taxExclusive() { $this->LineAmountTypes = 'Exclusive'; }
	public function noTax()        { $this->LineAmountTypes = 'NoTax'; }
}