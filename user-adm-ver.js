//function to check the login
function CheckLogin()
{
    //get the tokens
    const authtoken = localStorage.getItem('authToken');
    const usertoken= sessionStorage.getItem('username');
    const idtoken = sessionStorage.getItem("userid");
    const username = sessionStorage.getItem("username");

    if(!usertoken && !idtoken && !username && authtoken)
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
                    if(data.message === "User not Found")
                    {
                        sessionStorage.clear();
                        localStorage.clear();
                        window.location.href = 'loginuser.html';
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error grabbing username: ' + error.message);
            });
    }
    if(!authtoken && !usertoken && !username && !idtoken)
    {
        localStorage.clear();
        sessionStorage.clear();
        window.location.href = 'loginuser.html';
    }

}

//cheks the user type and returns true if user, false if adm
function CheckIfUser()
{
    var typetoken = sessionStorage.getItem('type');
    return typetoken === "user";
}