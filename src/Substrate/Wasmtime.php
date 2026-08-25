<?php

declare(strict_types=1);

namespace Codejitsu\Substrate;

use Codejitsu\ExecutionContext;
use RuntimeException;

final class Wasmtime
{
    public function execute(string $module, ExecutionContext $context): mixed
    {
        $binary = base64_decode($module, true);
        if ($binary === false) {
            throw new RuntimeException('WASM source must be base64 encoded.');
        }

        $wasmtime = trim((string) shell_exec('command -v wasmtime'));
        if ($wasmtime === '') {
            throw new RuntimeException('Wasmtime is not installed.');
        }

        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'codejitsu-wasm-' . bin2hex(random_bytes(12));
        if (!mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create WASM sandbox.');
        }

        $modulePath = $directory . DIRECTORY_SEPARATOR . 'module.wasm';

        try {
            if (file_put_contents($modulePath, $binary, LOCK_EX) === false) {
                throw new RuntimeException('Unable to write WASM module.');
            }

            $command = [$wasmtime, 'run', '--invoke', 'run'];
            foreach ($context->policy->filesystemRoots as $root) {
                $command[] = '--dir';
                $command[] = $root;
            }
            foreach ($context->policy->environment as $name => $value) {
                $command[] = '--env';
                $command[] = sprintf('%s=%s', $name, $value);
            }
            $command[] = $modulePath;

            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];

            $process = proc_open($command, $descriptors, $pipes, $directory, []);
            if (!is_resource($process)) {
                throw new RuntimeException('Unable to start Wasmtime sandbox.');
            }

            fclose($pipes[0]);
            $stdout = '';
            $stderr = '';
            $deadline = microtime(true) + ($context->policy->timeoutMilliseconds / 1000);

            stream_set_blocking($pipes[1], false);
            stream_set_blocking($pipes[2], false);

            while (true) {
                $status = proc_get_status($process);
                $stdout .= stream_get_contents($pipes[1]) ?: '';
                $stderr .= stream_get_contents($pipes[2]) ?: '';

                if (!$status['running']) {
                    break;
                }

                if (microtime(true) >= $deadline) {
                    proc_terminate($process, 9);
                    throw new RuntimeException('WASM substrate execution exceeded its time limit.');
                }

                usleep(1000);
            }

            $stdout .= stream_get_contents($pipes[1]) ?: '';
            $stderr .= stream_get_contents($pipes[2]) ?: '';
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exitCode = proc_close($process);

            if ($exitCode !== 0) {
                throw new RuntimeException(sprintf(
                    'WASM substrate execution failed%s%s',
                    $stderr === '' ? '.' : ': ',
                    $stderr,
                ));
            }

            $result = trim($stdout);
            if (is_numeric($result) && preg_match('/^-?\d+$/', $result) === 1) {
                return (int) $result;
            }

            return $result;
        } finally {
            @unlink($modulePath);
            @rmdir($directory);
        }
    }
}
