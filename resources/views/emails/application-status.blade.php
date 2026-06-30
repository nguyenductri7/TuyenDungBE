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
              <div style="margin-top:18px;display:inline-block;padding:8px 14px;border-radius:999px;font-size:13px;font-weight:700;{{ $isAccepted ? 'background:#dcfce7;color:#166534;' : 'background:#fee2e2;color:#b91c1c;' }}">
                {{ $isAccepted ? 'Kết quả trúng tuyển' : 'Thông báo kết quả' }}
              </div>
              <h1 style="margin:18px 0 0;font-size:30px;line-height:1.25;font-weight:800;">
                {{ $isAccepted ? 'Chúc mừng bạn đã trúng tuyển' : 'Thông báo kết quả ứng tuyển' }}
              </h1>
              <p style="margin:14px 0 0;font-size:15px;line-height:1.7;opacity:0.92;">
                {{ $isAccepted ? 'Nhà tuyển dụng đã hoàn tất đánh giá và xác nhận bạn phù hợp với vị trí ứng tuyển.' : 'Nhà tuyển dụng đã hoàn tất đánh giá hồ sơ của bạn cho đợt tuyển dụng này.' }}
              </p>
            </td>
          </tr>

          <tr>
            <td style="padding:32px;">
              <p style="margin:0 0 18px;font-size:16px;line-height:1.7;">Xin chào <strong>{{ $candidateName }}</strong>,</p>

              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:separate;border-spacing:0 12px;">
                <tr>
                  <td style="width:180px;padding:16px 18px;background:#f8fafc;border-radius:16px;font-size:13px;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;color:#64748b;">Vị trí ứng tuyển</td>
                  <td style="padding:16px 18px;background:#f8fafc;border-radius:16px;font-size:16px;font-weight:700;color:#0f172a;">{{ $jobTitle }}</td>
                </tr>
                <tr>
                  <td style="width:180px;padding:16px 18px;background:#f8fafc;border-radius:16px;font-size:13px;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;color:#64748b;">Công ty</td>
                  <td style="padding:16px 18px;background:#f8fafc;border-radius:16px;font-size:16px;font-weight:700;color:#0f172a;">{{ $companyName }}</td>
                </tr>
              </table>

              <div style="margin-top:20px;padding:22px;border-radius:18px;{{ $isAccepted ? 'background:#f0fdf4;border:1px solid #bbf7d0;' : 'background:#fff7ed;border:1px solid #fed7aa;' }}">
                <p style="margin:0;font-size:16px;line-height:1.8;color:#334155;">
                  @if ($isAccepted)
                    Chúc mừng bạn! Sau quá trình đánh giá, nhà tuyển dụng đã quyết định <strong>tiếp nhận bạn cho vị trí này</strong>.
                    Vui lòng theo dõi email và khu vực ứng tuyển để cập nhật các bước tiếp theo như xác nhận nhận việc, trao đổi lịch onboarding hoặc những yêu cầu bổ sung từ doanh nghiệp.
                  @else
                    Cảm ơn bạn đã quan tâm và dành thời gian ứng tuyển vào vị trí này. Sau khi xem xét hồ sơ, nhà tuyển dụng đánh giá rằng hồ sơ của bạn <strong>chưa phù hợp nhất với nhu cầu tuyển dụng ở thời điểm hiện tại</strong>.
                    Đây không phải là đánh giá về năng lực tổng quát của bạn; rất mong bạn tiếp tục theo dõi các cơ hội khác phù hợp hơn trong thời gian tới.
                  @endif
                </p>
              </div>

              @if ($isAccepted)
                <div style="margin-top:20px;padding:20px 22px;border-radius:18px;background:#eff6ff;border:1px solid #bfdbfe;">
                  <p style="margin:0;font-size:15px;line-height:1.8;color:#1e3a8a;">
                    <strong>Bước tiếp theo:</strong> Hãy kiểm tra thường xuyên email và trạng thái đơn ứng tuyển trong hệ thống để không bỏ lỡ các hướng dẫn tiếp theo từ nhà tuyển dụng.
                  </p>
                </div>
              @else
                <div style="margin-top:20px;padding:20px 22px;border-radius:18px;background:#f8fafc;border:1px solid #e2e8f0;">
                  <p style="margin:0;font-size:15px;line-height:1.8;color:#475569;">
                    Bạn vẫn có thể cập nhật CV, kỹ năng và tiếp tục ứng tuyển những vị trí khác trong hệ thống để tăng cơ hội phù hợp ở các đợt tuyển dụng tiếp theo.
                  </p>
                </div>
              @endif

              <div style="margin-top:28px;text-align:center;">
                <a href="{{ $actionUrl }}" style="display:inline-block;padding:14px 26px;border-radius:14px;background:#2463eb;color:#ffffff;text-decoration:none;font-size:15px;font-weight:700;">
                  {{ $ctaLabel ?? 'Xem đơn ứng tuyển' }}
                </a>
              </div>

              <p style="margin:28px 0 0;font-size:14px;line-height:1.8;color:#475569;">
                {{ $isAccepted ? 'Chúc mừng bạn và chúc bạn có một khởi đầu thật thuận lợi với cơ hội mới.' : 'Chúc bạn sớm tìm được vị trí phù hợp với định hướng phát triển của mình.' }}
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
