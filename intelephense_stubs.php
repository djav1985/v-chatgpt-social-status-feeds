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

}
