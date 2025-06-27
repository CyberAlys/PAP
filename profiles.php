<?php
include("conexion.php");
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

DEFINE("logpath", __DIR__ . "/logs/testlogs.log");

// Initialize response array
$response = ['success' => 'No', 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method");
    }

    $type = $_GET['type'] ?? '';
    $data = json_decode(file_get_contents("php://input"), true);


    switch ($type) {
        case "userposts":
            $response = getUserPosts($data);
            break;
        case "likedposts":
            $response = getUserLikedPosts($data);
            break;
        case "getuserinfo":
            $response = getuserinfo($data);
            break;
        case "follow":
            $response = followUser($data);
            break;
        case "unfollow":
            $response = unfollowUser($data);
            break;
        case "checkfollow":
            $response = checkFollow($data);
            break;
        case "profilepic":
            $response = updateProfilePic();
            break;
        case "username":
            $response = updateUsername();
            break;
        case "password":
            $response = updatePassword();
            break;
        default:
            throw new Exception("Invalid request type");
    }
} catch (Exception $e) {
    error_log("[" . date('Y-m-d H:i:s') . "] Error: " . $e->getMessage() . "\n", 3, logpath);
    $response = ['success' => 'No', 'message' => $e->getMessage()];
}

echo json_encode($response);
exit;

function getUserPosts($data) {
    global $liga;

    $response = ['success' => 'No', 'posts' => []];

    try {
        if (!$liga) {
            throw new Exception("DB connection failed");
        }

        $userid = $data['userid'] ?? null;

        $sql = "SELECT postid, userid, text, date, photoid 
                FROM post 
                WHERE userid = ? 
                ORDER BY date DESC";

        $stmt = $liga->prepare($sql);
        $stmt->bind_param("i", $userid);
        $stmt->execute();
        $result = $stmt->get_result();

        $posts = [];
        while ($row = $result->fetch_assoc()) {
            $post = processPostRow($row, $userid);
            if ($post) {
                $posts[] = $post;
            }
        }

        $response = ['success' => 'Yes', 'posts' => $posts];
    } catch (Exception $e) {
        error_log("[" . date('Y-m-d H:i:s') . "] getUserPosts() failed: " . $e->getMessage() . "\n", 3, logpath);
        $response['message'] = $e->getMessage();
    }

    return $response;
}

function getUserLikedPosts($data) {
    global $liga;
    $response = ['success' => 'No', 'posts' => []];

    try {
        if (!$liga) {
            throw new Exception("DB connection failed");
        }

        $userid = $data['userid'] ?? null;

        $sql = "SELECT post.postid, post.userid AS posterid, post.text, post.date, post.photoid
                FROM post
                INNER JOIN likes ON post.postid = likes.postid
                WHERE likes.userid = ?
                ORDER BY post.date DESC";

        $stmt = $liga->prepare($sql);
        $stmt->bind_param("i", $userid);
        $stmt->execute();
        $result = $stmt->get_result();

        $posts = [];
        while ($row = $result->fetch_assoc()) {
            $post = processPostRow($row, $row['posterid']);
            if ($post) {
                $posts[] = $post;
            }
        }

        $response = ['success' => 'Yes', 'posts' => $posts];
    } catch (Exception $e) {
        error_log("[" . date('Y-m-d H:i:s') . "] getUserLikedPosts() failed: " . $e->getMessage() . "\n", 3, logpath);
        $response['message'] = $e->getMessage();
    }

    return $response;
}

function processPostRow($row, $userid) {
    global $liga;

    try {
        // Get user info
        $stmt_user = $liga->prepare("SELECT username, profilepic FROM users WHERE userid = ?");
        $stmt_user->bind_param("i", $userid);
        $stmt_user->execute();
        $stmt_user->bind_result($username, $profilepic);
        $stmt_user->fetch();
        $stmt_user->close();

        $postData = [
            "postid" => $row['postid'],
            "userid" => $userid,
            "text" => $row['text'],
            "date" => $row['date'],
            "username" => $username,
            "profilepic" => $profilepic,
            "photo" => false
        ];

        if (!empty($row['photoid'])) {
            $stmt_photo = $liga->prepare("SELECT filepath FROM photos WHERE photoid = ?");
            $stmt_photo->bind_param("i", $row['photoid']);
            $stmt_photo->execute();
            $stmt_photo->bind_result($filepath);

            if ($stmt_photo->fetch()) {
                $postData["photo"] = true;
                $postData["path"] = $filepath;
            }
            $stmt_photo->close();
        }

        return $postData;
    } catch (Exception $e) {
        error_log("[" . date('Y-m-d H:i:s') . "] Error processing post: " . $e->getMessage() . "\n", 3, logpath);
        return null;
    }
}

function getuserinfo($data) {
    global $liga;
    $response = ['success' => 'No'];

    try {
        $userid = $data['userid'] ?? null;

        $stmt = $liga->prepare("SELECT username, profilepic FROM users WHERE userid = ?");
        $stmt->bind_param("i", $userid);
        $stmt->execute();
        $stmt->bind_result($username, $profilepic);

        if ($stmt->fetch()) {
            $stmt->close();

            // Get follower/following count
            $stmtFollowers = $liga->prepare("SELECT COUNT(*) FROM followers WHERE following_id = ?");
            $stmtFollowers->bind_param("i", $userid);
            $stmtFollowers->execute();
            $stmtFollowers->bind_result($followersCount);
            $stmtFollowers->fetch();
            $stmtFollowers->close();

            $stmtFollowing = $liga->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = ?");
            $stmtFollowing->bind_param("i", $userid);
            $stmtFollowing->execute();
            $stmtFollowing->bind_result($followingCount);
            $stmtFollowing->fetch();
            $stmtFollowing->close();

            $response = [
                "success" => "Yes",
                "username" => $username,
                "profilepic" => $profilepic,
                "followers" => $followersCount,
                "following" => $followingCount
            ];
        } else {
            throw new Exception("User not found");
        }
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }

    return $response;
}

function followUser($data) {
    global $liga;
    $response = ['success' => 'No'];

    try {
        $followerId = $data['followerId'] ?? null;
        $followedId = $data['followedId'] ?? null;

        if (!$followerId || !$followedId) {
            throw new Exception("Missing follower or followed ID");
        }

        // Check if follow relationship already exists
        $checkStmt = $liga->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = ? AND following_id = ?");
        $checkStmt->bind_param("ii", $followerId, $followedId);
        $checkStmt->execute();
        $checkStmt->bind_result($count);
        $checkStmt->fetch();
        $checkStmt->close();

        if ($count > 0) {
            return ['success' => 'Yes', 'isFollowing' => true];
        }

        // Insert new follow relationship
        $insertStmt = $liga->prepare("INSERT INTO followers (follower_id, following_id) VALUES (?, ?)");
        $insertStmt->bind_param("ii", $followerId, $followedId);
        $insertStmt->execute();

        if ($insertStmt->affected_rows > 0) {
            $response = [
                'success' => 'Yes',
                'isFollowing' => true,
                'message' => 'Successfully followed user'
            ];
        } else {
            throw new Exception("Failed to follow user");
        }

        $insertStmt->close();
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }

    return $response;
}

function unfollowUser($data) {
    global $liga;
    $response = ['success' => 'No'];

    try {
        $followerId = $data['followerId'] ?? null;
        $followedId = $data['followedId'] ?? null;

        if (!$followerId || !$followedId) {
            throw new Exception("Missing follower or followed ID");
        }

        $stmt = $liga->prepare("DELETE FROM followers WHERE follower_id = ? AND following_id = ?");
        $stmt->bind_param("ii", $followerId, $followedId);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $response = [
                'success' => 'Yes',
                'isFollowing' => false,
                'message' => 'Successfully unfollowed user'
            ];
        } else {
            throw new Exception("Failed to unfollow user");
        }

        $stmt->close();
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }

    return $response;
}

function checkFollow($data) {
    global $liga;
    $response = ['success' => 'No'];

    try {
        $followerId = $data['followerId'] ?? null;
        $followedId = $data['followedId'] ?? null;

        if (!$followerId || !$followedId) {
            throw new Exception("Missing follower or followed ID");
        }

        $stmt = $liga->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = ? AND following_id = ?");
        $stmt->bind_param("ii", $followerId, $followedId);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        $response = [
            'success' => 'Yes',
            'isFollowing' => $count > 0
        ];
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }

    return $response;
}


function updateProfilePic() {
    global $liga;

    // Validate input
    if (empty($_FILES['profilepic']) || !isset($_POST['userid'])) {
        throw new Exception("Missing file or user ID");
    }

    $userid = (int)$_POST['userid'];
    $file = $_FILES['profilepic'];

    // Validate file type
    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!array_key_exists($mime, $allowedTypes)) {
        throw new Exception("Unsupported file type. Please upload a JPEG, PNG, or WebP image.");
    }

    // Validate file size (max 2MB)
    if ($file['size'] > 2 * 1024 * 1024) {
        throw new Exception("File size exceeds 2MB limit");
    }

    // Create upload directory if it doesn't exist
    $uploadDir = __DIR__ . '/photos/profile_pictures/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Generate unique filename
    $extension = $allowedTypes[$mime];
    $filename = 'profile_' . $userid . '_' . time() . '.' . $extension;
    $destination = $uploadDir . $filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new Exception("Failed to save uploaded file");
    }

    // Update database
    $dbPath = 'photos/profile_pictures/' . $filename;
    $stmt = $liga->prepare("UPDATE users SET profilepic = ? WHERE userid = ?");
    $stmt->bind_param("si", $dbPath, $userid);

    if (!$stmt->execute()) {
        // Delete the uploaded file if DB update fails
        unlink($destination);
        throw new Exception("Failed to update profile picture in database");
    }

    $stmt->close();

    return [
        'success' => 'Yes',
        'message' => 'Profile picture updated successfully',
        'profilepic' => $dbPath,
        'timestamp' => time()];
}

function updateUsername()
{
    global $liga;
    $input = json_decode(file_get_contents("php://input"), true);
    $userid      = (int)($input['userid']       ?? 0);
    $newusername = trim($input['newusername']   ?? "");

    if (!$userid || !$newusername) {
        return ["success" => "No", "message" => "Missing data"];
    }

    // Check if unique
    $stmt = $liga->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND userid <> ?");
    $stmt->bind_param("si", $newusername, $userid);
    $stmt->execute();
    $stmt->bind_result($exists);
    $stmt->fetch();
    $stmt->close();

    if ($exists) {
        return ["success" => "No", "message" => "Username already in use"];
    }

    // Update querry
    $stmt = $liga->prepare("UPDATE users SET username = ? WHERE userid = ?");
    $stmt->bind_param("si", $newusername, $userid);
    $stmt->execute();
    $stmt->close();

    return ["success" => "Yes",
        "message" => "Username changed",
        "username" => $newusername,
        'timestamp' => time()];
}

function updatePassword()
{
    global $liga;
    $input = json_decode(file_get_contents("php://input"), true);
    $userid       = (int)($input['userid']        ?? 0);
    $oldpassword  = $input['oldpassword']         ?? "";
    $newpassword  = $input['newpassword']         ?? "";

    if (!$userid || !$oldpassword || !$newpassword) {
        return ["success" => "No", "message" => "Missing data"];
    }

    // Fetch current hashed password
    $stmt = $liga->prepare("SELECT password FROM users WHERE userid = ?");
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $stmt->bind_result($hash);
    if (!$stmt->fetch()) {
        $stmt->close();
        return ["success" => "No", "message" => "User not found"];
    }
    $stmt->close();

    // Verify and update
    if (!password_verify($oldpassword, $hash)) {
        return ["success" => "No", "message" => "Old password incorrect"];
    }

    $newHash = password_hash($newpassword, PASSWORD_DEFAULT);
    $stmt = $liga->prepare("UPDATE users SET password = ? WHERE userid = ?");
    $stmt->bind_param("si", $newHash, $userid);
    $stmt->execute();
    $stmt->close();

    return ["success" => "Yes", "message" => "Password changed"];
}

