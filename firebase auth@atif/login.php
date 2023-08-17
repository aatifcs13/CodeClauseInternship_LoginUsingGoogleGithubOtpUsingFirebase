<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");
session_start();
$con = mysqli_connect("localhost", "firebase_login", "firebase_login", "firebase_login");

function saveUserInDatabase($con, $email, $username, $token, $provider) {
    $check_user = mysqli_query($con, "SELECT * FROM users WHERE email='" . $email . "'");
    if (mysqli_num_rows($check_user) > 0) {
        echo "Login Successful";
        $_SESSION["email"] = $email;
    } else {
        $qr = mysqli_query($con, "INSERT INTO users (username, email, token, created_at, login_type) VALUES ('" . $username . "','" . $email . "','" . $token . "','" . date('Y-m-d H:i:s') . "','" . $provider . "')");
        
        if ($qr) {
            echo "User Created";
            $_SESSION["email"] = $email;
        } else {
            echo "Failed to Create User";
        }
    }
}

if ($con) {
    //echo "Connected";
}

// Check if the required parameters are present in the request
if (isset($_REQUEST['email']) && isset($_REQUEST['provider']) && isset($_REQUEST['username']) && isset($_REQUEST['token'])) {
    $email    = $_REQUEST['email'];
    $provider = $_REQUEST['provider'];
    $username = $_REQUEST['username'];
    $token    = $_REQUEST['token'];

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://identitytoolkit.googleapis.com/v1/accounts:lookup?key=AIzaSyBFumZTyqAzmq6Flp6whzsFZPv4hMudiCw&idToken=' . $token, // Replace YOUR_API_KEY with your actual API key
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_HTTPHEADER => array("Content-length:0"),
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    $array_response = json_decode($response, true);

    if (array_key_exists("users", $array_response)) {
        $user_res = $array_response["users"];
        if (count($user_res) > 0) {
            $user_res_1 = $user_res[0];
            
            if (array_key_exists("phoneNumber", $user_res_1)) {
                if ($email == $user_res_1['phoneNumber']) {
                    saveUserInDatabase($con, $email, $username, $token, "Phone");
                } else {
                    echo "Invalid Login Request";
                }
            } else {
                if ($user_res_1["email"] == $email) {
                    $provider1 = $user_res_1["providerUserInfo"][0]["providerId"];
                    if ($user_res_1["emailVerified"] == "1" || $user_res_1["emailVerified"] == "true" || $user_res_1["emailVerified"] == true || $provider1 == "facebook.com") {
                        saveUserInDatabase($con, $email, $username, $token, $provider);
                    } else {
                        echo "Please Verify Your Email to Get Login";
                    }
                } else {
                    echo "Unknown Email User";
                }
            }
        } else {
            echo "Invalid Request User Not Found";
        }
    } else {
        echo "Unknown Bad Request";
    }
} else {
    echo "Missing or Invalid Parameters in the Request";
}