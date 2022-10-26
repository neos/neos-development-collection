<?php
declare(strict_types=1);

use Doctum\Doctum;
use Doctum\RemoteRepository\GitHubRemoteRepository;
use Symfony\Component\Finder\Finder;

$iterator = Finder::create()
    ->files()
    ->name('*.php')
    ->path('/Classes/')
    ->in(__DIR__);

return new Doctum($iterator, [
    'title' => 'Neos CMS',
    'base_url' => 'https://neos.github.io/',
    'favicon' => 'https://www.neos.io/favicon-32x32.png',
    'language' => 'en',
    'remote_repository' => new GitHubRemoteRepository('neos/neos-development-collection', __DIR__),
    'footer_link' => [
        'href' => 'https://www.neos.io',
        'rel' => 'noreferrer noopener',
        'target' => '_blank',
        'before_text' => 'Learn more about the',
        'link_text' => 'Neos Content Application Platform',
        'after_text' => 'if you like!',
    ]
]);
