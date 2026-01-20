<?php

namespace WoowUp\Cleansers\Validators;

/**
 * Validates that email doesn't contain generic/invalid terms.
 * 
 * Rejects emails containing terms like 'test', 'example', 'noemail', etc.
 */
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

    /**
     * Validates that email doesn't contain generic/invalid terms.
     * 
     * @param string $email The email to validate
     * @return bool true if valid (no generic terms found), false otherwise
     */
    public function validate(string $email): bool
    {
        return !array_filter(
            self::GENERIC_EMAILS,
            fn (string $term): bool => stripos($email, $term) !== false
        );
    }
}
