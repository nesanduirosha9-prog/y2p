// evaluations.js — Interactive star rating buttons, submission feedback, and review modal.

document.addEventListener('DOMContentLoaded', function () {
    // ---- Review Dashboard: Details Drawer & Search ----
    const searchInput = document.getElementById('evalSearchInput');
    const table = document.getElementById('evaluationsTable');
    const drawer = document.getElementById('evalDetailsDrawer');
    const backdrop = document.getElementById('evalDrawerBackdrop');
    const closeDrawerBtn = document.getElementById('closeEvalDrawerBtn');
    const drawerStaffName = document.getElementById('drawerStaffName');
    const drawerCourseInfo = document.getElementById('drawerCourseInfo');
    const drawerContent = document.getElementById('drawerEvalContent');

    function closeDrawer() {
        if (drawer) drawer.classList.remove('open');
        if (backdrop) backdrop.classList.remove('active');
    }

    function openDrawer() {
        if (drawer) drawer.classList.add('open');
        if (backdrop) backdrop.classList.add('active');
    }

    if (searchInput && table) {
        searchInput.addEventListener('input', function () {
            const q = this.value.trim().toLowerCase();
            const rows = table.querySelectorAll('tbody tr.eval-row');
            rows.forEach(row => {
                const text = row.getAttribute('data-search') || '';
                row.style.display = text.includes(q) ? '' : 'none';
            });
        });
    }

    if (table && drawer) {
        table.addEventListener('click', function (e) {
            const btn = e.target.closest('.view-eval-btn');
            if (!btn) return;

            try {
                const data = JSON.parse(btn.getAttribute('data-eval'));
                if (drawerStaffName) drawerStaffName.textContent = `${data.staff_name} (${data.staff_code})`;
                if (drawerCourseInfo) drawerCourseInfo.textContent = `${data.course_code} — ${data.course_name} · Evaluated by ${data.evaluator_name} on ${data.date}`;

                if (drawerContent) {
                    drawerContent.innerHTML = `
                        <div style="display:flex; justify-content:space-between; align-items:center; background:#f8fafc; padding:14px; border-radius:10px; border:1px solid #e2e8f0;">
                            <div>
                                <span style="font-size:11px; font-weight:700; text-transform:uppercase; color:#64748b; letter-spacing:0.04em;">Overall Rating</span>
                                <p style="margin:2px 0 0; font-size:12px; color:#0f1c2e;">Calculated from 5 metrics</p>
                            </div>
                            <span class="eval-score-pill score-high" style="font-size:15px; padding:6px 12px;">
                                <i class="fa-solid fa-star"></i> ${data.overall_score} <span style="font-size:12px; font-weight:500; color:#059669;">/ 5.0</span>
                            </span>
                        </div>

                        <div>
                            <h4 style="font-size:13px; font-weight:700; color:#1e293b; margin:0 0 10px 0;">Performance Criteria Breakdown</h4>
                            <div style="display:flex; flex-direction:column; gap:8px;">
                                <div style="display:flex; justify-content:space-between; align-items:center; background:#fff; border:1px solid #e2e8f0; padding:10px 14px; border-radius:8px;">
                                    <span style="font-size:12px; color:#334155; font-weight:600;">1. Punctuality & Attendance</span>
                                    <span style="font-size:12px; font-weight:700; color:#0f1c2e;">${data.punctuality} / 5</span>
                                </div>
                                <div style="display:flex; justify-content:space-between; align-items:center; background:#fff; border:1px solid #e2e8f0; padding:10px 14px; border-radius:8px;">
                                    <span style="font-size:12px; color:#334155; font-weight:600;">2. Technical Competence</span>
                                    <span style="font-size:12px; font-weight:700; color:#0f1c2e;">${data.technical} / 5</span>
                                </div>
                                <div style="display:flex; justify-content:space-between; align-items:center; background:#fff; border:1px solid #e2e8f0; padding:10px 14px; border-radius:8px;">
                                    <span style="font-size:12px; color:#334155; font-weight:600;">3. Student Guidance & Support</span>
                                    <span style="font-size:12px; font-weight:700; color:#0f1c2e;">${data.support} / 5</span>
                                </div>
                                <div style="display:flex; justify-content:space-between; align-items:center; background:#fff; border:1px solid #e2e8f0; padding:10px 14px; border-radius:8px;">
                                    <span style="font-size:12px; color:#334155; font-weight:600;">4. Duty Commitment</span>
                                    <span style="font-size:12px; font-weight:700; color:#0f1c2e;">${data.commitment} / 5</span>
                                </div>
                                <div style="display:flex; justify-content:space-between; align-items:center; background:#fff; border:1px solid #e2e8f0; padding:10px 14px; border-radius:8px;">
                                    <span style="font-size:12px; color:#334155; font-weight:600;">5. Marking Accuracy & Timeliness</span>
                                    <span style="font-size:12px; font-weight:700; color:#0f1c2e;">${data.marking} / 5</span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h4 style="font-size:13px; font-weight:700; color:#1e293b; margin:0 0 6px 0;"><i class="fa-solid fa-thumbs-up" style="color:#059669; margin-right:4px;"></i> Key Strengths</h4>
                            <p style="font-size:13px; color:#334155; background:#f0fdf4; border:1px solid #bbf7d0; padding:12px; border-radius:8px; margin:0; line-height:1.5;">${data.strengths || 'None recorded'}</p>
                        </div>

                        <div>
                            <h4 style="font-size:13px; font-weight:700; color:#1e293b; margin:0 0 6px 0;"><i class="fa-solid fa-lightbulb" style="color:#ca8a04; margin-right:4px;"></i> Areas for Improvement</h4>
                            <p style="font-size:13px; color:#334155; background:#fefce8; border:1px solid #fef08a; padding:12px; border-radius:8px; margin:0; line-height:1.5;">${data.improvements || 'None recorded'}</p>
                        </div>

                        <div>
                            <h4 style="font-size:13px; font-weight:700; color:#1e293b; margin:0 0 6px 0;"><i class="fa-solid fa-comment-dots" style="color:#2563eb; margin-right:4px;"></i> Lecturer Appraisal</h4>
                            <p style="font-size:13px; color:#1e293b; background:#f8fafc; border:1px solid #e2e8f0; padding:12px; border-radius:8px; margin:0; line-height:1.5;">${data.comments}</p>
                        </div>
                    `;
                }

                openDrawer();
            } catch (err) {
                console.error(err);
            }
        });
    }

    if (closeDrawerBtn) closeDrawerBtn.addEventListener('click', closeDrawer);
    if (backdrop) backdrop.addEventListener('click', closeDrawer);
});

