<?php

declare(strict_types=1);

namespace Codejitsu\Substrate;

use Codejitsu\ExecutionContext;
use Codejitsu\ExecutionPolicy;
use RuntimeException;

final class PhpRunner
{
    public function execute(string $source, ExecutionContext $context): mixed
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'codejitsu-' . bin2hex(random_bytes(12));
        if (!mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create PHP substrate sandbox.');
        }

        $runner = $directory . DIRECTORY_SEPARATOR . 'runner.php';
        $payload = $directory . DIRECTORY_SEPARATOR . 'context.json';
        $script = $directory . DIRECTORY_SEPARATOR . 'source.php';

        try {
            $this->write($script, $this->prepareSource($source));
            $this->write($payload, json_encode([
                'arguments' => $context->arguments,
                'environment' => $context->policy->environment,
            ], JSON_THROW_ON_ERROR));
            $this->write($runner, $this->runnerSource());

            [$process, $pipes] = $this->open($runner, $payload, $script, $directory, $context->policy);
            $stdout = '';
            $stderr = '';
            $observedExitCode = null;
            $deadline = microtime(true) + ($context->policy->timeoutMilliseconds / 1000);

            stream_set_blocking($pipes[1], false);
            stream_set_blocking($pipes[2], false);

            while (true) {
                $status = proc_get_status($process);
                $stdout .= stream_get_contents($pipes[1]) ?: '';
                $stderr .= stream_get_contents($pipes[2]) ?: '';

                if (!$status['running']) {
                    $observedExitCode = is_int($status['exitcode']) ? $status['exitcode'] : null;
                    break;
                }

                if (microtime(true) >= $deadline) {
                    proc_terminate($process, 9);
                    foreach ($pipes as $pipe) {
                        if (is_resource($pipe)) {
                            fclose($pipe);
                        }
                    }
                    proc_close($process);
                    throw new RuntimeException('PHP substrate execution exceeded its time limit.');
                }

                usleep(1000);
            }

            $stdout .= stream_get_contents($pipes[1]) ?: '';
            $stderr .= stream_get_contents($pipes[2]) ?: '';
            fclose($pipes[1]);
            fclose($pipes[2]);
            $closedExitCode = proc_close($process);
            $exitCode = $observedExitCode !== null && $observedExitCode >= 0
                ? $observedExitCode
                : $closedExitCode;

            if ($exitCode !== 0) {
                throw new RuntimeException(sprintf(
                    'PHP substrate execution failed%s%s',
                    $stderr === '' ? '.' : ': ',
                    $stderr,
                ));
            }

            $decoded = unserialize(base64_decode(trim($stdout), true) ?: '', ['allowed_classes' => false]);
            if (!is_array($decoded) || !array_key_exists('result', $decoded)) {
                throw new RuntimeException('PHP substrate returned an invalid result.');
            }

            return $decoded['result'];
        } finally {
            $this->removeDirectory($directory);
        }
    }

    private function prepareSource(string $source): string
    {
        $source = preg_replace('/^\s*#![^\r\n]*\r?\n/', '', $source, 1);
        if ($source === null) {
            throw new RuntimeException('Unable to prepare PHP source.');
        }

        if (!preg_match('/^\s*<\?php\b/', $source)) {
            throw new RuntimeException('PHP source must begin with <?php.');
        }

        return $source;
    }

    /** @return array{0: resource, 1: array<int, resource>} */
    private function open(
        string $runner,
        string $payload,
        string $script,
        string $directory,
        ExecutionPolicy $policy,
    ): array {
        $disabled = implode(',', [
            'exec', 'passthru', 'shell_exec', 'system', 'proc_open', 'popen',
            'pcntl_exec', 'putenv', 'dl', 'mail', 'fsockopen', 'pfsockopen',
            'stream_socket_client', 'stream_socket_server', 'stream_socket_accept',
            'curl_exec', 'curl_multi_exec', 'socket_connect', 'socket_create',
            'get_headers',
        ]);

        $command = [
            PHP_BINARY,
            '-d', 'memory_limit=' . $policy->memoryBytes,
            '-d', 'open_basedir=' . $directory,
            '-d', 'allow_url_fopen=0',
            '-d', 'allow_url_include=0',
            '-d', 'ffi.enable=false',
            '-d', 'disable_functions=' . $disabled,
            $runner,
            $payload,
            $script,
        ];

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes, $directory, $policy->environment);
        if (!is_resource($process)) {
            throw new RuntimeException('Unable to start PHP substrate sandbox.');
        }

        fclose($pipes[0]);
        return [$process, $pipes];
    }

    private function runnerSource(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

$payload = json_decode(file_get_contents($argv[1]), false, 512, JSON_THROW_ON_ERROR);
$context = $payload;
ob_start();
$result = include $argv[2];
ob_end_clean();
echo base64_encode(serialize(['result' => $result]));
PHP;
    }

    private function write(string $path, string $contents): void
    {
        if (file_put_contents($path, $contents, LOCK_EX) === false) {
            throw new RuntimeException(sprintf('Unable to write sandbox file [%s].', $path));
        }
    }

    private function removeDirectory(string $directory): void
    {
        foreach (glob($directory . DIRECTORY_SEPARATOR . '*') ?: [] as $path) {
            if (is_file($path) || is_link($path)) {
                @unlink($path);
            }
        }
        @rmdir($directory);
    }
}
