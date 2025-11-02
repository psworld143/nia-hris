<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/id_encryption.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin', 'admin', 'human_resource', 'hr_manager'])) {
    header('Location: index.php');
    exit();
}

// Set page title
$page_title = 'View Performance Review';

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

// Get review details with employee information
$review_query = "SELECT 
    pr.*,
    e.first_name,
    e.last_name,
    e.email,
    e.department,
    e.position,
    e.phone,
    u.username as reviewer_name,
    u2.username as approved_by_name
FROM performance_reviews pr
LEFT JOIN employees e ON pr.employee_id = e.id
LEFT JOIN users u ON pr.reviewer_id = u.id
LEFT JOIN users u2 ON pr.approved_by = u2.id
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

// Get performance review categories and criteria with scores
$categories_query = "SELECT 
    prc.*
FROM performance_review_categories prc
WHERE prc.is_active = 1
ORDER BY prc.id";
$categories_result = mysqli_query($conn, $categories_query);
$categories = [];
while ($row = mysqli_fetch_assoc($categories_result)) {
    $categories[] = $row;
}

// Get criteria with scores for this review
$criteria_scores_query = "SELECT 
    prc.*,
    prc2.category_name,
    prc2.weight_percentage as category_weight,
    prs.score,
    prs.comments as score_comments,
    (prs.score / prc.max_score) * 100 as percentage_score
FROM performance_review_criteria prc
LEFT JOIN performance_review_categories prc2 ON prc.category_id = prc2.id
LEFT JOIN performance_review_scores prs ON prc.id = prs.criteria_id AND prs.performance_review_id = ?
WHERE prc.is_active = 1
ORDER BY prc.category_id, prc.display_order";
$stmt = mysqli_prepare($conn, $criteria_scores_query);
mysqli_stmt_bind_param($stmt, "i", $review_id);
mysqli_stmt_execute($stmt);
$criteria_result = mysqli_stmt_get_result($stmt);
$criteria_scores = [];
while ($row = mysqli_fetch_assoc($criteria_result)) {
    $criteria_scores[$row['category_id']][] = $row;
}

// Get goals for this review
$goals_query = "SELECT * FROM performance_review_goals WHERE performance_review_id = ? ORDER BY id";
$stmt = mysqli_prepare($conn, $goals_query);
mysqli_stmt_bind_param($stmt, "i", $review_id);
mysqli_stmt_execute($stmt);
$goals_result = mysqli_stmt_get_result($stmt);
$goals = [];
while ($row = mysqli_fetch_assoc($goals_result)) {
    $goals[] = $row;
}

// Get attachments for this review (if table exists)
$attachments = [];
// Check if table exists first
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'performance_review_attachments'");
if (mysqli_num_rows($table_check) > 0) {
    try {
        $attachments_query = "SELECT 
            pra.*,
            u.username as uploaded_by_name
        FROM performance_review_attachments pra
        LEFT JOIN users u ON pra.uploaded_by = u.id
        WHERE pra.performance_review_id = ? 
        ORDER BY pra.created_at DESC";
        $stmt = mysqli_prepare($conn, $attachments_query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $review_id);
            if (mysqli_stmt_execute($stmt)) {
    $attachments_result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($attachments_result)) {
        $attachments[] = $row;
    }
            }
            mysqli_stmt_close($stmt);
        }
    } catch (mysqli_sql_exception $e) {
        // Error fetching attachments, attachments will remain empty array
        $attachments = [];
    } catch (Exception $e) {
        // Any other exception, attachments will remain empty array
        $attachments = [];
    }
}

// Calculate overall statistics
$total_weighted_score = 0;
$total_weight = 0;
foreach ($criteria_scores as $category_id => $criteria_list) {
    foreach ($criteria_list as $criterion) {
        if ($criterion['score'] !== null) {
            $total_weighted_score += $criterion['weighted_score'];
            $total_weight += $criterion['weight_percentage'];
        }
    }
}

$calculated_overall_percentage = $total_weight > 0 ? ($total_weighted_score / $total_weight) * 100 : 0;

include 'includes/header.php';
?>

<!-- Page Header -->
<div class="mb-6">
    <div class="bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div>
    <h2 class="text-2xl font-bold mb-2">
        <i class="fas fa-star mr-2"></i>Performance Review Details
    </h2>
    <p class="opacity-90">View detailed performance evaluation and assessment</p>
            </div>
            <div class="flex items-center gap-3">
    <?php if ($review['status'] === 'draft' || $review['status'] === 'in_progress'): ?>
    <a href="conduct-performance-review.php?id=<?php echo safe_encrypt_id($review['id']); ?>" 
       class="bg-white text-green-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
        <i class="fas fa-edit mr-2"></i>Edit Review
    </a>
    <?php endif; ?>
    <a href="performance-reviews.php" 
       class="bg-white/20 hover:bg-white/30 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
        <i class="fas fa-arrow-left mr-2"></i>Back to Reviews
    </a>
            </div>
        </div>
    </div>
</div>

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
        <?php
        $status_colors = [
            'draft' => 'bg-gray-100 text-gray-800',
            'in_progress' => 'bg-yellow-100 text-yellow-800',
            'completed' => 'bg-green-100 text-green-800',
            'approved' => 'bg-blue-100 text-blue-800',
            'rejected' => 'bg-red-100 text-red-800'
        ];
        $status_color = $status_colors[$review['status']] ?? 'bg-gray-100 text-gray-800';
        ?>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $status_color; ?>">
            <?php echo ucfirst(str_replace('_', ' ', $review['status'])); ?>
        </span>
    </p>
            </div>
        </div>
        
        <div>
            <h5 class="font-medium text-gray-900 mb-2">Review Information</h5>
            <div class="space-y-1 text-sm text-gray-600">
    <p><span class="font-medium">Reviewed by:</span> <?php echo htmlspecialchars($review['reviewer_name']); ?></p>
    <p><span class="font-medium">Created:</span> <?php echo date('M j, Y g:i A', strtotime($review['created_at'])); ?></p>
    <?php if ($review['updated_at'] !== $review['created_at']): ?>
    <p><span class="font-medium">Updated:</span> <?php echo date('M j, Y g:i A', strtotime($review['updated_at'])); ?></p>
    <?php endif; ?>
    <?php if ($review['approved_by']): ?>
    <p><span class="font-medium">Approved by:</span> <?php echo htmlspecialchars($review['approved_by_name']); ?></p>
    <p><span class="font-medium">Approved:</span> <?php echo date('M j, Y g:i A', strtotime($review['approved_at'])); ?></p>
    <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Overall Rating Summary -->
<?php if ($review['overall_rating'] || $review['overall_percentage']): ?>
<div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 mb-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Overall Performance Summary</h3>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <?php if ($review['overall_rating']): ?>
        <div class="text-center">
            <div class="text-4xl font-bold text-indigo-600 mb-2">
    <?php echo number_format((float)$review['overall_rating'], 1); ?>/5.0
            </div>
            <p class="text-sm text-gray-600">Overall Rating</p>
        </div>
        <?php endif; ?>
        
        <?php if ($review['overall_percentage']): ?>
        <div class="text-center">
            <div class="text-4xl font-bold text-green-600 mb-2">
    <?php echo number_format((float)$review['overall_percentage'], 1); ?>%
            </div>
            <p class="text-sm text-gray-600">Overall Percentage</p>
        </div>
        <?php endif; ?>
        
        <?php if ($calculated_overall_percentage > 0): ?>
        <div class="text-center">
            <div class="text-4xl font-bold text-blue-600 mb-2">
    <?php echo number_format((float)$calculated_overall_percentage, 1); ?>%
            </div>
            <p class="text-sm text-gray-600">Calculated Score</p>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Performance Categories -->
<?php foreach ($categories as $category): ?>
<?php if (isset($criteria_scores[$category['id']])): ?>
<div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($category['name']); ?></h3>
            <span class="text-sm text-gray-500">Weight: <?php echo number_format((float)$category['weight_percentage'], 1); ?>%</span>
        </div>
        <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($category['description']); ?></p>
        
        <div class="space-y-4">
            <?php 
            $category_total_score = 0;
            $category_total_weight = 0;
            $category_max_score = 0;
            ?>
            <?php foreach ($criteria_scores[$category['id']] as $criterion): ?>
            <div class="border border-gray-200 rounded-lg p-4">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex-1">
                        <h4 class="font-medium text-gray-900"><?php echo htmlspecialchars($criterion['criteria_name']); ?></h4>
                        <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($criterion['description']); ?></p>
                    </div>
                    <div class="text-right">
                        <?php if ($criterion['score'] !== null): ?>
                        <div class="text-2xl font-bold text-green-600">
                            <?php echo number_format((float)$criterion['score'], 1); ?>/<?php echo number_format((float)$criterion['max_score'], 1); ?>
                        </div>
                        <div class="text-sm text-gray-500">
                            <?php echo number_format((float)$criterion['percentage_score'], 1); ?>%
                        </div>
                        <?php else: ?>
                        <div class="text-2xl font-bold text-gray-400">
                            Not Scored
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($criterion['score_comments']): ?>
                <div class="bg-gray-50 rounded-lg p-3">
                    <p class="text-sm text-gray-700"><?php echo nl2br(htmlspecialchars($criterion['score_comments'])); ?></p>
                </div>
                <?php endif; ?>
                
                <?php
                if ($criterion['score'] !== null) {
                    $category_total_score += $criterion['weighted_score'];
                    $category_total_weight += $criterion['weight_percentage'];
                    $category_max_score += $criterion['max_score'] * ($criterion['weight_percentage'] / 100);
                }
                ?>
            </div>
            <?php endforeach; ?>
            
            <!-- Category Summary -->
            <?php if ($category_total_weight > 0): ?>
            <div class="bg-indigo-50 rounded-lg p-4 border border-indigo-200">
                <div class="flex items-center justify-between">
                    <span class="font-medium text-indigo-900">Category Score:</span>
                    <span class="text-xl font-bold text-indigo-600">
                        <?php echo number_format((float)(($category_total_score / $category_total_weight) * 100), 1); ?>%
                    </span>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endforeach; ?>

    <!-- Overall Assessment -->
    <?php if ($review['goals_achieved'] || $review['areas_of_strength'] || $review['areas_for_improvement'] || $review['development_plan'] || $review['recommendations']): ?>
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Overall Assessment</h3>
        
        <div class="space-y-6">
            <?php if ($review['goals_achieved']): ?>
            <div>
                <h4 class="font-medium text-gray-900 mb-2">Goals Achieved</h4>
                <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                    <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($review['goals_achieved'])); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($review['areas_of_strength']): ?>
            <div>
                <h4 class="font-medium text-gray-900 mb-2">Areas of Strength</h4>
                <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                    <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($review['areas_of_strength'])); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($review['areas_for_improvement']): ?>
            <div>
                <h4 class="font-medium text-gray-900 mb-2">Areas for Improvement</h4>
                <div class="bg-yellow-50 rounded-lg p-4 border border-yellow-200">
                    <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($review['areas_for_improvement'])); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($review['development_plan']): ?>
            <div>
                <h4 class="font-medium text-gray-900 mb-2">Development Plan</h4>
                <div class="bg-purple-50 rounded-lg p-4 border border-purple-200">
                    <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($review['development_plan'])); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($review['recommendations']): ?>
            <div>
                <h4 class="font-medium text-gray-900 mb-2">Recommendations</h4>
                <div class="bg-indigo-50 rounded-lg p-4 border border-indigo-200">
                    <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($review['recommendations'])); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Performance Goals -->
    <?php if (!empty($goals)): ?>
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Performance Goals</h3>
        
        <div class="space-y-4">
            <?php foreach ($goals as $index => $goal): ?>
            <div class="border border-gray-200 rounded-lg p-4">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex-1">
                        <h4 class="font-medium text-gray-900"><?php echo htmlspecialchars($goal['goal_title']); ?></h4>
                        <?php if ($goal['goal_description']): ?>
                        <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($goal['goal_description']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="text-right">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php
                            $status_colors = [
                                'not_started' => 'bg-gray-100 text-gray-800',
                                'in_progress' => 'bg-yellow-100 text-yellow-800',
                                'completed' => 'bg-green-100 text-green-800',
                                'overdue' => 'bg-red-100 text-red-800',
                                'cancelled' => 'bg-gray-100 text-gray-800'
                            ];
                            echo $status_colors[$goal['achievement_status']] ?? 'bg-gray-100 text-gray-800';
                        ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $goal['achievement_status'])); ?>
                        </span>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <?php if ($goal['target_date']): ?>
                    <div>
                        <span class="font-medium text-gray-700">Target Date:</span>
                        <span class="text-gray-600"><?php echo date('M j, Y', strtotime($goal['target_date'])); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div>
                        <span class="font-medium text-gray-700">Achievement:</span>
                        <span class="text-gray-600"><?php echo number_format((float)$goal['achievement_percentage'], 1); ?>%</span>
                    </div>
                    
                    <div class="md:col-span-1">
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-green-600 h-2 rounded-full" style="width: <?php echo min(100, (float)$goal['achievement_percentage']); ?>%"></div>
                        </div>
                    </div>
                </div>
                
                <?php if ($goal['comments']): ?>
                <div class="mt-3 bg-gray-50 rounded-lg p-3">
                    <p class="text-sm text-gray-700"><?php echo nl2br(htmlspecialchars($goal['comments'])); ?></p>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Comments Section -->
    <?php if ($review['manager_comments'] || $review['employee_comments']): ?>
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Additional Comments</h3>
        
        <div class="space-y-4">
            <?php if ($review['manager_comments']): ?>
            <div>
                <h4 class="font-medium text-gray-900 mb-2">Manager Comments</h4>
                <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                    <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($review['manager_comments'])); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($review['employee_comments']): ?>
            <div>
                <h4 class="font-medium text-gray-900 mb-2">Employee Comments</h4>
                <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                    <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($review['employee_comments'])); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Attachments -->
    <?php if (!empty($attachments)): ?>
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Attachments</h3>
        
        <div class="space-y-3">
            <?php foreach ($attachments as $attachment): ?>
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">
                <div class="flex items-center">
                    <i class="fas fa-file-alt text-gray-400 mr-3"></i>
                    <div>
                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($attachment['file_name']); ?></p>
                        <p class="text-sm text-gray-500">
                            <?php echo number_format((float)$attachment['file_size'] / 1024, 1); ?> KB • 
                            Uploaded by <?php echo htmlspecialchars($attachment['uploaded_by_name']); ?> on 
                            <?php echo date('M j, Y g:i A', strtotime($attachment['created_at'])); ?>
                        </p>
                    </div>
                </div>
                <a href="<?php echo htmlspecialchars($attachment['file_path']); ?>" 
                   target="_blank" 
                   class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                    <i class="fas fa-download"></i>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Review Timeline -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Review Timeline</h3>
        
        <div class="space-y-4">
            <div class="flex items-center">
                <div class="flex-shrink-0 w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-plus text-green-600 text-sm"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-900">Review Created</p>
                    <p class="text-sm text-gray-500"><?php echo date('M j, Y g:i A', strtotime($review['created_at'])); ?> by <?php echo htmlspecialchars($review['reviewer_name']); ?></p>
                </div>
            </div>
            
            <?php if ($review['updated_at'] !== $review['created_at']): ?>
            <div class="flex items-center">
                <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-edit text-blue-600 text-sm"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-900">Review Updated</p>
                    <p class="text-sm text-gray-500"><?php echo date('M j, Y g:i A', strtotime($review['updated_at'])); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($review['approved_by']): ?>
            <div class="flex items-center">
                <div class="flex-shrink-0 w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-check text-indigo-600 text-sm"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-900">Review Approved</p>
                    <p class="text-sm text-gray-500"><?php echo date('M j, Y g:i A', strtotime($review['approved_at'])); ?> by <?php echo htmlspecialchars($review['approved_by_name']); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($review['next_review_date']): ?>
            <div class="flex items-center">
                <div class="flex-shrink-0 w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-calendar text-yellow-600 text-sm"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-900">Next Review Scheduled</p>
                    <p class="text-sm text-gray-500"><?php echo date('M j, Y', strtotime($review['next_review_date'])); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex justify-end gap-4">
        <a href="performance-reviews.php" 
           class="px-6 py-3 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors duration-200">
            Back to Reviews
        </a>
        
        <?php if ($review['status'] === 'draft' || $review['status'] === 'in_progress'): ?>
        <a href="conduct-performance-review.php?id=<?php echo safe_encrypt_id($review['id']); ?>" 
           class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors duration-200">
            <i class="fas fa-edit"></i> Edit Review
        </a>
        <?php endif; ?>
        
        <button onclick="window.print()" 
                class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200">
            <i class="fas fa-print"></i> Print Review
        </button>
    </div>

<style>
@media print {
    .no-print {
        display: none !important;
    }
    
    body {
        background: white !important;
    }
    
    .bg-gray-50 {
        background: white !important;
    }
    
    .shadow-sm {
        box-shadow: none !important;
    }
    
    .border {
        border: 1px solid #e5e7eb !important;
    }
}
</style>

