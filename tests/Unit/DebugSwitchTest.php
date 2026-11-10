<?php

namespace tests\unit;

/**
 * สวิตช์ debug บนเซิร์ฟเวอร์จริง (config/debug.php): เปิดได้เฉพาะเมื่อมีไฟล์รหัสลับ และส่งรหัสถูกต้อง
 */
class DebugSwitchTest extends \Codeception\Test\Unit
{
    private const KEY = 'test-secret-key-0123456789abcdef';
    private string $file;
    private ?string $backup = null;

    protected function _before()
    {
        $this->file = dirname(__DIR__, 2) . '/config/debug-local.php';
        if (is_file($this->file)) {
            $this->backup = file_get_contents($this->file);   // ไม่ทับไฟล์จริงของเครื่องนี้
        }
        $_GET = $_COOKIE = [];
    }

    protected function _after()
    {
        $this->backup !== null ? file_put_contents($this->file, $this->backup) : @unlink($this->file);
        $_GET = $_COOKIE = [];
    }

    private function enabled(): bool
    {
        return (require dirname(__DIR__, 2) . '/config/debug.php')();
    }

    private function key(string $k): void
    {
        file_put_contents($this->file, "<?php return " . var_export($k, true) . ";\n");
    }

    public function testOffWithoutKeyFile()
    {
        @unlink($this->file);
        $_GET['qr_debug'] = self::KEY;
        verify($this->enabled())->false();
    }

    public function testShortKeyNeverEnables()
    {
        $this->key('short');
        $_GET['qr_debug'] = 'short';
        verify($this->enabled())->false();
    }

    public function testRightKeyByQueryOrCookie()
    {
        $this->key(self::KEY);
        verify($this->enabled())->false();                 // ไม่ส่งรหัส
        $_GET['qr_debug'] = 'wrong-secret-key-0123456789abcdef';
        verify($this->enabled())->false();                 // รหัสผิด
        $_GET['qr_debug'] = ['array'];
        verify($this->enabled())->false();                 // ส่งมาเป็น array
        $_GET['qr_debug'] = self::KEY;
        verify($this->enabled())->true();
        $_GET = [];
        $_COOKIE['qr_debug'] = self::KEY;
        verify($this->enabled())->true();
    }

    public function testTrueEnablesForEveryone()
    {
        file_put_contents($this->file, "<?php return true;\n");
        verify($this->enabled())->true();
        file_put_contents($this->file, "<?php return 1;\n");   // ต้องเป็น true จริง ๆ เท่านั้น
        verify($this->enabled())->false();
    }

    public function testKeyFileIsNotCommitted()
    {
        exec('cd ' . escapeshellarg(dirname(__DIR__, 2)) . ' && git check-ignore -q config/debug-local.php', $o, $code);
        verify($code)->equals(0);
    }
}
