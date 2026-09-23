<?php

/**
 * EmailCleanser: typos de Gmail en GMAIL_DOMAINS.
 *
 * GMAIL_DOMAINS se matchea por substring (strpos), no contra el dominio completo, así que
 * cada variante que se suma puede reescribir a @gmail.com dominios que no son Gmail.
 * Este test fija las dos caras: las variantes aceptadas se normalizan y una lista de
 * dominios reales (o centinelas) queda intacta.
 *
 * Ejecutar: php tests/EmailCleanserGmailTyposTest.php
 */

// Autoloader propio sobre src/: el test corre con `php` plano, sin composer install
// y sin depender de que haya un vendor instalado.
spl_autoload_register(function ($class) {
    $prefijo = 'WoowUp\\';
    if (strpos($class, $prefijo) !== 0) {
        return;
    }
    $file = __DIR__ . '/../src/' . str_replace('\\', '/', substr($class, strlen($prefijo))) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

use WoowUp\Cleansers\EmailCleanser;

$passed = 0;
$failed = 0;

function check($cond, $msg)
{
    global $passed, $failed;
    if ($cond) { $passed++; echo "  OK   $msg\n"; }
    else       { $failed++; echo "  FAIL $msg\n"; }
}

$cleanser = new EmailCleanser();
$user     = 'juliana15';

echo "======================================================================\n";
echo " EmailCleanser: typos de Gmail\n";
echo "======================================================================\n";

// ------------------------------------------------------------------ positivos
echo "\n--- las variantes nuevas se normalizan a @gmail.com ---\n";

$variantes = [
    'gamail', 'gimail', 'gmasil', 'gmnail', 'gamial', 'gmauil', 'gomail', 'gmsil',
    'gtmail', 'gmaqil', 'gmaail', 'gmiail', 'gmsail', 'gimai', 'gmaoil', 'gnmail',
    'gmayl', 'gmisl', 'gamaoil', 'gimeil', 'gma8il', 'gamcil', 'giimail', 'gimil',
    'gmaeil', 'gamlil', 'gfmail', 'gmia',
    'g.mail', 'gm.ail',
];

foreach ($variantes as $variante) {
    $dominio = "$variante.com";
    $out     = $cleanser->sanitize("$user@$dominio");
    check($out === "$user@gmail.com", "$dominio -> " . var_export($out, true));
}

echo "\n--- la variante base también cubre .com.ar ---\n";

foreach (['gomail.com.ar', 'gmasil.com.ar', 'gmsil.com.ar', 'gamail.com.ar'] as $dominio) {
    $out = $cleanser->sanitize("$user@$dominio");
    check($out === "$user@gmail.com", "$dominio -> " . var_export($out, true));
}

echo "\n--- gmaol.com no se descarta como mezcla Gmail + AOL ---\n";

// 'aol' estaba en KNOWN_DOMAINS y es substring de 'gmaol': hasMixedDomains() lo tomaba
// como Gmail + otro proveedor y sanitize() devolvía false en vez de corregirlo.
$out = $cleanser->sanitize("$user@gmaol.com");
check($out === "$user@gmail.com", 'gmaol.com -> ' . var_export($out, true));

// ------------------------------------------------------------------ negativos
echo "\n--- dominios que NO deben modificarse ---\n";

$intactos = [
    // Proveedores reales a una o dos letras de "gmail". ymail.com es de Yahoo.
    'ymail.com', 'email.com', 'mail.com', 'gmx.com', 'googlemail.com',
    'hotmail.com.ar', 'outlook.com', 'yahoo.com.ar',
    // Productos/servidores de mail reales: iMail (Ipswitch), qmail (MTA), hMailServer.
    'imail.com', 'qmail.com', 'hmail.com',
    // Cortos y genéricos: no hay forma de descartar un dominio corporativo real.
    'tmail.com', 'gma.com', 'mail.com.ar', 'dmail.com',
    // Centinela semántico ("no tiene mail"), no un typo.
    'nomail.com',
];

foreach ($intactos as $dominio) {
    $email = "$user@$dominio";
    $out   = $cleanser->sanitize($email);
    check($out === $email, "$dominio -> " . var_export($out, true));
}

// ---------------------------------------------------------- limitaciones conocidas
// El matching por substring también hace match con el final de otros dominios y con el
// user part. No se corrige acá: cambiarlo a match exacto del label dejaría de corregir
// casos hoy cubiertos (4gmail.com, gmail81.com, gmaiil.com, ...). Se listan para que
// quede a la vista si alguien cambia el matching: no cuentan como fallo.
echo "\n--- limitaciones conocidas del matching por substring (informativo) ---\n";

$conocidos = [
    "$user@abigail.com"             => "$user@abigail.com",
    "$user@amigomail.com"           => "$user@amigomail.com",
    'gmia.comercial@empresa.com.ar' => 'gmia.comercial@empresa.com.ar',
];

foreach ($conocidos as $email => $esperado) {
    $out    = $cleanser->sanitize($email);
    $estado = $out === $esperado ? 'YA OK' : 'KNOWN';
    echo "  $estado $email -> " . var_export($out, true) . "\n";
}

echo "\n$passed OK, $failed FAIL\n";
exit($failed === 0 ? 0 : 1);
