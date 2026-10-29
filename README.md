# omnidrive/local

A directory of this machine as an [omnidrive](https://github.com/glitchr-omnisense/omnidrive) drive,
through league/flysystem-local.

```sh
composer require omnisense/omnidrive omnidrive/local
```

```php
use Omnidrive\Local\LocalDriveFactory;
use Omnidrive\Registry;

$drives = new Registry([new LocalDriveFactory()], [
    'uploads' => ['factory' => 'local', 'options' => ['root' => __DIR__.'/var/uploads', 'public_url' => '/uploads']],
]);
$drives->get('uploads')->write('avatars/1.png', $bytes);
$drives->get('uploads')->localPath('avatars/1.png');   // .../var/uploads/avatars/1.png
```

| Option | Default | |
|---|---|---|
| `root` | required | the directory behind the drive's paths |
| `lazy_root` | false | the root made on the first write rather than when the drive is built |
| `lock` | true | an exclusive lock while a file is written |
| `links` | `disallow` | a symbolic link met in a listing is an error; `skip` leaves it out |
| `permissions` | 0644/0600, 0755/0700 | the Unix mode of each visibility, for files and directories |
| `public_url`, `visibility`, `directory_visibility`, `retain_visibility` | | as every drive |

`capabilities()`: local, public addresses with `public_url`, no temporary address.

## Verified, and not

- Verified (2026-10-23, omnidrive's harness, PHP 8.4): the contract's tests on a temporary
  directory; the modes of each visibility as `permissions` gives them (octal strings too); a link
  refused, then skipped; the root made at once or lazily; the drive in plain PHP (`bare`).
- Not verified: Windows paths and permissions.

## Responsibility

Unofficial; not affiliated with The PHP League. Whoever runs this package answers for the files it writes and for the access a public address of the drive gives to them. MIT licence.
