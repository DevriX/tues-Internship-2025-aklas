// For job name click on index.html
document.addEventListener('DOMContentLoaded', function() {
    // Job name click: go to single.html with job name in query string
    document.querySelectorAll('.job-title a').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const jobName = link.textContent.trim();
            window.location.href = `single.html?job=${encodeURIComponent(jobName)}`;
        });
    });

    // "Apply now" button click (works on any page)
    const applyBtn = document.querySelector('.button.button-wide');
    if (applyBtn) {
        applyBtn.addEventListener('click', function(e) {
            e.preventDefault();
            window.location.href = 'apply-submission.html';
        });
    }

    // Ensure modal HTML exists in the document
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

    // Convert all .job-location spans to <a> tags if not already
    document.querySelectorAll('.job-location').forEach(function(loc) {
        if (loc.tagName !== 'A') {
            const locationText = loc.textContent.trim();
            const a = document.createElement('a');
            a.href = '#';
            a.textContent = locationText;
            a.className = loc.className;
            a.removeAttribute('style'); // Remove inline styles, let CSS handle
            loc.replaceWith(a);
        }
    });

    // Add click event to all .job-location <a> tags
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

    // On single.html: Show job name from query string
    if (window.location.pathname.endsWith('single.html')) {
        const params = new URLSearchParams(window.location.search);
        const jobName = params.get('job');
        if (jobName) {
            // Find the job title and set it
            const jobTitle = document.querySelector('.job-title a');
            if (jobTitle) jobTitle.textContent = jobName;
        }
    }

    // Universal search bar functionality for all pages
    const searchInput = document.querySelector('.search-form-input');
    const jobsList = document.querySelector('.jobs-listing');
    if (searchInput && jobsList) {
        searchInput.addEventListener('input', function() {
            const query = searchInput.value.trim().toLowerCase();
            let anyMatch = false;
            jobsList.querySelectorAll('.job-card').forEach(function(card) {
                // Try to match job title, company, and location
                const title = card.querySelector('.job-title')?.textContent?.toLowerCase() || '';
                const company = card.querySelector('.meta-company')?.textContent?.toLowerCase() || '';
                const location = card.querySelector('.job-location')?.textContent?.toLowerCase() || '';
                const match =
                    title.includes(query) ||
                    company.includes(query) ||
                    location.includes(query);
                if (query === '') {
                    card.style.display = '';
                    anyMatch = true;
                } else if (match) {
                    card.style.display = '';
                    anyMatch = true;
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }
});