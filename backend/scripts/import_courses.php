<?php
// htdocs/backend/scripts/import_courses.php

require_once __DIR__ . '/../../admin/db_connection.php'; // gives $conn (mysqli)

if (!isset($conn)) {
    die("DB connection not found.");
}

// Load JSON
$jsonPath = __DIR__ . '/../../data/courses.json';

if (!file_exists($jsonPath)) {
    die("courses.json not found.");
}

$data = json_decode(file_get_contents($jsonPath), true);

if (!isset($data['courses'])) {
    die("Invalid JSON format.");
}

$courseCount = 0;
$lessonCount = 0;

foreach ($data['courses'] as $courseIndex => $course) {

    $slug = $course['id'];
    $title = $course['title'];
    $level = $course['level'] ?? null;
    $sortOrder = $courseIndex;

    // Insert or update course
    $stmt = $conn->prepare("
        INSERT INTO courses (slug, title, level, status, sort_order)
        VALUES (?, ?, ?, 'published', ?)
        ON DUPLICATE KEY UPDATE
            title = VALUES(title),
            level = VALUES(level),
            sort_order = VALUES(sort_order),
            status = 'published'
    ");

    $stmt->bind_param("sssi", $slug, $title, $level, $sortOrder);
    $stmt->execute();
    $stmt->close();

    // Get course ID
    $stmt = $conn->prepare("SELECT id FROM courses WHERE slug = ? LIMIT 1");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    $courseRow = $result->fetch_assoc();
    $courseId = $courseRow['id'];
    $stmt->close();

    $courseCount++;

    if (!empty($course['lessons'])) {
        foreach ($course['lessons'] as $lessonIndex => $lesson) {

            $lessonSlug = $lesson['id'];
            $lessonTitle = $lesson['title'];
            $lessonType = $lesson['type'] ?? 'Topic';
            $videoUrl = $lesson['videoUrl'] ?? null;
            $pdfUrl = $lesson['pdfUrl'] ?? null;
            $lessonSort = $lessonIndex;

            $stmt = $conn->prepare("
                INSERT INTO lessons (course_id, slug, title, lesson_type, video_url, pdf_url, sort_order, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'published')
                ON DUPLICATE KEY UPDATE
                    title = VALUES(title),
                    lesson_type = VALUES(lesson_type),
                    video_url = VALUES(video_url),
                    pdf_url = VALUES(pdf_url),
                    sort_order = VALUES(sort_order),
                    status = 'published'
            ");

            $stmt->bind_param(
                "isssssi",
                $courseId,
                $lessonSlug,
                $lessonTitle,
                $lessonType,
                $videoUrl,
                $pdfUrl,
                $lessonSort
            );

            $stmt->execute();
            $stmt->close();

            $lessonCount++;
        }
    }
}

echo "IMPORT OK<br>";
echo "Courses upserted: {$courseCount}<br>";
echo "Lessons upserted: {$lessonCount}<br>";