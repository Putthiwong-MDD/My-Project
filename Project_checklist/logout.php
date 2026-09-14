<?php
session_start();
session_unset(); // เคลียร์ตัวแปร session ทั้งหมด
session_destroy(); // ทำลาย session

header("Location: index_login.php");
exit;