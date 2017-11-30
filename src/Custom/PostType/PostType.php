<?php

namespace Fulcrum\Custom\PostType;

use Fulcrum\Config\ConfigContract;
use Fulcrum\Support\Exceptions\ConfigurationException;
use Fulcrum\Config\Fulcrum;
use InvalidArgumentException;

class PostType implements PostTypeContract
{
    /**
     * Configuration parameters
     *
     * @var ConfigContract
     */
    protected $config;

    /**
     * Post type name (all lowercase & no spaces)
     *
     * @var string
     */
    protected $postType;

    /**
     * Instance of the Post Type Supports Handler
     *
     * @var Post_Type_Supports
     */
    protected $supports;

    /**
     * Internal flag if the labels are configured
     *
     * @var bool
     */
    private $_areLabelsConfigured = false;

    /**
     * Internal flag if the columns_data is configured
     *
     * @var bool
     */
    private $_isColumnsDataConfigured = false;

    /**
     * Internal flag if the sortable_data is configured
     *
     * @var bool
     */
    private $_isSortableColumnsConfigured = false;

    /**
     * Internal flag if the sort_columns_by is configured
     *
     * @var bool
     */
    private $_isSortColumnsByConfigured = false;

    /**
     * Internal flag if the query_vars has a post_type key
     *
     * @var bool
     */
    protected $queryVarsHasPostTypes = false;

    /****************************
     * Instantiate & Initialize
     ***************************/

    /**
     * Instantiate the Custom Post Type
     *
     * @since 3.0.0
     *
     * @param ConfigContract $config Runtime configuration parameters.
     * @param string $postTypeName Post type name (all lowercase & no spaces).
     * @param PostTypeSupports $supports Instance of the post type supports handler.
     *
     * @throws InvalidArgumentException
     */
    public function __construct(ConfigContract $config, $postTypeName, PostTypeSupports $supports)
    {
        $this->config   = $config;
        $this->postType = $postTypeName;
        $this->supports = $supports;

        if ($this->isStartingStateValid()) {
            $this->initConfig();
            $this->initEvents();
        }
    }

    /**
     * Remove this CPT from the post types upon object destruct
     *
     * @since 3.0.0
     *
     * @uses global $wp_post_type
     * @return null
     */
    public function __destruct()
    {
        global $wp_post_types;

        if (isset($wp_post_types[$this->postType])) {
            unset($wp_post_types[$this->postType]);
        }
    }

    /**
     * Setup Hooks & Filters
     *
     * @since 3.0.0
     *
     * @return null
     */
    protected function initEvents()
    {
        add_action('init', [$this, 'register']);

        $this->addColumnsFilter();

        $this->addColumnData();

        $this->initSorting();

        add_filter('request', [$this, 'addOrRemoveToFromRssFeed']);
    }

    /*****************************************************
     * Register Methods
     ***************************************************/

    /**
     * Register Custom Post Type
     *
     * @since 3.0.0
     *
     * @uses self::buildArgs() Builds up the needed args from defaults & configuration
     *
     * @return null
     */
    public function register()
    {
        register_post_type($this->postType, $this->buildArgs($this->config->args));
    }

    /**
     * Get all of the supports
     *
     * @since 3.0.0
     *
     * @return array
     */
    public function getTheSupports()
    {
        return $this->supports->getSupports();
    }

    /*****************************************************
     * Helper Methods
     ***************************************************/

    /**
     * Build the args for the register_post_type
     *
     * @since 3.0.0
     *
     * @param array $args Runtime configuration parameters.
     *
     * @return array
     */
    protected function buildArgs(array $args)
    {
        if (!$this->isLabelsConfigured($args)) {
            $args['labels'] = $this->buildLabels();
        }

        $args['supports'] = $this->supports->buildSupports($args);

        $this->convertTaxonomyIntoArray($args);

        return $args;
    }

    /*****************************************************
     * Taxonomy Methods
     ***************************************************/

    /**
     * Checks and, if necessary, converts the taxomony(ies) into an array.
     *
     * @since 3.0.0
     *
     * @param array $args
     *
     * @return array
     */
    protected function convertTaxonomyIntoArray(array &$args)
    {
        if (array_key_exists('taxonomies', $args) && !is_array($args['taxonomies'])) {
            $args['taxonomies'] = explode(',', $args['taxonomies']);
        }
    }

    /*****************************************************
     * Configuration Handlers
     ***************************************************/

    /**
     * Checks if the starting state is valid
     *
     * @since 3.0.0
     *
     * @throws InvalidArgumentException
     * @return bool
     */
    protected function isStartingStateValid()
    {
        if (!$this->postType) {
            throw new InvalidArgumentException(
                __('For Custom Post Type Configuration, the Post type cannot be empty', 'fulcrum')
            );
        }

        if (!$this->isConfigurationValid()) {
            throw new InvalidArgumentException(sprintf(
                __('For Custom Post Type Configuration, the config for [%s] cannot be empty.', 'fulcrum'),
                $this->postType
            ));
        }

        return true;
    }

    /**
     * Checks if $config is valid
     *
     * @since 3.0.0
     *
     * @return bool
     */
    protected function isConfigurationValid()
    {
        return !empty($this->config->all());
    }

    /**
     * Initialized Config
     *
     * @since 3.0.0
     *
     * @return null
     */
    protected function init_config()
    {
        if (!$this->config->has('addFeed') || !isset($this->config->addFeed) || true !== $this->config->addFeed) {
            $this->config->addFeed = false;
        }

        $this->_areLabelsConfigured = $this->config->isArray('args') && $this->config->isArray('args.labels');

        $this->_isColumnsDataConfigured = $this->config->isArray('columnsData');

        $this->_isSortableColumnsConfigured = $this->config->isArray('sortableColumns');

        $this->_isSortColumnsByConfigured = $this->config->isArray('sortColumnsBy');
    }

    /*****************************************************
     * Feed Methods
     ***************************************************/

    /**
     * Handles adding (or removing) this CPT to/from the RSS Feed
     *
     * @since 3.0.0
     *
     * @param array $queryVars Query variables from parse_request
     *
     * @return array $queryVars
     */
    public function addOrRemoveToFromRssFeed($queryVars)
    {
        if (!isset($queryVars['feed'])) {
            return $queryVars;
        }

        $this->addOrRemoveFeedHandler($queryVars);

        return $queryVars;
    }

    /**
     * Checks whether to add or remove the post type from feed. If yes, then it either adds or removes it.
     *
     * @since 3.0.0
     *
     * @param array $queryVars
     */
    protected function addOrRemoveFeedHandler(&$queryVars)
    {
        $postTypeIndex = false;

        if (!$this->isPostTypeInQueryVar($queryVars) && $this->queryVarsHasPostTypes) {
            $postTypeIndex = array_search($this->post_type, (array)$queryVars['post_type']);
        }

        if ($this->isSetToAddToFeed($postTypeIndex)) {
            return $this->addPostTypeToFeed($queryVars);
        }

        if ($this->isSetToRemoveFromFeed($postTypeIndex)) {
            return $this->removePostTypeFromFeed($queryVars, $postTypeIndex);
        }
    }

    /**
     * Add post type to the feed.
     *
     * @since 3.0.0
     *
     * @param array $queryVars
     *
     * @return void
     */
    protected function addPostTypeToFeed(array &$queryVars)
    {
        if (!$this->queryVarsHasPostTypes) {
            $queryVars['post_type'] = ['post', $this->postType];
        } else {
            $queryVars['post_type'][] = $this->postType;
        }
    }

    /**
     * Remove the post type from the feed.
     *
     * @since 3.0.0
     *
     * @param array $queryVars
     * @param bool|int $postTypeIndex
     *
     * @return void
     */
    protected function removePostTypeFromFeed(array &$queryVars, $postTypeIndex)
    {
        unset($queryVars['post_type'][$postTypeIndex]);

        $queryVars['post_type'] = array_values($queryVars['post_type']);
    }

    /**
     * Checks if this post type is in the `$queryVars['post_type']`.
     *
     * @since 3.0.0
     *
     * @param array $queryVars
     *
     * @return bool
     */
    protected function isPostTypeInQueryVar(array $queryVars)
    {
        if (!$this->doesQueryVarsHavePostTypes($queryVars)) {
            return false;
        }

        return in_array($this->post_type, (array)$queryVars['post_type']);
    }

    /**
     * Checks if the query_vars already has `post_type` key and it is an array.
     *
     * @since 3.0.0
     *
     * @param array $queryVars
     *
     * @return bool
     */
    public function doesQueryVarsHavePostTypes(array $queryVars)
    {
        $this->queryVarsHasPostTypes = array_key_exists('post_type', $queryVars) && is_array($queryVars['post_type']);

        return $this->queryVarsHasPostTypes;
    }

    /**
     * Checks if conditions are set to add the custom post type from the feed.
     *
     * @since 3.0.0
     *
     * @param bool|int $index
     *
     * @return bool
     */
    protected function isSetToAddToFeed($index)
    {
        return false === $index && $this->config->addFeed;
    }

    /**
     * Checks if conditions are set to remove the custom post type from the feed.
     *
     * @since 3.0.0
     *
     * @param bool|int $index
     *
     * @return bool
     */
    protected function isSetToRemoveFromFeed($index)
    {
        return $this->queryVarsHasPostTypes &&
            false !== $index &&
            !$this->config->addFeed;
    }

    /*****************************************************
     * Column Data Handlers
     ***************************************************/

    /**
     * Filter the data that shows up in the columns
     *
     * @since 3.0.0
     *
     * @param string $columnName The name of the column to display.
     * @param int $postId The current post ID.
     *
     * @return null
     *
     * @throws Configuration_Exception
     */
    public function columns_data($columnName, $postId)
    {
        $columnConfig = $this->getColumnConfig($columnName, $postId);
        if (false === $columnConfig) {
            return;
        }

        if ($this->isCallbackCallable($columnConfig['callback'])) {
            $response = call_user_func_array($columnConfig['callback'], $columnConfig['args']);
            if ($columnConfig['echo']) {
                echo $response;
            }
        }
    }

    /**
     * Modify the columns for this custom post type
     *
     * @since 3.0.0
     *
     * @param array $columns Array of Columns
     *
     * @return array Amended Array
     */
    public function columns_filter($columns)
    {
        foreach ($this->config->columnsFilter as $column => $value) {
            if ('cb' == $column && true == $value) {
                $columns['cb'] = '<input type="checkbox" />';
            } else {
                $columns[$column] = $value;
            }
        }

        return $columns;
    }

    /**
     * Check if the column name is valid for our configuration
     *
     * @since 3.0.0
     *
     * @param string $columnName
     *
     * @return bool
     */
    protected function isColumnNameValid($columnName)
    {
        /** @noinspection PhpIllegalArrayKeyTypeInspection */
        return $this->_isColumnsDataConfigured &&
            array_key_exists($columnName, $this->config->columns_data) &&
            is_array($this->config->columnsData[$columnName]) && !empty($this->config->columnsData[$columnName]) &&
            isset($this->config->columnsData[$columnName]['callback']) && !empty($this->config->columnsData[$columnName]['callback']);
    }

    /**
     * Get the config for the injected column name
     *
     * @since 3.0.0
     *
     * @param string $columnName
     * @param int $postId
     *
     * @return array|bool
     */
    protected function getColumnConfig($columnName, $postId)
    {
        if (!$this->isColumnNameValid($columnName)) {
            return false;
        }

        $columnConfig = array_merge(
            [
                'callback' => '',
                'echo'     => true,
                'args'     => [],
            ],
            $this->config->columnsData[$columnName]
        );

        $columnConfig['args'][] = $postId;

        return $columnConfig;
    }

    /**
     * Add columns filter when it is configured for use.
     *
     * @since 3.0.0
     *
     * @return void
     */
    protected function addColumnsFilter()
    {
        if ($this->config->has('columnsFilter') && $this->config->isArray('columnsFilter')) {
            add_filter("manage_{$this->postType}_posts_columns", [$this, 'columnsFilter']);
        }
    }

    /**
     * Add columns data when it is configured for use.
     *
     * @since 3.0.0
     *
     * @return void
     */
    protected function addColumnData()
    {
        if ($this->config->has('columnsData') && $this->config->isArray('columnsData')) {
            add_action("manage_{$this->postType}_posts_custom_column", [$this, 'columnsData'], 10, 2);
        }
    }

    /*****************************************************
     * Column Sorting Handlers
     ***************************************************/

    /**
     * Initialize the sorting features (i.e. to customize it).
     *
     * @since 3.0.0
     *
     * @return void
     */
    protected function initSorting()
    {
        if (!$this->_isSortableColumnsConfigured) {
            return;
        }

        add_filter("manage_edit-{$this->postType}_sortable_columns", [$this, 'makeColumnsSortable']);

//		add_filter( 'request', array( $this, 'sort_columns_by' ), 50 );
    }

    /**
     * Filter for making the columns sortable
     *
     * @since  3.0.0
     *
     * @param  array $sortableColumns Sortable columns
     *
     * @return array Amended $sortableColumns
     */
    public function makeColumnsSortable($sortableColumns)
    {
        foreach (array_keys($this->config['sortableSolumns']) as $key) {
            $sortableColumns[$key] = $key;
        }

        return $sortableColumns;
    }

    /**
     * Sort columns by the configuration
     *
     * @since 3.0.0
     *
     * @param $vars
     *
     * @return mixed
     */
    public function sort_columns_by($vars)
    {
        if (!isset($vars['post_type']) || $this->post_type !== $vars['post_type']) {
            return $vars;
        }

        //* TODO-Tonya Add code for sorting columns by
//        foreach( (array) $this->config['sort_columns_by'] as $key => $sc_vars) {
//            if ( isset( $vars['orderby'] ) && $sc_vars['meta_key'] == $vars['orderby'] ) {
//                // $vars = array_merge($vars, array(
//                //     'meta_key'  => $sc_vars['meta_key'],
//                //     'orderby'   => $sc_vars['orderby']
//                // ));
//            }
//        }

        return $vars;
    }

    /*****************************************************
     * Label Methods
     ***************************************************/

    /**
     * Build the labels for the register_post_type() $args
     *
     * @since 3.0.0
     *
     * @return array
     */
    protected function buildLabels()
    {
        $defaultLabels = [
            'name'               => _x($this->config->pluralName, 'post type general name', 'fulcrum'),
            'singular_name'      => _x($this->config->singularName, 'post type singular name', 'fulcrum'),
            'add_new'            => _x('Add New', $this->post_type, 'fulcrum'),
            'add_new_item'       => sprintf('%s %s', __('Add New', 'fulcrum'), $this->config->singularName),
            'edit_item'          => sprintf('%s %s', __('Edit', 'fulcrum'), $this->config->singularName),
            'new_item'           => sprintf('%s %s', __('New', 'fulcrum'), $this->config->singularName),
            'view_item'          => sprintf('%s %s', __('View', 'fulcrum'), $this->config->singularName),
            'search_items'       => sprintf('%s %s', __('Search', 'fulcrum'), $this->config->pluralName),
            'not_found'          => sprintf(__('No %s found', 'fulcrum'), strtolower($this->config->singularName)),
            'not_found_in_trash' => sprintf(__('No %s found in Trash', 'fulcrum'), strtolower($this->config->pluralName)),
            'parent_item_colon'  => '',
            'all_items'          => sprintf('%s %s', __('All', 'fulcrum'), $this->config->pluralName),
            'menu_name'          => _x($this->config->pluralName, 'admin menu', 'fulcrum'),
        ];

        return $this->_areLabelsConfigured
            ? array_merge($defaultLabels, $this->config->args['labels'])
            : $defaultLabels;
    }

    /**
     * Convert the post type from a slug to a name
     *
     * @since 3.0.0
     *
     * @return string
     */
    protected function convertPostTypeToName()
    {
        if ($this->isLabelsKeyConfigured('name')) {
            return $this->config->args['labels']['name'];
        }

        $name = str_replace('-', ' ', $this->postType);

        return ucwords($name);
    }

    /**
     * Get the label name
     *
     * @since 3.0.0
     *
     * @return string
     */
    protected function getLabelName()
    {
        return $this->config->has('singularName')
            ? $this->config->singularName
            : $this->convertPostTypeToName();
    }

    /**
     * Check if the given $key is in the labels configuration array and has a value
     *
     * @since 3.0.0
     *
     * @param string $key Key in the labels configuration array to check
     *
     * @return bool
     */
    protected function isLabelsKeyConfigured($key)
    {
        return $this->_areLabelsConfigured && isset($this->config->args['labels'][$key]) &&
            !empty($this->config->args['labels'][$key]);
    }

    /*****************************************************
     * State Checkers
     ***************************************************/

    /**
     * Checks if the labels are configured.
     *
     * @since 3.0.0
     *
     * @param array $args Array of arguments.
     *
     * @return bool
     */
    protected function isLabelsConfigured(array $args)
    {
        return array_key_exists('labels', $args) && !empty($args['labels']);
    }

    /**
     * Checks if the callback is callback
     *
     * @since 3.0.0
     *
     * @param string $callback
     *
     * @return bool
     * @throws ConfigurationException
     */
    protected function isCallbackCallable($callback)
    {
        if (is_callable($callback)) {
            return true;
        }

        throw new ConfigurationException(
            sprintf(
                __('The callback [%s], for the custom post type [%s], was not found, as call_user_func_array() expects a valid callback function/method.', 'fulcrum'),
                $callback,
                $this->postType
            )
        );
    }
}
