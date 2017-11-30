<?php

namespace Fulcrum\Custom\PostType;

interface PostTypeContract
{
    /**
     * Register Custom Post Type
     *
     * @since 3.0.0
     *
     * @uses self::buildArgs() Builds up the needed args from defaults & configuration
     *
     * @return void
     */
    public function register();

    /**
     * Modify the columns for this custom post type.
     *
     * @since 3.0.0
     *
     * @param array $columns Array of Columns
     *
     * @return array
     */
    public function columnsFilter($columns);

    /**
     * Filter the data that shows up in the columns.
     *
     * @since 3.0.0
     *
     * @param string $columnName The name of the column to display.
     * @param int $postId The current post ID.
     *
     * @return null
     *
     * @throws ConfigurationException
     */
    public function columns_data($columnName, $postId);

    /**
     * Filter for making the columns sortable.
     *
     * @since  3.0.0
     *
     * @param array $sortableColumns Sortable columns
     *
     * @return array
     */
    public function make_columns_sortable($sortableColumns);

    /**
     * Sort columns by the configuration.
     *
     * @since 3.0.0
     *
     * @param $vars
     *
     * @return mixed
     */
    public function sort_columns_by($vars);

    /**
     * Handles adding (or removing) this CPT to/from the RSS Feed.
     *
     * @since 3.0.0
     *
     * @param array $queryVars Query variables from parse_request
     *
     * @return array
     */
    public function addOrRemoveToFromRssFeed($queryVars);

    /**
     * Get all of the supports.
     *
     * @since 3.0.0
     *
     * @return array
     */
    public function getTheSupports();
}
