<!-- Submission Details Popup Modal -->
<div id="submission-details-modal" class="job-details-modal-overlay" style="display:none; ">
  <div class="job-details-modal-content" id="submission-details-modal-content">
    <button class="close-job-details-modal" id="close-submission-details-modal" aria-label="Close">&times;</button>
    <h2 id="modal-submission-title">Submission Details</h2>
    <div class="modal-job-info">
      <p><strong>Name:</strong> <span id="modal-submission-name"></span></p>
      <p><strong>Email:</strong> <span id="modal-submission-email"></span></p>
      <p><strong>Submitted At:</strong> <span id="modal-submission-date"></span></p>
      <div>
        <strong>Files:</strong> <span id="modal-files"></span>
      </div>
      <p><strong>Cover Letter:</strong></p>
      <p><strong>Position:</strong> <span id="modal-job-title"></span></p>
      <p><strong>Company:</strong> <span id="modal-company-name"></span></p>
      <p id="modal-submission-cover"></p>
    </div>
  </div>
</div>
<script>
function openSubmissionDetailsModal(sub) {
  document.getElementById('modal-submission-name').textContent = sub.name;
  document.getElementById('modal-submission-email').textContent = sub.email;
  document.getElementById('modal-submission-date').textContent = sub.date;
  // Display all files
  const filesSpan = document.getElementById('modal-files');
  filesSpan.innerHTML = '';
  if (sub.files && Array.isArray(sub.files) && sub.files.length > 0) {
    filesSpan.innerHTML = sub.files.map(f => {
      const fname = f.split('/').pop();
      return `<a href="${f}" target="_blank">${fname}</a>`;
    }).join('<br>');
  } else {
    filesSpan.textContent = '-';
  }
  document.getElementById('modal-job-title').textContent = sub.job_title;
  document.getElementById('modal-company-name').textContent = sub.company_name;
  document.getElementById('modal-submission-cover').textContent = sub.cover;
  document.getElementById('submission-details-modal').style.display = 'block';
}
document.addEventListener('DOMContentLoaded', function() {
  var modal = document.getElementById('submission-details-modal');
  var closeBtn = document.getElementById('close-submission-details-modal');
  if (closeBtn) {
    closeBtn.onclick = function() {
      modal.style.display = 'none';
    };
  }
  modal.onclick = function(event) {
    if (event.target === modal) {
      modal.style.display = 'none';
    }
  };
});
</script> 