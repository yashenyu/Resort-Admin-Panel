/**
 * Resort Admin Panel - Common JavaScript Functions
 */

// Show alert message with auto-dismiss
function showAlert(message, type = 'success', duration = 5000) {
  // Create alert element
  const alertEl = document.createElement('div');
  alertEl.className = `alert alert-${type} alert-dismissible fade show`;
  alertEl.setAttribute('role', 'alert');
  alertEl.innerHTML = `
    ${message}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  `;
  
  // Find alert container or create one
  let alertContainer = document.querySelector('.alert-container');
  if (!alertContainer) {
    alertContainer = document.createElement('div');
    alertContainer.className = 'alert-container position-fixed top-0 end-0 p-3';
    alertContainer.style.zIndex = '1050';
    document.body.appendChild(alertContainer);
  }
  
  // Add to container
  alertContainer.appendChild(alertEl);
  
  // Auto dismiss
  if (duration > 0) {
    setTimeout(() => {
      if (alertEl) {
        const bsAlert = new bootstrap.Alert(alertEl);
        bsAlert.close();
      }
    }, duration);
  }
}

// Format currency
function formatCurrency(amount, currency = '₱') {
  return `${currency}${parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,')}`;
}

// Format date
function formatDate(dateString) {
  const options = { year: 'numeric', month: 'short', day: 'numeric' };
  return new Date(dateString).toLocaleDateString(undefined, options);
}

// Add active state to sidebar links based on current page
document.addEventListener('DOMContentLoaded', function() {
  const currentPage = window.location.pathname.split('/').pop();
  const sidebarLinks = document.querySelectorAll('.sidebar-links .nav-link');
  
  sidebarLinks.forEach(link => {
    const href = link.getAttribute('href');
    if (href === currentPage) {
      link.classList.add('active');
    }
  });
});

// Add confirmation for dangerous actions
document.addEventListener('DOMContentLoaded', function() {
  const dangerButtons = document.querySelectorAll('.btn-danger');
  
  dangerButtons.forEach(button => {
    if (!button.hasAttribute('data-no-confirm')) {
      button.addEventListener('click', function(e) {
        if (!confirm('Are you sure you want to proceed with this action?')) {
          e.preventDefault();
        }
      });
    }
  });
});

// DataTable default configuration
const dataTableDefaults = {
  responsive: true,
  language: {
    search: "_INPUT_",
    searchPlaceholder: "Search...",
    lengthMenu: "Show _MENU_ entries",
    info: "Showing _START_ to _END_ of _TOTAL_ entries",
    infoEmpty: "Showing 0 to 0 of 0 entries",
    infoFiltered: "(filtered from _MAX_ total entries)"
  },
  dom: 'Bfrtip',
  buttons: [
    'copy', 'csv', 'excel', 'pdf', 'print'
  ]
};
