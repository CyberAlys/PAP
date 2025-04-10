
//switches the password input type for a text input type in the log in form
function showPasswordlogin()
    {
        var pass = document.getElementById("log-password");

        if (pass.type === "password")
        {
        pass.type = "text";
    }
        else
    {
        pass.type = "password";
    }
    }


//same as the above but for the register
function showPasswordreg()
{
    var pass = document.getElementById("Reg-Password");
    var confpass = document.getElementById("Reg-confPass");

    if (pass.type === "password")
    {
        pass.type = "text";
        confpass.type = "text";
    }
        else
    {
        pass.type = "password";
        confpass.type = "password";
    }
}

//switches the form from the log in to the register
    function showregitser()
    {
        document.getElementById('register').style.display =  'block';
        document.getElementById('switchtologin').style.display = 'block';
        document.getElementById('login').style.display =  'none';
        document.getElementById('switchtoregister').style.display = 'none';

        document.title = "Register";
    }

//switches the form from the register to the log in
    function showlogin()
    {
        document.getElementById('login').style.display =  'block';
        document.getElementById('switchtoregister').style.display = 'block';
        document.getElementById('register').style.display =  'none';
        document.getElementById('switchtologin').style.display = 'none';

        document.title = "Log In";
    }

//register function
    function RegisterPHP(formdata)
    {
        //call login.php and send trough GET the type register, inserts formdata trough POST
        fetch('login.php?type=Register', {
            method: 'POST',
            body: formdata
        })
        //response is an object that gets what the fetch returns
        .then(response => {
                //response.ok serves as a bool that it's true if the status range is 200-299(successfull)
                if (!response.ok)
                {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }

                //check if response is JSON (troubleshooting)
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json'))
                {
                    return response.json();
                }
                else
                {
                    //If not JSON, get the text and show the error with the text
                    return response.text().then(text => {
                        throw new Error(`Server returned non-Json response: ${text}`);
                    });
                }
            })
            //gets the tokens from the php
         .then(data => {
             if(data.token && data.username)
             {
                 //stores the tokens in the browser and changes the webpage
                localStorage.setItem('authToken', data.token);
                sessionStorage.setItem('type', data.type);
                sessionStorage.setItem('username', data.username);
                sessionStorage.setItem('userid', data.userid);

                window.location.replace('mainpage.html');
             }
             else
             {
                 //alerts in case of registering but having a problems with the tokens
                alert(data.message || 'registration complete, an error occured, try loggin in manually');
             }
         })
            .catch( error => {
                //Try-catch like C# not much to see here
                console.error('Error', error);
                alert('Error during registration: ' + error.message);
            });

        return false;
    }


    //function to log in
    function LoginPHP()
    {
        //grabs data from the form (another way to do the same thing done above)
    const formData = new FormData(document.getElementById('login'));

        //call ajax
    fetch('login.php?type=Login',
        {method: 'POST',
            body: formData
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.token && data.username) {

                // Store tokens in localStorage or in SessionStorage
                localStorage.setItem('authToken', data.token);
                sessionStorage.setItem('type', data.type);
                sessionStorage.setItem('username', data.username);
                sessionStorage.setItem('userid', data.userid);

                // Verify if localStorage was set
                console.log("localStorage set:", {
                    token: localStorage.getItem('authToken'),
                    type: localStorage.getItem('type'),
                    username: localStorage.getItem('username'),
                    userid: sessionStorage.getItem('userid')
                });

                //going to the new webpage
                window.location.href = 'mainpage.html';
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error logging in: ' + error.message);
        });

        return false;
    }