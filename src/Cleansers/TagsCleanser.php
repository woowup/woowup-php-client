<?php
namespace WoowUp\Cleansers;

/**
 * Tags management cleanser
 *
 * Handles tag addition, removal, and validation in comma-separated tag strings.
 * Tags are metadata labels attached to users to track events or states.
 *
 * Business rules:
 * - Tags are comma-separated strings (e.g., "vip,telephone_cleaned,high_value")
 * - Duplicates are prevented
 * - Empty tags are filtered out
 * - Whitespace is trimmed from individual tags
 */
class TagsCleanser
{
    /**
     * Add a tag to existing tags string
     *
     * Prevents duplicates and handles empty/null cases
     *
     * @param string|null $currentTags Current tags (comma-separated or null)
     * @param string $newTag Tag to add
     * @return string|null Updated tags string or null if no tags
     */
    public function addTag(?string $currentTags, string $newTag): ?string
    {
        $newTag = trim($newTag);

        if ($newTag === '') {
            return $currentTags;
        }

        if ($currentTags === null || trim($currentTags) === '') {
            return $newTag;
        }

        if ($this->hasTag($currentTags, $newTag)) {
            return $currentTags;
        }

        return $currentTags . ',' . $newTag;
    }

    /**
     * Check if a specific tag exists in tags string
     *
     * @param string|null $tags Tags string
     * @param string $tag Tag to search for
     * @return bool True if tag exists
     */
    public function hasTag(?string $tags, string $tag): bool
    {
        if ($tags === null || trim($tags) === '') {
            return false;
        }

        return strpos($tags, $tag) !== false;
    }

    /**
     * Remove a specific tag from tags string
     *
     * @param string|null $tags Current tags
     * @param string $tagToRemove Tag to remove
     * @return string|null Updated tags or null if empty
     */
    public function removeTag(?string $tags, string $tagToRemove): ?string
    {
        if ($tags === null || trim($tags) === '') {
            return null;
        }

        $tagToRemove = trim($tagToRemove);
        $tagsArray = explode(',', $tags);
        $tagsArray = array_map('trim', $tagsArray);
        $tagsArray = array_filter($tagsArray, fn($t) => $t !== $tagToRemove && $t !== '');

        if (empty($tagsArray)) {
            return null;
        }

        return implode(',', $tagsArray);
    }

    /**
     * Normalize tags string
     *
     * - Removes duplicates
     * - Trims whitespace from each tag
     * - Filters out empty tags
     * - Sorts alphabetically for consistency
     *
     * @param string|null $tags Tags to normalize
     * @return string|null Normalized tags or null if empty
     */
    public function normalize(?string $tags): ?string
    {
        if ($tags === null || trim($tags) === '') {
            return null;
        }

        $tagsArray = explode(',', $tags);
        $tagsArray = array_map('trim', $tagsArray);
        $tagsArray = array_filter($tagsArray, fn($t) => $t !== '');
        $tagsArray = array_unique($tagsArray);
        sort($tagsArray);

        if (empty($tagsArray)) {
            return null;
        }

        return implode(',', $tagsArray);
    }
}