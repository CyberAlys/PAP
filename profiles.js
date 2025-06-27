// Utility function to handle fetch errors
async function fetchData(url, options) {
    try {
        const response = await fetch(url, options);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return await response.json();
    } catch (error) {
        console.error('Fetch error:', error);
        throw error;
    }
}

async function loadUserInfo(userid) {
    try {
        const data = await fetchData("profiles.php?type=getuserinfo", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ userid })
        });

        console.log("User info data:", data);
        if (data.success === "Yes") {
            document.getElementById("username").textContent = data.username || "User";
            const pfp = document.getElementById("userpfp");
            pfp.src = data.profilepic || "defaultpfp.png";
            pfp.onerror = () => { pfp.src = "defaultpfp.png"; };

            document.getElementById("followers-count").textContent = `${data.followers} followers`;
            document.getElementById("following-count").textContent = `${data.following} following`;
        }
        else {
            throw new Error(data.message || "Failed to load user info");
        }
    } catch (err) {
        console.error("Error loading user info:", err);
        document.getElementById("username").textContent = "Error loading profile";
    }
}

async function loadUserPosts(userid) {
    const section = document.getElementById("posts-section");
    section.innerHTML = "<p>Loading posts...</p>";

    try {
        const data = await fetchData("profiles.php?type=userposts", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ userid })
        });

        section.innerHTML = "";

        if (data.success === "Yes" && Array.isArray(data.posts)) {
            const template = document.querySelector("#post-template");

            if (data.posts.length === 0) {
                section.innerHTML = "<p>No posts to show.</p>";
                return;
            }

            data.posts.forEach(post => {
                const clone = template.content.cloneNode(true);
                const postElem = clone.querySelector(".post");
                postElem.style.display = "block";
                postElem.setAttribute("data-postid", post.postid);

                postElem.querySelector(".pfp").src = post.profilepic;
                postElem.querySelector(".username").textContent = post.username;
                postElem.querySelector(".text").textContent = post.text;

                if (post.photo && post.path) {
                    const img = postElem.querySelector(".photo");
                    if (img) {
                        img.src = post.path;
                        img.style.width = "300px";
                        img.style.height = "300px";
                    }
                } else {
                    const photoContainer = postElem.querySelector(".photo-container");
                    if (photoContainer) {
                        photoContainer.remove();
                    }
                }

                postElem.addEventListener("click", () => {
                    sessionStorage.setItem("postId", post.postid);
                    window.location.href = "photodisplay.html";
                });

                section.appendChild(postElem);
            });
        } else {
            section.innerHTML = `<p>${data.message || "No posts to show."}</p>`;
        }
    } catch (err) {
        console.error("Failed to load user posts:", err);
        section.innerHTML = "<p>Error loading posts. Please try again.</p>";
    }
}

async function loadUserLikedPosts(userid) {
    const section = document.getElementById("liked-section");
    section.innerHTML = "<p>Loading liked posts...</p>";

    try {
        const data = await fetchData("profiles.php?type=likedposts", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ userid })
        });

        console.log("Liked posts data:", data);
        section.innerHTML = "";

        if (data.success === "Yes" && Array.isArray(data.posts)) {
            const template = document.querySelector("#post-template");

            if (data.posts.length === 0) {
                section.innerHTML = "<p>No liked posts to show.</p>";
                return;
            }

            data.posts.forEach(post => {
                const clone = template.content.cloneNode(true);
                const postElem = clone.querySelector(".post");
                postElem.style.display = "block";
                postElem.setAttribute("data-postid", post.postid);

                postElem.querySelector(".pfp").src = post.profilepic;
                postElem.querySelector(".username").textContent = post.username;
                postElem.querySelector(".text").textContent = post.text;

                if (post.photo && post.path) {
                    const img = postElem.querySelector(".photo");
                    if (img) {
                        img.src = post.path;
                        img.style.width = "300px";
                        img.style.height = "300px";
                    }
                } else {
                    const photoContainer = postElem.querySelector(".photo-container");
                    if (photoContainer) {
                        photoContainer.remove();
                    }
                }

                postElem.addEventListener("click", () => {
                    sessionStorage.setItem("postId", post.postid);
                    window.location.href = "photodisplay.html";
                });

                section.appendChild(postElem);
            });
        } else {
            section.innerHTML = `<p>${data.message || "No liked posts to show."}</p>`;
        }
    } catch (err) {
        console.error("Failed to load liked posts:", err);
        section.innerHTML = "<p>Error loading liked posts. Please try again.</p>";
    }
}

async function toggleFollow(viewerId, profileUserId) {
    try {
        const currentlyFollowing = await checkFollowStatus(viewerId, profileUserId);
        const action = currentlyFollowing ? "unfollow" : "follow";

        const response = await fetch(`profiles.php?type=${action}`, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                followerId: viewerId,
                followedId: profileUserId
            })
        });

        const data = await response.json();

        if (data.success === "Yes") {
            // Update button text
            document.getElementById("profile-action-btn").textContent =
                data.isFollowing ? "Unfollow" : "Follow";

            // Refresh counts
            await loadUserInfo(profileUserId);

            return data.isFollowing;
        } else {
            throw new Error(data.message || "Failed to update follow status");
        }
    } catch (err) {
        console.error("Follow error:", err);
        alert("Error updating follow status");
        throw err;
    }
}

async function checkFollowStatus(viewerId, profileUserId) {
    try {
        const response = await fetch("profiles.php?type=checkfollow", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                followerId: viewerId,
                followedId: profileUserId
            })
        });

        const data = await response.json();

        if (data.success === "Yes") {
            return data.isFollowing;
        } else {
            throw new Error(data.message || "Failed to check follow status");
        }
    } catch (err) {
        console.error("Follow check error:", err);
        return false;
    }
}

async function ProfilePicChange(userid) {
    const form = document.getElementById("pfp-form");
    const fileInput = document.getElementById("profilepic");
    const uploadBtn = document.getElementById("upload-btn");
    const progress = document.getElementById("upload-progress");

    const file = fileInput.files[0];
    if (!file) {

        showMessage({ success: "No", message: "Please select a file" }, "pfp-message");
        return;
    }

    if (file.size > 2 * 1024 * 1024) {
        showMessage({ success: "No", message: "File size exceeds 2MB limit" }, "pfp-message");
        return;
    }

    const formData = new FormData();
    formData.append("profilepic", file);
    formData.append("userid", userid);

    try {
        uploadBtn.disabled = true;
        progress.style.display = "block";

        const res = await fetch("profiles.php?type=profilepic", {
            method: "POST",
            body: formData
        });

        const data = await res.json();

        if (data.success === "Yes") {
            const currentPic = document.getElementById("current-profile-pic");
            currentPic.src = data.profilepic;
            sessionStorage.setItem("profilepic", data.profilepic);
        }

        showMessage(data, "pfp-message");
    } catch (err) {
        console.error("Upload error:", err);
        showMessage({ success: "No", message: "Upload failed. Please try again." }, "pfp-message");
    } finally {
        uploadBtn.disabled = false;
        progress.style.display = "none";
    }
}


// Username change function
async function UsernameChange(userid) {
    const form = document.getElementById("username-form");
    const newusername = form.newusername.value.trim();

    if (!newusername) {
        showMessage({ success: "No", message: "Please enter a username" }, "username-message");
        return;
    }

    try {
        const res = await fetch("profiles.php?type=username", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ userid, newusername })
        });
        const data = await res.json();
        showMessage(data, "username-message");

        if (data.success === "Yes" && data.username) {
            sessionStorage.setItem("username", data.username);
        }
    } catch (err) {
        console.error("Username change error:", err);
        showMessage({ success: "No", message: "Username update failed." }, "username-message");
    }
}


// Password change function
async function PasswordChange(userid) {
    const form = document.getElementById("password-form");
    const oldpassword = form.oldpassword.value;
    const newpassword = form.newpassword.value;
    const confirmpassword = form.confirmpassword.value;

    if (newpassword !== confirmpassword) {
        showMessage({ success: "No", message: "Passwords do not match" }, "password-message");
        return;
    }

    try {
        const res = await fetch("profiles.php?type=password", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ userid, oldpassword, newpassword })
        });
        const data = await res.json();
        showMessage(data, "password-message");
    } catch (err) {
        console.error("Password change error:", err);
        showMessage({ success: "No", message: "Password change failed." }, "password-message");
    }
}
