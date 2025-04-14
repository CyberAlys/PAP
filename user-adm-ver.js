//functioon to check the login
function CheckLogin()
{
    //get the tokens
    var authtoken = localStorage.getItem('authToken');
    var usertoken= sessionStorage.getItem('username');
    var idtoken = sessionStorage.getItem("userid");

    if(!usertoken && !idtoken && authtoken)
    {
        fetch('login.php?type=FindUser', {
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

                    // Store token in localStorage
                    sessionStorage.setItem('username', data.username);

                    // Verify if localStorage was set
                    console.log("SessionStorage set:", {
                        username: sessionStorage.getItem('username'),
                    });

                    //I used this to test it
                    //alert(data.username);

                    //going to the new webpage
                    window.location.href = 'ReLogin.html';
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error grabbing username: ' + error.message);
            });
    }
    if(!authtoken && !usertoken && !idtoken)
    {
        localStorage.clear();
        sessionStorage.clear();
        window.location.href = 'loginuser.html';
    }

}

function CheckIfUser()
{
    var typetoken = sessionStorage.getItem('type');
    if(typetoken == "user")
    {
        return true;
    }
    else
    {
        return false;
    }
}