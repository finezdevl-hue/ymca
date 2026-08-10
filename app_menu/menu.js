// Synchronously apply theme on script load to prevent light-flash
(function() {
    var savedTheme = localStorage.getItem('site-theme') || 'light';
    var htmlEl = document.documentElement;
    if (savedTheme === 'dark') {
        htmlEl.classList.add('dark-theme');
    }
})();

var menuLoaded = false;

function loadMenu(force) {
    if (force === true) menuLoaded = false;
    if (menuLoaded) return;
    
    var container = document.getElementById('divMenuContainer');
    if (!container) return;
    
    menuLoaded = true;
    
    fetch("../app_menu/menu.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: "action=load_menu_data"
    })
    .then(function(response) {
        return response.text();
    })
    .then(function(html) {
        container.innerHTML = html;
        
        // Dynamic avatar injection in sidebar
        var sideMenu = document.getElementById('side-menu');
        if (sideMenu && sideMenu.getAttribute('data-avatar')) {
            var avatarUrl = sideMenu.getAttribute('data-avatar');
            var profileImg = document.querySelector('.profile-element img, .navbar-default img');
            if (profileImg) {
                profileImg.src = avatarUrl;
                profileImg.style.paddingTop = '0';
                profileImg.style.width = '64px';
                profileImg.style.height = '64px';
                profileImg.style.objectFit = 'cover';
            }
        }
        
        function initMetis() {
            if (window.jQuery && window.jQuery.fn.metisMenu) {
                var $sideMenu = window.jQuery('#side-menu');
                $sideMenu.removeData('mm');
                $sideMenu.metisMenu();
                return true;
            }
            return false;
        }

        if (!initMetis()) {
            var checkInterval = setInterval(function() {
                if (initMetis()) clearInterval(checkInterval);
            }, 100);
        }
    })
    .catch(function(error) {
        console.error("loadMenu error:", error);
        menuLoaded = false;
    });
}

// Inject UX enhancements (Theme toggle, Scroll to top, Toast notifications)
function initUxEnhancements() {
    // 1. Apply theme to body
    var savedTheme = localStorage.getItem('site-theme') || 'light';
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-theme');
    }
    
    // 2. Inject Theme Toggle next to logout
    var logoutLink = document.querySelector('a[href*="logout.php"]');
    if (logoutLink && !document.getElementById('theme-toggle-btn')) {
        var isLi = logoutLink.parentElement.tagName === 'LI';
        var container = document.createElement(isLi ? 'li' : 'span');
        container.id = 'theme-toggle-container';
        
        var toggleBtn = document.createElement('a');
        toggleBtn.id = 'theme-toggle-btn';
        toggleBtn.style.cursor = 'pointer';
        toggleBtn.style.display = 'inline-flex';
        toggleBtn.style.alignItems = 'center';
        toggleBtn.style.justifyContent = 'center';
        toggleBtn.style.padding = '8px 12px';
        toggleBtn.style.marginRight = '8px';
        toggleBtn.style.borderRadius = '10px';
        toggleBtn.style.border = '1px solid rgba(255,255,255,0.08)';
        toggleBtn.style.transition = 'all 0.25s ease';
        
        // Find if we are in admin dashboard or standard page to inherit styling
        if (logoutLink.className) {
            toggleBtn.className = logoutLink.className;
        }
        
        var icon = document.createElement('i');
        icon.className = savedTheme === 'dark' ? 'fa fa-sun-o' : 'fa fa-moon-o';
        icon.style.fontSize = '15px';
        icon.style.color = savedTheme === 'dark' ? '#f59e0b' : '#64748b';
        
        var label = document.createElement('span');
        label.innerText = ' Theme';
        label.style.fontSize = '13.5px';
        label.style.fontWeight = '500';
        
        toggleBtn.appendChild(icon);
        toggleBtn.appendChild(label);
        container.appendChild(toggleBtn);
        
        // Insert before logout
        logoutLink.parentElement.parentNode.insertBefore(container, logoutLink.parentElement);
        
        toggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            var currentTheme = localStorage.getItem('site-theme') || 'light';
            var newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            localStorage.setItem('site-theme', newTheme);
            
            if (newTheme === 'dark') {
                document.documentElement.classList.add('dark-theme');
                document.body.classList.add('dark-theme');
                icon.className = 'fa fa-sun-o';
                icon.style.color = '#f59e0b';
                window.showToast('Dark theme activated', 'info');
            } else {
                document.documentElement.classList.remove('dark-theme');
                document.body.classList.remove('dark-theme');
                icon.className = 'fa fa-moon-o';
                icon.style.color = '#64748b';
                window.showToast('Light theme activated', 'info');
            }
        });
    }
    
    // 3. Inject Scroll-to-Top button
    if (!document.getElementById('scroll-to-top-btn')) {
        var scrollBtn = document.createElement('button');
        scrollBtn.id = 'scroll-to-top-btn';
        scrollBtn.innerHTML = '<i class="fa fa-chevron-up"></i>';
        scrollBtn.setAttribute('aria-label', 'Scroll to Top');
        
        scrollBtn.style.position = 'fixed';
        scrollBtn.style.bottom = '30px';
        scrollBtn.style.right = '30px';
        scrollBtn.style.width = '46px';
        scrollBtn.style.height = '46px';
        scrollBtn.style.borderRadius = '50%';
        scrollBtn.style.background = 'linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%)';
        scrollBtn.style.color = '#fff';
        scrollBtn.style.border = 'none';
        scrollBtn.style.cursor = 'pointer';
        scrollBtn.style.boxShadow = '0 6px 16px rgba(29, 78, 216, 0.35)';
        scrollBtn.style.display = 'none';
        scrollBtn.style.zIndex = '9999';
        scrollBtn.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
        scrollBtn.style.alignItems = 'center';
        scrollBtn.style.justifyContent = 'center';
        
        document.body.appendChild(scrollBtn);
        
        window.addEventListener('scroll', function() {
            if (window.scrollY > 250) {
                scrollBtn.style.display = 'flex';
            } else {
                scrollBtn.style.display = 'none';
            }
        });
        
        scrollBtn.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
        
        scrollBtn.addEventListener('mouseenter', function() {
            scrollBtn.style.transform = 'translateY(-4px) scale(1.05)';
            scrollBtn.style.boxShadow = '0 8px 22px rgba(29, 78, 216, 0.55)';
        });
        scrollBtn.addEventListener('mouseleave', function() {
            scrollBtn.style.transform = 'translateY(0) scale(1)';
            scrollBtn.style.boxShadow = '0 6px 16px rgba(29, 78, 216, 0.35)';
        });
    }
}

// 4. Inject reusable premium Toast notification system
window.showToast = function(message, type) {
    type = type || 'info'; // success, warning, error, info
    
    var container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.style.position = 'fixed';
        container.style.top = '24px';
        container.style.right = '24px';
        container.style.zIndex = '100000';
        container.style.display = 'flex';
        container.style.flexDirection = 'column';
        container.style.gap = '12px';
        document.body.appendChild(container);
    }
    
    var toast = document.createElement('div');
    toast.className = 'toast-item toast-' + type;
    toast.style.background = 'var(--card-bg, #ffffff)';
    toast.style.color = 'var(--text-primary, #1e293b)';
    toast.style.padding = '14px 18px';
    toast.style.borderRadius = '14px';
    toast.style.boxShadow = '0 12px 30px rgba(0,0,0,0.12)';
    toast.style.display = 'flex';
    toast.style.alignItems = 'center';
    toast.style.gap = '12px';
    toast.style.minWidth = '300px';
    toast.style.maxWidth = '420px';
    toast.style.border = '1px solid var(--border-color, #f1f5f9)';
    toast.style.borderLeft = '5px solid #3b82f6';
    toast.style.transform = 'translateX(120%)';
    toast.style.transition = 'all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1)';
    
    var icon = 'info-circle';
    var color = '#3b82f6';
    if (type === 'success') {
        icon = 'check-circle';
        color = '#10b981';
    } else if (type === 'warning') {
        icon = 'exclamation-circle';
        color = '#f59e0b';
    } else if (type === 'error') {
        icon = 'times-circle';
        color = '#ef4444';
    }
    toast.style.borderLeftColor = color;
    
    toast.innerHTML = '<i class="fa fa-' + icon + '" style="color:' + color + '; font-size: 18px;"></i>' +
                      '<div style="font-weight: 600; font-size: 14px; flex: 1; font-family:\'Outfit\',sans-serif;">' + message + '</div>' +
                      '<i class="fa fa-times" style="color:#94a3b8; cursor:pointer; font-size:12px; transition:color 0.2s;" onmouseenter="this.style.color=\'#64748b\'" onmouseleave="this.style.color=\'#94a3b8\'" onclick="this.parentElement.remove()"></i>';
    
    container.appendChild(toast);
    
    // Animate in
    setTimeout(function() {
        toast.style.transform = 'translateX(0)';
    }, 40);
    
    // Auto dismiss
    setTimeout(function() {
        toast.style.transform = 'translateX(120%)';
        toast.style.opacity = '0';
        setTimeout(function() {
            toast.remove();
        }, 350);
    }, 4500);
};

// Auto-trigger load
document.addEventListener("DOMContentLoaded", function() {
    loadMenu();
    initUxEnhancements();
    initPwaAndSwipe();
});
window.addEventListener("load", initUxEnhancements);

// Close sidebar on mobile when clicking outside
document.addEventListener('click', function(e) {
    if (window.innerWidth <= 768) {
        var body = document.body;
        var sidebar = document.querySelector('.navbar-default');
        var hamburger = document.querySelector('.navbar-minimalize, .mem-hamburger');
        
        if (body.classList.contains('mini-navbar')) {
            if (sidebar && !sidebar.contains(e.target) && hamburger && !hamburger.contains(e.target)) {
                body.classList.remove('mini-navbar');
            }
        }
    }
});

// Touch swipe navigation & PWA loader
function initPwaAndSwipe() {
    // 1. Register PWA Manifest dynamically
    if (!document.querySelector('link[rel="manifest"]')) {
        var link = document.createElement('link');
        link.rel = 'manifest';
        link.href = '../manifest.json';
        document.head.appendChild(link);
    }
    
    // 2. Register Service Worker
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('../sw.js')
        .then(function(reg) {
            console.log('ServiceWorker registration successful');
        })
        .catch(function(err) {
            console.log('ServiceWorker registration failed: ', err);
        });
    }

    // 3. Touch swipe navigation for mobile sidebar
    var touchStartX = 0;
    var touchStartY = 0;
    var touchEndX = 0;
    var touchEndY = 0;
    
    document.addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
        touchStartY = e.changedTouches[0].screenY;
    }, { passive: true });
    
    document.addEventListener('touchend', function(e) {
        touchEndX = e.changedTouches[0].screenX;
        touchEndY = e.changedTouches[0].screenY;
        handleSwipe();
    }, { passive: true });
    
    function handleSwipe() {
        var diffX = touchEndX - touchStartX;
        var diffY = touchEndY - touchStartY;
        
        // Ensure it's a horizontal swipe, not vertical scroll
        if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 80) {
            var body = document.body;
            if (diffX > 0) {
                // Swipe Left-to-Right: Open Menu
                if (window.innerWidth <= 768 && !body.classList.contains('mini-navbar')) {
                    body.classList.add('mini-navbar');
                }
            } else {
                // Swipe Right-to-Left: Close Menu
                if (window.innerWidth <= 768 && body.classList.contains('mini-navbar')) {
                    body.classList.remove('mini-navbar');
                }
            }
        }
    }
}

// Global Date Picker Auto-Initializer for Desktop Pages
(function() {
    function getTodayDateString() {
        var now = new Date();
        var yyyy = now.getFullYear();
        var mm = String(now.getMonth() + 1).padStart(2, '0');
        var dd = String(now.getDate()).padStart(2, '0');
        return yyyy + '-' + mm + '-' + dd;
    }

    function initCurrentDatePickers() {
        var todayVal = getTodayDateString();
        if (window.jQuery) {
            window.jQuery('input[type="date"]').each(function() {
                var currentVal = window.jQuery(this).val();
                if (!currentVal || currentVal === '0000-00-00') {
                    window.jQuery(this).val(todayVal);
                }
            });
        } else {
            var inputs = document.querySelectorAll('input[type="date"]');
            inputs.forEach(function(input) {
                if (!input.value || input.value === '0000-00-00') {
                    input.value = todayVal;
                }
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCurrentDatePickers);
    } else {
        initCurrentDatePickers();
    }

    if (window.jQuery) {
        window.jQuery(document).on('show.bs.modal shown.bs.modal', function() {
            initCurrentDatePickers();
        });
        window.jQuery(document).on('click focus', 'input[type="date"]', function() {
            var currentVal = window.jQuery(this).val();
            if (!currentVal || currentVal === '0000-00-00') {
                window.jQuery(this).val(getTodayDateString());
            }
        });
    }
})();

