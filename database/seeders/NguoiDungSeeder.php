<?php

namespace Database\Seeders;

use App\Models\NguoiDung;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class NguoiDungSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        NguoiDung::updateOrCreate(
            ['email' => 'admin@kltn.com'],
            [
                'ho_ten' => 'Nguyễn Minh Quân',
                'email_verified_at' => $now,
                'mat_khau' => Hash::make('Admin@123'),
                'so_dien_thoai' => '0901234567',
                'ngay_sinh' => '1990-01-15',
                'gioi_tinh' => 'nam',
                'dia_chi' => '27 Nguyễn Huệ, Quận 1, TP. Hồ Chí Minh',
                'vai_tro' => NguoiDung::VAI_TRO_ADMIN,
                'cap_admin' => NguoiDung::CAP_ADMIN_SUPER_ADMIN,
                'quyen_admin' => NguoiDung::allAdminPermissions(),
                'trang_thai' => 1,
            ]
        );

        $employers = [
            ['Trần Gia Huy', 'hr.techviet@demo.vn', '0912345678', '1987-04-12', 'nam', '102 Pasteur, Quận 3, TP. Hồ Chí Minh'],
            ['Võ Ngọc Anh', 'hr.saigoncloud@demo.vn', '0912345679', '1990-09-23', 'nu', '18 Tôn Đức Thắng, Quận 1, TP. Hồ Chí Minh'],
            ['Phan Quốc Thịnh', 'hr.northstar@demo.vn', '0912345680', '1985-11-05', 'nam', '19 Duy Tân, Cầu Giấy, Hà Nội'],
            ['Đỗ Minh Trang', 'hr.mobilewave@demo.vn', '0912345681', '1991-06-18', 'nu', '86 Nguyễn Văn Linh, Hải Châu, Đà Nẵng'],
            ['Nguyễn Hoài Nam', 'hr.mekongcommerce@demo.vn', '0912345682', '1988-01-22', 'nam', '9 Trần Văn Khéo, Ninh Kiều, Cần Thơ'],
            ['Lê Thanh Hằng', 'hr.anphatretail@demo.vn', '0912345683', '1992-03-07', 'nu', '312 Nguyễn Trãi, Thanh Xuân, Hà Nội'],
            ['Bùi Anh Tuấn', 'hr.digigrowth@demo.vn', '0912345684', '1989-12-20', 'nam', '88 Bạch Đằng, Hải Châu, Đà Nẵng'],
            ['Mai Phương Thảo', 'hr.bloommedia@demo.vn', '0912345685', '1993-05-26', 'nu', '43 Võ Văn Tần, Quận 3, TP. Hồ Chí Minh'],
            ['Hoàng Đức Minh', 'hr.lotusfinance@demo.vn', '0912345686', '1986-08-14', 'nam', '55 Phan Chu Trinh, Hoàn Kiếm, Hà Nội'],
            ['Đặng Khánh Linh', 'hr.fincore@demo.vn', '0912345687', '1991-10-09', 'nu', '17 Nguyễn Thị Minh Khai, Quận 1, TP. Hồ Chí Minh'],
            ['Trương Minh Khang', 'hr.talentbridge@demo.vn', '0912345688', '1987-07-30', 'nam', '201 Hoàng Văn Thụ, Phú Nhuận, TP. Hồ Chí Minh'],
            ['Phạm Bảo Ngân', 'hr.peoplesphere@demo.vn', '0912345689', '1994-02-11', 'nu', '24 Lý Thường Kiệt, Hoàn Kiếm, Hà Nội'],
            ['Lê Quốc Bảo', 'hr.eduspark@demo.vn', '0912345690', '1989-04-25', 'nam', '42 Lê Lợi, Hải Châu, Đà Nẵng'],
            ['Ngô Thùy Dương', 'hr.sunriseacademy@demo.vn', '0912345691', '1992-09-16', 'nu', '66 Điện Biên Phủ, Bình Thạnh, TP. Hồ Chí Minh'],
            ['Đinh Gia Phúc', 'hr.medilink@demo.vn', '0912345692', '1985-06-02', 'nam', '210 Điện Biên Phủ, Bình Thạnh, TP. Hồ Chí Minh'],
            ['Vũ Hồng Nhung', 'hr.healcare@demo.vn', '0912345693', '1990-11-19', 'nu', '35 Nguyễn Văn Cừ, Ninh Kiều, Cần Thơ'],
            ['Nguyễn Việt Dũng', 'hr.skylinebuild@demo.vn', '0912345694', '1984-03-28', 'nam', '6A Nguyễn Hữu Thọ, Quận 7, TP. Hồ Chí Minh'],
            ['Tạ Hoàng Yến', 'hr.greenhome@demo.vn', '0912345695', '1993-01-08', 'nu', '12 Tố Hữu, Nam Từ Liêm, Hà Nội'],
            ['Phạm Tuấn Kiệt', 'hr.vietlogix@demo.vn', '0912345696', '1988-12-03', 'nam', '128 Xa Lộ Hà Nội, TP. Thủ Đức, TP. Hồ Chí Minh'],
            ['Châu Mỹ Hạnh', 'hr.lumieretravel@demo.vn', '0912345697', '1991-07-12', 'nu', '76 Trần Phú, Nha Trang, Khánh Hòa'],
        ];

        foreach ($employers as [$name, $email, $phone, $birthday, $gender, $address]) {
            NguoiDung::updateOrCreate(
                ['email' => $email],
                [
                    'ho_ten' => $name,
                    'email_verified_at' => $now,
                    'mat_khau' => Hash::make('NTD@123456'),
                    'so_dien_thoai' => $phone,
                    'ngay_sinh' => $birthday,
                    'gioi_tinh' => $gender,
                    'dia_chi' => $address,
                    'vai_tro' => NguoiDung::VAI_TRO_NHA_TUYEN_DUNG,
                    'cap_admin' => null,
                    'quyen_admin' => null,
                    'trang_thai' => 1,
                ]
            );
        }

        $candidates = [
            ['Phạm Văn An', 'ungvien.backend@demo.vn', '0934567890', '1999-11-05', 'nam', '12 Trần Phú, Ba Đình, Hà Nội'],
            ['Lê Thị Bình', 'ungvien.frontend@demo.vn', '0945678901', '2000-07-22', 'nu', '89 Pasteur, Bình Thạnh, TP. Hồ Chí Minh'],
            ['Nguyễn Hoàng Long', 'ungvien.data@demo.vn', '0956789012', '1997-03-14', 'nam', '43 Nguyễn Tri Phương, Hải Châu, Đà Nẵng'],
            ['Trương Khánh Vy', 'ungvien.marketing@demo.vn', '0967890123', '1998-12-01', 'nu', '17 Võ Văn Tần, Quận 3, TP. Hồ Chí Minh'],
            ['Bùi Đức Nam', 'ungvien.qa@demo.vn', '0978901234', '1996-06-19', 'nam', '75 Trần Duy Hưng, Cầu Giấy, Hà Nội'],
            ['Đặng Minh Châu', 'ungvien.sales@demo.vn', '0981234567', '1999-02-03', 'nu', '91 Nguyễn Văn Linh, Hải Châu, Đà Nẵng'],
            ['Hoàng Anh Tú', 'ungvien.accounting@demo.vn', '0981234568', '1995-09-17', 'nam', '120 Lê Duẩn, Quận 1, TP. Hồ Chí Minh'],
            ['Võ Thanh Mai', 'ungvien.hr@demo.vn', '0981234569', '1998-05-27', 'nu', '32 Kim Mã, Ba Đình, Hà Nội'],
            ['Nguyễn Bảo Khang', 'ungvien.teacher@demo.vn', '0981234570', '1997-10-10', 'nam', '45 Nguyễn Huệ, Quận 1, TP. Hồ Chí Minh'],
            ['Lâm Phương Nhi', 'ungvien.nurse@demo.vn', '0981234571', '1996-01-21', 'nu', '18 Cách Mạng Tháng 8, Quận 10, TP. Hồ Chí Minh'],
            ['Đỗ Quốc Hưng', 'ungvien.construction@demo.vn', '0981234572', '1994-08-08', 'nam', '8 Lê Văn Lương, Thanh Xuân, Hà Nội'],
            ['Ngô Hải Yến', 'ungvien.logistics@demo.vn', '0981234573', '1998-04-14', 'nu', '64 Nguyễn Văn Cừ, Ninh Kiều, Cần Thơ'],
        ];

        foreach ($candidates as [$name, $email, $phone, $birthday, $gender, $address]) {
            NguoiDung::updateOrCreate(
                ['email' => $email],
                [
                    'ho_ten' => $name,
                    'email_verified_at' => $now,
                    'mat_khau' => Hash::make('UV@123456'),
                    'so_dien_thoai' => $phone,
                    'ngay_sinh' => $birthday,
                    'gioi_tinh' => $gender,
                    'dia_chi' => $address,
                    'vai_tro' => NguoiDung::VAI_TRO_UNG_VIEN,
                    'cap_admin' => null,
                    'quyen_admin' => null,
                    'trang_thai' => 1,
                ]
            );
        }

        $this->command->info('✅ NguoiDungSeeder: Đã tạo bộ tài khoản demo sạch, không dùng factory ngẫu nhiên.');
        $this->command->table(
            ['Nhóm', 'Số lượng', 'Thông tin đăng nhập'],
            [
                ['Super Admin', '1', 'admin@kltn.com / Admin@123'],
                ['Nhà tuyển dụng', (string) count($employers), 'hr.<company>@demo.vn / NTD@123456'],
                ['Ứng viên', (string) count($candidates), 'ungvien.<persona>@demo.vn / UV@123456'],
            ]
        );
    }
}
