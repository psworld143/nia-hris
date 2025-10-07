<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in and has human_resource role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager'])) {
    header('Location: ../index.php');
    exit();
}

// Set page title
$page_title = 'Job Postings Management';

// Ensure upload directory exists and has proper permissions
function ensure_upload_directory($path) {
    if (!is_dir($path)) {
        if (!mkdir($path, 0755, true)) {
            error_log("Failed to create directory: " . $path);
            return false;
        }
    }
    return is_writable($path);
}

$message = '';
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Detect oversized POST (when PHP discards body, $_POST becomes empty)
    if (empty($_POST) && !empty($_SERVER['CONTENT_LENGTH'])) {
        $error_message = 'Upload too large. Please reduce file sizes.';
    } else {
        $title = isset($_POST['title']) ? sanitize_input($_POST['title']) : '';
        $content = isset($_POST['content']) ? $_POST['content'] : ''; // CKEditor content - keep HTML
        $position = isset($_POST['position']) ? sanitize_input($_POST['position']) : '';
        $department = isset($_POST['department']) ? sanitize_input($_POST['department']) : '';
        $employment_type = isset($_POST['employment_type']) ? sanitize_input($_POST['employment_type']) : '';
        $salary_range = isset($_POST['salary_range']) ? sanitize_input($_POST['salary_range']) : '';
        $requirements = isset($_POST['requirements']) ? $_POST['requirements'] : ''; // CKEditor content - keep HTML
        $benefits = isset($_POST['benefits']) ? $_POST['benefits'] : ''; // CKEditor content - keep HTML
        $application_deadline = isset($_POST['application_deadline']) ? $_POST['application_deadline'] : '';

        // Server-side validation
        if (trim($title) === '' || trim($content) === '' || trim($position) === '') {
            $error_message = 'Please complete Title, Position, and Job Description before submitting.';
        } else {
            // Handle image upload
            $image_url = NULL;
            if (isset($_FILES['job_image']) && $_FILES['job_image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../assets/images/news/';
                
                if (!ensure_upload_directory($upload_dir)) {
                    $error_message = 'Error: Could not create or access upload directory.';
                } else {
                    $file_tmp = $_FILES['job_image']['tmp_name'];
                    $file_name = basename($_FILES['job_image']['name']);
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    
                    if (in_array($file_ext, $allowed_exts)) {
                        $new_name = uniqid('job_', true) . '.' . $file_ext;
                        $dest_path = $upload_dir . $new_name;
                        if (move_uploaded_file($file_tmp, $dest_path)) {
                            $image_url = 'assets/images/news/' . $new_name;
                        } else {
                            $error_message = 'Error uploading job image. Please try again.';
                        }
                    } else {
                        $error_message = 'Invalid file type. Please upload JPG, PNG, or GIF files only.';
                    }
                }
            }

            if (empty($error_message)) {
                // Create job posting content with structured data
                // Content now comes from CKEditor and may contain HTML
                $job_content = $content;
                
                // Add structured job details
                if (!empty($requirements) || !empty($benefits) || !empty($salary_range) || !empty($position) || !empty($department) || !empty($employment_type) || !empty($application_deadline)) {
                    $job_content .= "\n\n<!-- Job Details -->\n";
                    
                    // Add job metadata section
                    $job_content .= "<div class=\"job-details-section\">\n";
                    
                    if (!empty($position)) {
                        $job_content .= "<h3>Position: " . htmlspecialchars($position) . "</h3>\n";
                    }
                    if (!empty($department)) {
                        $job_content .= "<p><strong>Department:</strong> " . htmlspecialchars($department) . "</p>\n";
                    }
                    if (!empty($employment_type)) {
                        $job_content .= "<p><strong>Employment Type:</strong> " . htmlspecialchars($employment_type) . "</p>\n";
                    }
                    if (!empty($salary_range)) {
                        $job_content .= "<p><strong>Salary Range:</strong> " . htmlspecialchars($salary_range) . "</p>\n";
                    }
                    if (!empty($application_deadline)) {
                        $job_content .= "<p><strong>Application Deadline:</strong> " . date('F j, Y', strtotime($application_deadline)) . "</p>\n";
                    }
                    
                    $job_content .= "</div>\n";
                    
                    // Add requirements section (from CKEditor - already contains HTML)
                    if (!empty($requirements)) {
                        $job_content .= "<div class=\"job-requirements-section\">\n";
                        $job_content .= "<h4>Requirements:</h4>\n" . $requirements . "\n";
                        $job_content .= "</div>\n";
                    }
                    
                    // Add benefits section (from CKEditor - already contains HTML)
                    if (!empty($benefits)) {
                        $job_content .= "<div class=\"job-benefits-section\">\n";
                        $job_content .= "<h4>Benefits & Perks:</h4>\n" . $benefits . "\n";
                        $job_content .= "</div>\n";
                    }
                }

                // Determine status based on action
                if (isset($_POST['action']) && $_POST['action'] === 'save_draft') {
                    $status = 'draft';
                    $success_message = 'Job posting draft saved successfully!';
                } else {
                    $status = 'pending'; // Submit for approval by Social Media Manager
                    $success_message = 'Job posting created successfully and submitted for approval by Social Media Manager!';
                }

                // Insert job posting into posts table with type 'hiring'
                $insert_query = "INSERT INTO posts (title, content, type, status, author_id, image_url, created_at) 
                                VALUES (?, ?, 'hiring', ?, ?, ?, NOW())";
                $stmt = mysqli_prepare($conn, $insert_query);
                
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "sssis", $title, $job_content, $status, $_SESSION['user_id'], $image_url);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        // Clear form data on success
                        $title = $content = $position = $department = $employment_type = $salary_range = $requirements = $benefits = $application_deadline = '';
                    } else {
                        $error_message = 'Error creating job posting: ' . mysqli_error($conn);
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $error_message = 'Database error: ' . mysqli_error($conn);
                }
            }
        }
    }
}

// Get job posting statistics
$stats_query = "SELECT 
    COUNT(*) as total_jobs,
    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_jobs,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_jobs,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_jobs,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_jobs
    FROM posts 
    WHERE type = 'hiring' AND author_id = ?";

$stats_stmt = mysqli_prepare($conn, $stats_query);
mysqli_stmt_bind_param($stats_stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stats_stmt);
$stats_result = mysqli_stmt_get_result($stats_stmt);
$stats = mysqli_fetch_assoc($stats_result);

// Get recent job postings
$recent_query = "SELECT id, title, status, created_at, updated_at 
                FROM posts 
                WHERE type = 'hiring' AND author_id = ? 
                ORDER BY created_at DESC 
                LIMIT 10";

$recent_stmt = mysqli_prepare($conn, $recent_query);
mysqli_stmt_bind_param($recent_stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($recent_stmt);
$recent_result = mysqli_stmt_get_result($recent_stmt);

// Include the header
include 'includes/header.php';
?>

<!-- Page Header -->
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Job Postings Management</h1>
            <p class="text-gray-600">Create and manage job postings for approval by Social Media Manager</p>
        </div>
        <div class="flex space-x-3">
            <a href="my-job-postings.php" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transform transition-all hover:scale-105 font-medium">
                <i class="fas fa-list mr-2"></i>My Job Postings
            </a>
        </div>
    </div>
</div>

<!-- Success/Error Messages -->
<?php if (!empty($success_message)): ?>
    <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-check-circle text-green-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-green-800"><?php echo htmlspecialchars($success_message); ?></p>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
    <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-circle text-red-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-red-800"><?php echo htmlspecialchars($error_message); ?></p>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                <i class="fas fa-briefcase text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Total Jobs</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_jobs']; ?></p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-gray-500">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-gray-100 text-gray-600">
                <i class="fas fa-edit text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Drafts</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['draft_jobs']; ?></p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-yellow-500">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                <i class="fas fa-clock text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Pending</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['pending_jobs']; ?></p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-600">
                <i class="fas fa-check text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Approved</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['approved_jobs']; ?></p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-red-500">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-red-100 text-red-600">
                <i class="fas fa-times text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Rejected</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['rejected_jobs']; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Job Posting Form -->
<div class="bg-white rounded-xl shadow-lg p-6 mb-8">
    <div class="flex items-center mb-6">
        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-4">
            <i class="fas fa-plus text-green-600 text-lg"></i>
        </div>
        <div>
            <h3 class="text-xl font-bold text-gray-900">Create New Job Posting</h3>
            <p class="text-gray-600 text-sm">Fill out the form below to create a new job posting</p>
        </div>
    </div>

    <form method="POST" enctype="multipart/form-data" class="space-y-6">
        <!-- Basic Information -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Job Title <span class="text-red-500">*</span></label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($title ?? ''); ?>" required
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="e.g., Software Engineer, Marketing Manager">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Position <span class="text-red-500">*</span></label>
                <input type="text" name="position" value="<?php echo htmlspecialchars($position ?? ''); ?>" required
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                       placeholder="e.g., Senior Software Engineer">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                <select name="department" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
                    <option value="">Select Department</option>
                    <option value="Information Technology" <?php echo ($department ?? '') === 'Information Technology' ? 'selected' : ''; ?>>Information Technology</option>
                    <option value="Computer Science" <?php echo ($department ?? '') === 'Computer Science' ? 'selected' : ''; ?>>Computer Science</option>
                    <option value="Engineering" <?php echo ($department ?? '') === 'Engineering' ? 'selected' : ''; ?>>Engineering</option>
                    <option value="Business Administration" <?php echo ($department ?? '') === 'Business Administration' ? 'selected' : ''; ?>>Business Administration</option>
                    <option value="Human Resources" <?php echo ($department ?? '') === 'Human Resources' ? 'selected' : ''; ?>>Human Resources</option>
                    <option value="Finance" <?php echo ($department ?? '') === 'Finance' ? 'selected' : ''; ?>>Finance</option>
                    <option value="Marketing" <?php echo ($department ?? '') === 'Marketing' ? 'selected' : ''; ?>>Marketing</option>
                    <option value="Academic Affairs" <?php echo ($department ?? '') === 'Academic Affairs' ? 'selected' : ''; ?>>Academic Affairs</option>
                    <option value="Student Affairs" <?php echo ($department ?? '') === 'Student Affairs' ? 'selected' : ''; ?>>Student Affairs</option>
                    <option value="Library Services" <?php echo ($department ?? '') === 'Library Services' ? 'selected' : ''; ?>>Library Services</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Employment Type</label>
                <select name="employment_type" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
                    <option value="">Select Type</option>
                    <option value="Full-time" <?php echo ($employment_type ?? '') === 'Full-time' ? 'selected' : ''; ?>>Full-time</option>
                    <option value="Part-time" <?php echo ($employment_type ?? '') === 'Part-time' ? 'selected' : ''; ?>>Part-time</option>
                    <option value="Contract" <?php echo ($employment_type ?? '') === 'Contract' ? 'selected' : ''; ?>>Contract</option>
                    <option value="Temporary" <?php echo ($employment_type ?? '') === 'Temporary' ? 'selected' : ''; ?>>Temporary</option>
                    <option value="Internship" <?php echo ($employment_type ?? '') === 'Internship' ? 'selected' : ''; ?>>Internship</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Application Deadline</label>
                <input type="date" name="application_deadline" value="<?php echo htmlspecialchars($application_deadline ?? ''); ?>"
                       class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Salary Range</label>
            <input type="text" name="salary_range" value="<?php echo htmlspecialchars($salary_range ?? ''); ?>"
                   class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                   placeholder="e.g., ₱30,000 - ₱50,000 per month">
        </div>

        <!-- Job Description -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Job Description <span class="text-red-500">*</span>
                <span id="job_description_loading" class="text-xs text-green-600 ml-2">
                    <i class="fas fa-spinner fa-spin"></i> Loading rich text editor...
                </span>
            </label>
            <textarea name="content" id="job_description" required rows="8"
                      class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                      placeholder="Provide a detailed description of the job role, responsibilities, and what the candidate will be doing..."><?php echo $content ?? ''; ?></textarea>
        </div>

        <!-- Requirements -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Requirements
                <span id="job_requirements_loading" class="text-xs text-green-600 ml-2">
                    <i class="fas fa-spinner fa-spin"></i> Loading rich text editor...
                </span>
            </label>
            <textarea name="requirements" id="job_requirements" rows="6"
                      class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                      placeholder="List the qualifications, skills, experience, and education requirements..."><?php echo $requirements ?? ''; ?></textarea>
        </div>

        <!-- Benefits -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Benefits & Perks
                <span id="job_benefits_loading" class="text-xs text-green-600 ml-2">
                    <i class="fas fa-spinner fa-spin"></i> Loading rich text editor...
                </span>
            </label>
            <textarea name="benefits" id="job_benefits" rows="4"
                      class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all"
                      placeholder="Describe the benefits, perks, and compensation package..."><?php echo $benefits ?? ''; ?></textarea>
        </div>

        <!-- Job Image -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Job Posting Image</label>
            <input type="file" name="job_image" accept="image/*"
                   class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
            <p class="text-sm text-gray-500 mt-1">Optional: Upload an image for the job posting (JPG, PNG, GIF)</p>
        </div>

        <!-- Rich Text Editor Info -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-edit text-blue-400"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">Rich Text Editor Features</h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <p>Use the rich text editors above to format your job posting with <strong>bold text</strong>, <em>italic text</em>, headings, lists, links, tables, and block quotes for professional presentation.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Approval Process Info -->
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-info-circle text-yellow-400"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">Job Posting Review Process</h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <p>Your job posting will be submitted for review by the Social Media Manager. Once approved, it will be published on the website and can be shared on social media platforms.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-4">
            <button type="submit" name="action" value="publish"
                    class="flex-1 bg-gradient-to-r from-green-500 to-green-600 text-white px-6 py-3 rounded-lg hover:from-green-600 hover:to-green-500 transform transition-all hover:scale-105 font-medium shadow-lg">
                <i class="fas fa-paper-plane mr-2"></i>Submit for Approval
            </button>
            
            <button type="submit" name="action" value="save_draft"
                    class="flex-1 bg-gray-500 text-white px-6 py-3 rounded-lg hover:bg-gray-600 transform transition-all hover:scale-105 font-medium">
                <i class="fas fa-save mr-2"></i>Save as Draft
            </button>
        </div>
    </form>
</div>

<!-- Recent Job Postings -->
<div class="bg-white rounded-xl shadow-lg p-6">
    <div class="flex items-center mb-6">
        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-4">
            <i class="fas fa-history text-blue-600 text-lg"></i>
        </div>
        <div>
            <h3 class="text-xl font-bold text-gray-900">Recent Job Postings</h3>
            <p class="text-gray-600 text-sm">Your latest job postings and their status</p>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job Title</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Updated</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (mysqli_num_rows($recent_result) > 0): ?>
                    <?php while ($job = mysqli_fetch_assoc($recent_result)): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($job['title']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $status_colors = [
                                    'draft' => 'bg-gray-100 text-gray-800',
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'approved' => 'bg-green-100 text-green-800',
                                    'rejected' => 'bg-red-100 text-red-800'
                                ];
                                ?>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $status_colors[$job['status']] ?? 'bg-gray-100 text-gray-800'; ?>">
                                    <?php echo ucfirst($job['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('M j, Y', strtotime($job['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('M j, Y', strtotime($job['updated_at'])); ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                            No job postings found. Create your first job posting above.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- CKEditor CDN -->
<script src="https://cdn.ckeditor.com/ckeditor5/40.2.0/classic/ckeditor.js"></script>

<script>
let jobDescriptionEditor, requirementsEditor, benefitsEditor;

// Debug: Check if CKEditor loaded
console.log('CKEditor script loaded, ClassicEditor available:', typeof ClassicEditor);

// Initialize CKEditor for all rich text fields
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing CKEditor...');
    
    // Add a small delay to ensure CKEditor script is fully loaded
    setTimeout(function() {
        console.log('Checking CKEditor availability...');
        
        // Check if CKEditor is available
        if (typeof ClassicEditor === 'undefined') {
            console.error('CKEditor not loaded! Using fallback textareas.');
            alert('Rich text editor failed to load. Please refresh the page or use basic text areas.');
            return;
        }
        
        initializeCKEditors();
    }, 500);
});

function initializeCKEditors() {
    
    // Check if elements exist
    const jobDescElement = document.querySelector('#job_description');
    const reqElement = document.querySelector('#job_requirements');
    const benefitsElement = document.querySelector('#job_benefits');
    
    console.log('Job description element:', jobDescElement);
    console.log('Requirements element:', reqElement);
    console.log('Benefits element:', benefitsElement);
    
    if (!jobDescElement || !reqElement || !benefitsElement) {
        console.error('One or more textarea elements not found!');
        return;
    }
    
    // Job Description Editor
    ClassicEditor
        .create(document.querySelector('#job_description'), {
            toolbar: {
                items: [
                    'heading', '|',
                    'bold', 'italic', '|',
                    'bulletedList', 'numberedList', '|',
                    'outdent', 'indent', '|',
                    'link', 'blockQuote', '|',
                    'insertTable', '|',
                    'undo', 'redo'
                ]
            },
            heading: {
                options: [
                    { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                    { model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1' },
                    { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                    { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' }
                ]
            },
            placeholder: 'Provide a detailed description of the job role, responsibilities, and what the candidate will be doing...',
            removePlugins: ['MediaEmbed', 'ImageUpload', 'ImageInsert', 'EasyImage', 'ImageResize', 'ImageStyle', 'ImageToolbar', 'ImageCaption'],
            table: {
                contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells']
            }
        })
        .then(editor => {
            jobDescriptionEditor = editor;
            console.log('Job description editor initialized successfully!');
            
            // Hide loading indicator
            const loadingIndicator = document.getElementById('job_description_loading');
            if (loadingIndicator) {
                loadingIndicator.style.display = 'none';
            }
            
            // Set initial content if available
            const initialContent = document.querySelector('#job_description').value;
            if (initialContent) {
                editor.setData(initialContent);
            }
            
            // Auto-save functionality
            editor.model.document.on('change:data', () => {
                autoSaveDraft();
            });
        })
        .catch(error => {
            console.error('Error initializing job description editor:', error);
            alert('Error loading job description editor: ' + error.message);
        });

    // Requirements Editor
    ClassicEditor
        .create(document.querySelector('#job_requirements'), {
            toolbar: {
                items: [
                    'heading', '|',
                    'bold', 'italic', '|',
                    'bulletedList', 'numberedList', '|',
                    'outdent', 'indent', '|',
                    'link', 'blockQuote', '|',
                    'insertTable', '|',
                    'undo', 'redo'
                ]
            },
            heading: {
                options: [
                    { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                    { model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1' },
                    { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                    { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' }
                ]
            },
            placeholder: 'List the qualifications, skills, experience, and education requirements...',
            removePlugins: ['MediaEmbed', 'ImageUpload', 'ImageInsert', 'EasyImage', 'ImageResize', 'ImageStyle', 'ImageToolbar', 'ImageCaption'],
            table: {
                contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells']
            }
        })
        .then(editor => {
            requirementsEditor = editor;
            console.log('Requirements editor initialized successfully!');
            
            // Hide loading indicator
            const loadingIndicator = document.getElementById('job_requirements_loading');
            if (loadingIndicator) {
                loadingIndicator.style.display = 'none';
            }
            
            // Set initial content if available
            const initialContent = document.querySelector('#job_requirements').value;
            if (initialContent) {
                editor.setData(initialContent);
            }
            
            // Auto-save functionality
            editor.model.document.on('change:data', () => {
                autoSaveDraft();
            });
        })
        .catch(error => {
            console.error('Error initializing requirements editor:', error);
            alert('Error loading requirements editor: ' + error.message);
        });

    // Benefits Editor
    ClassicEditor
        .create(document.querySelector('#job_benefits'), {
            toolbar: {
                items: [
                    'heading', '|',
                    'bold', 'italic', '|',
                    'bulletedList', 'numberedList', '|',
                    'outdent', 'indent', '|',
                    'link', 'blockQuote', '|',
                    'insertTable', '|',
                    'undo', 'redo'
                ]
            },
            heading: {
                options: [
                    { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                    { model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1' },
                    { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                    { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' }
                ]
            },
            placeholder: 'Describe the benefits, perks, and compensation package...',
            removePlugins: ['MediaEmbed', 'ImageUpload', 'ImageInsert', 'EasyImage', 'ImageResize', 'ImageStyle', 'ImageToolbar', 'ImageCaption'],
            table: {
                contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells']
            }
        })
        .then(editor => {
            benefitsEditor = editor;
            console.log('Benefits editor initialized successfully!');
            
            // Hide loading indicator
            const loadingIndicator = document.getElementById('job_benefits_loading');
            if (loadingIndicator) {
                loadingIndicator.style.display = 'none';
            }
            
            // Set initial content if available
            const initialContent = document.querySelector('#job_benefits').value;
            if (initialContent) {
                editor.setData(initialContent);
            }
            
            // Auto-save functionality
            editor.model.document.on('change:data', () => {
                autoSaveDraft();
            });
        })
        .catch(error => {
            console.error('Error initializing benefits editor:', error);
            alert('Error loading benefits editor: ' + error.message);
        });
}

// Add form validation with CKEditor support
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
    const title = document.querySelector('input[name="title"]').value.trim();
    const position = document.querySelector('input[name="position"]').value.trim();
    
    // Get content from CKEditor
    let content = '';
    if (jobDescriptionEditor) {
        content = jobDescriptionEditor.getData().trim();
        // Update the hidden textarea with CKEditor content
        document.querySelector('#job_description').value = content;
    } else {
        content = document.querySelector('#job_description').value.trim();
    }
    
    // Update other editors' content to textareas
    if (requirementsEditor) {
        document.querySelector('#job_requirements').value = requirementsEditor.getData();
    }
    if (benefitsEditor) {
        document.querySelector('#job_benefits').value = benefitsEditor.getData();
    }
    
    if (!title || !position || !content) {
        e.preventDefault();
        alert('Please fill in all required fields: Job Title, Position, and Job Description.');
        return false;
    }
    
    // Show loading state
    const submitBtn = e.target.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
    }
        });
    }
});

// Auto-save draft functionality with CKEditor support
let autoSaveTimeout;
function autoSaveDraft() {
    clearTimeout(autoSaveTimeout);
    autoSaveTimeout = setTimeout(() => {
        // Update textareas with CKEditor content before saving
        if (jobDescriptionEditor) {
            document.querySelector('#job_description').value = jobDescriptionEditor.getData();
        }
        if (requirementsEditor) {
            document.querySelector('#job_requirements').value = requirementsEditor.getData();
        }
        if (benefitsEditor) {
            document.querySelector('#job_benefits').value = benefitsEditor.getData();
        }
        
        const form = document.querySelector('form');
        const formData = new FormData(form);
        formData.set('action', 'save_draft');
        
        // Only auto-save if there's content
        const title = formData.get('title').trim();
        const content = formData.get('content').trim();
        
        if (title && content) {
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            }).then(response => {
                if (response.ok) {
                    console.log('Draft auto-saved');
                    // Show a subtle notification
                    showAutoSaveNotification();
                }
            }).catch(error => {
                console.error('Auto-save failed:', error);
            });
        }
    }, 30000); // Auto-save every 30 seconds
}

// Show auto-save notification
function showAutoSaveNotification() {
    // Remove existing notification
    const existingNotification = document.querySelector('.auto-save-notification');
    if (existingNotification) {
        existingNotification.remove();
    }
    
    // Create new notification
    const notification = document.createElement('div');
    notification.className = 'auto-save-notification fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50 text-sm';
    notification.innerHTML = '<i class="fas fa-check mr-2"></i>Draft auto-saved';
    
    document.body.appendChild(notification);
    
    // Remove notification after 3 seconds
    setTimeout(() => {
        if (document.body.contains(notification)) {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (document.body.contains(notification)) {
                    document.body.removeChild(notification);
                }
            }, 300);
        }
    }, 3000);
}

// Initialize auto-save for regular form inputs when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Attach auto-save to regular form inputs (non-CKEditor fields)
    document.querySelectorAll('input:not([type="file"]), select').forEach(element => {
        element.addEventListener('input', autoSaveDraft);
    });
});
</script>

<style>
/* CKEditor custom styling to match the form theme */
.ck-editor__editable {
    min-height: 200px;
    border: 2px solid #e5e7eb !important;
    border-radius: 0.5rem !important;
    transition: all 0.3s ease !important;
    font-family: system-ui, -apple-system, sans-serif !important;
    line-height: 1.6 !important;
}

.ck-editor__editable:focus {
    border-color: #10b981 !important;
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2) !important;
}

.ck-toolbar {
    border: 2px solid #e5e7eb !important;
    border-bottom: none !important;
    border-radius: 0.5rem 0.5rem 0 0 !important;
    background: #f9fafb !important;
    padding: 8px !important;
}

.ck-editor__main {
    border-radius: 0 0 0.5rem 0.5rem !important;
}

/* CKEditor content styling */
.ck-content h1, .ck-content h2, .ck-content h3 {
    color: #1f2937 !important;
    font-weight: bold !important;
}

.ck-content h1 { font-size: 1.875rem !important; }
.ck-content h2 { font-size: 1.5rem !important; }
.ck-content h3 { font-size: 1.25rem !important; }

.ck-content ul, .ck-content ol {
    padding-left: 1.5rem !important;
}

.ck-content blockquote {
    border-left: 4px solid #10b981 !important;
    padding-left: 1rem !important;
    margin: 1rem 0 !important;
    font-style: italic !important;
    color: #4b5563 !important;
}

.ck-content table {
    border-collapse: collapse !important;
    margin: 1rem 0 !important;
}

.ck-content table td, .ck-content table th {
    border: 1px solid #d1d5db !important;
    padding: 0.5rem !important;
}

.ck-content table th {
    background-color: #f3f4f6 !important;
    font-weight: bold !important;
}

/* Auto-save notification styling */
.auto-save-notification {
    transition: all 0.3s ease;
}

/* Responsive CKEditor */
@media (max-width: 768px) {
    .ck-toolbar {
        font-size: 12px;
    }
    
    .ck-toolbar__items {
        flex-wrap: wrap;
    }
}
</style>

