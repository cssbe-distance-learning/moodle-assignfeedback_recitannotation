<?php
// This file is part of Moodle - http://moodle.org/
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
 * Upgrade script for assignfeedback_recitannotation.
 *
 * @package   assignfeedback_recitannotation
 * @copyright 2025 RÉCIT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade the plugin's database tables.
 *
 * @param int $oldversion the version we are upgrading from
 * @return bool result
 */
function xmldb_assignfeedback_recitannotation_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    $newversion = 2025120802;
    if ($oldversion < $newversion) {
        // Create new table for AI prompts.
        $table = new xmldb_table('assignfeedback_recitannot_promptai');

        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('assignment', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('prompt_ai', XMLDB_TYPE_TEXT, null, null, null, null, null);

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('fkassignmentid', XMLDB_KEY_FOREIGN, ['assignment'], 'assign', ['id']);
            $table->add_key('uniqueassignment', XMLDB_KEY_UNIQUE, ['assignment']);

            $dbman->create_table($table);
        }

        // Update criteria table with AI instruction field.
        $table = new xmldb_table('assignfeedback_recitannot_crit');

        $fields = [
            new xmldb_field('instruction_ai', XMLDB_TYPE_TEXT, null, null, null, null, null),
        ];

        // Conditionally launch add fields.
        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        upgrade_plugin_savepoint(true, $newversion, 'assignfeedback', 'recitannotation');
    }

    $newversion = 2026061201;
    if ($oldversion < $newversion) {
        // Rename tables to use the full plugin component prefix.
        $renames = [
            'assignfeedback_recitannot_crit'     => 'assignfeedback_recitannotation_crit',
            'assignfeedback_recitannot_comment'  => 'assignfeedback_recitannotation_comment',
            'assignfeedback_recitannot_promptai' => 'assignfeedback_recitannotation_promptai',
        ];
        foreach ($renames as $oldname => $newname) {
            $oldtable = new xmldb_table($oldname);
            if ($dbman->table_exists($oldtable)) {
                $dbman->rename_table($oldtable, $newname);
            }
        }

        upgrade_plugin_savepoint(true, $newversion, 'assignfeedback', 'recitannotation');
    }

    return true;
}
