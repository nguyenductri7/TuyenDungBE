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
              <div style="margin-top:18px;display:inline-block;padding:8px 14px;border-radius:999px;font-size:13px;font-weight:700;background:#dcfce7;color:#166534;">
                Đề nghị nhận việc
              </div>
              <h1 style="margin:18px 0 0;font-size:30px;line-height:1.25;font-weight:800;">
                Bạn đã nhận được offer từ nhà tuyển dụng
              </h1>
              <p style="margin:14px 0 0;font-size:15px;line-height:1.7;opacity:0.92;">
                Nhà tuyển dụng đã hoàn tất quy trình tuyển chọn và gửi đề nghị nhận việc đến bạn. Vui lòng xem kỹ thông tin và phản hồi trong thời hạn phù hợp.
              </p>
            </td>
          </tr>

          <tr>
            <td style="padding:32px;">
              <p style="margin:0 0 18px;font-size:16px;line-height:1.7;">Xin chào <strong>{{ $candidateName }}</strong>,</p>

              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:separate;border-spacing:0 12px;">
                <tr>
                  <td style="width:180px;padding:16px 18px;background:#f8fafc;border-radius:16px;font-size:13px;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;color:#64748b;">Vị trí</td>
                  <td style="padding:16px 18px;background:#f8fafc;border-radius:16px;font-size:16px;font-weight:700;color:#0f172a;">{{ $jobTitle }}</td>
                </tr>
                <tr>
                  <td style="width:180px;padding:16px 18px;background:#f8fafc;border-radius:16px;font-size:13px;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;color:#64748b;">Công ty</td>
                  <td style="padding:16px 18px;background:#f8fafc;border-radius:16px;font-size:16px;font-weight:700;color:#0f172a;">{{ $companyName }}</td>
                </tr>
                @if (!empty($offerDeadline))
                  <tr>
                    <td style="width:180px;padding:16px 18px;background:#f8fafc;border-radius:16px;font-size:13px;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;color:#64748b;">Hạn phản hồi</td>
                    <td style="padding:16px 18px;background:#f8fafc;border-radius:16px;font-size:16px;font-weight:700;color:#0f172a;">{{ $offerDeadline }}</td>
                  </tr>
                @endif
              </table>

              @if (!empty($offerNote))
                <div style="margin-top:20px;padding:22px;border-radius:18px;background:#eff6ff;border:1px solid #bfdbfe;">
                  <p style="margin:0 0 8px;font-size:13px;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;color:#2563eb;">Tóm tắt offer</p>
                  <p style="margin:0;font-size:15px;line-height:1.8;color:#334155;">{{ $offerNote }}</p>
                </div>
              @endif

              @if (!empty($offerLink))
                <div style="margin-top:18px;text-align:center;">
                  <a href="{{ $offerLink }}" style="display:inline-block;padding:12px 22px;border-radius:14px;background:#eff6ff;color:#1d4ed8;text-decoration:none;font-size:14px;font-weight:700;">
                    Xem tài liệu offer
                  </a>
                </div>
              @endif

              <div style="margin-top:28px;">
                <p style="margin:0 0 14px;font-size:14px;font-weight:700;color:#0f172a;">Phản hồi đề nghị nhận việc</p>
                <table role="presentation" cellspacing="0" cellpadding="0" style="width:100%;">
                  <tr>
                    <td style="padding:0 8px 0 0;">
                      <a href="{{ $acceptUrl }}" style="display:block;text-align:center;padding:14px 20px;border-radius:14px;background:#2463eb;color:#ffffff;text-decoration:none;font-size:15px;font-weight:700;">
                        Chấp nhận offer
                      </a>
                    </td>
                    <td style="padding:0 0 0 8px;">
                      <a href="{{ $declineUrl }}" style="display:block;text-align:center;padding:14px 20px;border-radius:14px;background:#fff1f2;color:#be123c;text-decoration:none;font-size:15px;font-weight:700;border:1px solid #fecdd3;">
                        Từ chối offer
                      </a>
                    </td>
                  </tr>
                </table>
              </div>

              <div style="margin-top:18px;text-align:center;">
                <a href="{{ $actionUrl }}" style="display:inline-block;padding:12px 22px;border-radius:14px;background:#f8fafc;color:#0f172a;text-decoration:none;font-size:14px;font-weight:700;border:1px solid #dbe4f0;">
                  Mở trang ứng tuyển
                </a>
              </div>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
