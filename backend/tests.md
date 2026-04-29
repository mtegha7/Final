# Test If DB is Working:

<?php
require_once 'config/database.php';

$db = Database::getInstance()->conn;

echo "Connected!";

---

## Test if API is calling

<?php

Response::success([
    "message" => "API working 🚀"
]);

---
