const loginForm = document.querySelector('form.form-panel');

if (loginForm) {
    loginForm.addEventListener('submit', (event) => {
        const username = loginForm.username.value.trim();
        const password = loginForm.password.value;
        const messages = [];

        if (!username) {
            messages.push('Username is required.');
        }

        if (!password) {
            messages.push('Password is required.');
        }

        if (messages.length > 0) {
            event.preventDefault();
            alert(messages.join('\n'));
        }
    });
}
