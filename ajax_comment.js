const commentForm = document.getElementById('commentForm');

if (commentForm) {

    commentForm.addEventListener('submit', function (event) {
        event.preventDefault(); 

        fetch('add_comment.php', { method: 'POST', body: new FormData(commentForm) })
            .then(function (response) {
                return response.text();
            })
            .then(function (html) {
                if (html === '') {
                    alert('Please write a comment.');
                    return;
                }

                document.getElementById('commentList').insertAdjacentHTML('afterbegin', html);
                commentForm.reset();
            });
    });

}