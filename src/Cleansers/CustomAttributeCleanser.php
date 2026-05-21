<?php
namespace WoowUp\Cleansers;

/**
 * Custom attribute name normalization
 *
 * Matches WoowUp server's bugSlugify: deleteAccents + lowercase + spaces/dashes → underscore.
 * Use this so attribute names in client code resolve to the same key that WoowUp stores.
 */
class CustomAttributeCleanser
{
    /**
     * Normalize a custom attribute name to its canonical form.
     *
     * "Baño" → "bano", "Colección" → "coleccion", "nombre-comp" → "nombre_comp"
     *
     * @param string $name Raw attribute name
     * @return string Normalized name
     */
    public function normalizeName(string $name): string
    {
        $name       = trim($name);
        $normalized = \Normalizer::normalize($name, \Normalizer::FORM_D) ?: $name;
        $noAccents  = preg_replace('/\p{Mn}/u', '', $normalized) ?? $name;
        return str_replace([' ', '-'], '_', mb_strtolower($noAccents));
    }

    /**
     * Normalize all keys of a custom_attributes array.
     *
     * Renames keys without touching values. When two keys collapse to the same
     * normalized form, the last one wins (same behaviour as WoowUp server).
     *
     * @param array $attributes Associative array keyed by attribute name
     * @return array Same values, normalized keys
     */
    public function normalizeKeys(array $attributes): array
    {
        $result = [];
        foreach ($attributes as $name => $value) {
            $trimmed = trim((string) $name);
            if ($trimmed === '') {
                continue;
            }
            $result[$this->normalizeName($trimmed)] = $value;
        }
        return $result;
    }
}
