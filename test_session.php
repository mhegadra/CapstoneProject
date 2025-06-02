<?php
session_start();
if (isset($_SESSION['parent_id'])) {
    echo "Parent ID in session: " . $_SESSION['parent_id'];
} else {
    echo "No parent ID set in session.";
}
?>
