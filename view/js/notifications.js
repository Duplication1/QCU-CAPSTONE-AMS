/**
 * Notification System - Replaces all alert() and confirm() with modern UI
 * Provides toast/chip notifications and confirmation modals
 */

// Toast/Chip Notification System
function showNotification(message, type = 'success', duration = 4000) {
    // Remove existing notification
    const existing = document.getElementById('notification-toast');
    if (existing) {
        existing.remove();
    }

    // Create notification element
    const notification = document.createElement('div');
    notification.id = 'notification-toast';
    notification.className = 'fixed top-6 right-6 z-[9999] transform transition-all duration-300 ease-in-out';
    
    // Determine icon and colors based on type
    let iconClass, bgColor, borderColor, textColor;
    switch(type) {
        case 'success':
            iconClass = 'fa-check-circle';
            bgColor = 'bg-green-50';
            borderColor = 'border-green-400';
            textColor = 'text-green-800';
            break;
        case 'error':
            iconClass = 'fa-exclamation-circle';
            bgColor = 'bg-red-50';
            borderColor = 'border-red-400';
            textColor = 'text-red-800';
            break;
        case 'warning':
            iconClass = 'fa-exclamation-triangle';
            bgColor = 'bg-yellow-50';
            borderColor = 'border-yellow-400';
            textColor = 'text-yellow-800';
            break;
        case 'info':
            iconClass = 'fa-info-circle';
            bgColor = 'bg-blue-50';
            borderColor = 'border-blue-400';
            textColor = 'text-blue-800';
            break;
        default:
            iconClass = 'fa-info-circle';
            bgColor = 'bg-gray-50';
            borderColor = 'border-gray-400';
            textColor = 'text-gray-800';
    }

    notification.innerHTML = `
        <div class="${bgColor} ${textColor} border-l-4 ${borderColor} px-6 py-4 rounded-lg shadow-lg max-w-md flex items-start gap-3 animate-slide-in">
            <i class="fas ${iconClass} text-xl mt-0.5"></i>
            <div class="flex-1">
                <p class="font-medium text-sm leading-relaxed">${message}</p>
            </div>
            <button onclick="this.closest('#notification-toast').remove()" class="text-current opacity-50 hover:opacity-100 transition-opacity">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;

    document.body.appendChild(notification);

    // Auto-remove after duration
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => notification.remove(), 300);
    }, duration);
}

// Confirmation Modal System
function showConfirmModal(options) {
    return new Promise((resolve) => {
        // Remove existing modal if any
        const existing = document.getElementById('confirm-modal');
        if (existing) {
            existing.remove();
        }

        // Default options
        const {
            title = 'Confirm Action',
            message = 'Are you sure you want to proceed?',
            confirmText = 'Confirm',
            cancelText = 'Cancel',
            confirmColor = 'bg-blue-600 hover:bg-blue-700',
            type = 'info' // 'info', 'warning', 'danger', 'success'
        } = options;

        // Determine icon and colors based on type
        let iconClass, iconBgColor;
        switch(type) {
            case 'danger':
                iconClass = 'fa-exclamation-triangle';
                iconBgColor = 'bg-red-100';
                break;
            case 'warning':
                iconClass = 'fa-exclamation-circle';
                iconBgColor = 'bg-yellow-100';
                break;
            case 'success':
                iconClass = 'fa-check-circle';
                iconBgColor = 'bg-green-100';
                break;
            default:
                iconClass = 'fa-info-circle';
                iconBgColor = 'bg-blue-100';
        }

        // Create modal
        const modal = document.createElement('div');
        modal.id = 'confirm-modal';
        modal.className = 'fixed inset-0 z-[10000] flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm animate-fade-in';
        
        modal.innerHTML = `
            <div class="bg-white rounded-xl shadow-2xl p-6 max-w-md w-full mx-4 animate-scale-in">
                <div class="flex items-start gap-4 mb-4">
                    <div class="${iconBgColor} rounded-full p-3">
                        <i class="fas ${iconClass} text-2xl ${type === 'danger' ? 'text-red-600' : type === 'warning' ? 'text-yellow-600' : type === 'success' ? 'text-green-600' : 'text-blue-600'}"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-gray-900 mb-2">${title}</h3>
                        <p class="text-sm text-gray-600">${message}</p>
                    </div>
                </div>
                <div class="flex gap-3 justify-end mt-6">
                    <button id="confirm-cancel" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium transition-colors">
                        ${cancelText}
                    </button>
                    <button id="confirm-ok" class="${confirmColor} text-white px-4 py-2 rounded-lg font-medium transition-colors">
                        ${confirmText}
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Handle confirm
        document.getElementById('confirm-ok').addEventListener('click', () => {
            modal.remove();
            resolve(true);
        });

        // Handle cancel
        document.getElementById('confirm-cancel').addEventListener('click', () => {
            modal.remove();
            resolve(false);
        });

        // Handle backdrop click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
                resolve(false);
            }
        });

        // Handle escape key
        const escapeHandler = (e) => {
            if (e.key === 'Escape') {
                modal.remove();
                document.removeEventListener('keydown', escapeHandler);
                resolve(false);
            }
        };
        document.addEventListener('keydown', escapeHandler);
    });
}

// Chip notification (fixed position, dismissible alert)
function showChip(message, type = 'info', containerId = 'chip-container', duration = 0) {
    let container = document.getElementById(containerId);
    
    // Create container if it doesn't exist - now fixed at top center
    if (!container) {
        container = document.createElement('div');
        container.id = containerId;
        container.className = 'fixed top-20 left-1/2 transform -translate-x-1/2 z-[9998] w-full max-w-2xl px-4';
        document.body.appendChild(container);
    }

    // Remove existing chips in the container
    container.innerHTML = '';

    // Determine styles based on type
    let iconClass, bgColor, borderColor, textColor;
    switch(type) {
        case 'success':
            iconClass = 'fa-check-circle';
            bgColor = 'bg-green-50';
            borderColor = 'border-green-400';
            textColor = 'text-green-800';
            break;
        case 'error':
            iconClass = 'fa-times-circle';
            bgColor = 'bg-red-50';
            borderColor = 'border-red-400';
            textColor = 'text-red-800';
            break;
        case 'warning':
            iconClass = 'fa-exclamation-triangle';
            bgColor = 'bg-yellow-50';
            borderColor = 'border-yellow-400';
            textColor = 'text-yellow-800';
            break;
        case 'info':
            iconClass = 'fa-info-circle';
            bgColor = 'bg-blue-50';
            borderColor = 'border-blue-400';
            textColor = 'text-blue-800';
            break;
        default:
            iconClass = 'fa-info-circle';
            bgColor = 'bg-gray-50';
            borderColor = 'border-gray-400';
            textColor = 'text-gray-800';
    }

    const chip = document.createElement('div');
    chip.className = `${bgColor} ${textColor} border-l-4 ${borderColor} px-6 py-4 rounded-lg shadow-lg flex items-center gap-3 animate-slide-down`;
    chip.innerHTML = `
        <i class="fas ${iconClass} text-xl"></i>
        <span class="flex-1 text-sm font-medium">${message}</span>
        <button onclick="this.closest('div').remove()" class="text-current opacity-60 hover:opacity-100 transition-opacity">
            <i class="fas fa-times"></i>
        </button>
    `;

    container.appendChild(chip);

    // Auto-remove if duration is set
    if (duration > 0) {
        setTimeout(() => {
            chip.style.opacity = '0';
            chip.style.transform = 'translateY(-20px)';
            setTimeout(() => {
                chip.remove();
                // Remove container if empty
                if (container.children.length === 0) {
                    container.remove();
                }
            }, 300);
        }, duration);
    }
}

// Loading modal
function showLoadingModal(message = 'Processing...') {
    // Remove existing loading modal
    const existing = document.getElementById('loading-modal');
    if (existing) {
        existing.remove();
    }

    const modal = document.createElement('div');
    modal.id = 'loading-modal';
    modal.className = 'fixed inset-0 z-[10000] flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm';
    
    modal.innerHTML = `
        <div class="bg-white rounded-xl shadow-2xl p-8 max-w-sm w-full mx-4 text-center">
            <div class="mb-4">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-blue-600 border-t-transparent"></div>
            </div>
            <p class="text-gray-700 font-medium">${message}</p>
        </div>
    `;

    document.body.appendChild(modal);
    return modal;
}

function hideLoadingModal() {
    const modal = document.getElementById('loading-modal');
    if (modal) {
        modal.remove();
    }
}

// Add animations to document if not already present
if (!document.getElementById('notification-styles')) {
    const style = document.createElement('style');
    style.id = 'notification-styles';
    style.textContent = `
        @keyframes slide-in {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        @keyframes slide-down {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        @keyframes fade-in {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes scale-in {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        .animate-slide-in {
            animation: slide-in 0.3s ease-out;
        }
        .animate-slide-down {
            animation: slide-down 0.3s ease-out;
        }
        .animate-fade-in {
            animation: fade-in 0.2s ease-out;
        }
        .animate-scale-in {
            animation: scale-in 0.3s ease-out;
        }
    `;
    document.head.appendChild(style);
}

// Legacy support - map old function names to new ones
window.showTopAlert = function(type, msg, duration = 5000) {
    showNotification(msg, type, duration);
};

window.showAlert = function(type, msg, duration = 5000) {
    showNotification(msg, type, duration);
};

window.showToast = function(message, type = 'success', duration = 4000) {
    showNotification(message, type, duration);
};
