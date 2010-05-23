<?php defined('SYSPATH') OR die('No direct script access.');

class Model_Entry extends Versioned_Sprig {

	public function _init() {
		parent::_init();
		$this->_fields += array(
			'text'      => new Sprig_Field_Versioned,
			'title'     => new Sprig_Field_Tracked(array(
				'empty' => TRUE,
			)),
			'revisions' => new Sprig_Field_HasMany(array(
				'model' => 'Entry_Revision',
			)),
		);
	}
}

