
function changetouploadpage()
{
    sessionStorage.setItem('phototype', 'upload');
    window.location.href = 'photos.html';
}
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
                window.location.href = 'mainpage.html';
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
function loadPhotoDetails(postId, containerSelector = "#post-container") {
    if (!postId) {
        alert("No post ID provided.");
        return;
    }

    fetch('photos.php?type=getphoto', {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({ postid: postId })
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

                // fill the fields in the divs
                pfp.src = data.profilepic;
                name.textContent = data.username;
                text.textContent = data.text;
                date.textContent = `Posted on ${data.date}`;

                //if to see if there's a photo
                if (data.photo === true) {
                    photo.src = data.path;
                    photo.alt = "Uploaded photo";
                    dimensions.textContent = `Size: ${data.width} x ${data.height}`;
                    photoContainer.style.display = "block";
                } else {
                    photoContainer.style.display = "none";
                }

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
