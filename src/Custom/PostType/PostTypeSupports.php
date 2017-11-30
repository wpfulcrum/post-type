<?php

namespace Fulcrum\Custom\PostType;

use Fulcrum\Config\ConfigContract;

class PostTypeSupports
{
	/**
	 * Configuration parameters.
	 *
	 * @var ConfigContract
	 */
	protected $config;

	/**
	 * Default supports.
	 *
	 * @var array
	 */
	protected $defaults = [];

	/**
	 * Array of supports
	 *
	 * @var array
	 */
	protected $supports = [];

	/****************************
	 * Instantiate & Initialize
	 ***************************/

	/**
	 * PostTypeSupports constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param ConfigContract $config Runtime configuration parameters.
	 */
	public function __construct(ConfigContract $config)
	{
		$this->config = $config;
	}

	/**
	 * Build the supports argument.  If it is not configured, then grab all of the
	 * supports from the built-in 'post' post type.
	 *
	 * @since 3.0.0
	 *
	 * @param array $args Runtime configuration parameters.
	 *
	 * @return array
	 */
	public function buildSupports(array $args)
	{
		if (array_key_exists('supports', $args)) {
			$this->supports = $args['supports'];
			$this->addPageAttributes();
			return $this->supports;
		}

		$this->buildSupportsByConfiguration();
		return $this->supports;
	}

	/**
	 * Gets the array of supports for this post type.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	public function getSupports()
	{
		return $this->supports;
	}

	/*****************************************************
	 * Helpers
	 ***************************************************/

	/**
	 * Build the supports from the configuration.  The starting defaults are from the 'post' supports.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	protected function buildSupportsByConfiguration()
	{
		$this->supports = get_all_post_type_supports('post');

		if ($this->areAdditionalSupportsEnabled()) {
			$this->supports = array_merge($this->supports, $this->config->additionalSupports);
		}

		$this->filterOutExcludedSupports();

		$this->supports = array_keys($this->supports);

		$this->addPageAttributes();
	}

	/**
	 * Adds the 'page-attributes' support when required.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	protected function addPageAttributes()
	{
		if ($this->isPageAttributesSupportRequired()) {
			$this->supports[] = 'page-attributes';
		}
	}

	/**
	 * Filters out the unwanted (excluded) supports.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	protected function filterOutExcludedSupports()
	{
		$this->supports = array_filter($this->supports, function ($includeSupport) {
			return $includeSupport;
		});
	}

	/*****************************************************
	 * State Checkers
	 ***************************************************/

	/**
	 * Checks if the exclude_supports parameter is configured.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	protected function areAdditionalSupportsEnabled()
	{
		return $this->config->has('additionalSupports') &&
		       $this->config->isArray('additionalSupports');
	}

	/**
	 * Checks if the page-attributes support is required.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	protected function isPageAttributesSupportRequired()
	{
		if (!$this->isHierachical()) {
			return false;
		}

		return empty($this->config->args['supports']) ||
		       !in_array('page-attributes', $this->config->args['supports']);
	}

	/**
	 * Checks if this post type is hierarchical.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	protected function isHierachical()
	{
		return isset($this->config->args['hierarchical']) &&
		       $this->config->args['hierarchical'];
	}
}
