<?php namespace Daursu\Xero\Models;

class Filter
{
	// The parameters we will eventually pass into the Xero get() function.
	public $params = [];

	// Access to convert to an array to be passed into the Xero API methods
	public function toArray() { return $this->params; }

	// Factory function to create an instance of Filter
	public static function make() { return new self(); }

	// Specifying a sort order
	public function orderBy($field, $sort = 'ASC')
	{
		$this->params['order'] = $field.' '.$sort;
		return $this;
	}

	// Date based - modified since
	public function modifiedSince($localTime)
	{
		if ($localTime instanceof \Carbon\Carbon)
		{
			$timestamp = $localTime->timestamp;
		}
		else if (is_string($localTime))
		{
			$timestamp = strtotime($localTime);
		}
		else
		{
			$timestamp = $localTime;
		}

		$this->params['If-Modified-Since'] = gmdate('D, d M Y H:i:s T', $timestamp);

		return $this;
	}

	// Eloquent Where style parameter
	//
	// e.g. where('Name', 'John') generates Name=="John"
	//
	public function where($field, $operator, $value = null, $glue = 'AND')
	{
		// If only two arguments, assume == operator
		if (func_num_args() == 2) list($value, $operator) = [$operator, '=='];

		if (is_string($value)) $value = '"'.$value.'"'; // Quote only string types
		if (is_null($value)) $value = 'null'; // Allow null values for comparison

		$this->addWhereClause($field.'=='.$value, $glue);

		return $this;
	}

	// Allow filtering by non-null values
	//
	public function notNull($field)
	{
		$this->addWhereClause($field.'!=null');
		return $this;
	}

	// Eloquent orWhere style parameter
	//
	public function orWhere($field, $operator, $value = null)
	{
		// If only two arguments, assume == operator
		if (func_num_args() == 2) list($value, $operator) = [$operator, '=='];

		return $this->where($field,$operator,$value,'OR');
	}

	public function contains($field, $value)     { return $this->addWhereClause($field.'.Contains("'.$value.'")',   'AND'); }
	public function startsWith($field, $value)   { return $this->addWhereClause($field.'.StartsWith("'.$value.'")', 'AND'); }
	public function endsWith($field, $value)     { return $this->addWhereClause($field.'.EndsWith("'.$value.'")',   'AND'); }
	public function orStartsWith($field, $value) { return $this->addWhereClause($field.'.StartsWith("'.$value.'")', 'OR'); }
	public function orContains($field, $value)   { return $this->addWhereClause($field.'.Contains("'.$value.'")',   'OR'); }
	public function orEndsWith($field, $value)   { return $this->addWhereClause($field.'.EndsWith("'.$value.'")',   'OR'); }

	// Add an additional clause to the where parameter
	//
	protected function addWhereClause($clause, $glue = 'AND')
	{
		if (!isset($this->params['where'])) $this->params['where'] = '';
		if (!empty($this->params['where'])) $this->params['where'] .= " $glue ";
		$this->params['where'] .= $clause;

		return $this;
	}
}