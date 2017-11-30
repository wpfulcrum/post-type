<?php

namespace Fulcrum\Custom\PostType;

use Fulcrum\Foundation\ServiceProvider\Provider;

class PostTypeProvider extends Provider
{
    /**
     * Flag to indicate whether to skip the queue and register directly into the Container.
     *
     * @var bool
     */
    protected $skipQueue = true;

    /**
     * Get the concrete based upon the configuration supplied.
     *
     * @since 3.0.0
     *
     * @param array $config Runtime configuration parameters.
     * @param string $uniqueId Container's unique key ID for this instance.
     *
     * @return array
     */
    public function getConcrete(array $config, $uniqueId = '')
    {
        return [
            'autoload' => $config['autoload'],
            'concrete' => function ($container) use ($config) {
                $configObj = $this->instantiateConfig($config);

                return new PostType(
                    $configObj,
                    $config['postTypeName'],
                    new PostTypeSupports($configObj)
                );
            },
        ];
    }

    /**
     * Flush rewrite rules for custom post type.
     *
     * @since 3.0.0
     *
     * @return void
     */
    public function flushRewriteRules()
    {
        foreach ($this->uniqueIds as $uniqueId) {
            $this->fulcrum[$uniqueId]->register();
        }

        flush_rewrite_rules();
    }

    /**
     * Get the default structure for the concrete.
     *
     * @since 3.0.0
     *
     * @return array
     */
    protected function getConcreteDefaultStructure()
    {
        return [
            'autoload'                => false,
            'postTypeName'            => '',
            'config'                  => [],
            'enablePermalinkHandlers' => false,
            'permalinkHandlers'       => [],
        ];
    }
}
