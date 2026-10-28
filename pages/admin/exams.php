<?php
/**
 * CASPA School OS – Exam Management (Admin / Principal)
 * pages/admin/exams.php
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

$ayRow    = $pdo->query('SELECT id, label FROM academic_years WHERE is_current=1 LIMIT 1')->fetch();
$ayId     = $ayRow ? (int)$ayRow['id'] : 0;
$allAY    = $pdo->query('SELECT id, label FROM academic_years ORDER BY starts_on DESC')->fetchAll();
$allCS    = $pdo->query('
    SELECT cs.id, c.name AS class_name, c.section, s.name AS subject_name
    FROM class_subjects cs
    JOIN classes c ON c.id=cs.class_id
    JOIN subjects s ON s.id=cs.subject_id
    ORDER BY c.name, c.section, s.name
')->fetchAll();

$pageTitle  = 'Exam Management';
$activeMenu = 'Exams';
require_once ROOT . '/includes/layout_header.php';
?>

<div class="container-fluid py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="bi bi-file-earmark-text me-2 text-primary"></i>Exam Management</h4>
    <button class="btn btn-caspa-primary" data-bs-toggle="modal" data-bs-target="#createExamModal">
      <i class="bi bi-plus-lg me-1"></i>New Exam
    </button>
  </div>

  <!-- Exam list -->
  <div id="examList"><div class="text-center py-4"><div class="spinner-border text-primary"></div></div></div>
</div>

<!-- Create Exam Modal -->
<div class="modal fade" id="createExamModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Create Exam</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Exam Name *</label>
          <input type="text" class="form-control" id="examName" placeholder="e.g. Mid-Term 2025">
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Term</label>
            <input type="text" class="form-control" id="examTerm" value="Term 1">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Type</label>
            <select class="form-select" id="examType">
              <option value="midterm">Mid-term</option>
              <option value="final">Final</option>
              <option value="unit">Unit Test</option>
              <option value="other">Other</option>
            </select>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Academic Year</label>
          <select class="form-select" id="examAY">
            <?php foreach ($allAY as $ay): ?>
              <option value="<?= $ay['id'] ?>" <?= $ay['id']==$ayId?'selected':'' ?>><?= htmlspecialchars($ay['label'],ENT_QUOTES) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div id="createErr" class="alert alert-danger d-none"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-caspa-primary" id="btnCreateExam">Create</button>
      </div>
    </div>
  </div>
</div>

<!-- Subjects Modal -->
<div class="modal fade" id="subjectsModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="subjectModalTitle">Add Subjects to Exam</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-2 mb-3 align-items-end">
          <div class="col-md-5">
            <label class="form-label">Class – Subject</label>
            <select class="form-select" id="addCS">
              <option value="">— Select —</option>
              <?php foreach ($allCS as $cs): ?>
                <option value="<?= $cs['id'] ?>"><?= htmlspecialchars($cs['class_name'].' '.$cs['section'].' – '.$cs['subject_name'],ENT_QUOTES) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Max Marks</label>
            <input type="number" class="form-control" id="addMaxMarks" value="100" min="1">
          </div>
          <div class="col-md-3">
            <label class="form-label">Exam Date</label>
            <input type="date" class="form-control" id="addExamDate">
          </div>
          <div class="col-md-1">
            <button class="btn btn-caspa-primary w-100" id="btnAddSubject"><i class="bi bi-plus-lg"></i></button>
          </div>
        </div>
        <div id="subjectList"></div>
      </div>
    </div>
  </div>
</div>

<!-- Toast -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:9999">
  <div id="toast" class="toast align-items-center text-white border-0" role="alert">
    <div class="d-flex"><div class="toast-body" id="toastMsg"></div>
    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>
  </div>
</div>

<script>
const APP_URL = '<?= APP_URL ?>';
let activeExamId = null;

loadExams();

async function loadExams() {
  const res  = await fetch(`${APP_URL}/api/exams/index.php`);
  const json = await res.json();
  const el   = document.getElementById('examList');
  if (!json.ok) { el.innerHTML = `<div class="alert alert-danger">${json.error}</div>`; return; }

  if (!json.data.length) {
    el.innerHTML = '<div class="caspa-card p-4 text-center text-muted"><i class="bi bi-file-earmark-x display-4"></i><p class="mt-2">No exams yet.</p></div>';
    return;
  }

  let html = '<div class="row g-3">';
  json.data.forEach(e => {
    const badge = e.is_published
      ? '<span class="badge bg-success">Published</span>'
      : '<span class="badge bg-secondary">Draft</span>';
    html += `
      <div class="col-md-6 col-xl-4">
        <div class="caspa-card p-3 h-100">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <h6 class="fw-bold mb-0">${escHtml(e.name)}</h6>${badge}
          </div>
          <div class="text-muted small mb-3">${escHtml(e.term)} &bull; ${escHtml(e.exam_type)} &bull; ${escHtml(e.academic_year)}</div>
          <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-primary" onclick="openSubjects(${e.id}, '${escHtml(e.name).replace(/'/g,"\\'")}')">
              <i class="bi bi-list-ul me-1"></i>Subjects
            </button>
            <a href="${APP_URL}/pages/admin/marks_entry.php?exam_id=${e.id}" class="btn btn-sm btn-outline-secondary">
              <i class="bi bi-pencil-square me-1"></i>Marks
            </a>
            <button class="btn btn-sm ${e.is_published ? 'btn-warning' : 'btn-success'}" onclick="togglePublish(${e.id}, ${e.is_published})">
              ${e.is_published ? '<i class="bi bi-eye-slash me-1"></i>Unpublish' : '<i class="bi bi-eye me-1"></i>Publish'}
            </button>
          </div>
        </div>
      </div>`;
  });
  html += '</div>';
  el.innerHTML = html;
}

document.getElementById('btnCreateExam').addEventListener('click', async () => {
  const name = document.getElementById('examName').value.trim();
  const term = document.getElementById('examTerm').value.trim();
  const type = document.getElementById('examType').value;
  const ayId = document.getElementById('examAY').value;
  const errEl = document.getElementById('createErr');

  if (!name) { errEl.textContent = 'Exam name is required.'; errEl.classList.remove('d-none'); return; }
  errEl.classList.add('d-none');

  const res  = await fetch(`${APP_URL}/api/exams/index.php`, {
    method: 'POST', headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ action:'create', name, term, exam_type:type, academic_year_id: parseInt(ayId) }),
  });
  const json = await res.json();
  if (json.ok) {
    bootstrap.Modal.getInstance(document.getElementById('createExamModal')).hide();
    showToast('Exam created!', 'success');
    loadExams();
  } else {
    errEl.textContent = json.error; errEl.classList.remove('d-none');
  }
});

async function togglePublish(examId, current) {
  const res  = await fetch(`${APP_URL}/api/exams/index.php`, {
    method: 'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({ action:'publish', exam_id:examId, is_published: current ? 0 : 1 }),
  });
  const json = await res.json();
  if (json.ok) { showToast(`Exam ${json.data.is_published ? 'published' : 'unpublished'}.`, 'success'); loadExams(); }
  else showToast(json.error, 'danger');
}

async function openSubjects(examId, name) {
  activeExamId = examId;
  document.getElementById('subjectModalTitle').textContent = 'Subjects: ' + name;
  await loadSubjects();
  bootstrap.Modal.getOrCreateInstance(document.getElementById('subjectsModal')).show();
}

async function loadSubjects() {
  const res  = await fetch(`${APP_URL}/api/exams/subjects.php?exam_id=${activeExamId}`);
  const json = await res.json();
  const el   = document.getElementById('subjectList');
  if (!json.ok) { el.innerHTML = `<div class="alert alert-danger">${json.error}</div>`; return; }
  if (!json.data.length) { el.innerHTML = '<p class="text-muted">No subjects yet. Add one above.</p>'; return; }
  let html = '<ul class="list-group">';
  json.data.forEach(s => {
    html += `
      <li class="list-group-item d-flex justify-content-between align-items-center">
        <div>
          <div class="fw-semibold">${escHtml(s.class_name)} ${escHtml(s.section)} – ${escHtml(s.subject_name)}</div>
          <small class="text-muted">Max: ${s.max_marks} | Date: ${s.exam_date}</small>
        </div>
        <button class="btn btn-sm btn-outline-danger" onclick="removeSubject(${s.id})"><i class="bi bi-trash"></i></button>
      </li>`;
  });
  html += '</ul>';
  el.innerHTML = html;
}

document.getElementById('btnAddSubject').addEventListener('click', async () => {
  const csId     = document.getElementById('addCS').value;
  const maxMarks = document.getElementById('addMaxMarks').value;
  const examDate = document.getElementById('addExamDate').value;
  if (!csId || !examDate) { showToast('Select subject and exam date.', 'danger'); return; }

  const res  = await fetch(`${APP_URL}/api/exams/subjects.php`, {
    method: 'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({ action:'add', exam_id:activeExamId, class_subject_id:parseInt(csId), max_marks:parseFloat(maxMarks), exam_date:examDate }),
  });
  const json = await res.json();
  if (json.ok) { showToast('Subject added.', 'success'); loadSubjects(); }
  else showToast(json.error, 'danger');
});

async function removeSubject(esId) {
  if (!confirm('Remove this subject?')) return;
  const res  = await fetch(`${APP_URL}/api/exams/subjects.php`, {
    method: 'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({ action:'remove', exam_subject_id:esId }),
  });
  const json = await res.json();
  if (json.ok) loadSubjects();
  else showToast(json.error, 'danger');
}

function showToast(msg, type) {
  const t = document.getElementById('toast');
  t.className = `toast align-items-center text-white border-0 bg-${type}`;
  document.getElementById('toastMsg').textContent = msg;
  bootstrap.Toast.getOrCreateInstance(t).show();
}
function escHtml(str) {
  return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>

<?php require_once ROOT . '/includes/layout_footer.php'; ?>
