<?php
    include("conexion.php");

    //it suppresses errors from being displayed on the screen but error_reporting being on E_All ensures that errors are still detected
    ini_set('display_errors', 0);
    error_reporting(E_ALL);

    //defines the header it says that the response should be intreperted as JSON data
    header('Content-Type: application/json');

    //checks to see the type of operation (login or register)
    if($_SERVER['REQUEST_METHOD'] === 'POST')
    {
        try
        {
            $type = $_GET['type'];
            if($type == 'register')
            {
                register();
            }
            elseif ($type == 'login')
            {
                login();
            }
            else
            {
                echo json_encode(["message" => "Invalid Action type"]);
            }
        }
        catch (Exception $e)
        {
            echo json_encode(["message" => "Server Error", "Error"]);
        }
    }

    //creates and returns the tokens
    function generatetoken($userId)
    {
        //random number generator that generates 32 bytes then converts them to hexadecimal
        $random = bin2hex(random_bytes(32));
        //saves the Unix time (number of seconds since Jan 1 1970)
        $timestamp = time();
        //creates the token which uses the
        $token = hash('sha256', $random . $userId . $timestamp);
        return $token;
    }

    //checks if the usernames already exists
    function UsernameExists($username)
    {
        global $liga;

        //query to check if a user with that username exists, use alias to make it easier to call on code
        $sql = "Select COUNT(*) as count from users where username = ?";
        //Replaces the "?" with $username
        $stmt = $liga->prepare($sql);
        $stmt-> bind_param("s", $username);

        //execute query
        $stmt -> execute();

        //get the results
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        //return true if the username exists
        return $row['count'] > 0;
    }

    //basically the same check as the username but for the email
    function EmailExists($email)
    {
        global $liga;

        //query to check if a user with that email exists, use alias to make it easier to call on code
        $sql = "Select COUNT(*) as count from users where email = ?";
        //Replaces the "?" with $username
        $stmt = $liga->prepare($sql);
        $stmt-> bind_param("s", $email);

        //execute the query
        $stmt -> execute();

        //gets the results
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        //return true if the email exists
        return $row['count'] > 0;
    }

    //registers the user
     function register()
    {
        //grab $liga from connection.php
        global $liga;

        //define user_type
        $user_type = "user";

        //grab info from form
        $username = $_POST['Reg-Username'];
        $password= $_POST['Reg-Password'];
        $confpass = $_POST['Reg-confPass'];
        $name = $_POST['Reg-Name'];
        $email = $_POST['Reg-Email'];
        $birthday = $_POST['Reg-Birth'];

        //calculate the age
        $birthdate = new DateTime($birthday);
        $today = new DateTime();
        $age = $today->diff($birthdate)->y;


        //verifications

        //username
        if(UsernameExists($username))
        {
            echo json_encode(["message" => "This Username is already in use."]);
            return;
        }

        //email
        if (EmailExists($email))
        {
            echo json_encode(["message" => "This Email is already in use."]);
            return;
        }

        //age
        if($age < 17)
        {
            echo json_encode(["message" => "You need to be at least 17 to create an account."]);
            return;
        }

        if ($age > 110)
        {
            echo json_encode(["message" => "Invalid Age, Try verifying the inserted birthdate"]);
            return;
        }

        //password
        if ($password != $confpass)
        {
            echo json_encode(["message" => "The password and it's confirmation don't match"]);
            return;
        }

        //Password encryption
        $hashpassword = password_hash($password, PASSWORD_BCRYPT);

        //Default pfp, user will be able to change it in their personal settings
        $profilepic = "photos/profile_pictures/Default_pfp.jpg";

        //query to insert and the statement that replaces the "?"'s
        $sql = "INSERT INTO `users` (`userid`, `user_type`, `username`, `password`, `AdmPin`, `profilepic`, `name`, `email`, `birthday`) 
        VALUES (NULL, ?, ?, ?, NULL, ?, ?, ?, ?);";
        $stmt =$liga->prepare($sql);
        $stmt-> bind_param( "sssssss",$user_type, $username,
            $hashpassword, $name, $email, $birthday, $profilepic );

        if ($stmt->execute())
        {
            //if the query is sucessfull it will extract the necessary information to create the tokens in js
            $userid = $stmt->insert_id;
            $token = generatetoken($userid);

            echo json_encode([
                "message" => "Account created successfully",
                "token" => $token,
                "type" => $user_type,
                "username" => $username,
                "userid" => $userid
            ]);
        }
        else
        {
            echo json_encode(["message" => "Error creating account", "Error"=> $stmt->error]);
        }
    }


    //login function (similar to register so fewer details)
    function login()
    {
        global $liga;


        //grab info from js
        $username = $_POST['log-user'];
        $password  = $_POST['log-password'];

        //query stuff
        $sql = "SELECT * FROM users WHERE username = ?";

        $stmt = $liga -> prepare($sql);
        $stmt ->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        //if match for username is found
        if($result -> num_rows > 0)
        {
            $user = $result->fetch_assoc();
            //password_verify checks if the password inputted by the user matches with the hash that the DB provides
            if(password_verify($password, $user['password']))
            {
                $token = generatetoken($user['userid']);

                //define user type
                $user_type = $user['user_type'];

                echo json_encode([
                    "message" => "Logged in successfully",
                    "token" => $token,
                    "type" => $user_type,
                    "username" => $user['username'],
                    "userid" => $user['userid']

                ]);
            }
            else
            {
                echo json_encode(["message" => "Wrong Password"]);
            }
        }
        else
        {
            echo json_encode(["message" => "User not found"]);
        }
    }
