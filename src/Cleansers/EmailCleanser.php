<?php

namespace WoowUp\Cleansers;

use WoowUp\Cleansers\Formatters\EmailFormatter;
use WoowUp\Cleansers\Validators\GenericEmailValidator;
use WoowUp\Cleansers\Validators\LengthValidator;
use WoowUp\Cleansers\Validators\RepeatedValidator;
use WoowUp\Cleansers\Validators\SequenceValidator;

class EmailCleanser
{
	const GENERIC_TLDS    = ["com", "net", "org", "info", "edu", "gov", "mil"];
	const GEOGRAPHIC_TLDS = ["ar", "es", "co", "pe", "bo", "br", "fr", "do", "co.uk"];

    const GMAIL_DOMAINS = [
        'gmail',
        'gamil', 'gmial', 'gmai', 'gmal', 'gnail', 'gmaul', 'gmaol', 'gmaik', 'gmaio',
        'gmeil', 'gmeel', 'gmel',
        'gmaill', 'gmil', 'ggmail', 'gmmail', 'gmailm',
        'gemail', 'gaiml', 'gail', 'gmailcom', 'gmailcomcom',
    ];

    // Dominios conocidos que NO deben mezclarse con Gmail
    // Si detectamos múltiples dominios en el mismo email, no corregimos a Gmail
    const KNOWN_DOMAINS = [
        'hotmail', 'hot', 'outlook', 'yahoo', 'live', 'msn', 'aol',
        'icloud', 'me', 'mac', 'protonmail', 'proton', 'zoho',
    ];

    /**
     * @var EmailFormatter
     */
    private $formatter;

    /**
     * @var array Array of ValidatorInterface instances
     */
    private $validators;

    private $emailUser;
    private $emailDomain;

	public function __construct()
	{
        $this->formatter = new EmailFormatter();
        $this->validators = [
            new LengthValidator(6, 30),
            new RepeatedValidator(8, false),
            new SequenceValidator(7, false),
            new GenericEmailValidator(),
        ];
        $this->emailDomain = null;
	    $this->emailUser   = null;
    }

	public function sanitize($email)
	{
        // Normalizamos el tipo de entrada 
        if (!is_string($email) && !is_numeric($email)) {
            return false;
        }

        $email = (string) $email;
        $email = trim($email);

        if ($email === '') {
            return false;
        }

        $this->extractEmailParts($email);

        // Si no pudimos extraer partes válidas, el email es inválido
        if ($this->emailUser === null || $this->emailDomain === null) {
            return false;
        }

        $isGmail = ($this->emailDomain === '@gmail.com');

        if ($isGmail) {
            // Si es Gmail (o variante mal escrita), aplicar limpieza profunda
            $this->emailUser = mb_strtolower(trim((string) $this->emailUser));
            $cleanedUserEmail = $this->formatter->clean((string) $this->emailUser);

            if ($cleanedUserEmail === '') {
                return false;
            }

            // Guardamos el user ya formateado aunque luego falle validación, para inspección
            $this->emailUser = $cleanedUserEmail;

            // Validar el email user limpio con todos los validadores
            foreach ($this->validators as $validator) {
                if (!$validator->validate($cleanedUserEmail)) {
                    return false;
                }
            }

            $sanitizedEmail = $cleanedUserEmail . '@gmail.com';
        } else {
            $sanitizedEmail = $this->prettify($email);
            $this->emailUser = mb_strtolower(trim((string) $this->emailUser));
        }

        return $sanitizedEmail;
	}

	public function validate($email)
	{
		return (filter_var($email, FILTER_VALIDATE_EMAIL) !== false);
	}

	public function prettify($email)
	{
		return utf8_encode(mb_strtolower(trim($email)));
	}

	protected function buildPopularTlds()
	{
		$popularTlds = array_merge(self::GENERIC_TLDS, self::GEOGRAPHIC_TLDS);
		foreach (self::GENERIC_TLDS as $genericTld) {
			foreach (self::GEOGRAPHIC_TLDS as $geoTld) {
				$popularTlds[] = $genericTld . "." . $geoTld;
			}
		}

		return $popularTlds;
	}

    protected function extractEmailParts(string $email): void
    {
        $email = trim($email);

        if ($email === '') {
            $this->emailUser   = null;
            $this->emailDomain = null;
            return;
        }

        $lowerEmail = mb_strtolower($email);
        $atPos = strpos($email, '@');

        // Si hay @, extraemos la parte del dominio
        $domainPart = ($atPos !== false) ? substr($lowerEmail, $atPos) : $lowerEmail;

        // 1) Verificar si hay múltiples dominios conocidos mezclados
        // Si detectamos Gmail + otro dominio conocido, no corregimos (dejamos tal cual)
        // Esto evita casos como: valentina@gmailhotmail.com, valentiana@yahoogmail.com, etc.
        $foundGmail = false;
        $foundOtherDomain = false;

        // Buscar Gmail (o variantes)
        foreach (self::GMAIL_DOMAINS as $gmailDomain) {
            if (strpos($domainPart, $gmailDomain) !== false) {
                $foundGmail = true;
                break;
            }
        }

        // Buscar otros dominios conocidos
        if ($foundGmail) {
            foreach (self::KNOWN_DOMAINS as $knownDomain) {
                if (strpos($domainPart, $knownDomain) !== false) {
                    $foundOtherDomain = true;
                    break;
                }
            }
        }

        // Si hay Gmail Y otro dominio conocido mezclados, dejarlo tal cual
        if ($foundGmail && $foundOtherDomain) {
            if ($atPos !== false) {
                $this->emailUser   = substr($email, 0, $atPos);
                $this->emailDomain = substr($email, $atPos);
            } else {
                $this->emailUser   = null;
                $this->emailDomain = null;
            }
            return;
        }

        // 2) Intentamos detectar dominios de Gmail mal escritos o variantes
        // Solo si NO hay otros dominios mezclados
        foreach (self::GMAIL_DOMAINS as $knownDomain) {
            $pos = strpos($lowerEmail, $knownDomain);
            if ($pos !== false) {
                if ($atPos !== false && $atPos < $pos) {
                    // Caso típico: usuario@gmial.com, usuario@gmail.com.com, etc.
                    $this->emailUser = substr($email, 0, $atPos);
                } else {
                    // Caso sin @ pero con "gmail"/typo en el string: usuariogmial.com, usuario gmail com
                    $this->emailUser = substr($email, 0, $pos);
                }

                $this->emailDomain = '@gmail.com';
                return;
            }
        }

        // 3) Si no es Gmail ni variante, usamos el @ "normal" si existe
        if ($atPos !== false) {
            $this->emailUser   = substr($email, 0, $atPos);
            $this->emailDomain = substr($email, $atPos);
            return;
        }

        // 4) Si no encontramos nada reconocible, marcamos como inválido
        $this->emailUser   = null;
        $this->emailDomain = null;
    }

    public function getEmailUser(): ?string
    {
        return $this->emailUser;
    }

    public function getEmailDomain(): ?string
    {
        return $this->emailDomain;
    }

}
