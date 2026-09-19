<?php

namespace App\controllers;

use App\models\Location;

class LocationController extends Controller
{
    /**
     * خروجی لیست استان‌های فعال به صورت JSON
     * GET /api/locations/provinces
     */
    public function provinces()
    {
        $provinces = Location::getActiveProvinces();
        $this->jsonResponse($provinces);
    }

    /**
     * خروجی لیست شهرهای یک استان به صورت JSON
     * GET /api/locations/cities?province_id=1 یا ?province=کردستان
     */
    public function cities()
    {
        $provinceId = isset($_GET['province_id']) ? (int) $_GET['province_id'] : 0;
        $provinceName = trim($_GET['province'] ?? '');

        if ($provinceId > 0) {
            $cities = Location::getCitiesByProvinceId($provinceId);
        } elseif (!empty($provinceName)) {
            $cities = Location::getCitiesByProvinceName($provinceName);
        } else {
            $this->jsonResponse(['error' => 'شناسه یا نام استان الزامی است.'], 400);
        }

        $this->jsonResponse($cities);
    }
}