<?php require __DIR__.'/includes/functions.php';
header('Location: '.(me()?area().'/dashboard.php':'auth/login.php'));
