//functioon to check the login
function checklogin()
{
    //get the tokens
    var authtoken = localStorage.getItem('authToken');
    var usertoken= sessionStorage.getItem('username');

    if(!usertoken && authtoken)
    {
        fetch('login.php?type=re-login', {
            method: 'POST' ,
            body: authtoken
         })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.token && data.username) {

                    // Store tokens in localStorage
                    sessionStorage.setItem('userid', data.userid);

                    // Verify if localStorage was set
                    console.log("localStorage set:", {
                        userid: sessionStorage.getItem('userid')
                    });

                    //going to the new webpage
                    window.location.href = 'ReLogin.html';
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error logging in: ' + error.message);
            });
    }

}

function checkusertype()
{

}