// Dark Mode Toggle - Global JavaScript
(function() {
    'use strict';
    
    // Dark mode toggle function
    window.toggleDarkMode = function() {
        const html = document.documentElement;
        const isDark = html.classList.contains('dark');
        
        if (isDark) {
            html.classList.remove('dark');
            localStorage.setItem('darkMode', 'false');
        } else {
            html.classList.add('dark');
            localStorage.setItem('darkMode', 'true');
        }
        
        // Update dark mode button icon
        updateDarkModeIcon();
    };
    
    // Update dark mode button icon
    function updateDarkModeIcon() {
        const darkModeButton = document.querySelector('[onclick="toggleDarkMode()"]');
        if (darkModeButton) {
            const icon = darkModeButton.querySelector('span');
            const isDark = document.documentElement.classList.contains('dark');
            
            if (isDark) {
                icon.textContent = '🌙'; // Moon for dark mode
            } else {
                icon.textContent = '☀️'; // Sun for light mode
            }
        }
    }
    
    // Load dark mode preference on page load
    function loadDarkModePreference() {
        const darkMode = localStorage.getItem('darkMode');
        if (darkMode === 'false') {
            document.documentElement.classList.remove('dark');
        } else {
            document.documentElement.classList.add('dark');
        }
        
        // Update icon after loading preference
        setTimeout(updateDarkModeIcon, 100);
    }
    
    // Initialize dark mode when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadDarkModePreference);
    } else {
        loadDarkModePreference();
    }
    
    // Also initialize immediately for faster loading
    loadDarkModePreference();
})();
