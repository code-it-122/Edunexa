<?php
/**
 * CASPA School OS – Timetable Builder (Admin / Principal)
 * pages/admin/timetable.php
 */
declare(strict_types=1);
if (!defined('ROOT')) { define('ROOT', dirname(__DIR__, 2)); }
require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/db.php';

session_boot();
require_login();
require_role(['admin', 'principal']);

$user = current_user();
$pdo  = db();

$classes  = $pdo->query('SELECT id, name, section FROM classes ORDER BY name, section')->fetchAll();
$periods  = $pdo->query('SELECT * FROM periods ORDER BY period_number')->fetchAll();
$ay       = $pdo->query('SELECT id, label FROM academic_years WHERE is_current=1 LIMIT 1')->fetch();
$ayId     = $ay ? (int)$ay['id'] : 0;

// All class_subjects with teacher info
$allCS = $pdo->query('
    SELECT cs.id, cs.class_id, s.name AS subject_name, u.name AS teacher_name
    FROM class_subjects cs
    JOIN subjects s ON s.id=cs.subject_id
    LEFT JOIN users u ON u.id=cs.teacher_id
    ORDER BY cs.class_id, s.name
')->fetchAll();

$pageTitle = 'Timetable Builder';
require_once ROOT . '/includes/layout_header.php';
?>

<div class="container-fluid py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold mb-0"><i class="bi bi-calendar-week me-2 text-primary"></i>Timetable Builder</h4>
      <small class="text-muted"><?= htmlspecialchars($ay['label'] ?? '', ENT_QUOTES) ?></small>
    </div>
  </div>

  <!-- Controls -->
  <div class="caspa-card p-3 mb-4">
    <div class="row g-3 align-items-end">
      <div class="col-md-4">
        <label class="form-label fw-semibold">Class</label>
        <select class="form-select" id="classSelect">
          <option value="">— Select class —</option>
          <?php foreach ($classes as $c): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name'] . ' ' . $c['section'], ENT_QUOTES) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-auto">
        <button class="btn btn-caspa-primary" id="btnLoad"><i class="bi bi-arrow-clockwise me-1"></i>Load</button>
        <button class="btn btn-success ms-2" id="btnSave" disabled><i class="bi bi-floppy me-1"></i>Save Timetable</button>
      </div>
    </div>
  </div>

  <!-- Grid -->
  <div id="ttContainer" class="d-none">
    <div class="caspa-card p-0">
      <div class="table-responsive">
        <table class="table caspa-table mb-0 text-center" id="ttGrid">
          <thead>
            <tr>
              <th style="min-width:100px">Period</th>
              <th>Monday</th><th>Tuesday</th><th>Wednesday</th><th>Thursday</th><th>Friday</th>
            </tr>
          </thead>
          <tbody id="ttBody"></tbody>
        </table>
      </div>
    </div>
  </div>

  <div id="ttEmpty" class="text-center text-muted py-5">
    <i class="bi bi-calendar-week display-4"></i>
    <p class="mt-2">Select a class and click Load.</p>
  </div>

  <!-- Toast -->
  <div class="position-fixed bottom-0 end-0 p-3" style="z-index:9999">
    <div id="toast" class="toast align-items-center text-white border-0" role="alert" aria-live="assertive">
      <div class="d-flex">
        <div class="toast-body" id="toastMsg"></div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    </div>
  </div>
</div>

<script>
const APP_URL  = '<?= APP_URL ?>';
const AY_ID    = <?= $ayId ?>;
const DAYS     = ['', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
const PERIODS  = <?= json_encode($periods, JSON_HEX_TAG) ?>;
const ALL_CS   = <?= json_encode($allCS,   JSON_HEX_TAG) ?>;
const CLASSES  = <?= json_encode($classes, JSON_HEX_TAG) ?>;

// timetable data: key = "dow-periodId" => class_subject_id|null
let ttData = {};

document.getElementById('btnLoad').addEventListener('click', loadTimetable);
document.getElementById('btnSave').addEventListener('click', saveTimetable);

async function loadTimetable() {
  const classId = document.getElementById('classSelect').value;
  if (!classId) { showToast('Select a class first.', 'danger'); return; }

  const res = await fetch(`${APP_URL}/api/timetable/slots.php?class_id=${classId}&academic_year_id=${AY_ID}`);
  const json = await res.json();
  if (!json.ok) { showToast(json.error, 'danger'); return; }

  // Build lookup
  ttData = {};
  json.data.forEach(s => {
    ttData[`${s.day_of_week}-${s.period_id}`] = s.class_subject_id || '';
  });

  renderGrid(parseInt(classId));
  document.getElementById('ttContainer').classList.remove('d-none');
  document.getElementById('ttEmpty').classList.add('d-none');
  document.getElementById('btnSave').disabled = false;
}

function renderGrid(classId) {
  const csOptions = ALL_CS.filter(cs => cs.class_id == classId);
  const tbody = document.getElementById('ttBody');
  tbody.innerHTML = '';

  PERIODS.forEach(p => {
    const tr = document.createElement('tr');
    // Period label
    const tdLabel = document.createElement('td');
    tdLabel.classList.add('fw-semibold', 'bg-light');
    tdLabel.innerHTML = `<div class="small">${p.label}</div><div class="text-muted" style="font-size:.7rem">${p.start_time.substring(0,5)}–${p.end_time.substring(0,5)}</div>`;
    tr.appendChild(tdLabel);

    for (let dow = 1; dow <= 5; dow++) {
      const td = document.createElement('td');
      if (p.is_break) {
        td.classList.add('bg-light', 'text-muted');
        td.innerHTML = '<small>Break</small>';
      } else {
        const key = `${dow}-${p.id}`;
        const curVal = ttData[key] || '';
        const sel = document.createElement('select');
        sel.className = 'form-select form-select-sm';
        sel.dataset.key = key;
        sel.innerHTML = '<option value="">—</option>' +
          csOptions.map(cs =>
            `<option value="${cs.id}" ${curVal == cs.id ? 'selected' : ''}>${cs.subject_name}${cs.teacher_name ? ' (' + cs.teacher_name + ')' : ''}</option>`
          ).join('');
        td.appendChild(sel);
      }
      tr.appendChild(td);
    }
    tbody.appendChild(tr);
  });
}

async function saveTimetable() {
  const classId = document.getElementById('classSelect').value;
  const slots = [];
  document.querySelectorAll('#ttBody select').forEach(sel => {
    const [dow, periodId] = sel.dataset.key.split('-');
    slots.push({
      day_of_week: parseInt(dow),
      period_id:   parseInt(periodId),
      class_subject_id: sel.value ? parseInt(sel.value) : null,
    });
  });

  document.getElementById('btnSave').disabled = true;
  try {
    const res = await fetch(`${APP_URL}/api/timetable/slots.php`, {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ action:'save', class_id: parseInt(classId), academic_year_id: AY_ID, slots }),
    });
    const json = await res.json();
    if (json.ok) {
      showToast(`Saved ${json.data.saved} slots.`, 'success');
    } else {
      showToast(json.error, 'danger');
    }
  } finally {
    document.getElementById('btnSave').disabled = false;
  }
}

function showToast(msg, type) {
  const t = document.getElementById('toast');
  const m = document.getElementById('toastMsg');
  t.className = `toast align-items-center text-white border-0 bg-${type}`;
  m.textContent = msg;
  bootstrap.Toast.getOrCreateInstance(t).show();
}
</script>

<?php require_once ROOT . '/includes/layout_footer.php'; ?>
