<?php
include "db.php";

$result = mysqli_query($conn, "SELECT * FROM users");
?>

<!DOCTYPE html>
<html>
    <head>
        <title>users</title>
    </head>
    <body>
        
    <h1>Registered Users</h1>

    <?php while ($row = mysqli_fetch_assoc($result)) { ?>
    <p>
        Name: <?php echo $row['name']; ?> <br>
        Email: <?php echo $row['email']; ?> <br>
        Role: <?php echo $row['role']; ?>
    </p>
    <hr>
<?php } ?>

    </body>
</html>