-- 023_alter_staff_status_inactive.sql
-- A member who leaves the university (a transfer, a resignation) cannot be
-- DELETEd: nine tables CASCADE from staff (their leave history, messages,
-- workload…), four SET NULL (who reviewed a leave request, who manages a
-- session…) and leave_days.cover_code RESTRICTs the delete outright. So they
-- are deactivated instead: the row stays, every history screen still resolves
-- their name, and login refuses anything that is not `active`.
--
-- `deactivated_at` / `deactivated_by` record when and by whom, and are cleared
-- again on reactivation. The existing chk_staff_rank_matches_role CHECK needs
-- no change: an inactive row keeps its role and rank.
--
-- One statement, like every migration here (see 016 for why).
ALTER TABLE staff
    MODIFY COLUMN status ENUM('pending', 'active', 'inactive') NOT NULL DEFAULT 'active',
    ADD COLUMN deactivated_at DATETIME    NULL AFTER status,
    ADD COLUMN deactivated_by VARCHAR(12) NULL AFTER deactivated_at,
    ADD CONSTRAINT fk_staff_deactivated_by FOREIGN KEY (deactivated_by) REFERENCES staff (code) ON DELETE SET NULL;
