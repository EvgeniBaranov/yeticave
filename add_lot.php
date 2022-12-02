<?php
require_once("helpers.php");
//require_once("data.php");
require_once("functions.php");
require_once("init.php");
require_once("model.php");

$categories = get_query_category_list($con);
$categories_id = array_column($categories, "id");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $required = ["lot-name", "category", "message", "lot-rate", "lot-step", "lot-date"];
    $errors = [];

    $rules = [
        "category" => function ($value) use ($categories_id) {
            return validate_category($value, $categories_id);
        },
        "lot-rate" => function ($value) {
            return validate_num($value);
        },
        "lot-step" => function ($value) {
            return validate_num($value);
        },
        "lot-date" => function ($value) {
            return validate_date($value);
        },
        "message" => function ($value) {
            return validate_length($value, 10, 2500);
        },
        "lot-name" => function ($value) {
            return validate_length($value, 10, 200);
        }
    ];

    $lot = filter_input_array(INPUT_POST,
    [
        "lot-name"=>FILTER_DEFAULT,
        "category"=>FILTER_DEFAULT,
        "message"=>FILTER_DEFAULT,
        "lot-rate"=>FILTER_DEFAULT,
        "lot-step"=>FILTER_DEFAULT,
        "lot-date"=>FILTER_DEFAULT
    ], true);

    foreach ($lot as $field => $value) {
        if (isset($rules[$field])) {
            $rule = $rules[$field];
            $errors[$field] = $rule($value);
        }
        if (in_array($field, $required) && empty($value)) {
            $errors[$field] = "Поле $field нужно заполнить";
        }
    }

    $errors = array_filter($errors);

    if (!empty($_FILES["lot-img"]["name"])) {
        $tmp_name = $_FILES["lot-img"]["tmp_name"];
        $path = $_FILES["lot-img"]["name"];
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $file_type = finfo_file($finfo, $tmp_name);

        if ($file_type === "image/jpeg") {
            $filename = uniqid() . ".jpg";
            move_uploaded_file($tmp_name, "uploads/" . $filename);
            $lot["path"] = $filename;
        } else if ($file_type === "image/png") {
            $filename = uniqid() . ".png";
            move_uploaded_file($tmp_name, "uploads/" . $filename);
            $lot["path"] = $filename;
        }
        else {
            $errors["lot-img"] = "Загрузите картинку в формате jpg, jpeg, png";
        }
    } else {
        $errors["lot-img"] = "Вы не загрузили файл";
    }

    if (count($errors)) {
        $page_content = include_template("add.php", [
            "categories" => $categories,
            "errors" => $errors,
            "lot" => $lot
        ]);
    } else {
    }
} else {
    $page_content = include_template("add.php", [
        "categories" => $categories,
        "lot" => null
    ]);
}

$layout_content = include_template("layout.php", [
    "content" => $page_content,
    "categories" => $categories,
    "title" => "Титул",
    "is_auth" => $is_auth,
    "user_name" => $user_name
]);

print($layout_content);