<?php
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');
    }

    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
            header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
            header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

        exit(0);
    }

    require_once("DB.php");

    $db = new DB("localhost", "dbhonofre", "root", "q1w2e3r4*");

    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        /* Login */
        if ($_GET['url'] == "auth") {
            $postBody = takePostBody();

            if ($postBody->username && $postBody->password) {
                $username = $postBody->username;
                $password = $postBody->password;

                $returnData = $db->query("SELECT * FROM users WHERE username='".$username."' AND password='".$password."'");

                if ($returnData) {
                    $currentUser = $returnData[0][username];
                    if($returnData[0][active_token] == NULL) {
                        $newToken = md5(uniqid(rand(), true));
                        $db->query("UPDATE users SET active_token='".$newToken."' WHERE username='".$currentUser."'");
                        echo('{"current_user":"'.$currentUser.'","token":"'.$newToken.'"}');
                    }
                    else {
                        $currentTokenr = $returnData[0][active_token];
                        echo('{"current_user":"'.$currentUser.'","token":"'.$currentTokenr.'"}');
                    }
                    http_response_code(200);
                }
                else {
                    http_response_code(401);
                }
            }
            else {
                http_response_code(400);
            }
        }
        /* Logout */
        elseif ($_GET['url'] == "logout") {
            $postBody = takePostBody();

            if ($postBody->username) {
                $currentUser = $postBody->username;
                $db->query("UPDATE users SET active_token=NULL WHERE username='".$currentUser."'");
                http_response_code(200);
            }
            else {
                http_response_code(400);
            }
        }
        /* Add Categories */
        elseif ($_SERVER['REQUEST_URI'] == "/categories/add") {
            if (isValidToken($db)) {
                $postBody = takePostBody();

                if (isset($postBody->title) &&
                    isset($postBody->sequence) &&
                    isset($postBody->status) &&
                    isset($postBody->type)) {
                    $title = $postBody->title;
                    $sequence = $postBody->sequence;
                    $status_id = $postBody->status;
                    $parent_id = $postBody->type;

                    if ($sequence == "null") {
                        if ($parent_id == "null") {
                            $returnData = $db->query("SELECT MAX(sequence) as sequence FROM categories WHERE parent_id IS NULL");
                        }
                        else {
                            $returnData = $db->query("SELECT MAX(sequence) as sequence FROM categories WHERE parent_id = " .$parent_id);
                        }
                        $resultArray = json_decode(json_encode($returnData));
                        $sequence = $resultArray[0]->sequence + 1;
                    }

                    $db->query("INSERT INTO categories(parent_id, status_id, title, sequence) VALUES (" .$parent_id .", " .$status_id .", '" .$title ."', '" .$sequence ."')");
                    http_response_code(200);
                }
                else {
                    http_response_code(400);
                }
            }
            else {
                http_response_code(401);
            }
        }
        /* Edit Categories */
        elseif (strpos($_SERVER['REQUEST_URI'], '/categories/edit') !== false) {
            $id = str_replace("/categories/edit/", "", $_SERVER['REQUEST_URI']);
            if (is_numeric($id)) {
                if (isValidToken($db)) {
                    $postBody = takePostBody();
                    $returnData = $db->query("SELECT title FROM categories WHERE id = ".$id);

                    if ($returnData &&
                        isset($postBody->title) &&
                        isset($postBody->sequence) &&
                        isset($postBody->status) &&
                        isset($postBody->type)) {
                        $title = $postBody->title;
                        $sequence = $postBody->sequence;
                        $status_id = $postBody->status;
                        $parent_id = $postBody->type;
                        $db->query("UPDATE categories SET parent_id=" .$parent_id .", status_id=" .$status_id .", title='" .$title ."', sequence=" .$sequence ." WHERE id = " .$id);
                        http_response_code(200);
                    }
                    else {
                      http_response_code(405);
                    }
                }
                else {
                    http_response_code(401);
                }
            }
            else {
                http_response_code(405);
            }
        }
        /* Adicionar Pacotes */
        elseif ($_SERVER['REQUEST_URI'] == "/packages/add") {
            if (isValidToken($db)) {
                $postBody = takePostBody();

                if (isset($postBody->title) &&
                    isset($postBody->status) &&
                    isset($postBody->short_description) &&
                    isset($postBody->description) &&
                    isset($postBody->value)) {
                    $title = $postBody->title;
                    $status_id = $postBody->status;
                    $short_description = $postBody->short_description;
                    $description = $postBody->description;
                    $value = $postBody->value;

                    $db->query("INSERT INTO packages(status_id, title, short_description, description, value) VALUES (" .$status_id .", '" .$title ."', '" .$short_description ."', '" .$description ."', " .$value .")");
                    http_response_code(200);

                    $returnData = $db->query("SELECT id from packages WHERE title = '". $title ."' AND value = " .$value);
                    echo json_encode($returnData);
                }
                else {
                    http_response_code(400);
                }
            }
            else {
                http_response_code(401);
            }
        }
        /* Adicionar imagens do Pacote */
        elseif ($_SERVER['REQUEST_URI'] == "/packages/image/add") {
            if (isValidToken($db)) {

                    $id = $_REQUEST['id'];
                    $sequence = $_REQUEST['sequence'];
                    $src = $_FILES['packageImage'];

                    $new_image_name = md5(uniqid(rand(), true));
                    $ext = pathinfo($src['name'], PATHINFO_EXTENSION);
                    $new_image_name = $new_image_name ."." .$ext;

                    move_uploaded_file($src['tmp_name'], dirname(__FILE__) . "./uploads/" .$new_image_name);

                    $db->query("INSERT INTO package_images(packages_id, sequence, src) VALUES (" .$id ."," .$sequence .",'" ."http://localhost:4500" ."/uploads/" .$new_image_name ."')");
                    http_response_code(200);

            }
            else {
                http_response_code(401);
            }
        }
        /* Adicionar categorias do Pacote */
        elseif (strpos($_SERVER['REQUEST_URI'], '/packages/categories/add') !== false) {
            $id = str_replace("/packages/categories/add/", "", $_SERVER['REQUEST_URI']);
            if (is_numeric($id)) {
                if (isValidToken($db)) {
                    $postBody = takePostBody();
                    if ($postBody) {
                        foreach ($postBody as $value) {
                            $db->query("INSERT INTO categories_packages(categories_id, packages_id) VALUES (" .$value->id ."," .$id .")");
                            http_response_code(200);
                        }
                        http_response_code(200);
                    }
                    else {
                        http_response_code(405);
                    }
                }
                else {
                    http_response_code(401);
                }
            }
            else {
                http_response_code(405);
            }
        }
        /* Editar Pacotes */
        elseif (strpos($_SERVER['REQUEST_URI'], '/packages/edit') !== false) {
            $id = str_replace("/packages/edit/", "", $_SERVER['REQUEST_URI']);
            if (is_numeric($id)) {
                if (isValidToken($db)) {
                    $postBody = takePostBody();

                    if (isset($postBody->title) &&
                        isset($postBody->status) &&
                        isset($postBody->short_description) &&
                        isset($postBody->description) &&
                        isset($postBody->value)) {
                        $title = $postBody->title;
                        $status_id = $postBody->status;
                        $short_description = $postBody->short_description;
                        $description = $postBody->description;
                        $value = $postBody->value;

                        $db->query("UPDATE packages SET status_id=" .$status_id .",title='" .$title ."',short_description='" .$short_description ."',description='" .$description ."',value=".$value ." WHERE id=" .$id);
                        http_response_code(200);
                    }
                    else {
                        http_response_code(400);
                    }
                }
                else {
                    http_response_code(401);
                }
            }
            else {
                http_response_code(405);
            }
        }
        /* Editar categorias do Pacotes */
        elseif (strpos($_SERVER['REQUEST_URI'], '/packages/categories/update') !== false) {
          $id = str_replace("/packages/categories/update/", "", $_SERVER['REQUEST_URI']);
          if (is_numeric($id)) {
                if (isValidToken($db)) {
                    $postBody = takePostBody();
                    if ($postBody) {
                        $db->query("DELETE FROM categories_packages WHERE packages_id = ".$id);
                        foreach ($postBody as $value) {
                            $db->query("INSERT INTO categories_packages(categories_id, packages_id) VALUES (" .$value->id ."," .$id .")");
                        }
                        http_response_code(200);
                    }
                    else {
                        http_response_code(405);
                    }
                }
                else {
                    http_response_code(401);
                }
            }
            else {
                http_response_code(405);
            }
        }
        /* Editar imagens do Pacotes */
        elseif (strpos($_SERVER['REQUEST_URI'], '/packages/image/edit') !== false) {
            $id = str_replace("/packages/image/edit/", "", $_SERVER['REQUEST_URI']);
            if (is_numeric($id)) {
                if (isValidToken($db)) {
                    $sequence = $_REQUEST['sequence'];
                    $src = $_REQUEST['src'];
                    $file = $_FILES['packageImage'];

                    $SLQExistSequence = $db->query("SELECT * FROM package_images WHERE packages_id = " .$id ." AND sequence = " .$sequence);

                    if ($SLQExistSequence) {
                        if (isset($file)) {
                            $db->query("DELETE FROM package_images WHERE packages_id = ".$id ." AND sequence = " .$sequence);
                            $currentSrc = str_replace('http://localhost:4500/uploads/', '', $SLQExistSequence[0]['src']);
                            unlink(dirname(__FILE__) . "./uploads/" .$currentSrc);

                            $new_image_name = md5(uniqid(rand(), true));
                            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                            $new_image_name = $new_image_name ."." .$ext;

                            move_uploaded_file($file['tmp_name'], dirname(__FILE__) . "./uploads/" .$new_image_name);

                            $db->query("INSERT INTO package_images(packages_id, sequence, src) VALUES (" .$id ."," .$sequence .",'" ."http://localhost:4500" ."/uploads/" .$new_image_name ."')");
                            http_response_code(200);
                        }
                        else if (!isset($src) || $src == "") {
                            $db->query("DELETE FROM package_images WHERE packages_id = ".$id ." AND sequence = " .$sequence);
                            $currentSrc = str_replace('http://localhost:4500/uploads/', '', $SLQExistSequence[0]['src']);
                            unlink(dirname(__FILE__) . "./uploads/" .$currentSrc);
                            http_response_code(200);
                        }
                    }
                    else {
                        if (isset($file)) {
                            $new_image_name = md5(uniqid(rand(), true));
                            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                            $new_image_name = $new_image_name ."." .$ext;

                            move_uploaded_file($file['tmp_name'], dirname(__FILE__) . "./uploads/" .$new_image_name);

                            $db->query("INSERT INTO package_images(packages_id, sequence, src) VALUES (" .$id ."," .$sequence .",'" ."http://localhost:4500" ."/uploads/" .$new_image_name ."')");
                            http_response_code(200);
                        }
                    }
                }
                else {
                    http_response_code(401);
                }
            }
            else {
                http_response_code(405);
            }
        }
        /* Add Banners */
        elseif ($_SERVER['REQUEST_URI'] == "/banners/add") {
            if (isValidToken($db)) {
                if (($_REQUEST['status'] == 0 || $_REQUEST['status'] == 1) &&
                    $_FILES['bannerImg']) {
                    $status_id = $_REQUEST['status'];
                    $url = $_REQUEST['url'];
                    $new_window = $_REQUEST['new_window'];
                    $src = $_FILES['bannerImg'];
                    $sequence = $_REQUEST['sequence'];
                    $alt = $_REQUEST['alt'];

                    $new_image_name = md5(uniqid(rand(), true));
                    $ext = pathinfo($src['name'], PATHINFO_EXTENSION);
                    $new_image_name = $new_image_name ."." .$ext;

                    move_uploaded_file($src['tmp_name'], dirname(__FILE__) . "./uploads/" .$new_image_name);

                    if ($sequence == "null") {
                        $returnData = $db->query("SELECT MAX(sequence) as sequence FROM banners");

                        $resultArray = json_decode(json_encode($returnData));
                        $sequence = $resultArray[0]->sequence + 1;
                    }

                    if($new_window == "") {
                        $new_window = 0;
                    }

                    $db->query("INSERT INTO banners(status_id, url, new_window, src, sequence, alt) VALUES (" .$status_id .",'" .$url ."'," .$new_window .",'" ."http://localhost:4500" ."/uploads/" .$new_image_name ."'," .$sequence .",'" .$alt ."')");
                    http_response_code(200);
                }
                else {
                    http_response_code(400);
                }
            }
            else {
                http_response_code(401);
            }
        }
        /* Edit Banners */
        elseif (strpos($_SERVER['REQUEST_URI'], '/banners/edit') !== false) {
            $id = str_replace("/banners/edit/", "", $_SERVER['REQUEST_URI']);
            if (is_numeric($id)) {
                if (isValidToken($db)) {
                  if (($_REQUEST['status'] == 0 || $_REQUEST['status'] == 1)) {

                      $status_id = $_REQUEST['status'];
                      $url = $_REQUEST['url'];
                      $new_window = $_REQUEST['new_window'];
                      $src = $_FILES['bannerImg'];
                      $sequence = $_REQUEST['sequence'];
                      $alt = $_REQUEST['alt'];

                      if($src == null) {
                          $db->query("UPDATE banners SET status_id=" .$status_id .",url='" .$url ."',new_window=" .$new_window .",sequence=" .$sequence .",alt='" .$alt ."' WHERE id = " .$id);
                          http_response_code(200);
                      }
                      else {
                          $returnData = $db->query("SELECT src FROM banners WHERE id = " .$id);
                          $currentSrc = str_replace('http://localhost:4500/uploads/', '', $returnData[0]['src']);
                          unlink(dirname(__FILE__) . "./uploads/" .$currentSrc);

                          $new_image_name = md5(uniqid(rand(), true));
                          $ext = pathinfo($src['name'], PATHINFO_EXTENSION);
                          $new_image_name = $new_image_name ."." .$ext;

                          move_uploaded_file($src['tmp_name'], dirname(__FILE__) . "./uploads/" .$new_image_name);

                          $db->query("UPDATE banners SET status_id=" .$status_id .",url='" .$url ."',new_window=" .$new_window .",src='" ."http://localhost:4500" ."/uploads/" .$new_image_name ."',sequence=" .$sequence .",alt='" .$alt ."' WHERE id = " .$id);
                          http_response_code(200);
                      }
                  }
                  else {
                      http_response_code(400);
                  }
                }
                else {
                    http_response_code(401);
                }
            }
            else {
                http_response_code(405);
            }
        }
        else {
            http_response_code(405);
        }
    }
    elseif ($_SERVER['REQUEST_METHOD'] == "GET") {
        /* Categories all */
        if ($_SERVER['REQUEST_URI'] == "/categories/all") {
            if (isValidToken($db)) {
                $returnData = $db->query("SELECT cat.id, (SELECT c.title from categories as c WHERE c.id = cat.parent_id) as parent, cat.title FROM categories as cat WHERE cat.status_id = 1 ORDER BY cat.sequence");
                echo json_encode($returnData);
                http_response_code(200);
            }
            else {
                http_response_code(401);
            }
        }
        /* Categories primary */
        elseif ($_SERVER['REQUEST_URI'] == "/categories/primary/all") {
            if (isValidToken($db)) {
                $returnData = $db->query("SELECT c.id, c.title, c.sequence, c.status_id, s.title as status FROM categories as c INNER JOIN status as s on s.id = c.status_id WHERE c.parent_id IS NULL");
                echo json_encode($returnData);
                http_response_code(200);
            }
            else {
                http_response_code(401);
            }
        }
        /* Categories sub */
        elseif (strpos($_SERVER['REQUEST_URI'], '/categories/sub/') !== false) {
            $id = str_replace("/categories/sub/", "", $_SERVER['REQUEST_URI']);

            if (is_numeric($id)) {
                if (isValidToken($db)) {
                    $returnData = $db->query("SELECT c.id, c.title, c.sequence, c.status_id, s.title as status FROM categories as c INNER JOIN status as s on s.id = c.status_id WHERE c.parent_id = " .$id);
                    echo json_encode($returnData);
                    http_response_code(200);
                }
                else {
                    http_response_code(401);
                }
            }
            else {
                http_response_code(405);
            }
        }
        /* Banners */
        elseif ($_SERVER['REQUEST_URI'] == "/banners/all") {
            if (isValidToken($db)) {
                $returnData = $db->query("SELECT b.id, b.url, b.new_window, b.alt, b.src, b.sequence, b.status_id, s.title as status FROM banners as b INNER JOIN status as s on s.id = b.status_id");
                echo json_encode($returnData);
                http_response_code(200);
            }
            else {
                http_response_code(401);
            }
        }
        /* packages */
        elseif ($_SERVER['REQUEST_URI'] == "/packages/all") {
            if (isValidToken($db)) {
                $returnData = $db->query("SELECT p.id, p.title, p.value, (SELECT src FROM package_images as i WHERE i.packages_id = p.id AND i.sequence = 1) as image, s.title as status FROM packages as p INNER JOIN status as s on s.id = p.status_id");
                echo json_encode($returnData);
                http_response_code(200);
            }
            else {
                http_response_code(401);
            }
        }
        elseif (strpos($_SERVER['REQUEST_URI'], '/packages/extrainfo') !== false) {
            $id = str_replace("/packages/extrainfo/", "", $_SERVER['REQUEST_URI']);
            if (is_numeric($id)) {
                if (isValidToken($db)) {
                    $result = [];
                    $returnData = $db->query("SELECT short_description, description FROM packages WHERE id = " .$id);
                    $result['short_description'] = $returnData[0]['short_description'];
                    $result['description'] = $returnData[0][1];
                    $returnData = $db->query("SELECT id, sequence, src FROM package_images WHERE packages_id = " .$id);
                    $result['images'] = $returnData;
                    $returnData = $db->query("SELECT cp.categories_id as id, (SELECT ct.title from categories as ct WHERE ct.id = c.parent_id) as parent, c.title FROM categories_packages AS cp INNER JOIN categories AS c ON c.id = cp.categories_id WHERE packages_id = " .$id);
                    $result['categories'] = $returnData;

                    echo json_encode($result);
                    http_response_code(200);
                }
                else {
                    http_response_code(401);
                }
            }
            else {
                http_response_code(405);
            }
        }
        else {
            http_response_code(405);
        }
    }
    elseif ($_SERVER['REQUEST_METHOD'] == "DELETE") {
        /* Categories remove */
        if (strpos($_SERVER['REQUEST_URI'], '/categories/remove/') !== false) {
            $id = str_replace("/categories/remove/", "", $_SERVER['REQUEST_URI']);
            if (is_numeric($id)) {
                if (isValidToken($db)) {
                    $returnData = $db->query("SELECT title FROM categories WHERE id = ".$id);

                    if ($returnData) {
                        $db->query("DELETE FROM categories WHERE id = ".$id);
                        $db->query("DELETE FROM categories WHERE parent_id = ".$id);
                    }
                    else {
                      http_response_code(405);
                    }
                }
                else {
                    http_response_code(401);
                }
            }
            else {
                http_response_code(405);
            }
        }
        /* Banners remove */
        elseif (strpos($_SERVER['REQUEST_URI'], '/banners/remove/') !== false) {
            $id = str_replace("/banners/remove/", "", $_SERVER['REQUEST_URI']);
            if (is_numeric($id)) {
                if (isValidToken($db)) {
                    $returnSrc = $db->query("SELECT src FROM banners WHERE id = ".$id);

                    if ($returnSrc) {
                        $currentSrc = str_replace('http://localhost:4500/uploads/', '', $returnSrc[0]['src']);
                        unlink(dirname(__FILE__) . "./uploads/" .$currentSrc);

                        $db->query("DELETE FROM banners WHERE id = ".$id);
                    }
                    else {
                      http_response_code(405);
                    }
                }
                else {
                    http_response_code(401);
                }
            }
            else {
                http_response_code(405);
            }
        }
        /* Packages remove */
        elseif (strpos($_SERVER['REQUEST_URI'], '/packages/remove/') !== false) {
            $id = str_replace("/packages/remove/", "", $_SERVER['REQUEST_URI']);
            if (is_numeric($id)) {
                if (isValidToken($db)) {
                    $returnSrc = $db->query("SELECT src FROM package_images WHERE packages_id = ".$id);

                    if ($returnSrc) {
                        foreach ($returnSrc as $value) {
                            $currentSrc = str_replace('http://localhost:4500/uploads/', '', $value['src']);
                            unlink(dirname(__FILE__) . "./uploads/" .$currentSrc);
                        }
                    }

                    $db->query("DELETE FROM package_images WHERE packages_id = ".$id);
                    $db->query("DELETE FROM categories_packages WHERE packages_id = ".$id);
                    $db->query("DELETE FROM packages WHERE id = ".$id);

                    http_response_code(200);
                }
                else {
                    http_response_code(401);
                }
            }
            else {
                http_response_code(405);
            }
        }
        else {
            http_response_code(405);
        }
    }
    else {
        http_response_code(405);
    }

    /* Functions */

    function takePostBody() {
        return json_decode(file_get_contents("php://input"));
    }

    function isValidToken($db) {
        $token = null;
        $headers = apache_request_headers();
        if(isset($headers['Authorization'])){
            $token = $headers['Authorization'];
        }
        $returnData = $db->query("SELECT * FROM users WHERE active_token='".$token."'");
        return $returnData;
    }
?>
