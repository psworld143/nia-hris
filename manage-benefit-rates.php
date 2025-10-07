<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'human_resource', 'hr_manager'])) {
    header('Location: index.php');
    exit();
}

// Check if tables exist
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'government_benefit_rates'");
if (mysqli_num_rows($table_check) == 0) {
    $page_title = 'Setup Government Benefits';
    include 'includes/header.php';
    ?>
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-xl shadow-lg p-8 text-center">
            <div class="w-20 h-20 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-exclamation-triangle text-yellow-600 text-3xl"></i>
            </div>
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Government Benefits Tables Not Found</h2>
            <p class="text-gray-600 mb-6 text-lg">
                Click the button below to install government benefit contribution rate tables.
            </p>
            
            <button onclick="setupTables()" id="setupBtn" 
                    class="bg-green-500 text-white px-8 py-3 rounded-lg hover:bg-green-600 transition-all font-medium text-lg shadow-lg">
                <i class="fas fa-cog mr-2"></i>Install Government Benefits Tables
            </button>
            
            <div id="setupResult" class="mt-6 hidden"></div>
        </div>
    </div>
    
    <script>
    function setupTables() {
        const btn = document.getElementById('setupBtn');
        const result = document.getElementById('setupResult');
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Installing...';
        
        fetch('setup-government-benefits.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'ajax=1'
        })
        .then(response => response.json())
        .then(data => {
            result.classList.remove('hidden');
            if (data.success) {
                result.innerHTML = `
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg">
                        <h3 class="font-bold mb-2">Installation Successful!</h3>
                        <p>${data.message}</p>
                        <button onclick="location.reload()" class="mt-3 bg-green-500 text-white px-6 py-2 rounded-lg">
                            Continue
                        </button>
                    </div>
                `;
            } else {
                result.innerHTML = `<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg">${data.message}</div>`;
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-redo mr-2"></i>Try Again';
            }
        });
    }
    </script>
    <?php
    exit();
}

// Get all unique benefit types from the database
$benefit_types_query = "SELECT DISTINCT benefit_type FROM government_benefit_rates WHERE is_active = 1 ORDER BY benefit_type";
$benefit_types_result = mysqli_query($conn, $benefit_types_query);
$benefit_types = [];
while ($bt = mysqli_fetch_assoc($benefit_types_result)) {
    $benefit_types[] = $bt['benefit_type'];
}

// Get benefit rates
$rates_query = "SELECT * FROM government_benefit_rates WHERE is_active = 1 ORDER BY benefit_type, salary_range_min";
$rates_result = mysqli_query($conn, $rates_query);

// Store rates by type dynamically
$rates_by_type = [];
while ($rate = mysqli_fetch_assoc($rates_result)) {
    if (!isset($rates_by_type[$rate['benefit_type']])) {
        $rates_by_type[$rate['benefit_type']] = [];
    }
    $rates_by_type[$rate['benefit_type']][] = $rate;
}

// Get tax brackets
$tax_query = "SELECT * FROM tax_brackets WHERE is_active = 1 ORDER BY income_min";
$tax_result = mysqli_query($conn, $tax_query);
$tax_brackets = [];
while ($tax = mysqli_fetch_assoc($tax_result)) {
    $tax_brackets[] = $tax;
}

// Add tax to benefit types
$benefit_types[] = 'tax';

// Define benefit type configurations
$benefit_config = [
    'gsis' => [
        'name' => 'GSIS Life',
        'full_name' => 'GSIS Life Insurance',
        'description' => 'Government Service Insurance System - Life Insurance Premium',
        'icon' => 'fa-shield-alt',
        'color' => 'indigo'
    ],
    'gsis_ps' => [
        'name' => 'GSIS Personal Share',
        'full_name' => 'GSIS Personal Share',
        'description' => 'GSIS Employee & Employer Contribution (9% + 12%)',
        'icon' => 'fa-user-shield',
        'color' => 'blue'
    ],
    'gsis_optional' => [
        'name' => 'GSIS Optional',
        'full_name' => 'GSIS Optional Life Insurance',
        'description' => 'GSIS Optional Life Insurance Premium',
        'icon' => 'fa-certificate',
        'color' => 'teal'
    ],
    'sss' => [
        'name' => 'SSS Rates',
        'full_name' => 'Social Security System',
        'description' => 'Social Security System contribution (Private Sector)',
        'icon' => 'fa-id-card',
        'color' => 'cyan'
    ],
    'philhealth' => [
        'name' => 'PhilHealth',
        'full_name' => 'PhilHealth',
        'description' => 'National Health Insurance Program (5% premium rate)',
        'icon' => 'fa-heart',
        'color' => 'green'
    ],
    'pagibig' => [
        'name' => 'Pag-IBIG',
        'full_name' => 'Pag-IBIG Fund',
        'description' => 'Home Development Mutual Fund (HDMF)',
        'icon' => 'fa-home',
        'color' => 'yellow'
    ],
    'tax' => [
        'name' => 'Tax Brackets',
        'full_name' => 'Withholding Tax',
        'description' => 'Income tax brackets based on TRAIN Law',
        'icon' => 'fa-calculator',
        'color' => 'red'
    ]
];

// Add default config for unknown benefit types
foreach ($benefit_types as $type) {
    if (!isset($benefit_config[$type]) && $type !== 'tax') {
        $benefit_config[$type] = [
            'name' => ucwords(str_replace('_', ' ', $type)),
            'full_name' => ucwords(str_replace('_', ' ', $type)),
            'description' => ucwords(str_replace('_', ' ', $type)) . ' contribution rates',
            'icon' => 'fa-coins',
            'color' => 'purple'
        ];
    }
}

$page_title = 'Manage Government Benefit Rates';
include 'includes/header.php';
?>

<style>
.tab-button {
    transition: all 0.3s ease;
}
.tab-button.active {
    background-color: #10b981;
    color: white;
    border-bottom: 3px solid #059669;
}
.tab-content {
    display: none;
    animation: fadeIn 0.3s ease-in;
}
.tab-content.active {
    display: block;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<!-- Page Header -->
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">
                <i class="fas fa-shield-alt text-blue-600 mr-2"></i>Government Benefit Rates
            </h1>
            <p class="text-gray-600 mt-1">Manage contribution rates and tax brackets</p>
        </div>
        <a href="government-benefits.php" class="bg-gray-500 text-white px-6 py-3 rounded-lg hover:bg-gray-600 font-medium shadow-lg transition">
            <i class="fas fa-arrow-left mr-2"></i>Back
        </a>
    </div>
</div>

<!-- Tabs Navigation -->
<div class="bg-white rounded-t-xl shadow-lg overflow-hidden">
    <div class="flex border-b border-gray-200 overflow-x-auto">
        <?php foreach ($benefit_types as $index => $type): 
            $config = $benefit_config[$type];
            $active_class = $index === 0 ? 'active' : '';
        ?>
            <button onclick="switchTab('<?php echo $type; ?>')" id="tab-<?php echo $type; ?>" 
                    class="tab-button flex-shrink-0 px-6 py-4 font-semibold text-gray-700 hover:bg-gray-50 <?php echo $active_class; ?>">
                <i class="fas <?php echo $config['icon']; ?> mr-2"></i><?php echo $config['name']; ?>
            </button>
        <?php endforeach; ?>
    </div>
</div>

<!-- Tab Contents -->
<div class="bg-white rounded-b-xl shadow-lg mb-6">
    
    <?php foreach ($benefit_types as $index => $type): 
        $config = $benefit_config[$type];
        $active_class = $index === 0 ? 'active' : '';
        $color = $config['color'];
    ?>
    
    <!-- <?php echo ucwords($type); ?> Tab -->
    <div id="content-<?php echo $type; ?>" class="tab-content <?php echo $active_class; ?> p-6">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">
                    <i class="fas <?php echo $config['icon']; ?> text-<?php echo $color; ?>-600 mr-2"></i><?php echo $config['full_name']; ?> <?php echo $type === 'tax' ? '' : 'Contribution Rates'; ?>
                </h2>
                <p class="text-sm text-gray-600 mt-1"><?php echo $config['description']; ?></p>
            </div>
            <button onclick="<?php echo $type === 'tax' ? 'addTaxBracket()' : "addRate('$type')"; ?>" 
                    class="bg-<?php echo $color; ?>-600 text-white px-6 py-3 rounded-lg hover:bg-<?php echo $color; ?>-700 font-medium shadow-lg transition">
                <i class="fas fa-plus mr-2"></i>Add <?php echo $config['name']; ?>
            </button>
        </div>
        <?php if ($type === 'tax'): ?>
            <!-- Tax Brackets Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gradient-to-r from-<?php echo $color; ?>-50 to-<?php echo $color; ?>-100">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Bracket Name</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Income Range</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Base Tax</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Tax Rate</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Effective Date</th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($tax_brackets as $tax): ?>
                            <tr class="hover:bg-<?php echo $color; ?>-50 transition-colors">
                                <td class="px-6 py-4 text-sm font-bold text-gray-900"><?php echo htmlspecialchars($tax['bracket_name']); ?></td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                    ₱<?php echo number_format($tax['income_min'], 2); ?> - 
                                    <?php echo $tax['income_max'] ? '₱' . number_format($tax['income_max'], 2) : '<span class="text-gray-500">Above</span>'; ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">₱<?php echo number_format($tax['base_tax'], 2); ?></td>
                                <td class="px-6 py-4 text-sm font-bold text-<?php echo $color; ?>-600"><?php echo number_format($tax['tax_rate'], 2); ?>%</td>
                                <td class="px-6 py-4 text-sm text-gray-600"><?php echo date('M j, Y', strtotime($tax['effective_date'])); ?></td>
                                <td class="px-6 py-4 text-center">
                                    <button onclick="editTax(<?php echo htmlspecialchars(json_encode($tax)); ?>)" 
                                            class="text-<?php echo $color; ?>-600 hover:text-<?php echo $color; ?>-900 mr-2" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteTax(<?php echo $tax['id']; ?>)" 
                                            class="text-red-600 hover:text-red-900" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <!-- Regular Benefit Rates Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gradient-to-r from-<?php echo $color; ?>-50 to-<?php echo $color; ?>-100">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Salary Range</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Employee Share</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Employer Share</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Effective Date</th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php 
                        $rates = isset($rates_by_type[$type]) ? $rates_by_type[$type] : [];
                        foreach ($rates as $rate): 
                        ?>
                            <tr class="hover:bg-<?php echo $color; ?>-50 transition-colors">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                    ₱<?php echo number_format($rate['salary_range_min'], 2); ?> - 
                                    ₱<?php echo number_format($rate['salary_range_max'], 2); ?>
                                </td>
                                <td class="px-6 py-4 text-sm font-bold text-<?php echo $color; ?>-600">
                                    <?php echo $rate['is_percentage'] ? number_format($rate['employee_rate'], 2) . '%' : '₱' . number_format($rate['employee_rate'], 2); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    <?php echo $rate['is_percentage'] ? number_format($rate['employer_rate'], 2) . '%' : '₱' . number_format($rate['employer_rate'], 2); ?>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $rate['is_percentage'] ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?>">
                                        <?php echo $rate['is_percentage'] ? 'Percentage' : 'Fixed'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <?php echo date('M j, Y', strtotime($rate['effective_date'])); ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button onclick="editRate(<?php echo htmlspecialchars(json_encode($rate)); ?>)" 
                                            class="text-<?php echo $color; ?>-600 hover:text-<?php echo $color; ?>-900 mr-2" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteRate(<?php echo $rate['id']; ?>)" 
                                            class="text-red-600 hover:text-red-900" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <?php endforeach; ?>
</div>

<script>
function switchTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Remove active from all buttons
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active');
    });
    
    // Show selected tab
    document.getElementById('content-' + tabName).classList.add('active');
    document.getElementById('tab-' + tabName).classList.add('active');
}

function addRate(type) {
    alert('Add ' + type.toUpperCase() + ' rate functionality - to be implemented');
}

function editRate(rate) {
    alert('Edit rate functionality - to be implemented\n\n' + JSON.stringify(rate, null, 2));
}

function deleteRate(id) {
    if (confirm('Are you sure you want to delete this rate?')) {
        alert('Delete rate ' + id + ' - to be implemented');
    }
}

function addTaxBracket() {
    alert('Add tax bracket functionality - to be implemented');
}

function editTax(tax) {
    alert('Edit tax bracket functionality - to be implemented\n\n' + JSON.stringify(tax, null, 2));
}

function deleteTax(id) {
    if (confirm('Are you sure you want to delete this tax bracket?')) {
        alert('Delete tax bracket ' + id + ' - to be implemented');
    }
}
</script>

