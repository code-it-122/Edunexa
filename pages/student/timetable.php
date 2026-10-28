<?php
/**
 * CASPA School OS – Student/Parent: Today's Timetable
 * pages/student/timetable.php
 */
declare(strict_types=1);
if (!defined('ROOT')) { define('ROOT', dirname(__DIR__, 2)); }
require_once ROOT . '/includes/auth.php';

session_boot();
require_login();
require_role(['student', 'parent']);

$pageTitle = "Today's Timetable";
require_once ROOT . '/includes/layout_header.php';
?>

<div class="container-fluid py-4">
  <h4 class="fw-bold mb-4"><i class="bi bi-calendar-day me-2 text-primary"></i>Today's Timetable</h4>
  <div id="ttContent"><div class="text-center py-5"><div class="spinner-border text-primary"></div></div></div>
</div>

<script>
const APP_URL = '<?= APP_URL ?>';
const DAYS = ['','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

fetch(`${APP_URL}/api/timetable/today.php`)
  .then(r => r.json())
  .then(json => {
    const el = document.getElementById('ttContent');
    if (!json.ok) { el.innerHTML = `<div class="alert alert-danger">${json.error}</div>`; return; }
    const { slots, next_class, day_of_week } = json.data;

    if (day_of_week > 5) {
      el.innerHTML = '<div class="alert alert-info">It\'s the weekend!</div>';
      return;
    }
    if (!slots.length) {
      el.innerHTML = '<div class="alert alert-secondary">No timetable found for your class today.</div>';
      return;
    }

    let html = `<p class="text-muted mb-3">Today is <strong>${DAYS[day_of_week]}</strong></p>`;
    if (next_class && !next_class.is_break) {
      html += `
        <div class="caspa-card p-3 mb-4 border-success border-start border-4">
          <div class="small text-success fw-semibold mb-1"><i class="bi bi-alarm me-1"></i>NEXT PERIOD</div>
          <h5 class="mb-0">${escHtml(next_class.subject_name)}</h5>
          <div class="text-muted small">${next_class.start_time.substring(0,5)} – ${next_class.end_time.substring(0,5)}</div>
        </div>`;
    }

    html += '<div class="list-group">';
    slots.forEach(s => {
      const time = `${s.start_time.substring(0,5)}–${s.end_time.substring(0,5)}`;
      if (s.is_break) {
        html += `<div class="list-group-item bg-light text-muted"><i class="bi bi-cup-hot me-2"></i>${escHtml(s.period_label)} — Break ${time}</div>`;
      } else {
        html += `
          <div class="list-group-item">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <div class="fw-semibold">${escHtml(s.subject_name || '—')}</div>
                <div class="text-muted small">${escHtml(s.teacher_name || 'TBA')}</div>
              </div>
              <span class="badge bg-secondary">${time}</span>
            </div>
          </div>`;
      }
    });
    html += '</div>';
    el.innerHTML = html;
  });

function escHtml(str) {
  return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>

<?php require_once ROOT . '/includes/layout_footer.php'; ?>
