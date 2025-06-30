<?php
    include("conexion.php");

    //it suppresses errors from being displayed on the screen but error_reporting being on E_All ensures that errors are still detected
    ini_set('display_errors', 0);
    error_reporting(E_ALL);

    //defines the header it says that the response should be intreperted as JSON data
    header('Content-Type: application/json');

    //checks to see the type of operation (login or register or other operations)
    if($_SERVER['REQUEST_METHOD'] === 'POST')
    {
        try
        {
            $type = $_GET['type'] ?? '';

            switch ($type) {
                case 'Register':
                    register();
                    break;
                case 'Login':
                    login();
                    break;
                case 'Relogin':
                    relogin();
                    break;
                case 'FindUser':
                    findUser();
                    break;
                case 'PreRegisterValidation':
                    preRegisterValidation();
                    break;
                case 'SendVerification':
                    sendVerificationEmail();
                    break;
                case 'VerifyCode':
                    verifyEmailCode();
                    break;
                case 'sendResetCode':
                    sendResetCode();
                    break;
                case 'resetPassword':
                    resetPassword();
                    break;
                default:
                    echo json_encode(["message" => "Invalid Action type"]);
                    break;
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
function register() {
    global $liga;

    session_start();
    if (!isset($_SESSION['verified_email']) || $_SESSION['verified_email'] !== $_POST['Reg-Email']) {
        echo json_encode(["message" => "Email not verified"]);
        return;
    }

    if (EmailExists($_POST['Reg-Email'])) {
        echo json_encode(["message" => "This Email is already in use."]);
        return;
    }

    // Rest of your existing register function...
    $user_type = "user";
    $username = $_POST['Reg-Username'];
    $password = $_POST['Reg-Password'];
    $name = $_POST['Reg-Name'];
    $email = $_POST['Reg-Email'];
    $birthday = $_POST['Reg-Birth'];
    $hashpassword = password_hash($password, PASSWORD_BCRYPT);
    $profilepic = "photos/profile_pictures/Default_pfp.jpg";

    //SQL query
    $sql = "INSERT INTO `users` (`userid`, `user_type`, `username`, `password`, `AdmPin`, `profilepic`, `name`, `email`, `birthday`, `email_verified`) 
            VALUES (NULL, ?, ?, ?, NULL, ?, ?, ?, ?, TRUE)";

    $stmt = $liga->prepare($sql);
    $stmt->bind_param("sssssss", $user_type, $username, $hashpassword, $profilepic, $name, $email, $birthday);

    if ($stmt->execute()) {
        $userid = $stmt->insert_id;
        $token = generatetoken($userid);

        $sqltoken = "INSERT INTO `usertoken` (`userid`, `token`) VALUES (?, ?)";
        $stmttoken = $liga->prepare($sqltoken);
        $stmttoken->bind_param("ss", $userid, $token);

        if ($stmttoken->execute()) {
            // Clear verified email from session
            unset($_SESSION['verified_email']);

            echo json_encode([
                "message" => "Account created successfully",
                "token" => $token,
                "type" => $user_type,
                "username" => $username,
                "userid" => $userid,
                "pfp" => $profilepic
            ]);
        } else {
            echo json_encode(["message" => "Error creating Tokens", "Error" => $stmttoken->error]);
        }
    } else {
        echo json_encode(["message" => "Error creating account", "Error" => $stmt->error]);
    }
}

    //login function (similar to register so fewer details on comments)
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

                //change token n shit
                $sqltoken = "UPDATE `usertoken` SET `token` = ? WHERE `usertoken`.`userid` = ?";
                $stmttoken = $liga->prepare($sqltoken);
                $stmttoken-> bind_param("ss", $token , $user['userid']);

                if ($stmttoken->execute())
                {
                    echo json_encode([
                        "message" => "Logged in successfully",
                        "token" => $token,
                        "type" => $user_type,
                        "username" => $user['username'],
                        "userid" => $user['userid'],
                        "pfp" => $user['profilepic']

                    ]);
                }
                else
                {
                    echo json_encode(["message" => "Error creating Tokens", "Error" => $stmttoken->error]);
                }


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


    //find the username through the token in local storage
    function finduser()
    {
        global $liga;

        $token = file_get_contents("php://input");

        $query = "SELECT users.username FROM users INNER JOIN usertoken on usertoken.userid = users.userid WHERE usertoken.token = ?";
        $stmt = $liga -> prepare($query);
        $stmt -> bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result -> num_rows > 0)
        {
            $user = $result->fetch_assoc();

            echo json_encode([
                "message" => "Got username sucessfully",
                "token" => $token,
                "username" => $user['username']

            ]);

        }
        else
        {
             echo json_encode(["message" => "User not Found"]);
        }

    }

    function relogin()
    {
        global $liga;

        $input = json_decode(file_get_contents('php://input'), true);
        $username = $input['user'];
        $pass = $input['pass'];

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
            if(password_verify($pass, $user['password']))
            {
                $token = generatetoken($user['userid']);

                //define user type
                $user_type = $user['user_type'];

                //change the tokens
                $sqltoken = "UPDATE `usertoken` SET `token` = ? WHERE `usertoken`.`userid` = ?";
                $stmttoken = $liga->prepare($sqltoken);
                $stmttoken-> bind_param("ss", $token , $user['userid']);

                if ($stmttoken->execute())
                {
                    echo json_encode([
                        "message" => "Logged in successfully",
                        "token" => $token,
                        "type" => $user_type,
                        "username" => $user['username'],
                        "userid" => $user['userid'],
                        "pfp" => $user['profilepic']

                    ]);
                }
                else
                {
                    echo json_encode(["message" => "Error creating Tokens", "Error" => $stmttoken->error]);
                }


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

function sendVerificationEmail() {
    require 'vendor/autoload.php'; // Require PHPMailer

    session_start();

    $data = json_decode(file_get_contents('php://input'), true);
    $email = $data['email'];
    $code = $data['code'];

    // Store in session
    $_SESSION['verification_code'] = $code;
    $_SESSION['verification_email'] = $email;

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'the.photographerpap@gmail.com';
        $mail->Password = 'bbyp zddi irkb hurc';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('no-reply@yourdomain.com', 'The Photographer');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your Verification Code';
        $mail->Body = "Your verification code is: <b>$code</b>";
        $mail->AltBody = "Your verification code is: $code";

        $mail->send();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function verifyEmailCode() {
    session_start();
    $data = json_decode(file_get_contents('php://input'), true);
    $enteredCode = $data['code'];

    if ($_SESSION['verification_code'] === $enteredCode) {
        $_SESSION['verified_email'] = $_SESSION['verification_email'];
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
}

function preRegisterValidation() {
    global $liga;

    // Get form data
    $username = $_POST['Reg-Username'];
    $password = $_POST['Reg-Password'];
    $confpass = $_POST['Reg-confPass'];
    $name = $_POST['Reg-Name'];
    $birthday = $_POST['Reg-Birth'];

    // Validations
    if (UsernameExists($username)) {
        echo json_encode(["success" => false, "message" => "This Username is already in use."]);
        return;
    }

    // Calculate age
    $birthdate = new DateTime($birthday);
    $today = new DateTime();
    $age = $today->diff($birthdate)->y;

    if ($age < 17) {
        echo json_encode(["success" => false, "message" => "You need to be at least 17 to create an account."]);
        return;
    }

    if ($age > 110) {
        echo json_encode(["success" => false, "message" => "Invalid Age, Try verifying the inserted birthdate"]);
        return;
    }

    if ($password != $confpass) {
        echo json_encode(["success" => false, "message" => "The password and its confirmation don't match"]);
        return;
    }

    // If all validations pass
    echo json_encode(["success" => true]);
}

function sendResetCode() {
    global $liga;
    session_start();

    $data = json_decode(file_get_contents('php://input'), true);
    $email = $data['email'];
    $code = $data['code'];

    // Verify email exists
    $stmt = $liga->prepare("SELECT userid FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    if ($stmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Email not found']);
        return;
    }

    // Store code in session
    $_SESSION['reset_code'] = $code;
    $_SESSION['reset_email'] = $email;

    // Send email (using your existing PHPMailer setup)
    require 'vendor/autoload.php';
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'the.photographerpap@gmail.com';
        $mail->Password = 'bbyp zddi irkb hurc';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('no-reply@yourdomain.com', 'The Photographer');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->addAddress($email);
        $mail->Subject = 'Password Reset Code';
        $mail->Body = "Your password reset code is: <b>$code</b>";

        $mail->send();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to send email']);
    }
}
function resetPassword() {
    global $liga;
    session_start();

    $data = json_decode(file_get_contents('php://input'), true);
    $email = $data['email'];
    $newPassword = $data['newPassword'];
    $code = $data['code']; // <- added

    // Verify session and code
    if (
        !isset($_SESSION['reset_email'], $_SESSION['reset_code']) ||
        $_SESSION['reset_email'] !== $email ||
        $_SESSION['reset_code'] !== $code
    ) {
        echo json_encode(['success' => false, 'message' => 'Invalid reset code or session']);
        return;
    }

    // Update password
    $hashpassword = password_hash($newPassword, PASSWORD_BCRYPT);
    $stmt = $liga->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->bind_param("ss", $hashpassword, $email);

    if ($stmt->execute()) {
        // Clear reset session
        unset($_SESSION['reset_code']);
        unset($_SESSION['reset_email']);

        echo json_encode(['success' => true, 'message' => 'Password updated successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating password']);
    }
}
