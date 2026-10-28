<?php
/**
 * CASPA School OS – Teacher: Today's Classes
 * pages/teacher/timetable.php
 */
declare(strict_types=1);
if (!defined('ROOT')) { define('ROOT', dirname(__DIR__, 2)); }
require_once ROOT . '/includes/auth.php';

session_boot();
require_login();
require_role('teacher');

$pageTitle  = "Today's Classes";
$activeMenu = "Today's Classes";
require_once ROOT . '/includes/layout_header.php';
?>

<div class="container-fluid py-4">
  <h4 class="fw-bold mb-4"><i class="bi bi-clock me-2 text-primary"></i>Today's Classes</h4>
  <div id="ttContent"><div class="text-center py-5"><div class="spinner-border text-primary"></div></div></div>
</div>

<script>
const APP_URL = '<?= APP_URL ?>';
const DAYS = ['', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
const NOW_DOW = <?= (int)date('N') ?>;

fetch(`${APP_URL}/api/timetable/today.php`)
  .then(r => r.json())
  .then(json => {
    const el = document.getElementById('ttContent');
    if (!json.ok) { el.innerHTML = `<div class="alert alert-danger">${json.error}</div>`; return; }
    const { slots, next_class, day_of_week } = json.data;

    if (day_of_week > 5) {
      el.innerHTML = '<div class="alert alert-info"><i class="bi bi-emoji-smile me-2"></i>It\'s the weekend — enjoy your time off!</div>';
      return;
    }

    if (!slots.length) {
      el.innerHTML = '<div class="alert alert-secondary"><i class="bi bi-calendar-x me-2"></i>No classes scheduled for today.</div>';
      return;
    }

    let html = `<p class="text-muted mb-3">Today is <strong>${DAYS[day_of_week]}</strong></p>`;

    if (next_class) {
      html += `
        <div class="caspa-card p-3 mb-4 border-primary border-start border-4">
          <div class="small text-primary fw-semibold mb-1"><i class="bi bi-alarm me-1"></i>NEXT CLASS</div>
          <h5 class="mb-0">${escHtml(next_class.subject_name || '—')}</h5>
          <div class="text-muted small">${escHtml(next_class.class_name)} ${escHtml(next_class.section)} &bull; ${next_class.start_time.substring(0,5)}–${next_class.end_time.substring(0,5)}</div>
        </div>`;
    }

    html += '<div class="row g-3">';
    slots.forEach(s => {
      if (s.is_break) {
        html += `<div class="col-md-6"><div class="caspa-card p-3 bg-light text-muted"><i class="bi bi-cup-hot me-2"></i>${escHtml(s.period_label)} &bull; ${s.start_time.substring(0,5)}–${s.end_time.substring(0,5)}</div></div>`;
      } else {
        const now = new Date();
        const [h,m] = s.start_time.split(':');
        const slotTime = new Date(); slotTime.setHours(+h, +m, 0);
        const isPast = now > slotTime;
        html += `
          <div class="col-md-6">
            <div class="caspa-card p-3 ${isPast ? 'opacity-75' : ''}">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <div class="fw-semibold">${escHtml(s.subject_name || '—')}</div>
                  <div class="text-muted small">${escHtml(s.class_name)} ${escHtml(s.section)}</div>
                </div>
                <span class="badge bg-primary">${s.start_time.substring(0,5)}–${s.end_time.substring(0,5)}</span>
              </div>
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
