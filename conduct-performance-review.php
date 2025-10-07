<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/id_encryption.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager'])) {
    header('Location: index.php');
    exit();
}

// Set page title
$page_title = 'Conduct Performance Review';

$message = '';
$message_type = '';

// Check if review ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: performance-reviews.php');
    exit();
}

// Decrypt the review ID
$review_id = safe_decrypt_id($_GET['id']);
if ($review_id <= 0) {
    header('Location: performance-reviews.php');
    exit();
}

// Get review details
$review_query = "SELECT 
    pr.*,
    f.first_name,
    f.last_name,
    f.email,
    f.department,
    f.position,
    f.phone,
    u.username as reviewer_name
FROM performance_reviews pr
LEFT JOIN faculty f ON pr.employee_id = f.id
LEFT JOIN users u ON pr.reviewer_id = u.id
WHERE pr.id = ?";
$stmt = mysqli_prepare($conn, $review_query);
mysqli_stmt_bind_param($stmt, "i", $review_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$review = mysqli_fetch_assoc($result);

if (!$review) {
    header('Location: performance-reviews.php');
    exit();
}

// Get performance review categories and criteria
$categories_query = "SELECT 
    prc.*,
    COUNT(prc2.id) as criteria_count
FROM performance_review_categories prc
LEFT JOIN performance_review_criteria prc2 ON prc.id = prc2.category_id AND prc2.status = 'active'
WHERE prc.status = 'active'
GROUP BY prc.id
ORDER BY prc.id";
$categories_result = mysqli_query($conn, $categories_query);
$categories = [];
while ($row = mysqli_fetch_assoc($categories_result)) {
    $categories[] = $row;
}

// Get criteria for each category
$criteria_query = "SELECT * FROM performance_review_criteria WHERE status = 'active' ORDER BY category_id, order_number";
$criteria_result = mysqli_query($conn, $criteria_query);
$criteria = [];
while ($row = mysqli_fetch_assoc($criteria_result)) {
    $criteria[$row['category_id']][] = $row;
}

// Get existing scores
$scores_query = "SELECT criteria_id, score, comments FROM performance_review_scores WHERE review_id = ?";
$stmt = mysqli_prepare($conn, $scores_query);
mysqli_stmt_bind_param($stmt, "i", $review_id);
mysqli_stmt_execute($stmt);
$scores_result = mysqli_stmt_get_result($stmt);
$existing_scores = [];
while ($row = mysqli_fetch_assoc($scores_result)) {
    $existing_scores[$row['criteria_id']] = $row;
}

// Get existing goals
$goals_query = "SELECT * FROM performance_review_goals WHERE review_id = ? ORDER BY id";
$stmt = mysqli_prepare($conn, $goals_query);
mysqli_stmt_bind_param($stmt, "i", $review_id);
mysqli_stmt_execute($stmt);
$goals_result = mysqli_stmt_get_result($stmt);
$existing_goals = [];
while ($row = mysqli_fetch_assoc($goals_result)) {
    $existing_goals[] = $row;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'save_review':
                // Start transaction
                mysqli_begin_transaction($conn);
                
                try {
                    // Update review details
                    $update_review_query = "UPDATE performance_reviews SET 
                        overall_rating = ?, 
                        overall_percentage = ?,
                        goals_achieved = ?,
                        areas_of_strength = ?,
                        areas_for_improvement = ?,
                        development_plan = ?,
                        recommendations = ?,
                        manager_comments = ?,
                        employee_comments = ?,
                        next_review_date = ?,
                        status = ?,
                        updated_at = NOW()
                        WHERE id = ?";
                    
                    $overall_rating = !empty($_POST['overall_rating']) ? (float)$_POST['overall_rating'] : null;
                    $overall_percentage = !empty($_POST['overall_percentage']) ? (float)$_POST['overall_percentage'] : null;
                    $goals_achieved = sanitize_input($_POST['goals_achieved'] ?? '');
                    $areas_of_strength = sanitize_input($_POST['areas_of_strength'] ?? '');
                    $areas_for_improvement = sanitize_input($_POST['areas_for_improvement'] ?? '');
                    $development_plan = sanitize_input($_POST['development_plan'] ?? '');
                    $recommendations = sanitize_input($_POST['recommendations'] ?? '');
                    $manager_comments = sanitize_input($_POST['manager_comments'] ?? '');
                    $employee_comments = sanitize_input($_POST['employee_comments'] ?? '');
                    $next_review_date = !empty($_POST['next_review_date']) ? $_POST['next_review_date'] : null;
                    $status = sanitize_input($_POST['status']);
                    
                    $stmt = mysqli_prepare($conn, $update_review_query);
                    mysqli_stmt_bind_param($stmt, "ddssssssssi", 
                        $overall_rating, $overall_percentage, $goals_achieved, $areas_of_strength, 
                        $areas_for_improvement, $development_plan, $recommendations, 
                        $manager_comments, $employee_comments, $next_review_date, $status, $review_id);
                    
                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception('Error updating review: ' . mysqli_error($conn));
                    }
                    
                    // Delete existing scores
                    $delete_scores_query = "DELETE FROM performance_review_scores WHERE review_id = ?";
                    $stmt = mysqli_prepare($conn, $delete_scores_query);
                    mysqli_stmt_bind_param($stmt, "i", $review_id);
                    mysqli_stmt_execute($stmt);
                    
                    // Insert new scores
                    if (isset($_POST['scores']) && is_array($_POST['scores'])) {
                        $insert_score_query = "INSERT INTO performance_review_scores (review_id, criteria_id, score, comments) VALUES (?, ?, ?, ?)";
                        $stmt = mysqli_prepare($conn, $insert_score_query);
                        
                        foreach ($_POST['scores'] as $criteria_id => $score_data) {
                            if (!empty($score_data['score']) && $score_data['score'] > 0) {
                                $score = (float)$score_data['score'];
                                $comments = sanitize_input($score_data['comments'] ?? '');
                                mysqli_stmt_bind_param($stmt, "iids", $review_id, $criteria_id, $score, $comments);
                                mysqli_stmt_execute($stmt);
                            }
                        }
                    }
                    
                    // Delete existing goals
                    $delete_goals_query = "DELETE FROM performance_review_goals WHERE review_id = ?";
                    $stmt = mysqli_prepare($conn, $delete_goals_query);
                    mysqli_stmt_bind_param($stmt, "i", $review_id);
                    mysqli_stmt_execute($stmt);
                    
                    // Insert new goals
                    if (isset($_POST['goals']) && is_array($_POST['goals'])) {
                        $insert_goal_query = "INSERT INTO performance_review_goals (review_id, goal_title, goal_description, target_date, achievement_status, achievement_percentage, comments) VALUES (?, ?, ?, ?, ?, ?, ?)";
                        $stmt = mysqli_prepare($conn, $insert_goal_query);
                        
                        foreach ($_POST['goals'] as $goal_data) {
                            if (!empty($goal_data['title'])) {
                                $title = sanitize_input($goal_data['title']);
                                $description = sanitize_input($goal_data['description'] ?? '');
                                $target_date = !empty($goal_data['target_date']) ? $goal_data['target_date'] : null;
                                $status = sanitize_input($goal_data['status'] ?? 'not_started');
                                $percentage = !empty($goal_data['percentage']) ? (float)$goal_data['percentage'] : 0;
                                $comments = sanitize_input($goal_data['comments'] ?? '');
                                
                                mysqli_stmt_bind_param($stmt, "issssds", 
                                    $review_id, $title, $description, $target_date, $status, $percentage, $comments);
                                mysqli_stmt_execute($stmt);
                            }
                        }
                    }
                    
                    // Commit transaction
                    mysqli_commit($conn);
                    
                    $_SESSION['message'] = 'Performance review saved successfully.';
                    $_SESSION['message_type'] = 'success';
                    header('Location: performance-reviews.php');
                    exit();
                    
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    $message = 'Error saving review: ' . $e->getMessage();
                    $message_type = 'error';
                }
                break;
        }
    }
}

include 'includes/header.php';
?>

<main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="max-w-6xl mx-auto">
                <!-- Header Section -->
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Conduct Performance Review</h1>
                            <p class="text-gray-600 mt-2">Evaluate employee performance and set development goals</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <a href="performance-reviews.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors duration-200">
                                <i class="fas fa-arrow-left"></i> Back to Reviews
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Message Display -->
                <?php if (!empty($message)): ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <!-- Employee Information -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Employee Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-16 w-16">
                                <div class="h-16 w-16 rounded-full bg-green-100 flex items-center justify-center">
                                    <span class="text-xl font-medium text-green-800">
                                        <?php echo strtoupper(substr($review['first_name'], 0, 1) . substr($review['last_name'], 0, 1)); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-lg font-semibold text-gray-900">
                                    <?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?>
                                </h4>
                                <p class="text-gray-600"><?php echo htmlspecialchars($review['position']); ?></p>
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($review['department']); ?></p>
                            </div>
                        </div>
                        
                        <div>
                            <h5 class="font-medium text-gray-900 mb-2">Review Details</h5>
                            <div class="space-y-1 text-sm text-gray-600">
                                <p><span class="font-medium">Type:</span> <?php echo ucfirst(str_replace('_', ' ', $review['review_type'])); ?></p>
                                <p><span class="font-medium">Period:</span> <?php echo date('M j, Y', strtotime($review['review_period_start'])); ?> - <?php echo date('M j, Y', strtotime($review['review_period_end'])); ?></p>
                                <p><span class="font-medium">Status:</span> 
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        <?php echo ucfirst(str_replace('_', ' ', $review['status'])); ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                        
                        <div>
                            <h5 class="font-medium text-gray-900 mb-2">Contact Information</h5>
                            <div class="space-y-1 text-sm text-gray-600">
                                <p><i class="fas fa-envelope mr-2"></i><?php echo htmlspecialchars($review['email']); ?></p>
                                <?php if ($review['phone']): ?>
                                <p><i class="fas fa-phone mr-2"></i><?php echo htmlspecialchars($review['phone']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Performance Review Form -->
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="save_review">
                    
                    <!-- Performance Categories -->
                    <?php foreach ($categories as $category): ?>
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($category['name']); ?></h3>
                            <span class="text-sm text-gray-500">Weight: <?php echo number_format($category['weight_percentage'], 1); ?>%</span>
                        </div>
                        <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($category['description']); ?></p>
                        
                        <?php if (isset($criteria[$category['id']])): ?>
                        <div class="space-y-4">
                            <?php foreach ($criteria[$category['id']] as $criterion): ?>
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex-1">
                                        <h4 class="font-medium text-gray-900"><?php echo htmlspecialchars($criterion['criteria_name']); ?></h4>
                                        <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($criterion['description']); ?></p>
                                    </div>
                                    <span class="text-sm text-gray-500">Max: <?php echo number_format($criterion['max_score'], 1); ?></span>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Score (0 - <?php echo number_format($criterion['max_score'], 1); ?>)</label>
                                        <input type="number" 
                                               name="scores[<?php echo $criterion['id']; ?>][score]" 
                                               value="<?php echo isset($existing_scores[$criterion['id']]) ? $existing_scores[$criterion['id']]['score'] : ''; ?>"
                                               min="0" 
                                               max="<?php echo $criterion['max_score']; ?>" 
                                               step="0.1"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Comments</label>
                                        <textarea name="scores[<?php echo $criterion['id']; ?>][comments]" 
                                                  rows="2"
                                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                                  placeholder="Add comments about this criteria..."><?php echo isset($existing_scores[$criterion['id']]) ? htmlspecialchars($existing_scores[$criterion['id']]['comments']) : ''; ?></textarea>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>

                    <!-- Overall Assessment -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Overall Assessment</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Overall Rating (0-5)</label>
                                <input type="number" 
                                       name="overall_rating" 
                                       value="<?php echo $review['overall_rating'] ? $review['overall_rating'] : ''; ?>"
                                       min="0" 
                                       max="5" 
                                       step="0.1"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Overall Percentage (0-100)</label>
                                <input type="number" 
                                       name="overall_percentage" 
                                       value="<?php echo $review['overall_percentage'] ? $review['overall_percentage'] : ''; ?>"
                                       min="0" 
                                       max="100" 
                                       step="0.1"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            </div>
                        </div>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Goals Achieved</label>
                                <textarea name="goals_achieved" 
                                          rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                          placeholder="Describe the goals that were achieved during this review period..."><?php echo htmlspecialchars($review['goals_achieved'] ?? ''); ?></textarea>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Areas of Strength</label>
                                <textarea name="areas_of_strength" 
                                          rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                          placeholder="Highlight the employee's key strengths and positive contributions..."><?php echo htmlspecialchars($review['areas_of_strength'] ?? ''); ?></textarea>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Areas for Improvement</label>
                                <textarea name="areas_for_improvement" 
                                          rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                          placeholder="Identify areas where the employee can improve and grow..."><?php echo htmlspecialchars($review['areas_for_improvement'] ?? ''); ?></textarea>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Development Plan</label>
                                <textarea name="development_plan" 
                                          rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                          placeholder="Outline specific development activities and training opportunities..."><?php echo htmlspecialchars($review['development_plan'] ?? ''); ?></textarea>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Recommendations</label>
                                <textarea name="recommendations" 
                                          rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                          placeholder="Provide specific recommendations for the employee's continued growth..."><?php echo htmlspecialchars($review['recommendations'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Goals Section -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Performance Goals</h3>
                            <button type="button" onclick="addGoal()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm transition-colors duration-200">
                                <i class="fas fa-plus"></i> Add Goal
                            </button>
                        </div>
                        
                        <div id="goals-container" class="space-y-4">
                            <?php foreach ($existing_goals as $index => $goal): ?>
                            <div class="goal-item border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="font-medium text-gray-900">Goal <?php echo $index + 1; ?></h4>
                                    <button type="button" onclick="removeGoal(this)" class="text-red-600 hover:text-red-800">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Goal Title</label>
                                        <input type="text" 
                                               name="goals[<?php echo $index; ?>][title]" 
                                               value="<?php echo htmlspecialchars($goal['goal_title']); ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Target Date</label>
                                        <input type="date" 
                                               name="goals[<?php echo $index; ?>][target_date]" 
                                               value="<?php echo $goal['target_date']; ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                        <select name="goals[<?php echo $index; ?>][status]" 
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                            <option value="not_started" <?php echo $goal['achievement_status'] === 'not_started' ? 'selected' : ''; ?>>Not Started</option>
                                            <option value="in_progress" <?php echo $goal['achievement_status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                            <option value="completed" <?php echo $goal['achievement_status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="overdue" <?php echo $goal['achievement_status'] === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                                            <option value="cancelled" <?php echo $goal['achievement_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Achievement %</label>
                                        <input type="number" 
                                               name="goals[<?php echo $index; ?>][percentage]" 
                                               value="<?php echo $goal['achievement_percentage']; ?>"
                                               min="0" 
                                               max="100" 
                                               step="1"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Goal Description</label>
                                    <textarea name="goals[<?php echo $index; ?>][description]" 
                                              rows="2"
                                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                              placeholder="Describe the goal in detail..."><?php echo htmlspecialchars($goal['goal_description']); ?></textarea>
                                </div>
                                
                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Comments</label>
                                    <textarea name="goals[<?php echo $index; ?>][comments]" 
                                              rows="2"
                                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                              placeholder="Add comments about this goal..."><?php echo htmlspecialchars($goal['comments']); ?></textarea>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Comments Section -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Additional Comments</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Manager Comments</label>
                                <textarea name="manager_comments" 
                                          rows="4"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                          placeholder="Add any additional manager comments..."><?php echo htmlspecialchars($review['manager_comments'] ?? ''); ?></textarea>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Employee Comments</label>
                                <textarea name="employee_comments" 
                                          rows="4"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                          placeholder="Employee can add their own comments here..."><?php echo htmlspecialchars($review['employee_comments'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Review Settings -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Review Settings</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Review Status</label>
                                <select name="status" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                    <option value="draft" <?php echo $review['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                    <option value="in_progress" <?php echo $review['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="completed" <?php echo $review['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="approved" <?php echo $review['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Next Review Date</label>
                                <input type="date" 
                                       name="next_review_date" 
                                       value="<?php echo $review['next_review_date']; ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end gap-4">
                        <a href="performance-reviews.php" 
                           class="px-6 py-3 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors duration-200">
                            Cancel
                        </a>
                        <button type="submit" 
                                class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors duration-200">
                            <i class="fas fa-save"></i> Save Review
                        </button>
                    </div>
                </form>
            </div>
        </main>

<script>
let goalIndex = <?php echo count($existing_goals); ?>;

function addGoal() {
    const container = document.getElementById('goals-container');
    const goalHtml = `
        <div class="goal-item border border-gray-200 rounded-lg p-4">
            <div class="flex items-center justify-between mb-3">
                <h4 class="font-medium text-gray-900">Goal ${goalIndex + 1}</h4>
                <button type="button" onclick="removeGoal(this)" class="text-red-600 hover:text-red-800">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Goal Title</label>
                    <input type="text" 
                           name="goals[${goalIndex}][title]" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Target Date</label>
                    <input type="date" 
                           name="goals[${goalIndex}][target_date]" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select name="goals[${goalIndex}][status]" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <option value="not_started">Not Started</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="overdue">Overdue</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Achievement %</label>
                    <input type="number" 
                           name="goals[${goalIndex}][percentage]" 
                           value="0"
                           min="0" 
                           max="100" 
                           step="1"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                </div>
            </div>
            
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Goal Description</label>
                <textarea name="goals[${goalIndex}][description]" 
                          rows="2"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                          placeholder="Describe the goal in detail..."></textarea>
            </div>
            
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Comments</label>
                <textarea name="goals[${goalIndex}][comments]" 
                          rows="2"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                          placeholder="Add comments about this goal..."></textarea>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', goalHtml);
    goalIndex++;
}

function removeGoal(button) {
    if (confirm('Are you sure you want to remove this goal?')) {
        button.closest('.goal-item').remove();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
