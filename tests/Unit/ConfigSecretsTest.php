<?php

namespace tests\unit;

/**
 * ความลับ (รหัสผ่าน, client secret, cookie key) ต้องอยู่ใน config/*-local.php ที่ไม่ถูก commit เท่านั้น
 * test นี้ล้มทันทีถ้าไฟล์ config ที่ git ติดตามอยู่มีค่าความลับ
 */
class ConfigSecretsTest extends \Codeception\Test\Unit
{
    /** ค่าทดสอบที่อนุญาตใน config/test.php */
    private const ALLOWED = ['', 'test', 'x'];

    private function trackedConfigFiles(): array
    {
        $root = dirname(__DIR__, 2);
        $out = shell_exec('cd ' . escapeshellarg($root) . ' && git ls-files config 2>/dev/null');
        $files = array_filter(explode("\n", (string)$out));
        verify($files)->notEmpty('git ls-files config returned nothing');
        return array_map(fn($f) => "$root/$f", $files);
    }

    public function testNoSecretsInTrackedConfig()
    {
        foreach ($this->trackedConfigFiles() as $file) {
            $src = file_get_contents($file);
            preg_match_all("/'([A-Za-z.]*(?:password|[sS]ecret|cookieValidationKey|apiKey))'\\s*=>\\s*'([^']*)'/", $src, $m, PREG_SET_ORDER);
            foreach ($m as [, $key, $value]) {
                verify(in_array($value, self::ALLOWED, true))->true(basename($file) . ": '$key' has a real value — move it to config/*-local.php");
            }
            verify(preg_match('/GOCSPX-[A-Za-z0-9_-]{10,}/', $src))->equals(0, basename($file) . ': contains a Google client secret');
            verify(preg_match('/sk-or-[A-Za-z0-9_-]{10,}/', $src))->equals(0, basename($file) . ': contains an OpenRouter API key');
        }
    }

    public function testLocalFilesAreIgnored()
    {
        $root = dirname(__DIR__, 2);
        foreach (['config/db-local.php', 'config/params-local.php', 'config/test_db-local.php', 'runtime/deploy/x.sql'] as $f) {
            exec('cd ' . escapeshellarg($root) . ' && git check-ignore -q ' . escapeshellarg($f), $o, $code);
            verify($code)->equals(0, "$f must be git-ignored");
        }
    }
}
