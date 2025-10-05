/**
 * Dashboard JavaScript - Origin Driving School Management System
 *FILE PATH: public/js/dashboard.js
 *
 * Handles all dashboard-specific interactions and functionality
 *
 * @author [SUJAN DARJI K231673 AND ANTHONY ALLAN REGALADO K231715]
 * @version 1.0
 */

document.addEventListener("DOMContentLoaded", function () {
  // Initialize all dashboard features
  initDeleteConfirmations();
  initSearchFunctionality();
  initModalFunctionality();
  initFormSubmissions();
  initTooltips();
  initDatePickers();
  initTableSorting();
  initPrintButtons();

  console.log("Dashboard JavaScript loaded successfully");
});

/**
 * Delete Confirmation
 * Adds confirmation dialog to all delete buttons
 */
function initDeleteConfirmations() {
  const deleteButtons = document.querySelectorAll(
    "[data-delete], .btn-delete, .delete-btn"
  );

  deleteButtons.forEach(function (button) {
    button.addEventListener("click", function (e) {
      const message =
        this.getAttribute("data-confirm-message") ||
        "Are you sure you want to delete this item? This action cannot be undone.";

      if (!confirm(message)) {
        e.preventDefault();
        return false;
      }
    });
  });
}

/**
 * Search Functionality
 * Implements real-time search with debouncing
 */
function initSearchFunctionality() {
  const searchInputs = document.querySelectorAll(
    "[data-search], .search-input"
  );

  searchInputs.forEach(function (input) {
    input.addEventListener(
      "input",
      debounce(function (e) {
        const searchTerm = e.target.value.toLowerCase().trim();
        performSearch(searchTerm);
      }, 300)
    );
  });
}

/**
 * Perform search on table rows
 */
function performSearch(searchTerm) {
  const table = document.querySelector("table tbody");

  if (!table) return;

  const rows = table.querySelectorAll("tr");
  let visibleCount = 0;

  rows.forEach(function (row) {
    const text = row.textContent.toLowerCase();

    if (text.includes(searchTerm)) {
      row.style.display = "";
      visibleCount++;
    } else {
      row.style.display = "none";
    }
  });

  // Show "no results" message if needed
  updateNoResultsMessage(visibleCount);
}

/**
 * Update no results message
 */
function updateNoResultsMessage(count) {
  let noResultsRow = document.getElementById("no-results-row");
  const table = document.querySelector("table tbody");

  if (!table) return;

  if (count === 0) {
    if (!noResultsRow) {
      const colspan =
        table.querySelector("tr")?.querySelectorAll("td, th").length || 5;
      noResultsRow = document.createElement("tr");
      noResultsRow.id = "no-results-row";
      noResultsRow.innerHTML = `
                <td colspan="${colspan}" style="text-align: center; padding: 2rem; color: #666;">
                    <i class="fas fa-search" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                    No results found
                </td>
            `;
      table.appendChild(noResultsRow);
    }
  } else {
    if (noResultsRow) {
      noResultsRow.remove();
    }
  }
}

/**
 * Modal Functionality
 * Handle opening and closing modals
 */
function initModalFunctionality() {
  // Open modal buttons
  const modalTriggers = document.querySelectorAll("[data-modal]");
  modalTriggers.forEach(function (trigger) {
    trigger.addEventListener("click", function (e) {
      e.preventDefault();
      const modalId = this.getAttribute("data-modal");
      openModal(modalId);
    });
  });

  // Close modal buttons
  const modalCloses = document.querySelectorAll(
    ".modal-close, [data-modal-close]"
  );
  modalCloses.forEach(function (closeBtn) {
    closeBtn.addEventListener("click", function () {
      const modal = this.closest(".modal");
      if (modal) {
        closeModal(modal.id);
      }
    });
  });

  // Close modal when clicking outside
  window.addEventListener("click", function (e) {
    if (e.target.classList.contains("modal")) {
      closeModal(e.target.id);
    }
  });

  // Close modal with Escape key
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
      const openModals = document.querySelectorAll(
        '.modal[style*="display: block"]'
      );
      openModals.forEach(function (modal) {
        closeModal(modal.id);
      });
    }
  });
}

/**
 * Open modal by ID
 */
function openModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.style.display = "block";
    document.body.style.overflow = "hidden";

    // Focus first input in modal
    const firstInput = modal.querySelector("input, select, textarea");
    if (firstInput) {
      setTimeout(function () {
        firstInput.focus();
      }, 100);
    }
  }
}

/**
 * Close modal by ID
 */
function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.style.display = "none";
    document.body.style.overflow = "auto";
  }
}

/**
 * AJAX Form Submissions
 * Handle form submissions via AJAX
 */
function initFormSubmissions() {
  const ajaxForms = document.querySelectorAll("[data-ajax-form]");

  ajaxForms.forEach(function (form) {
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      submitFormAjax(this);
    });
  });
}

/**
 * Submit form via AJAX
 */
function submitFormAjax(form) {
  const formData = new FormData(form);
  const url = form.getAttribute("action") || window.location.href;
  const method = form.getAttribute("method") || "POST";

  // Show loading
  showLoader();

  // Disable submit button
  const submitBtn = form.querySelector('button[type="submit"]');
  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.innerHTML =
      '<i class="fas fa-spinner fa-spin"></i> Processing...';
  }

  fetch(url, {
    method: method,
    body: formData,
    headers: {
      "X-Requested-With": "XMLHttpRequest",
    },
  })
    .then((response) => response.json())
    .then((data) => {
      hideLoader();

      // Re-enable submit button
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML =
          submitBtn.getAttribute("data-original-text") || "Submit";
      }

      if (data.success) {
        showAlert(
          "success",
          data.message || "Operation completed successfully"
        );

        // Close modal if form is in modal
        const modal = form.closest(".modal");
        if (modal) {
          closeModal(modal.id);
        }

        // Redirect if specified
        if (data.redirect) {
          setTimeout(function () {
            window.location.href = data.redirect;
          }, 1500);
        }

        // Reload page if specified
        if (data.reload) {
          setTimeout(function () {
            window.location.reload();
          }, 1500);
        }
      } else {
        showAlert(
          "error",
          data.message || "An error occurred. Please try again."
        );

        // Show field errors if provided
        if (data.errors) {
          displayFormErrors(form, data.errors);
        }
      }
    })
    .catch((error) => {
      hideLoader();

      // Re-enable submit button
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML =
          submitBtn.getAttribute("data-original-text") || "Submit";
      }

      showAlert(
        "error",
        "A network error occurred. Please check your connection and try again."
      );
      console.error("Form submission error:", error);
    });
}

/**
 * Display form validation errors
 */
function displayFormErrors(form, errors) {
  // Clear existing errors
  form.querySelectorAll(".error-message").forEach((el) => el.remove());
  form.querySelectorAll(".error").forEach((el) => el.classList.remove("error"));

  // Display new errors
  for (const [field, message] of Object.entries(errors)) {
    const input = form.querySelector(`[name="${field}"]`);
    if (input) {
      input.classList.add("error");

      const errorDiv = document.createElement("div");
      errorDiv.className = "error-message";
      errorDiv.textContent = message;

      input.parentNode.appendChild(errorDiv);
    }
  }
}

/**
 * Show alert message
 */
function showAlert(type, message) {
  // Remove existing alerts
  const existingAlerts = document.querySelectorAll(".alert.dynamic-alert");
  existingAlerts.forEach((alert) => alert.remove());

  const alertDiv = document.createElement("div");
  alertDiv.className = `alert alert-${type} dynamic-alert`;
  alertDiv.style.marginBottom = "1rem";

  const icon =
    type === "success"
      ? "check-circle"
      : type === "error"
      ? "exclamation-circle"
      : type === "warning"
      ? "exclamation-triangle"
      : "info-circle";

  alertDiv.innerHTML = `
        <i class="fas fa-${icon}"></i>
        <span>${message}</span>
    `;

  // Insert at top of content area
  const contentArea = document.querySelector(".content-area");
  if (contentArea) {
    contentArea.insertBefore(alertDiv, contentArea.firstChild);
  } else {
    document.body.insertBefore(alertDiv, document.body.firstChild);
  }

  // Auto-hide after 5 seconds
  setTimeout(function () {
    alertDiv.style.transition = "opacity 0.3s ease";
    alertDiv.style.opacity = "0";
    setTimeout(function () {
      alertDiv.remove();
    }, 300);
  }, 5000);
}

/**
 * Show loading spinner
 */
function showLoader() {
  const loader = document.getElementById("pageLoader");
  if (loader) {
    loader.classList.add("active");
  } else {
    // Create loader if it doesn't exist
    const loaderDiv = document.createElement("div");
    loaderDiv.id = "pageLoader";
    loaderDiv.className = "page-loader active";
    loaderDiv.innerHTML = '<div class="loader-spinner"></div>';
    document.body.appendChild(loaderDiv);
  }
}

/**
 * Hide loading spinner
 */
function hideLoader() {
  const loader = document.getElementById("pageLoader");
  if (loader) {
    loader.classList.remove("active");
  }
}

/**
 * Debounce function
 * Limits the rate at which a function can fire
 */
function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

/**
 * Initialize tooltips
 */
function initTooltips() {
  const tooltipElements = document.querySelectorAll("[data-tooltip]");

  tooltipElements.forEach(function (element) {
    element.addEventListener("mouseenter", function () {
      showTooltip(this);
    });

    element.addEventListener("mouseleave", function () {
      hideTooltip(this);
    });
  });
}

/**
 * Show tooltip
 */
function showTooltip(element) {
  const text = element.getAttribute("data-tooltip");

  const tooltip = document.createElement("div");
  tooltip.className = "custom-tooltip";
  tooltip.textContent = text;
  tooltip.style.position = "absolute";
  tooltip.style.background = "#333";
  tooltip.style.color = "#fff";
  tooltip.style.padding = "8px 12px";
  tooltip.style.borderRadius = "5px";
  tooltip.style.fontSize = "0.85rem";
  tooltip.style.zIndex = "10000";
  tooltip.style.whiteSpace = "nowrap";
  tooltip.style.boxShadow = "0 2px 8px rgba(0,0,0,0.15)";

  document.body.appendChild(tooltip);

  const rect = element.getBoundingClientRect();
  const tooltipRect = tooltip.getBoundingClientRect();

  tooltip.style.top = rect.top - tooltipRect.height - 8 + window.scrollY + "px";
  tooltip.style.left =
    rect.left + rect.width / 2 - tooltipRect.width / 2 + window.scrollX + "px";

  element._tooltip = tooltip;
}

/**
 * Hide tooltip
 */
function hideTooltip(element) {
  if (element._tooltip) {
    element._tooltip.remove();
    element._tooltip = null;
  }
}

/**
 * Initialize date pickers
 */
function initDatePickers() {
  const datePickers = document.querySelectorAll('input[type="date"]');

  datePickers.forEach(function (picker) {
    // Set min date to today if not already set
    if (!picker.getAttribute("min")) {
      const today = new Date().toISOString().split("T")[0];
      picker.setAttribute("min", today);
    }

    // Format date display (browser-dependent)
    picker.addEventListener("change", function () {
      console.log("Date selected:", this.value);
    });
  });
}

/**
 * Initialize table sorting
 */
function initTableSorting() {
  const sortableHeaders = document.querySelectorAll("th[data-sortable]");

  sortableHeaders.forEach(function (header) {
    header.style.cursor = "pointer";
    header.innerHTML +=
      ' <i class="fas fa-sort" style="font-size: 0.8em; margin-left: 5px;"></i>';

    header.addEventListener("click", function () {
      sortTable(this);
    });
  });
}

/**
 * Sort table by column
 */
function sortTable(header) {
  const table = header.closest("table");
  const tbody = table.querySelector("tbody");
  const rows = Array.from(tbody.querySelectorAll("tr"));
  const columnIndex = Array.from(header.parentNode.children).indexOf(header);
  const isAscending = header.classList.contains("sort-asc");

  // Remove sort classes from all headers
  header.parentNode.querySelectorAll("th").forEach(function (th) {
    th.classList.remove("sort-asc", "sort-desc");
    const icon = th.querySelector("i.fa-sort, i.fa-sort-up, i.fa-sort-down");
    if (icon) {
      icon.className = "fas fa-sort";
    }
  });

  // Sort rows
  rows.sort(function (a, b) {
    const aValue = a.children[columnIndex].textContent.trim();
    const bValue = b.children[columnIndex].textContent.trim();

    // Try numeric comparison first
    const aNum = parseFloat(aValue);
    const bNum = parseFloat(bValue);

    if (!isNaN(aNum) && !isNaN(bNum)) {
      return isAscending ? bNum - aNum : aNum - bNum;
    }

    // String comparison
    return isAscending
      ? bValue.localeCompare(aValue)
      : aValue.localeCompare(bValue);
  });

  // Update header class and icon
  header.classList.add(isAscending ? "sort-desc" : "sort-asc");
  const icon = header.querySelector("i");
  if (icon) {
    icon.className = isAscending ? "fas fa-sort-down" : "fas fa-sort-up";
  }

  // Re-append sorted rows
  rows.forEach(function (row) {
    tbody.appendChild(row);
  });
}

/**
 * Initialize print buttons
 */
function initPrintButtons() {
  const printButtons = document.querySelectorAll("[data-print], .btn-print");

  printButtons.forEach(function (button) {
    button.addEventListener("click", function (e) {
      e.preventDefault();
      window.print();
    });
  });
}

/**
 * Format currency
 */
function formatCurrency(amount) {
  return (
    "$" +
    parseFloat(amount)
      .toFixed(2)
      .replace(/\d(?=(\d{3})+\.)/g, "$&,")
  );
}

/**
 * Format date
 */
function formatDate(dateString, format = "DD/MM/YYYY") {
  const date = new Date(dateString);
  const day = String(date.getDate()).padStart(2, "0");
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const year = date.getFullYear();

  return format.replace("DD", day).replace("MM", month).replace("YYYY", year);
}

/**
 * Export table to CSV
 */
function exportTableToCSV(filename) {
  const table = document.querySelector("table");
  if (!table) {
    alert("No table found to export");
    return;
  }

  let csv = [];
  const rows = table.querySelectorAll("tr");

  rows.forEach(function (row) {
    const cols = row.querySelectorAll("td, th");
    const rowData = [];

    cols.forEach(function (col) {
      // Get text content and escape quotes
      let data = col.textContent.trim();
      data = data.replace(/"/g, '""');
      rowData.push('"' + data + '"');
    });

    csv.push(rowData.join(","));
  });

  downloadCSV(csv.join("\n"), filename || "export.csv");
}

/**
 * Download CSV file
 */
function downloadCSV(csv, filename) {
  const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
  const link = document.createElement("a");

  if (navigator.msSaveBlob) {
    // IE 10+
    navigator.msSaveBlob(blob, filename);
  } else {
    link.href = URL.createObjectURL(blob);
    link.download = filename;
    link.style.display = "none";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }
}

/**
 * Validate form before submission
 */
function validateForm(form) {
  let isValid = true;
  const requiredFields = form.querySelectorAll("[required]");

  requiredFields.forEach(function (field) {
    if (!field.value.trim()) {
      field.classList.add("error");
      isValid = false;
    } else {
      field.classList.remove("error");
    }
  });

  return isValid;
}

// Export functions for use in other scripts
window.dashboardUtils = {
  openModal: openModal,
  closeModal: closeModal,
  showAlert: showAlert,
  showLoader: showLoader,
  hideLoader: hideLoader,
  formatCurrency: formatCurrency,
  formatDate: formatDate,
  exportTableToCSV: exportTableToCSV,
  validateForm: validateForm,
};
