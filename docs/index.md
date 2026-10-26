# omnidrive/local

A directory of this machine as an [omniscient/omnidrive](https://github.com/glitchr-studio/omnidrive)
drive, through league/flysystem-local (`League\Flysystem\Local\LocalFilesystemAdapter`). No
server, no network: the drive's paths are files under its `root`, and `localPath()` gives them.

## Installation

```sh
composer require omniscient/omnidrive omnidrive/local
```

Requires PHP 8.2, `omniscient/omnidrive` (1.x) and `league/flysystem-local` (^3.0). Under Symfony
or Laravel, a local storage of the framework is already a drive through omnibridge
([Hosts](https://github.com/glitchr-studio/omnidrive/blob/1.x/docs/hosts.md)); this factory is for
plain PHP and for drives the framework does not configure.

## Credentials

None: the PHP process's own Unix user and its permissions on `root`.

## Options

`new LocalDriveFactory()`; factory name `local`.

| Option | Type | Default | Required | |
|---|---|---|---|---|
| `root` | string | - | yes | the directory behind the drive's paths (a trailing `/` removed; `''` is `/`) |
| `lazy_root` | bool | `false` | no | the root made on the first write, not when the drive is built |
| `lock` | bool | `true` | no | an exclusive lock (`LOCK_EX`) while a file is written |
| `links` | `disallow`, `skip` | `disallow` | no | a symbolic link met in a listing is an error, or left out |
| `permissions` | map | `{file: {public: 0644, private: 0600}, dir: {public: 0755, private: 0700}}` | no | the Unix mode of each visibility; octal strings accepted, a mode left out keeps its default |
| `public_url` | string or list | none | no | the prefix files are served from, or several (sharded) |
| `visibility` | `public`/`private` | `public` (the adapter's) | no | a write's visibility when it does not say |
| `directory_visibility` | `public`/`private` | `private` | no | the same for directories |
| `retain_visibility` | bool | `true` | no | whether a copy keeps its source's visibility |

## Capabilities

| Capability | |
|---|---|
| `local` (`localPath()`) | yes: `root` + the path |
| `publicUrls` | with `public_url` |
| `temporaryUrls` | no |
| visibility per file | yes: the file's Unix mode, by `permissions` |
| checksum | any algorithm `hash()` knows (the adapter hashes the file) |

## Behaviour

Every operation is the adapter's: PHP's file functions on `root`. The root is made when the drive
is built, with the `directory_visibility` mode, unless `lazy_root`. A write makes the
missing directories with the `directory_visibility` mode - `private` (`0700`) unless that option
says `public`, whatever the file's own visibility. `visibility()` reads the file's mode back against `permissions`. A listing follows no symbolic
link: `disallow` makes the listing fail on one, `skip` leaves it out. Paths climbing out of
`root` (`..`) are refused before any file function runs.

## Example

```php
<?php

require __DIR__.'/vendor/autoload.php';

use Omnidrive\Local\LocalDriveFactory;
use Omnidrive\Registry;
use Omnidrive\Visibility;

$drives = new Registry([new LocalDriveFactory()], [
    'uploads' => ['factory' => 'local', 'options' => [
        'root' => sys_get_temp_dir().'/site/public/uploads',
        'public_url' => '/uploads',
        'permissions' => ['file' => ['public' => '0664'], 'dir' => ['public' => '0775']],
        'links' => 'skip',
    ]],
]);
$uploads = $drives->get('uploads');

$uploads->write('avatars/1.png', "\x89PNG...", ['visibility' => Visibility::Public]);
$uploads->write('drafts/notes.txt', 'private notes', ['visibility' => 'private']);
symlink('/etc/hostname', sys_get_temp_dir().'/site/public/uploads/link');      // left out of listings

echo json_encode($uploads->capabilities()->toArray()), "\n";
echo $uploads->publicUrl('avatars/1.png'), ' ', $uploads->localPath('avatars/1.png'), "\n";
printf("%o %o %o\n", fileperms($uploads->localPath('avatars/1.png')) & 0777, fileperms($uploads->localPath('drafts/notes.txt')) & 0777, fileperms(dirname($uploads->localPath('avatars/1.png'))) & 0777);
echo implode(' ', array_map(static fn ($e) => $e->path, [...$uploads->list('')])), "\n";

unlink(sys_get_temp_dir().'/site/public/uploads/link');
$uploads->deleteDirectory('avatars');
$uploads->deleteDirectory('drafts');
```

```
{"local":true,"publicUrls":true,"temporaryUrls":false}
/uploads/avatars/1.png /tmp/site/public/uploads/avatars/1.png
664 600 700
avatars drafts
```

(run on 2026-10-25 in omnidrive's harness, PHP 8.4: the file `0664` as `permissions` asked, the
private one `0600`, the directory `0700` - the default `directory_visibility` is `private`; the
link left out of the listing)

## Errors

What PHP's file functions refuse (a permission, a full disk, a link with `links: disallow`) is a
`DriveException` whose message ends with PHP's own warning; a missing file read is a
`FileNotFoundException`; `temporaryUrl()` and `publicUrl()` without `public_url` are
`NotSupportedException`s. A missing `root` is an `InvalidConfigException` (`The "local" drive
needs: root.`).

## Verified, and not

Verified (2026-10-23 and 2026-10-25, omnidrive's harness, PHP 8.4): the contract's tests on a
temporary directory; the modes of each visibility as `permissions` gives them (octal strings
too); a link refused, then skipped; the root made at once or lazily; the drive in plain PHP
(`bare`). Not verified: Windows paths and permissions.

## Responsibility

An independent, unofficial client, not affiliated with The PHP League. Whoever runs it is responsible for the data it stores and the
access it gives (a public or temporary address serves the file to whoever holds it); see the core's
Responsibility section ([omniscient/omnidrive](https://github.com/glitchr-studio/omnidrive#responsibility)).
