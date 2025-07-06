document.addEventListener('DOMContentLoaded', function() {
            // 🔹 Job name click: redirect with ?job=...
        document.querySelectorAll('.job-title a').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const jobName = link.textContent.trim();
                window.location.href = `single.php?job=${encodeURIComponent(jobName)}`;
            });
        });

    // Ensure modal HTML exists
    function ensureMapsModal() {
        if (!document.getElementById('maps-modal')) {
            const modal = document.createElement('div');
            modal.id = 'maps-modal';
            modal.style.display = 'none';
            modal.style.position = 'fixed';
            modal.style.top = '0';
            modal.style.left = '0';
            modal.style.width = '100vw';
            modal.style.height = '100vh';
            modal.style.background = 'rgba(0,0,0,0.4)';
            modal.style.zIndex = '9999';
            modal.style.alignItems = 'center';
            modal.style.justifyContent = 'center';
            modal.innerHTML = `
                <div class="maps-modal-content">
                    <button id="close-maps-modal">&times;</button>
                    <iframe id="maps-iframe" src="" allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    <a id="maps-link" href="#" target="_blank">Open in Google Maps</a>
                </div>
            `;
            document.body.appendChild(modal);
        }
    }

    ensureMapsModal();

    // Convert .job-location to <a> if needed
    document.querySelectorAll('.job-location').forEach(function(loc) {
        if (loc.tagName !== 'A') {
            const locationText = loc.textContent.trim();
            const a = document.createElement('a');
            a.href = '#';
            a.textContent = locationText;
            a.className = loc.className;
            a.removeAttribute('style');
            loc.replaceWith(a);
        }
    });

    // Add map modal click to all .job-location links
    document.querySelectorAll('.job-location').forEach(function(loc) {
        loc.addEventListener('click', function(e) {
            e.preventDefault();
            const location = loc.textContent.trim();
            const mapsUrl = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(location)}`;
            const iframe = document.getElementById('maps-iframe');
            const mapsLink = document.getElementById('maps-link');
            const modal = document.getElementById('maps-modal');
            iframe.src = `https://www.google.com/maps?q=${encodeURIComponent(location)}&output=embed`;
            mapsLink.href = mapsUrl;
            mapsLink.textContent = 'Open in Google Maps';
            modal.style.display = 'flex';
        });
    });

    // Modal close logic
    const modal = document.getElementById('maps-modal');
    const iframe = document.getElementById('maps-iframe');
    const closeBtn = document.getElementById('close-maps-modal');
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            modal.style.display = 'none';
            iframe.src = '';
        });
    }
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.style.display = 'none';
                iframe.src = '';
            }
        });
    }

    // Show job name from query string on single.php
    if (window.location.pathname.endsWith('single.php')) {
        const params = new URLSearchParams(window.location.search);
        const jobName = params.get('job');
        if (jobName) {
            const jobTitle = document.querySelector('.job-title a');
            if (jobTitle) jobTitle.textContent = jobName;
        }
    }

    // Universal search
    const searchInput = document.querySelector('.search-form-input');
    const jobsList = document.querySelector('.jobs-listing');
    if (searchInput && jobsList) {
        searchInput.addEventListener('input', function() {
            const query = searchInput.value.trim().toLowerCase();
            jobsList.querySelectorAll('.job-card').forEach(function(card) {
                const title = card.querySelector('.job-title')?.textContent?.toLowerCase() || '';
                const company = card.querySelector('.meta-company')?.textContent?.toLowerCase() || '';
                const location = card.querySelector('.job-location')?.textContent?.toLowerCase() || '';
                const match = title.includes(query) || company.includes(query) || location.includes(query);
                card.style.display = match || query === '' ? '' : 'none';
            });
        });
    }

    // Collapsible vertical menu
    const menu = document.querySelector('.footer-vertical-menu');
    const toggleBtn = document.querySelector('.footer-vertical-menu .menu-toggle-arrow');
    if (menu && toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            menu.classList.toggle('collapsed');
        });
    }
});

// Vertical navbar toggling
window.addEventListener('DOMContentLoaded', function() {
    const menuBtn = document.getElementById('menu-toggle-btn');
    const verticalNavbar = document.getElementById('vertical-navbar');
    const closeBtn = document.getElementById('close-vertical-navbar');

    if (menuBtn && verticalNavbar) {
        menuBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            verticalNavbar.classList.add('open');
        });
    }

    if (closeBtn && verticalNavbar) {
        closeBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            verticalNavbar.classList.remove('open');
        });
    }

    document.addEventListener('click', function(event) {
        if (
            verticalNavbar &&
            verticalNavbar.classList.contains('open') &&
            !verticalNavbar.contains(event.target) &&
            event.target !== menuBtn
        ) {
            verticalNavbar.classList.remove('open');
        }
    });
});
