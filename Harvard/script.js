document.addEventListener('DOMContentLoaded', () => {
    const menuToggle = document.querySelector('.menu-toggle');
    const mobileMenu = document.querySelector('.mobile-menu');
    const closeBtn = document.querySelector('.mobile-menu .close-btn');
    const mobileMenuLinks = document.querySelectorAll('.mobile-menu a[href^="#"]');

    // Get the search button
    const searchBtn = document.querySelector('.search-btn');


    // Toggle mobile menu
    if (menuToggle && mobileMenu && closeBtn) {
        menuToggle.addEventListener('click', () => {
            mobileMenu.classList.add('active');
            document.body.style.overflow = 'hidden'; // Prevent body scrolling
        });

        closeBtn.addEventListener('click', () => {
            mobileMenu.classList.remove('active');
            document.body.style.overflow = ''; // Re-enable body scrolling
        });

        // Close menu if clicked outside
        document.addEventListener('click', (event) => {
            const isClickOutsideMenu = !mobileMenu.contains(event.target);
            const isClickOnMenuToggle = menuToggle.contains(event.target);

            // Ensure the menu is active and the click is truly outside both the menu and its toggle button
            if (mobileMenu.classList.contains('active') && isClickOutsideMenu && !isClickOnMenuToggle) {
                mobileMenu.classList.remove('active');
                document.body.style.overflow = ''; // Re-enable body scrolling
            }
        });

        // Close mobile menu when a navigation link inside it is clicked
        mobileMenuLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (mobileMenu.classList.contains('active')) {
                    mobileMenu.classList.remove('active');
                    document.body.style.overflow = ''; // Re-enable body scrolling
                }
            });
        });
    }

    // Add functionality to the search button
    if (searchBtn) {
        searchBtn.addEventListener('click', () => {
            // This is where you would implement your search functionality.
            // For a basic example, we'll just log to the console and provide ideas.
            console.log('Search button clicked!');

            // --- Ideas for making the search functional: ---
            // 1. **Show a Search Overlay/Modal:**
            //    You could have a hidden div in your HTML for a search input field and a search button.
            //    When 'searchBtn' is clicked, toggle a class on that div to make it visible.
            //    Example:
            //    const searchOverlay = document.querySelector('.search-overlay');
            //    if (searchOverlay) {
            //        searchOverlay.classList.toggle('active');
            //        // Optionally, focus on an input field inside the overlay
            //        // document.querySelector('.search-overlay input[type="text"]').focus();
            //    }

            // 2. **Redirect to a Search Page:**
            //    If you have a dedicated search page (e.g., 'search.html'), you can redirect.
            //    window.location.href = '/search.html';

            // 3. **Implement Live Search (More Complex):**
            //    This would involve an input field where users type, and results appear dynamically
            //    below it using JavaScript to fetch data (e.g., from an API or a predefined list).

            alert('Search functionality will appear here!'); // Temporary alert for demonstration
        });
    }

    // Timeline functionality (your existing code)
    const timelineButtons = document.querySelectorAll('.timeline-btn');
    const timelineItems = document.querySelectorAll('.timeline-item');

    timelineButtons.forEach(button => {
        button.addEventListener('click', () => {
            timelineButtons.forEach(btn => btn.classList.remove('active'));
            timelineItems.forEach(item => item.classList.remove('active'));

            button.classList.add('active');

            const period = button.dataset.period;
            const activeItem = document.querySelector(`.timeline-item.${period}`);
            if (activeItem) {
                activeItem.classList.add('active');
            }
        });
    });

    if (timelineButtons.length > 0 && timelineItems.length > 0) {
        timelineButtons[0].classList.add('active');
        timelineItems[0].classList.add('active');
    }
});