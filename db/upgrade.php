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
 * Upgrade script for the offlinequiz module
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer
 * @copyright     2012 The University of Vienna
 * @since         Moodle 2.2+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 **/

defined('MOODLE_INTERNAL') || die();


function xmldb_offlinequiz_upgrade($oldversion = 0) {
    global $CFG, $THEME, $DB, $OUTPUT;

    $dbman = $DB->get_manager();

    // And upgrade begins here. For each one, you'll need one
    // block of code similar to the next one. Please, delete
    // this comment lines once this file start handling proper
    // upgrade code.

    // ONLY UPGRADE FROM Moodle 1.9.x (module version 2009042100) is supported.

    if ($oldversion < 2009120700) {

        // Define field counter to be added to offlinequiz_i_log
        $table = new xmldb_table('offlinequiz_i_log');
        $field = new xmldb_field('counter');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'rawdata');

        // Launch add field counter
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field corners to be added to offlinequiz_i_log
        $field = new xmldb_field('corners');
        $field->set_attributes(XMLDB_TYPE_CHAR, '50', null, null, null, null, 'counter');

        // Launch add field corners
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field pdfintro to be added to offlinequiz
        $table = new xmldb_table('offlinequiz');
        $field = new xmldb_field('pdfintro');
        $field->set_attributes(XMLDB_TYPE_TEXT, 'small', null, null, null, null, 'intro');

        // Launch add field pdfintro
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // offlinequiz savepoint reached
        upgrade_mod_savepoint(true, 2009120700, 'offlinequiz');
    }

    if ($oldversion < 2010082900) {

        // Define table offlinequiz_p_list to be created
        $table = new xmldb_table('offlinequiz_p_list');

        // Adding fields to table offlinequiz_p_list
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('offlinequiz', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, null, null, null, null, null);
        $table->add_field('list', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1');

        // Adding keys to table offlinequiz_p_list
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Launch create table for offlinequiz_p_list
        $dbman->create_table($table);

        // Define field position to be dropped from offlinequiz_participants
        $table = new xmldb_table('offlinequiz_participants');
        $field = new xmldb_field('position');

        // Launch drop field position
        $dbman->drop_field($table, $field);

        // Define field page to be dropped from offlinequiz_participants
        $table = new xmldb_table('offlinequiz_participants');
        $field = new xmldb_field('page');

        // Launch drop field page
        $dbman->drop_field($table, $field);

        // Define field list to be added to offlinequiz_participants
        $table = new xmldb_table('offlinequiz_participants');
        $field = new xmldb_field('list');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1', 'userid');

        // Launch add field list
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // offlinequiz savepoint reached
        upgrade_mod_savepoint(true, 2010082900, 'offlinequiz');
    }

    if ($oldversion < 2010090600) {

        // Define index offlinequiz (not unique) to be added to offlinequiz_p_list
        $table = new xmldb_table('offlinequiz_p_list');
        $index = new XMLDBIndex('offlinequiz');
        $index->set_attributes(XMLDB_INDEX_NOTUNIQUE, array('offlinequiz'));

        // Launch add index offlinequiz
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $index = new XMLDBIndex('list');
        $index->set_attributes(XMLDB_INDEX_NOTUNIQUE, array('list'));

        // Launch add index list
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index offlinequiz (not unique) to be added to offlinequiz_participants
        $table = new xmldb_table('offlinequiz_participants');
        $index = new XMLDBIndex('offlinequiz');
        $index->set_attributes(XMLDB_INDEX_NOTUNIQUE, array('offlinequiz'));

        // Launch add index offlinequiz
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $index = new XMLDBIndex('list');
        $index->set_attributes(XMLDB_INDEX_NOTUNIQUE, array('list'));

        // Launch add index list
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $index = new XMLDBIndex('userid');
        $index->set_attributes(XMLDB_INDEX_NOTUNIQUE, array('userid'));

        // Launch add index list
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // offlinequiz savepoint reached
        upgrade_mod_savepoint(true, 2010090600, 'offlinequiz');
    }

    if ($oldversion < 2011021400) {

        // Define field fileformat to be added to offlinequiz
        $table = new xmldb_table('offlinequiz');
        $field = new xmldb_field('fileformat');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'timemodified');

        // Launch add field fileformat
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // offlinequiz savepoint reached
        upgrade_mod_savepoint(true, 2011021400, 'offlinequiz');
    }

    if ($oldversion < 2011032900) {

        // Define field page to be added to offlinequiz_i_log
        $table = new xmldb_table('offlinequiz_i_log');
        $field = new xmldb_field('page');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'corners');

        // Launch add field page
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field username to be added to offlinequiz_i_log
        $field = new xmldb_field('username');
        $field->set_attributes(XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null, 'page');

        // Launch add field username
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define index username (not unique) to be added to offlinequiz_i_log
        $index = new XMLDBIndex('username');
        $index->set_attributes(XMLDB_INDEX_NOTUNIQUE, array('username'));

        // Launch add index username
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define field showgrades to be added to offlinequiz
        $table = new xmldb_table('offlinequiz');
        $field = new xmldb_field('showgrades');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'fileformat');

        // Launch add field showgrades
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // offlinequiz savepoint reached
        upgrade_mod_savepoint(true, 2011032900, 'offlinequiz');
    }

    if ($oldversion < 2011081700) {
        // Define field showtutorial to be added to offlinequiz
        $table = new xmldb_table('offlinequiz');
        $field = new xmldb_field('showtutorial');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'showgrades');

        // Launch add field showtutorial
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // offlinequiz savepoint reached
        upgrade_mod_savepoint(true, 2011081700, 'offlinequiz');
    }

    // ======================================================
    // UPGRADE for Moodle 2.0 module starts here.
    // ======================================================
    // first we do the changes to the main table 'offlinequiz'
    // ======================================================
    if ($oldversion < 2012010100) {

        // Define field docscreated to be added to offlinequiz
        $table = new xmldb_table('offlinequiz');
        $field = new xmldb_field('docscreated', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'questionsperpage');

        // Conditionally launch add field docscreated
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // offlinequiz savepoint reached
        upgrade_mod_savepoint(true, 2012010100, 'offlinequiz');
    }

    // fill the new field docscreated
    if ($oldversion < 2012010101) {

        $offlinequizzes = $DB->get_records('offlinequiz');
        foreach ($offlinequizzes as $offlinequiz) {
            $dirname = $CFG->dataroot . '/' . $offlinequiz->course . '/moddata/offlinequiz/' . $offlinequiz->id . '/pdfs';
            // if the answer pdf file for group 1 exists then we have created the documents
            if (file_exists($dirname . '/answer-a.pdf')) {
                $DB->set_field('offlinequiz', 'docscreated', 1, array('id' => $offlinequiz->id));
            }
        }
        // offlinequiz savepoint reached
        upgrade_mod_savepoint(true, 2012010101, 'offlinequiz');
    }

    if ($oldversion < 2012010105) {

        // Define table offlinequiz_reports to be created
        $table = new xmldb_table('offlinequiz_reports');

        // Adding fields to table offlinequiz_reports
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('displayorder', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('lastcron', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('cron', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('capability', XMLDB_TYPE_CHAR, '255', null, null, null, null);

        // Adding keys to table offlinequiz_reports
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table offlinequiz_reports
        // $table->add_index('name', XMLDB_INDEX_UNIQUE, array('name'));

        // Conditionally launch create table for offlinequiz_reports
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        if (!$DB->get_records_sql("SELECT * FROM {offlinequiz_reports} WHERE name = 'overview'", array())) {
            $record = new stdClass();
            $record->name         = 'overview';
            $record->displayorder = '10000';
            $DB->insert_record('offlinequiz_reports', $record);
        }
        if (!$DB->get_records_sql("SELECT * FROM {offlinequiz_reports} WHERE name = 'rimport'", array())) {
            $record = new stdClass();
            $record->name         = 'rimport';
            $record->displayorder = '9000';
            $DB->insert_record('offlinequiz_reports', $record);
        }

        // offlinequiz savepoint reached
        upgrade_mod_savepoint(true, 2012010105, 'offlinequiz');
    }

    // ======================================================
    // now we create all the new tables
    // ======================================================
    // create table offlinequiz_groups
    if ($oldversion < 2012010200) {

        echo $OUTPUT->notification('Creating new tables', 'notifysuccess');

        // Define table offlinequiz_groups to be created
        $table = new xmldb_table('offlinequiz_groups');

        // Adding fields to table offlinequiz_groups
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('offlinequizid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('number', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('sumgrades', XMLDB_TYPE_NUMBER, '10, 5', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('numberofpages', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('templateusageid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

        // Adding keys to table offlinequiz_groups
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table offlinequiz_groups
        $table->add_index('offlinequizid', XMLDB_INDEX_NOTUNIQUE, array('offlinequizid'));

        // Conditionally launch create table for offlinequiz_groups
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // offlinequiz savepoint reached
        upgrade_mod_savepoint(true, 2012010200, 'offlinequiz');
    }

    // create table offlinequiz_group_questions
    if ($oldversion < 2012010300) {

        // Define table offlinequiz_group_questions to be created
        $table = new xmldb_table('offlinequiz_group_questions');

        // Adding fields to table offlinequiz_group_questions
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('offlinequizid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('offlinegroupid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('questionid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('position', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1');
        $table->add_field('pagenumber', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, null, null, null);
        $table->add_field('usageslot', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, null, null, null);

        // Adding keys to table offlinequiz_group_questions
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table offlinequiz_group_questions
        $table->add_index('offlinequiz', XMLDB_INDEX_NOTUNIQUE, array('offlinequizid'));

        // Conditionally launch create table for offlinequiz_group_questions
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // offlinequiz savepoint reached
        upgrade_mod_savepoint(true, 2012010300, 'offlinequiz');
    }

    if ($oldversion < 2012010400) {

        // Define table offlinequiz_scanned_pages to be created
        $table = new xmldb_table('offlinequiz_scanned_pages');

        // Adding fields to table offlinequiz_scanned_pages
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('offlinequizid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('resultid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $table->add_field('filename', XMLDB_TYPE_CHAR, '1000', null, null, null, null);
        $table->add_field('warningfilename', XMLDB_TYPE_CHAR, '1000', null, null, null, null);
        $table->add_field('groupnumber', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, null, null, null);
        $table->add_field('userkey', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('pagenumber', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, null, null, null);
        $table->add_field('time', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('error', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('info', XMLDB_TYPE_TEXT, 'medium', null, null, null, null);

        // Adding keys to table offlinequiz_scanned_pages
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table offlinequiz_scanned_pages
        $table->add_index('offlinequizid', XMLDB_INDEX_NOTUNIQUE, array('offlinequizid'));

        // Conditionally launch create table for offlinequiz_scanned_pages
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // offlinequiz savepoint reached
        upgrade_mod_savepoint(true, 2012010400, 'offlinequiz');
    }

    if ($oldversion < 2012010500) {

        // Define table offlinequiz_choices to be created
        $table = new xmldb_table('offlinequiz_choices');

        // Adding fields to table offlinequiz_choices
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('scannedpageid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('slotnumber', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('choicenumber', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('value', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table offlinequiz_choices
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table offlinequiz_choices
        $table->add_index('scannedpageid', XMLDB_INDEX_NOTUNIQUE, array('scannedpageid'));

        // Conditionally launch create table for offlinequiz_choices
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // offlinequiz savepoint reached
        upgrade_mod_savepoint(true, 2012010500, 'offlinequiz');
    }

    if ($oldversion < 2012010600) {

        // Define table offlinequiz_page_corners to be created
        $table = new xmldb_table('offlinequiz_page_corners');

        // Adding fields to table offlinequiz_page_corners
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('scannedpageid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('x', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('y', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('position', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);

        // Adding keys to table offlinequiz_page_corners
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for offlinequiz_page_corners
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // offlinequiz savepoint reached
        upgrade_mod_savepoint(true, 2012010600, 'offlinequiz');
    }

    if ($oldversion < 2012010700) {

        // Define table offlinequiz_results to be created
        $table = new xmldb_table('offlinequiz_results');

        // Adding fields to table offlinequiz_results
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('offlinequizid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('offlinegroupid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('sumgrades', XMLDB_TYPE_NUMBER, '10, 5', null, null, null, null);
        $table->add_field('usageid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('teacherid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('attendant', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('status', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timestart', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('timefinish', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('preview', XMLDB_TYPE_INTEGER, '3', XMLDB_UNSIGNED, null, null, '0');

        // Adding keys to table offlinequiz_results
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for offlinequiz_results
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // offlinequiz savepoint reached
        upgrade_mod_savepoint(true, 2012010700, 'offlinequiz');
    }

    if ($oldversion < 2012010800) {

        // Define table offlinequiz_scanned_p_pages to be created
        $table = new xmldb_table('offlinequiz_scanned_p_pages');

        // Adding fields to table offlinequiz_scanned_p_pages
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('offlinequizid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('listnumber', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, null, null, null);
        $table->add_field('filename', XMLDB_TYPE_CHAR, '1000', null, null, null, null);
        $table->add_field('time', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('status', XMLDB_TYPE_TEXT, 'small', null, XMLDB_NOTNULL, null);
        $table->add_field('error', XMLDB_TYPE_TEXT, 'small', null, null, null, null);

        // Adding keys to table offlinequiz_scanned_p_pages
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for offlinequiz_scanned_p_pages
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // offlinequiz savepoint reached
        upgrade_mod_savepoint(true, 2012010800, 'offlinequiz');
    }

    if ($oldversion < 2012010900) {

        // Define table offlinequiz_p_choices to be created
        $table = new xmldb_table('offlinequiz_p_choices');

        // Adding fields to table offlinequiz_p_choices
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('scannedppageid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $table->add_field('value', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table offlinequiz_p_choices
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for offlinequiz_p_choices
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // offlinequiz savepoint reached
        upgrade_mod_savepoint(true, 2012010900, 'offlinequiz');
    }

    if ($oldversion < 2012011000) {

        // Define table offlinequiz_p_lists to be created
        $table = new xmldb_table('offlinequiz_p_lists');

        // Adding fields to table offlinequiz_p_lists
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('offlinequizid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('number', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1');
        $table->add_field('filename', XMLDB_TYPE_CHAR, '1000', null, null, null, null);

        // Adding keys to table offlinequiz_p_lists
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table offlinequiz_p_lists
        $table->add_index('offlinequizid', XMLDB_INDEX_NOTUNIQUE, array('offlinequizid'));

        // Conditionally launch create table for offlinequiz_p_lists
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // offlinequiz savepoint reached
        upgrade_mod_savepoint(true, 2012011000, 'offlinequiz');
    }

    // ======================================================
    // new we rename fields in old tables
    // ======================================================

    // rename fields in offlinequiz_queue table
    if ($oldversion < 2012020100) {

        echo $OUTPUT->notification('Renaming fields in old tables.', 'notifysuccess');

        // Rename field offlinequiz on table offlinequiz_queue to NEWNAMEGOESHERE
        $table = new xmldb_table('offlinequiz_queue');
        $field = new xmldb_field('offlinequiz');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'timefinish');

        // Launch rename field offlinequiz
        $dbman->rename_field($table, $field, 'offlinequizid');

        $field = new xmldb_field('importadmin');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'id');

        // Launch rename field importadmin
        $dbman->rename_field($table, $field, 'importuserid');

        // new status field
        $field = new xmldb_field('status', XMLDB_TYPE_TEXT, 'small', null, null, null, 'processed', 'timefinish');

        // Conditionally launch add field status
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // offlinequiz savepoint reached
        upgrade_mod_savepoint(true, 2012020100, 'offlinequiz');
    }

    // add and rename fields in table offlinquiz_queue_data
    if ($oldversion < 2012020200) {

        // Define field status to be added to offlinequiz_queue_data
        $table = new xmldb_table('offlinequiz_queue_data');
        $field = new xmldb_field('status', XMLDB_TYPE_TEXT, 'small', null, XMLDB_NOTNULL, null, 'ok', 'filename');

        // Conditionally launch add field status
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        } else {
            $dbman->change_field_type($table, $field);
            $dbman->change_field_precision($table, $field);
            $dbman->change_field_notnull($table, $field);
            $dbman->change_field_unsigned($table, $field);
        }

        // add new field 'error'
        $field = new xmldb_field('error', XMLDB_TYPE_TEXT, 'small', null, null, null, null, 'status');

        // Conditionally launch add field error
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // rename field queue to queueid
        $field = new xmldb_field('queue', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'id');

        // Launch rename field queueid
        $dbman->rename_field($table, $field, 'queueid');

        // offlinequiz savepoint reached
        upgrade_mod_savepoint(true, 2012020200, 'offlinequiz');
    }

    // Rename field list on table offlinequiz_participants to listid
    if ($oldversion < 2012020300) {

        // Rename field list on table offlinequiz_participants to listid
        $table = new xmldb_table('offlinequiz_participants');
        $field = new xmldb_field('list', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'id');

        // Launch rename field listid
        $dbman->rename_field($table, $field, 'listid');

        // offlinequiz savepoint reached
        upgrade_mod_savepoint(true, 2012020300, 'offlinequiz');
    }

    // migrate the old lists of participants to the new table offlinequiz_p_lists (with 's').
    if ($oldversion < 2012020400) {

        $oldplists = $DB->get_records('offlinequiz_p_list');
        foreach ($oldplists as $oldplist) {
            $newplist = new StdClass();
            $newplist->offlinequizid = $oldplist->offlinequiz;
            $newplist->name = $oldplist->name;
            $newplist->number = $oldplist->list;
            // NOTE
            // we don't set filename because we can always recreate the PDF files if needed.
            $newplist->id = $DB->insert_record('offlinequiz_p_lists', $newplist);

            // get all the participants linked to the old list and link them to the new list in offlinequiz_p_lists
            if ($oldparts = $DB->get_records('offlinequiz_participants', array('listid' => $oldplist->id))) {
                foreach ($oldparts as $oldpart) {
                    $oldpart->listid = $newplist->id;
                    $DB->update_record('offlinequiz_participants', $oldpart);
                }
            }
        }

        // offlinequiz savepoint reached
        upgrade_mod_savepoint(true, 2012020400, 'offlinequiz');
    }

    // check if there are inconsistencies in the DB, i.e. uniqueids used by both quizzes and offlinequizzes
    if ($oldversion < 2012020410) {

        $sql = 'SELECT uniqueid
        FROM {offlinequiz_attempts} qa WHERE
        EXISTS (SELECT id from {quiz_attempts} where uniqueid = qa.uniqueid)';
        $doubleids = $DB->get_fieldset_sql($sql, array());

        // for each double uniqueid create a new uniqueid and change the fields in the tables
        // offlinequiz_attempts, question_sessions and question_states.
        echo $OUTPUT->notification('Fixing ' . count($doubleids) . ' question attempt uniqueids that are not unique', 'notifysuccess');

        foreach ($doubleids as $doubleid) {
            echo $doubleid . ', ';
            if ($usage = $DB->get_record('question_usages', array('id' => $doubleid))) {
                $transaction = $DB->start_delegated_transaction();
                unset($usage->id);
                $usage->id = $DB->insert_record('question_usages', $usage);

                $DB->set_field_select('offlinequiz_attempts', 'uniqueid', $usage->id, 'uniqueid = :oldid', array('oldid' => $doubleid));
                $DB->set_field_select('question_states', 'attempt', $usage->id, 'attempt = :oldid', array('oldid' => $doubleid));
                $DB->set_field_select('question_sessions', 'attemptid', $usage->id, 'attemptid = :oldid', array('oldid' => $doubleid));
                $transaction->allow_commit();
            }
        }
        upgrade_mod_savepoint(true, 2012020410, 'offlinequiz');
    }

    // ==========================================================
    //  update the contextid field in question_usages (compare lib/db/upgrade.php lines 6108 following)
    // ==========================================================
    if ($oldversion < 2012020500) {

        echo $OUTPUT->notification('Fixing question usages context ID', 'notifysuccess');

        // update the component field if necessary
        $DB->set_field('question_usages', 'component', 'mod_offlinequiz', array('component' => 'offlinequiz'));

        // Populate the contextid field.
        $offlinequizmoduleid = $DB->get_field('modules', 'id', array('name' => 'offlinequiz'));
        $DB->execute("
                UPDATE {question_usages} SET contextid = (
                SELECT ctx.id
                FROM {context} ctx
                JOIN {course_modules} cm ON cm.id = ctx.instanceid AND cm.module = $offlinequizmoduleid
                JOIN {offlinequiz_attempts} quiza ON quiza.offlinequiz = cm.instance
                WHERE ctx.contextlevel = " . CONTEXT_MODULE . "
                AND quiza.uniqueid = {question_usages}.id)
                WHERE (
                SELECT ctx.id
                FROM {context} ctx
                JOIN {course_modules} cm ON cm.id = ctx.instanceid AND cm.module = $offlinequizmoduleid
                JOIN {offlinequiz_attempts} quiza ON quiza.offlinequiz = cm.instance
                WHERE ctx.contextlevel = " . CONTEXT_MODULE . "
                AND quiza.uniqueid = {question_usages}.id) IS NOT NULL
                ");

        // offlinequiz savepoint reached
        upgrade_mod_savepoint(true, 2012020500, 'offlinequiz');
    }

    // ==========================================================
    //  now we migrate data from the old to the new tables
    // ==========================================================

    // we have to delete redundant question instances from offlinequizzes because they are incompatible with the new code
    if ($oldversion < 2012030100) {

        echo $OUTPUT->notification('Migrating old offline quizzes to new offline quizzes...', 'notifysuccess');

        require_once($CFG->dirroot . '/mod/offlinequiz/db/upgradelib.php');
        offlinequiz_remove_redundant_q_instances();

        // offlinequiz savepoint reached
        upgrade_mod_savepoint(true, 2012030100, 'offlinequiz');
    }

    // migrate all entries in the offlinequiz_group table to the new tables offlinequiz_groups  and offlinequiz_group_questions
    if ($oldversion < 2012030101) {

        echo $OUTPUT->notification('Creating new offlinequiz groups', 'notifysuccess');

        $offlinequizzes = $DB->get_records('offlinequiz');

        $counter = 0;
        foreach ($offlinequizzes as $offlinequiz) {
            if (!$DB->get_records('offlinequiz_groups', array('offlinequizid' => $offlinequiz->id))) {
                echo '.';
                $counter++;
                flush();
                ob_flush();
                if ($counter % 100 == 0) {
                    echo "<br/>\n";
                    echo $counter;
                }
                $transaction = $DB->start_delegated_transaction();
                $oldgroups = $DB->get_records('offlinequiz_group', array('offlinequiz' => $offlinequiz->id), 'groupid ASC');
                $newgroups = array();
                foreach ($oldgroups as $oldgroup) {
                    $newgroup = new StdClass();
                    $newgroup->offlinequizid = $offlinequiz->id;
                    $newgroup->number = $oldgroup->groupid;
                    $newgroup->sumgrades = $oldgroup->sumgrades;
                    $newgroup->timecreated = time();
                    $newgroup->timemodified = time();
                    // first we need the ID of the new group
                    if (!$oldid = $DB->get_field('offlinequiz_groups', 'id', array('offlinequizid' => $offlinequiz->id,
                            'number' => $newgroup->number))) {
                            $newgroup->id = $DB->insert_record('offlinequiz_groups', $newgroup);
                    } else {
                        $newgroup->id = $oldid;
                    }
                    // now create an entry in offlinquiz_group_questions for each question in the old group layout
                    $questions = explode(',', $oldgroup->questions);
                    $position = 1;
                    foreach ($questions as $question) {
                        $groupquestion = new StdClass();
                        $groupquestion->offlinequizid = $offlinequiz->id;
                        $groupquestion->offlinegroupid = $newgroup->id;
                        $groupquestion->questionid = $question;
                        $groupquestion->position = $position++;
                        if (!$DB->get_record('offlinequiz_group_questions', array('offlinequizid' => $offlinequiz->id,
                                'offlinegroupid' => $newgroup->id,
                                'questionid' => $question))) {
                                $DB->insert_record('offlinequiz_group_questions', $groupquestion);
                        }
                    }
                    $newgroups[] = $newgroup;

                }
                require_once($CFG->dirroot . '/mod/offlinequiz/evallib.php');
                list($maxquestions, $maxanswers, $formtype, $questionsperpage) =  offlinequiz_get_question_numbers($offlinequiz, $newgroups);

                foreach ($newgroups as $newgroup) {
                    // now we know the number of pages of the group
                    $newgroup->numberofpages = ceil($maxquestions / ($formtype * 24));
                    $DB->update_record('offlinequiz_groups', $newgroup);
                }

                $transaction->allow_commit();
            }
        }

        // offlinequiz savepoint reached
        upgrade_mod_savepoint(true, 2012030101, 'offlinequiz');
    }

    // migrate all entries in the offlinequiz_i_log table to the new tables offlinequiz_scanned_pages, offlinequiz_choices and
    // offlinequiz_page_corners. Also migrate the files to the new filesystem.

    // first we mark all offlinequizzes s.t. we upgrade them only once. Many things can go wrong here...
    if ($oldversion < 2012030200) {
        // Define field needsilogupgrade to be added to offlinequiz_attempts
        $table = new xmldb_table('offlinequiz');
        $field = new xmldb_field('needsilogupgrade', XMLDB_TYPE_INTEGER, '3', XMLDB_UNSIGNED,
                XMLDB_NOTNULL, null, '0', 'timeopen');

        // Launch add field needsilogupgrade
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $DB->set_field('offlinequiz', 'needsilogupgrade', 1);

        // offlinequiz savepoint reached
        upgrade_mod_savepoint(true, 2012030200, 'offlinequiz');
    }

    // then we mark all offlinequiz_attempts to be upgraded
    if ($oldversion < 2012030300) {
        // Define field needsupgradetonewqe to be added to offlinequiz_attempts
        $table = new xmldb_table('offlinequiz_attempts');
        $field = new xmldb_field('needsupgradetonewqe', XMLDB_TYPE_INTEGER, '3', XMLDB_UNSIGNED,
                XMLDB_NOTNULL, null, '0', 'sheet');

        // Launch add field needsupgradetonewqe
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $DB->set_field('offlinequiz_attempts', 'needsupgradetonewqe', 1);

        // quiz savepoint reached
        upgrade_mod_savepoint(true, 2012030300, 'offlinequiz');
    }

    // In a first step we upgrade the offlinequiz_attempts exactly like quiz_attempts (see mod/quiz/db/upgrade.php)
    if ($oldversion < 2012030400) {
        $table = new xmldb_table('question_states');
        // echo "upgrading attempts to new question engine <br/>\n";

        if ($dbman->table_exists($table)) {
            // NOTE: We need all attemps, also the ones with sheet=1 because the are the groups' template attempts

            // Now update all the old attempt data.
            $oldrcachesetting = $CFG->rcache;
            $CFG->rcache = false;

            require_once($CFG->dirroot . '/mod/offlinequiz/db/upgradelib.php');

            $upgrader = new offlinequiz_attempt_upgrader();
            $upgrader->convert_all_quiz_attempts();

            $CFG->rcache = $oldrcachesetting;
        }

        // offlinequiz savepoint reached
        upgrade_mod_savepoint(true, 2012030400, 'offlinequiz');
    }

    // then we mark all offlinequiz_attempts to be upgraded
    if ($oldversion < 2012030500) {
        // Define field resultid to be added to offlinequiz_attempts for later reference
        set_time_limit(3000);

        $table = new xmldb_table('offlinequiz_attempts');
        $field = new xmldb_field('resultid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

        // Launch add field resultid
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // quiz savepoint reached
        upgrade_mod_savepoint(true, 2012030500, 'offlinequiz');
    }

    // In a second step we convert all offlinequiz_attempts into offlinequiz_results and also upgrade the ilog table.
    if ($oldversion < 2012060101) {

        require_once($CFG->dirroot . '/mod/offlinequiz/db/upgradelib.php');

        $oldrcachesetting = $CFG->rcache;
        $CFG->rcache = false;

        $upgrader = new offlinequiz_ilog_upgrader();
        $upgrader->convert_all_offlinequiz_attempts();

        $CFG->rcache = $oldrcachesetting;
        // offlinequiz savepoint reached
        upgrade_mod_savepoint(true, 2012060101, 'offlinequiz');
    }

    if ($oldversion < 2012060105) {

        // Changing type of field grade on table offlinequiz_q_instances to number
        $table = new xmldb_table('offlinequiz_q_instances');
        $field = new xmldb_field('grade', XMLDB_TYPE_NUMBER, '12, 7', null, XMLDB_NOTNULL, null, '0', 'question');

        // Launch change of type for field grade
        $dbman->change_field_type($table, $field);
        // Launch change of precision for field grade
        $dbman->change_field_precision($table, $field);

        // offlinequiz savepoint reached
        upgrade_mod_savepoint(true, 2012060105, 'offlinequiz');
    }

    if ($oldversion < 2012121200) {

        // Define field introformat to be added to offlinequiz
        $table = new xmldb_table('offlinequiz');
        $field = new xmldb_field('introformat', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'intro');

        // Conditionally launch add field introformat
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // offlinequiz savepoint reached
        upgrade_mod_savepoint(true, 2012121200, 'offlinequiz');
    }
}