<?php
include("conexion.php");
ini_set('display_errors', 1);
error_reporting(E_ALL);

//defines the header it says that the response should be intreperted as JSON data
header('Content-Type: application/json');

DEFINE("logpath" ,   __DIR__ . "/logs/testlogs.log");

//checks to see the type of operation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $type = $_GET['type'] ?? '';

        error_log("[" . date('Y-m-d H:i:s') . "] type selector called with: " . $type . "\n", 3, logpath);

        switch ($type) {
            case 'saveinserver':
                saveinserver();
                break;
            case 'getphoto':
                getphoto();
                break;
            case 'getfeed':
                getfeed();
                break;
            case 'changelike':
                changelike();
                break;
            case 'insertcomment':
                insertcomment();
                break;
            case 'getcomments':
                getcomments();
                break;
            case 'deletepost':
                deletepost();
                break;
            case 'editpost':
                editpost();
                break;
            case 'deletecomment':
                deleteComment();
                break;
            case 'search':
                search();
                break;
            default:
                echo json_encode(["message" => "Invalid Action type"]);
        }
    } catch (Exception $e) {
        echo json_encode(["message" => $e->getMessage()]);
    }
}


//saves the post in the server storage and in the database
function saveinserver()
{
    error_log("[" . date('Y-m-d H:i:s') . "] saveinserver() called" . "\n", 3, logpath);

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
            $filename = "{$user}_{$uploadDate}.{$ext}";

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
        error_log("[" . date('Y-m-d H:i:s') . "] saveinserver() succeeded" . "\n", 3, logpath);
    }
    catch (Exception $e)
    {
        error_log("[" . date('Y-m-d H:i:s') . "] saveinserver() failed" . "\n", 3, logpath);
        echo json_encode(["message" => $e->getMessage()]);
    }

}


//grabs the posts
function getphoto()
{
    error_log("[" . date('Y-m-d H:i:s') . "] getphoto() called" . "\n", 3, logpath);


    try {
        global $liga;

        $input = json_decode(file_get_contents("php://input"), true);
        $id = $input['postid'] ?? null;
        $viwer = $input['viewerid'] ?? null;

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

        //get like number
        $sql_like = " SELECT COUNT(*) AS total_likes,
                MAX(CASE WHEN userid = ? THEN 1 ELSE 0 END) AS user_liked
                FROM likes WHERE postid = ?";

        $stmt_like = $liga->prepare($sql_like);
        $stmt_like->bind_param("ii", $viwer, $id);
        $stmt_like->execute();
        $stmt_like->bind_result($Likenumber, $viwerlike);

        if (!$stmt_like->fetch()) {
            echo json_encode(["success" => "No", "message" => "like not found"]);
            $stmt_like->close();
            return;
        }
        $stmt_like->close();

        // No photo
        if (is_null($photoid)) {
            echo json_encode([
                "success" => "Yes",
                "photo" => false,
                "text" => $text,
                "date" => $date,
                "username" => $username,
                "userid" => $userid,
                "profilepic" => $profilepic,
                "likenumber" => $Likenumber,
                "viwerlike" => $viwerlike
            ]);
            error_log("[" . date('Y-m-d H:i:s') . "] getphoto() no photo succeeded" . "\n", 3, logpath);
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
                "userid" => $userid,
                "profilepic" => $profilepic,
                "likenumber" => $Likenumber,
                "viwerlike" => $viwerlike
            ]);
            error_log("[" . date('Y-m-d H:i:s') . "] getphoto() with photo succeeded" . "\n", 3, logpath);
        } else {
            echo json_encode(["success" => "No", "message" => "Photo not found"]);
        }

        $stmt_photo->close();


    } catch (Exception $e) {
        error_log("[" . date('Y-m-d H:i:s') . "] getphoto() failed" . "\n", 3, logpath);
        echo json_encode(["message" => $e->getMessage()]);
    }
}


//gets multiple posts to create the feed
function getfeed()
{
    error_log("[" . date('Y-m-d H:i:s') . "] getfeed() called" . "\n", 3, logpath);

    global $liga;

    if (!$liga) {
        die(json_encode(["success" => "No", "message" => "DB connection failed"]));
    }

    try {
        $input = json_decode(file_get_contents("php://input"), true);
        $page = isset($input['page']) ? (int)$input['page'] : 1;
        $limit = 15; //number of posts per page
        $offset = ($page - 1) * $limit;

        $sort = $input['sort'] ?? 'date_desc';

        switch ($sort) {
            case 'date_asc':
                $orderBy = "date ASC";
                break;
            case 'likes_desc':
                $orderBy = "(SELECT COUNT(*) FROM likes WHERE likes.postid = post.postid) DESC";
                break;
            case 'likes_asc':
                $orderBy = "(SELECT COUNT(*) FROM likes WHERE likes.postid = post.postid) ASC";
                break;
            default:
                $orderBy = "date DESC";
        }


        $sql = "SELECT postid, userid, text, date, photoid
        FROM post
        ORDER BY $orderBy
        LIMIT ? OFFSET ?";
        $stmt = $liga->prepare($sql);
        $stmt->bind_param("ii", $limit, $offset);

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
            $stmt_user->bind_param("i", $userid);
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

        error_log("[" . date('Y-m-d H:i:s') . "] getfeed() succeeded" . "\n", 3, logpath);

    } catch (Exception $e) {
        error_log("[" . date('Y-m-d H:i:s') . "] getfeed() failled" . "\n", 3, logpath);
        echo json_encode([
            "success" => "No",
            "message" => $e->getMessage()
        ]);
    }
}

function changelike()
{
    global $liga;
    error_log("[" . date('Y-m-d H:i:s') . "] changelike() called\n", 3, logpath);

    try {
        $input = json_decode(file_get_contents("php://input"), true);
        $liked = $input['liked'] ?? null;
        $postid = $input['postid'] ?? null;
        $userid = $input['userid'] ?? null;

        if ($liked === true) {
            $sqllike = "INSERT IGNORE INTO likes (userid, postid) VALUES (?, ?)";
        } else {
            $sqllike = "DELETE FROM likes WHERE userid = ? AND postid = ?";
        }

        $stmt_like = $liga->prepare($sqllike);
        $stmt_like->bind_param("ii", $userid, $postid);
        $stmt_like->execute();
        $stmt_like->close();

        // Count total likes
        $sqlcountlike = "SELECT COUNT(*) FROM likes WHERE postid = ?";
        $stmt_countlike = $liga->prepare($sqlcountlike);
        $stmt_countlike->bind_param("i", $postid);
        $stmt_countlike->execute();
        $stmt_countlike->bind_result($countlike);
        $stmt_countlike->fetch();
        $stmt_countlike->close();

        die(json_encode([
            "success" => "Yes",
            "countlike" => $countlike
        ]));

    } catch(Exception $e) {
        error_log("[" . date('Y-m-d H:i:s') . "] changelike() failed: " . $e->getMessage() . "\n", 3, logpath);
        die (json_encode([
            "success" => "No",
            "message" => $e->getMessage()
        ]));
    }
}

function insertComment() {
    global $liga;

    $input = json_decode(file_get_contents("php://input"), true);
    $userid = $input['userid'] ?? null;
    $commentText = $input['commenttext'] ?? '';
    $postid = $input['response_postid'] ?? null;
    $commentid = $input['response_commentid'] ?? null;

    if (!$userid || !$commentText || (!$postid && !$commentid)) {
        echo json_encode(["success" => "No", "message" => "Missing required fields"]);
        return;
    }

    try {
        // Decide which column to use
        if ($postid) {
            $sql = "INSERT INTO comment (userid, response_postid, commenttext) VALUES (?, ?, ?)";
            $stmt = $liga->prepare($sql);
            $stmt->bind_param("iis", $userid, $postid, $commentText);
        } else {
            $sql = "INSERT INTO comment (userid, response_commentid, commenttext) VALUES (?, ?, ?)";
            $stmt = $liga->prepare($sql);
            $stmt->bind_param("iis", $userid, $commentid, $commentText);
        }

        $stmt->execute();
        $stmt->close();

        echo json_encode(["success" => "Yes"]);
    } catch (Exception $e) {
        echo json_encode(["success" => "No", "message" => $e->getMessage()]);
    }
}

function getcomments() {
    global $liga;

    error_log("[" . date('Y-m-d H:i:s') . "] getcomments() called\n", 3, logpath);

    $input = json_decode(file_get_contents("php://input"), true);
    $postid = $input['postid'] ?? null;

    if (!$postid) {
        echo json_encode(["success" => "No", "message" => "Missing post ID"]);
        return;
    }

    try {
        $sql = "    
        SELECT 
            c.commentid,
            c.commenttext,
            c.date,
            c.userid,
            c.is_anonymous,
            c.response_postid,
            c.response_commentid,
            IF(c.userid IS NULL OR c.is_anonymous = 1, 'Anonymous', u.username) AS username,
            IF(c.userid IS NULL OR c.is_anonymous = 1, 'default_avatar.png', u.profilepic) AS profilepic
        FROM comment c
        LEFT JOIN users u ON c.userid = u.userid
        WHERE c.response_postid = ? OR c.response_commentid IS NOT NULL
        ORDER BY c.date ASC
    ";

        $stmt = $liga->prepare($sql);
        $stmt->bind_param("i", $postid);
        $stmt->execute();
        $result = $stmt->get_result();

        $flatComments = [];
        while ($row = $result->fetch_assoc()) {
            $flatComments[] = $row;
        }

        $visited = [];
        $nestedComments = buildNestedComments($flatComments, $postid, null, 0, $visited);

        echo json_encode(["success" => "Yes", "comments" => $nestedComments]);
        error_log("[" . date('Y-m-d H:i:s') . "] getcomments() succeeded\n", 3, logpath);

    } catch (Exception $e) {
        echo json_encode(["success" => "No", "message" => $e->getMessage()]);
        error_log("[" . date('Y-m-d H:i:s') . "] getcomments() failed: " . $e->getMessage() . "\n", 3, logpath);
    }
}

function buildNestedComments($comments, $postid, $parentId = null, $depth = 0, &$visited = []) {
    $nested = [];

    foreach ($comments as $comment) {
        $commentId = $comment['commentid'];

        // Prevent circular references
        if (isset($visited[$commentId])) {
            continue;
        }

        // Check if this is a top-level comment or a reply
        $isTopLevel = $parentId === null && $comment['response_postid'] == $postid && $comment['response_commentid'] === null;
        $isReply = $parentId !== null && $comment['response_commentid'] == $parentId;

        if ($isTopLevel || $isReply) {
            $visited[$commentId] = true;

            $replies = buildNestedComments($comments, $postid, $commentId, $depth + 1, $visited);

            $comment['replies'] = $replies ?: [];

            $nested[] = $comment;
        }
    }

    return $nested;
}

function deletepost()
{
    global $liga;

    try {
        $input = json_decode(file_get_contents("php://input"), true);
        $postid = $input['postid'] ?? null;

        if (!$postid) {
            throw new Exception("Post ID is required.");
        }

        // Step 1: Delete all comments on the post and replies to those comments
        $deleteComments = "
            DELETE FROM comment 
            WHERE response_postid = ? OR response_commentid IN (
                SELECT commentid FROM comment WHERE response_postid = ?
            )
        ";
        $stmt = $liga->prepare($deleteComments);
        $stmt->bind_param("ii", $postid, $postid);
        $stmt->execute();

        // Step 2: Delete the post itself
        $deletePost = "DELETE FROM post WHERE postid = ?";
        $stmt = $liga->prepare($deletePost);
        $stmt->bind_param("i", $postid);
        $stmt->execute();

        echo json_encode([
            "success" => "Yes",
            "message" => "Post and associated comments deleted."
        ]);
    } catch (Exception $e) {
        echo json_encode([
            "success" => "No",
            "message" => $e->getMessage()
        ]);
    }
}

function editpost() {
    global $liga;
    $input = json_decode(file_get_contents("php://input"), true);

    $postid = $input['postid'] ?? null;
    $text = $input['text'] ?? null;

    if (!$postid || !$text) {
        echo json_encode(["success" => "No", "message" => "Missing fields"]);
        return;
    }

    try {
        $stmt = $liga->prepare("UPDATE post SET text = ? WHERE postid = ?");
        $stmt->bind_param("si", $text, $postid);
        $stmt->execute();

        echo json_encode(["success" => "Yes"]);
    } catch (Exception $e) {
        echo json_encode(["success" => "No", "message" => $e->getMessage()]);
    }
}

function deleteComment() {
    global $liga;

    $input = json_decode(file_get_contents("php://input"), true);
    $commentId = $input['commentid'] ?? null;
    $userId = $input['userid'] ?? null;

    if (!$commentId || !$userId) {
        echo json_encode(["success" => "No", "message" => "Missing parameters"]);
        return;
    }

    try {
        // First check if user is admin
        $stmt = $liga->prepare("SELECT AdmPin FROM users WHERE userid = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $adminCheck = $stmt->get_result()->fetch_assoc();
        $isAdmin = !empty($adminCheck); // User exists in admins table

        // Then get comment owner
        $stmt = $liga->prepare("SELECT userid FROM comment WHERE commentid = ?");
        $stmt->bind_param("i", $commentId);
        $stmt->execute();
        $commentData = $stmt->get_result()->fetch_assoc();

        if (!$commentData) {
            echo json_encode(["success" => "No", "message" => "Comment not found"]);
            return;
        }

        $isOwner = $commentData['userid'] == $userId;

        if (!$isAdmin && !$isOwner) {
            echo json_encode(["success" => "No", "message" => "Unauthorized"]);
            return;
        }

        // Delete the comment
        $stmt = $liga->prepare("DELETE FROM comment WHERE commentid = ?");
        $stmt->bind_param("i", $commentId);
        $stmt->execute();

        echo json_encode(["success" => "Yes", "message" => "Comment deleted"]);
    } catch (Exception $e) {
        echo json_encode(["success" => "No", "message" => $e->getMessage()]);
    }
}

function search() {
    global $liga;
    $input = json_decode(file_get_contents("php://input"), true);
    $query = $input['query'] ?? '';

    if (!$query) {
        echo json_encode(["success" => "No", "message" => "Empty search query"]);
        return;
    }

    try {
        $sql_users = "SELECT userid, username, profilepic FROM users 
                     WHERE username LIKE CONCAT('%', ?, '%')";
        $stmt_users = $liga->prepare($sql_users);
        $stmt_users->bind_param("s", $query);
        $stmt_users->execute();
        $users = $stmt_users->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_users->close();


        $sql_posts = "SELECT p.postid, p.text, p.photoid, ph.filepath as path,
                     u.username, u.profilepic
              FROM post p
              LEFT JOIN photos ph ON p.photoid = ph.photoid
              INNER JOIN users u ON p.userid = u.userid
              WHERE p.text LIKE CONCAT('%', ?, '%')";
        $stmt_posts = $liga->prepare($sql_posts);
        $stmt_posts->bind_param("s", $query);
        $stmt_posts->execute();
        $posts = $stmt_posts->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_posts->close();

        echo json_encode([
            "success" => "Yes",
            "users" => $users,
            "posts" => $posts
        ]);
    } catch (Exception $e) {
        error_log("Search error: " . $e->getMessage());
        echo json_encode([
            "success" => "No",
            "message" => "An error occurred during search"
        ]);
    }
}
