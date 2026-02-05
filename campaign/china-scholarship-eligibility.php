<?php
// campaign-scholarship.php
// Simple lead page – uses main site header & footer
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Check Your China Scholarship Eligibility | DemoticEdu</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <meta name="description" content="Quick China scholarship eligibility check. Free consultation with DemoticEdu experts.">

  <!-- Main site styles (brand colors included) -->
  <link rel="stylesheet" href="../styles.css?v=20251215">
</head>
<body>

<!-- ===== HEADER (shared) ===== -->
<?php include __DIR__ . '/../header-footer/header.html'; ?>

<main class="site-main">
  <section class="section">
    <div class="container">

      <!-- Badge -->
      <span class="badge" style="margin-bottom:12px;">
        China Scholarship — Quick Check
      </span>

      <!-- Title -->
      <h1 style="margin-top:10px;">
        Check your scholarship eligibility
      </h1>

      <p class="lead">
        Answer a few questions. We’ll estimate what type of scholarship you may qualify for
        and contact you for a <strong>free consultation</strong>.
      </p>

      <div class="grid cols-2" style="margin-top:24px;">

        <!-- LEFT: WHY -->
        <div class="card">
          <h2>Why this eligibility check?</h2>
          <p class="muted">
            Scholarships depend on your results, study level, intake timing,
            and university requirements. This quick check helps us guide you properly.
          </p>

          <ul style="margin-top:14px; line-height:1.8;">
            <li>Advisor-reviewed suggestions</li>
            <li>No spam — only admission guidance</li>
            <li>Free consultation after submission</li>
          </ul>
        </div>

        <!-- RIGHT: FORM -->
        <div class="card">
          <h2>Quick profile check</h2>

          <form method="post" action="submit-lead.php">

            <div class="form-row">
              <div>
                <label>Name *</label>
                <input type="text" name="name" placeholder="Your full name" required>
              </div>

              <div>
                <label>Phone / WhatsApp *</label>
                <input type="tel" name="phone" placeholder="+880…" required>
              </div>
            </div>

            <div class="form-row">
              <div>
                <label>Email (optional)</label>
                <input type="email" name="email" placeholder="you@email.com">
              </div>

              <div>
                <label>Country (optional)</label>
                <input type="text" name="country" value="Bangladesh">
              </div>
            </div>

            <div class="form-row">
              <div>
                <label>Highest Qualification</label>
                <select name="qualification">
                  <option value="">Select</option>
                  <option>SSC</option>
                  <option>HSC</option>
                  <option>Bachelor</option>
                  <option>Master</option>
                </select>
              </div>

              <div>
                <label>Result range</label>
                <select name="result">
                  <option value="">Select</option>
                  <option>Below Average</option>
                  <option>Average</option>
                  <option>Good</option>
                  <option>Excellent</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div>
                <label>Target Study Level</label>
                <select name="target_level">
                  <option value="">Select</option>
                  <option>Chinese Language</option>
                  <option>Diploma</option>
                  <option>Bachelor</option>
                  <option>Master</option>
                  <option>PhD</option>
                </select>
              </div>

              <div>
                <label>Preferred intake</label>
                <select name="intake">
                  <option>Not sure</option>
                  <option>March</option>
                  <option>September</option>
                </select>
              </div>
            </div>

            <div>
              <label>Preferred major (optional)</label>
              <input type="text" name="major" placeholder="e.g. CSE, Business, MBBS">
            </div>

            <div class="form-actions">
              <button type="submit" class="btn">
                Request Guidance
              </button>
              <span class="muted">We’ll contact you soon.</span>
            </div>

            <!-- tracking -->
            <input type="hidden" name="source" value="<?= htmlspecialchars($_GET['src'] ?? 'campaign_china') ?>">

          </form>
        </div>

      </div>
    </div>
  </section>
</main>

<!-- ===== FOOTER (shared) ===== -->
<?php include __DIR__ . '/../header-footer/footer.html'; ?>

<script src="../main.js"></script>
</body>
</html>
