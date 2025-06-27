function toggleImageUpload() {
    const imageSection = document.getElementById("image-section");
    imageSection.style.display = imageSection.style.display === "none" ? "block" : "none";

    const imgInput = document.getElementById("img");
    if (imageSection.style.display === "none") {
        // clear file input
        imgInput.value = "";
    }
}
function uploadpicturetophp(e) {
    e.preventDefault();

    const userid = sessionStorage.getItem("userid");

    const textInput = document.getElementById("text");
    if (!textInput) {
        alert("Post input field not found.");
        return;
    }
    const text = textInput.value;

    // Check if image section is visible
    if (document.getElementById("image-section").style.display !== "none") {
        const fileInput = document.getElementById("img");
        const file = fileInput.files[0];

        if (!file) {
            alert("No image selected!");
            return;
        }

        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        const maxSize = 70 * 1024 * 1024; // 70 MB

        if (!allowedTypes.includes(file.type)) {
            alert("Only JPG, PNG, or GIF images are allowed.");
            return;
        }

        if (file.size > maxSize) {
            alert("Image is too large. Please select one under 70MB.");
            return;
        }

        const reader = new FileReader();

        reader.onload = function (e) {
            const img = new Image();

            img.onload = function () {
                const width = img.width;
                const height = img.height;

                const data = {
                    usrid: userid,
                    istherephoto: true,
                    photo: e.target.result,
                    text: text,
                    imagewidth: width,
                    imageheight: height
                };

                sendToServer(data); // call the fetch directly
            };

            img.src = e.target.result;
        };

        reader.readAsDataURL(file);
    } else {
        const data = {
            usrid: userid,
            istherephoto: false,
            text: text
        };

        sendToServer(data); // same as above
    }

    return false;
}

// Separate function to send data to PHP
function sendToServer(data) {
    fetch('photos.php?type=saveinserver', {
        method: 'POST',
        body: JSON.stringify(data)
    })
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            if (data.success === "Yes") {
                window.top.location.href = 'mainpage.html';
            } else {
                alert(data.message || "Upload failed.");
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error uploading message: ' + error.message);
        });
}

function IDSubmit(id) {

    sessionStorage.setItem("postId", id);
    window.location.href = 'photodisplay.html';
}

//grabs and loads all things of the post
function loadPhotoDetails(postId, viewerid, containerSelector = "#post-container") {
    if (!postId) {
        window.location.href = "allphotos.html";
        return;
    }

    fetch('photos.php?type=getphoto', {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({ postid: postId, viewerid: viewerid })
    })
        .then(response => {
            if (!response.ok) console.error('Network response was not ok');
            return response.json();})
        .then(data => {
            if (data.success === "Yes") {
                const container = document.querySelector(containerSelector);
                if (!container) return;

                const pfp = container.querySelector("#OP-pfp");
                const name = container.querySelector("#OP-name");
                const text = container.querySelector("#post-text");
                const photoContainer = container.querySelector("#photo-container");
                const photo = container.querySelector("#displayed-photo");
                const dimensions = container.querySelector("#photo-dimensions");
                const date = container.querySelector("#post-date");
                const likenumber = container.querySelector("#likenumber");
                const likechbox = container.querySelector('#like');


                // fill the fields in the divs
                pfp.src = data.profilepic;
                const opName = document.getElementById("OP-name");
                opName.textContent = data.username;
                opName.href = `userarea.html?userid=${data.userid}`;
                text.textContent = data.text;
                date.textContent = `Posted on ${data.date}`;

                const type = sessionStorage.getItem('type');
                console.log("viewerid", viewerid, "post owner", data.userid);

                if ((parseInt(viewerid) === parseInt(data.userid)) || type === "adm") {
                    document.getElementById("post-options").style.display = "block";
                }

                //if to see if there's a photo
                if (data.photo === true) {
                    photo.src = data.path;
                    photo.alt = "Uploaded photo";
                    dimensions.textContent = `Size: ${data.width} x ${data.height}`;
                    photoContainer.style.display = "block";
                } else {
                    photoContainer.style.display = "none";
                }

                const likeButton = container.querySelector('#like-button');
                const heart = likeButton?.querySelector(".heart-icon");

                if (likeButton) {
                    if (data.viwerlike === 1) {
                        likeButton.classList.add('liked');
                        if (heart) heart.textContent = "💙";
                    } else {
                        likeButton.classList.remove('liked');
                        if (heart) heart.textContent = "🤍";
                    }
                }

                const likeDisplay = document.getElementById("likenumber");
                if (likeDisplay) likeDisplay.textContent = data.likenumber;



            } else {
                alert(data.message || "Failed to load post.");
            }
        })
        .catch(err => {
            console.error("Error:", err);
            alert("Error fetching post.");
        });
}

//requests the info to create the feed and populates and clones the div
function loadFeed() {
    const page = 1;

    console.log("js called");

    fetch("photos.php?type=getfeed", {
        method: "POST",
        headers: {
        "Content-Type": "application/json"
    },
    body: JSON.stringify({ page: page })
    })
        .then(response => {
            if (!response.ok)  console.error('Network response was not ok');
            return response.json();})
        .then(data => {
            if (data.success === "Yes" && Array.isArray(data.posts)) {
                //grabs the div and puts into vars
                const feed = document.querySelector("#post-feed");
                const template = document.querySelector("#post-template");

                //for each item in the array
                data.posts.forEach(post => {
                    //grabs template and clones it
                    const clone = template.cloneNode(true);
                    clone.style.display = "block";

                    //stores the id
                    clone.setAttribute("data-postid", post.postid);

                    //populates clones
                    clone.querySelector(".pfp").src = post.profilepic;
                    clone.querySelector(".username").textContent = post.username;
                    clone.querySelector(".text").textContent = post.text;

                    //populate photos in case there's one if not hides the img
                    if (post.photo) {
                        const img = clone.querySelector(".photo");
                        img.src = post.path;
                        img.style.width = "300px";
                        img.style.height = "300px";
                    } else {
                        clone.querySelector(".photo-container").remove(); // remove image box if no photo
                    }

                    feed.appendChild(clone);
                });
            }
            else if(data.success === "No")
            {
                alert(data.message)
            }
        })
    .catch(err => {
         console.error("Failed to load feed:", err);
         alert(err);
    });
}

function like(isLiked) {
    const postId = sessionStorage.getItem("postId");
    const userId = sessionStorage.getItem("userid");

    fetch('photos.php?type=changelike', {
        method: 'POST',
        body: JSON.stringify({ postid: postId, userid: userId, liked: isLiked }),
        headers: { 'Content-Type': 'application/json' }
    })
        .then(response => {
            if (!response.ok) throw new Error("Network error");
            return response.json();
        })
        .then(data => {
            if (data.success === "Yes") {
                const likeDisplay = document.getElementById("likenumber");
                if (likeDisplay) likeDisplay.textContent = data.countlike;
            } else {
                console.error("Server error:", data.message || "Unknown error");
            }
        })
        .catch(err => {
            console.error("Error sending like:", err);
        });
}

function updateLikeCount() {
    // Call endpoint or reload the number
    fetch(`/getlikes.php?postId=${sessionStorage.getItem("postId")}`)
        .then(res => res.json())
        .then(data => {
            document.getElementById("likenumber").textContent = data.likes || 0;
        });
}


function submitComment(commentData) {
    // Create temporary comment data
    const tempComment = {
        commentid: 'temp-' + Date.now(),
        commenttext: commentData.commenttext,
        date: new Date().toISOString(),
        username: sessionStorage.getItem('username'),
        profilepic: sessionStorage.getItem('profilepic'),
        replies: []
    };

    // Remove the reply form immediately
    const replyForm = document.querySelector(`.reply-form[data-parentid="${commentData.response_commentid}"]`);
    if (replyForm) replyForm.remove();

    // Optimistically render the comment
    if (commentData.response_commentid) {
        const parentComment = document.querySelector(`[data-comment-id="${commentData.response_commentid}"]`);
        if (parentComment) {
            const repliesContainer = parentComment.querySelector('.comment-replies') ||
                parentComment.appendChild(document.createElement('div')).classList.add('comment-replies');
            renderComment(tempComment, repliesContainer);
        }
    }

    // Submit to server
    fetch('photos.php?type=insertcomment', {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(commentData)
    })
        .then(res => res.json())
        .then(data => {
            if (data.success === "Yes") {
                // Replace temporary comment with server data if needed
                const tempElement = document.querySelector(`[data-comment-id="${tempComment.commentid}"]`);
                if (tempElement) {
                    // Update with real data if your server returns the created comment
                    if (data.comment) {
                        tempElement.querySelector('.comment-text').textContent = data.comment.commenttext;
                        tempElement.dataset.commentId = data.comment.commentid;
                    }
                }
                loadComments(sessionStorage.getItem("postId"));
            } else {
                // Remove temporary comment if submission failed
                document.querySelector(`[data-comment-id="${tempComment.commentid}"]`)?.remove();
                alert(data.message || "Failed to post comment.");
            }
        })
        .catch(err => {
            console.error("Error submitting comment:", err);
            document.querySelector(`[data-comment-id="${tempComment.commentid}"]`)?.remove();
        });
}


function formatDate(dateString){
const date = new Date(dateString);
return date.toLocaleString(); // Customize format if needed
}


// Recursive renderer for comments and their replies
function renderComment(commentData, parentContainer) {
    const template = document.querySelector('#comment-template');
    const clone = template.content.cloneNode(true);
    const commentDiv = clone.querySelector('.comment');
    const currentUserId = parseInt(sessionStorage.getItem('userid'));
    const isAdmin = sessionStorage.getItem('type') === 'adm';
    const isCommentOwner = currentUserId === commentData.userid;

    commentDiv.dataset.commentId = commentData.commentid;

    // Handle profile picture and username
    const profilePic = commentData.is_anonymous || commentData.userid === null ?
        'photos/profile_pictures/Default_pfp.jpg' :
        (commentData.profilepic || 'photos/profile_pictures/Default_pfp.jpg');

    const username = commentData.is_anonymous || commentData.userid === null ?
        'Anonymous' :
        commentData.username;

    clone.querySelector('.comment-pfp').src = profilePic;
    clone.querySelector('.comment-username').textContent = username;
    clone.querySelector('.comment-date').textContent = formatDate(commentData.date);
    clone.querySelector('.comment-text').textContent = commentData.commenttext;

    // Set up menu visibility (only show if admin or owner)
    const menuContainer = clone.querySelector('.comment-options-menu');
    if (isAdmin || isCommentOwner) {
        const menuButton = clone.querySelector('.comment-menu-toggle');
        const dropdown = clone.querySelector('.comment-menu-dropdown');

        menuButton.addEventListener('click', (e) => {
            e.stopPropagation();
            menuContainer.classList.toggle('active');
        });

        // Delete button handler
        clone.querySelector('.delete-comment').addEventListener('click', () => {
            if (confirm('Are you sure you want to delete this comment?')) {
                deleteComment(commentData.commentid);
            }
            menuContainer.classList.remove('active');
        });

        // Close when clicking outside
        document.addEventListener('click', function closeMenu(e) {
            if (!menuContainer.contains(e.target)) {
                menuContainer.classList.remove('active');
                document.removeEventListener('click', closeMenu);
            }
        });
    } else {
        menuContainer.style.display = 'none';
    }

    // Set up reply button
    const replyButton = clone.querySelector('.reply-button');
    replyButton.addEventListener('click', () => {
        showReplyForm(commentData.commentid);
    });

    parentContainer.appendChild(clone);

    // Render replies if any
    if (commentData.replies && commentData.replies.length > 0) {
        const repliesContainer = commentDiv.querySelector('.comment-replies');
        commentData.replies.forEach(reply => renderComment(reply, repliesContainer));
    }
}

function showReplyForm(parentCommentId) {
    // Remove any existing form first
    const existing = document.querySelector(`.reply-form[data-parentid="${parentCommentId}"]`);
    if (existing) {
        existing.remove();
        return;
    }

    const form = document.createElement("div");
    form.classList.add("comment-form", "reply-form");
    form.dataset.responsetype = "comment";
    form.dataset.responseid = parentCommentId;
    form.dataset.parentid = parentCommentId;

    form.innerHTML = `
        <textarea class="comment-input" placeholder="Write a reply..." autofocus></textarea>
        <div class="reply-form-buttons">
            <button class="comment-submit">Reply</button>
            <button class="cancel-reply">Cancel</button>
        </div>
    `;

    const parentComment = document.querySelector(`[data-comment-id="${parentCommentId}"]`);
    if (!parentComment) {
        console.error("Parent comment not found");
        return;
    }

    // Find or create replies container
    let repliesContainer = parentComment.querySelector('.comment-replies');
    if (!repliesContainer) {
        repliesContainer = document.createElement('div');
        repliesContainer.classList.add('comment-replies');
        parentComment.appendChild(repliesContainer);
    }

    repliesContainer.appendChild(form);

    // Focus the textarea immediately
    form.querySelector(".comment-input").focus();

    // Submit handler
    form.querySelector(".comment-submit").addEventListener("click", () => {
        const commenttext = form.querySelector(".comment-input").value.trim();
        const userid = parseInt(sessionStorage.getItem('userid'));
        const response_commentid = parseInt(form.dataset.responseid);

        if (!commenttext) {
            alert("Reply cannot be empty.");
            return;
        }

        submitComment({ userid, commenttext, response_commentid });
    });

    // Cancel handler
    form.querySelector(".cancel-reply").addEventListener("click", () => {
        form.remove();
    });

    // Close when clicking outside
    document.addEventListener('click', function closeForm(e) {
        if (!form.contains(e.target) && e.target.className !== 'reply-button') {
            form.remove();
            document.removeEventListener('click', closeForm);
        }
    }, { once: true });
}

// Load comments from server
function loadComments(postId) {
    fetch('photos.php?type=getcomments', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ postid: postId })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success === "Yes" && Array.isArray(data.comments)) {
                const container = document.querySelector('#comments-section');

                // Only clear if we have new comments to show
                if (data.comments.length > 0) {
                    container.innerHTML = "";
                    data.comments.forEach(comment => renderComment(comment, container));
                }
            } else {
                console.error("Failed to load comments");
            }
        })
        .catch(err => {
            console.error("Error fetching comments:", err);
        });
}

function deletePost() {
    const postid = sessionStorage.getItem("postId");

    fetch("photos.php?type=deletepost", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ postid: postid}),
    })
        .then((res) => res.json())
        .then((data) => {
            if (data.success === "Yes") {
                alert("Post deleted!");
                window.location.href = "allphotos.html";
            } else {
                alert("Failed to delete post: " + data.message);
            }
        })
        .catch((err) => {
            console.error("Delete failed:", err);
        });
}

function cancelEdit() {
    document.getElementById("edit-post-area").style.display = "none";
}

function saveEditedPost() {
    const newText = document.getElementById("edit-post-text").value.trim();
    const postid = sessionStorage.getItem("postId");

    if (!newText) {
        alert("Post text cannot be empty.");
        return;
    }

    fetch("photos.php?type=editpost", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ postid, text: newText })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success === "Yes") {
                document.getElementById("post-text").textContent = newText;
                cancelEdit();
            } else {
                alert("Failed to edit post: " + data.message);
            }
        })
        .catch(err => {
            console.error("Edit error:", err);
            alert("Error editing post.");
        });
}

function deleteComment(commentId) {
    fetch('photos.php?type=deletecomment', {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            commentid: commentId,
            userid: sessionStorage.getItem('userid')
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success === "Yes") {
                // Remove the comment from DOM
                const commentElement = document.querySelector(`[data-comment-id="${commentId}"]`);
                if (commentElement) {
                    commentElement.remove();
                }
                alert("Comment deleted successfully");
            } else {
                alert(data.message || "Failed to delete comment");
            }
        })
        .catch(err => {
            console.error("Error deleting comment:", err);
            alert("An error occurred while deleting the comment");
        });
}