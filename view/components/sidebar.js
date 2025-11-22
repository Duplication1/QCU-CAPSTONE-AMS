/**
 * Sidebar Navigation JavaScript Component
 * 
 * This script handles all sidebar navigation functionality including:
 * - Desktop toggle/collapse
 * - Mobile menu overlay
 * - Responsive behavior
 * - Text hiding/showing animations
 * 
 * Usage: Include this script after the sidebar HTML is loaded
 */

document.addEventListener('DOMContentLoaded', function() {
    // Get all necessary elements
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const mobileOverlay = document.getElementById('mobile-overlay');

    // Mobile toggle functionality (burger button only visible on mobile)
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            // Mobile: toggle sidebar visibility
            sidebar.classList.toggle('-translate-x-full');
            mobileOverlay.classList.toggle('hidden');
            if (sidebar.classList.contains('-translate-x-full')) {
                document.body.style.overflow = 'auto';
            } else {
                document.body.style.overflow = 'hidden';
            }
        });
    }
    // Mobile menu toggle
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function() {
            sidebar.classList.remove('-translate-x-full');
            mobileOverlay.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        });
    }

    // Mobile close functionality
    if (mobileClose) {
        mobileClose.addEventListener('click', function() {
            sidebar.classList.add('-translate-x-full');
            mobileOverlay.classList.add('hidden');
            document.body.style.overflow = 'auto';
        });
    }

    // Mobile overlay click to close
    if (mobileOverlay) {
        mobileOverlay.addEventListener('click', function() {
            sidebar.classList.add('-translate-x-full');
            mobileOverlay.classList.add('hidden');
            document.body.style.overflow = 'auto';
        });
    }

    // Handle window resize for responsive behavior
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 1024) {
            // Desktop view - ensure sidebar is visible and positioned correctly
            sidebar.classList.remove('-translate-x-full');
            mobileOverlay.classList.add('hidden');
            document.body.style.overflow = 'auto';
            // Reset main wrapper margin for desktop based on collapsed state
            if (!isCollapsed) {
                mainWrapper.classList.remove('ml-20');
                mainWrapper.classList.add('ml-[220px]');
            } else {
                mainWrapper.classList.remove('ml-[220px]');
                mainWrapper.classList.add('ml-20');
            }
        } else {
            // Mobile view - hide sidebar by default
            sidebar.classList.add('-translate-x-full');
            mobileOverlay.classList.add('hidden');
            document.body.style.overflow = 'auto';
            mainWrapper.classList.remove('ml-20', 'ml-[220px]');
        }
    });

    // Initialize sidebar state based on screen size
    if (window.innerWidth < 1024) {
        sidebar.classList.add('-translate-x-full');
        mainWrapper.classList.remove('ml-20', 'ml-[220px]');
    } else {
        // Desktop - ensure sidebar is visible and proper initial margin
        sidebar.classList.remove('-translate-x-full');
        mainWrapper.classList.remove('ml-20');
        mainWrapper.classList.add('ml-[220px]');
    }
    
    // Add transition class after initial load to prevent animation on page load
    setTimeout(() => {
        sidebar.classList.add('transition-transform', 'duration-300', 'ease-in-out');
    }, 100);

    // Add keyboard support for accessibility
    
    // Close mobile menu when clicking overlay
    if (mobileOverlay) {
        mobileOverlay.addEventListener('click', function() {
            sidebar.classList.add('-translate-x-full');
            mobileOverlay.classList.add('hidden');
            document.body.style.overflow = 'auto';
        });
    }

    // Close mobile menu on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !mobileOverlay.classList.contains('hidden')) {
            sidebar.classList.add('-translate-x-full');
            mobileOverlay.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
    });
});