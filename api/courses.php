<?php
// htdocs/demoticedu/api/courses.php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../app/Core/bootstrap.php';

use App\Core\DB;

try {
    $pdo = DB::pdo();

    // Courses
    $stmtCourses = $pdo->prepare("
        SELECT id, slug, title, level
        FROM courses
        WHERE status IN ('active','published')
        ORDER BY sort_order ASC, id ASC
    ");
    $stmtCourses->execute();
    $coursesRows = $stmtCourses->fetchAll(PDO::FETCH_ASSOC);

    // Lessons
    $stmtLessons = $pdo->prepare("
        SELECT id, slug, title, lesson_type, video_url, pdf_url
        FROM lessons
        WHERE course_id = :course_id
          AND status IN ('active','published')
        ORDER BY sort_order ASC, id ASC
    ");

    $response = ['courses' => []];

    foreach ($coursesRows as $c) {

        $courseId = (int)$c['id'];

        $course = [
            'id' => (string)$c['slug'],   // course slug for URLs
            'slug' => (string)$c['slug'],
            'title' => (string)($c['title'] ?? ''),
            'level' => (string)($c['level'] ?? ''),
            'lessons' => []
        ];

        $stmtLessons->execute(['course_id' => $courseId]);
        $lessonsRows = $stmtLessons->fetchAll(PDO::FETCH_ASSOC);

        foreach ($lessonsRows as $l) {

            $course['lessons'][] = [
                'id' => (string)$l['id'],          // ✅ NUMERIC LESSON ID
                'slug' => (string)$l['slug'],      // optional but useful
                'title' => (string)($l['title'] ?? ''),
                'type' => (string)($l['lesson_type'] ?? 'Topic'),
                'videoUrl' => (string)($l['video_url'] ?? ''),
                'pdfUrl' => (string)($l['pdf_url'] ?? ''),
            ];
        }

        $response['courses'][] = $course;
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}