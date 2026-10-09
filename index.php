<?php

require "db.php";

$result = $conn->query("SELECT * FROM users");

while ($row = $result->fetch_assoc()) {
      echo $row['id'] . " - "
         . $row['first_name'] . " "
         . $row['last_name'] . " ("
         . $row['username'] . ")<br>"
         . $row['password']
         . $row['created_at'];

  }