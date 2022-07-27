<?php

namespace PluginMaster\Foundation\Enqueue;


use PluginMaster\Contracts\Enqueue\EnqueueHandlerInterface;
use PluginMaster\Contracts\Foundation\ApplicationInterface;

class EnqueueHandler implements EnqueueHandlerInterface
{
    /**
     * @var array
     */
    public array $enqueueData = [];

    /**
     * @var array
     */
    public array $attributes = [];

    /**
     * @var ApplicationInterface
     */
    protected ApplicationInterface $appInstance;


    public function setAppInstance(ApplicationInterface $app): self
    {
        $this->appInstance = $app;
        return $this;
    }

    /**
     * @param  string  $enqueueFile
     */
    public function loadEnqueueFile(string $enqueueFile): void
    {
        require $enqueueFile;
    }

    /**
     * @param  array  $config
     */
    public function register(array $config): void
    {
        $this->enqueueData[] = $config;
    }

    /**
     * initiate Enqueue
     */
    public function initEnqueue(): void
    {
        $version = $this->appInstance->version();

        $hookBasedEnqueue = [];
        foreach ($this->enqueueData as $enqueue) {
            if (isset($enqueue['type'])) {
                $hookBasedEnqueue[$enqueue['hook']]['inline'][] = [
                    'fn' => $enqueue['type'],
                    'param' => $enqueue['param']
                ];
            } else {
                $path = $this->formatPath($enqueue['path'], $enqueue['cdn'] ?? false);
                if (gettype($enqueue['options']) == 'string') {
                    $id = $enqueue['options'];
                    $dependency = [];
                } else {
                    $id = ($enqueue['options']['id'] ?? ($enqueue['options']['handle'] ?? base64_encode($path)));
                    $dependency = $enqueue['options']['dependency'] ?? ($enqueue['options']['deps'] ?? []);
                }

                $script = $enqueue['script'] ?? false;

                if ($script) {
                    $attribute = $enqueue['options']['attributes'] ?? false;

                    if ($attribute && is_array($attribute)) {
                        $this->attributes[$id] = $attribute;
                    }

                    $hookBasedEnqueue[$enqueue['hook']]['script'][] = [
                        $id,
                        $path,
                        $dependency,
                        $version,
                        $enqueue['in_footer'] ?? false
                    ];
                } else {
                    $hookBasedEnqueue[$enqueue['hook']]['style'][] = [
                        $id,
                        $path,
                        $dependency,
                        $version,
                        $options['media'] ?? 'all'
                    ];
                }
            }
        }

        foreach ($hookBasedEnqueue as $hook => $enqueueData) {
            add_action($hook, function () use ($enqueueData) {
                foreach ($enqueueData['inline'] ?? [] as $enqueue) {
                    $this->{$enqueue['fn']}(...$enqueue['param']);
                }

                foreach ($enqueueData['script'] ?? [] as $enqueue) {
                    wp_enqueue_script(...$enqueue);
                }

                foreach ($enqueueData['style'] ?? [] as $enqueue) {
                    wp_enqueue_style(...$enqueue);
                }
            });
        }

        add_filter('script_loader_tag', [$this, 'processAttributes'], 59, 3);
    }

    private function formatPath(string $path, bool $cdn): string
    {
        return $cdn ? $path : $this->appInstance->asset($path);
    }

    /**
     * add custom attribute to script tag
     *
     * @param $tag
     * @param $handle
     * @param $source
     * @return string
     */
    public function processAttributes($tag, $handle, $source): string
    {
        if (isset($this->attributes[$handle])) {
            $attribute = $this->attributes[$handle];
            $attribute['src'] = $source;
            $attribute['id'] = $handle;
            return sprintf("<script %s></script>\n", wp_sanitize_script_attributes($attribute));
        }

        return $tag;
    }

    /**
     * @param  string  $id
     * @param  string  $objectName
     * @param  array  $data
     */
    public function localizeScript(string $id, string $objectName, array $data): void
    {
        wp_localize_script($id, $objectName, $data);
    }

    /**
     * @param  string  $data
     * @param  array  $option
     */
    public function inlineScript(string $data, array $option): void
    {
        $id = gettype($option) == 'string' ? $option : ($option['id'] ?? ($option['handle'] ?? 'pluginMaster_'.uniqid(
                )));
        wp_add_inline_script($id, $data, $option['position'] ?? 'after');
    }

    /**
     * @param  string  $data
     * @param  string  $handle
     */
    public function inlineStyle(string $data, string $handle): void
    {
        wp_add_inline_style($handle ?? 'pluginMaster_'.uniqid(), $data);
    }
}
