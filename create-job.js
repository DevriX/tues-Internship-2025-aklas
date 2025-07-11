const modal = document.getElementById("categoryModal");
const categoryInput = document.getElementById("category-input");
const selectedDiv = document.getElementById("selected-categories");

function openModal() {
    modal.style.display = "flex"; // Changed to flex to center content
}

function closeModal() {
    modal.style.display = "none";
}

function saveCategories() {
    const checkboxes = document.querySelectorAll('.category-checkbox:checked');
    const selected = [];
    checkboxes.forEach(cb => selected.push(cb.value));

    selectedDiv.innerHTML = "Selected: " + selected.join(', ');
    closeModal();

    // Clear and recreate hidden inputs
    const form = document.querySelector('form');
    document.querySelectorAll('input[name="category[]"]').forEach(e => e.remove());
    selected.forEach(val => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'category[]';
        input.value = val;
        form.appendChild(input);
    });
}

// Close modal on outside click
window.onclick = function(event) {
    if (event.target == modal) {
        closeModal();
    }
}
