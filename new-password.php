<?php require_once "controllerUserData.php"; ?>
<?php 
$email = $_SESSION['email'];
if($email == false){
    header('Location: login-user.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create a New Password</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f6f9;
            color: #333;
        }
        .container {
            margin-top: 50px;
        }
        .icon-container {
            text-align: center;
            margin-bottom: 20px;
        }
        .icon-container img {
            width: 100%; /* Full width of the parent container */
            height: auto; /* Maintain aspect ratio */
            max-width: 150px; /* Limit maximum width */
            border-radius: 50%; /* Circle shape */
            object-fit: cover; /* Cover the circle without distortion */  
            animation: pulse 1.5s infinite; /* Add pulse animation */
        }
                /* Define pulse animation */
                @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
            }
        }
        h2 {
            margin-bottom: 20px;
            font-weight: 600;
        }
        .alert {
            margin-bottom: 20px;
        }
        .form {
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1); /* Enhance shadow for form */
        }
        .form .form-control {
            border-radius: 4px;
            border: 1px solid #ddd;
            box-shadow: none;
            margin-bottom: 15px;
        }
        .form .form-control:focus {
            border-color: #007bff;
            box-shadow: none;
        }
        .form .button {
            background-color: #007bff;
            color: #fff;
            border: none;
            cursor: pointer;
            font-weight: 600; /* Bold the button text */
            padding: 10px 20px;
        }
        .form .button:hover {
            background-color: #0056b3;
        }
        @media (max-width: 767px) {
            .col-md-4 {
                width: 100%;
                padding: 0;
            }
            .form {
                margin: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-md-4 offset-md-4 form">
                <div class="icon-container">
                    <img src="images/immunitrack.png" alt="Icon"> <!-- Updated path to the uploaded icon -->
                </div>
                <form action="new-password.php" method="POST" autocomplete="off">
                    <?php 
                    if(isset($_SESSION['info'])){
                        ?>
                        <div class="alert alert-success text-center">
                            <?php echo $_SESSION['info']; ?>
                        </div>
                        <?php
                    }
                    ?>
                    <?php
                    if(count($errors) > 0){
                        ?>
                        <div class="alert alert-danger text-center">
                            <?php
                            foreach($errors as $showerror){
                                echo $showerror;
                            }
                            ?>
                        </div>
                        <?php
                    }
                    ?>
                    <div class="form-group">
                        <input class="form-control" type="password" name="password" placeholder="Create new password" required>
                    </div>
                    <div class="form-group">
                        <input class="form-control" type="password" name="cpassword" placeholder="Confirm your password" required>
                    </div>
                    <div class="form-group">
                        <input class="form-control button" type="submit" name="change-password" value="Change">
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
