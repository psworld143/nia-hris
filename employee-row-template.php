<tr class="hover:bg-gray-50 transition-colors <?php echo $employee['is_eligible'] ? 'bg-green-50' : ''; ?>">
    <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap">
        <div class="flex items-center">
            <div class="w-6 h-6 sm:w-8 sm:h-8 md:w-10 md:h-10 bg-gradient-to-r <?php echo $employee['is_eligible'] ? 'from-green-500 to-green-600' : 'from-gray-400 to-gray-500'; ?> rounded-full flex items-center justify-center text-white font-bold text-xs mr-2">
                <?php echo strtoupper(substr($employee['first_name'], 0, 1) . substr($employee['last_name'], 0, 1)); ?>
            </div>
            <div class="min-w-0 flex-1">
                <div class="text-xs sm:text-sm font-medium text-gray-900 truncate">
                    <?php echo $employee['first_name'] . ' ' . $employee['last_name']; ?>
                </div>
                <div class="text-xs text-gray-500 hidden sm:block">ID: <?php echo $employee['emp_id']; ?></div>
                <div class="text-xs text-gray-500 sm:hidden"><?php echo $employee['position']; ?></div>
            </div>
        </div>
    </td>
    <td class="hidden sm:table-cell px-6 py-4 whitespace-nowrap">
        <div class="text-xs sm:text-sm text-gray-900"><?php echo $employee['position']; ?></div>
        <div class="text-xs text-gray-500 hidden md:block"><?php echo $employee['department']; ?></div>
    </td>
    <td class="hidden lg:table-cell px-6 py-4 whitespace-nowrap text-xs sm:text-sm text-gray-900">
        <?php echo date('M d, Y', strtotime($employee['hire_date'])); ?>
    </td>
    <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap">
        <span class="px-1.5 py-0.5 sm:px-2 sm:py-1 text-xs font-medium <?php echo $employee['years_service'] >= 3 ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?> rounded-full">
            <?php echo round($employee['years_service'], 1); ?>y
        </span>
    </td>
    <td class="hidden md:table-cell px-6 py-4 whitespace-nowrap text-xs sm:text-sm text-gray-500">
        <?php if ($employee['last_increment_date']): ?>
            <?php echo date('M d, Y', strtotime($employee['last_increment_date'])); ?>
            <div class="text-xs text-gray-400 hidden lg:block">
                (<?php echo round($employee['years_since_last_increment'], 1); ?> years ago)
            </div>
        <?php else: ?>
            <span class="text-gray-400">Never</span>
        <?php endif; ?>
    </td>
    <td class="hidden sm:table-cell px-6 py-4 whitespace-nowrap text-xs sm:text-sm font-semibold text-gray-900">
        ₱<?php echo number_format($employee['basic_salary'] ?? 0, 0); ?>
        <?php if ($employee['is_eligible'] && $employee['expected_increment'] > 0): ?>
            <div class="text-xs text-green-600 hidden md:block">→ ₱<?php echo number_format(($employee['basic_salary'] ?? 0) + $employee['expected_increment'], 0); ?></div>
            <div class="text-xs text-gray-500 hidden md:block">+₱<?php echo number_format($employee['expected_increment'], 0); ?></div>
        <?php elseif (!$employee['is_eligible'] && !empty($employee['salary_structure_id'])): ?>
            <div class="text-xs text-orange-600 hidden md:block">
                <?php 
                $frequency_years = $employee['incrementation_frequency_years'] ?? 3;
                $years_needed = $frequency_years - $employee['years_since_last_increment'];
                if ($years_needed > 0) {
                    echo "In " . round($years_needed, 1) . "y";
                } else {
                    echo "Not eligible";
                }
                ?>
            </div>
        <?php elseif (empty($employee['salary_structure_id'])): ?>
            <div class="text-xs text-red-600 hidden md:block">No structure</div>
        <?php endif; ?>
    </td>
    <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap">
        <div class="flex flex-col space-y-1 sm:space-y-2">
            <?php if (!empty($employee['pending_increment_id'])): ?>
                <!-- Pending Increment Status -->
                <span class="px-1.5 py-0.5 sm:px-2 sm:py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                    <i class="fas fa-clock mr-1"></i>Pending
                </span>
                <div class="text-xs text-green-700 hidden sm:block">
                    ₱<?php echo number_format($employee['pending_increment_amount'], 0); ?> pending
                </div>
                <div class="text-xs text-gray-500 hidden md:block">
                    Effective: <?php echo date('M d, Y', strtotime($employee['pending_effective_date'])); ?>
                </div>
                
                <!-- Confirm/Reject Buttons -->
                <div class="flex flex-col sm:flex-row space-y-1 sm:space-y-0 sm:space-x-1 mt-1 sm:mt-2">
                    <button onclick="confirmIncrement(<?php echo $employee['pending_increment_id']; ?>, '<?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>', <?php echo $employee['pending_increment_amount']; ?>)" 
                            class="px-2 py-1 text-xs bg-green-600 text-white rounded hover:bg-green-700 transition-colors action-buttons">
                        <i class="fas fa-check"></i><span class="hidden sm:inline ml-1">Confirm</span>
                    </button>
                    <button onclick="rejectIncrement(<?php echo $employee['pending_increment_id']; ?>, '<?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>')" 
                            class="px-2 py-1 text-xs bg-red-100 text-red-800 rounded hover:bg-red-200 transition-colors action-buttons">
                        <i class="fas fa-times"></i><span class="hidden sm:inline ml-1">Reject</span>
                    </button>
                </div>
            <?php elseif ($employee['is_eligible']): ?>
                <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                    <i class="fas fa-check mr-1"></i>Eligible
                </span>
                <div class="text-xs text-gray-500 hidden sm:block">
                    <?php 
                    $frequency_years = $employee['incrementation_frequency_years'] ?? 3;
                    echo "Every {$frequency_years}y";
                    ?>
                </div>
            <?php elseif (empty($employee['salary_structure_id'])): ?>
                <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">
                    <i class="fas fa-exclamation-triangle mr-1"></i>No Structure
                </span>
                <div class="text-xs text-red-600 hidden sm:block">Create structure</div>
            <?php else: ?>
                <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">
                    <i class="fas fa-clock mr-1"></i>Waiting
                </span>
                <div class="text-xs text-gray-500 hidden sm:block">
                    <?php 
                    $frequency_years = $employee['incrementation_frequency_years'] ?? 3;
                    $years_needed = $frequency_years - $employee['years_since_last_increment'];
                    if ($years_needed > 0) {
                        echo "In " . round($years_needed, 1) . "y";
                    } else {
                        echo "Not eligible";
                    }
                    ?>
                </div>
            <?php endif; ?>
            
            <!-- View History Button -->
            <button onclick="viewSalaryHistory(<?php echo $employee['employee_id']; ?>, '<?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>')" 
                    class="mt-1 sm:mt-2 px-1.5 py-0.5 sm:px-2 sm:py-1 text-xs bg-green-100 text-green-800 rounded hover:bg-green-200 transition-colors">
                <i class="fas fa-history"></i><span class="hidden sm:inline ml-1">View History</span>
            </button>
        </div>
    </td>
</tr>
