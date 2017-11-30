<?php

if (!function_exists('fulcrum_get_all_supports_for_post_type')) {
    /**
     * Get all of the supports for the given post type.
     *
     * @since 3.0.0
     *
     * @param string $type Post type to fetch the supports for.
     * @param bool|null $keysOnly Flag to indicate whether to return only the supports.
     *
     * @return array
     */
    function fulcrum_get_all_supports_for_post_type($type, $keysOnly = null)
    {
        $enabled_post_types = get_all_post_type_supports($type);

        if (true === $keysOnly) {
            return array_keys($enabled_post_types);
        }

        return $enabled_post_types;
    }
}

if (!function_exists('fulcrum_get_all_post_types')) {
    /**
     * Gets all the of the "post" types, which includes Custom Post
     * Types and the builtin 'post'.
     *
     * @since 3.0.0
     *
     * @param bool|null $includeBuiltinPost When true, include the builtin 'post'; else, include only the custom post types.
     *
     * @return array
     */
    function fulcrum_get_all_post_types($includeBuiltinPost = null)
    {
        $customPostTypes = get_post_types(
            [
                '_builtin' => false,
            ]
        );

        if (true === $includeBuiltinPost) {
            $customPostTypes['post'] = 'post';
        }

        return $customPostTypes;
    }
}
