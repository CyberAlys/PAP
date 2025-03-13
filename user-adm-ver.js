function checklogin()
{
    var authtoken = localStorage.getItem('authToken');
    var usertoken= sessionStorage.getItem('username');

    if(!usertoken && authtoken)
    {
        //if user false and auth true
        //user has logged in once but needs to re-login
    }
    if(!usertoken && !authtoken)
    {
        //if user and auth are both false
        //user closed page and the authtoken is destroyed
    }
}

function checkadm()
{

}