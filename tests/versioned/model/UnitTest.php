<?php defined('SYSPATH') or die('No direct access allowed.');

/**
 * Versioned module entry model unit tests
 *
 * @author  Kyle Treubig
 * @group   versioned
 * @group   versioned.model.entry
 */
class Versioned_Model_UnitTest extends PHPUnit_Framework_TestCase {

    /**
     * Setup the test case
     * - Create MySQL entries & history tables
     * - Insert mock entry
     */
    protected function setUp() {
        // Use unit test database
        Kohana::config('database')->default['connection']['database'] = "unit_test";
        // import test schema file
        $entries_schema = Kohana::find_file('queries/schemas', 'entries', 'sql');
        $entries_sql = file_get_contents($entries_schema);
        $revisions_schema = Kohana::find_file('queries/schemas', 'entry_revisions', 'sql');
        $revisions_sql = file_get_contents($revisions_schema);
        try {
            DB::query(Database::INSERT, $entries_sql)->execute();
            DB::query(Database::INSERT, $revisions_sql)->execute();
        } catch (Database_Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * Clean up the test case
     * - drop the MySQL tables
     */
    protected function tearDown() {
        DB::query(Database::DELETE, 'DROP TABLE entries')->execute();
        DB::query(Database::DELETE, 'DROP TABLE entry_revisions')->execute();
    }

    /**
     * Test saving of the first version
     * As in, create new article
     */
    public function testSavingFirstVersion() {
        $SUT = Sprig::factory('entry');
        $SUT->text = 'First version of a revisionable article.';
        try {
            $SUT->create();
        } catch (Validate_Exception $e) {
            echo $e->getMessage();
            print_r($e->array->errors('versioned'));
        }

        $revision = $SUT->revisions->current();
        $this->assertEquals(1, $revision->version);
        $this->assertEquals('', $revision->diff);
        $this->assertTrue(empty($revision->comments));
    }

    /**
     * Test saving of the first revision
     * As in modify pre-filled entry (page content)
     */
    public function testSavingFirstRevision() {
        DB::insert('entries', array('id', 'text', 'version'))
            ->values(array(2, 'Prefab entry', 0))->execute();

        $SUT = Sprig::factory('entry', array('id'=>2))->load();
        $SUT->text = 'First version of a prefab entry.';
        $SUT->update();

        $revision = $SUT->revisions->current();
        $this->assertEquals(1, $revision->version);
        $this->assertEquals('', $revision->diff);
        $this->assertTrue(empty($revision->comments));
    }

    /**
     * Test saving of new version
     */
    public function testSavingNewVersion() {
        DB::insert('entries', array('id', 'text', 'version'))
            ->values(array(3, 'Test the save method, first version', 1))->execute();

        $SUT = Sprig::factory('entry', array('id'=>3))->load();
        $SUT->text = 'Test the update method, first version revision';
        $SUT->update();

        $revision = $SUT->revisions->current();
        $this->assertEquals(2, $revision->version, "The versions don't match");
        $this->assertEquals(2, count($revision->diff), "The number of diffs don't match");
        $this->assertEquals('save', $revision->diff[3]['d'][0]);
        $this->assertEquals('revision', $revision->diff[7]['i'][0]);
    }

    /**
     * Test saving with comment
     */
    public function testSavingWithComment() {
        DB::insert('entries', array('id', 'text', 'version'))
            ->values(array(4, 'Prefab entry', 2))->execute();

        $SUT = Sprig::factory('entry', array('id'=>4))->load();
        $SUT->text = 'Saved with a comment';
        $SUT->comment = 'This is my comment';
        $SUT->update();

        $revision = $SUT->revisions->current();
        $this->assertEquals(3, $revision->version);
        $this->assertEquals(1, count($revision->comments));
        $this->assertEquals('This is my comment', $revision->comments[0]);
    }

    /**
     * Test saving of comment, no new version
     */
    public function testSavingOfComment() {
        DB::insert('entries', array('id', 'text', 'version'))
            ->values(array(5, 'Prefab entry', 3))->execute();
        DB::insert('entry_revisions', array('entry_id', 'version'))
            ->values(array(5, 3))->execute();

        $SUT = Sprig::factory('entry', array('id'=>5))->load();
        $SUT->comment = 'This is a new comment';
        $SUT->update();

        $revision = $SUT->revisions->current();

        $this->assertEquals(3, $revision->version);
        $this->assertEquals(1, count($revision->comments));
        $this->assertEquals('This is a new comment', $revision->comments[0]);
    }

    /**
     * Test saving changed title
     */
    public function testSavingChangedTitle() {
        DB::insert('entries', array('id', 'title', 'version'))
            ->values(array(6, 'My Title', 4))->execute();
        DB::insert('entry_revisions', array('entry_id', 'version'))
            ->values(array(6, 4))->execute();

        $SUT = Sprig::factory('entry', array('id'=>6))->load();
        $SUT->title = 'Your Title';
        $SUT->update();

        $revision = $SUT->revisions->current();

        $this->assertEquals(4, $revision->version);
        $this->assertEquals(1, count($revision->comments));
        $this->assertRegExp('/My Title.*Your Title/', $revision->comments[0]);
    }

    /**
     * Test saving but no new version
     */
    public function testSavingNoRevision() {
        DB::insert('entries', array('id', 'text', 'version'))
            ->values(array(6, 'Prefab entry', 2))->execute();
        DB::insert('entry_revisions', array('entry_id', 'version'))
            ->values(array(6, 2))->execute();

        $SUT = Sprig::factory('entry', array('id'=>6))->load();
        $SUT->text = 'Some new text';
        $SUT->update(FALSE);

        $revision = $SUT->revisions->current();

        $this->assertEquals(2, $revision->version);
        $this->assertEquals('Some new text', $SUT->text);
    }

    /**
     * Populate table with versions
     */
    private function populate($id) {
        $text1 = "This is my string";
        $text2 = "This is my first string";    // inserted "first"
        $text3 = "This is our first string";   // changed "my" to "our"
        $text4 = "This is our first";          // dropped "string"

        $diff2 = serialize(Version::clean_array(Version::diff($text1, $text2)));
        $diff3 = serialize(Version::clean_array(Version::diff($text2, $text3)));
        $diff4 = serialize(Version::clean_array(Version::diff($text3, $text4)));

        try {
            DB::insert('entry_revisions', array('entry_id','version','diff'))
                ->values(array($id,1,''))->execute();
            DB::insert('entry_revisions', array('entry_id','version','diff'))
                ->values(array($id,2,$diff2))->execute();
            DB::insert('entry_revisions', array('entry_id','version','diff'))
                ->values(array($id,3,$diff3))->execute();
            DB::insert('entry_revisions', array('entry_id','version','diff'))
                ->values(array($id,4,$diff4))->execute();
            DB::insert('entries', array('id','version','text'))
                ->values(array($id,4,$text4))->execute();
        } catch (Database_Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * Provide versions and strings to check against
     */
    public function providerVersionString() {
        return array(
            array(0, 1, 'This is my string'),
            array(1, 1, 'This is my string'),
            array(2, 2, 'This is my first string'),
            array(3, 3, 'This is our first string'),
            array(4, 4, 'This is our first'),
            array(5, 4, 'This is our first'),
        );
    }

    /**
     * Test retrieving previous versions
     * @dataProvider    providerVersionString
     */
    public function testRetrievingVersions($requested_version, $expected_version, $expected_string) {
        $this->populate(4);
        $SUT = Sprig::factory('entry', array('id'=>4))->load();

        $SUT->version($requested_version);
        $this->assertEquals($expected_version, $SUT->version);
        $this->assertEquals($expected_string, $SUT->text);
    }

    /**
     * Test retrieving revisions
     * May not even need this, check code coverage
     */
    //public function testRetrievingRevisions() { }

}
