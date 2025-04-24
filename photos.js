function changetouploadpage()
{
 sessionStorage.setItem('phototype', 'upload');
 window.location.href = 'photos.html';
}

function uploadpicturetophp(e) {
 e.preventDefault();

 const tittle = document.getElementById("Tittle").value;
 const descript = document.getElementById("description").value;
 const fileInput = document.getElementById("img");
 const file = fileInput.files[0];

 if (!file) {
  alert("No image selected!");
  return;
 }

 const reader = new FileReader();

 reader.onload = function (e) {
  const img = new Image();

  img.onload = function () {
   const width = img.width;
   const height = img.height;

   alert(`Tittle: ${tittle}\nDescription: ${descript}\nImage size: ${width} x ${height}`);

   // 🧠 Do more here if needed: send to PHP, validate, etc.
  };

  img.src = e.target.result;
 };

 reader.readAsDataURL(file);
}