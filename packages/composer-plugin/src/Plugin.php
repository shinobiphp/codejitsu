<?php
declare(strict_types=1);
namespace Codejitsu\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

final class Plugin implements PluginInterface, EventSubscriberInterface
{
    public function activate(Composer $composer, IOInterface $io): void {}
    public function deactivate(Composer $composer, IOInterface $io): void {}
    public function uninstall(Composer $composer, IOInterface $io): void {}

    public static function getSubscribedEvents(): array
    {
        return [ScriptEvents::POST_INSTALL_CMD => 'rebuild', ScriptEvents::POST_UPDATE_CMD => 'rebuild'];
    }

    public function rebuild(Event $event): void
    {
        (new PackageInstaller())->rebuild(dirname($event->getComposer()->getConfig()->get('vendor-dir')));
    }
}
