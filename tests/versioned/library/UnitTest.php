<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Versioned module version library unit tests
 *
 * @author  Kyle Treubig
 * @group   versioned
 * @group   versioned.library.version
 */
class Versioned_Library_UnitTest extends PHPUnit_Framework_TestCase {

    protected $line1 = "line 1
line 1.5
line 2
line 3
line 5
line 4";
    protected $line2 = "line 1
line 1.5
line 2a
line 2.5
line 3
line 4";

    protected $text1 = "An original string\nWith an extra sentence\nWith three sentences\nAnd no changes\nLast sentence";
    protected $text2 = "An unoriginal string\nWith three sentences\nAnd three changes\nLike a new sentence\nLast sentence";

    /**
     * Test diff by line
     */
    public function testDiffByLine() {
        $diff = Versioned::diff(explode("\n", $this->line1), explode("\n", $this->line2));

        $this->assertEquals('line 1', $diff[1]);
        $this->assertEquals('line 1.5', $diff[2]);
        $this->assertEquals('line 2', $diff[3]['d'][0]);
        $this->assertEquals('line 2a', $diff[3]['i'][0]);
        $this->assertEquals('line 2.5', $diff[3]['i'][1]);
        $this->assertEquals('line 3', $diff[4]);
        $this->assertEquals('line 5', $diff[5]['d'][0]);
        $this->assertEquals('line 4', $diff[6]);
    }

    /**
     * Test diff by word
     */
    public function testDiffByWord() {
        $diff = Versioned::diff($this->text1, $this->text2);

        $this->assertEquals('An', $diff[1]);
        $this->assertEquals('original', $diff[2]['d'][0]);
        $this->assertEquals('unoriginal', $diff[2]['i'][0]);
        $this->assertEquals('an', $diff[4]['d'][0]);
        $this->assertEquals('extra', $diff[4]['d'][1]);
    }

    /**
     * Test inline comparison
     */
    public function testInlineComparison() {
        $diff = Versioned::inline_diff($this->text1, $this->text2);

        $this->assertRegExp('/<ins>unoriginal<\/ins>/', $diff);
        $this->assertRegExp('/<del>original<\/del>/', $diff);
    }

    /**
     * Test complex side by side comparison
     */
    public function testComplexSideComparison() {
        $text1 = "line 1\nline 4\nline a\nline 5\nline b\nline 7\nline c\nline d\nline 10\nline e\nline 13\nline 15\nline f\nline g\nline 16\nline h\nline i\nline 18\nline 19";
        $text2 = "line 1\nline 2\nline 3\nline 4\nline 5\nline 6\nline 7\nline 8\nline 9\nline 10\nline 11\nline 12\nline 13\nline 14\nline 15\nline 16\nline 17\nline 18\nline 19\nline 20";
        $diff = Versioned::side_diff($text1, $text2);

        $old = implode("\n", $diff['old']);
        $new = implode("\n", $diff['new']);

        // deletions
        $this->assertEquals(9, preg_match_all('/deleted/', $old, $out));
        $this->assertRegExp('/<li class="deleted">line a<\/li>/', $old);
        $this->assertRegExp('/<li class="deleted">line b<\/li>/', $old);
        $this->assertRegExp('/<li class="deleted">line c<\/li>/', $old);
        $this->assertRegExp('/<li class="deleted">line d<\/li>/', $old);
        $this->assertRegExp('/<li class="deleted">line e<\/li>/', $old);
        $this->assertRegExp('/<li class="deleted">line f<\/li>/', $old);
        $this->assertRegExp('/<li class="deleted">line g<\/li>/', $old);
        $this->assertRegExp('/<li class="deleted">line h<\/li>/', $old);
        $this->assertRegExp('/<li class="deleted">line i<\/li>/', $old);

        // insertions
        $this->assertEquals(10, preg_match_all('/added/', $new, $out));
        $this->assertRegExp('/<li class="added">line 2<\/li>/', $new);
        $this->assertRegExp('/<li class="added">line 3<\/li>/', $new);
        $this->assertRegExp('/<li class="added">line 6<\/li>/', $new);
        $this->assertRegExp('/<li class="added">line 8<\/li>/', $new);
        $this->assertRegExp('/<li class="added">line 9<\/li>/', $new);
        $this->assertRegExp('/<li class="added">line 11<\/li>/', $new);
        $this->assertRegExp('/<li class="added">line 12<\/li>/', $new);
        $this->assertRegExp('/<li class="added">line 14<\/li>/', $new);
        $this->assertRegExp('/<li class="added">line 17<\/li>/', $new);
        $this->assertRegExp('/<li class="added">line 20<\/li>/', $new);

        // empty lines in old
        $this->assertEquals(10, preg_match_all('/<li> <\/li>/', $old, $out));

        // empty lines in new
        $this->assertEquals(9, preg_match_all('/<li> <\/li>/', $new, $out));
    }

    /**
     * Test first line change side by side comparison
     */
    public function testFirstLineChangeSideComparison() {
        $text1 = "line a\nline 2\nline 3";
        $text2 = "line 1\nline 2\nline 3";
        $diff = Versioned::side_diff($text1, $text2);

        $old = implode("\n", $diff['old']);
        $new = implode("\n", $diff['new']);

        $this->assertRegExp('/<li class="deleted">line a<\/li><li> <\/li>/', $old);
        $this->assertRegExp('/<li class="added">line 1<\/li>/', $new);
        $this->assertRegExp('/^<li> <\/li>/', $new);
    }

    /**
     * Test first line insertion side comparison
     */
    public function testFirstLineInsertionSideComparison() {
        $text1 = "line 2\nline 3\nline 4";
        $text2 = "line 1\nline 2\nline 3\nline 4";
        $diff = Versioned::side_diff($text1, $text2);

        $old = implode("\n", $diff['old']);
        $new = implode("\n", $diff['new']);

        $this->assertRegExp('/^<li> <\/li>/', $old);
        $this->assertRegExp('/<li class="added">line 1<\/li>/', $new);
    }

    /**
     * Test first line deletion side comparison
     */
    public function testFirstLineDeletionSideComparison() {
        $text1 = "line 1\nline 2\nline 3";
        $text2 = "line 2\nline 3";
        $diff = Versioned::side_diff($text1, $text2);

        $old = implode("\n", $diff['old']);
        $new = implode("\n", $diff['new']);

        $this->assertRegExp('/^<li> <\/li>/', $new);
        $this->assertRegExp('/<li class="deleted">line 1<\/li>/', $old);
    }
}

