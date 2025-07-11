document.addEventListener('DOMContentLoaded', function() {
    // Apply now button
    const applyBtn = document.querySelector('.button.button-wide');
    if (applyBtn) {
        applyBtn.addEventListener('click', function(e) {
            e.preventDefault();
            window.location.href = 'apply-submission.php';
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
            a.removeAttribute('style'); // Let CSS handle styling
            loc.replaceWith(a);
        }
    });

    // Add click event to all .job-location <a> tags to open modal with Google Maps iframe
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
    if (window.location.pathname.endsWith('single.php')) {
        const params = new URLSearchParams(window.location.search);
        const jobName = params.get('job');
        if (jobName) {
            const jobTitle = document.querySelector('.job-title a');
            if (jobTitle) jobTitle.textContent = jobName;
        }
    }

    // Collapsible vertical menu toggle
    var menu = document.querySelector('.footer-vertical-menu');
    var toggleBtn = document.querySelector('.footer-vertical-menu .menu-toggle-arrow');
    if (menu && toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            menu.classList.toggle('collapsed');
        });
    }

    // -------------------------------
    // CATEGORY TAGS: show more/less toggle logic
    // -------------------------------
    const tagsList = document.getElementById('category-tags-list');
    if (!tagsList) return;

    // Category selection logic
    tagsList.addEventListener('click', function(e) {
        // Only handle clicks on category tags, not the show more/less button
        if (e.target.classList.contains('list-item-link') && !e.target.closest('.show-more-li')) {
            e.preventDefault();
            const cat = e.target.getAttribute('data-category');
            const url = new URL(window.location);
            let categories = url.searchParams.getAll('categories[]');
            if (categories.includes(cat)) {
                categories = categories.filter(c => c !== cat);
            } else {
                categories.push(cat);
            }
            url.searchParams.delete('categories[]');
            categories.forEach(c => url.searchParams.append('categories[]', c));
            window.location = url.toString();
        }
    });

    // Show more/less logic
    const showMoreBtn = document.getElementById('show-more-categories');
    const showMoreLi = tagsList.querySelector('.show-more-li');
    const hiddenItems = tagsList.querySelectorAll('.hidden-category');

    function updateCategories() {
        if (!showMoreLi || !showMoreBtn) return;
        if (hiddenItems.length === 0) {
            showMoreLi.style.display = 'none';
            return;
        }
        showMoreLi.style.display = 'inline-block';
        showMoreBtn.textContent = tagsList.classList.contains('expanded') ? '−' : '+';
    }

    if (showMoreBtn && showMoreLi) {
        showMoreBtn.addEventListener('click', function(e) {
            e.preventDefault();
            tagsList.classList.toggle('expanded');
            updateCategories();
        });
        // Initial state
        tagsList.classList.remove('expanded');
        updateCategories();
    }

    // Auto-submit search form if input is cleared (becomes empty)
    document.querySelectorAll('.search-form-input').forEach(function(input) {
        let lastValue = input.value;
        input.addEventListener('input', function() {
            if (lastValue.trim() !== '' && input.value.trim() === '') {
                input.form.submit();
            }
            lastValue = input.value;
        });
    });
});


// Toggle vertical navbar from burger menu
window.addEventListener('DOMContentLoaded', function() {
    var menuBtn = document.getElementById('menu-toggle-btn');
    var verticalNavbar = document.getElementById('vertical-navbar');
    var closeBtn = document.getElementById('close-vertical-navbar');

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
    // Optional: clicking outside closes the navbar
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


//my sumbision js
let formToDelete = null;
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.delete-application-btn').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      formToDelete = btn.closest('form');
      document.getElementById('confirm-delete-modal').style.display = 'flex';
    });
  });
  document.getElementById('confirm-delete-yes').onclick = function() {
    document.getElementById('confirm-delete-modal').style.display = 'none';
    if (formToDelete) {
      formToDelete.submit();
      formToDelete = null;
    }
  };
  document.getElementById('confirm-delete-no').onclick = function() {
    document.getElementById('confirm-delete-modal').style.display = 'none';
    formToDelete = null;
  };
  document.getElementById('confirm-delete-modal').onclick = function(e) {
    if (e.target === this) {
      this.style.display = 'none';
      formToDelete = null;
    }
  };
});