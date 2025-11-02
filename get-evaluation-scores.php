<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin', 'admin', 'human_resource', 'hr_manager'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

header('Content-Type: application/json');

$employee_id = (int)($_POST['employee_id'] ?? 0);
$employee_type = $_POST['employee_type'] ?? '';

if (!$employee_id || !in_array($employee_type, ['employee', 'faculty'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit();
}

try {
    // Get evaluation scores - ONLY for faculty members
    $evaluation_scores = [];
    
    if ($employee_type === 'faculty') {
        // Get faculty evaluation scores (peer-to-peer and head-to-teacher)
        $faculty_eval_query = "
            SELECT 
                es.id as session_id,
                es.evaluator_type,
                es.evaluation_date,
                es.status as session_status,
                mec.name as category_name,
                mec.evaluation_type,
                AVG(er.rating_value) as average_rating,
                COUNT(er.rating_value) as total_ratings,
                MAX(er.rating_value) as max_rating,
                MIN(er.rating_value) as min_rating
            FROM evaluation_sessions es
            JOIN main_evaluation_categories mec ON es.main_category_id = mec.id
            LEFT JOIN evaluation_responses er ON es.id = er.evaluation_session_id
            WHERE es.evaluatee_id = ? 
            AND es.status = 'completed'
            AND mec.evaluation_type IN ('peer_to_peer', 'head_to_teacher')
            AND er.rating_value IS NOT NULL
            GROUP BY es.id, es.evaluator_type, mec.evaluation_type
            ORDER BY es.evaluation_date DESC
        ";
        
        $stmt = mysqli_prepare($conn, $faculty_eval_query);
        mysqli_stmt_bind_param($stmt, "i", $employee_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($result)) {
            $evaluation_scores[] = [
                'session_id' => $row['session_id'],
                'evaluator_type' => $row['evaluator_type'],
                'evaluation_date' => $row['evaluation_date'],
                'category_name' => $row['category_name'],
                'evaluation_type' => $row['evaluation_type'],
                'average_rating' => round($row['average_rating'], 2),
                'total_ratings' => $row['total_ratings'],
                'max_rating' => $row['max_rating'],
                'min_rating' => $row['min_rating'],
                'status' => $row['session_status']
            ];
        }
    } else {
        // For employees, return empty evaluation data (no evaluations for employees)
        // This ensures the system works but shows no evaluation results for employees
        $evaluation_scores = [];
    }
    
    // Calculate overall evaluation statistics
    $overall_stats = [
        'total_evaluations' => count($evaluation_scores),
        'peer_to_peer_count' => 0,
        'head_to_teacher_count' => 0,
        'student_to_teacher_count' => 0,
        'overall_average' => 0,
        'latest_evaluation_date' => null,
        'evaluation_status' => 'no_evaluations'
    ];
    
    if (!empty($evaluation_scores)) {
        $total_rating = 0;
        $rating_count = 0;
        $latest_date = null;
        
        foreach ($evaluation_scores as $score) {
            if ($score['average_rating'] > 0) {
                $total_rating += $score['average_rating'];
                $rating_count++;
            }
            
            // Count by evaluation type
            if ($score['evaluation_type'] === 'peer_to_peer') {
                $overall_stats['peer_to_peer_count']++;
            } elseif ($score['evaluation_type'] === 'head_to_teacher') {
                $overall_stats['head_to_teacher_count']++;
            } elseif ($score['evaluation_type'] === 'student_to_teacher') {
                $overall_stats['student_to_teacher_count']++;
            }
            
            // Track latest evaluation date
            if ($latest_date === null || $score['evaluation_date'] > $latest_date) {
                $latest_date = $score['evaluation_date'];
            }
        }
        
        $overall_stats['overall_average'] = $rating_count > 0 ? round($total_rating / $rating_count, 2) : 0;
        $overall_stats['latest_evaluation_date'] = $latest_date;
        
        // Determine evaluation status
        if ($overall_stats['overall_average'] >= 4.0) {
            $overall_stats['evaluation_status'] = 'excellent';
        } elseif ($overall_stats['overall_average'] >= 3.5) {
            $overall_stats['evaluation_status'] = 'good';
        } elseif ($overall_stats['overall_average'] >= 3.0) {
            $overall_stats['evaluation_status'] = 'satisfactory';
        } elseif ($overall_stats['overall_average'] >= 2.0) {
            $overall_stats['evaluation_status'] = 'needs_improvement';
        } else {
            $overall_stats['evaluation_status'] = 'poor';
        }
    }
    
    echo json_encode([
        'success' => true,
        'evaluation_scores' => $evaluation_scores,
        'overall_stats' => $overall_stats
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching evaluation scores: ' . $e->getMessage()]);
}
?>
