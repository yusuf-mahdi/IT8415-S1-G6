const signupForm = document.querySelector('#signup-form');

if (signupForm) {
    signupForm.addEventListener('submit', (event) => {
        const username = signupForm.username.value.trim();
        const email = signupForm.email.value.trim();
        const password = signupForm.password.value;
        const confirmPassword = signupForm.confirm_password.value;
        const usernamePattern = /^[A-Za-z0-9_]{3,50}$/;
        const messages = [];

        if (!usernamePattern.test(username)) {
            messages.push('Username must be 3-50 characters and use only letters, numbers, and underscores.');
        }

        if (!email || !signupForm.email.validity.valid) {
            messages.push('Enter a valid email address.');
        }

        if (password.length < 8) {
            messages.push('Password must be at least 8 characters.');
        }

        if (password !== confirmPassword) {
            messages.push('Password confirmation does not match.');
        }

        if (messages.length > 0) {
            event.preventDefault();
            alert(messages.join('\n'));
        }
    });
}
