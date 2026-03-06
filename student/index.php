<?php
// htdocs/student/index.php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Core/bootstrap.php';

use App\Core\DB;

// ✅ Session guard
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if ($userId <= 0) {
  header('Location: ' . BASE_URL . '/auth/login.php?next=' . urlencode('student/index.php'));
  exit;
}

// ✅ Load user (minimal)
$pdo = DB::pdo();
$stmt = $pdo->prepare("SELECT id, name, email, phone, role, is_active FROM users WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || (int)($user['is_active'] ?? 0) !== 1 || (string)($user['role'] ?? '') !== 'student') {
  // destroy session if invalid
  $_SESSION = [];
  if (session_status() === PHP_SESSION_ACTIVE) session_destroy();
  header('Location: ' . BASE_URL . '/auth/login.php?next=' . urlencode('student/index.php'));
  exit;
}

$userEmail = (string)($user['email'] ?? '');
$userName  = (string)($user['name'] ?? '');

// Provide BASE_URL to JS safely
$baseUrlJs = (defined('BASE_URL') ? BASE_URL : '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Student Dashboard | DemoticEdu</title>

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
    .hello h1{margin:0;font-size:18px}
    .hello p{margin:2px 0 0;font-size:12.8px;color:rgba(11,18,32,.62);font-weight:700}
    .actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap;justify-content:flex-end}
    .pill{border:1px solid rgba(15,23,42,.10);background:rgba(15,23,42,.04);color:rgba(11,18,32,.72);padding:10px 12px;border-radius:999px;font-size:12.5px;font-weight:900;max-width:320px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .btn{display:inline-flex;align-items:center;justify-content:center;text-decoration:none;padding:10px 12px;border-radius:14px;font-weight:1000;font-size:13px;border:1px solid rgba(248,176,48,.35);background:linear-gradient(135deg,var(--yellow),#ffd58b);color:#1b1600;box-shadow:0 18px 44px rgba(248,176,48,.18)}
    .grid{margin-top:14px;display:grid;grid-template-columns:repeat(12,1fr);gap:12px}
    .card{grid-column:span 6;background:rgba(255,255,255,.86);border:1px solid rgba(15,23,42,.10);border-radius:var(--r22);box-shadow:var(--shadowSoft);padding:14px;backdrop-filter:blur(10px)}
    .cardBig{grid-column:span 12}
    .card h3{margin:0 0 6px;font-size:14.5px}
    .card p{margin:0;color:rgba(11,18,32,.62);font-weight:700;font-size:13px;line-height:1.45}
    .row{margin-top:10px;display:flex;gap:10px;flex-wrap:wrap;align-items:center}
    .tag{font-size:12.5px;font-weight:900;color:rgba(11,18,32,.72);padding:8px 10px;border-radius:999px;border:1px solid rgba(15,23,42,.10);background:rgba(15,23,42,.04)}
    .link{font-size:12.6px;font-weight:900;color:var(--blue);text-decoration:none;padding:10px 12px;border-radius:14px;border:1px solid rgba(32,83,122,.18);background:rgba(32,83,122,.06)}
    .prog{margin-top:12px}
    .bar{margin-top:8px;height:10px;border-radius:999px;border:1px solid rgba(15,23,42,.10);background:rgba(15,23,42,.04);overflow:hidden}
    .fill{height:100%;border-radius:999px;background:linear-gradient(90deg, rgba(32,83,122,.95), rgba(248,176,48,.95));width:0%}
    .pct{font-weight:1000;font-size:12.5px;color:rgba(11,18,32,.72)}
    @media(max-width:920px){.layout{grid-template-columns:1fr}.side{border-right:0;border-bottom:1px solid var(--border)}.card{grid-column:span 12}}
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

      <nav class="nav" aria-label="Student navigation">
        <a class="active" href="index.php">Dashboard</a>
        <a href="course.php">My Courses</a>
      </nav>
    </aside>

    <main class="main">
      <div class="topbar">
        <div class="hello">
          <h1>Welcome back 👋 <?php echo htmlspecialchars($userName ?: ''); ?></h1>
          <p>Continue your learning and track progress.</p>
        </div>
        <div class="actions">
          <div class="pill" id="userEmail"><?php echo htmlspecialchars($userEmail ? ("Logged in: " . $userEmail) : "Logged in"); ?></div>
          <a class="btn" href="<?php echo htmlspecialchars(BASE_URL . '/auth/logout.php'); ?>">Logout</a>
        </div>
      </div>

      <section class="grid" id="dashGrid"></section>
    </main>
  </div>

  <script>
  // BASE_URL from PHP
  const BASE_URL = <?php echo json_encode($baseUrlJs, JSON_UNESCAPED_SLASHES); ?>;

  // ========= localStorage fallback =========
  function keyLast(courseId){ return "lastLesson_" + courseId; }
  function keyCompleted(courseId){ return "completed_" + courseId; }
  function toId(x){ return String(x ?? "").trim(); }

  function safeParseArray(raw){
    try{
      const v = JSON.parse(raw);
      return Array.isArray(v) ? v : [];
    }catch(e){ return []; }
  }

  function uniqStrings(arr){
    const out = [];
    const seen = new Set();
    for(const x of (arr || [])){
      const s = toId(x);
      if(!s) continue;
      if(seen.has(s)) continue;
      seen.add(s);
      out.push(s);
    }
    return out;
  }

  function getCompletedLS(courseId){
    const raw = localStorage.getItem(keyCompleted(courseId)) || "[]";
    return uniqStrings(safeParseArray(raw));
  }

  function getLastLessonLS(courseId){
    const s = toId(localStorage.getItem(keyLast(courseId)));
    return s ? s : "";
  }

  function clampPct(p){
    const n = Number.isFinite(p) ? p : 0;
    return Math.max(0, Math.min(100, Math.round(n)));
  }

  function computeProgress(lessons, completedArr){
    const total = Array.isArray(lessons) ? lessons.length : 0;
    if(total === 0) return { done: 0, total: 0, pct: 0 };

    const doneSet = new Set(uniqStrings(completedArr || []));
    let done = 0;
    for(const l of lessons){
      const lid = toId(l && l.id);
      if(lid && doneSet.has(lid)) done++;
    }
    return { done, total, pct: clampPct((done / total) * 100) };
  }

  // ========= DB progress (preferred) =========
  async function loadProgressDB(courseId){
    const res = await fetch(BASE_URL + "/api/progress.php?course=" + encodeURIComponent(courseId), { cache: "no-store" });
    if(!res.ok) throw new Error("progress http " + res.status);
    const data = await res.json();
    const completed = Array.isArray(data.completed) ? uniqStrings(data.completed) : [];
    const lastLesson = toId(data.lastLesson || "");
    return { completed, lastLesson };
  }

  // ========= Courses API =========
  async function loadCourses(){
    const res = await fetch(BASE_URL + "/api/courses.php", { cache: "no-store" });
    if(!res.ok) throw new Error("Failed to load courses");
    const data = await res.json();
    if(!data || !Array.isArray(data.courses)) throw new Error("Invalid courses response");
    return data.courses;
  }

  function el(tag, cls){
    const x = document.createElement(tag);
    if(cls) x.className = cls;
    return x;
  }

  (async function(){
    const grid = document.getElementById("dashGrid");

    try{
      const courses = await loadCourses();
      grid.innerHTML = "";

      if(!courses.length){
        const c = el("div","card cardBig");
        c.innerHTML = `<h3>No Courses Found</h3><p>Please check your courses data.</p>`;
        grid.appendChild(c);
        return;
      }

      // Load progress per course (DB-first, fallback)
      for (const course of courses){
        const courseId = String(course.id);
        const lessons = Array.isArray(course.lessons) ? course.lessons : [];

        let completed = [];
        let lastLessonId = "";

        try{
          const p = await loadProgressDB(courseId);
          completed = p.completed;
          lastLessonId = p.lastLesson;
        }catch(e){
          completed = getCompletedLS(courseId);
          lastLessonId = getLastLessonLS(courseId);
        }

        const prog = computeProgress(lessons, completed);
        const firstLessonId = lessons.length ? toId(lessons[0].id) : "";

        const startHref = firstLessonId
          ? `lesson.php?course=${encodeURIComponent(courseId)}&lesson=${encodeURIComponent(firstLessonId)}`
          : `course.php?course=${encodeURIComponent(courseId)}`;

        const continueHref = lastLessonId
          ? `lesson.php?course=${encodeURIComponent(courseId)}&lesson=${encodeURIComponent(lastLessonId)}`
          : "";

        const card = el("div","card");
        card.innerHTML = `
          <h3>${course.title || courseId}</h3>
          <p>${course.level || ""}</p>

          <div class="prog">
            <div style="display:flex;justify-content:space-between;gap:10px;align-items:center">
              <span class="tag">${lessons.length ? `Completed: ${prog.done}/${prog.total}` : "No content yet"}</span>
              <span class="pct">${lessons.length ? (prog.pct + "%") : "0%"}</span>
            </div>
            <div class="bar"><div class="fill" style="width:${prog.pct}%;"></div></div>
          </div>

          <div class="row">
            ${continueHref
              ? `<a class="btn" href="${continueHref}">Continue</a>`
              : `<a class="btn" href="${startHref}">Start</a>`
            }
            <a class="link" href="course.php?course=${encodeURIComponent(courseId)}">View Lessons</a>
          </div>
        `;
        grid.appendChild(card);
      }

    }catch(err){
      console.error(err);
      grid.innerHTML = `<div class="card cardBig"><h3>Error</h3><p>Could not load courses.</p></div>`;
    }
  })();
</script>
</body>
</html>