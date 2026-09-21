# Seeded users

These are the built-in login accounts created by the seed data for local testing and demo usage. All seeded accounts use the same placeholder password:

`Password123!`

This is the same password documented in [database/README.md](database/README.md).

## Accounts

| Role / position | Dashboard | Email | Name |
|---|---|---|---|
| Timetable Officer | `/timetable` | `tmo@ucsc.cmb.ac.lk` | T. M. Officer |
| Coordinator (`academic_staff`, junior + `position=coordinator`) | `/instructor/timetable` + Staff tab | `mka@ucsc.cmb.ac.lk` | Mr. Kwame Addo |
| In-Charge (`academic_staff`, senior + `position=in_charge`) | `/instructor/timetable` + Staff + Accounts tabs | `dsc@ucsc.cmb.ac.lk` | Dr. Sarah Chen |
| Lecturer / junior staff (`academic_staff`, junior, no position) | `/instructor/timetable` | `mad@ucsc.cmb.ac.lk`, `mab@ucsc.cmb.ac.lk`, `mat@ucsc.cmb.ac.lk`, `mko@ucsc.cmb.ac.lk`, `mem@ucsc.cmb.ac.lk`, `meq@ucsc.cmb.ac.lk`, `mna@ucsc.cmb.ac.lk`, `tmf@ucsc.cmb.ac.lk`, `myd@ucsc.cmb.ac.lk`, `myb@ucsc.cmb.ac.lk` | Ato Baidoo, Adom Boateng, Atta Tetteh, Kojo Amoah, Efua Mensah, Esi Quaye, Nana Ama, Thilini Fernando, Yaa Darko, Yaw Bediako |
| Senior Lecturer (`academic_staff`, senior, no position) | `/instructor/timetable` | `dad@ucsc.cmb.ac.lk`, `dep@ucsc.cmb.ac.lk`, `dfa@ucsc.cmb.ac.lk`, `dka@ucsc.cmb.ac.lk`, `dlo@ucsc.cmb.ac.lk`, `dlw@ucsc.cmb.ac.lk`, `pdn@ucsc.cmb.ac.lk`, `pjo@ucsc.cmb.ac.lk`, `pka@ucsc.cmb.ac.lk`, `prm@ucsc.cmb.ac.lk` | Amara Diallo, Elena Petrov, Fatima Ahmed, Kofi Anning, Linda Osei, Liu Wei, David Nkrumah, James Osei, Kweku Asante, Richard Mensah |

## Notes

- These accounts are intended for local development and demo work.
- New self-registrations are not included here; they remain pending until approved by a Coordinator or In-Charge.
- To re-create the seeded data, run the database migration with the seed option as described in [database/README.md](database/README.md).
