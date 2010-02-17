<?php defined('SYSPATH') or die('No direct access allowed.');

/**
 * Paul's Simple Diff Algorithm v 0.1
 * (C) Paul Butler 2007 <http://www.paulbutler.org/>
 * May be used and distributed under the zlib/libpng license.
 *
 * This code is intended for learning purposes; it was written with short
 * code taking priority over performance. It could be used in a practical
 * application, but there are a few ways it could be optimized.
 *
 * Given two arrays, the function diff will return an array of the changes.
 * I won't describe the format of the array, but it will be obvious
 * if you use print_r() on the result of a diff on some test data.
 *
 * htmlDiff is a wrapper for the diff command, it takes two strings and
 * returns the differences in HTML. The tags used are <ins> and <del>,
 * which can easily be styled with CSS.  
 *
 * @brief   Version diff and patch algorithms
 */
class Version {

    /**
     * Find the difference between two strings
     * @author      Paul Butler
     * @param old   The old string
     * @param new   The new string
     * @return      Array of adds and deletions
     */
    public static function diff($old, $new) {
        $old = is_string($old) ? explode(" ", $old) : $old;
        $new = is_string($new) ? explode(" ", $new) : $new;
        $maxlen = 0;
        foreach($old as $oindex => $ovalue){
            $nkeys = array_keys($new, $ovalue);
            foreach($nkeys as $nindex){
                $matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ?
                    $matrix[$oindex - 1][$nindex - 1] + 1 : 1;
                if($matrix[$oindex][$nindex] > $maxlen){
                    $maxlen = $matrix[$oindex][$nindex];
                    $omax = $oindex + 1 - $maxlen;
                    $nmax = $nindex + 1 - $maxlen;
                }
            }
        }
        if($maxlen == 0) return array(array('d'=>$old, 'i'=>$new));
        return array_merge(
            self::diff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
            array_slice($new, $nmax, $maxlen),
            self::diff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen))
        );
    }

    /**
     * Perform a diff of two strings, marking up the differences
     * @author      Paul Butler
     * @param old   The old string
     * @param new   The new string
     * @return      String with markup
     */
    public static function inline_diff($old, $new) {
        $diff = self::diff(explode(' ', $old), explode(' ', $new));
        $ret = '';
        foreach($diff as $k) {
            if(is_array($k)) {
                $ret .= (!empty($k['d'])?"<del>".implode(' ',$k['d'])."</del> ":'').
                    (!empty($k['i'])?"<ins>".implode(' ',$k['i'])."</ins> ":'');
            } else {
                $ret .= $k . ' ';
            }
        }
        return $ret;
    }

    /**
     * Apply diff array to patch a string to the new version
     * @author _ck_
     * @see http://paulbutler.org/archives/a-simple-diff-algorithm-in-php/
     * @param string    The original string
     * @param diff      Array of adds and deletions
     * @param reverse   Whether the original string is the old or new string
     * @return          The target string
     */
    public static function patch($string, $diff, $reverse=FALSE) {
        $string = is_string($string) ? explode(" ", $string) : $string;
        $add = $reverse ? 'd' : 'i';
        $del = $reverse ? 'i' : 'd';
        $diff = self::clean_array($diff);
        $offset = -1;

        foreach($diff as $key=>$value) {
            array_splice($string, $key+$offset, count($diff[$key][$del]), $diff[$key][$add]);
            $offset += count($diff[$key][$add]) - 1;
        }

        return implode(" ", $string);
    }

    /**
     * Perform a side-by-side diff of two strings.
     * Each string is turned into an array of lines
     * enclosed in <li> tags.  Lines deleted from the
     * old string will be styled with <li class="deleted">
     * while lines added to the new string will be styled
     * with <li class="added">.
     *
     * @author  Kyle Treubig
     * @param old   The old string
     * @param new   The new string
     * @return      array of left and right arrays
     */
    public static function side_diff($old, $new) {
        $old = explode("\n", $old);
        $new = explode("\n", $new);
        $diff = self::diff($old, $new);

        $formatted_old = array(0 => '');
        foreach($old as $num=>$line) {
            $formatted_old[$num+1] = '<li>' . $line . '</li>';
        }
        $formatted_new = array(0 => '');
        foreach($new as $num=>$line) {
            $formatted_new[$num+1] = '<li>' . $line . '</li>';
        }

        $old_line_no = $adds = $dels = 0;

        foreach($diff as $point=>$action) {
            if (is_array($action)) {
                // Increment current line number if a deletion is present
                $old_line_no += count($action['d']) ? 1 : 0;
                // Calculate the corresponding line number in the new text
                $new_line_no = $old_line_no + $adds - $dels;
                $dels += count($action['d']);
                $adds += count($action['i']);

                // For each line deleted, mark it in the old text
                for ($i=$old_line_no; $i<$old_line_no+count($action['d']); $i++) {
                    $formatted_old[$i] = '<li class="deleted">'.$old[$i-1].'</li>';
                }

                // If deletion occuring, the corresponding insertion line
                // number in the new text will be the same as the corresponding
                // line number, else it is the next line
                $ins_line = count($action['d']) ? $new_line_no : $new_line_no + 1;
                // For each line inserted, mark it in the new text
                for ($j=$ins_line; $j<$ins_line+count($action['i']); $j++) {
                    $formatted_new[$j] = '<li class="added">'.$new[$j-1].'</li>';
                }

                // If more than one deletion, add empty lines after all deletions
                $offset = (count($action['d']) > 1) ? count($action['d'])-1 : 0;
                // For each insertion, add an empty line to the old text
                for ($k=0; $k<count($action['i']); $k++) {
                    $formatted_old[$old_line_no+$offset] .= '<li> </li>';
                }

                // For each deletion, add an empty line to the new text
                for ($l=0; $l<count($action['d']); $l++) {
                    $formatted_new[$new_line_no-1] .= '<li> </li>';
                }

                // Increment current line number if multiple deletions occured
                $old_line_no += count($action['d']) ? count($action['d'])-1 : 0;
            } else {
                $old_line_no++;
            }
        }

        return array($formatted_old,$formatted_new);
    }

    /**
     * Clean diff array of empty values to save storage space
     * @author      Kyle Treubig
     * @param array The diff array
     * @return      The cleaned array
     */
    public static function clean_array($array) {
        foreach($array as $key=>$value) {
            if (empty($value) OR ! is_array($value)) {
                unset($array[$key]);
            }
            if (is_array($value) &&
                count($value['d']) == 0 &&
                count($value['i']) == 0)
                unset($array[$key]);
        }
        return $array;
    }

}

