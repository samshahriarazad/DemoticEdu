<?php
// htdocs/student/lesson.php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Core/bootstrap.php';

use App\Core\DB;

// ✅ Student session guard
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$role   = (string)($_SESSION['role'] ?? '');

// next should be relative, not absolute
$next = $_SERVER['REQUEST_URI'] ?? (BASE_URL . '/student/lesson.php');
$next = ltrim((string)$next, '/');
if ($next === '') $next = 'student/lesson.php';

if ($userId <= 0 || $role !== 'student') {
  header('Location: ' . BASE_URL . '/auth/login.php?next=' . urlencode($next));
  exit;
}

// ✅ Validate user
$pdo = DB::pdo();
$stmt = $pdo->prepare("SELECT id, role, is_active FROM users WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || (int)($user['is_active'] ?? 0) !== 1 || (string)($user['role'] ?? '') !== 'student') {
  $_SESSION = [];
  if (session_status() === PHP_SESSION_ACTIVE) session_destroy();
  header('Location: ' . BASE_URL . '/auth/login.php?next=' . urlencode($next));
  exit;
}

// For JS
$baseUrlJs = (defined('BASE_URL') ? BASE_URL : '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Lesson | DemoticEdu</title>

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
    .wrap{width:min(1100px, calc(100% - 36px));margin:18px auto 28px}
    .topbar{
      background:rgba(255,255,255,.86);
      border:1px solid rgba(15,23,42,.08);
      border-radius:var(--r22);
      padding:14px;
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:12px;
      box-shadow:var(--shadowSoft);
      backdrop-filter:blur(10px);
    }
    .topbar h1{margin:0;font-size:16px}
    .topbar p{margin:2px 0 0;color:rgba(11,18,32,.62);font-weight:700;font-size:13px}
    .actions{display:flex;gap:10px;align-items:center;flex-wrap:wrap;justify-content:flex-end}
    .btn{
      text-decoration:none;
      padding:10px 12px;
      border-radius:14px;
      font-weight:1000;
      background:linear-gradient(135deg,var(--yellow),#ffd58b);
      color:#1b1600;
      border:1px solid rgba(248,176,48,.35);
      box-shadow:0 18px 44px rgba(248,176,48,.18);
      display:inline-flex;
      align-items:center;
      justify-content:center;
      gap:8px;
    }
    .btnGhost{
      text-decoration:none;
      padding:10px 12px;
      border-radius:14px;
      font-weight:900;
      background:rgba(15,23,42,.04);
      border:1px solid rgba(15,23,42,.10);
      color:rgba(11,18,32,.78);
      display:inline-flex;
      align-items:center;
      justify-content:center;
      gap:8px;
    }
    .grid{margin-top:12px;display:grid;grid-template-columns:2fr 1fr;gap:12px;align-items:start}
    .card{
      background:rgba(255,255,255,.92);
      border:1px solid var(--border);
      border-radius:var(--r22);
      box-shadow:var(--shadowSoft);
      padding:14px;
      backdrop-filter:blur(10px);
    }
    .card h2{margin:0 0 6px;font-size:15px}
    .meta{color:var(--muted);font-weight:700;font-size:13px}
    .block{
      margin-top:12px;
      border:1px dashed rgba(15,23,42,.22);
      border-radius:18px;
      padding:14px;
      background:rgba(15,23,42,.02);
    }
    .block b{display:block;margin-bottom:6px}
    iframe{
      width:100%;
      height:420px;
      border:0;
      border-radius:16px;
      background:#fff;
    }
    .list a{
      display:block;
      text-decoration:none;
      padding:10px 12px;
      border-radius:14px;
      border:1px solid rgba(15,23,42,.10);
      background:#fff;
      color:rgba(11,18,32,.86);
      font-weight:900;
      margin-top:10px;
    }
    .list a.primary{
      background:rgba(32,83,122,.06);
      border-color:rgba(32,83,122,.18);
      color:var(--blue);
    }
    .toast{
      position:fixed;left:50%;transform:translateX(-50%);
      bottom:18px;background:#0b1220;color:#fff;
      padding:12px 14px;border-radius:14px;
      opacity:0;pointer-events:none;transition:.2s;font-size:13px;
      max-width:min(560px, calc(100% - 26px));
      text-align:center;
    }
    .toast.show{opacity:1}
    @media(max-width:980px){.grid{grid-template-columns:1fr} iframe{height:320px}}
  </style>
</head>

<body>
  <div class="wrap">
    <div class="topbar">
      <div>
        <h1 id="pageTitle">Lesson</h1>
        <p id="pageMeta">Loading...</p>
      </div>
      <div class="actions">
        <a class="btnGhost" id="backBtn" href="<?php echo htmlspecialchars(BASE_URL . '/student/course.php'); ?>">Back</a>
        <a class="btn" href="<?php echo htmlspecialchars(BASE_URL . '/auth/logout.php'); ?>">Logout</a>
      </div>
    </div>

    <div class="grid">
      <section class="card">
        <h2 id="lessonName">Lesson</h2>
        <div class="meta" id="lessonType">—</div>

        <div class="block" id="videoBlock">
          <b>Video</b>
          <div class="meta" id="videoHint">Lesson video will be available soon.</div>
          <div id="videoWrap" style="margin-top:10px; display:none;"></div>
        </div>

        <div class="block" id="pdfBlock">
          <b>PDF / Notes</b>
          <div class="meta" id="pdfHint">Study materials will be available soon.</div>
          <div id="pdfWrap" style="margin-top:10px; display:none;"></div>
        </div>
      </section>

      <aside class="card">
        <h2>Lesson Actions</h2>
        <div class="meta">Progress is saved to database.</div>

        <div class="list">
          <a href="#" id="markDone" class="primary">Mark as Completed</a>
          <a href="#" id="prevLesson">Prev Lesson</a>
          <a href="#" id="nextLesson">Next Lesson</a>
          <a href="<?php echo htmlspecialchars(BASE_URL . '/student/course.php'); ?>" id="goCourses">Go to My Courses</a>
        </div>
      </aside>
    </div>
  </div>

  <div id="toast" class="toast" role="status" aria-live="polite"></div>

<script>
const BASE_URL = <?php echo json_encode($baseUrlJs, JSON_UNESCAPED_SLASHES); ?>;

function qs(name){
  return new URLSearchParams(window.location.search).get(name) || "";
}
function toStr(x){ return String(x ?? "").trim(); }

// IMPORTANT: lesson slug is preferred, fallback to id
function lessonKey(l){
  return toStr(l && l.id);
}

// =========================
// localStorage keys (fallback)
// =========================
function keyLast(courseSlug){ return "lastLesson_" + courseSlug; }
function keyLastTime(courseSlug){ return "lastLessonTime_" + courseSlug; }
function keyCompleted(courseSlug){ return "completed_" + courseSlug; }

function safeParseArray(raw){
  try{
    const v = JSON.parse(raw);
    return Array.isArray(v) ? v : [];
  }catch(e){
    return [];
  }
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
function setCompletedLS(courseSlug, arr){
  localStorage.setItem(keyCompleted(courseSlug), JSON.stringify(uniqStrings(arr)));
}
function setLastLessonLS(courseSlug, lessonSlug){
  const lid = toStr(lessonSlug);
  if(!courseSlug || !lid) return;
  localStorage.setItem(keyLast(courseSlug), lid);
  localStorage.setItem(keyLastTime(courseSlug), String(Date.now()));
}

function showToast(msg){
  const t = document.getElementById("toast");
  t.textContent = msg;
  t.classList.add("show");
  clearTimeout(window.__toastTimer);
  window.__toastTimer = setTimeout(()=>t.classList.remove("show"), 1800);
}

// ✅ allow safe URLs (basic)
function safeUrl(u){
  const s = String(u || "").trim();
  if(!s) return "";
  if (/^javascript:/i.test(s)) return "";
  return s;
}

// ✅ Load ONE course by slug from DB API
async function loadCourse(courseSlug){

  const url = BASE_URL + "/api/courses.php";
  const res = await fetch(url, { cache: "no-store" });

  if(!res.ok) throw new Error("Failed to load " + url);

  const data = await res.json();

  if(!data || !Array.isArray(data.courses)){
    throw new Error("Invalid courses response");
  }

  const course = data.courses.find(c => String(c.slug) === String(courseSlug));

  if(!course){
    throw new Error("Course not found: " + courseSlug);
  }

  return course;
}

// ✅ DB progress read (course slug)
async function loadProgressDB(courseSlug){
  const url = BASE_URL + "/api/progress.php?course=" + encodeURIComponent(courseSlug);
  const res = await fetch(url, { cache: "no-store" });
  if(!res.ok) throw new Error("Failed to load " + url);
  const data = await res.json();
  const completed = Array.isArray(data.completed) ? uniqStrings(data.completed) : [];
  const lastLesson = toStr(data.lastLesson || "");
  return { completed, lastLesson };
}

// ✅ Send progress to DB
async function sendProgress(courseSlug, lessonSlug, action){
  try{
    const res = await fetch(BASE_URL + "/api/progress-update.php", {
      method: "POST",
      credentials: "same-origin",
      headers: {"Content-Type":"application/json"},
      body: JSON.stringify({ course: courseSlug, lesson: lessonSlug, action })
    });
    if(!res.ok) throw new Error("HTTP " + res.status);
    const data = await res.json().catch(()=>null);
    if(!data || data.ok !== true) throw new Error("Bad response");
    return true;
  }catch(e){
    return false;
  }
}

// URL params must be slugs:
const courseSlug = toStr(qs("course"));
const lessonSlug = toStr(qs("lesson"));

(async function(){
  const backBtn = document.getElementById("backBtn");

  // Back to course.php, keep selected course
  if(courseSlug){
    const backHref = BASE_URL + "/student/course.php?course=" + encodeURIComponent(courseSlug);
    backBtn.href = backHref;
    document.getElementById("goCourses").href = backHref;
  }

  if(!courseSlug || !lessonSlug){
    document.getElementById("pageTitle").textContent = "Invalid lesson";
    document.getElementById("pageMeta").textContent = "Missing course/lesson in URL.";
    document.getElementById("lessonName").textContent = "Lesson not found";
    document.getElementById("lessonType").textContent = "Go back and select a lesson.";
    return;
  }

  try{
    // 1) Load course+lessons
    const course = await loadCourse(courseSlug);
    const lessons = Array.isArray(course.lessons) ? course.lessons : [];

    const idx = lessons.findIndex(l => lessonKey(l) === lessonSlug);
    const lesson = idx >= 0 ? lessons[idx] : null;

    document.getElementById("pageTitle").textContent = course.title || "Course";
    const lessonNumber = idx + 1;

document.getElementById("pageMeta").textContent =
  "Course: " + (course.title || courseSlug) + " • Lesson: " + lessonNumber;

    if(!lesson){
      document.getElementById("lessonName").textContent = "Lesson not found";
      document.getElementById("lessonType").textContent = "This lesson does not exist in DB.";
      return;
    }

    // 2) Set last lesson locally + send seen to DB
    setLastLessonLS(courseSlug, lessonSlug);
    sendProgress(courseSlug, lessonSlug, "seen");

    document.getElementById("lessonName").textContent = lesson.title || ("Lesson " + lessonSlug);
    document.getElementById("lessonType").textContent = lesson.type || "Lesson";

    // Video
    const videoUrl = safeUrl(lesson.videoUrl || "");
    if(videoUrl){
      document.getElementById("videoHint").textContent = "Video available.";
      const wrap = document.getElementById("videoWrap");
      wrap.style.display = "block";

      const iframe = document.createElement("iframe");
      iframe.src = videoUrl;
      iframe.allow = "autoplay; encrypted-media";
      iframe.allowFullscreen = true;

      wrap.innerHTML = "";
      wrap.appendChild(iframe);
    }

    // PDF
    const pdfUrl = safeUrl(lesson.pdfUrl || "");
    if(pdfUrl){
      document.getElementById("pdfHint").textContent = "PDF available.";
      const wrap = document.getElementById("pdfWrap");
      wrap.style.display = "block";

      const iframe = document.createElement("iframe");
      iframe.src = pdfUrl;
      iframe.setAttribute("title", "PDF Preview");

      wrap.innerHTML = "";
      wrap.appendChild(iframe);
    }

    // 3) Determine completed state: DB-first, fallback localStorage
    completedSet = new Set();
    try{
      const p = await loadProgressDB(courseSlug);
      completedSet = new Set(uniqStrings(p.completed));
      setCompletedLS(courseSlug, Array.from(completedSet));
    }catch(e){
      completedSet = new Set(getCompletedLS(courseSlug));
    }

    const markBtn = document.getElementById("markDone");

    const refreshMark = ()=>{
      const done = completedSet.has(lessonSlug);
      markBtn.textContent = done ? "Completed ✓" : "Mark as Completed";
    };
    refreshMark();

    // 4) Click complete
    markBtn.addEventListener("click", async (e)=>{
      e.preventDefault();

      completedSet.add(lessonSlug);
      setCompletedLS(courseSlug, Array.from(completedSet));
      refreshMark();

      const ok = await sendProgress(courseSlug, lessonSlug, "complete");
      showToast(ok ? "Saved in database ✓" : "Saved locally (DB failed)");
    });

    // Prev/Next
    const prevBtn = document.getElementById("prevLesson");
    const nextBtn = document.getElementById("nextLesson");

    const prevLesson = (idx > 0) ? lessons[idx - 1] : null;
    const nextLesson = (idx >= 0 && idx < lessons.length - 1) ? lessons[idx + 1] : null;

    if(prevLesson){
      const prevSlug = lessonKey(prevLesson);
      prevBtn.classList.add("primary");
      prevBtn.href = BASE_URL + "/student/lesson.php?course=" + encodeURIComponent(courseSlug) + "&lesson=" + encodeURIComponent(prevSlug);
      prevBtn.textContent = "Prev Lesson";
    }else{
      prevBtn.href = "#";
      prevBtn.textContent = "Prev (none)";
      prevBtn.addEventListener("click", (e)=>{ e.preventDefault(); showToast("This is the first lesson"); });
    }

    if(nextLesson){
      const nextSlug = lessonKey(nextLesson);
      nextBtn.classList.add("primary");
      nextBtn.href = BASE_URL + "/student/lesson.php?course=" + encodeURIComponent(courseSlug) + "&lesson=" + encodeURIComponent(nextSlug);
      nextBtn.textContent = "Next Lesson";
    }else{
      nextBtn.href = "#";
      nextBtn.textContent = "Next (none)";
      nextBtn.addEventListener("click", (e)=>{ e.preventDefault(); showToast("This is the last lesson"); });
    }

  }catch(err){
    console.error(err);
    document.getElementById("pageTitle").textContent = "Error";
    document.getElementById("pageMeta").textContent = "Could not load lesson from DB API.";
  }
})();
</script>
</body>
</html>