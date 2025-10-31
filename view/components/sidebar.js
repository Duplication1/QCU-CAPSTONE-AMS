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
    const mainWrapper = document.getElementById('main-wrapper');
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileClose = document.getElementById('mobile-close');
    const mobileOverlay = document.getElementById('mobile-overlay');
    const navTexts = document.querySelectorAll('.nav-text');
    const sidebarBrand = document.getElementById('sidebar-brand');
    const toggleIcon = document.getElementById('toggle-icon');
    
    let isCollapsed = false;

    // Update desktop sidebar state
    function updateDesktopSidebar() {
        if (isCollapsed) {
            sidebar.classList.remove('w-64');
            sidebar.classList.add('w-20');
            mainWrapper.classList.remove('lg:ml-64');
            mainWrapper.classList.add('lg:ml-20');
            hideTexts();
            toggleIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>';
        } else {
            sidebar.classList.remove('w-20');
            sidebar.classList.add('w-64');
            mainWrapper.classList.remove('lg:ml-20');
            mainWrapper.classList.add('lg:ml-64');
            showAllTexts();
            toggleIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7"></path>';
        }
    }

    // Hide navigation texts when collapsed
    function hideTexts() {
        navTexts.forEach(text => {
            text.style.opacity = '0';
            text.style.width = '0';
            text.style.overflow = 'hidden';
        });
        if (sidebarBrand) {
            sidebarBrand.style.opacity = '0';
            sidebarBrand.style.width = '0';
            sidebarBrand.style.overflow = 'hidden';
        }
    }

    // Show navigation texts when expanded
    function showAllTexts() {
        navTexts.forEach(text => {
            text.style.opacity = '1';
            text.style.width = 'auto';
            text.style.overflow = 'visible';
        });
        if (sidebarBrand) {
            sidebarBrand.style.opacity = '1';
            sidebarBrand.style.width = 'auto';
            sidebarBrand.style.overflow = 'visible';
        }
    }

    // Desktop toggle functionality
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            isCollapsed = !isCollapsed;
            updateDesktopSidebar();
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
            // Desktop view - ensure sidebar is positioned correctly
            sidebar.classList.add('-translate-x-full', 'lg:translate-x-0');
            mobileOverlay.classList.add('hidden');
            document.body.style.overflow = 'auto';
        } else {
            // Mobile view - hide sidebar by default
            sidebar.classList.add('-translate-x-full');
            mobileOverlay.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
    });

    // Initialize sidebar state based on screen size
    if (window.innerWidth < 1024) {
        sidebar.classList.add('-translate-x-full');
    }

    // Add keyboard support for accessibility
    document.addEventListener('keydown', function(e) {
        // Close mobile menu on Escape key
        if (e.key === 'Escape' && !mobileOverlay.classList.contains('hidden')) {
            sidebar.classList.add('-translate-x-full');
            mobileOverlay.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
    });

    // Add active state management for navigation items
    const navLinks = document.querySelectorAll('nav a[href^="#"]');
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Remove active class from all links
            navLinks.forEach(l => l.classList.remove('bg-blue-100', 'text-blue-700'));
            
            // Add active class to clicked link
            this.classList.add('bg-blue-100', 'text-blue-700');
            
            // Close mobile menu if open
            if (window.innerWidth < 1024) {
                sidebar.classList.add('-translate-x-full');
                mobileOverlay.classList.add('hidden');
                document.body.style.overflow = 'auto';
            }
        });
    });
});