// Dark Mode - Always enabled
(function() {
    'use strict';
    
    // Force dark mode always
    function forceDarkMode() {
        document.documentElement.classList.add('dark');
        localStorage.setItem('darkMode', 'true');
    }
    
    // Initialize dark mode immediately
    forceDarkMode();
    
    // Also initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', forceDarkMode);
    } else {
        forceDarkMode();
    }
})();
