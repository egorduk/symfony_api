<?php

namespace Btc;

use Composer\Script\Event;

class ComposerBootstrap
{
    public static function binaries(Event $event)
    {
        if (!is_dir('bin')) {
            $event->getIO()->write(sprintf('The "bin" directory was not found in %s.', getcwd()));
            return;
        }
        $bins = [
            'githooks', 'archive'
        ];
        foreach ($bins as $binary) {
            if (@symlink($src = '../app/Resources/bin/' . $binary, $dst = 'bin/' . $binary) === false) {
                if (!file_exists($dst)) {
                    $event->getIO()->write(sprintf('Failed to symlink %s from %s.', $src, $dst));
                    return;
                }
                continue;
            }
            $event->getIO()->write(sprintf('Installed binary %s.', $dst));
        }
    }
}
