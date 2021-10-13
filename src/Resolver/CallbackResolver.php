<?php

namespace PluginMaster\Foundation\Resolver;

use WP_Error;

class CallbackResolver
{

    public static function resolve($callback, $options = [])
    {

        $methodSeparator = $options['methodSeparator'] ?? '@';
        $namespace       = $options['namespace'] ?? '';
        $container       = $options['container'] ?? null;

        $object         = false;
        $callbackClass  = null;
        $callbackMethod = null;

        if (is_string($callback)) {

            $segments = explode($methodSeparator, $callback);

            $callbackClass  = class_exists($segments[0]) ? $segments[0] : $namespace.$segments[0];
            $callbackMethod = isset($segments[1]) ? $segments[1] : '__invoke';

        }

        if (is_array($callback)) {

            if (is_object($callback[0])) {
                $object        = true;
                $callbackClass = $callback[0];
            }

            if (is_string($callback[0])) {
                $callbackClass = class_exists($callback[0]) ? $callback[0] : $namespace.$callback[0];
            }

            $callbackMethod = isset($callback[1]) ? $callback[1] : '__invoke';

        }


        if (!$callbackClass || !$callbackMethod) {
            new WP_Error('not-found', "Controller Class or Method not found ");
            exit;
        }

        $instance = $object ? $callbackClass : ($container ? $container->get($callbackClass) : new $callbackClass());

        return [$instance, $callbackMethod];
    }

}
