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