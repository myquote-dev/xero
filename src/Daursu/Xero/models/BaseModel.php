<?php namespace Daursu\Xero\Models;

use \XeroOAuth, \Log, \SimpleXMLElement, \Config;
use \Daursu\Xero\Contracts\AccountsBaseModelInterface;
use \Daursu\Xero\XeroGeneralException;
use \Daursu\Xero\InvalidXeroConfigurationException;


class BaseModel implements AccountsBaseModelInterface {

	/**
	 * A reference to the XeroApi Oauth Class
	 *
	 * @var XeroOAuth
	 */
	protected $api;

	/**
	 * The name of the entity
	 *
	 * @var string
	 */
	protected static $entity;

	/**
	 * The singular name of the entity
	 *
	 * @var string
	 */
	protected static $entity_singular;

	/**
	 * Validation rules for the object
	 *
	 * @var array
	 */
	protected static $rules = array();

	/**
	 * The name of the primary column.
	 *
	 * @var string
	 */
	protected $primary_column;

	/**
	 * The format to use when retrieving data from the Xero API.
	 *
	 * @var string
	 */
	protected $format = 'xml';

	/**
	 * The path to the api. The following options are available:
	 * - core
	 * - payroll
	 *
	 * @var string
	 */
	protected $apiPath = 'core';

	/**
	 * The field name that holds the status
	 *
	 * @var string
	 */
	protected $status_field = "Status";

	/**
	 * All the model attributes
	 *
	 * @var array
	 */
	protected $attributes = array();

	/**
	 * An array containing all the relationships of its parent entity
	 *
	 * @var array
	 */
	protected $relationships = array();

	/**
	 * Constructor function.
	 * Initialises the XeroOAuth api.
	 *
	 */
	public function __construct($attributes = array())
	{
		$this->api = new XeroOAuth(Config::get('xero'));

		// Run an initial diagnostic on the configuration
		$errors = $this->api->diagnostics();

		if ( count($errors)) {
			Log::error($errors);

			throw new InvalidXeroConfigurationException("The are errors with your Xero Oauth configuration: " . implode("\n", $errors));
		}

		// Update the configuration settings
		$this->api->config['access_token'] = $this->api->config['consumer_key'];
		$this->api->config['access_token_secret'] = $this->api->config['shared_secret'];
		$this->api->config['oauth_session_handle'] = '';

		$this->setAttributes($attributes);
	}

	/**
	 * Sets the attributes for this model
	 *
	 * @param mixed $key
	 * @param mixed $value
	 */
	public function setAttribute($key, $value)
	{
		if ($value instanceof BaseModel || $value instanceof Collection)
			$this->setRelationship($value, $key);
		elseif ( !is_array($value))
			$this->attributes[$key] = $value;
		else
			$this->setRelationship($value, $key);
	}

	/**
	 * Sets the relationships on this model,
	 * which in fact are collections of other models
	 *
	 * @param string $key
	 * @param array $value
	 */
	public function setRelationship($value, $key = '')
	{
		if (is_array($value)) {

			$class = __NAMESPACE__ . '\\' . str_singular($key);

			if (class_exists($class)) {

				// Check to see if the key value is plural or singular
				// if it is plural then we create a collection
				// otherwise we instantiate the class and add a single item to the relationship
				if (str_singular($key) == $key) {
					$this->addRelationship(new $class($value));
				}
				else {
					$collection = $class::newCollection($value);
					$this->addRelationship($collection);
				}
			}
			else
			{
				// No class exists for the relationship. Add it as an array attribute instead...
				$this->attributes[$key] = $value;
			}
		}
		elseif ($value instanceof Collection) {
			$this->addRelationship($value);
		}
		elseif ($value instanceof BaseModel) {
			$this->addRelationship($value);
		}
	}

	/**
	 * Get an attribute from the model.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function getAttribute($key)
	{
		if (isset($this->attributes[$key]))
			return $this->attributes[$key];

		return $this->getRelationship($key);
	}

	/**
	 * Return all the attributes
	 *
	 * @return array
	 */
	public function getAttributes()
	{
		return $this->attributes;
	}

	/**
	 * Set all the attributes from an array.
	 *
	 * @param array $data
	 */
	public function setAttributes(array $data)
	{
		$data = $this->stripResponseData($data);

		foreach ($data as $key => $value) {
			$this->setAttribute($key, $value);
		}

		return $this->getAttributes();
	}

	/**
	 * Sets the primary id
	 *
	 * @param mixed $value
	 */
	public function setId($value)
	{
		$this->setAttribute($this->primary_column, $value);
		return $this;
	}

	/**
	 * Retrieve the primary id
	 *
	 * @return mixed
	 */
	public function getId()
	{
		return $this->getAttribute($this->primary_column);
	}

	/**
	 * Dynamically retrieve attributes on the model.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function __get($key)
	{
		return $this->getAttribute($key);
	}

	/**
	 * Dynamically set attributes on the model.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function __set($key, $value)
	{
		$this->setAttribute($key, $value);
	}

	/**
	 * Check an attribute on the model.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function __isset($key)
	{
		if (isset($this->attributes[$key])) return true;
		if (!is_null($this->getRelationship($key))) return true;
		return false;
	}

	/**
	 * Add an object as a relationship
	 *
	 * @param  string  $name
	 * @param  mixed   $arguments
	 * @return mixed
	 */
	public function push($object)
	{
		$class = get_class($object);
		$relationshipName = str_plural(last(explode('\\',$class)));
		$relationship = $this->getRelationship($relationshipName);
		if (is_null($relationship))
		{
			$this->addRelationship($class::newCollection());
			$relationship = $this->getRelationship($relationshipName);
		}

		if ($relationship instanceof Collection)
		{
			$relationship->push($object);
		}

		return $this;
	}

	/**
	 * Add a new relationship
	 *
	 * @param mixed $value
	 */
	public function addRelationship($value)
	{
		return array_push($this->relationships, $value);
	}

	/**
	 * Retrieves the named relationship (or NULL if it doesn't exist)
	 *
	 * @return mixed
	 */
	public function getRelationship($name)
	{
		foreach ($this->relationships as $key => $value)
		{
			if ($value->getSingularEntityName() == str_singular($name)) return $value;
		}
		return null;
	}

	/**
	 * Retrieves the api url.
	 *
	 * @return string
	 */
	public function getUrl()
	{
		return $this->api->url(static::getEntityName(), $this->apiPath);
	}

	/**
	 * Retrieves the api url for this particular instance of the entity
	 *
	 * @return string
	 */
	public function getInstanceUrl()
	{
		return $this->getUrl().'/'.$this->getId();
	}

	/**
	 * Return the entity name
	 *
	 * @return string
	 */
	public static function getEntityName()
	{
		if (isset(self::$entity))
			return self::$entity;

		return str_plural(class_basename(get_called_class()));
	}

	/**
	 * Retrieves the singular name of the entity we are querying.
	 *
	 * @return string
	 */
	public static function getSingularEntityName()
	{
		if ( isset(self::$entity_singular) && ! empty(self::$entity_singular))
			return self::$entity_singular;

		return str_singular(static::getEntityName());
	}

	/**
	 * A high level request method used from static methods.
	 *
	 * @param  string $method
	 * @param  string $url
	 * @param  array  $params
	 * @param  string $xml
	 * @param  string $format
	 * @return Daursu\Xero\BaseModel
	 */
	public function request($method, $url, $params = array(), $xml = "", $format = "")
	{
		if (!$format) $format = $this->format;
		if ($params instanceof Filter) $params = $params->toArray();
		$response = $this->api->request($method, $url, $params, $xml, $format);
		return $this->parseResponse($response);
	}

	/**
	 * Get the model data as an array filtered by the class name or return null if no data present
	 *
	 * @param  array  $params
	 * @return mixed
	 */
	public static function getModelData($params = array())
	{
		$object = new static;
		$data = $object->request('GET', $object->getUrl(), $params);
		if (!$object->hasResponseData($data)) return null;

		// We have some valid data in this response.
		return $object->stripResponseData($data);
	}

	/**
	 * Get a collection of items
	 *
	 * @param  array  $params
	 * @return Daursu\Xero\BaseModel
	 */
	public static function get($params = array())
	{
		// Initialise a collection
		$collection = self::newCollection();

		// Make the request and get data as an array
		$data = static::getModelData($params);
		if (is_null($data)) return $collection;

		if (isset($data[0]) && is_array($data[0])) {
			// This should be a collection
			$collection->setItems($data);
		}
		// Handle single element in a collection as per organisation response
		else if (is_array($data))
		{
			$collection->push($data);
		}

		return $collection;
	}

	/**
	 * Find a single element by its ID
	 *
	 * @param  mixed $id
	 * @return Daursu\Xero\BaseModel
	 */
	public static function find($id)
	{
		$object = new static;
		$response = $object->request('GET', sprintf('%s/%s', $object->getUrl(), $id));

		return $response ? $object : null;
	}

	/**
	 * Get a single element by using where clauses
	 *
	 * @param  array $params
	 * @return Daursu\Xero\BaseModel
	 */
	public static function findBy($params)
	{
		return self::get($params);
	}

	/**
	 * Creates a new entity in Xero
	 *
	 * @param  array $data
	 * @return boolean
	 */
	public function create($params = array())
	{
		$response = $this->api->request('PUT', $this->getUrl(), $params, $this->toXML(), $this->format);
		return $this->parseResponse($response) ? true : false;
	}

	/**
	 * Update an existing entity in Xero
	 *
	 * @param  mixed $id
	 * @param  array $data
	 * @return boolean
	 */
	public function update($params = array())
	{
		$response = $this->api->request('POST', $this->getUrl(), $params, $this->toXML(), $this->format);
		return $this->parseResponse($response) ? true : false;
	}

	/**
	 * Save an entity. If it doesn't have the primary key set
	 * then it will create it, otherwise it will update it.
	 *
	 * @param  array $params
	 * @return boolean
	 */
	public function save($params = array())
	{
		if ( isset($this->attributes[$this->primary_column])) {
			return $this->update($params);
		}
		else {
			return $this->create($params);
		}
	}

	/**
	 * Delete an existing entity
	 *
	 * @param  mixed $id
	 * @return array
	 */
	public function delete()
	{
		if ( !isset($this->attributes[$this->primary_column]))
			throw new XeroGeneralException(sprintf("The %s attribute is required.", $this->primary_column));

		$this->setAttribute($this->status_field, 'DELETED');

		$response = $this->api->request('POST', $this->getUrl(), array(), $this->toXML(), $this->format);
		return $this->parseResponse($response) ? true : false;
	}

	/**
	 * Make a PUT request to attach the provided model to this one
	 *
	 * @param  array $params
	 * @return boolean
	 */
	public function associate(BaseModel $model, $params = array())
	{
		$url = $this->getInstanceUrl() . '/' . $model->getEntityName();
		$response = $this->api->request('PUT', $url, $params, $model->toXML(), $this->format);
		return $this->parseResponse($response) ? true : false;
	}

	/**
	 * Create a new collection
	 *
	 * @return Daursu\Xero\Collection
	 */
	public static function newCollection(array $items = array())
	{
		return new Collection(static::getEntityName(), static::getSingularEntityName(), $items);
	}

	/**
	 * Convert the model to an array
	 *
	 * @return array
	 */
	public function toArray()
	{
		$output = $this->getAttributes();

		foreach ($this->relationships as $key => $value) {

			if ($value instanceof BaseModel) {
				$value = array($value->getSingularEntityName() => $value->toArray());
			}
			else {
				$value = $value->toArray();
			}

			$output = array_merge($output, $value);
		}

		return $output;
	}

	/**
	 * Convert the model to JSON
	 *
	 * @param  int	$options
	 * @return string
	 */
	public function toJson($options = 0)
	{
		return json_encode($this->toArray(), $options);
	}

	/**
	 * Converts the model to XML
	 *
	 * @return string
	 */
	public function toXML($singular = false)
	{
		$root = ($singular) ? static::getSingularEntityName() : static::getEntityName();

		$output = new SimpleXMLElement(
			sprintf('<%s></%s>', $root, $root)
		);

		if ( ! $singular) {
			$node = $output->addChild(static::getSingularEntityName());
			self::array_to_xml($this->toArray(), $node);
		}
		else {
			self::array_to_xml($this->toArray(), $output);
		}

		return $output->asXML();
	}

	/**
	 * Convert the model to its string representation.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->toJson();
	}

	/**
	 * Helper function to convert an array to XML
	 *
	 * @param  array $array
	 * @param  SimpleXMLElement $xml
	 * @return string
	 */
	public static function array_to_xml($array, &$xml)
	{
		foreach($array as $key => $value) {
			if(is_array($value)) {
				if(!is_numeric($key)){
					$subnode = $xml->addChild("$key");
					self::array_to_xml($value, $subnode);
				} elseif ($key == 0) {
					self::array_to_xml($value, $xml);
				} else {
					$name = $xml->getName();
					$subnode = $xml->xpath("..")[0]->addChild("$name");
					self::array_to_xml($value, $subnode);
				}
			} else {
				$childName = "$key";
				$xml->$childName = "$value";
			}
		}
	}

	/**
	 * This function removes all the unecessary data from a response
	 * and leaves us with what we need when trying to populate objects or
	 * collections with data
	 *
	 * @param  array $data
	 * @return array
	 */
	public function stripResponseData(array $data)
	{
		if (isset($data[static::getEntityName()]) && is_array($data[static::getEntityName()]))
			$data = $data[static::getEntityName()];

		if (isset($data[static::getSingularEntityName()]) && is_array($data[static::getSingularEntityName()]))
			$data = $data[static::getSingularEntityName()];

		return $data;
	}

	/**
	 * This function checks for existence of response data to indicate a successful query
	 *
	 * @param  array $data
	 * @return boolean
	 */
	public function hasResponseData($data)
	{
		$hasData = false;

		if (isset($data[static::getEntityName()]) && is_array($data[static::getEntityName()])) {
			$data = $data[static::getEntityName()];
			$hasData = true;
		}

		if (isset($data[static::getSingularEntityName()]) && is_array($data[static::getSingularEntityName()])) {
			$data = $data[static::getSingularEntityName()];
			$hasData = true;
		}

		return ($hasData ? !empty($data) : false);
	}

	/**
	 * Parses the response retrieved from Xero,
	 * or throws an exception if it fails.
	 *
	 * @param  array $response
	 * @return array
	 */
	protected function parseResponse($response, $setAttributes = true)
	{
		if ($response['code'] == 200)
		{
			$data = $this->api->parseResponse($response['response'], $response['format']);

			if ($response['format'] == 'xml') {
				$data = json_encode($data);
				$data = json_decode($data, true);
			}

			// print_r($data);

			if ($setAttributes && is_array($data))
				$this->setAttributes($data);

			return $data;
		}
		elseif ($response['code'] == 404)
		{
			return false;
		}
		else
		{
			$exception = new XeroGeneralException('Error from Xero: ' . $response['response']);
			$exception->code = $response['code'];
			throw $exception;
		}
	}
}
