<?php
/* START SESSION (If not already started) */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* SET FLASH MESSAGE: Stores message temporarily in session */
function set_flash($type, $message) {
    $_SESSION['flash'][$type] = $message;
}

/*
DISPLAY FLASH MESSAGE: Automatically clears after showing */
function display_flash() {
    if (isset($_SESSION['flash'])) {

        foreach ($_SESSION['flash'] as $type => $message) {

            $safe_message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

            /* Basic styling based on type */
            if ($type == "success") {
                echo "<div style='padding:10px;background:#d4edda;color:#155724;
                                margin-bottom:10px;border-radius:5px;'>
                       {$safe_message} 
                    </div>";
            }

            if ($type == "error") {
                echo "<div style='padding:10px;background:#f8d7da;color:#721c24;
                                margin-bottom:10px;border-radius:5px;'>
                        {$safe_message} 
                    </div>";
            }
        }

        /* Clear flash after displaying */
        unset($_SESSION['flash']);
    }
}