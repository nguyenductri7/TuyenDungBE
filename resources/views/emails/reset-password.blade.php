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
                Bảo mật tài khoản
              </div>
              <h1 style="margin:18px 0 0;font-size:30px;line-height:1.25;font-weight:800;">
                Yêu cầu đặt lại mật khẩu của bạn
              </h1>
              <p style="margin:14px 0 0;font-size:15px;line-height:1.7;opacity:0.92;">
                Chúng tôi đã nhận được yêu cầu đặt lại mật khẩu cho tài khoản AI Recruitment của bạn.
              </p>
            </td>
          </tr>

          <tr>
            <td style="padding:32px;">
              <p style="margin:0 0 18px;font-size:16px;line-height:1.7;">Xin chào <strong>{{ $candidateName }}</strong>,</p>

              <div style="padding:22px;border-radius:18px;background:#f8fafc;border:1px solid #dbe4f0;">
                <p style="margin:0;font-size:16px;line-height:1.8;color:#334155;">
                  Để tiếp tục, vui lòng sử dụng nút bên dưới để thiết lập mật khẩu mới cho tài khoản của bạn.
                  Sau khi đổi mật khẩu thành công, bạn có thể đăng nhập lại và tiếp tục sử dụng hệ thống như bình thường.
                </p>
              </div>

              <div style="margin-top:20px;padding:20px 22px;border-radius:18px;background:#fff7ed;border:1px solid #fed7aa;">
                <p style="margin:0;font-size:15px;line-height:1.8;color:#9a3412;">
                  <strong>Lưu ý bảo mật:</strong> Liên kết đặt lại mật khẩu này sẽ hết hạn sau 60 phút.
                  Nếu bạn không yêu cầu thao tác này, bạn có thể bỏ qua email và mật khẩu hiện tại vẫn được giữ nguyên.
                </p>
              </div>

              <div style="margin-top:28px;text-align:center;">
                <a href="{{ $resetUrl }}" style="display:inline-block;padding:14px 26px;border-radius:14px;background:#2463eb;color:#ffffff;text-decoration:none;font-size:15px;font-weight:700;">
                  Đặt lại mật khẩu
                </a>
              </div>

              <div style="margin-top:18px;text-align:center;">
                <a href="{{ $actionUrl }}" style="display:inline-block;padding:12px 22px;border-radius:14px;background:#eff6ff;color:#1d4ed8;text-decoration:none;font-size:14px;font-weight:700;">
                  Mở màn hình đăng nhập
                </a>
              </div>

              <p style="margin:28px 0 0;font-size:14px;line-height:1.8;color:#475569;">
                Nếu bạn cần hỗ trợ thêm, hãy thử yêu cầu đặt lại mật khẩu lại từ hệ thống hoặc liên hệ quản trị viên.
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
