<?php

namespace PluginMaster\Foundation\Enqueue;

use PluginMaster\Contracts\Enqueue\EnqueueHandlerInterface;
use PluginMaster\Contracts\Enqueue\EnqueueInterface;
use PluginMaster\Contracts\Foundation\ApplicationInterface;

class EnqueueHandler implements EnqueueHandlerInterface
{
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
     * @param string $enqueueFile
     */
    public function loadEnqueueFile(string $enqueueFile): void
    {
        require $enqueueFile;
    }

    /**
     * initiate Enqueue
     */
    public function initEnqueue(EnqueueInterface $enqueue): void
    {
        $version = $this->appInstance->version();

        $hookBasedEnqueue = [];
        foreach ($enqueue->getData() as $enqueue) {

            $hook = $enqueue['hook'];

            if (isset($enqueue['type'])) {
                $hookBasedEnqueue[$hook]['inline'][] = [
                    'fn' => $enqueue['type'],
                    'param' => $enqueue['param']
                ];
            } else {

                $path = $this->formatPath($enqueue['path'], $enqueue['cdn'] ?? false);
                $id = ($enqueue['id'] ?? base64_encode($path));
                $dependency = $enqueue['dependency'] ?? [];

                $script = $enqueue['script'] ?? false;

                if ($script) {
                    $attribute = $enqueue['attributes'] ?? false;

                    if ($attribute && is_array($attribute)) {
                        $this->attributes[$id] = $attribute;
                    }

                    $hookBasedEnqueue[$hook]['script'][] = [
                        $id,
                        $path,
                        $dependency,
                        $enqueue['version'] ?? $version,
                        $enqueue['in_footer'] ?? false
                    ];
                } else {
                    $hookBasedEnqueue[$hook]['style'][] = [
                        $id,
                        $path,
                        $dependency,
                        $enqueue['version'] ?? $version,
                        $enqueue['media'] ?? 'all'
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
            }, 12);
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
     * @param string $id
     * @param string $objectName
     * @param array $data
     */
    public function localizeScript(string $id, string $objectName, array $data): void
    {
        wp_localize_script($id, $objectName, $data);
    }

    /**
     * @param string $data
     * @param array $option
     */
    public function inlineScript(string $data, array $option): void
    {
        $id = gettype($option) == 'string' ? $option : ($option['id'] ?? ($option['handle'] ?? 'pluginMaster_' . uniqid(
                )));
        wp_add_inline_script($id, $data, $option['position'] ?? 'after');
    }

    /**
     * @param string $data
     * @param string $handle
     */
    public function inlineStyle(string $data, string $handle): void
    {
        wp_add_inline_style($handle ?? 'pluginMaster_' . uniqid(), $data);
    }
}
