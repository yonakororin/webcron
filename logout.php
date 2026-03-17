<?php
session_start();
session_destroy();
// Redirect to SSO logout to clear central session as well
header("Location: http://localhost:8001/index.php?logout=1");
exit;
