<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Versioned module revision model
 *
 * @package     Versioned
 * @author      Kyle Treubig
 * @copyright   (c) 2010 Kyle Treubig
 * @license     MIT
 */
class Versioned_Revision extends Sprig {

    protected $_sorting = array('version' => 'desc');

    /**
     * Initialize the Sprig model fields
     */
    public function _init() {
        $this->_fields += array(
            'id'        => new Sprig_Field_Auto,
            'version'   => new Sprig_Field_Integer(array(
                'default'   => 1,
            )),
            'date'      => new Sprig_Field_Timestamp(array(
                'auto_now_create' => TRUE,
            )),
            'editor'    => new Sprig_Field_BelongsTo(array(
                'model'     => 'User',
                'column'    => 'editor_id',
                'default'   => 0,
            )),
            'diff'      => new Sprig_Field_Text(array(
                'empty' => TRUE,
            )),
            'comment'   => new Sprig_Field_Text(array(
                'empty' => TRUE,
            )),
        );
    }

    /**
     * Overload Sprig::__get() to return comments array
     * @param key   Variable name
     */
    public function __get($key) {
        if ($key == 'comments') {
            return empty($this->comment) ? array() : unserialize($this->comment);
        } elseif ($key == 'diff') {
            return empty($this->_original['diff']) ? '' : unserialize($this->_original['diff']);
        } else {
            return parent::__get($key);
        }
    }

    /**
     * Overload Sprig::__set() to serialize comments
     * @param key   Variable name
     * @param value Variable value
     */
    public function __set($key, $value) {
        if ($key == 'comments') {
            $this->comment = serialize($value);
            return;
        }
        parent::__set($key, $value);
    }

}

