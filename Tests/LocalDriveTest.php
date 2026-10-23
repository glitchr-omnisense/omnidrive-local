<?php

namespace Omnidrive\Local\Tests;

use Omnidrive\Drive;
use Omnidrive\Exception\InvalidConfigException;
use Omnidrive\Local\LocalDriveFactory;
use Omnidrive\Testing\DriveContractTests;
use Omnidrive\Visibility;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\TestCase;

final class LocalDriveTest extends TestCase
{
    use DriveContractTests;

    private string $root = '';

    protected function drive(): Drive
    {
        return (new LocalDriveFactory())->create(['root' => $this->root(), 'public_url' => '/uploads'], 'uploads');
    }

    private function root(): string
    {
        return $this->root = '' !== $this->root ? $this->root : sys_get_temp_dir().'/omnidrive-local-'.bin2hex(random_bytes(4));
    }

    #[After]
    protected function removeTheRoot(): void
    {
        if ('' !== $this->root && is_dir($this->root)) {
            exec('rm -rf '.escapeshellarg($this->root));
        }
    }

    public function testTheRootIsRequiredAndMadeAtOnceUnlessLazy(): void
    {
        $lazy = $this->root().'/lazy';
        (new LocalDriveFactory())->create(['root' => $lazy, 'lazy_root' => 'true']);
        self::assertDirectoryDoesNotExist($lazy);
        (new LocalDriveFactory())->create(['root' => $lazy]);
        self::assertDirectoryExists($lazy);

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('The "local" drive needs: root.');
        (new LocalDriveFactory())->create();
    }

    public function testTheModesOfEachVisibilityAreThePermissionsOption(): void
    {
        $drive = (new LocalDriveFactory())->create(['root' => $this->root(), 'permissions' => ['file' => ['public' => '0640', 'private' => 0600], 'dir' => ['public' => '0750']], 'directory_visibility' => 'public']);
        $drive->write('shared/a.txt', 'a', ['visibility' => Visibility::Public]);
        $drive->write('shared/b.txt', 'b', ['visibility' => 'private']);
        clearstatcache();

        self::assertSame(0640, fileperms($this->root.'/shared/a.txt') & 0777);
        self::assertSame(0600, fileperms($this->root.'/shared/b.txt') & 0777);
        self::assertSame(0750, fileperms($this->root.'/shared') & 0777);
        self::assertSame(Visibility::Public, $drive->visibility('shared/a.txt'));
    }

    public function testASymbolicLinkIsRefusedOrSkippedAsTold(): void
    {
        $root = $this->root();
        $drive = (new LocalDriveFactory())->create(['root' => $root]);
        $drive->write('real.txt', 'real');
        symlink($root.'/real.txt', $root.'/link.txt');

        $skipping = (new LocalDriveFactory())->create(['root' => $root, 'links' => 'skip']);
        self::assertSame(['real.txt'], array_map(static fn ($e) => $e->path, [...$skipping->list()]));

        $this->expectException(\Omnidrive\Exception\DriveException::class);
        iterator_to_array($drive->list());
    }

    public function testTheDriveSaysItIsLocalAndWhereItsFilesAre(): void
    {
        $drive = $this->drive();

        self::assertSame(['local' => true, 'publicUrls' => true, 'temporaryUrls' => false], $drive->capabilities()->toArray());
        self::assertSame($this->root.'/avatars/1.png', $drive->localPath('/avatars/1.png'));
        self::assertSame('/uploads/avatars/1.png', $drive->publicUrl('avatars/1.png'));
        self::assertFalse((new LocalDriveFactory())->create(['root' => $this->root])->capabilities()->publicUrls);
    }
}
