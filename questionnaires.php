<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/id_encryption.php';

// Check if user is logged in and has HR role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['human_resource', 'hr_manager'])) {
    header('Location: login.php');
    exit();
}

// Get sub_category_id from URL
$sub_category_id = isset($_GET['sub_category_id']) ? (int)$_GET['sub_category_id'] : null;

if (!$sub_category_id) {
    header('Location: evaluations.php');
    exit();
}

// Get sub-category details
$sub_category_query = "SELECT esc.*, mec.name as main_category_name, mec.id as main_category_id
                       FROM evaluation_sub_categories esc
                       JOIN main_evaluation_categories mec ON esc.main_category_id = mec.id
                       WHERE esc.id = ? AND esc.status = 'active' AND mec.evaluation_type = 'peer_to_peer'";
$sub_category_stmt = mysqli_prepare($conn, $sub_category_query);
mysqli_stmt_bind_param($sub_category_stmt, "i", $sub_category_id);
mysqli_stmt_execute($sub_category_stmt);
$sub_category_result = mysqli_stmt_get_result($sub_category_stmt);
$sub_category = mysqli_fetch_assoc($sub_category_result);

if (!$sub_category) {
    header('Location: evaluations.php');
    exit();
}

// Set page title
$page_title = 'Questionnaires - ' . $sub_category['name'];

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_SESSION['role'], ['human_resource', 'hr_manager'])) {
    if (isset($_POST['action']) && $_POST['action'] === 'add_questionnaire') {
        $question_text = trim($_POST['question_text']);
        $question_type = $_POST['question_type'];
        $order_number = (int)$_POST['order_number'];
        
        if (empty($question_text)) {
            $message = 'Question text is required.';
            $message_type = 'error';
        } else {
            $insert_question_query = "INSERT INTO evaluation_questionnaires (sub_category_id, question_text, question_type, order_number, status, created_by) 
                                     VALUES (?, ?, ?, ?, 'active', ?)";
            $insert_question_stmt = mysqli_prepare($conn, $insert_question_query);
            $created_by = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
            mysqli_stmt_bind_param($insert_question_stmt, "issii", $sub_category_id, $question_text, $question_type, $order_number, $created_by);
            
            if (mysqli_stmt_execute($insert_question_stmt)) {
                $message = 'Question added successfully.';
                $message_type = 'success';
            } else {
                $message = 'Error adding question: ' . mysqli_error($conn);
                $message_type = 'error';
            }
        }
    }
}

// Check for session messages
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// Get questionnaires for this sub-category
$questionnaires_query = "SELECT * FROM evaluation_questionnaires 
                         WHERE sub_category_id = ? AND status = 'active' 
                         ORDER BY order_number ASC";
$questionnaires_stmt = mysqli_prepare($conn, $questionnaires_query);
mysqli_stmt_bind_param($questionnaires_stmt, "i", $sub_category_id);
mysqli_stmt_execute($questionnaires_stmt);
$questionnaires_result = mysqli_stmt_get_result($questionnaires_stmt);
$questionnaires = [];
while ($row = mysqli_fetch_assoc($questionnaires_result)) {
    $questionnaires[] = $row;
}

// Include header
include 'includes/header.php';
?>

<div class="mb-6 sm:mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-seait-dark mb-2">Questionnaires</h1>
            <p class="text-sm sm:text-base text-gray-600">
                Sub-Category: <span class="font-medium text-purple-600"><?php echo htmlspecialchars($sub_category['name']); ?></span>
                <span class="text-gray-400 mx-2">•</span>
                Category: <span class="font-medium text-purple-600"><?php echo htmlspecialchars($sub_category['main_category_name']); ?></span>
            </p>
        </div>
        <a href="peer-evaluation-progress.php?main_category_id=<?php echo safe_encrypt_id($sub_category['main_category_id']); ?>" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition">
            <i class="fas fa-arrow-left mr-2"></i>Back
        </a>
    </div>
</div>

<!-- Message Display -->
<?php if ($message): ?>
<div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-red-100 text-red-700 border border-red-200'; ?>">
    <div class="flex items-center">
        <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-2"></i>
        <?php echo $message; ?>
    </div>
</div>
<?php endif; ?>

<!-- Sub-Category Info -->
<div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($sub_category['name']); ?></h2>
    </div>
    <div class="p-6">
        <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($sub_category['description']); ?></p>
        <div class="flex items-center text-sm text-gray-500">
            <i class="fas fa-question-circle mr-1"></i>
            <?php echo count($questionnaires); ?> questions
        </div>
    </div>
</div>

<!-- Add New Question (HR Manager Only) -->
<?php if (in_array($_SESSION['role'], ['human_resource', 'hr_manager'])): ?>
<div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-medium text-gray-900">Add New Question</h2>
    </div>
    <div class="p-6">
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="add_questionnaire">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Question Text</label>
                <textarea name="question_text" rows="3" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Enter the evaluation question..."></textarea>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Question Type</label>
                    <select name="question_type" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="">Select question type</option>
                        <option value="rating_1_5">Rating (1-5 Scale)</option>
                        <option value="text">Text Response</option>
                        <option value="yes_no">Yes/No</option>
                        <option value="multiple_choice">Multiple Choice</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Order Number</label>
                    <input type="number" name="order_number" value="<?php echo count($questionnaires) + 1; ?>" min="1" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
            </div>
            
            <div class="flex justify-end">
                <button type="submit" class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition">
                    <i class="fas fa-plus mr-2"></i>Add Question
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Questionnaires List -->
<div class="space-y-4">
    <?php if (empty($questionnaires)): ?>
        <div class="bg-white rounded-lg shadow-md p-8 text-center">
            <i class="fas fa-question-circle text-gray-300 text-6xl mb-4"></i>
            <p class="text-gray-500">No questions found for this sub-category.</p>
            <?php if (in_array($_SESSION['role'], ['human_resource', 'hr_manager'])): ?>
            <p class="text-gray-400 text-sm mt-2">Use the form above to add questions.</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <?php foreach ($questionnaires as $index => $questionnaire): ?>
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-6">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center mb-3">
                            <span class="inline-block bg-purple-500 text-white text-sm font-bold px-3 py-1 rounded-full mr-3">
                                Q<?php echo $questionnaire['order_number']; ?>
                            </span>
                            <span class="px-2 py-1 text-xs rounded-full <?php
                                switch($questionnaire['question_type']) {
                                    case 'rating_1_5':
                                        echo 'bg-blue-100 text-blue-800';
                                        break;
                                    case 'text':
                                        echo 'bg-green-100 text-green-800';
                                        break;
                                    case 'yes_no':
                                        echo 'bg-yellow-100 text-yellow-800';
                                        break;
                                    case 'multiple_choice':
                                        echo 'bg-purple-100 text-purple-800';
                                        break;
                                    default:
                                        echo 'bg-gray-100 text-gray-800';
                                }
                            ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $questionnaire['question_type'])); ?>
                                <?php if ($questionnaire['question_type'] === 'rating_1_5'): ?>
                                    (1-5)
                                <?php endif; ?>
                            </span>
                        </div>
                        <p class="text-gray-900 leading-relaxed"><?php echo htmlspecialchars($questionnaire['question_text']); ?></p>
                        
                        <div class="mt-3 flex items-center text-sm text-gray-500">
                            <span class="mr-4">
                                <i class="fas fa-sort-numeric-up mr-1"></i>
                                Order: <?php echo $questionnaire['order_number']; ?>
                            </span>
                            <span class="text-red-500">
                                <i class="fas fa-asterisk mr-1"></i>
                                Required
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Question Type Preview -->
                <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Preview:</h4>
                    <?php switch($questionnaire['question_type']): 
                        case 'rating_1_5': ?>
                            <div class="flex space-x-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <div class="w-8 h-8 border-2 border-gray-300 rounded-lg flex items-center justify-center text-sm font-medium">
                                    <?php echo $i; ?>
                                </div>
                                <?php endfor; ?>
                            </div>
                            <?php break;
                        case 'text': ?>
                            <textarea class="w-full px-3 py-2 border border-gray-300 rounded-lg" rows="2" disabled placeholder="Text response area..."></textarea>
                            <?php break;
                        case 'yes_no': ?>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="radio" name="preview_<?php echo $questionnaire['id']; ?>" disabled class="mr-2">
                                    <span>Yes</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="preview_<?php echo $questionnaire['id']; ?>" disabled class="mr-2">
                                    <span>No</span>
                                </label>
                            </div>
                            <?php break;
                        case 'multiple_choice': ?>
                            <div class="text-gray-500 text-sm">Multiple choice options would be configured separately</div>
                            <?php break;
                    endswitch; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

