<?php
/**
 * Intelephense/PHP static analysis stubs.
 *
 * This file provides lightweight function stubs for global PHP functions
 * that may be flagged by some language servers when PHP runtime stubs
 * are not available. All definitions are guarded by function_exists to
 * avoid runtime re-declaration.
 *
 * NOTE: This file is only for static analysis assistance and should not
 * affect runtime behavior due to the existence checks.
 */

namespace {

    if (!function_exists('random_bytes')) {
        /**
         * @param int $length
         * @return string
         */
        function random_bytes(int $length) {
            // Stub for static analysis only.
        }
    }

    if (!function_exists('random_int')) {
        /**
         * @param int $min
         * @param int $max
         * @return int
         */
        function random_int(int $min, int $max) {
            // Stub for static analysis only.
        }
    }

    if (!function_exists('mt_rand')) {
        /**
         * @param int|null $min
         * @param int|null $max
         * @return int
         */
        function mt_rand(?int $min = null, ?int $max = null) {
            // Stub for static analysis only.
        }
    }

    if (!function_exists('posix_kill')) {
        /**
         * @param int $pid
         * @param int $sig
         * @return bool
         */
        function posix_kill(int $pid, int $sig): bool {
            // Stub for static analysis only.
            return false;
        }
    }

    if (!function_exists('apcu_enabled')) {
        /**
         * @return bool
         */
        function apcu_enabled(): bool {
            // Stub for static analysis only.
            return false;
        }
    }

    if (!function_exists('apcu_fetch')) {
        /**
         * @param string|string[] $key
         * @param bool $success
         * @return mixed
         */
        function apcu_fetch(string|array $key, bool &$success = null): mixed {
            // Stub for static analysis only.
        }
    }

    if (!function_exists('apcu_store')) {
        /**
         * @param string|array $key
         * @param mixed $value
         * @param int $ttl
         * @return bool|array
         */
        function apcu_store(string|array $key, mixed $value = null, int $ttl = 0): bool|array {
            // Stub for static analysis only.
            return false;
        }
    }

    if (!function_exists('apcu_delete')) {
        /**
         * @param string|string[]|APCUIterator $key
         * @return bool|string[]
         */
        function apcu_delete(string|array|APCUIterator $key): bool|array {
            // Stub for static analysis only.
            return false;
        }
    }

    if (!function_exists('apcu_exists')) {
        /**
         * @param string|string[] $keys
         * @return bool|string[]
         */
        function apcu_exists(string|array $keys): bool|array {
            // Stub for static analysis only.
            return false;
        }
    }

    if (!class_exists('APCUIterator')) {
        /**
         * APCUIterator stub for static analysis.
         */
        class APCUIterator implements Iterator {
            /**
             * @param string|string[]|null $search
             * @param int $format
             * @param int $chunk_size
             * @param int $list
             */
            public function __construct(
                string|array|null $search = null,
                int $format = APC_ITER_ALL,
                int $chunk_size = 100,
                int $list = APC_LIST_ACTIVE
            ) {}

            public function current(): mixed {}
            public function key(): mixed {}
            public function next(): void {}
            public function rewind(): void {}
            public function valid(): bool { return false; }
            public function getTotalCount(): int { return 0; }
            public function getTotalHits(): int { return 0; }
            public function getTotalSize(): int { return 0; }
        }
    }

}
