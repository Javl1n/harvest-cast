import { InertiaLinkProps } from '@inertiajs/react';
import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
    sensors: SensorInterface[];
    weather: WeatherInterface;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

export interface SensorInterface {
    id: string;
    mac: string;
    created_at: string;
    updated_at: string;
    readings: SensorReadingInterface[];
    latest_reading: SensorReadingInterface;
    oldest_reading: SensorReadingInterface;
    [key: string]: unknown;
}

export interface SensorReadingInterface {
    id: string | number;
    sensor: SensorInterface;
    longitude: number;
    latitude: number;
    moisture: number;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
}

export interface WeatherInterface {
    [key: string]: any;
}

export interface CommodityInterface {
    id: number;
    name: string;
    created_at: string;
    updated_at: string;
    variants?: CommodityVariantInterface[];
}

export interface CommodityVariantInterface {
    id: number;
    commodity_id: number;
    name: string;
    created_at: string;
    updated_at: string;
}

export interface ScheduleInterface {
    id: number;
    commodity_id: number;
    sensor_id: string;
    hectares: number;
    seeds_planted: number;
    date_planted: string;
    expected_harvest_date?: string;
    actual_harvest_date?: string;
    yield?: number;
    expected_income: number;
    income?: number;
    created_at: string;
    updated_at: string;
    commodity?: CommodityInterface;
    sensor?: SensorInterface;
}

export interface CropRecommendation {
    crop: string;
    variety: string;
    score: number;
    suitability: 'excellent' | 'good' | 'fair' | 'poor' | 'unsuitable';
    reasons: string[];
    planting_tips: string;
    harvest_time: string;
    harvest_days: number;
    optimal_conditions: string;
    water_requirements: string;
}

export interface CurrentConditions {
    soil_moisture: number;
    temperature?: number;
    weather_condition?: string;
    humidity?: number;
    reading_date: string;
    weather_date?: string;
}
