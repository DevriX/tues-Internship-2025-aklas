document.addEventListener('DOMContentLoaded', function() {
    // "Apply now" button click
    const applyBtn = document.querySelector('.button.button-wide');
    if (applyBtn) {
        applyBtn.addEventListener('click', function(e) {
            e.preventDefault();
            window.location.href = 'apply-submission.html';
        });
    }

    // Modal elements
    const modal = document.getElementById('maps-modal');
    const iframe = document.getElementById('maps-iframe');
    const closeBtn = document.getElementById('close-maps-modal');

    // Make job location clickable to open Google Maps in modal
    document.querySelectorAll('.job-location').forEach(function(loc) {
        loc.addEventListener('click', function() {
            const location = loc.textContent.trim();
            iframe.src = `https://www.google.com/maps?q=${encodeURIComponent(location)}&output=embed`;
            modal.style.display = 'flex';
        });
        loc.style.cursor = 'pointer';
        loc.style.color = '#3c71fe';
    });

    // Close modal
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            modal.style.display = 'none';
            iframe.src = '';
        });
    }

    // Optional: close modal when clicking outside the modal content
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.style.display = 'none';
                iframe.src = '';
            }
        });
    }
});