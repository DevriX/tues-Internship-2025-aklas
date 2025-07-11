document.addEventListener('DOMContentLoaded', function() {
  // Bulk Edit Mode Toggle
  const toggleBtn = document.getElementById('toggle-bulk-edit');
  const bulkForm = document.getElementById('bulk-assign-form');
  let bulkMode = false;
  toggleBtn.addEventListener('click', function() {
    bulkMode = !bulkMode;
    document.querySelectorAll('.bulk-job-checkbox').forEach(cb => {
      cb.style.display = bulkMode ? 'inline-block' : 'none';
      cb.checked = false;
    });
    bulkForm.style.display = bulkMode ? 'flex' : 'none';
    toggleBtn.textContent = bulkMode ? 'Exit Bulk Edit' : 'Bulk Edit';
  });

  // Bulk Assign Submit
  bulkForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const selected = Array.from(document.querySelectorAll('.bulk-job-checkbox:checked')).map(cb => cb.value);
    const categoryId = document.getElementById('bulk-category-select').value;
    if (!categoryId || selected.length === 0) {
      alert('Please select at least one job and a category.');
      return;
    }
    fetch('bulk-assign-category.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ job_ids: selected, category_id: categoryId })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert('Category assigned successfully!');
        location.reload();
      } else {
        alert('Error: ' + (data.error || 'Unknown error'));
      }
    })
    .catch(() => alert('Request failed.'));
  });
});

// Restore Google Maps modal and job details modal functionality

document.addEventListener('DOMContentLoaded', function() {
  // Location link click: open Google Maps modal, stop propagation
  document.querySelectorAll('.job-location-link').forEach(function(link) {
    link.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      const location = link.getAttribute('data-location');
      const iframe = document.getElementById('maps-iframe');
      const modal = document.getElementById('maps-modal');
      if (iframe && modal) {
        iframe.src = `https://www.google.com/maps?q=${encodeURIComponent(location)}&output=embed`;
        modal.style.display = 'flex';
      }
      // Optionally update the maps-link href
      const mapsLink = document.getElementById('maps-link');
      if (mapsLink) {
        mapsLink.href = `https://www.google.com/maps?q=${encodeURIComponent(location)}`;
      }
    });
  });

  // Job card click: open job details modal
  document.querySelectorAll('.job-card').forEach(function(card) {
    card.addEventListener('click', function(e) {
      // Prevent opening modal if clicking on approve/reject buttons, location link, or bulk edit checkbox
      if (
        e.target.closest('form') ||
        e.target.classList.contains('job-location-link') ||
        (e.target.classList && e.target.classList.contains('bulk-job-checkbox'))
      ) return;
      const job = {
        title: card.getAttribute('data-title'),
        company: card.getAttribute('data-company'),
        location: card.getAttribute('data-location'),
        salary: card.getAttribute('data-salary'),
        description: card.getAttribute('data-description'),
        created_at: card.getAttribute('data-created_at'),
        approved: card.getAttribute('data-approved') === '1',
        categories: card.getAttribute('data-categories')
      };
      openJobDetailsModal(job);
    });
  });
});