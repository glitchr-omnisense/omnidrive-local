<?php

namespace Omnidrive\Local\Tests;

use Omnisense\Contracts\Policy\Permission;
use Omnisense\Contracts\Policy\UsagePolicy;
use PHPUnit\Framework\TestCase;

/** Its usage policy, as data: no third party of its own: the drive is the site's own directory or a server it names, so nothing is limited. */
final class PolicyTest extends TestCase
{
    public function testThePackageDeclaresItsPolicy(): void
    {
        $policy = UsagePolicy::fromFile(__DIR__.'/../policy.json');

        self::assertSame('local', $policy->provider, 'named after the factory');
        self::assertSame([], $policy->rateLimits, 'no service of the package\'s: no limit');
        self::assertSame(Permission::Allowed, $policy->rights->storage, 'the files are the site\'s own');
        self::assertFalse($policy->requirements->key);
        self::assertSame('2026-11-10', $policy->provenance->verifiedAt);
        self::assertMatchesRegularExpression('~"omnisense": \{\s*"policy": "policy\.json"~', (string) file_get_contents(__DIR__.'/../composer.json'), 'composer.json names it');
    }
}
