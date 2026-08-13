/* ==========================================================================
   RACS PROJECTS - MEP Engineering & Contracting
   Main JavaScript
   ========================================================================== */

document.addEventListener('DOMContentLoaded', () => {
  // 1. Sticky Navbar Dynamic Styling on Scroll
  const navbar = document.querySelector('.navbar-racs');
  
  const handleScroll = () => {
    if (window.scrollY > 40) {
      navbar?.classList.add('scrolled');
    } else {
      navbar?.classList.remove('scrolled');
    }
  };

  window.addEventListener('scroll', handleScroll);
  handleScroll(); // Trigger on initial load

  // Active Link Highlighter based on current page filename
  const currentPage = window.location.pathname.split('/').pop() || 'index.html';
  const navLinks = document.querySelectorAll('.nav-link-racs');
  
  navLinks.forEach(link => {
    const href = link.getAttribute('href');
    if (href === currentPage || (currentPage === '' && href === 'index.html')) {
      link.classList.add('active');
    } else {
      link.classList.remove('active');
    }
  });

  // 2. IntersectionObserver for Scroll Reveal Animations
  const revealElements = document.querySelectorAll('.reveal-on-scroll');

  if ('IntersectionObserver' in window) {
    const revealObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('active');
          observer.unobserve(entry.target);
        }
      });
    }, {
      root: null,
      threshold: 0.12,
      rootMargin: '0px 0px -40px 0px'
    });

    revealElements.forEach(el => revealObserver.observe(el));
  } else {
    // Fallback if IntersectionObserver is not supported
    revealElements.forEach(el => el.classList.add('active'));
  }

  // 3. Counter Animation for Statistics
  const statNumbers = document.querySelectorAll('.stat-number');
  let animated = false;

  const animateCounters = () => {
    statNumbers.forEach(stat => {
      const target = parseInt(stat.getAttribute('data-target') || '0', 10);
      const suffix = stat.getAttribute('data-suffix') || '';
      const prefix = stat.getAttribute('data-prefix') || '';
      const duration = 2000; // ms
      const startTime = performance.now();

      const updateCount = (currentTime) => {
        const elapsedTime = currentTime - startTime;
        const progress = Math.min(elapsedTime / duration, 1);
        // Ease out quadratic function
        const easedProgress = 1 - (1 - progress) * (1 - progress);
        const currentCount = Math.floor(easedProgress * target);

        stat.textContent = `${prefix}${currentCount}${suffix}`;

        if (progress < 1) {
          requestAnimationFrame(updateCount);
        } else {
          stat.textContent = `${prefix}${target}${suffix}`;
        }
      };

      requestAnimationFrame(updateCount);
    });
  };

  const statsSection = document.querySelector('.stats-section');
  if (statsSection && 'IntersectionObserver' in window) {
    const statsObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting && !animated) {
          animated = true;
          animateCounters();
        }
      });
    }, { threshold: 0.3 });

    statsObserver.observe(statsSection);
  }

  // 4. Projects Category Filter (for projects.html)
  const filterBtns = document.querySelectorAll('.filter-btn');
  const projectItems = document.querySelectorAll('.project-item');

  if (filterBtns.length > 0 && projectItems.length > 0) {
    filterBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        // Remove active class from all buttons
        filterBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        const filterValue = btn.getAttribute('data-filter') || 'all';

        projectItems.forEach(item => {
          const category = item.getAttribute('data-category');

          if (filterValue === 'all' || category === filterValue) {
            item.style.display = 'block';
            setTimeout(() => {
              item.style.opacity = '1';
              item.style.transform = 'scale(1)';
            }, 50);
          } else {
            item.style.opacity = '0';
            item.style.transform = 'scale(0.95)';
            setTimeout(() => {
              item.style.display = 'none';
            }, 300);
          }
        });
      });
    });
  }

  // 5. Contact Form Validation & Submission Feedback (contact.html)
  const contactForm = document.getElementById('racs-contact-form');
  const formAlert = document.getElementById('form-alert-message');

  if (contactForm) {
    contactForm.addEventListener('submit', (e) => {
      e.preventDefault();
      
      // Perform standard validation check
      if (!contactForm.checkValidity()) {
        e.stopPropagation();
        contactForm.classList.add('was-validated');
        if (formAlert) {
          formAlert.className = 'alert alert-danger mt-3';
          formAlert.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-2"></i> Please complete all required fields correctly before submitting.';
          formAlert.classList.remove('d-none');
        }
        return;
      }

      // Simulated submit state
      const submitBtn = contactForm.querySelector('button[type="submit"]');
      const originalBtnText = submitBtn ? submitBtn.innerHTML : 'Submit Enquiry';
      
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Submitting...';
      }

      setTimeout(() => {
        contactForm.reset();
        contactForm.classList.remove('was-validated');
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalBtnText;
        }
        window.location.href = 'thankyou.html';
      }, 600);
    });
  }

  // 6. Smooth Scrolling for Internal Links
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      const targetId = this.getAttribute('href');
      if (targetId !== '#') {
        const targetElement = document.querySelector(targetId);
        if (targetElement) {
          e.preventDefault();
          targetElement.scrollIntoView({
            behavior: 'smooth'
          });
        }
      }
    });
  });

  // 7. Interactive Floating Bubble Animation for "The Bubbles Media" Link
  const bubbleLinks = document.querySelectorAll('.bubbles-media-link');
  bubbleLinks.forEach(link => {
    let bubbleInterval;

    link.addEventListener('mouseenter', function () {
      // Spawn initial cluster of bubbles
      for (let i = 0; i < 5; i++) {
        setTimeout(() => createBubble(this), i * 140);
      }

      // Continuous spawning while hovering
      bubbleInterval = setInterval(() => {
        createBubble(this);
      }, 250);
    });

    link.addEventListener('mouseleave', function () {
      clearInterval(bubbleInterval);
    });
  });

  function createBubble(parent) {
    if (!parent) return;
    const bubble = document.createElement('span');
    bubble.className = 'js-floating-bubble';

    const size = Math.floor(Math.random() * 10) + 6;
    const leftPos = Math.floor(Math.random() * 85) + 5;
    const duration = (Math.random() * 0.6 + 1.2).toFixed(2);

    bubble.style.width = `${size}px`;
    bubble.style.height = `${size}px`;
    bubble.style.left = `${leftPos}%`;
    bubble.style.animationDuration = `${duration}s`;

    parent.appendChild(bubble);

    setTimeout(() => {
      if (bubble && bubble.parentNode) {
        bubble.parentNode.removeChild(bubble);
      }
    }, parseFloat(duration) * 1000 + 100);
  }
});
