<?php defined('SYSPATH') OR die('No direct script access.');

class Model_Entry extends Versioned_Sprig {

    public function _init() {
        parent::_init();
        $this->_fields += array(
            'title'     => new Sprig_Field_Char(array(
                'empty' => TRUE,
            )),
            'revisions'  => new Sprig_Field_HasMany(array(
                'model' => 'Entry_Revision',
            )),
        );
    }
}

