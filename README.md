# Versioned

Revision history for text-centric models.  Currently, only available for Sprig (ORM to be implemented in the future).

## Creating a Versioned Model

In order to create a versioned model (such as a blog article model, or a page content model), two models must be defined.

### The "entry" model

The main entry model must extend `Versioned_Sprig` (or `Versioned_ORM` when it is implemented) and define the `revisions`
has-many field.  The field for which diffs are stored should be a `Sprig_Field_Versioned` field type and a field for which
changes are tracked (and stored as revision comments) should be a `Sprig_Field_Tracked` field type.  For example:

    class Model_Article extends Versioned_Sprig {
        public function _init() {
            parent::_init();
            $this->_fields += array(
                'title'     => new Sprig_Field_Tracked(array(
                    'empty' => FALSE,
                )),
                'text'      => new Sprig_Field_Versioned,
                'revisions' => new Sprig_Field_HasMany(array(
                    'model' => 'Article_Revision',
                )),
            );
        }
    }

### The "revision" model

Every versioned model must have a corresponding model to represent its revisions.  This model must extend `Versioned_Revision` and
define the `entry` belongs-to field.  For example:

    class Model_Article_Revision extends Versioned_Revision {
        public function _init() {
            parent::_init();
            $this->_fields += array(
                'entry' => new Sprig_Field_BelongsTo(array(
                    'model' => 'Article',
                )),
            );
        }
    }

Obviously, the entry model's `revisions` field must reference the corresponding revision model, while the revision model's `entry`
field must reference the corresponding entry model.

## Using a Versioned Model

### Basic Operations

A versioned model is treated no differently than a normal Sprig (or ORM) model.  Values can be set and the model can be created or updated.

The following fields are provided by `Versioned_Sprig`: `version`, `comment`, `editor`.  Obviously, `version` will contain the current version number.  `text` contains the main entry body, and is version controlled (ie, changes to the `text` field result in a new revision and version number increment).  The `comment` field can be set with a string to be recorded along with the new revision.  This can be useful for requiring users to record why they are changing a versioned entry.  The `editor` field can be set with the user ID of the current user making the modification (each revision has an editor).

If a Sprig_Field_Tracked field has been specified, `Versioned_Sprig` will track changes to that field, creating a revision comment indicating the change.  For example,

    $entry = Sprig::factory('article', array('id'=>4))->load();
    $entry->text = "Some modification to text.";
    $entry->title = "Versioned Entry #2";            // Previously "Versioned Entry #1"
    $entry->comment = "Fixed spelling error";
    $entry->editor = 3;
    $entry->update();

Will result in the comments, "Fixed spelling error" and "Title changed from 'Versioned Entry #1' to 'Versioned Entry #2'", to be recorded along with the revision.

When a versioned entry is deleted, all of its revisions are deleted from the database as well.

### Revision History

To retrieve a specific version of an entry, the `version()` method is provided by `Versioned_Sprig`.  The integer argument indicates which version to retrieve.  For example,

    $entry = Sprig::factory('article', array('id'=>3))->load();
    echo $entry->text;      // echoes "Article text for version 5"
    echo $entry->version;   // echoes "5"
    $entry->version(2);
    echo $entry->text;      // echoes "Article text for version 2"
    echo $entry->version;   // echoes "2"

Note: the `text` and `version` fields are the only fields modified by a `version()` call.

The revisions of a versioned entry can be accessed by the `revisions` field of the entry.  This can be used for printing out a list of changes made to the entry.  For example,

    $entry = Sprig::factory('article', array('id'=>6))->load();
    $revisions = $entry->revisions;
    foreach($revisions as $revision) {
        echo $revision->version;
        echo $revision->date;
        echo $revision->editor->name;
        echo implode("\n", $revision->comments);
    }

## Working with the Version Library

The `Version` library included with the Versioned Module can assist with showing differences between versions of text.

Inline difference (uses &lt;ins&gt; and &lt;del&gt; tags to mark up the text):
    $entry = Sprig::factory('article', array('id'=>2))->load();
    $new_text = $entry->text;
    $entry->version($entry->version - 1);
    $old_text = $entry->text;
    echo Version::inline_diff($old_text, $new_text);

Side-by-side difference (produces two arrays, showing added/deleted lines between two versions):
    $entry = Sprig::factory('article', array('id'=>2))->load();
    $new_text = $entry->text;
    $entry->version($entry->version - 1);
    $old_text = $entry->text;
    $diff = Version::side_diff($old_text, $new_text);

    echo '<ul class="old_text">';
    foreach($diff['old'] as $line) {
        echo $line;
    }
    echo '</ul>';

    echo '<ul class="new_text">';
    foreach($diff['new'] as $line) {
        echo $line;
    }
    echo '</ul>';

## Database Schemas

### Entries Table


    CREATE TABLE IF NOT EXISTS `entries` ( 
        `id` int(11) NOT NULL auto_increment, 
        `version` int(4) NOT NULL, 
        `title` varchar(100) NOT NULL, 
        `text` text NOT NULL, 
        PRIMARY KEY (`id`) 
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8; 

### Revisions Table


    CREATE TABLE IF NOT EXISTS `entry_revisions` ( 
        `id` int(11) NOT NULL auto_increment, 
        `entry_id` int(11) NOT NULL, 
        `version` int(4) NOT NULL, 
        `date` int(10) NOT NULL, 
        `editor_id` int(11) NOT NULL, 
        `diff` text NOT NULL, 
        `comment` text NOT NULL, 
        PRIMARY KEY (`id`) 
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
