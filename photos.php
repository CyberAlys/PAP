<?php
include("conexion.php");
ini_set('display_errors', 1);

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
        $type = $_GET['type'];
        if($type == 'saveinserver')
        {
            saveinserver();
        }
        elseif ($type == 'getphoto')
        {
            getphoto();
        }
        elseif ($type == 'Relogin')
        {
            relogin();
        }
        elseif ($type == 'FindUser')
        {
            findUser();
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

function saveinserver()
{
    //grab $liga from connection.php
    global $liga;


    //grab info from form
    $input = json_decode(file_get_contents('php://input'), true);
    $user = $input['usrid'];
    $title = $input['photoTitle'];
    $photo = $input['photo'];
    $description = $input['description'];
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

        //takes some characters out of the title that could be dangerous
        $safeTitle = preg_replace('/[^a-zA-Z0-9_-]/', '_', $title);
        $uploadDate = date('Ymd');
        $filename = "{$user}_{$uploadDate}_{$safeTitle}.{$ext}";

        $uploadPath = __DIR__ . '/photos/images/' . $filename;
        file_put_contents($uploadPath, $photoData);

        $sqlpath = '/photos/images/' . $filename;
    }
    else {
    throw new Exception('Did not receive a valid base64 image');}

    //query to insert and the statement that replaces the "?"'s
    $sql = "INSERT INTO `photos` (`photoid`, `userid`, `filepath`, `width`, `height`, `filetype`, `title`, `description`, `uploaddate`) 
        VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?);";
    $stmt =$liga->prepare($sql);
    $stmt-> bind_param( "ssssssss",$user, $sqlpath, $width, $height, $ext, $safeTitle, $description, $uploadDate );

    if ($stmt->execute())
    {

            echo json_encode([
                "message" => "Image uploaded successfully",
                "success" => "Yes",
                ]);

    }
    else
    {
        echo json_encode(["message" => "Error uploading Image", "Error"=> $stmt->error]);
    }
}

function getphoto() {
    global $liga;

    $input = json_decode(file_get_contents("php://input"), true);
    $id = $input['photoid'] ?? null;

    if (!$id || !is_numeric($id)) {
        echo json_encode(["success" => "No", "message" => "Invalid photo ID"]);
        return;
    }

    if (!$id) {
        echo json_encode(["success" => "No", "message" => "No photo ID provided"]);
        return;
    }

    $sql = "SELECT filepath, title, description, width, height FROM photos WHERE photoid = ?";
    $stmt = $liga->prepare($sql);
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $stmt->bind_result($path, $title, $description, $width, $height);

    if ($stmt->fetch()) {
        echo json_encode([
            "success" => "Yes",
            "path" => $path,
            "title" => $title,
            "description" => $description,
            "width" => $width,
            "height" => $height
        ]);
    } else {
        echo json_encode(["success" => "No", "message" => "Photo not found"]);
    }

    $stmt->close();
}