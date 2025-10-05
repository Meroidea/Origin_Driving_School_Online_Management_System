/**
 * Main JavaScript - Origin Driving School Management System
 *
 * FILE PATH: public/js/script.js
 *
 * Handles interactive features and validations for public pages
 * Created for DWIN309 Final Assessment at Kent Institute Australia
 *
 * @author [SUJAN DARJI K231673 AND ANTHONY ALLAN REGALADO K231715]
 * @version 1.0
 */

// Wait for DOM to be fully loaded
document.addEventListener("DOMContentLoaded", function () {
  // Initialize all features
  initMobileMenu();
  initSmoothScrolling();
  initFormValidation();
  initAlerts();
  initScrollAnimations();
  initContactForm();

  console.log("Origin Driving School - Main JavaScript loaded successfully");
});

/**
 * Mobile Menu Toggle
 * Handles responsive navigation menu
 */
function initMobileMenu() {
  const mobileMenuToggle = document.querySelector(".mobile-menu-toggle");
  const navMenu = document.querySelector(".nav-menu");

  if (mobileMenuToggle && navMenu) {
    mobileMenuToggle.addEventListener("click", function () {
      navMenu.classList.toggle("active");

      // Change icon
      const icon = this.querySelector("i");
      if (icon) {
        if (navMenu.classList.contains("active")) {
          icon.classList.remove("fa-bars");
          icon.classList.add("fa-times");
        } else {
          icon.classList.remove("fa-times");
          icon.classList.add("fa-bars");
        }
      }
    });

    // Close menu when clicking outside
    document.addEventListener("click", function (e) {
      if (!mobileMenuToggle.contains(e.target) && !navMenu.contains(e.target)) {
        navMenu.classList.remove("active");
        const icon = mobileMenuToggle.querySelector("i");
        if (icon) {
          icon.classList.remove("fa-times");
          icon.classList.add("fa-bars");
        }
      }
    });

    // Close menu when clicking on a link
    const navLinks = navMenu.querySelectorAll("a");
    navLinks.forEach(function (link) {
      link.addEventListener("click", function () {
        if (window.innerWidth <= 768) {
          navMenu.classList.remove("active");
          const icon = mobileMenuToggle.querySelector("i");
          if (icon) {
            icon.classList.remove("fa-times");
            icon.classList.add("fa-bars");
          }
        }
      });
    });
  }
}

/**
 * Smooth Scrolling
 * Enables smooth scrolling for anchor links
 */
function initSmoothScrolling() {
  const anchorLinks = document.querySelectorAll('a[href^="#"]');

  anchorLinks.forEach(function (anchor) {
    anchor.addEventListener("click", function (e) {
      const href = this.getAttribute("href");

      // Ignore # only links
      if (href === "#" || href.length <= 1) {
        return;
      }

      const target = document.querySelector(href);

      if (target) {
        e.preventDefault();

        const headerOffset = 80; // Offset for fixed header
        const elementPosition = target.getBoundingClientRect().top;
        const offsetPosition =
          elementPosition + window.pageYOffset - headerOffset;

        window.scrollTo({
          top: offsetPosition,
          behavior: "smooth",
        });

        // Update URL without jumping
        if (history.pushState) {
          history.pushState(null, null, href);
        }
      }
    });
  });
}

/**
 * Form Validation
 * Validates forms before submission
 */
function initFormValidation() {
  const forms = document.querySelectorAll(
    "form[data-validate], form.validate-form"
  );

  forms.forEach(function (form) {
    form.addEventListener("submit", function (e) {
      if (!validateForm(this)) {
        e.preventDefault();

        // Scroll to first error
        const firstError = this.querySelector(".error");
        if (firstError) {
          firstError.scrollIntoView({ behavior: "smooth", block: "center" });
          firstError.focus();
        }

        return false;
      }
    });

    // Real-time validation
    const inputs = form.querySelectorAll("input, select, textarea");
    inputs.forEach(function (input) {
      input.addEventListener("blur", function () {
        validateField(this);
      });

      input.addEventListener("input", function () {
        if (this.classList.contains("error")) {
          validateField(this);
        }
      });
    });
  });
}

/**
 * Validate entire form
 */
function validateForm(form) {
  let isValid = true;
  const inputs = form.querySelectorAll(
    "input[required], select[required], textarea[required]"
  );

  inputs.forEach(function (input) {
    if (!validateField(input)) {
      isValid = false;
    }
  });

  return isValid;
}

/**
 * Validate individual field
 */
function validateField(field) {
  const value = field.value.trim();
  const type = field.type;
  const required = field.hasAttribute("required");

  // Clear previous error
  clearError(field);

  // Check if required field is empty
  if (required && !value) {
    showError(field, "This field is required");
    return false;
  }

  // Skip further validation if field is empty and not required
  if (!value) {
    return true;
  }

  // Email validation
  if (type === "email") {
    if (!isValidEmail(value)) {
      showError(field, "Please enter a valid email address");
      return false;
    }
  }

  // Phone validation
  if (field.name === "phone" || field.classList.contains("phone-input")) {
    if (!isValidPhone(value)) {
      showError(field, "Please enter a valid phone number");
      return false;
    }
  }

  // Password validation
  if (type === "password" && field.name === "password") {
    const minLength = field.getAttribute("minlength") || 8;
    if (value.length < minLength) {
      showError(
        field,
        `Password must be at least ${minLength} characters long`
      );
      return false;
    }
  }

  // Confirm password validation
  if (field.name === "confirm_password" || field.name === "password_confirm") {
    const passwordField = field.form.querySelector('input[name="password"]');
    if (passwordField && value !== passwordField.value) {
      showError(field, "Passwords do not match");
      return false;
    }
  }

  // Number validation
  if (type === "number") {
    const min = field.getAttribute("min");
    const max = field.getAttribute("max");
    const numValue = parseFloat(value);

    if (min !== null && numValue < parseFloat(min)) {
      showError(field, `Value must be at least ${min}`);
      return false;
    }

    if (max !== null && numValue > parseFloat(max)) {
      showError(field, `Value must not exceed ${max}`);
      return false;
    }
  }

  // Date validation
  if (type === "date") {
    const minDate = field.getAttribute("min");
    const maxDate = field.getAttribute("max");

    if (minDate && value < minDate) {
      showError(field, "Date is too early");
      return false;
    }

    if (maxDate && value > maxDate) {
      showError(field, "Date is too late");
      return false;
    }
  }

  return true;
}

/**
 * Show error message for field
 */
function showError(field, message) {
  field.classList.add("error");

  // Remove existing error message
  clearError(field);

  // Create error message element
  const errorDiv = document.createElement("div");
  errorDiv.className = "error-message";
  errorDiv.textContent = message;

  // Insert after the field
  field.parentNode.appendChild(errorDiv);
}

/**
 * Clear error message for field
 */
function clearError(field) {
  field.classList.remove("error");

  const errorDiv = field.parentNode.querySelector(".error-message");
  if (errorDiv) {
    errorDiv.remove();
  }
}

/**
 * Validate email format
 */
function isValidEmail(email) {
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return re.test(email);
}

/**
 * Validate phone format
 */
function isValidPhone(phone) {
  // Allow various phone formats
  const re = /^[\d\s\-\+\(\)]{8,}$/;
  return re.test(phone);
}

/**
 * Auto-hide Alerts
 * Automatically hide alert messages after a delay
 */
function initAlerts() {
  const alerts = document.querySelectorAll(".alert");

  alerts.forEach(function (alert) {
    // Add close button if not exists
    if (!alert.querySelector(".alert-close")) {
      const closeBtn = document.createElement("span");
      closeBtn.className = "alert-close";
      closeBtn.innerHTML = "&times;";
      closeBtn.style.marginLeft = "auto";
      closeBtn.style.cursor = "pointer";
      closeBtn.style.fontSize = "1.5rem";
      closeBtn.style.fontWeight = "bold";
      closeBtn.onclick = function () {
        alert.style.opacity = "0";
        setTimeout(function () {
          alert.remove();
        }, 300);
      };
      alert.appendChild(closeBtn);
    }

    // Auto-hide after 5 seconds
    setTimeout(function () {
      alert.style.transition = "opacity 0.3s ease";
      alert.style.opacity = "0";
      setTimeout(function () {
        alert.remove();
      }, 300);
    }, 5000);
  });
}

/**
 * Scroll Animations
 * Add animations when elements come into view
 */
function initScrollAnimations() {
  const animatedElements = document.querySelectorAll(
    ".feature-card, .service-item, .course-card, .stat-item"
  );

  if (animatedElements.length === 0) return;

  const observer = new IntersectionObserver(
    function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.style.opacity = "1";
          entry.target.style.transform = "translateY(0)";
        }
      });
    },
    {
      threshold: 0.1,
      rootMargin: "0px 0px -50px 0px",
    }
  );

  animatedElements.forEach(function (element) {
    element.style.opacity = "0";
    element.style.transform = "translateY(20px)";
    element.style.transition = "opacity 0.5s ease, transform 0.5s ease";
    observer.observe(element);
  });
}

/**
 * Contact Form Handler
 * Handle contact form submission
 */
function initContactForm() {
  const contactForm = document.getElementById("contact-form");

  if (!contactForm) return;

  contactForm.addEventListener("submit", function (e) {
    e.preventDefault();

    if (!validateForm(this)) {
      return false;
    }

    // Get form data
    const formData = new FormData(this);

    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

    // Simulate form submission (replace with actual AJAX call)
    setTimeout(function () {
      submitBtn.disabled = false;
      submitBtn.textContent = originalText;

      // Show success message
      showNotification(
        "success",
        "Thank you for your message! We will get back to you soon."
      );

      // Reset form
      contactForm.reset();
    }, 1500);
  });
}

/**
 * Show notification message
 */
function showNotification(type, message) {
  const notification = document.createElement("div");
  notification.className = `alert alert-${type}`;
  notification.style.position = "fixed";
  notification.style.top = "20px";
  notification.style.right = "20px";
  notification.style.zIndex = "10000";
  notification.style.minWidth = "300px";
  notification.style.boxShadow = "0 4px 6px rgba(0,0,0,0.1)";

  const icon =
    type === "success"
      ? "check-circle"
      : type === "error"
      ? "exclamation-circle"
      : type === "warning"
      ? "exclamation-triangle"
      : "info-circle";

  notification.innerHTML = `
        <i class="fas fa-${icon}"></i>
        <span>${message}</span>
    `;

  document.body.appendChild(notification);

  // Auto-hide after 5 seconds
  setTimeout(function () {
    notification.style.transition = "opacity 0.3s ease, transform 0.3s ease";
    notification.style.opacity = "0";
    notification.style.transform = "translateX(400px)";
    setTimeout(function () {
      notification.remove();
    }, 300);
  }, 5000);
}

/**
 * Format Currency
 * Format number as currency
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
 * Format Date
 * Format date string
 */
function formatDate(dateString) {
  const date = new Date(dateString);
  const day = String(date.getDate()).padStart(2, "0");
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const year = date.getFullYear();
  return `${day}/${month}/${year}`;
}

/**
 * Debounce Function
 * Limit the rate at which a function can fire
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
 * Throttle Function
 * Ensure a function is called at most once in a specified time period
 */
function throttle(func, limit) {
  let inThrottle;
  return function (...args) {
    if (!inThrottle) {
      func.apply(this, args);
      inThrottle = true;
      setTimeout(() => (inThrottle = false), limit);
    }
  };
}

/**
 * Confirm Action
 * Show confirmation dialog
 */
function confirmAction(message) {
  return confirm(message || "Are you sure you want to proceed?");
}

/**
 * Password Toggle Visibility
 * Toggle password field visibility
 */
function initPasswordToggle() {
  const passwordFields = document.querySelectorAll('input[type="password"]');

  passwordFields.forEach(function (field) {
    // Create toggle button
    const toggleBtn = document.createElement("button");
    toggleBtn.type = "button";
    toggleBtn.className = "password-toggle";
    toggleBtn.innerHTML = '<i class="fas fa-eye"></i>';
    toggleBtn.style.position = "absolute";
    toggleBtn.style.right = "10px";
    toggleBtn.style.top = "50%";
    toggleBtn.style.transform = "translateY(-50%)";
    toggleBtn.style.background = "none";
    toggleBtn.style.border = "none";
    toggleBtn.style.cursor = "pointer";
    toggleBtn.style.color = "#666";

    // Make parent position relative
    field.parentNode.style.position = "relative";

    // Add padding to input for button
    field.style.paddingRight = "40px";

    // Insert button
    field.parentNode.appendChild(toggleBtn);

    // Toggle visibility
    toggleBtn.addEventListener("click", function () {
      if (field.type === "password") {
        field.type = "text";
        this.innerHTML = '<i class="fas fa-eye-slash"></i>';
      } else {
        field.type = "password";
        this.innerHTML = '<i class="fas fa-eye"></i>';
      }
    });
  });
}

// Initialize password toggle on page load
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initPasswordToggle);
} else {
  initPasswordToggle();
}

/**
 * Sticky Header
 * Make header sticky on scroll
 */
window.addEventListener(
  "scroll",
  throttle(function () {
    const header = document.querySelector(".main-header");

    if (header) {
      if (window.scrollY > 100) {
        header.classList.add("sticky");
      } else {
        header.classList.remove("sticky");
      }
    }
  }, 100)
);

/**
 * Back to Top Button
 */
function initBackToTop() {
  // Create button
  const backToTopBtn = document.createElement("button");
  backToTopBtn.id = "back-to-top";
  backToTopBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
  backToTopBtn.style.cssText = `
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 50px;
        height: 50px;
        background: var(--color-primary, #4e7e95);
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        display: none;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        z-index: 1000;
        transition: all 0.3s ease;
    `;

  document.body.appendChild(backToTopBtn);

  // Show/hide based on scroll position
  window.addEventListener("scroll", function () {
    if (window.scrollY > 300) {
      backToTopBtn.style.display = "flex";
    } else {
      backToTopBtn.style.display = "none";
    }
  });

  // Scroll to top on click
  backToTopBtn.addEventListener("click", function () {
    window.scrollTo({
      top: 0,
      behavior: "smooth",
    });
  });

  // Hover effect
  backToTopBtn.addEventListener("mouseenter", function () {
    this.style.transform = "scale(1.1)";
  });

  backToTopBtn.addEventListener("mouseleave", function () {
    this.style.transform = "scale(1)";
  });
}

// Initialize back to top button
initBackToTop();

// Export utilities for use in other scripts
window.appUtils = {
  formatCurrency: formatCurrency,
  formatDate: formatDate,
  debounce: debounce,
  throttle: throttle,
  confirmAction: confirmAction,
  showNotification: showNotification,
  validateEmail: isValidEmail,
  validatePhone: isValidPhone,
};

// Log loaded
console.log("Main JavaScript utilities loaded");
