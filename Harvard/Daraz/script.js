document.addEventListener('DOMContentLoaded', () => {
    // --- Carousel functionality for index.html ---
    const carouselTrack = document.querySelector('.carousel-track');
    const carouselItems = document.querySelectorAll('.carousel-item');
    const carouselDotsContainer = document.querySelector('.carousel-dots');

    if (carouselTrack && carouselItems.length > 0) {
        let currentIndex = 0;
        const totalItems = carouselItems.length;

        // Create dots
        for (let i = 0; i < totalItems; i++) {
            const dot = document.createElement('div');
            dot.classList.add('carousel-dot');
            if (i === 0) {
                dot.classList.add('active');
            }
            dot.addEventListener('click', () => {
                showSlide(i);
            });
            carouselDotsContainer.appendChild(dot);
        }

        const carouselDots = document.querySelectorAll('.carousel-dot');

        const showSlide = (index) => {
            if (index >= totalItems) {
                currentIndex = 0;
            } else if (index < 0) {
                currentIndex = totalItems - 1;
            } else {
                currentIndex = index;
            }

            const offset = -currentIndex * 100;
            carouselTrack.style.transform = `translateX(${offset}%)`;

            carouselDots.forEach((dot, i) => {
                if (i === currentIndex) {
                    dot.classList.add('active');
                } else {
                    dot.classList.remove('active');
                }
            });
        };

        // Automatic slider
        setInterval(() => {
            showSlide(currentIndex + 1);
        }, 3000); // Change image every 3 seconds
    }

    // --- Form validation for form.html ---
    const registrationForm = document.getElementById('registrationForm');

    if (registrationForm) {
        registrationForm.addEventListener('submit', (event) => {
            event.preventDefault(); // Prevent default form submission

            let isValid = true;

            // Clear previous error messages
            document.querySelectorAll('.error-message').forEach(el => el.textContent = '');
            document.querySelectorAll('.form-input').forEach(el => el.classList.remove('border-red-500'));

            // Validate Email
            const emailInput = document.getElementById('email');
            const emailError = document.getElementById('emailError');
            if (!emailInput.value.trim()) {
                emailError.textContent = 'Email is required.';
                emailInput.classList.add('border-red-500');
                isValid = false;
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value)) {
                emailError.textContent = 'Invalid email format.';
                emailInput.classList.add('border-red-500');
                isValid = false;
            }

            // Validate Password
            const passwordInput = document.getElementById('password');
            const passwordError = document.getElementById('passwordError');
            const passwordValue = passwordInput.value;
            if (!passwordValue.trim()) {
                passwordError.textContent = 'Password is required.';
                passwordInput.classList.add('border-red-500');
                isValid = false;
            } else if (passwordValue.length < 6 || passwordValue.length > 20) {
                passwordError.textContent = 'Password must be 6-20 characters long.';
                passwordInput.classList.add('border-red-500');
                isValid = false;
            } else if (!/^(?=.*[a-zA-Z])(?=.*\d)(?=.*[!@#$%^&*()_+{}\[\]:;<>,.?~\\-]).{6,20}$/.test(passwordValue)) {
                passwordError.textContent = 'Password needs at least 2 different format characters (letters, digits, special symbols).';
                passwordInput.classList.add('border-red-500');
                isValid = false;
            }


            // Validate Confirm Password
            const confirmPasswordInput = document.getElementById('confirmPassword');
            const confirmPasswordError = document.getElementById('confirmPasswordError');
            if (!confirmPasswordInput.value.trim()) {
                confirmPasswordError.textContent = 'Confirm password is required.';
                confirmPasswordInput.classList.add('border-red-500');
                isValid = false;
            } else if (confirmPasswordInput.value !== passwordValue) {
                confirmPasswordError.textContent = 'Passwords do not match.';
                confirmPasswordInput.classList.add('border-red-500');
                isValid = false;
            }

            // Validate Mobile Number
            const mobileInput = document.getElementById('mobileNumber');
            const mobileError = document.getElementById('mobileNumberError');
            if (!mobileInput.value.trim()) {
                mobileError.textContent = 'Mobile number is required.';
                mobileInput.classList.add('border-red-500');
                isValid = false;
            } else if (!/^\d{7,15}$/.test(mobileInput.value)) { // Basic digit validation
                mobileError.textContent = 'Invalid mobile number format.';
                mobileInput.classList.add('border-red-500');
                isValid = false;
            }

            // Validate Checkboxes
            const termsCheckbox = document.getElementById('terms');
            const consentCheckbox = document.getElementById('consent');
            const checkboxError = document.getElementById('checkboxError');

            if (!termsCheckbox.checked || !consentCheckbox.checked) {
                checkboxError.textContent = 'You must agree to the terms and consent.';
                isValid = false;
            }


            if (isValid) {
                // If all validations pass, you can submit the form
                // For this example, we'll just log a success message
                console.log('Form submitted successfully!');
                // In a real application, you would send this data to a server
                alert('Registration successful!'); // Using alert for demonstration, replace with custom modal
                registrationForm.reset(); // Clear the form
            } else {
                console.log('Form validation failed.');
            }
        });

        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(icon => {
            icon.addEventListener('click', () => {
                const targetId = icon.dataset.target;
                const targetInput = document.getElementById(targetId);
                const type = targetInput.getAttribute('type') === 'password' ? 'text' : 'password';
                targetInput.setAttribute('type', type);
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            });
        });

        // Placeholder for "Send Email Verification Code"
        const sendEmailCodeBtn = document.getElementById('sendEmailCode');
        if (sendEmailCodeBtn) {
            sendEmailCodeBtn.addEventListener('click', () => {
                const email = document.getElementById('email').value;
                if (email) {
                    alert(`Verification code sent to ${email} (simulated).`);
                } else {
                    alert('Please enter your email first.');
                }
            });
        }

        // Placeholder for "Send SMS Verification Code"
        const sendSmsCodeBtn = document.getElementById('sendSmsCode');
        if (sendSmsCodeBtn) {
            sendSmsCodeBtn.addEventListener('click', () => {
                const mobileNumber = document.getElementById('mobileNumber').value;
                if (mobileNumber) {
                    alert(`SMS verification code sent to ${mobileNumber} (simulated).`);
                } else {
                    alert('Please enter your mobile number first.');
                }
            });
        }
    }
});
