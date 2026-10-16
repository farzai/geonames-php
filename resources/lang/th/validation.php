<?php

return [
    'country' => ':attribute ต้องเป็นรหัสประเทศ (ISO 3166-1 alpha-2, alpha-3 หรือตัวเลข)',
    'admin1' => ':attribute ต้องเป็นเขตการปกครองระดับแรก (รหัส admin1 ของ GeoNames) ของประเทศที่เลือก',
    'city' => ':attribute ต้องเป็นรหัสเมือง (geoname id) ของ GeoNames ในประเทศที่เลือก',
    'postal_code' => ':attribute ต้องเป็นรหัสไปรษณีย์ตามรูปแบบของประเทศที่เลือก',
    'postal_code_unknown' => ':attribute ไม่ใช่รหัสไปรษณีย์ที่รู้จักของประเทศที่เลือก',
    'postal_elsewhere' => 'รหัสไปรษณีย์ของประเทศไทยไม่อยู่ในข้อมูล GeoNames ให้ตรวจกับ farzai/thailand-address',
    'not_fetched' => 'ยังไม่ได้ดาวน์โหลดข้อมูล GeoNames ที่ใช้ตรวจ :attribute (php artisan geonames:fetch)',
];
