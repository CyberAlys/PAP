<?php
include("conexion.php");
ini_set('display_errors', 1);
error_reporting(E_ALL);

//defines the header it says that the response should be intreperted as JSON data
header('Content-Type: application/json');

DEFINE("logpath" ,   __DIR__ . "/logs/testlogs.log");

//checks to see the type of operation (login or register or other operations)
if($_SERVER['REQUEST_METHOD'] === 'POST')
{
    try
    {

        $type = $_GET['type'];

        error_log("type selector called with: " ."$type" ."\n", 3, logpath);

        if($type == 'saveinserver')
        {
            saveinserver();
        }
        elseif ($type == 'getphoto')
        {
            getphoto();
        }
        elseif ($type == 'getfeed')
        {
            getfeed();
        }
//        elseif ($type == 'FindUser')
//        {
//           // findUser();
//        }
        else
        {
            echo json_encode(["message" => "Invalid Action type"]);
        }
    }
    catch (Exception $e)
    {
        echo json_encode(["message" => $e->getMessage()]);
    }
}


//saves the post in the server storage and in the database
function saveinserver()
{
    error_log("saveinserver() called " ."\n", 3, logpath);

    try {

    //grab $liga from connection.php
    global $liga;

    //grab info from form
    $input = json_decode(file_get_contents('php://input'), true);
    $user = $input['usrid'];
    $text = $input['text'];

    $postdate = date('Y-m-d H:i:s');

    if($input['istherephoto'] == true) {

        $photo = $input['photo'];
        $width = $input['imagewidth'];
        $height = $input['imageheight'];

        //check if $photo is actually an image and put the image into $photo and the type into $type
        if (preg_match('/^data:image\/(\w+);base64,/', $photo, $type)) {

            //removes the first part of the string (data:image/png;base64,) so $photoData keeps only the raw data
            $photoData = substr($photo, strpos($photo, ',') + 1);
            //decodes the data
            $photoData = base64_decode($photoData);

            //decode verification
            if ($photoData === false) {
                throw new Exception('Base64 decode failed');
            }

            //checks if image is valid
            $imageInfo = @getimagesizefromstring($photoData);
            if ($imageInfo === false) {
                throw new Exception('Uploaded data is not a valid image');
            }

            //get the extension from the array
            $ext = strtolower($type[1]);

            //check if the extension is valid
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                throw new Exception('Invalid image type');
            }

            $uploadDate = date('Ymd-his');
            $filename = "{$user}_   {$uploadDate}.{$ext}";

            $uploadPath = __DIR__ . '/photos/images/' . $filename;
            file_put_contents($uploadPath, $photoData);

            $sqlpath = '/photos/images/' . $filename;

        } else {
            throw new Exception('Did not receive a valid base64 image');
        }

        //query to insert and the statement that replaces the "?"'s
        $sql = "INSERT INTO `photos` (`photoid`, `filepath`, `width`, `height`, `filetype`) 
        VALUES (NULL, ?, ?, ?, ?);";
        $stmt = $liga->prepare($sql);
        $stmt->bind_param("ssss",  $sqlpath, $width, $height, $ext);

        if ($stmt->execute()) {

            $photoid = $stmt->insert_id;

            $postsql = "INSERT INTO `post` (`postid`, `userid`, `text`, `date`, `photoid`) 
            VALUES (NULL, ?, ?, ?, ?);";
            $poststmt = $liga->prepare($postsql);
            $poststmt->bind_param("sssi",  $user, $text, $postdate, $photoid);

            if (!$poststmt->execute()) {
                echo json_encode(["message" => "Error uploading Post", "Error" => $poststmt->error]);
                return;
            }


        } else {
            echo json_encode(["message" => "Error uploading Image", "Error" => $stmt->error]);
            return;
        }
    }
    else {
        $postsql = "INSERT INTO `post` (`postid`, `userid`, `text`, `date`, `photoid`) 
         VALUES (NULL, ?, ?, ?, NULL );";
        $poststmt = $liga->prepare($postsql);
        $poststmt->bind_param("sss", $user, $text, $postdate);

        if (!$poststmt->execute()) {
            echo json_encode(["message" => "Error uploading Post", "Error" => $poststmt->error]);
            return;

        }
    }
        echo json_encode([
            "message" => "Post uploaded successfully",
            "success" => "Yes",
        ]);

    }
    catch (Exception $e)
    {
        echo json_encode(["message" => $e->getMessage()]);
    }

}


//grabs the posts
function getphoto()
{
    error_log("getphoto() called " ."\n", 3, logpath);


    try {
        global $liga;

        $input = json_decode(file_get_contents("php://input"), true);
        $id = $input['postid'] ?? null;

        if (!$id || !is_numeric($id)) {
            echo json_encode(["success" => "No", "message" => "Invalid photo ID"]);
            return;
        }

        //stuff for the post info
        $sql_post = "SELECT userid, text, date, photoid FROM post WHERE postid = ?";
        $stmt_post = $liga->prepare($sql_post);
        $stmt_post->bind_param("s", $id);
        $stmt_post->execute();
        $stmt_post->bind_result($userid, $text, $date, $photoid);

        if (!$stmt_post->fetch()) {
            echo json_encode(["success" => "No", "message" => "Post not found"]);
            $stmt_post->close();
            return;
        }
        $stmt_post->close();

        // Get user info
        $sql_user = "SELECT username, profilepic FROM users WHERE userid = ?";
        $stmt_user = $liga->prepare($sql_user);
        $stmt_user->bind_param("s", $userid);
        $stmt_user->execute();
        $stmt_user->bind_result($username, $profilepic);

        if (!$stmt_user->fetch()) {
            echo json_encode(["success" => "No", "message" => "User not found"]);
            $stmt_user->close();
            return;
        }
        $stmt_user->close();

        // No photo
        if (is_null($photoid)) {
            echo json_encode([
                "success" => "Yes",
                "photo" => false,
                "text" => $text,
                "date" => $date,
                "username" => $username,
                "profilepic" => $profilepic
            ]);
            return;
        }

        // Get photo info
        $sql_photo = "SELECT filepath, width, height FROM photos WHERE photoid = ?";
        $stmt_photo = $liga->prepare($sql_photo);
        $stmt_photo->bind_param("i", $photoid);
        $stmt_photo->execute();
        $stmt_photo->bind_result($path, $width, $height);

        if ($stmt_photo->fetch()) {
            echo json_encode([
                "success" => "Yes",
                "photo" => true,
                "path" => $path,
                "text" => $text,
                "date" => $date,
                "width" => $width,
                "height" => $height,
                "username" => $username,
                "profilepic" => $profilepic
            ]);
        } else {
            echo json_encode(["success" => "No", "message" => "Photo not found"]);
        }

        $stmt_photo->close();

    } catch (Exception $e) {
        echo json_encode(["message" => $e->getMessage()]);
    }
}


//gets multiple posts to create the feed
function getfeed()
{
    error_log("getfeed() called" . "\n", 3, logpath);
    global $liga;

    if (!$liga) {
        die(json_encode(["success" => "No", "message" => "DB connection failed"]));
    }

    try {
        $sql = "SELECT postid, userid, text, date, photoid FROM post ORDER BY date DESC LIMIT 20";
        $stmt = $liga->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();

        $posts = [];

        while ($row = $result->fetch_assoc()) {
            $postid = $row['postid'];
            $userid = $row['userid'];
            $text = $row['text'];
            $date = $row['date'];
            $photoid = $row['photoid'];

            // Get user info
            $sql_user = "SELECT username, profilepic FROM users WHERE userid = ?";
            $stmt_user = $liga->prepare($sql_user);
            $stmt_user->bind_param("s", $userid);
            $stmt_user->execute();
            $stmt_user->bind_result($username, $profilepic);
            $stmt_user->fetch();
            $stmt_user->close();

            $postData = [
                "postid" => $postid,
                "userid" => $userid,
                "text" => $text,
                "date" => $date,
                "username" => $username,
                "profilepic" => $profilepic,
                "photo" => false
            ];

            if (!is_null($photoid)) {
                $sql_photo = "SELECT filepath, width, height FROM photos WHERE photoid = ?";
                $stmt_photo = $liga->prepare($sql_photo);
                $stmt_photo->bind_param("i", $photoid);
                $stmt_photo->execute();
                $stmt_photo->bind_result($filepath, $width, $height);

                if ($stmt_photo->fetch()) {
                    $postData["photo"] = true;
                    $postData["path"] = $filepath;
                    $postData["width"] = $width;
                    $postData["height"] = $height;
                }

                $stmt_photo->close();
            }

            $posts[] = $postData;
        }

        $stmt->close();

        echo json_encode([
            "success" => "Yes",
            "posts" => $posts
        ]);

    } catch (Exception $e) {
        echo json_encode([
            "success" => "No",
            "message" => $e->getMessage()
        ]);
    }
}
