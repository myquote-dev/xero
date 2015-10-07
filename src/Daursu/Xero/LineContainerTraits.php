<?php namespace Daursu\Xero;

trait LineContainerTraits
{
	/*
	 * Calculate the total by adding up all the line items
	 */

	public function lineItemsTotal()
	{
		$amount = null;
		if (!isset($this->LineItems) || empty($this->LineItems)) return $amount;

		foreach ($this->LineItems as $line)
		{
			$amount += $line->LineAmount;
		}

		return $amount;
	}
}