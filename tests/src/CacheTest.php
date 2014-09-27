<?php
namespace Metaphore\Tests;

use Metaphore\Cache;
use Metaphore\Store\MockStore;

class CacheTest extends \PHPUnit_Framework_TestCase
{
    /*** @var \Metaphore\Cacche */
    protected $cache;

    public function setUp()
    {
        $this->cache = new Cache(new MockStore());
    }

    public function testCachesValue()
    {
        $key = "gago5";
        $result = "Fernando Rubén Gago plays as a defensive midfielder for Boca Juniors and the Argentine team.";

        $actualResult = $this->cache->cache($key, $this->createFunc($result), 30);

        $this->assertSame($actualResult, $result);
        $this->assertSame($this->cache->get($key), $result);
    }

    public function testServesStaleValueIfOtherProcessIsGeneratingContent()
    {
        $key = "masche14";
        $resultNew = "Javier Alejandro Mascherano plays for FC Barcelona in La Liga and the Argentina national team.";
        $resultStale = "Javier Mascherano";

        $retVal = $this->cache->cache($key, $this->createFunc($resultStale), -1);

        $this->assertSame($retVal, $resultStale);

        // simulate lock (other process generating content)
        $this->cache->getLockManager()->acquire($key, 30);

        // try to cache new (but stale value should be returned as lock is acquired earlier)
        $ret_val = $this->cache->cache($key, $this->createFunc($resultNew), -1);

        $this->assertSame($retVal, $resultStale);

        // release lock and try to cache new value again
        $this->cache->getLockManager()->release($key);

        $retVal = $this->cache->cache($key, $this->createFunc($resultNew), -1);

        $this->assertSame($retVal, $resultNew);
    }

    protected function createFunc($result)
    {
        return (function () use ($result) {
            return $result;
        });
    }
}
