const reviewForm = document.querySelector('#review-form');

if (reviewForm) {
    reviewForm.addEventListener('submit', (event) => {
        const bookId = reviewForm.book_id.value;
        const title = reviewForm.review_title.value.trim();
        const content = reviewForm.review_content.value.trim();
        const messages = [];

        if (!bookId) {
            messages.push('Select a book.');
        }

        if (!title) {
            messages.push('Review title is required.');
        } else if (title.length > 200) {
            messages.push('Review title must be 200 characters or less.');
        }

        if (content.length < 30) {
            messages.push('Review content must be at least 30 characters.');
        }

        ['cover_image', 'media_file', 'downloadable_file'].forEach((fieldName) => {
            const fileInput = reviewForm[fieldName];

            if (fileInput.files.length > 0 && fileInput.files[0].size > 10 * 1024 * 1024) {
                messages.push(`${fileInput.labels[0].textContent} must be 10MB or smaller.`);
            }
        });

        if (messages.length > 0) {
            event.preventDefault();
            alert(messages.join('\n'));
        }
    });
}
