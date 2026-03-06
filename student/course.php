<?php
// htdocs/student/course.php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Core/bootstrap.php';

use App\Core\DB;

// ✅ Student session guard
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$role   = (string)($_SESSION['role'] ?? '');

// Build next (relative)
$next = $_SERVER['REQUEST_URI'] ?? '';
$next = ltrim((string)$next, '/');
if ($next === '') $next = 'student/course.php';

if ($userId <= 0 || $role !== 'student') {
  header('Location: ' . BASE_URL . '/auth/login.php?next=' . urlencode($next));
  exit;
}

// ✅ Load user (minimal)
$pdo = DB::pdo();
$stmt = $pdo->prepare("SELECT id, name, email, role, is_active FROM users WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || (int)($user['is_active'] ?? 0) !== 1 || (string)($user['role'] ?? '') !== 'student') {
  $_SESSION = [];
  if (session_status() === PHP_SESSION_ACTIVE) session_destroy();
  header('Location: ' . BASE_URL . '/auth/login.php?next=' . urlencode($next));
  exit;
}

$userEmail = (string)($user['email'] ?? '');

// ✅ Optional subject: physics | math | chemistry
$subject = $_GET['subject'] ?? '';
if (!is_string($subject)) $subject = '';
$subject = strtolower(trim($subject));

$subjectTitle = '';
if ($subject === 'physics') $subjectTitle = 'Physics';
if ($subject === 'math') $subjectTitle = 'Mathematics';
if ($subject === 'chemistry') $subjectTitle = 'Chemistry';

// For JS
$baseUrlJs = (defined('BASE_URL') ? BASE_URL : '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo htmlspecialchars($subjectTitle ? ($subjectTitle . " | DemoticEdu") : "My Courses | DemoticEdu"); ?></title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

  <style>
    :root{
      --blue:#20537a; --yellow:#F8B030;
      --ink:#0b1220; --muted:rgba(11,18,32,.66);
      --bg:#f6f7fb; --border:rgba(15,23,42,.10);
      --shadowSoft:0 10px 22px rgba(2,6,23,.06);
      --r22:22px;
    }
    *{box-sizing:border-box}
    html,body{margin:0;padding:0}
    body{
      font-family:Inter,"Hind Siliguri",system-ui,sans-serif;
      background:var(--bg);
      color:var(--ink);
      overflow-x:hidden;
    }
    body::before{
      content:""; position:fixed; inset:0; z-index:-2;
      background:
        radial-gradient(900px 360px at 12% 6%, rgba(248,176,48,.12), transparent 60%),
        radial-gradient(900px 360px at 88% 0%, rgba(32,83,122,.10), transparent 60%),
        linear-gradient(#f8f9fd,#f6f7fb);
    }
    .layout{min-height:100vh;display:grid;grid-template-columns:260px 1fr}
    .side{padding:18px;border-right:1px solid var(--border);background:rgba(255,255,255,.70);backdrop-filter:blur(10px)}
    .brand{display:flex;gap:10px;align-items:center;padding:10px;border-radius:18px;border:1px solid rgba(15,23,42,.08);background:#fff;box-shadow:var(--shadowSoft)}
    .dot{width:38px;height:38px;border-radius:14px;display:grid;place-items:center;background:rgba(248,176,48,.20);font-weight:1000;color:#1b1600;border:1px solid rgba(248,176,48,.35)}
    .nav{margin-top:14px;display:flex;flex-direction:column;gap:8px}
    .nav a{text-decoration:none;color:rgba(11,18,32,.86);font-weight:900;font-size:13.5px;padding:12px;border-radius:14px;border:1px solid rgba(15,23,42,.08);background:#fff}
    .nav a.active{background:rgba(32,83,122,.08);border-color:rgba(32,83,122,.20)}
    .main{padding:18px 18px 28px}
    .topbar{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px;border:1px solid rgba(15,23,42,.08);border-radius:var(--r22);background:rgba(255,255,255,.78);backdrop-filter:blur(10px);box-shadow:var(--shadowSoft)}
    .topbar h1{margin:0;font-size:18px}
    .topbar p{margin:2px 0 0;font-size:12.8px;color:rgba(11,18,32,.62);font-weight:700}
    .actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap;justify-content:flex-end}
    .pill{border:1px solid rgba(15,23,42,.10);background:rgba(15,23,42,.04);color:rgba(11,18,32,.72);padding:10px 12px;border-radius:999px;font-size:12.5px;font-weight:900;max-width:320px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .btn{display:inline-flex;align-items:center;justify-content:center;text-decoration:none;padding:10px 12px;border-radius:14px;font-weight:1000;font-size:13px;border:1px solid rgba(248,176,48,.35);background:linear-gradient(135deg,var(--yellow),#ffd58b);color:#1b1600;box-shadow:0 18px 44px rgba(248,176,48,.18)}
    .content{margin-top:14px;display:grid;grid-template-columns:360px 1fr;gap:12px;align-items:start}
    .panel{background:rgba(255,255,255,.92);border:1px solid var(--border);border-radius:var(--r22);box-shadow:var(--shadowSoft);padding:14px;backdrop-filter:blur(10px)}
    .panel h2{margin:0 0 10px;font-size:14px}
    .courseItem{border:1px solid rgba(15,23,42,.10);border-radius:16px;padding:12px;background:#fff;cursor:pointer;transition:.12s;margin-bottom:10px}
    .courseItem:hover{transform:translateY(-1px)}
    .courseItem.active{border-color:rgba(32,83,122,.22);background:rgba(32,83,122,.06)}
    .courseItem b{display:block}
    .courseItem span{color:var(--muted);font-weight:700;font-size:12.8px}
    .lessonHead{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;margin-bottom:10px}
    .lessonHead h2{margin:0;font-size:14px}
    .small{color:var(--muted);font-weight:700;font-size:12.8px}
    .lesson{border:1px solid rgba(15,23,42,.10);border-radius:16px;background:#fff;padding:12px;display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:10px}
    .lesson b{display:block}
    .lesson span{display:block;color:var(--muted);font-weight:700;font-size:12.8px}
    .open{white-space:nowrap;text-decoration:none;padding:10px 12px;border-radius:14px;border:1px solid rgba(32,83,122,.18);background:rgba(32,83,122,.06);color:var(--blue);font-weight:900}
    .badge{font-size:12px;font-weight:900;padding:6px 10px;border-radius:999px;border:1px solid rgba(15,23,42,.10);background:rgba(15,23,42,.04);color:rgba(11,18,32,.72)}
    .badgeOk{border-color:rgba(248,176,48,.40);background:rgba(248,176,48,.18);color:#1b1600}
    .prog{margin-top:8px}
    .bar{margin-top:8px;height:10px;border-radius:999px;border:1px solid rgba(15,23,42,.10);background:rgba(15,23,42,.04);overflow:hidden}
    .fill{height:100%;border-radius:999px;background:linear-gradient(90deg, rgba(32,83,122,.95), rgba(248,176,48,.95));width:0%}
    @media(max-width:980px){.layout{grid-template-columns:1fr}.side{border-right:0;border-bottom:1px solid var(--border)}.content{grid-template-columns:1fr}}
  </style>
</head>

<body>
  <div class="layout">
    <aside class="side">
      <div class="brand">
        <div class="dot">DE</div>
        <div>
          <b>DemoticEdu</b><br>
          <span style="font-size:12.5px;color:rgba(11,18,32,.66);font-weight:700">Student Panel</span>
        </div>
      </div>

      <nav class="nav">
        <a href="<?php echo htmlspecialchars(BASE_URL . '/student/index.php'); ?>">Dashboard</a>
        <a class="active" href="<?php echo htmlspecialchars(BASE_URL . '/student/course.php'); ?>">My Courses</a>
      </nav>
    </aside>

    <main class="main">
      <div class="topbar">
        <div>
          <h1><?php echo htmlspecialchars($subjectTitle ? ($subjectTitle . " — Materials") : "My Courses"); ?></h1>
          <p>Select a course to view lessons.</p>
        </div>
        <div class="actions">
          <div class="pill"><?php echo htmlspecialchars($userEmail ? ("Logged in: " . $userEmail) : "Logged in"); ?></div>
          <a class="btn" href="<?php echo htmlspecialchars(BASE_URL . '/auth/logout.php'); ?>">Logout</a>
        </div>
      </div>

      <div class="content">
        <section class="panel" aria-label="Course list">
          <h2>Courses</h2>
          <div id="courseList"></div>
        </section>

        <section class="panel" aria-label="Lesson list">
          <div class="lessonHead">
            <div>
              <h2 id="lessonTitle">Lessons</h2>
              <div class="small" id="lessonHint">Choose a course to see lessons</div>

              <div class="prog" id="progressWrap" style="display:none;">
                <div class="small" id="progressText">Progress: 0%</div>
                <div class="bar"><div class="fill" id="progressFill"></div></div>
              </div>
            </div>
            <a id="startBtn" class="btn" href="#" style="display:none">Start</a>
            <a id="continueBtn" class="open" href="#" style="display:none">Continue</a>
          </div>

          <div id="lessonList"></div>
        </section>
      </div>
    </main>
  </div>

<script>
const BASE_URL = <?php echo json_encode($baseUrlJs, JSON_UNESCAPED_SLASHES); ?>;

// ---------- helpers ----------
function qs(name){
  return new URLSearchParams(window.location.search).get(name) || "";
}
function toStr(x){ return String(x ?? "").trim(); }

// IMPORTANT: progress API uses course.slug + lesson.slug
function courseKey(c){
  const s = toStr(c && c.slug);
  return s ? s : toStr(c && c.id);
}
function lessonKey(l){
  return toStr(l && l.id);
}

// ---------- localStorage fallback ----------
function keyLast(courseSlug){ return "lastLesson_" + courseSlug; }
function keyCompleted(courseSlug){ return "completed_" + courseSlug; }

function safeParseArray(raw){
  try{ const v = JSON.parse(raw); return Array.isArray(v) ? v : []; }
  catch(e){ return []; }
}
function uniqStrings(arr){
  const out = [];
  const seen = new Set();
  for(const x of (arr || [])){
    const s = toStr(x);
    if(!s) continue;
    if(seen.has(s)) continue;
    seen.add(s);
    out.push(s);
  }
  return out;
}
function getCompletedLS(courseSlug){
  const raw = localStorage.getItem(keyCompleted(courseSlug)) || "[]";
  return uniqStrings(safeParseArray(raw));
}
function getLastLessonLS(courseSlug){
  const s = toStr(localStorage.getItem(keyLast(courseSlug)));
  return s ? s : "";
}
function clampPct(p){
  const n = Number.isFinite(p) ? p : 0;
  return Math.max(0, Math.min(100, Math.round(n)));
}

// ---------- DB progress ----------
async function loadProgressDB(courseSlug){
  const res = await fetch(BASE_URL + "/api/progress.php?course=" + encodeURIComponent(courseSlug), { cache: "no-store" });
  if(!res.ok) throw new Error("progress http " + res.status);
  const data = await res.json();
  const completed = Array.isArray(data.completed) ? uniqStrings(data.completed) : [];
  const lastLesson = toStr(data.lastLesson || "");
  return { completed, lastLesson };
}

// ---------- Courses API ----------
async function loadCoursesApi(){
  const res = await fetch(BASE_URL + "/api/courses.php", { cache: "no-store" });
  if(!res.ok) throw new Error("Failed to load /api/courses.php");
  const data = await res.json();
  if(!data || !Array.isArray(data.courses)) throw new Error("Invalid courses response");
  return data.courses;
}

const courseListEl = document.getElementById("courseList");
const lessonListEl = document.getElementById("lessonList");
const lessonTitleEl = document.getElementById("lessonTitle");
const lessonHintEl = document.getElementById("lessonHint");

function renderCourses(courses, selectedCourseSlug){
  courseListEl.innerHTML = "";
  courses.forEach(c=>{
    const cSlug = courseKey(c);
    const div = document.createElement("div");
    div.className = "courseItem" + (cSlug === selectedCourseSlug ? " active" : "");
    div.innerHTML = `<b>${(c.title || cSlug)}</b><span>${(c.level || "")}</span>`;
    div.addEventListener("click", ()=>{
      const url = new URL(window.location.href);
      url.searchParams.set("course", cSlug);
      window.location.href = url.toString();
    });
    courseListEl.appendChild(div);
  });
}

function computeProgressFrom(lessons, completedArr){
  const total = Array.isArray(lessons) ? lessons.length : 0;
  if(total === 0) return { done: 0, total: 0, pct: 0 };

  const doneSet = new Set(uniqStrings(completedArr || []));
  let done = 0;
  for(const l of lessons){
    const lid = lessonKey(l);
    if(lid && doneSet.has(lid)) done++;
  }
  return { done, total, pct: clampPct((done / total) * 100) };
}

async function renderLessons(course){
  const courseSlug = courseKey(course);
  const lessons = Array.isArray(course.lessons) ? course.lessons : [];

  lessonTitleEl.textContent = (course.title || courseSlug) + " — Lessons";
  lessonHintEl.textContent = lessons.length ? (lessons.length + " lessons available") : "No content yet.";

  // DB-first progress
  let completed = [];
  let lastLesson = "";

  try{
    const p = await loadProgressDB(courseSlug);
    completed = p.completed;
    lastLesson = p.lastLesson;
  }catch(e){
    completed = getCompletedLS(courseSlug);
    lastLesson = getLastLessonLS(courseSlug);
  }

  const startBtn = document.getElementById("startBtn");
  const continueBtn = document.getElementById("continueBtn");

  const firstLessonSlug = lessons.length ? lessonKey(lessons[0]) : "";

  if(firstLessonSlug){
    startBtn.style.display = "inline-flex";
    startBtn.href = BASE_URL + "/student/lesson.php?course=" + encodeURIComponent(courseSlug) + "&lesson=" + encodeURIComponent(firstLessonSlug);
  }else{
    startBtn.style.display = "none";
    startBtn.href = "#";
  }

  if(lastLesson){
    continueBtn.style.display = "inline-flex";
    continueBtn.href = BASE_URL + "/student/lesson.php?course=" + encodeURIComponent(courseSlug) + "&lesson=" + encodeURIComponent(lastLesson);
  }else{
    continueBtn.style.display = "none";
    continueBtn.href = "#";
  }

  // progress bar
  const prog = computeProgressFrom(lessons, completed);
  const progressWrap = document.getElementById("progressWrap");
  const progressText = document.getElementById("progressText");
  const progressFill = document.getElementById("progressFill");

  progressWrap.style.display = lessons.length ? "block" : "none";
  progressText.textContent = lessons.length ? `Progress: ${prog.pct}% (${prog.done}/${prog.total})` : "Progress: 0% (0/0)";
  progressFill.style.width = prog.pct + "%";

  // list lessons with completed badges
  lessonListEl.innerHTML = "";
  if(!lessons.length){
    lessonListEl.innerHTML = `<div class="small">Lesson video will be available soon.</div>`;
    return;
  }

  const doneSet = new Set(uniqStrings(completed));

  lessons.forEach((l, idx)=>{
    const lSlug = lessonKey(l);
    const isDone = lSlug && doneSet.has(lSlug);

    const row = document.createElement("div");
    row.className = "lesson";
    row.innerHTML = `
      <div>
        <b>${String(idx+1).padStart(2,"0")}. ${(l.title || ("Lesson " + (idx+1)))}</b>
        <span>${(l.type || "Lesson")}</span>
      </div>
      <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;justify-content:flex-end">
        <span class="badge ${isDone ? "badgeOk" : ""}">${isDone ? "Completed" : "Not completed"}</span>
        <a class="open" href="${BASE_URL}/student/lesson.php?course=${encodeURIComponent(courseSlug)}&lesson=${encodeURIComponent(lSlug)}">Open</a>
      </div>
    `;
    lessonListEl.appendChild(row);
  });
}

(async function(){
  const subject = <?php echo json_encode($subject, JSON_UNESCAPED_SLASHES); ?>;
  const subjectTitle = <?php echo json_encode($subjectTitle, JSON_UNESCAPED_SLASHES); ?>;

  // the URL param now must be course=<course_slug>
  const selectedParam = toStr(qs("course"));

  try{
    const courses = await loadCoursesApi();

    if(!courses.length){
      courseListEl.innerHTML = `<div class="small">No courses available.</div>`;
      return;
    }

    // selection: 1) course param matches courseKey()  2) subject matches title  3) first course
    let selectedCourse = null;

    if (selectedParam) {
      selectedCourse = courses.find(c => courseKey(c) === selectedParam) || null;
    }

    if (!selectedCourse && subject) {
      const s = String(subjectTitle || subject).toLowerCase();
      selectedCourse = courses.find(c => String(c.title || "").toLowerCase().includes(s)) || null;
    }

    if (!selectedCourse) selectedCourse = courses[0];

    const selectedCourseSlug = courseKey(selectedCourse);

    renderCourses(courses, selectedCourseSlug);
    await renderLessons(selectedCourse);

  }catch(err){
    console.error(err);
    courseListEl.innerHTML = `<div class="small">Error loading courses.</div>`;
    lessonListEl.innerHTML = ``;
  }
})();
</script>
</body>
</html>