<!-- Job Details Popup Modal -->
<div id="job-details-modal" class="job-details-modal-overlay" style="display:none;">
  <div class="job-details-modal-content" id="job-details-modal-content">
    <button class="close-job-details-modal" id="close-job-details-modal" aria-label="Close">&times;</button>
    <h2 id="modal-job-title">Job Title</h2>
    <div class="modal-job-info">
      <p><strong>Company:</strong> <span id="modal-job-company"></span></p>
      <p><strong>Location:</strong> <span id="modal-job-location"></span></p>
      <p><strong>Salary:</strong> <span id="modal-job-salary"></span></p>
      <p><strong>Description:</strong></p>
      <p id="modal-job-description"></p>
      <p><strong>Created At:</strong> <span id="modal-job-created"></span></p>
      <p><strong>Approved:</strong> <span id="modal-job-approved"></span></p>
    </div>
  </div>
</div>
<script>
// Modal open/close logic
function openJobDetailsModal(job) {
  document.getElementById('modal-job-title').textContent = job.title;
  document.getElementById('modal-job-company').textContent = job.company || '-';
  document.getElementById('modal-job-location').textContent = job.location;
  document.getElementById('modal-job-salary').textContent = job.salary;
  document.getElementById('modal-job-description').textContent = job.description;
  document.getElementById('modal-job-created').textContent = job.created_at;
  document.getElementById('modal-job-approved').textContent = job.approved ? 'Yes' : 'No';
  document.getElementById('job-details-modal').style.display = 'block';
}
document.addEventListener('DOMContentLoaded', function() {
  var modal = document.getElementById('job-details-modal');
  var closeBtn = document.getElementById('close-job-details-modal');
  if (closeBtn) {
    closeBtn.onclick = function() {
      modal.style.display = 'none';
    };
  }
  // Close when clicking outside modal content
  modal.onclick = function(event) {
    if (event.target === modal) {
      modal.style.display = 'none';
    }
  };
});
</script> 