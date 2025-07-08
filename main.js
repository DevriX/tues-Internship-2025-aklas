// For job name click on index.html
document.addEventListener('DOMContentLoaded', function() {
    // Job name click: go to single.html with job name in query string
    // document.querySelectorAll('.job-title a').forEach(function(link) {
    //     link.addEventListener('click', function(e) {
    //         e.preventDefault();
    //         const jobName = link.textContent.trim();
    //     });
    // });

    // "Apply now" button click (works on any page)
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
    if (window.location.pathname.endsWith('single.php')) {
        const params = new URLSearchParams(window.location.search);
        const jobName = params.get('job');
        if (jobName) {
            // Find the job title and set it
            const jobTitle = document.querySelector('.job-title a');
            if (jobTitle) jobTitle.textContent = jobName;
        }
    }

    // Universal search bar functionality for all pages
    // const searchInput = document.querySelector('.search-form-input');
    // const jobsList = document.querySelector('.jobs-listing');
    // if (searchInput && jobsList) {
    //     searchInput.addEventListener('input', function() {
    //         const query = searchInput.value.trim().toLowerCase();
    //         let anyMatch = false;
    //         jobsList.querySelectorAll('.job-card').forEach(function(card) {
    //             // Try to match job title, company, and location
    //             const title = card.querySelector('.job-title')?.textContent?.toLowerCase() || '';
    //             const company = card.querySelector('.meta-company')?.textContent?.toLowerCase() || '';
    //             const location = card.querySelector('.job-location')?.textContent?.toLowerCase() || '';
    //             const match =
    //                 title.includes(query) ||
    //                 company.includes(query) ||
    //                 location.includes(query);
    //             if (query === '') {
    //                 card.style.display = '';
    //                 anyMatch = true;
    //             } else if (match) {
    //                 card.style.display = '';
    //                 anyMatch = true;
    //             } else {
    //                 card.style.display = 'none';
    //             }
    //         });
    //     });
    // }

    // Collapsible vertical menu toggle
    var menu = document.querySelector('.footer-vertical-menu');
    var toggleBtn = document.querySelector('.footer-vertical-menu .menu-toggle-arrow');
    if (menu && toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            menu.classList.toggle('collapsed');
        });
    }

    // Category tags overflow logic for index.php
    const tagsList = document.getElementById('category-tags-list');
    const showMoreLi = tagsList ? tagsList.querySelector('.show-more-li') : null;
    if (tagsList && showMoreLi) {
        function checkOverflow() {
            showMoreLi.style.display = 'none';
            const items = Array.from(tagsList.querySelectorAll('.list-item:not(.show-more-li)'));
            if (items.length < 1) return;
            let firstTop = items[0].offsetTop;
            let secondLineTop = null;
            for (let i = 1; i < items.length; i++) {
                if (items[i].offsetTop > firstTop) {
                    secondLineTop = items[i].offsetTop;
                    break;
                }
            }
            if (!secondLineTop) return;
            let thirdLineTop = null;
            for (let i = 1; i < items.length; i++) {
                if (items[i].offsetTop > secondLineTop) {
                    thirdLineTop = items[i].offsetTop;
                    break;
                }
            }
            if (thirdLineTop) {
                // Hide all items that are on the third line or below
                items.forEach(item => {
                    if (item.offsetTop >= thirdLineTop) {
                        item.style.display = 'none';
                    } else {
                        item.style.display = '';
                    }
                });
                // Insert the show-more-li after the last visible item on the second line
                let lastSecondLineIndex = -1;
                for (let i = items.length - 1; i >= 0; i--) {
                    if (items[i].offsetTop === secondLineTop) {
                        lastSecondLineIndex = i;
                        break;
                    }
                }
                if (lastSecondLineIndex !== -1) {
                    items[lastSecondLineIndex].after(showMoreLi);
                } else {
                    items[items.length - 1].after(showMoreLi);
                }
                showMoreLi.style.display = '';
            } else {
                // Show all items if only two lines
                items.forEach(item => item.style.display = '');
                showMoreLi.style.display = 'none';
            }
        }
        checkOverflow();
        window.addEventListener('resize', checkOverflow);
    }
    const showMoreBtn = document.getElementById('show-more-categories');
    if (showMoreBtn) {
        showMoreBtn.addEventListener('click', function() {
            // Show all items
            const items = tagsList.querySelectorAll('.list-item');
            items.forEach(item => item.style.display = '');
            showMoreLi.style.display = 'none';
        });
    }

    // Auto-submit search form if input is cleared (becomes empty)
    document.querySelectorAll('.search-form-input').forEach(function(input) {
        let lastValue = input.value;
        input.addEventListener('input', function() {
            console.log('Input event:', lastValue, '->', input.value); // Add this line
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

document.addEventListener('DOMContentLoaded', function() {
    // Wait for layout paint
    setTimeout(() => {
        // Category tags overflow logic for index.php
        const tagsList = document.getElementById('category-tags-list');
        const showMoreLi = tagsList ? tagsList.querySelector('.show-more-li') : null;
        if (tagsList && showMoreLi) {
            function checkOverflow() {
                showMoreLi.style.display = 'none';
                const items = Array.from(tagsList.querySelectorAll('.list-item:not(.show-more-li)'));
                if (items.length < 1) return;
                let firstTop = items[0].offsetTop;
                let secondLineTop = null;
                for (let i = 1; i < items.length; i++) {
                    if (items[i].offsetTop > firstTop) {
                        secondLineTop = items[i].offsetTop;
                        break;
                    }
                }
                if (!secondLineTop) return;
                let thirdLineTop = null;
                for (let i = 1; i < items.length; i++) {
                    if (items[i].offsetTop > secondLineTop) {
                        thirdLineTop = items[i].offsetTop;
                        break;
                    }
                }
                if (thirdLineTop) {
                    // Hide all items that are on the third line or below
                    items.forEach(item => {
                        if (item.offsetTop >= thirdLineTop) {
                            item.style.display = 'none';
                        } else {
                            item.style.display = '';
                        }
                    });
                    // Insert the show-more-li after the last visible item on the second line
                    let lastSecondLineIndex = -1;
                    for (let i = items.length - 1; i >= 0; i--) {
                        if (items[i].offsetTop === secondLineTop) {
                            lastSecondLineIndex = i;
                            break;
                        }
                    }
                    if (lastSecondLineIndex !== -1) {
                        items[lastSecondLineIndex].after(showMoreLi);
                    } else {
                        items[items.length - 1].after(showMoreLi);
                    }
                    showMoreLi.style.display = '';
                } else {
                    // Show all items if only two lines
                    items.forEach(item => item.style.display = '');
                    showMoreLi.style.display = 'none';
                }
            }
            checkOverflow();
        }
    }, 100); // Delay a little to ensure layout is ready
});