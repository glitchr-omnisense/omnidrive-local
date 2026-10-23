<?php

namespace Omnidrive\Local;

use League\Flysystem\Local\LocalFilesystemAdapter;
use Omnidrive\Config;
use Omnidrive\Drive;
use Omnidrive\Flysystem\FlysystemDriveFactory;

/**
 * A directory of this machine, through league/flysystem-local:
 *
 *   (new LocalDriveFactory())->create(['root' => '/srv/app/var/uploads', 'public_url' => '/uploads']);
 *
 * Options: root (required), public_url, visibility, directory_visibility,
 * permissions (as every drive), lock (an exclusive lock while writing, true),
 * links ("disallow": a symbolic link met is an error; "skip": it is left out of
 * listings), lazy_root (the root made on the first write rather than at once).
 */
final class LocalDriveFactory extends FlysystemDriveFactory
{
    protected function populate(Config $c): void
    {
        $c->defaults([
            'omnidrive.factory_name' => 'local',
            'omnidrive.factory_title' => 'A directory of this machine',
            'omnidrive.required_options' => ['root'],
            'lock' => true,
            'links' => 'disallow',
            'lazy_root' => false,
        ]);
    }

    protected function build(Config $c, string $name): Drive
    {
        $root = rtrim((string) $c->string('root'), '/');
        $adapter = new LocalFilesystemAdapter(
            $root,
            $this->unixVisibility($c),
            $c->bool('lock', true) ? \LOCK_EX : 0,
            'skip' === strtolower((string) $c->string('links')) ? LocalFilesystemAdapter::SKIP_LINKS : LocalFilesystemAdapter::DISALLOW_LINKS,
            null,
            $c->bool('lazy_root'),
        );

        return $this->drive($name, $adapter, $c, '' === $root ? '/' : $root);
    }
}
