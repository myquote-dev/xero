<?php namespace Daursu\Xero\Models;

class Filter
{
	// The parameters we will eventually pass into the Xero get() function.
	public $params = [];

	// Access to convert to an array to be passed into the Xero API methods
	public function toArray() { return $this->params; }

	// Factory function to create an instance of Filter
	public function make() { return new self(); }

	// Date based - modified since
	public function modifiedSince($localTime)
	{
		$timestamp = $localTime;

		if ($localTime instanceof Carbon)
		{
			$timestamp = $localTime->timestamp;
		}
		else if (is_string($localTime))
		{
			$timestamp = strtotime($localTime);
		}

		$this->params['If-Modified-Since'] = gmdate('D, d M Y H:i:s T', $timestamp);

		return $this;
	}

	// Eloquent Where style parameter
	//
	// e.g. where('Name', 'John') generates Name=="John"
	//
	public function where($field, $value, $glue = 'AND')
	{
		if (!isset($params['where'])) $params['where'] = '';
		if (!empty($params['where'])) $params['where'] .= " $glue ";

		$params['where'] .= $field.'=='.$value;

		return $this;
	}

	// Eloquent orWhere style parameter
	//
	// e.g. where('Name', 'John') generates Name=="John"
	//
	public function orWhere($field, $value) { return $this->where($field,$value,'OR'); }
}