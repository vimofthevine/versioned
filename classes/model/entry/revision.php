<?php defined('SYSPATH') OR die('No direct access allowed.');

class Model_Entry_Revision extends Model_Revision {

    public function _init() {
        parent::_init();
        $this->_fields += array(
            'entry' => new Sprig_Field_BelongsTo(array(
                'model' => 'Entry',
            )),
        );
    }
}

