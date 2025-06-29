
//switches the password input type for a text input type in the log in form
function showPasswordlogin()
    {
        const pass = document.getElementById("log-password");

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
    const pass = document.getElementById("Reg-Password");
    const confpass = document.getElementById("Reg-confPass");

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
function showPasswordrelog()
{
    let pass = document.getElementById("Relog-Pass");

    if (pass.type === "password")
    {
        pass.type = "text";
    }
    else
    {
        pass.type = "password";
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
//     function RegisterPHP(formdata)
//     {
//         //call login.php and send trough GET the type register, inserts formdata trough POST
//         fetch('login.php?type=Register', {
//             method: 'POST',
//             body: formdata
//         })
//         //response is an object that gets what the fetch returns
//         .then(response => {
//                 //response.ok serves as a bool that it's true if the status range is 200-299(successfull)
//                 if (!response.ok)
//                 {
//                     throw new Error(`HTTP error! Status: ${response.status}`);
//                 }
//
//                 //check if response is JSON (troubleshooting)
//                 const contentType = response.headers.get('content-type');
//                 if (contentType && contentType.includes('application/json'))
//                 {
//                     return response.json();
//                 }
//                 else
//                 {
//                     //If not JSON, get the text and show the error with the text
//                     return response.text().then(text => {
//                         throw new Error(`Server returned non-Json response: ${text}`);
//                     });
//                 }
//             })
//             //gets the tokens from the php
//          .then(data => {
//              if(data.token && data.username)
//              {
//                  //stores the tokens in the browser and changes the webpage
//                 localStorage.setItem('authToken', data.token);
//                 sessionStorage.setItem('type', data.type);
//                 sessionStorage.setItem('username', data.username);
//                 sessionStorage.setItem('userid', data.userid);
//                 sessionStorage.setItem('profilepic', data.pfp)
//
//                 window.location.replace('mainpage.html');
//              }
//              else
//              {
//                  //alerts in case of registering but having a problems with the tokens
//                 alert(data.message || 'registration complete, an error occured, try loggin in manually');
//              }
//          })
//             .catch( error => {
//                 //Try-catch like C# not much to see here
//                 console.error('Error', error);
//                 alert('Error during registration: ' + error.message);
//             });
//
//         return false;
//     }


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
                sessionStorage.setItem('profilepic', data.pfp);

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

//log back in
function ReLoginPHP()
{
    const username = sessionStorage.getItem('username');
    const pass = document.getElementById('Relog-Pass').value;
    const data =
    {
        user: username,
        pass: pass
    }
    //call ajax
    fetch('login.php?type=Relogin',
        {method: 'POST',
            body: JSON.stringify(data)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.token && data.username) {

                //clean local and session storage
                localStorage.clear();
                sessionStorage.clear();

                // Store tokens in localStorage or in SessionStorage
                localStorage.setItem('authToken', data.token);
                sessionStorage.setItem('type', data.type);
                sessionStorage.setItem('username', data.username);
                sessionStorage.setItem('userid', data.userid);
                sessionStorage.setItem('profilepic', data.pfp);

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

let tempUserData = null; // Store user data temporarily during verification
let verificationCode = ''; // Store the generated code

//new register with mail
function RegisterPHP(formdata) {
    // First validate everything except email
    const formDataObj = Object.fromEntries(formdata);

    // First validate on client side (optional but improves UX)
    if (formDataObj['Reg-Password'] !== formDataObj['Reg-confPass']) {
        alert("Passwords don't match!");
        return false;
    }

    // Calculate age from birthdate
    const birthDate = new Date(formDataObj['Reg-Birth']);
    const ageDiff = Date.now() - birthDate.getTime();
    const ageDate = new Date(ageDiff);
    const age = Math.abs(ageDate.getUTCFullYear() - 1970);

    if (age < 17) {
        alert("You need to be at least 17 to register");
        return false;
    }

    if (age > 110) {
        alert("Invalid age. Please check your birthdate");
        return false;
    }

    // First send data to server for validation (except email)
    fetch('login.php?type=PreRegisterValidation', {
        method: 'POST',
        body: formdata
    }).then(response => response.json())
        .then(data => {
            if (data.success) {
                // If all validations pass, store data and proceed to email verification
                tempUserData = formDataObj;
                verificationCode = Math.floor(100000 + Math.random() * 900000).toString();
                document.getElementById('verification-modal').style.display = 'flex';

                // Send verification email
                fetch('login.php?type=SendVerification', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        email: tempUserData['Reg-Email'],
                        code: verificationCode
                    })
                }).then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            alert('Failed to send verification email: ' + (data.message || 'Unknown error'));
                            document.getElementById('verification-modal').style.display = 'none';
                        }
                    });
            } else {
                alert(data.message || "Validation failed");
            }
        });

    return false;
}


function verifyCode() {
    const enteredCode = Array.from(document.querySelectorAll('.code-input'))
        .map(input => input.value)
        .join('');

    fetch('login.php?type=VerifyCode', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ code: enteredCode })
    }).then(response => response.json())
        .then(data => {
            if (data.success) {
                completeRegistration();
            } else {
                document.getElementById('verification-message').textContent = 'Invalid code. Please try again.';
            }
        });
}

function completeRegistration() {
    const formdata = new FormData();
    for (const key in tempUserData) {
        formdata.append(key, tempUserData[key]);
    }

    fetch('login.php?type=Register', {
        method: 'POST',
        body: formdata
    }).then(response => response.json())
        .then(data => {
            if (data.token && data.username) {
                localStorage.setItem('authToken', data.token);
                sessionStorage.setItem('type', data.type);
                sessionStorage.setItem('username', data.username);
                sessionStorage.setItem('userid', data.userid);
                sessionStorage.setItem('profilepic', data.pfp);
                window.location.replace('mainpage.html');
            } else {
                alert(data.message || 'Registration failed');
            }
            document.getElementById('verification-modal').style.display = 'none';
        });
}

function sendResetCode() {
    const email = document.getElementById('reset-email').value;
    const messageElement = document.getElementById('reset-message');

    if (!email.includes('@') || !email.includes('.')) {
        messageElement.textContent = 'Please enter a valid email';
        messageElement.style.color = 'red';
        return;
    }

    resetCode = Math.floor(100000 + Math.random() * 900000).toString();
    resetEmail = email;

    fetch('login.php?type=sendResetCode', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, code: resetCode })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('reset-code-section').style.display = 'block';
                messageElement.textContent = 'Code sent to your email!';
                messageElement.style.color = 'green';
            } else {
                messageElement.textContent = data.message || 'Error sending code';
                messageElement.style.color = 'red';
            }
        });
}

function verifyResetCode() {
    const enteredCode = Array.from(document.querySelectorAll('#forgot-password-modal .code-input'))
        .map(input => input.value)
        .join('');
    const newPassword = document.getElementById('new-password').value;
    const messageElement = document.getElementById('reset-message');

    if (enteredCode !== resetCode) {
        messageElement.textContent = 'Invalid verification code';
        messageElement.style.color = 'red';
        return;
    }

    if (newPassword.length < 6) {
        messageElement.textContent = 'Password must be at least 6 characters';
        messageElement.style.color = 'red';
        return;
    }

    fetch('login.php?type=resetPassword', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: resetEmail, newPassword })
    })
        .then(response => response.json())
        .then(data => {
            messageElement.textContent = data.message;
            messageElement.style.color = data.success ? 'green' : 'red';

            if (data.success) {
                setTimeout(() => {
                    closeForgotPassword();
                    document.getElementById('login').reset();
                }, 1500);
            }
        });
}