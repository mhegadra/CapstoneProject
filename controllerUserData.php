<?php 
session_start();
require "connection.php";

$email = "";
$errors = array();

// Handle user login
if(isset($_POST['login'])){
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $password = mysqli_real_escape_string($con, $_POST['password']);
    $check_email = "SELECT * FROM usertable WHERE email = '$email'";
    $res = mysqli_query($con, $check_email);

    if(mysqli_num_rows($res) > 0){
        $fetch = mysqli_fetch_assoc($res);
        $fetch_pass = $fetch['password'];

        if(password_verify($password, $fetch_pass)){
            $_SESSION['email'] = $email;
            $status = $fetch['status'];

            if($status == 'verified'){
                $_SESSION['password'] = $password;
                header('Location: home.php');
            } else {
                $_SESSION['info'] = "Email not verified. Please check your email for verification.";
                header('Location: user-otp.php');
            }
        } else {
            $errors['login'] = "Incorrect email or password!";
        }
    } else {
        $errors['login'] = "User not found. Please signup.";
    }
}

// Handle OTP verification for login
if(isset($_POST['check'])){
    $_SESSION['info'] = "";
    $otp_code = mysqli_real_escape_string($con, $_POST['otp']);
    $check_code = "SELECT * FROM usertable WHERE code = $otp_code";
    $code_res = mysqli_query($con, $check_code);

    if(mysqli_num_rows($code_res) > 0){
        $fetch_data = mysqli_fetch_assoc($code_res);
        $fetch_code = $fetch_data['code'];
        $email = $fetch_data['email'];
        $status = 'verified';
        $update_otp = "UPDATE usertable SET code = 0, status = '$status' WHERE code = $fetch_code";
        $update_res = mysqli_query($con, $update_otp);

        if($update_res){
            $_SESSION['name'] = $fetch_data['name'];
            $_SESSION['email'] = $email;
            header('Location: home.php');
            exit();
        } else {
            $errors['otp-error'] = "Failed to update OTP code!";
        }
    } else {
        $errors['otp-error'] = "Incorrect OTP code!";
    }
}

// Handle forgot password
if(isset($_POST['check-email'])){
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $check_email = "SELECT * FROM usertable WHERE email='$email'";
    $run_sql = mysqli_query($con, $check_email);

    if(mysqli_num_rows($run_sql) > 0){
        $code = rand(999999, 111111);
        $insert_code = "UPDATE usertable SET code = $code WHERE email = '$email'";
        $run_query = mysqli_query($con, $insert_code);

        if($run_query){
            $subject = "Password Reset Code";
            $message = "Your password reset code is $code";
            $sender = "From: immunitrack2024@gmail.com";

            if(mail($email, $subject, $message, $sender)){
                $_SESSION['info'] = "Password reset code sent to your email - $email";
                $_SESSION['email'] = $email;
                header('Location: reset-code.php');
                exit();
            } else {
                $errors['otp-error'] = "Failed to send reset code!";
            }
        } else {
            $errors['db-error'] = "Failed to update reset code!";
        }
    } else {
        $errors['email'] = "Email address does not exist!";
    }
}

// Handle reset OTP verification
if(isset($_POST['check-reset-otp'])){
    $_SESSION['info'] = "";
    $otp_code = mysqli_real_escape_string($con, $_POST['otp']);
    $check_code = "SELECT * FROM usertable WHERE code = $otp_code";
    $code_res = mysqli_query($con, $check_code);

    if(mysqli_num_rows($code_res) > 0){
        $fetch_data = mysqli_fetch_assoc($code_res);
        $email = $fetch_data['email'];
        $_SESSION['email'] = $email;
        $_SESSION['info'] = "Please create a new password.";
        header('Location: new-password.php');
        exit();
    } else {
        $errors['otp-error'] = "Incorrect reset code!";
    }
}

// Handle change password
if(isset($_POST['change-password'])){
    $_SESSION['info'] = "";
    $password = mysqli_real_escape_string($con, $_POST['password']);
    $cpassword = mysqli_real_escape_string($con, $_POST['cpassword']);

    if($password !== $cpassword){
        $errors['password'] = "Passwords do not match!";
    } else {
        $email = $_SESSION['email'];
        $encpass = password_hash($password, PASSWORD_BCRYPT);
        $update_pass = "UPDATE usertable SET code = 0, password = '$encpass' WHERE email = '$email'";
        $run_query = mysqli_query($con, $update_pass);

        if($run_query){
            $_SESSION['info'] = "Password changed successfully. You can now login with your new password.";
            header('Location: password-changed.php');
        } else {
            $errors['db-error'] = "Failed to change password!";
        }
    }
}

// Handle login now button
if(isset($_POST['login-now'])){
    header('Location: login-user.php');
}
?>
