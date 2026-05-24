const commentForm = document.querySelector('.comment-form');
const commentList = document.querySelector('.comment-list');
const emptyText = document.querySelector('.muted-text');

if (commentForm && commentList) {
    commentForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const commentText = formData.get('comment_text').trim();

        if (commentText === '') return;

        fetch(window.location.href, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Create new comment element
                const newComment = document.createElement('article');
                newComment.className = 'comment-item';
                newComment.style.borderBottom = '1px solid #eee';
                newComment.style.padding = '1rem 0';
                newComment.innerHTML = `
                    <h3 style="margin: 0; font-size: 1.1em;">${data.username}</h3>
                    <p style="margin: 0.5rem 0;">${data.comment_text}</p>
                    <p class="muted-text" style="font-size: 0.85em;">${data.created_at}</p>
                `;

                // Add to list
                if (commentList.querySelector('.comment-item')) {
                    commentList.insertBefore(newComment, commentList.firstChild);
                } else {
                    commentList.innerHTML = '';
                    commentList.appendChild(newComment);
                }

                // Clear textarea
                commentForm.querySelector('textarea').value = '';

                // Hide empty state if it exists
                const emptyMsg = document.querySelector('.muted-text');
                if (emptyMsg && emptyMsg.textContent.includes('No comments yet')) {
                    emptyMsg.style.display = 'none';
                }
            } else {
                alert(data.message || 'Error posting comment.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    });
}
