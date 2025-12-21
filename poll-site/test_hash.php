<?php
$password = 'Test_Admin_Password'; // Введите пароль, который хотите использовать для админа
echo password_hash($password, PASSWORD_DEFAULT);
?>