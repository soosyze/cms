<?php

class RouteValue extends \Soosyze\Components\Validator\Rule
{
    protected function messages()
    {
        return [
            'must' => 'The value of :label must be a route.',
            'not'  => 'The value of :label should not be a route.'
        ];
    }

    protected function test($key, $value, $arg, $not = true)
    {
        /**
         * @var \Core
         */
        $app = \Core::getInstance();

        $uri        = \Soosyze\Components\Http\Uri::create($value);
        $linkSource = $app->get('router')->parseQueryFromRequest(
            $app->getRequest()->withUri($uri)
        );

        $linkSource = $app->get('alias')->getSource($linkSource, $linkSource);

        $uriSource = \Soosyze\Components\Http\Uri::create($linkSource);

        $isRoute = $app->get('router')->parse(
            $app->getRequest()
                ->withUri($uriSource->withQuery('q=' . $uriSource->getPath()))
                ->withMethod('get')
        );

        if (!$isRoute && $not) {
            $this->addReturn($key, 'must');
        }
    }
}

class RouteOrUrlValue extends \RouteValue
{
    protected function messages()
    {
        return [
            'must' => 'The value of :label must be a link or route.',
            'not'  => 'The value of :label should not be a link or route.'
        ];
    }

    protected function test($key, $value, $arg, $not = true)
    {
        $isRoute = !(new \RouteValue())
            ->hydrate('route', $key, $arg, $not)
            ->execute($value)
            ->hasErrors();
        $isLink = !(new \Soosyze\Components\Validator\Rules\Url())
            ->hydrate('url', $key, $arg, $not)
            ->execute($value)
            ->hasErrors();

        if (!($isRoute || $isLink) && $not) {
            $this->addReturn($key, 'must');
        }
    }
}

Soosyze\Components\Validator\Validator::addTestGlobal('route', new \RouteValue());
Soosyze\Components\Validator\Validator::addTestGlobal('route_or_url', new \RouteOrUrlValue());
