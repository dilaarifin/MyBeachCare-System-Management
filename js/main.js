/**
 * MyBeachCare Modern JavaScript Utility
 */

document.addEventListener('DOMContentLoaded', () => {
    // Initialize all components
    initScrollReveal();
    setupLoadMore();
    setupImagePreview();
    setupStatusAlerts();
    setupUserDropdown();
    setupMobileMenu();
});

/**
 * Scroll Reveal Animation
 * Adds a fade-in-up effect to elements as they enter the viewport
 */
function initScrollReveal() {
    const options = {
        threshold: 0.1,
        rootMargin: "0px 0px -50px 0px"
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('opacity-100', 'translate-y-0');
                entry.target.classList.remove('opacity-0', 'translate-y-8');
                observer.unobserve(entry.target);
            }
        });
    }, options);

    // Initial state for reveal elements
    document.querySelectorAll('.reveal').forEach(el => {
        el.classList.add('transition-all', 'duration-700', 'ease-out', 'opacity-0', 'translate-y-8');
        observer.observe(el);
    });
}

/**
 * Authentication Check for Volunteer Actions
 * Prompts guests to login when trying to perform user-only actions
 */
window.checkVolunteerAuth = async (event) => {
    if (event) event.preventDefault();

    const result = await Swal.fire({
        title: 'Login Required',
        text: 'You need to be logged in to join our cleanup events!',
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#512DA8',
        cancelButtonColor: '#CBD5E1',
        confirmButtonText: 'Login Now',
        cancelButtonText: 'Not Now',
        customClass: {
            popup: 'rounded-3xl',
            confirmButton: 'rounded-full px-8 py-3',
            cancelButton: 'rounded-full px-8 py-3'
        }
    });

    if (result.isConfirmed) {
        window.location.href = 'login.php';
    }
};

/**
 * AJAX Load More Beaches
 * Uses async/await for cleaner asynchronous logic
 */
function setupLoadMore() {
    const loadMoreBtn = document.getElementById('load-more');
    const container = document.getElementById('beach-container');

    if (!loadMoreBtn || !container) return;

    loadMoreBtn.addEventListener('click', async function () {
        const offset = this.getAttribute('data-offset');
        const urlParams = new URLSearchParams(window.location.search);
        const state = urlParams.get('state') || '';
        const status = urlParams.get('status') || '';
        const search = urlParams.get('search') || '';

        // UI State: Loading
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-circle-notch fa-spin mr-2"></i> Fetching Waves...';

        try {
            const response = await fetch(`index.php?load_more_beaches=1&offset=${offset}&state=${state}&status=${status}&search=${search}`);

            if (!response.ok) throw new Error('Network response was not ok');

            const html = await response.text();

            if (html.trim() === '') {
                this.parentElement.innerHTML = '<p class="text-gray-400 font-medium animate-pulse">That\'s all for now!</p>';
            } else {
                // Insert new beach cards directly
                container.insertAdjacentHTML('beforeend', html);

                // Update offset
                this.setAttribute('data-offset', parseInt(offset) + 6);

                // Re-init scroll reveal for new elements
                initScrollReveal();

                // UI State: Ready
                this.disabled = false;
                this.innerHTML = 'Show More Beaches';
            }
        } catch (error) {
            console.error('Fetch error:', error);
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i> Error! Try again';
        }
    });
}

/**
 * Image Preview for Profile Upload
 */
function setupImagePreview() {
    const uploadInput = document.getElementById('image_upload');
    if (!uploadInput) return;

    uploadInput.addEventListener('change', function () {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = (e) => {
                const container = document.querySelector('.w-32.h-32');
                if (container) {
                    container.innerHTML = `<img src="${e.target.result}" alt="Profile" class="w-full h-full object-cover ring-4 ring-primary/10">`;

                    // Add a nice "Selected" toast
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'info',
                        title: 'Image selected! Click Save to update.',
                        showConfirmButton: false,
                        timer: 3000
                    });
                }
            };
            reader.readAsDataURL(this.files[0]);
        }
    });
}

/**
 * Confirmation Dialog for Event Cancellation
 */
window.confirmCancel = async (event) => {
    event.preventDefault();
    const form = event.currentTarget;

    const result = await Swal.fire({
        title: 'Cancel Participation?',
        text: "You are about to withdraw from this event activity. Are you sure?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#512DA8',
        cancelButtonColor: '#f43f5e',
        confirmButtonText: 'Yes, cancel it',
        cancelButtonText: 'No, keep it',
        customClass: {
            popup: 'rounded-3xl',
            confirmButton: 'rounded-full px-8 py-2.5',
            cancelButton: 'rounded-full px-8 py-2.5'
        }
    });

    if (result.isConfirmed) {
        form.submit();
    }
    return false;
};

/**
 * Handle Success/Error Alerts from URL Params
 */
function setupStatusAlerts() {
    const urlParams = new URLSearchParams(window.location.search);
    const success = urlParams.get('success');
    const error = urlParams.get('error');

    if (success) {
        Swal.fire({
            icon: 'success',
            title: 'Great!',
            text: success,
            confirmButtonColor: '#512DA8',
            customClass: { popup: 'rounded-3xl' }
        });
    }

    if (error) {
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: error,
            confirmButtonColor: '#512DA8',
            customClass: { popup: 'rounded-3xl' }
        });
    }
}

/**
 * Setup User Dropdown Menu
 */
function setupUserDropdown() {
    const menuButton = document.getElementById('user-menu-button');
    const dropdown = document.getElementById('user-menu-dropdown');
    const chevron = document.getElementById('user-menu-chevron');

    if (!menuButton || !dropdown) return;

    menuButton.addEventListener('click', (e) => {
        e.stopPropagation();
        const isExpanded = menuButton.getAttribute('aria-expanded') === 'true';

        if (isExpanded) {
            closeDropdown();
        } else {
            openDropdown();
        }
    });

    // Close when clicking outside
    document.addEventListener('click', (e) => {
        if (!menuButton.contains(e.target) && !dropdown.contains(e.target)) {
            closeDropdown();
        }
    });

    function openDropdown() {
        menuButton.setAttribute('aria-expanded', 'true');
        dropdown.classList.remove('opacity-0', 'scale-95', 'pointer-events-none');
        dropdown.classList.add('opacity-100', 'scale-100', 'pointer-events-auto');
        if (chevron) chevron.classList.add('rotate-180');
    }

    function closeDropdown() {
        menuButton.setAttribute('aria-expanded', 'false');
        dropdown.classList.remove('opacity-100', 'scale-100', 'pointer-events-auto');
        dropdown.classList.add('opacity-0', 'scale-95', 'pointer-events-none');
        if (chevron) chevron.classList.remove('rotate-180');
    }
}

/**
 * Mobile Menu Toggle logic
 */
function setupMobileMenu() {
    const mobileButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');
    const mobileIcon = document.getElementById('mobile-menu-icon');

    if (!mobileButton || !mobileMenu) return;

    mobileButton.addEventListener('click', () => {
        const isHidden = mobileMenu.classList.contains('hidden');

        if (isHidden) {
            mobileMenu.classList.remove('hidden');
            mobileIcon.classList.remove('fa-bars');
            mobileIcon.classList.add('fa-xmark');
            // Accessibility
            mobileButton.setAttribute('aria-expanded', 'true');
        } else {
            mobileMenu.classList.add('hidden');
            mobileIcon.classList.remove('fa-xmark');
            mobileIcon.classList.add('fa-bars');
            // Accessibility
            mobileButton.setAttribute('aria-expanded', 'false');
        }
    });

    // Close on navigation
    const mobileLinks = mobileMenu.querySelectorAll('a');
    mobileLinks.forEach(link => {
        link.addEventListener('click', () => {
            mobileMenu.classList.add('hidden');
            mobileIcon.classList.remove('fa-xmark');
            mobileIcon.classList.add('fa-bars');
        });
    });

    // Close mobile menu on resize if it's open and we go to desktop
    window.addEventListener('resize', () => {
        if (window.innerWidth >= 768) {
            mobileMenu.classList.add('hidden');
            mobileIcon.classList.remove('fa-xmark');
            mobileIcon.classList.add('fa-bars');
        }
    });
}