// Instructor Timetable JS
document.addEventListener('DOMContentLoaded', () => {
    console.log('Instructor timetable JS loaded.');
    
    const legendHint = document.getElementById('legendHint');
    const detailModal = document.getElementById('detailsModal');
    
    // Close detail modal
    document.querySelectorAll('#closeDetailsModal, #closeDetailsBtn2').forEach(btn => {
        btn.addEventListener('click', () => {
            if (detailModal) detailModal.hidden = true;
        });
    });

    // Delegate clicks for timetable blocks
    document.querySelector('.tt-grid')?.addEventListener('click', (e) => {
        const block = e.target.closest('.tt-block');
        if (!block) return;
        
        const code = block.dataset.code;
        const title = block.dataset.title;
        const location = block.dataset.location;
        const day = block.dataset.day;
        const start = block.dataset.start;
        const type = block.dataset.type;
        const duration = parseInt(block.dataset.duration || '1', 10);
        
        let endTimeStr = '...';
        const match = start.match(/^(\d+)\s+(AM|PM)$/);
        if (match) {
            let h = parseInt(match[1], 10);
            let isPm = match[2] === 'PM';
            if (h === 12 && !isPm) { isPm = false; h = 0; }
            else if (h !== 12 && isPm) { h += 12; }
            let endH = h + duration;
            let endPm = endH >= 12;
            if (endH > 12) endH -= 12;
            if (endH === 0) endH = 12;
            endTimeStr = `${endH} ${endPm ? 'PM' : 'AM'}`;
        }

        if (detailModal) {
            document.getElementById('detailsCode').textContent = code;
            document.getElementById('detailsTitle').textContent = title;
            document.getElementById('detailsLocation').textContent = location || 'TBA';
            document.getElementById('detailsWhen').textContent = `${day}s, ${start} - ${endTimeStr}`;
            document.getElementById('detailsType').textContent = (type || 'Lecture').charAt(0).toUpperCase() + (type || 'lecture').slice(1);
            
            detailModal.hidden = false;
        }
    });
});
