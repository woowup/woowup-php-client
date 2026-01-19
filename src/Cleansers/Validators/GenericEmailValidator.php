<?php

namespace WoowUp\Cleansers\Validators;

class GenericEmailValidator implements ValidatorInterface
{
    private const GENERIC_EMAILS = [
        'correo',
        'notiene',
        'nocontacto',
        'unavailable',
        'noautoriz',
        'example',
        'ejemplo',
        'facturacion',
        'factura',
        'noemail',
        'prueba',
        'nomail',
        'notmail',
        'ninguno',
        'nosustent',
        'noregistra',
        'nomaneja',
        'nopresenta',
        'test',
        'user',
        'nombre',
        'apellido',
        'usuario',
        'email',
        'nocuenta',
        'sinmail',
        'noposee',
        'notengo',
        'noreply',
    ];

    public function validate(string $email): bool
    {
        return !array_filter(
            self::GENERIC_EMAILS,
            fn (string $term): bool => stripos($email, $term) !== false
        );
    }
}
