<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Versioned Sprig model
 *
 * @package     Versioned
 * @author      Kyle Treubig
 * @copyright   (c) 2010 Kyle Treubig
 * @license     MIT
 */
class Versioned_Sprig extends Sprig {

	/**
	 * Initialize the Sprig model fields
	 * necessary for versioning
	 */
	protected function _init() {
		$this->_fields += array(
			'id'        => new Sprig_Field_Auto,
			'version'   => new Sprig_Field_Integer(array(
				'editable'  => FALSE,
				'default'   => 1,
			)),
			// Internal, not in DB
			'editor'    => new Sprig_Field_Integer(array(
				'editable'  => FALSE,
				'in_db'  => FALSE,
				'default'   => 1,
			)),
			'comments'  => new Sprig_Field_Char(array(
				'editable'  => FALSE,
				'in_db'  => FALSE,
				'default'   => array(),
			)),
		);
	}

	/**
	 * Overload Sprig::create() to create new version
	 * entry upon creation
	 */
	public function create() {
		Kohana::$log->add(Kohana::DEBUG, 'Executing Versioned_Sprig::create');

		parent::create();
		$revision = Sprig::factory($this->_model.'_revision');
		$revision->values(array(
			'entry'  => $this->{$this->_primary_key},
			'version'   => $this->version,
			'editor'    => $this->editor,
		));
		return $revision->create();
	}

	/**
	 * Overload Sprig::update() to save revision change
	 * @param bump  whether to bump the version number
	 */
	public function update($bump=TRUE) {
		Kohana::$log->add(Kohana::DEBUG, 'Executing Versioned_Sprig::update');

		$updated = FALSE;

		foreach ($this->_fields as $field=>$object)
		{
			if ($object instanceof Sprig_Field_Tracked AND $this->changed($field))
			{
				$this->comment = UTF8::ucwords($object->label).' changed from "'.$this->_original[$field]
					.'" to "'.$this->_changed[$field].'".';
			}

			if ($object instanceof Sprig_Field_Versioned AND $this->changed($field) AND $bump)
			{
				$diff = '';
				if ($this->version != 0) {
					$diff = Versioned::diff($this->_original[$field], $this->_changed[$field]);
					$diff = Versioned::clean_array($diff);
					$diff = serialize($diff);
				}
				$this->version++;

				$revision = Sprig::factory($this->_model.'_revision');
				$revision->values(array(
					'entry'  => $this->id,
					'version'   => $this->version,
					'editor'    => $this->editor,
					'diff'      => $diff,
				));
				$revision->comments = $this->comments;
				$revision->create();

				$updated = TRUE;
				$this->comments = array();
			}
		}

		if ( ! $updated AND count($this->comments) > 0)
		{
			$revision = Sprig::factory($this->_model.'_revision');
			$revision->entry = $this->id;
			$revision->version = $this->version;
			$revision->load();
			$revision->comments = array_merge($revision->comments, $this->comments);
			$revision->update();
		}

		return parent::update();
	}

	/**
	 * Retrieve a specific version
	 * @param version   version of text to retrieve
	 */
	public function version($version) {
		Kohana::$log->add(Kohana::DEBUG, 'Executing Versioned_Sprig::version');

		if ($version < 1) {
			$version = 1;
		} elseif ($version == 'head' OR $version > $this->_original['version']) {
			$version = $this->_original['version'];
		}

		foreach ($this->_fields as $field=>$object)
		{
			if ($object instanceof Sprig_Field_Versioned)
			{
				$revisions = $this->revisions;
				$revision = $revisions->current();
				$text = $this->_original[$field];

				while ($revision->version > $version) {
					$diff = $revision->diff;
					$text = Versioned::patch($text, $diff, TRUE);
					$revision = $revisions->next()->current();
				}

				$this->{$field} = $text;
				$this->version = $version;
			}
		}
	}

	/**
	 * Overload Sprig::__set() to append comments to array
	 */
	public function __set($key, $value) {
		if ($key == 'comment') {
			Kohana::$log->add(Kohana::DEBUG, 'Executing Versioned_Sprig::__set(comment)');
			if ( ! empty($value)) {
				$this->comments = array_merge($this->comments, (array) $value);
			}
			return;
		}
		return parent::__set($key, $value);
	}
}

