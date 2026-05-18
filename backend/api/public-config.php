<?php
require_once __DIR__ . '/_core.php';
require_once __DIR__ . '/../config/mercadopago.php';

json_ok(['mp_public_key' => MP_PUBLIC_KEY]);
