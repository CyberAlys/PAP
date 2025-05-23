function changetouploadpage()
{
    sessionStorage.setItem('phototype', 'upload');
    window.location.href = 'photos.html';
}
function uploadpicturetophp(e) {
 e.preventDefault();

 const title = document.getElementById("Tittle").value;
 const descript = document.getElementById("description").value;
 const fileInput = document.getElementById("img");
 const file = fileInput.files[0];

 //basic checks for missing file, size and type
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

   const userid = sessionStorage.getItem("userid");
   const data =
       {
        usrid: userid,
        photoTitle : title,
        photo : e.target.result,
        description : descript,
        imagewidth : width,
        imageheight : height
       };
      fetch('photos.php?type=saveinserver',
          {method: 'POST',
              body: JSON.stringify(data)
          })
          .then(response => {
              if (!response.ok) {
                  throw new Error('Network response was not ok');
              }
              return response.json();
          })
          //to be done after php
          .then(data => {
              if (data.success === "Yes") {

                  //going to the new webpage
                  window.location.href = 'mainpage.html';
              } else {
                  alert(data.message);
              }
          })
          .catch(error => {
              console.error('Error:', error);
              alert('Error uploading message: ' + error.message);
          });

      return false;

  };

  img.src = e.target.result;
 };

 reader.readAsDataURL(file);
}

function handlePhotoSubmit(e) {
    e.preventDefault(); // Prevent page reload
    const id = document.getElementById('numbers').value;
    loadPhotoDetails(id);
}

function loadPhotoDetails(photoId, containerSelector = "#photo-container") {
    if (!photoId) {
        alert("No photo ID provided.");
        return;
    }

    fetch('photos.php?type=getphoto', {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({ photoid: photoId })
    })
        .then(res => res.json())
        .then(data => {
            if (data.success === "Yes") {
                const container = document.querySelector(containerSelector);
                if (!container) return;

                container.innerHTML = `
                    <img src="${data.path}" alt="${data.title}" />
                    <h2>${data.title}</h2>
                    <p>${data.description}</p>
                    <p>Size: ${data.width} x ${data.height}</p>
                `;
            } else {
                alert(data.message || "Failed to load photo.");
            }
        })
        .catch(err => {
            console.error("Error:", err);
            alert("Error fetching photo.");
        });
}