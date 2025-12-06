<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Throwable;

class SystemMonitorService
{
    /**
     * Return a snapshot of system metrics for display.
     */
    public function snapshot(): array
    {
        return [
            'hostname' => gethostname() ?: 'Unknown',
            'os' => PHP_OS_FAMILY,
            'kernel' => php_uname('r'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'uptime_seconds' => $this->getUptimeSeconds(),
            'cpu_cores' => $this->getCpuCores(),
            'load_average' => $this->getLoadAverage(),
            'memory' => $this->getMemoryUsage(),
            'disks' => $this->getDiskUsage(),
            'database' => $this->getDatabaseStatus(),
            'checked_at' => now(),
        ];
    }

    private function getLoadAverage(): ?array
    {
        if (!function_exists('sys_getloadavg')) {
            return null;
        }

        $load = sys_getloadavg();

        return [
            '1m' => $load[0] ?? null,
            '5m' => $load[1] ?? null,
            '15m' => $load[2] ?? null,
        ];
    }

    private function getCpuCores(): int
    {
        if (PHP_OS_FAMILY === 'Linux' && is_readable('/proc/cpuinfo')) {
            $content = file_get_contents('/proc/cpuinfo');
            $matches = [];
            preg_match_all('/^processor/m', $content, $matches);
            if (!empty($matches[0])) {
                return count($matches[0]);
            }
        }

        if (function_exists('shell_exec')) {
            $coreCount = (int) @shell_exec('nproc 2>/dev/null');
            if ($coreCount > 0) {
                return $coreCount;
            }
        }
        
        return 1;
    }

    private function getUptimeSeconds(): ?int
    {
        if (PHP_OS_FAMILY === 'Linux' && is_readable('/proc/uptime')) {
            $parts = explode(' ', trim(file_get_contents('/proc/uptime')));
            if (!empty($parts[0])) {
                return (int) floor((float) $parts[0]);
            }
        }

        return null;
    }

    private function getMemoryUsage(): array
    {
        $data = [
            'total' => null,
            'available' => null,
            'used' => null,
            'used_percent' => null,
        ];

        if (PHP_OS_FAMILY === 'Linux' && is_readable('/proc/meminfo')) {
            $meminfo = file('/proc/meminfo');
            $values = [];

            foreach ($meminfo as $line) {
                if (preg_match('/^(\w+):\s+(\d+)\s+kB$/', trim($line), $matches)) {
                    $values[$matches[1]] = (int) $matches[2] * 1024; // to bytes
                }
            }

            $total = $values['MemTotal'] ?? null;
            $available = $values['MemAvailable'] ?? null;

            if ($total !== null && $available !== null) {
                $used = $total - $available;
                $data['total'] = $total;
                $data['available'] = $available;
                $data['used'] = $used;
                $data['used_percent'] = $total > 0 ? round(($used / $total) * 100, 1) : null;
            }
        }

        return $data;
    }

    private function getDiskUsage(): array
    {
        $path = base_path();
        $total = @disk_total_space($path);
        $free = @disk_free_space($path);

        if ($total === false || $free === false) {
            return [];
        }

        $used = $total - $free;

        return [[
            'mount' => '/',
            'total' => $total,
            'used' => $used,
            'free' => $free,
            'used_percent' => $total > 0 ? round(($used / $total) * 100, 1) : null,
        ]];
    }

    private function getDatabaseStatus(): array
    {
        try {
            DB::select('select 1');
            return [
                'status' => 'online',
                'message' => 'Database reachable',
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'offline',
                'message' => $e->getMessage(),
            ];
        }
    }
}
