
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

    function showregiser()
    {
        document.getElementById('register').style.display =  'block';
        document.getElementById('switchtologin').style.display = 'block';
        document.getElementById('login').style.display =  'none';
        document.getElementById('switchtoregister').style.display = 'none';
    }

    function showlogin()
    {
        document.getElementById('login').style.display =  'block';
        document.getElementById('switchtoregister').style.display = 'block';
        document.getElementById('register').style.display =  'none';
        document.getElementById('switchtologin').style.display = 'none';
    }

    // function termsandconditions()
    // {
    //
    // }

function RegisterPHP()
{

}

