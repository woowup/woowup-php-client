<?php

/**
 * EmailCleanser: usuarios de relleno (GENERIC_USERS).
 *
 * Los locales cargan emails de relleno bien escritos y con dominio real ("no@mail.com",
 * "nosesabe@hotmail.com") cuando el cliente no da el suyo. sanitize() los devuelve como
 * INVALID_EMAIL: los de negación (GENERIC_USERS) en cualquier dominio y los que también son
 * casillas reales en un dominio propio (GENERIC_FREE_MAIL_USERS: test@, mail@...) solo en correo gratuito, así Multiusers::cleanEmail() guarda <documento>@noemail.com en vez
 * del email falso. El match es contra el usuario completo: un usuario que solo empieza o termina
 * con uno de esos términos sigue siendo válido.
 *
 * El chequeo corre antes de la corrección de TLD y solo mira el usuario, así que el test
 * usa el cleanser sin TldCorrector (que baja la lista de IANA por red).
 *
 * Ejecutar: php tests/EmailCleanserGenericUsersTest.php
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

echo "======================================================================\n";
echo " EmailCleanser: usuarios de relleno\n";
echo "======================================================================\n";

// ------------------------------------------------------------------ positivos
echo "\n--- cada usuario de negación es INVALID_EMAIL en cualquier dominio ---\n";

$gratuitos = ['gmail.com', 'hotmail.com', 'mail.com', 'yahoo.com.ar', 'outlook.com', 'gamil.com', 'hotmial.com', 'live.com.ar'];
$propios   = ['empresa.com.ar', 'estudio.com', 'agencia.com', 'hotelplaza.com', 'macro.com.ar'];

foreach (EmailCleanser::GENERIC_USERS as $usuario) {
    $fallidos = [];
    foreach (array_merge($gratuitos, $propios) as $dominio) {
        $out = $cleanser->sanitize("$usuario@$dominio");
        if ($out !== EmailCleanser::INVALID_EMAIL) {
            $fallidos[] = "$dominio -> " . var_export($out, true);
        }
    }
    check($fallidos === [], "$usuario@ en " . (count($gratuitos) + count($propios)) . ' dominios' . ($fallidos ? ': ' . implode(', ', $fallidos) : ''));
}

echo "\n--- test@, mail@... son INVALID_EMAIL en correo gratuito ---\n";

foreach (EmailCleanser::GENERIC_FREE_MAIL_USERS as $usuario) {
    $fallidos = [];
    foreach ($gratuitos as $dominio) {
        $out = $cleanser->sanitize("$usuario@$dominio");
        if ($out !== EmailCleanser::INVALID_EMAIL) {
            $fallidos[] = "$dominio -> " . var_export($out, true);
        }
    }
    check($fallidos === [], "$usuario@ en " . count($gratuitos) . ' dominios gratuitos' . ($fallidos ? ': ' . implode(', ', $fallidos) : ''));
}

echo "\n--- test@, mail@... siguen válidos en un dominio propio ---\n";

foreach (EmailCleanser::GENERIC_FREE_MAIL_USERS as $usuario) {
    $fallidos = [];
    foreach ($propios as $dominio) {
        $out = $cleanser->sanitize("$usuario@$dominio");
        if ($out === false || $out === EmailCleanser::INVALID_EMAIL) {
            $fallidos[] = "$dominio -> " . var_export($out, true);
        }
    }
    check($fallidos === [], "$usuario@ en " . count($propios) . ' dominios propios' . ($fallidos ? ': ' . implode(', ', $fallidos) : ''));
}

echo "\n--- los ejemplos de la carga de Cardon ---\n";

foreach (['no@mail.com', 'nosesabe@hotmail.com', 'noquieredar@gamil.com', 'nosesabe@hotmial.com', 'sinmail@gmail.com',
             'null@gmail.com', 'a@a.com', 'mail@gmail.com', 'noquiere@yahoo.com', 'test@test.com', 'prueba@prueba.com.ar'] as $email) {
    $out = $cleanser->sanitize($email);
    check($out === EmailCleanser::INVALID_EMAIL, "$email -> " . var_export($out, true));
}

echo "\n--- mayúsculas y espacios ---\n";

foreach (['NO@Mail.com', '  NoSeSabe@Hotmail.com  ', 'Test@Gmail.com', 'mailto:no@mail.com'] as $email) {
    $out = $cleanser->sanitize($email);
    check($out === EmailCleanser::INVALID_EMAIL, var_export($email, true) . ' -> ' . var_export($out, true));
}

// ------------------------------------------------------------------ negativos
echo "\n--- los placeholders @noemail.com no cambian ---\n";

// INVALID_EMAIL tiene que seguir volviendo como INVALID_EMAIL para que se mapee a
// <documento>@noemail.com; si diera false se guardaría crudo y juntaría clientes distintos.
foreach (['noemail@noemail.com' => EmailCleanser::INVALID_EMAIL, 'NOEMAIL@NOEMAIL.COM' => EmailCleanser::INVALID_EMAIL,
             '123@noemail.com' => '123@noemail.com', '30123456@noemail.com' => '30123456@noemail.com'] as $email => $esperado) {
    $out = $cleanser->sanitize($email);
    check($out === $esperado, "$email -> " . var_export($out, true));
}

echo "\n--- usuarios reales que contienen un término de relleno quedan válidos ---\n";

$validos = [
    'ana@hotmail.com', 'nora@hotmail.com', 'noelia@yahoo.com.ar', 'mailen@hotmail.com', 'nadal@outlook.com',
    'testa@hotmail.com', 'sinatra@mail.com', 'ana.no@hotmail.com', 'aaron@hotmail.com', 'juan@notiene.com',
    'pepe@nomail.com', 'demo.ventas@empresa.com.ar', 'mail.juan@empresa.com',
];

foreach ($validos as $email) {
    $out = $cleanser->sanitize($email);
    check($out !== false && $out !== EmailCleanser::INVALID_EMAIL, "$email -> " . var_export($out, true));
}

echo "\n$passed OK, $failed FAIL\n";
exit($failed === 0 ? 0 : 1);
