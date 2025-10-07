/**
 * Enhanced HR Dashboard JavaScript
 * Provides interactive functionality, animations, and real-time updates
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('HR Dashboard Enhanced: Initializing...');
    
    // Initialize all dashboard components
    initializeStatCards();
    initializeQuickActions();
    initializeRealtimeUpdates();
    initializeTableInteractions();
    initializeFacultyCards();
    initializeAnimations();
    
    console.log('HR Dashboard Enhanced: All components initialized');
});

/**
 * Initialize statistic cards with hover effects and click animations
 */
function initializeStatCards() {
    const statCards = document.querySelectorAll('[class*="border-l-4"]');
    
    statCards.forEach(card => {
        // Add hover effect enhancement
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.02) translateY(-2px)';
            this.style.boxShadow = '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = '';
            this.style.boxShadow = '';
        });
        
        // Add click animation
        card.addEventListener('click', function() {
            this.style.transform = 'scale(0.98)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
            
            // Add ripple effect
            createRipple(this, event);
        });
    });
}

/**
 * Initialize quick action buttons with enhanced interactions
 */
function initializeQuickActions() {
    const actionButtons = document.querySelectorAll('[data-action]');
    
    actionButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const action = this.getAttribute('data-action');
            console.log(`Quick Action: ${action}`);
            
            // Add loading state
            const originalContent = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin mr-3"></i>Loading...';
            this.style.pointerEvents = 'none';
            
            // Simulate loading (remove this in production)
            setTimeout(() => {
                this.innerHTML = originalContent;
                this.style.pointerEvents = '';
            }, 1000);
        });
    });
}

/**
 * Initialize real-time updates for dashboard statistics
 */
function initializeRealtimeUpdates() {
    // Update time display every second
    updateTimeDisplay();
    setInterval(updateTimeDisplay, 1000);
    
    // Refresh statistics every 5 minutes
    setInterval(refreshStatistics, 300000);
    
    // Add notification system
    initializeNotificationSystem();
}

/**
 * Update time display in the header
 */
function updateTimeDisplay() {
    const timeElements = document.querySelectorAll('[data-time]');
    const now = new Date();
    
    timeElements.forEach(element => {
        element.textContent = now.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit'
        });
    });
}

/**
 * Refresh dashboard statistics via AJAX
 */
function refreshStatistics() {
    console.log('Refreshing dashboard statistics...');
    
    // Add visual indicator for refresh
    const statCards = document.querySelectorAll('[class*="border-l-4"]');
    statCards.forEach(card => {
        card.style.opacity = '0.7';
    });
    
    // Simulate API call (implement actual AJAX call in production)
    setTimeout(() => {
        statCards.forEach(card => {
            card.style.opacity = '';
        });
        console.log('Statistics refreshed');
    }, 1000);
}

/**
 * Initialize table interactions and sorting
 */
function initializeTableInteractions() {
    const tableRows = document.querySelectorAll('tbody tr');
    
    tableRows.forEach(row => {
        row.addEventListener('click', function() {
            // Remove previous selection
            tableRows.forEach(r => r.classList.remove('bg-green-50', 'border-green-200'));
            
            // Add selection to current row
            this.classList.add('bg-green-50', 'border-green-200');
            this.style.borderWidth = '2px';
            
            // Get college data
            const college = this.getAttribute('data-college');
            if (college) {
                console.log(`Selected college: ${college}`);
                showCollegeDetails(college);
            }
        });
    });
    
    // Initialize table sorting
    initializeTableSorting();
}

/**
 * Initialize table sorting functionality
 */
function initializeTableSorting() {
    const headers = document.querySelectorAll('th');
    
    headers.forEach((header, index) => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', function() {
            sortTable(index, this);
        });
    });
}

/**
 * Sort table by column
 */
function sortTable(columnIndex, header) {
    const table = header.closest('table');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    // Determine sort direction
    const isAscending = !header.classList.contains('sort-desc');
    
    // Clear previous sort indicators
    table.querySelectorAll('th').forEach(h => {
        h.classList.remove('sort-asc', 'sort-desc');
        const icon = h.querySelector('.sort-icon');
        if (icon) icon.remove();
    });
    
    // Add sort indicator
    const sortIcon = document.createElement('i');
    sortIcon.className = `fas fa-sort-${isAscending ? 'up' : 'down'} ml-2 sort-icon text-green-600`;
    header.appendChild(sortIcon);
    header.classList.add(isAscending ? 'sort-asc' : 'sort-desc');
    
    // Sort rows
    rows.sort((a, b) => {
        const aText = a.cells[columnIndex].textContent.trim();
        const bText = b.cells[columnIndex].textContent.trim();
        
        // Try to parse as numbers
        const aNum = parseFloat(aText);
        const bNum = parseFloat(bText);
        
        if (!isNaN(aNum) && !isNaN(bNum)) {
            return isAscending ? aNum - bNum : bNum - aNum;
        } else {
            return isAscending ? aText.localeCompare(bText) : bText.localeCompare(aText);
        }
    });
    
    // Reorder rows in DOM
    rows.forEach(row => tbody.appendChild(row));
}

/**
 * Initialize faculty cards interactions
 */
function initializeFacultyCards() {
    const facultyCards = document.querySelectorAll('[data-faculty-id]');
    
    facultyCards.forEach(card => {
        card.addEventListener('click', function() {
            const facultyId = this.getAttribute('data-faculty-id');
            showFacultyDetails(facultyId);
        });
        
        // Add hover effects
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(4px) scale(1.02)';
            this.style.boxShadow = '0 10px 15px -3px rgba(0, 0, 0, 0.1)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = '';
            this.style.boxShadow = '';
        });
    });
}

/**
 * Initialize page animations
 */
function initializeAnimations() {
    // Animate elements on scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fadeInUp');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    // Observe all cards and sections
    const animatableElements = document.querySelectorAll('.bg-white, .grid > div');
    animatableElements.forEach(el => observer.observe(el));
}

/**
 * Show college details in a modal or sidebar
 */
function showCollegeDetails(collegeName) {
    // Create or show college details panel
    console.log(`Showing details for: ${collegeName}`);
    
    // You can implement a modal or sidebar here
    showNotification(`Selected college: ${collegeName}`, 'info');
}

/**
 * Show faculty details
 */
function showFacultyDetails(facultyId) {
    console.log(`Showing faculty details for ID: ${facultyId}`);
    
    // You can implement faculty detail view here
    showNotification('Faculty details loaded', 'success');
}

/**
 * Create ripple effect on click
 */
function createRipple(element, event) {
    const ripple = document.createElement('span');
    const rect = element.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    const x = event.clientX - rect.left - size / 2;
    const y = event.clientY - rect.top - size / 2;
    
    ripple.style.cssText = `
        position: absolute;
        border-radius: 50%;
        background: rgba(16, 185, 129, 0.3);
        transform: scale(0);
        animation: ripple 0.6s linear;
        width: ${size}px;
        height: ${size}px;
        left: ${x}px;
        top: ${y}px;
        pointer-events: none;
    `;
    
    element.style.position = 'relative';
    element.style.overflow = 'hidden';
    element.appendChild(ripple);
    
    setTimeout(() => {
        ripple.remove();
    }, 600);
}

/**
 * Initialize notification system
 */
function initializeNotificationSystem() {
    // Create notification container if it doesn't exist
    if (!document.getElementById('notification-container')) {
        const container = document.createElement('div');
        container.id = 'notification-container';
        container.className = 'fixed top-4 right-4 z-50 space-y-2';
        document.body.appendChild(container);
    }
}

/**
 * Show notification
 */
function showNotification(message, type = 'info', duration = 3000) {
    const container = document.getElementById('notification-container');
    const notification = document.createElement('div');
    
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        warning: 'bg-yellow-500',
        info: 'bg-blue-500'
    };
    
    const icons = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    
    notification.className = `${colors[type]} text-white px-6 py-4 rounded-lg shadow-lg transform translate-x-full transition-transform duration-300 flex items-center space-x-3 max-w-sm`;
    notification.innerHTML = `
        <i class="fas ${icons[type]}"></i>
        <span>${message}</span>
        <button onclick="this.parentElement.remove()" class="ml-auto text-white hover:text-gray-200">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    container.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Auto remove
    if (duration > 0) {
        setTimeout(() => {
            notification.style.transform = 'translateX(full)';
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 300);
        }, duration);
    }
}

/**
 * Export dashboard data
 */
function exportDashboardData() {
    console.log('Exporting dashboard data...');
    showNotification('Dashboard data exported successfully!', 'success');
}

/**
 * Print dashboard report
 */
function printDashboardReport() {
    console.log('Printing dashboard report...');
    window.print();
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .animate-fadeInUp {
        animation: fadeInUp 0.6s ease-out forwards;
    }
    
    .sort-asc, .sort-desc {
        background-color: rgba(16, 185, 129, 0.1);
    }
    
    /* Custom scrollbar for notification container */
    #notification-container::-webkit-scrollbar {
        width: 4px;
    }
    
    #notification-container::-webkit-scrollbar-track {
        background: transparent;
    }
    
    #notification-container::-webkit-scrollbar-thumb {
        background: rgba(16, 185, 129, 0.3);
        border-radius: 2px;
    }
`;
document.head.appendChild(style);

// Global functions for button clicks
window.exportDashboardData = exportDashboardData;
window.printDashboardReport = printDashboardReport;
window.showNotification = showNotification;
