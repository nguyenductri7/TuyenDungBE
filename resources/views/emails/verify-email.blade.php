<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $subjectText }}</title>
</head>
<body style="margin:0;padding:0;background:#f3f6fb;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
  <div style="display:none;max-height:0;overflow:hidden;opacity:0;">{{ $previewText }}</div>
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f6fb;padding:24px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:680px;background:#ffffff;border-radius:24px;overflow:hidden;border:1px solid #dbe4f0;">
          <tr>
            <td style="padding:28px 32px;background:linear-gradient(135deg,#102144 0%,#2463eb 100%);color:#ffffff;">
              <div style="font-size:13px;letter-spacing:0.28em;text-transform:uppercase;opacity:0.82;font-weight:700;">AIRecruitment</div>
              <div style="margin-top:18px;display:inline-block;padding:8px 14px;border-radius:999px;font-size:13px;font-weight:700;background:#dbeafe;color:#1d4ed8;">
                Xác thực tài khoản
              </div>
              <h1 style="margin:18px 0 0;font-size:30px;line-height:1.25;font-weight:800;">
                Kích hoạt email để bắt đầu sử dụng hệ thống
              </h1>
              <p style="margin:14px 0 0;font-size:15px;line-height:1.7;opacity:0.92;">
                Chỉ còn một bước xác thực nữa là bạn có thể truy cập đầy đủ các tính năng trên AI Recruitment.
              </p>
            </td>
          </tr>

          <tr>
            <td style="padding:32px;">
              <p style="margin:0 0 18px;font-size:16px;line-height:1.7;">Xin chào <strong>{{ $candidateName }}</strong>,</p>

              <div style="padding:22px;border-radius:18px;background:#f8fafc;border:1px solid #dbeafe;">
                <p style="margin:0;font-size:16px;line-height:1.8;color:#334155;">
                  Cảm ơn bạn đã đăng ký tài khoản trên <strong>AI Recruitment</strong>.
                  Vui lòng xác thực địa chỉ email để kích hoạt tài khoản và bắt đầu sử dụng đầy đủ các chức năng như ứng tuyển, theo dõi hồ sơ và nhận thông báo tuyển dụng.
                </p>
              </div>

              <div style="margin-top:20px;padding:20px 22px;border-radius:18px;background:#eff6ff;border:1px solid #bfdbfe;">
                <p style="margin:0;font-size:15px;line-height:1.8;color:#1e3a8a;">
                  <strong>Lưu ý:</strong> Liên kết xác thực này sẽ hết hạn sau 60 phút. Nếu hết hạn, bạn có thể đăng nhập lại để yêu cầu gửi lại email xác thực.
                </p>
              </div>

              <div style="margin-top:28px;text-align:center;">
                <a href="{{ $verificationUrl }}" style="display:inline-block;padding:14px 26px;border-radius:14px;background:#2463eb;color:#ffffff;text-decoration:none;font-size:15px;font-weight:700;">
                  Xác thực email
                </a>
              </div>

              <div style="margin-top:18px;text-align:center;">
                <a href="{{ $actionUrl }}" style="display:inline-block;padding:12px 22px;border-radius:14px;background:#eff6ff;color:#1d4ed8;text-decoration:none;font-size:14px;font-weight:700;">
                  Mở màn hình đăng nhập
                </a>
              </div>

              <p style="margin:28px 0 0;font-size:14px;line-height:1.8;color:#475569;">
                Nếu bạn không tạo tài khoản này, bạn có thể bỏ qua email mà không cần thực hiện thêm thao tác nào.
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
