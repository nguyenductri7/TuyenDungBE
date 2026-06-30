<?php

namespace App\Support;

use Throwable;

final class ApiErrorMessage
{
    public static function fromThrowable(Throwable $exception, ?int $status = null, ?string $fallback = null): string
    {
        return self::fromRawMessage($exception->getMessage(), $status, $fallback);
    }

    public static function fromRawMessage(?string $message, ?int $status = null, ?string $fallback = null): string
    {
        $status = self::normalizeStatus($status);
        $raw = trim((string) $message);

        if ($raw !== '') {
            $translated = self::translateKnownMessage($raw, $status);
            if ($translated !== null) {
                return $translated;
            }

            if (self::looksVietnamese($raw)) {
                return $raw;
            }
        }

        if ($fallback !== null && trim($fallback) !== '') {
            return trim($fallback);
        }

        return $status >= 500
            ? 'Hệ thống gặp lỗi khi xử lý yêu cầu. Vui lòng thử lại sau.'
            : 'Yêu cầu không thể thực hiện. Vui lòng kiểm tra lại dữ liệu và thử lại.';
    }

    private static function normalizeStatus(?int $status): int
    {
        $resolved = (int) ($status ?? 500);

        return ($resolved >= 400 && $resolved <= 599) ? $resolved : 500;
    }

    private static function looksVietnamese(string $message): bool
    {
        return preg_match('/[àáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđ]/iu', $message) === 1
            || preg_match('/\b(không|vui lòng|hệ thống|đăng nhập|không thể|dữ liệu|quyền|phiên|tài khoản|lỗi|tối thiểu|tối đa)\b/iu', $message) === 1;
    }

    private static function translateKnownMessage(string $rawMessage, int $status): ?string
    {
        $message = mb_strtolower($rawMessage);

        if (
            str_contains($message, "'nonetype' object has no attribute 'get'")
            || str_contains($message, '"nonetype" object has no attribute "get"')
            || str_contains($message, "has no attribute 'get'")
            || str_contains($message, 'has no attribute "get"')
        ) {
            return 'Hệ thống chưa nhận đủ dữ liệu để xử lý yêu cầu AI. Vui lòng thử lại hoặc bổ sung thêm thông tin đầu vào.';
        }

        if (self::containsAny($message, ['timed out', 'timeout', 'read timed out', 'operation timed out', 'curl error 28'])) {
            return 'Hệ thống xử lý quá lâu. Vui lòng thử lại sau ít phút.';
        }

        if (self::containsAny($message, [
            'connection refused',
            'failed to connect',
            'could not resolve host',
            'name or service not known',
            'network is unreachable',
            'temporary failure in name resolution',
            'curl error 6',
            'curl error 7',
            'max retries exceeded',
        ])) {
            return 'Không thể kết nối tới dịch vụ xử lý AI. Vui lòng thử lại sau.';
        }

        if (self::containsAny($message, ['invalid json', 'json decode', 'expecting value', 'extra data', 'unterminated string'])) {
            return 'Dịch vụ AI trả về dữ liệu không hợp lệ. Vui lòng thử lại sau.';
        }

        if (self::containsAny($message, ['field required', 'input should be'])) {
            return 'Dữ liệu gửi lên chưa hợp lệ. Vui lòng kiểm tra lại.';
        }

        if (self::containsAny($message, ['sqlstate[23000]', 'integrity constraint violation', 'duplicate entry'])) {
            return 'Dữ liệu đã tồn tại hoặc đang được liên kết, nên không thể thực hiện thao tác này.';
        }

        if (self::containsAny($message, ['sqlstate', 'syntax error or access violation', 'database is locked'])) {
            return 'Hệ thống dữ liệu gặp lỗi khi xử lý yêu cầu. Vui lòng thử lại sau.';
        }

        if (self::containsAny($message, ['unauthenticated', 'unauthorized'])) {
            return 'Phiên đăng nhập đã hết hạn hoặc chưa hợp lệ. Vui lòng đăng nhập lại.';
        }

        if (self::containsAny($message, ['forbidden', 'access denied'])) {
            return 'Bạn không có quyền thực hiện thao tác này.';
        }

        if (self::containsAny($message, ['not found', 'no query results for model'])) {
            return 'Không tìm thấy dữ liệu yêu cầu.';
        }

        if (str_contains($message, 'validation error')) {
            return 'Dữ liệu gửi lên chưa hợp lệ. Vui lòng kiểm tra lại.';
        }

        if (preg_match('/\b(attributeerror|typeerror|keyerror|indexerror|valueerror|runtimeerror)\b/i', $rawMessage) === 1) {
            return $status >= 500
                ? 'Hệ thống gặp lỗi xử lý nội bộ. Vui lòng thử lại sau.'
                : 'Dữ liệu gửi lên chưa phù hợp để xử lý. Vui lòng kiểm tra lại.';
        }

        return null;
    }

    private static function containsAny(string $message, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($message, $needle)) {
                return true;
            }
        }

        return false;
    }
}
