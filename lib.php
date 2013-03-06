<?php
// This file is for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Library of interface functions and constants for module offlinequiz
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 * All the offlinequiz specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer
 * @copyright     2012 The University of Vienna
 * @since         Moodle 2.2+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

//  If, for some reason, you need to use global variables instead of constants, do not forget to make them
//  global as this file can be included inside a function scope. However, using the global variables
//  at the module level is not a recommended.

// CONSTANTS

//  * The different review options are stored in the bits of $offlinequiz->review.
//  * These constants help to extract the options.
//  * Originally this method was copied from the Moodle 1.9 quiz module. We use:
//  * 111111100000000000
define('OFFLINEQUIZ_REVIEW_ATTEMPT',          0x1000);  // Show responses
define('OFFLINEQUIZ_REVIEW_MARKS',            0x2000);  // Show scores
define('OFFLINEQUIZ_REVIEW_SPECIFICFEEDBACK', 0x4000);  // Show feedback
define('OFFLINEQUIZ_REVIEW_RIGHTANSWER',      0x8000);  // Show correct answers
define('OFFLINEQUIZ_REVIEW_GENERALFEEDBACK',  0x10000); // Show general feedback
define('OFFLINEQUIZ_REVIEW_SHEET',            0x20000); // Show scanned sheet
define('OFFLINEQUIZ_REVIEW_CORRECTNESS',      0x40000); // Show scanned sheet
define('OFFLINEQUIZ_REVIEW_GRADEDSHEET',      0x800); // Show scanned sheet

// Define constants for cron job status
define('OQ_STATUS_PENDING', 1);
define('OQ_STATUS_OPERATING', 2);
define('OQ_STATUS_PROCESSED', 3);
define('OQ_STATUS_NEEDS_CORRECTION', 4);
define('OQ_STATUS_DOUBLE', 5);

// FUNCTIONS

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $offlinequiz An object from the form in mod_form.php
 * @return int The id of the newly inserted offlinequiz record
 */
function offlinequiz_add_instance($offlinequiz) {
    global $DB;

    // Process the options from the form.
    $offlinequiz->timecreated = time();
    $offlinequiz->questions = '';
    $offlinequiz->grade = 100;

    $result = offlinequiz_process_options($offlinequiz);

    if ($result && is_string($result)) {
        return $result;
    }
    if (!property_exists($offlinequiz, 'intro') || $offlinequiz->intro == null) {
        $offlinequiz->intro = '';
    }

    if (!$course = $DB->get_record('course', array('id' => $offlinequiz->course))) {
        print_error('invalidcourseid', 'error');
    }

    // Try to store it in the database.
    try {
        if (!$offlinequiz->id = $DB->insert_record('offlinequiz', $offlinequiz)) {
            print_error('Could not create Offlinequiz object!');
            return false;
        }
    } catch (Exception $e) {
        print_error("ERROR: ".$e->debuginfo);
    }

    // Do the processing required after an add or an update.
    offlinequiz_after_add_or_update($offlinequiz);

    return $offlinequiz->id;
}


/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $offlinequiz An object from the form in mod_form.php
 * @return boolean Success/Fail
 */
function offlinequiz_update_instance($offlinequiz) {
    global $DB;
    require_once('locallib.php');

    $offlinequiz->timemodified = time();
    $offlinequiz->id = $offlinequiz->instance;

    // Remember the old values of the shuffle settings
    $shufflequestions = $DB->get_field('offlinequiz', 'shufflequestions', array('id' => $offlinequiz->id));
    $shuffleanswers = $DB->get_field('offlinequiz', 'shuffleanswers', array('id' => $offlinequiz->id));

    // Process the options from the form.
    $result = offlinequiz_process_options($offlinequiz);
    if ($result && is_string($result)) {
        return $result;
    }

    // Update the database.
    if (! $DB->update_record('offlinequiz', $offlinequiz)) {
        return false;  // some error occurred
    }

    // Do the processing required after an add or an update.
    offlinequiz_after_add_or_update($offlinequiz);

    // Check whether shufflequestions of shuffleanswers has been changed
    // If so, we have to delete the question usage templates...
    // Note that we don't have to delete PDF files because the shuffle settings can not be changed when
    // the documents have been created.
    if (($offlinequiz->shufflequestions != $shufflequestions) || ($offlinequiz->shuffleanswers != $shuffleanswers)) {
        offlinequiz_delete_template_usages($offlinequiz, false);
    }

    return true;
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function offlinequiz_delete_instance($id) {
    global $DB;
    require_once('locallib.php');

    if (! $offlinequiz = $DB->get_record('offlinequiz', array('id' => $id))) {
        return false;
    }

    if (! $cm = get_coursemodule_from_instance("offlinequiz", $offlinequiz->id, $offlinequiz->course)) {
        return false;
    }
    $context = context_module::instance($cm->id);

    // Delete any dependent records here
    if ($results = $DB->get_records("offlinequiz_results", array('offlinequizid' => $offlinequiz->id))) {
        foreach ($results as $result) {
            offlinequiz_delete_result($result->id, $context);
        }
    }
    if ($scannedpages = $DB->get_records('offlinequiz_scanned_pages', array('offlinequizid' => $offlinequiz->id))) {
        foreach ($scannedpages as $page) {
            offlinequiz_delete_scanned_page($page, $context);
        }
    }
    if ($scannedppages = $DB->get_records('offlinequiz_scanned_p_pages', array('offlinequizid' => $offlinequiz->id))) {
        foreach ($scannedppages as $page) {
            offlinequiz_delete_scanned_p_page($page, $context);
        }
    }

    $tables_to_purge = array(
            'offlinequiz_groups' => 'offlinequizid',
            'offlinequiz_q_instances' => 'offlinequiz',
            'offlinequiz' => 'id'
    );

    foreach ($tables_to_purge as $table => $keyfield) {
        if (! $DB->delete_records($table, array($keyfield => $offlinequiz->id))) {
            $result = false;
        }
    }

    if ($plists = $DB->get_records('offlinequiz_p_lists', array('offlinequizid' => $offlinequiz->id))) {
        foreach ($plists as $plist) {
            $DB->delete_records('offlinequiz_participants', array('listid' => $plist->id));
            $DB->delete_records('offlinequiz_p_lists', array('id' => $plist->id));
        }
    }

    //  $pagetypes = page_import_types('mod/offlinequiz/');
    //  foreach ($pagetypes as $pagetype) {
    //      if (! $DB->delete_records('block_instance', array('pageid' => $offlinequiz->id, 'pagetype' => $pagetype))) {
    //          $result = false;
    //      }
    //  }
    offlinequiz_grade_item_delete($offlinequiz);

    if ($events = $DB->get_records('event', array('modulename' => 'offlinequiz', 'instance' => $offlinequiz->id))) {
        foreach ($events as $event) {
            $event = calendar_event::load($event->$id);
            $event->delete();
        }
    }

    return true;
}

/**
 * Delete grade item for given offlinequiz
 *
 * @param object $offlinequiz object
 * @return object offlinequiz
 */
function offlinequiz_grade_item_delete($offlinequiz) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    return grade_update('mod/offlinequiz', $offlinequiz->course, 'mod', 'offlinequiz', $offlinequiz->id, 0,
            null, array('deleted' => 1));
}


/**
 * Serve questiontext files in the question text when they are displayed in this report.
 * 
 * @param context $context the context
 * @param int $questionid the question id
 * @param array $args remaining file args
 * @param bool $forcedownload
 */
function offlinequiz_questiontext_preview_pluginfile($context, $questionid, $args, $forcedownload) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/offlinequiz/locallib.php');
    require_once($CFG->dirroot . '/lib/questionlib.php');

    list($context, $course, $cm) = get_context_info_array($context->id);
    require_login($course, false, $cm);

    // Assume only trusted people can see this report. There is no real way to
    // validate questionid, becuase of the complexity of random quetsions.
    require_capability('mod/offlinequiz:viewreports', $context);

    question_send_questiontext_file($questionid, $args, $forcedownload);
}

/**
 * Serve image files in the answer text when they are displayed in the preview
 * 
 * @param context $context the context
 * @param int $answerid the answer id
 * @param array $args remaining file args
 * @param bool $forcedownload
 */
// questiontext url http://131.130.103.117/mod_offlinequiz/pluginfile.php/1365/question/questiontext_preview/offlinequiz/7894/thomas1.jpg
//                  http://131.130.103.117/mod_offlinequiz/pluginfile.php/1365/question/answertext_preview/offlinequiz/32080/P1070019.JPG
function offlinequiz_answertext_preview_pluginfile($context, $answerid, $args, $forcedownload) {
    global $CFG;

    require_once($CFG->dirroot . '/mod/offlinequiz/locallib.php');
    require_once($CFG->dirroot . '/lib/questionlib.php');

    list($context, $course, $cm) = get_context_info_array($context->id);
    require_login($course, false, $cm);

    // Assume only trusted people can see this report. There is no real way to
    // validate questionid, becuase of the complexity of random quetsions.
    require_capability('mod/offlinequiz:viewreports', $context);

    offlinequiz_send_answertext_file($context, $answerid, $args, $forcedownload);
}

/**
 * Send a file in the text of an answer.
 * 
 * @param int $questionid the question id
 * @param array $args the remaining file arguments (file path).
 * @param bool $forcedownload whether the user must be forced to download the file.
 */
function offlinequiz_send_answertext_file($context, $answerid, $args, $forcedownload) {
    global $DB;
    require_once('locallib.php');

    $fs = get_file_storage();
    $fullpath = "/$context->id/question/answer/$answerid/" . implode('/', $args);
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        send_file_not_found();
    }

    send_stored_file($file, 0, 0, $forcedownload);
}

/**
 * Serves the offlinequiz files.
 *
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return bool false if file not found, does not return if found - justsend the file
 */
function offlinequiz_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) {
    global $CFG, $DB, $USER;
    require_once('locallib.php');
    require_once($CFG->libdir . '/questionlib.php');

    // TODO control file access!
    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_login($course, false, $cm);

    if (!$offlinequiz = $DB->get_record('offlinequiz', array('id' => $cm->instance))) {
        return false;
    }

    // 'pdfs' area is served by pluginfile.php
    $fileareas = array('pdfs', 'imagefiles');
    if (!in_array($filearea, $fileareas)) {
        return false;
    }

    //     if (!$feedback = $DB->get_record('offlinequiz_feedback', array('id'=>$feedbackid))) {
    //         return false;
    //     }

    $fs = get_file_storage();
    $relativepath = implode('/', $args);

    $fullpath = '/' . $context->id . '/mod_offlinequiz/' . $filearea . '/' . $relativepath;

    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }

    // teachers in this context are allowed to see all files in this context
    if (has_capability('mod/offlinequiz:viewreports', $context)) {
        if ($filearea == 'pdfs') {
            $filename = clean_filename($course->shortname) . '_' . clean_filename($offlinequiz->name) . '_' . $file->get_filename();
            send_stored_file($file, 86400, 0, $forcedownload, array('filename' => $filename));
        } else {
            send_stored_file($file, 86400, 0, $forcedownload);
        }
    } else {

        // get the corresponding scanned pages. There might be several in case an image file is used twice
        if (!$scannedpages = $DB->get_records('offlinequiz_scanned_pages',
                array('offlinequizid' => $offlinequiz->id, 'warningfilename' => $file->get_filename()))) {
            if (!$scannedpages = $DB->get_records('offlinequiz_scanned_pages', array('offlinequizid' => $offlinequiz->id,
                    'filename' => $file->get_filename()))) {
                    print_error('scanned page not found');
                    return false;
            }
        }

        // actually there should be only one scannedpage with that filename...
        foreach ($scannedpages as $scannedpage) {
            $sql = "SELECT *
            FROM {offlinequiz_results}
            WHERE id = :resultid
            AND status = 'complete'";
            if (!$result = $DB->get_record_sql($sql, array('resultid' => $scannedpage->resultid))) {
                return false;
            }

            // check whether the student is allowed to see scanned sheets.
            $options = offlinequiz_get_review_options($offlinequiz, $result, $context);
            if ($options->sheetfeedback == question_display_options::HIDDEN and
                    $options->gradedsheetfeedback == question_display_options::HIDDEN) {
                return false;
            }

            // if we found a page of a complete result that belongs to the user, we can send the file.
            if ($result->userid == $USER->id) {
                //              error_log("offlinequiz_pluginfile sending file " . $file->get_filename() . " !");
                send_stored_file($file, 86400, 0, $forcedownload);
                return true;
            }
        }
    }
}

/**
 * Return a list of page types
 * 
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function offlinequiz_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $module_pagetype = array(
            'mod-offlinequiz-*'=>get_string('page-mod-offlinequiz-x', 'offlinequiz'),
            'mod-offlinequiz-edit'=>get_string('page-mod-offlinequiz-edit', 'offlinequiz'));
    return $module_pagetype;
}

/**
 * Return a textual summary of the number of attempts that have been made at a particular offlinequiz,
 * returns '' if no attempts have been made yet, unless $returnzero is passed as true.
 *
 * @param object $offlinequiz the offlinequiz object. Only $offlinequiz->id is used at the moment.
 * @param object $cm the cm object. Only $cm->course, $cm->groupmode and
 *      $cm->groupingid fields are used at the moment.
 * @param bool $returnzero if false (default), when no attempts have been
 *      made '' is returned instead of 'Attempts: 0'.
 * @param int $currentgroup if there is a concept of current group where this method is being called
 *         (e.g. a report) pass it in here. Default 0 which means no current group.
 * @return string a string like "Attempts: 123", "Attemtps 123 (45 from your groups)" or
 *          "Attemtps 123 (45 from this group)".
 */
function offlinequiz_num_attempt_summary($offlinequiz, $cm, $returnzero = false, $currentgroup = 0) {
    global $DB, $USER;

    $sql = "SELECT COUNT(*)
              FROM {offlinequiz_results}
             WHERE offlinequizid = :offlinequizid
               AND status = 'complete'";

    $numattempts = $DB->count_records_sql($sql, array('offlinequizid'=> $offlinequiz->id));
    if ($numattempts || $returnzero) {
        return get_string('attemptsnum', 'offlinequiz', $numattempts);
    }
    return '';
}


/**
 * Returns the same as {@link offlinequiz_num_attempt_summary()} but wrapped in a link
 * to the offlinequiz reports.
 *
 * @param object $offlinequiz the offlinequiz object. Only $offlinequiz->id is used at the moment.
 * @param object $cm the cm object. Only $cm->course, $cm->groupmode and
 *      $cm->groupingid fields are used at the moment.
 * @param object $context the offlinequiz context.
 * @param bool $returnzero if false (default), when no attempts have been made
 *      '' is returned instead of 'Attempts: 0'.
 * @param int $currentgroup if there is a concept of current group where this method is being called
 *         (e.g. a report) pass it in here. Default 0 which means no current group.
 * @return string HTML fragment for the link.
 */
function offlinequiz_attempt_summary_link_to_reports($offlinequiz, $cm, $context, $returnzero = false,
        $currentgroup = 0) {
    global $CFG;
    $summary = offlinequiz_num_attempt_summary($offlinequiz, $cm, $returnzero, $currentgroup);
    if (!$summary) {
        return '';
    }

    $url = new moodle_url('/mod/offlinequiz/report.php', array(
            'id' => $cm->id, 'mode' => 'overview'));
    return html_writer::link($url, $summary);
}


/**
 * Check for features supported by offlinequizzes.
 * 
 * @param string $feature FEATURE_xx constant for requested feature
 * @return bool True if offlinequiz supports feature
 */
function offlinequiz_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:                  return true;
        case FEATURE_GROUPINGS:               return true;
        case FEATURE_GROUPMEMBERSONLY:        return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return false;
        case FEATURE_SHOW_DESCRIPTION:        return true;
        case FEATURE_GRADE_HAS_GRADE:         return true;
        case FEATURE_GRADE_OUTCOMES:          return true;
        case FEATURE_BACKUP_MOODLE2:          return true;

        default: return null;
    }
}

/**
 * Is this a graded offlinequiz? If this method returns true, you can assume that
 * $offlinequiz->grade and $offlinequiz->sumgrades are non-zero (for example, if you want to
 * divide by them).
 *
 * @param object $offlinequiz a row from the offlinequiz table.
 * @return bool whether this is a graded offlinequiz.
 */
function offlinequiz_has_grades($offlinequiz) {
    return $offlinequiz->grade >= 0.000005 && $offlinequiz->sumgrades >= 0.000005;
}

/**
 * Pre-process the offlinequiz options form data, making any necessary adjustments.
 * Called by add/update instance in this file, and the save code in admin/module.php.
 *
 * @param object $offlinequiz The variables set on the form.
 */
function offlinequiz_process_options(&$offlinequiz) {
    global $CFG;
    require_once($CFG->libdir . '/questionlib.php');

    $offlinequiz->timemodified = time();

    // offlinequiz open time.
    //  if (empty($offlinequiz->timeopen)) {
    //      $offlinequiz->preventlate = 0;
    //  }

    // Set trigger values to default
    //         if (empty($offlinequiz->lowertrigger) or $offlinequiz->lowertrigger == 0) {
    //             $offlinequiz->lowertrigger = $CFG->offlinequiz_lowertrigger;
    //         }
    //         if (empty($offlinequiz->uppertrigger) or $offlinequiz->uppertrigger == 0) {
    //             $offlinequiz->uppertrigger = $CFG->offlinequiz_uppertrigger;
    //        }

    // offlinequiz name. (Make up a default if one was not given.)
    if (empty($offlinequiz->name)) {
        if (empty($offlinequiz->intro)) {
            $offlinequiz->name = get_string('modulename', 'offlinequiz');
        } else {
            $offlinequiz->name = shorten_text(strip_tags($offlinequiz->intro));
        }
    }
    $offlinequiz->name = trim($offlinequiz->name);

    //  // Time limit. (Get rid of it if the checkbox was not ticked.)
    //  if (empty($offlinequiz->timelimitenable)) {
    //      $offlinequiz->timelimit = 0;
    //  }
    //  $offlinequiz->timelimit = round($offlinequiz->timelimit);

    // Settings that get combined to go into the optionflags column.
    $offlinequiz->optionflags = 0;
    if (!empty($offlinequiz->adaptive)) {
        $offlinequiz->optionflags |= QUESTION_ADAPTIVE;
    }

    // Settings that get combined to go into the review column.
    $review = 0;
    if (isset($offlinequiz->attemptclosed)) {
        $review += OFFLINEQUIZ_REVIEW_ATTEMPT;
        unset($offlinequiz->attemptclosed);
    }

    if (isset($offlinequiz->marksclosed)) {
        $review += OFFLINEQUIZ_REVIEW_MARKS;
        unset($offlinequiz->marksclosed);
    }

    if (isset($offlinequiz->feedbackclosed)) {
        $review += OFFLINEQUIZ_REVIEW_FEEDBACK;
        unset($offlinequiz->feedbackclosed);
    }

    if (isset($offlinequiz->correctnessclosed)) {
        $review += OFFLINEQUIZ_REVIEW_CORRECTNESS;
        unset($offlinequiz->correctnessclosed);
    }

    if (isset($offlinequiz->rightanswerclosed)) {
        $review += OFFLINEQUIZ_REVIEW_RIGHTANSWER;
        unset($offlinequiz->rightanswerclosed);
    }

    if (isset($offlinequiz->generalfeedbackclosed)) {
        $review += OFFLINEQUIZ_REVIEW_GENERALFEEDBACK;
        unset($offlinequiz->generalfeedbackclosed);
    }

    if (isset($offlinequiz->specificfeedbackclosed)) {
        $review += OFFLINEQUIZ_REVIEW_SPECIFICFEEDBACK;
        unset($offlinequiz->specificfeedbackclosed);
    }

    if (isset($offlinequiz->sheetclosed)) {
        $review += OFFLINEQUIZ_REVIEW_SHEET;
        unset($offlinequiz->sheetclosed);
    }

    if (isset($offlinequiz->gradedsheetclosed)) {
        $review += OFFLINEQUIZ_REVIEW_GRADEDSHEET;
        unset($offlinequiz->gradedsheetclosed);
    }

    $offlinequiz->review = $review;
}

/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 * 
 * @param unknown_type $course
 * @param unknown_type $user
 * @param unknown_type $mod
 * @param unknown_type $offlinequiz
 * @return stdClass|NULL
 */
function offlinequiz_user_outline($course, $user, $mod, $offlinequiz) {
    global $DB;

    $return = new stdClass;
    $return->time = 0;
    $return->info = '';

    if ($grade = $DB->get_record('offlinequiz_results', array('userid' => $user->id, 'offlinequiz' => $offlinequiz->id))) {
        if ((float) $grade->sumgrades) {
            $return->info = get_string('grade') . ':&nbsp;' . round($grade->sumgrades, $offlinequiz->decimalpoints);
        }
        $return->time = $grade->timemodified;
        return $return;
    }
    return null;
}

/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 * 
 * @param unknown_type $course
 * @param unknown_type $user
 * @param unknown_type $mod
 * @param unknown_type $offlinequiz
 * @return boolean
 */
function offlinequiz_user_complete($course, $user, $mod, $offlinequiz) {
    global $DB;

    if ($results = $DB->get_records('offlinequiz_results', array('userid' => $user->id, 'offlinequiz' => $offlinequiz->id))) {
        if ($offlinequiz->grade && $offlinequiz->sumgrades &&
                $grade = $DB->get_record('offlinequiz_results', array('userid' => $user->id, 'offlinequiz' => $offlinequiz->id))) {
            echo get_string('grade') . ': ' . round($grade->grade, $offlinequiz->decimalpoints) . '/' . $offlinequiz->grade . '<br />';
        }
        foreach ($results as $result) {
            echo get_string('result', 'offlinequiz') . ': ';
            if ($result->timefinish == 0) {
                print_string('unfinished');
            } else {
                echo round($result->sumgrades, $offlinequiz->decimalpoints) . '/' . $offlinequiz->sumgrades;
            }
            echo ' - ' . userdate($result->timemodified) . '<br />';
        }
    } else {
        print_string('noresults', 'offlinequiz');
    }

    return true;
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in offlinequiz activities and print it out.
 * Return true if there was output, or false is there was none.
 * 
 * @param unknown_type $course
 * @param unknown_type $viewfullnames
 * @param unknown_type $timestart
 * @return boolean
 */
function offlinequiz_print_recent_mod_activity($course, $viewfullnames, $timestart) {
    return false;  //  True if anything was printed, otherwise false
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * The cron function is empty. The evaluation of answer forms is done by a separate cron job using the cron.php script.
 *    
 **/
function offlinequiz_cron() {
    return true;
}

/**
 * Must return an array of users who are participants for a given instance
 * of offlinequiz. Must include every user involved in the instance,
 * independient of his role (student, teacher, admin...). The returned
 * objects must contain at least id property.
 * See other modules as example.
 *
 * @param int $offlinequizid ID of an instance of this module
 * @return boolean|array false if no participants, array of objects otherwise
 */
function offlinequiz_get_participants($offlinequizid) {
    global $CFG, $DB;

    // Get users from offlinequiz results
    $us_attempts = $DB->get_records_sql("
            SELECT DISTINCT u.id, u.id
              FROM {user} u,
                   {offlinequiz_results} r
             WHERE r.offlinequizid = '$offlinequizid'
               AND (u.id = r.userid OR u.id = r.teacherid");

    // Return us_attempts array (it contains an array of unique users)
    return $us_attempts;
}

/**
 * This function returns if a scale is being used by one offlinequiz
 * if it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $offlinequizid ID of an instance of this module
 * @return mixed
 */
function offlinequiz_scale_used($offlinequizid, $scaleid) {
    global $DB;

    $return = false;

    $rec = $DB->get_record('offlinequiz', array('id' => $offlinequizid, 'grade' => -$scaleid));

    if (!empty($rec) && !empty($scaleid)) {
        $return = true;
    }

    return $return;
}

/**
 * Checks if scale is being used by any instance of offlinequiz.
 * This function was added in 1.9
 *
 * This is used to find out if scale used anywhere
 * @param $scaleid int
 * @return boolean True if the scale is used by any offlinequiz
 */
function offlinequiz_scale_used_anywhere($scaleid) {
    global $DB;

    if ($scaleid and $DB->record_exists('offlinequiz', array('grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * This function is called at the end of offlinequiz_add_instance
 * and offlinequiz_update_instance, to do the common processing.
 *
 * @param object $offlinequiz the offlinequiz object.
 */
function offlinequiz_after_add_or_update($offlinequiz) {
    global $DB;

    // create group entries if they don't exist.
    if (property_exists($offlinequiz, 'numgroups')) {
        for ($i = 1; $i <= $offlinequiz->numgroups; $i++) {
            if (!$group = $DB->get_record('offlinequiz_groups', array('offlinequizid' => $offlinequiz->id, 'number' => $i))) {
                $group = new stdClass();
                $group->offlinequizid = $offlinequiz->id;
                $group->number = $i;
                $group->numberofpages = 1;
                $DB->insert_record('offlinequiz_groups', $group);
            }
        }
    }
    //  // Update the events relating to this offlinequiz.
    //  // This is slightly inefficient, deleting the old events and creating new ones. However,
    //  // there are at most two events, and this keeps the code simpler.
    //  if ($events = $DB->get_records('event', array('modulename' => 'offlinequiz', 'instance' => $offlinequiz->id))) {
    //      foreach ($events as $event) {
    //          global $CFG;
    //          require_once($CFG->dirroot.'/calendar/lib.php');
    //          $event = calendar_event::load($event->$id);
    //          $event->delete();
    //      }
    //  }

    //  $event = new stdClass;
    //  $event->description = ''; //$offlinequiz->intro;
    //  $event->courseid    = $offlinequiz->course;
    //  $event->groupid     = 0;
    //  $event->userid      = 0;
    //  $event->modulename  = 'offlinequiz';
    //  $event->instance    = $offlinequiz->id;
    //  $event->timeduration = 0;
    //  $event->visible     = instance_is_visible('offlinequiz', $offlinequiz);
    //  $event->eventtype   = 'open';

    //  if ($event->timestart = $offlinequiz->time) {
    //      $event->name = $offlinequiz->name;
    //      calendar_event::create($event);
    //  }
    //  if ($event->timestart = $offlinequiz->timeopen) {
    //      $event->name = $offlinequiz->name.' ('.get_string('reportstarts', 'offlinequiz').')';
    //      calendar_event::create($event);
    //  }
    // FIXME
    offlinequiz_grade_item_update($offlinequiz);
    return;
}

/**
 * Round a grade to to the correct number of decimal places, and format it for display.
 *
 * @param object $offlinequiz The offlinequiz table row, only $offlinequiz->decimalpoints is used.
 * @param float $grade The grade to round.
 * @return float
 */
function offlinequiz_format_grade($offlinequiz, $grade) {
    if (is_null($grade)) {
        return get_string('notyetgraded', 'offlinequiz');
    }
    return format_float($grade, $offlinequiz->decimalpoints);
}

/**
 * Round a grade to to the correct number of decimal places, and format it for display.
 *
 * @param object $offlinequiz The offlinequiz table row, only $offlinequiz->decimalpoints is used.
 * @param float $grade The grade to round.
 * @return float
 */
function offlinequiz_format_question_grade($offlinequiz, $grade) {
    require_once('locallib.php');

    if (empty($offlinequiz->questiondecimalpoints)) {
        $offlinequiz->questiondecimalpoints = -1;
    }
    if ($offlinequiz->questiondecimalpoints == -1) {
        return format_float($grade, $offlinequiz->decimalpoints);
    } else {
        return format_float($grade, $offlinequiz->questiondecimalpoints);
    }
}


/**
 * Return grade for given user or all users. The grade is taken from all complete offlinequiz results
 *
 * @param mixed $offlinequiz The offline quiz
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none
 */
function offlinequiz_get_user_grades($offlinequiz, $userid=0) {
    global $CFG, $DB;

    $maxgrade = $offlinequiz->grade;
    $groups = $DB->get_records('offlinequiz_groups', array('offlinequizid' => $offlinequiz->id), 'number', '*', 0, $offlinequiz->numgroups);

    $user = $userid ? " AND userid =  $userid " : "";

    $sql = "SELECT id, userid, sumgrades, offlinegroupid, timemodified as dategraded, timefinish AS datesubmitted
              FROM {offlinequiz_results}
             WHERE offlinequizid = :offlinequizid
               AND status = 'complete'
    $user";
    $params = array('offlinequizid' => $offlinequiz->id);

    $grades = array();

    if ($results = $DB->get_records_sql($sql, $params)) {
        foreach ($results as $result) {
            $key = $result->userid;
            $grades[$key] = array();
            $groupsumgrades = $groups[$result->offlinegroupid]->sumgrades;
            $grades[$key]['userid'] = $result->userid;
            $grades[$key]['rawgrade'] = round($result->sumgrades / $groupsumgrades * $maxgrade, $offlinequiz->decimalpoints);
            $grades[$key]['dategraded'] = $result->dategraded;
            $grades[$key]['datesubmitted'] = $result->datesubmitted;
        }
    }

    return $grades;
}

/**
 * Update grades in central gradebook
 *
 * @param object $offlinequiz the offline quiz settings.
 * @param int $userid specific user only, 0 means all users.
 */
function offlinequiz_update_grades($offlinequiz, $userid = 0, $nullifnone = true) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    if ($offlinequiz->grade == 0) {
        offlinequiz_grade_item_update($offlinequiz);

    } else if ($grades = offlinequiz_get_user_grades($offlinequiz, $userid)) {
        offlinequiz_grade_item_update($offlinequiz, $grades);

    } else if ($userid && $nullifnone) {
        $grade = new stdClass();
        $grade->userid = $userid;
        $grade->rawgrade = null;
        offlinequiz_grade_item_update($offlinequiz, $grade);

    } else {
        offlinequiz_grade_item_update($offlinequiz);
    }
}


/**
 * Create grade item for given offlinequiz
 *
 * @param object $offlinequiz object with extra cmidnumber
 * @param mixed $grades optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function offlinequiz_grade_item_update($offlinequiz, $grades = null) {
        global $CFG, $OUTPUT, $DB;

    require_once('locallib.php');
    require_once($CFG->libdir . '/gradelib.php');
    require_once($CFG->libdir . '/questionlib.php');

    if (array_key_exists('cmidnumber', $offlinequiz)) {
        // may not be always present
        $params = array('itemname' => $offlinequiz->name, 'idnumber' => $offlinequiz->cmidnumber);
    } else {
        $params = array('itemname' => $offlinequiz->name);
    }

    $offlinequiz->grade = $DB->get_field('offlinequiz', 'grade', array('id' => $offlinequiz->id));

    if (property_exists($offlinequiz, 'grade') && $offlinequiz->grade > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $offlinequiz->grade;
        $params['grademin']  = 0;

    } else {
        $params['gradetype'] = GRADE_TYPE_NONE;
    }

    // description by Juergen Zimmer (Tim Hunt):
    // 1. If the offlinequiz is set to not show grades while the offlinequiz is still open,
    //    and is set to show grades after the offlinequiz is closed, then create the
    //    grade_item with a show-after date that is the offlinequiz close date.
    // 2. If the offlinequiz is set to not show grades at either of those times,
    //    create the grade_item as hidden.
    // 3. If the offlinequiz is set to show grades, create the grade_item visible.
    $openreviewoptions = mod_offlinequiz_display_options::make_from_offlinequiz($offlinequiz);
    $closedreviewoptions = mod_offlinequiz_display_options::make_from_offlinequiz($offlinequiz);
    if ($openreviewoptions->marks < question_display_options::MARK_AND_MAX &&
            $closedreviewoptions->marks < question_display_options::MARK_AND_MAX) {
        $params['hidden'] = 1;

    } else if ($openreviewoptions->marks < question_display_options::MARK_AND_MAX &&
            $closedreviewoptions->marks >= question_display_options::MARK_AND_MAX) {
        if ($offlinequiz->timeclose) {
            $params['hidden'] = $offlinequiz->timeclose;
        } else {
            $params['hidden'] = 1;
        }
    } else {
        // a) both open and closed enabled
        // b) open enabled, closed disabled - we can not "hide after",
        //    grades are kept visible even after closing
        $params['hidden'] = 0;
    }

    if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    $gradebook_grades = grade_get_grades($offlinequiz->course, 'mod', 'offlinequiz', $offlinequiz->id);
    if (!empty($gradebook_grades->items)) {
        $grade_item = $gradebook_grades->items[0];
        if ($grade_item->locked) {
            $confirm_regrade = optional_param('confirm_regrade', 0, PARAM_INT);
            if (!$confirm_regrade) {
                $message = get_string('gradeitemislocked', 'grades');
                $back_link = $CFG->wwwroot . '/mod/offlinequiz/edit.php?q=' . $offlinequiz->id .
                '&amp;mode=overview';
                $regrade_link = qualified_me() . '&amp;confirm_regrade=1';
                echo $OUTPUT->box_start('generalbox', 'notice');
                echo '<p>'. $message .'</p>';
                echo $OUTPUT->container_start('buttons');
                echo $OUTPUT->single_button($regrade_link, get_string('regradeanyway', 'grades'));
                echo $OUTPUT->single_button($back_link,  get_string('cancel'));
                echo $OUTPUT->container_end();
                echo $OUTPUT->box_end();

                return GRADE_UPDATE_ITEM_LOCKED;
            }
        }
    }

    return grade_update('mod/offlinequiz', $offlinequiz->course, 'mod', 'offlinequiz', $offlinequiz->id, 0, $grades, $params);
}

/**
 * @param int $offlinequizid the offlinequiz id.
 * @param int $userid the userid.
 * @param string $status 'all', 'finished' or 'unfinished' to control
 * @param bool $includepreviews
 * @return an array of all the user's results at this offlinequiz. Returns an empty
 *      array if there are none.
 */
function offlinequiz_get_user_results($offlinequizid, $userid) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/mod/offlinequiz/locallib.php');

    $params = array();

    $params['offlinequizid'] = $offlinequizid;
    $params['userid'] = $userid;
    return $DB->get_records_select('offlinequiz_results',
            "offlinequizid = :offlinequizid AND userid = :userid AND status = 'complete'", $params, 'id ASC');
}

/**
 * This function extends the settings navigation block for the site.
 *
 * It is safe to rely on PAGE here as we will only ever be within the module
 * context when this is called
 *
 * @param settings_navigation $settings
 * @param navigation_node $offlinequiznode
 */
function offlinequiz_extend_settings_navigation($settings, $offlinequiznode) {
    global $PAGE, $CFG;

    // Included here as we only ever want to include this file if we really need to.
    require_once($CFG->libdir . '/questionlib.php');

    // We want to add these new nodes after the Edit settings node, and before the
    // Locally assigned roles node. Of course, both of those are controlled by capabilities.
    $keys = $offlinequiznode->get_children_key_list();
    $beforekey = null;
    $i = array_search('modedit', $keys);
    if ($i === false and array_key_exists(0, $keys)) {
        $beforekey = $keys[0];
    } else if (array_key_exists($i + 1, $keys)) {
        $beforekey = $keys[$i + 1];
    }

    if (has_capability('mod/offlinequiz:manage', $PAGE->cm->context)) {
        $node = navigation_node::create(get_string('editingofflinequiz', 'offlinequiz'),
                new moodle_url('/mod/offlinequiz/edit.php', array('cmid' => $PAGE->cm->id)),
                navigation_node::TYPE_SETTING, null, 'mod_offlinequiz_edit',
                new pix_icon('i/questions', ''));
        $offlinequiznode->add_node($node, $beforekey);

        $node = navigation_node::create(get_string('createofflinequiz', 'offlinequiz'),
                new moodle_url('/mod/offlinequiz/createquiz.php', array('id' => $PAGE->cm->id)),
                navigation_node::TYPE_SETTING, null, 'mod_offlinequiz_createpdfs',
                new pix_icon('f/pdf', ''));
        $offlinequiznode->add_node($node, $beforekey);

        $node = navigation_node::create(get_string('participantslists', 'offlinequiz'),
                new moodle_url('/mod/offlinequiz/participants.php', array('id' => $PAGE->cm->id)),
                navigation_node::TYPE_SETTING, null, 'mod_offlinequiz_participants',
                new pix_icon('i/group', ''));
        $offlinequiznode->add_node($node, $beforekey);

        $node = navigation_node::create(get_string('results', 'offlinequiz'),
                new moodle_url('/mod/offlinequiz/report.php', array('id' => $PAGE->cm->id, 'mode' => 'overview')),
                navigation_node::TYPE_SETTING, null, 'mod_offlinequiz_results',
                new pix_icon('i/grades', ''));
        $offlinequiznode->add_node($node, $beforekey);
    }

    question_extend_settings_navigation($offlinequiznode, $PAGE->cm->context)->trim_if_empty();
}