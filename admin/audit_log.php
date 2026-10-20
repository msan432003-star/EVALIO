<?php

if (!function_exists('logAction')) {
    /**
     * Record an admin action in the audit log.
     *
     * @param int    $admin_id     ID of the admin performing the action.
     * @param string $action       Short action code, e.g. 'approve_exam'.
     * @param string $target_table Table the action was performed on.
     * @param int    $target_id    ID of the row affected.
     * @param string $notes        Optional free-text notes.
     */
    function logAction($admin_id, $action, $target_table, $target_id, $notes = '')
    {
        global $dao;

        if (!($dao instanceof DataAccess)) {
            error_log('logAction: $dao is not a DataAccess object, nothing logged');
            return false;   // nothing to log to - don't take the page down with us
        }

        // Quotes and backslashes in the notes can break the INSERT when the DAO
        // does not escape values, so remove them (e.g. O'Reilly is logged as OReilly).
        $notes = str_replace(array("'", '"', '\\'), '', (string)$notes);

        // DataAccess::insert() takes the data array FIRST, then the table name
        // (same as course.php).
        try {
            $ok = $dao->insert(array(
                'admin_id'     => $admin_id,
                'action'       => $action,
                'target_table' => $target_table,
                'target_id'    => $target_id,
                'notes'        => $notes,
            ), 'audit_logs');

            if (!$ok) {
                error_log('logAction: insert into audit_logs returned false for action ' . $action);
            }
            return $ok;
        } catch (Throwable $e) {
            // The main action still succeeds; the reason is kept in the PHP error log
            error_log('logAction failed for ' . $action . ': ' . $e->getMessage());
            return false;
        }
    }
}