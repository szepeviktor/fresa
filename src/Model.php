<?php

namespace Fresa;

/**
 * Model base class
 */
abstract class Model
{
	use Concerns\CastsAttributes,
		Concerns\HasRelationships,
		Concerns\HasAttributes;

	/**
	 * The WP Post ID
	 * @var Int
	 */
	public $id = 0;

	/**
	 * The reserved keys on every model
	 * @var Array
	 */
	protected $default = [];

	/**
	 * Does this model exist in the database?
	 * @var Boolean
	 */
	public $exists = false;

	/**
	 * Define a set of required keys to validate against
	 * @var Array
	 */
	protected $required = [];

	public function __construct($args = [])
	{
		$this->hydrate($args);
	}

	/**
	 * Save the data for this model
	 * @return self
	 */
	public function save()
	{
		// Validate the data is good
		$this->validate();

		// Insert the base post if it's not there yet
		if ( empty($this->id) ) {
			$this->insertModel();
			$this->exists = true;
		} else {
			$this->persistDefaultFields();
		}

		$this->persistMetaFields();

		return $this;
	}

	/**
	 * Persist the WP Post fields to the database
	 * @return self
	 */
	abstract protected function persistDefaultFields();

	/**
	 * Persist meta fields to the DB
	 */
	abstract protected function persistMetaFields();

	/**
	 * Validate the current model for required keys
	 * @return Boolean  	Passes validation
	 * @throws Exception  	If validation fails
	 */
	protected function validate()
	{
		collect($this->required)->each(function($key) {
			if ( empty($this->attributes[$key]) ) {
				throw new \Exception("A {$key} attribute is required");
			}
		});

		return true;
	}

	/**
	 * Get a model from the database
	 * @param  Int $id    Post ID
	 * @return Model
	 */
	public static function find($id)
	{
		return (new static)->newFromObjectId($id);
	}

	/**
	 * Get a new instance from a DB object
	 * @var Mixed
	 */
	abstract public function newFromObject($object);

	/**
	 * Get a new instance from the ID of a DB object
	 * @var Int
	 */
	abstract public function newFromObjectId($objectId);

	/**
	 * Get the keys from the subclass
	 * @return Array
	 */
	public function keys()
	{
		return $this->keys;
	}

    /**
     * Hydrate attributes on object from arguments and meta
     * @param  array  $args Arguments
     * @return self
     */
    protected function hydrate($args = [])
    {
		if (!empty($args['id'])) {
			$this->exists = true;
			$this->id = (int) $args['id'];
			unset($args['id']);
		}

        foreach ($args as $key => $arg) {
            $this->setAttribute($key, $arg);
        }

        // Ensure the default keys exists on the object as well
        foreach ($this->default as $key) {
            if (empty($this->attributes[$key])) {
                $this->setAttribute($key, '');
            }
        }

        if ($this->exists) {
            $this->fillExistingMeta($this->fetchMetaFields());
        }

        return $this;
    }

    /**
     * Provide a method to easily fetch default values from the object for the DB
     * @var array
     */
    abstract public function getDefaultValues();

	/**
	 * Fetch meta fields and assign them to object keys
	 */
	abstract protected function fetchMetaFields();

	/**
	 * Get all models in the database
	 * @return Collection
	 */
	abstract public static function all();

	/**
	 * Instantiate a new model instance and save it
	 * @param  Array $args  Args
	 * @return Model
	 */
	public static function create($args = [])
	{
		return (new static($args))->save();
	}

	/**
	 * Provide a way to move this to a string
	 * @return string
	 */
	public function __toString()
	{
		return serialize($this);
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
     * @param  mixed  $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }
}
