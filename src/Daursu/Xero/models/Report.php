<?php
/**
 * Created by PhpStorm.
 * User: marcel
 * Date: 1/11/16
 * Time: 3:23 PM
 */

namespace Daursu\Xero\Models;


class Report extends BaseModel
{
	/**
	 * The name of the primary column.
	 *
	 * @var string
	 */
	protected $primary_column = 'ReportID';


	/**
	 * Get a report and store the result as array attributes
	 *
	 * @param  array  $params
	 * @return Daursu\Xero\BaseModel
	 */
	public static function get($params = array())
	{
		$result = static::getModelData($params);
		if (is_null($result)) return null;

		$object = new static;
		$object->attributes = $result;
		return $object;
	}

	/**
	 * Retrieves the api url.
	 *
	 * @return string
	 */
	public function getUrl()
	{
		$class = str_singular(class_basename(get_called_class()));
		return $this->api->url('Reports/'.$class, $this->apiPath);
	}

	/**
	 * Return the entity name
	 *
	 * @return string
	 */
	public static function getEntityName()
	{
		return 'Reports';
	}
}