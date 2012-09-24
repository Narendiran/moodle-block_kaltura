<?php
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
 * The is the block file for the Kaltura Video Extension
 *
 * @package   blocks-kaltura
 * @author    Akinsaya Delamarre <adelamarre@remote-learner.net>
 * @copyright 2011 Remote Learner - http://www.remote-learner.net/
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_kaltura extends block_base {
    function init() {
        $this->title   = get_string('blockname','block_kaltura');
        $this->version = 2012060803;
    }

    function get_content() {
        global $CFG;

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;

        $this->content = 'kaltura';

        return $this->content;

    }

    function instance_allow_config() {
        return false;
    }

    function instance_allow_multiple() {
        return true;
    }

    /**
     * Allow global configuration
     *
     * @return bool true
     */
    function has_config() {
        return true;
    }

   /**
     * Enable custom instance data section in backup and restore.
     *
     * If return true, then {@link instance_backup()} and
     * {@link instance_restore()} will be called during
     * backup/restore routines.
     *
     * @return boolean
     **/
    function backuprestore_instancedata_used() {
        return true;
    }

    /**
     * Allows the block class to have a backup routine.  Handy
     * when the block has its own tables that have foreign keys to
     * other tables (example: user table).
     *
     * Note: at the time of writing this comment, the indent level
     * for the {@link full_tag()} should start at 5.
     *
     * @param resource $bf Backup File
     * @param object $preferences Backup preferences
     * @return boolean
     **/
    function instance_backup($bf, $preferences) {
        global $CFG;

        $status = true;

        $records = get_records('block_kaltura_entries');

        //Start block_kaltura
        $status = fwrite ($bf,start_tag("BLOCK_KALTURA_ENTRIES", 5, true));

        // If assignment user info is being backed up.  Backup the submissions in the block backup
        if ($preferences->backup_user_info_assignment) {

            foreach ($preferences->assignment_instances as $instance) {

                if (0 == strcmp($instance->assignmenttype, 'kaltura')) {

                    $actid = 'backup_assignment_instance_' . $instance->id;

                    if (1 == $preferences->$actid) {

                        $assign_submit_id = get_field('assignment_submissions', 'id', 'assignment', $instance->id);

                        $record = get_record('block_kaltura_entries', 'context', 'S_'.$assign_submit_id,
                                             'courseid', $instance->course);

                        if ($record) {
                            $status = fwrite ($bf,start_tag("BLOCK_KALTURA_ENTRY", 6, true));

                            fwrite ($bf,full_tag("COURSEID", 7, false, $instance->course));
                            fwrite ($bf,full_tag("ID", 7, false, $record->id));
                            fwrite ($bf,full_tag("ENTRYID", 7, false, $record->entry_id));
                            fwrite ($bf,full_tag("DIMENSIONS", 7, false, $record->dimensions));
                            fwrite ($bf,full_tag("SIZE", 7, false, $record->size));
                            fwrite ($bf,full_tag("CUSTOMWIDTH", 7, false, $record->custom_width));
                            fwrite ($bf,full_tag("DESIGN", 7, false, $record->design));
                            fwrite ($bf,full_tag("TITLE", 7, false, $record->title));
                            fwrite ($bf,full_tag("CONTEXT", 7, false ,'S_' . $assign_submit_id));
                            fwrite ($bf,full_tag("ENTRYTYPE", 7, false, $record->entry_type));
                            fwrite ($bf,full_tag("MEDIATYPE", 7, false, $record->media_type));

                            $status = fwrite ($bf,end_tag("BLOCK_KALTURA_ENTRY", 6, true));
                        }
                    }
                }

            }
        }


        foreach ($preferences->resource_instances as $instance) {

            if (0 == strcmp($instance->type, 'kalturavideo') or
                0 == strcmp($instance->type, 'kalturaswfdoc')) {

                $actid = 'backup_resource_instance_' . $instance->id;

                if (1 == $preferences->$actid) {

                    $record = get_record('block_kaltura_entries', 'context', 'R_'.$instance->id,
                                         'courseid', $instance->course);

                    if ($record) {
                        $status = fwrite ($bf,start_tag("BLOCK_KALTURA_ENTRY", 6, true));

                        fwrite ($bf,full_tag("ID", 7, false, $record->id));
                        fwrite ($bf,full_tag("COURSEID", 7, false, $record->courseid));
                        fwrite ($bf,full_tag("ENTRYID", 7, false, $record->entry_id));
                        fwrite ($bf,full_tag("DIMENSIONS", 7, false, $record->dimensions));
                        fwrite ($bf,full_tag("SIZE", 7, false, $record->size));
                        fwrite ($bf,full_tag("CUSTOMWIDTH", 7, false, $record->custom_width));
                        fwrite ($bf,full_tag("DESIGN", 7, false, $record->design));
                        fwrite ($bf,full_tag("TITLE", 7, false, $record->title));
                        fwrite ($bf,full_tag("CONTEXT", 7, false ,'R_' . $instance->id));
                        fwrite ($bf,full_tag("ENTRYTYPE", 7, false, $record->entry_type));
                        fwrite ($bf,full_tag("MEDIATYPE", 7, false, $record->media_type));

                        $status = fwrite ($bf,end_tag("BLOCK_KALTURA_ENTRY", 6, true));
                    }
                }
            }
        }

        $status = fwrite ($bf,end_tag("BLOCK_KALTURA_ENTRIES", 5, true));

        return $status;
    }


    /**
     * Allows the block class to restore its backup routine.
     *
     * Should not return false if data is empty
     * because old backups would not contain block instance backup data.
     *
     * @param object $restore Standard restore object
     * @param object $data Object from backup_getid for this block instance
     * @return boolean
     **/
    function instance_restore($restore, $data) {


        $instance_obj = $data->info;
        $instancedata =
        $instance_obj['BLOCK_KALTURA_ENTRIES'][0]['#']['BLOCK_KALTURA_ENTRY'];

        for ($i = 0; $i < count($instancedata); $i++) {

            $activityinstance = $instancedata[$i];

            $kaltura                = new stdClass();
            $oldid                  = $activityinstance['#']['ID'][0]['#'];
            $kaltura->courseid      = $activityinstance['#']['COURSEID'][0]['#'];
            $kaltura->entry_id      = addslashes($activityinstance['#']['ENTRYID'][0]['#']);
            $kaltura->dimensions    = addslashes($activityinstance['#']['DIMENSIONS'][0]['#']);
            $kaltura->size          = addslashes($activityinstance['#']['SIZE'][0]['#']);
            $kaltura->custom_width  = addslashes($activityinstance['#']['CUSTOMWIDTH'][0]['#']);
            $kaltura->design        = addslashes($activityinstance['#']['DESIGN'][0]['#']);
            $kaltura->title         = addslashes($activityinstance['#']['TITLE'][0]['#']);

            if (empty($kaltura->title)) {
                $kaltura->title = '...';
            }

            $kaltura->context       = addslashes($activityinstance['#']['CONTEXT'][0]['#']);

            // Determine the activity type and get the new ID of the submission
            $table_name = '';
            $context = '';
            $activity_instance_id = false;

            if (false !== strpos($kaltura->context, 'S_')) {

                $activity_instance_id   = substr($kaltura->context, 2);
                $context                = 'S_';
                $table_name             = 'assignment_submission';
            } elseif (false !== strpos($kaltura->context, 'R_')) {

                $activity_instance_id   = substr($kaltura->context, 2);
                $context                = 'R_';
                $table_name             = 'resource';
            }

            if (false !== $activity_instance_id) {
                $new_instance = get_record('backup_ids',
                                           'backup_code', $restore->backup_unique_code,
                                           'table_name', $table_name,
                                           'old_id', $activity_instance_id,
                                           'new_id');

                if ($new_instance) {
                    $kaltura->context = $context . $new_instance->new_id;
                }
            } else {
                return false;
            }

            $kaltura->entry_type    = addslashes($activityinstance['#']['ENTRYTYPE'][0]['#']);
            $kaltura->media_type    = addslashes($activityinstance['#']['MEDIATYPE'][0]['#']);


            $kaltura->courseid = $restore->course_id;
            insert_record('block_kaltura_entries', $kaltura);

            if (!defined('RESTORE_SILENTLY')) {
                echo "<li>".get_string('blockname','block_kaltura')." \"".format_string(stripslashes($kaltura->title), true)."\"</li>";
            }

        }


        return true;
    }


    /**
     * Migrate all kaltura entries from the module version of the tables
     * to the block version of the tables
     */
    function after_install() {
        global $CFG;

        $table = new XMLDBTable('kaltura_entries');

        if (table_exists($table)) {
            $sql = "SELECT * ".
                   "FROM {$CFG->prefix}kaltura_entries ".
                   "WHERE context LIKE 'R_%'";

            // Migrate resource kaltura entries over to the block table
            $kaltura_entries = get_recordset_sql($sql);

            if ($kaltura_entries) {

                while (!$kaltura_entries->EOF) {


                    $context = $kaltura_entries->fields['context'];

                    // $context_parts[1] should equal the id of the plugin type (assignment/resource)
                    $context_parts = array();
                    $context_parts = explode('_', $context);

                    if (!empty($context_parts)) {

                    $course = get_field('resource', 'course', 'id', $context_parts[1]);

                        if (!empty($course)) {
                            $newrec = new stdClass();

                            $newrec->courseid       = $course;
                            $newrec->entry_id       = $kaltura_entries->fields['entry_id'];
                            $newrec->dimensions     = $kaltura_entries->fields['dimensions'];;
                            $newrec->size           = $kaltura_entries->fields['size'];;
                            $newrec->custom_width   = $kaltura_entries->fields['custom_width'];;
                            $newrec->design         = $kaltura_entries->fields['design'];;
                            $newrec->title          = $kaltura_entries->fields['title'];;
                            $newrec->context        = $context;
                            $newrec->entry_type     = $kaltura_entries->fields['entry_type'];;
                            $newrec->media_type     = $kaltura_entries->fields['media_type'];

                            $id = insert_record('block_kaltura_entries', $newrec);

                            if ($id) {
                                //
                            }

                        }

                    }

                    $kaltura_entries->MoveNext();
                }
            }

        // Migrate resource kaltura entries over to the block table
            $sql = "SELECT * ".
                   "FROM {$CFG->prefix}kaltura_entries ".
                   "WHERE context LIKE 'S_%'";

            $kaltura_entries = get_recordset_sql($sql);

            if ($kaltura_entries) {

                while (!$kaltura_entries->EOF) {


                    $context = $kaltura_entries->fields['context'];

                    // $context_parts[1] should equal the id of the plugin type (assignment/resource)
                    $context_parts = array();
                    $context_parts = explode('_', $context);

                    if (!empty($context_parts)) {

                        $field = $context_parts[1];
                        $sql = "SELECT assign_sumbit.id, a.course ".
                               "FROM {$CFG->prefix}assignment_submissions assign_sumbit ".
                               "RIGHT JOIN {$CFG->prefix}assignment a ON assign_sumbit.assignment = a.id ".
                               " WHERE assign_sumbit.id = {$field}";

                        $data = get_record_sql($sql);

                        if (!empty($data)) {

                            $newrec = new stdClass();

                            $newrec->courseid       = $data->course;
                            $newrec->entry_id       = $kaltura_entries->fields['entry_id'];
                            $newrec->dimensions     = $kaltura_entries->fields['dimensions'];;
                            $newrec->size           = $kaltura_entries->fields['size'];;
                            $newrec->custom_width   = $kaltura_entries->fields['custom_width'];;
                            $newrec->design         = $kaltura_entries->fields['design'];;
                            $newrec->title          = $kaltura_entries->fields['title'];;
                            $newrec->context        = $context;
                            $newrec->entry_type     = $kaltura_entries->fields['entry_type'];;
                            $newrec->media_type     = $kaltura_entries->fields['media_type'];

                            $id = insert_record('block_kaltura_entries', $newrec);

                            if ($id) {
                                //
                            }
                        }
                    }

                    $kaltura_entries->MoveNext();
                }
            }

        }
    }

}
?>
