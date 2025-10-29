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
    latest_schedule?: ScheduleInterface;
    schedules?: ScheduleInterface[];
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
    seed_weight_kg: number;
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

export interface CropCareRecommendation {
    action: string;
    description: string;
    icon: string;
    priority: 'high' | 'medium' | 'low';
    category: string;
}

export interface YieldForecast {
    predicted_yield: number;
    expected_yield?: number;
    optimistic_yield: number;
    pessimistic_yield: number;
    yield_per_hectare: number;
    confidence: 'high' | 'medium' | 'low';
    confidence_score: number;
    r_squared: number;
    variance_from_expected_percent: number;
    environmental_factors: EnvironmentalFactor[];
    historical_yields: HistoricalYieldPoint[];
    growth_progress_percent: number;
    days_until_harvest: number;
    model_type: 'ml_regression' | 'basic_estimate';
    sample_size: number;
}

export interface EnvironmentalFactor {
    factor: string;
    impact: string;
    weight: number;
    status?: 'good' | 'warning' | 'info' | 'error';
}

export interface HistoricalYieldPoint {
    date: string;
    yield: number;
    yield_per_hectare: number;
    hectares: number;
}

export interface IncomeForecast {
    predicted_income: number;
    optimistic_income: number;
    pessimistic_income: number;
    expected_income?: number;
    income_per_hectare: number;
    confidence: 'high' | 'medium' | 'low';
    confidence_score: number;
    variance_from_expected?: number;
    variance_from_expected_percent?: number;
    harvest_date: string;
    days_until_harvest: number;
    yield_component: {
        predicted_yield: number;
        optimistic_yield: number;
        pessimistic_yield: number;
        confidence: 'high' | 'medium' | 'low';
        confidence_score: number;
        model_type: 'ml_regression' | 'basic_estimate';
    };
    price_component: {
        forecast_price: number;
        optimistic_price: number;
        pessimistic_price: number;
        current_price: number;
        trend: 'increasing' | 'decreasing' | 'stable';
        confidence: 'high' | 'medium' | 'low';
        confidence_score: number;
        price_volatility: number;
    };
    yield_factors: EnvironmentalFactor[];
    historical_income: HistoricalIncomePoint[];
    calculation_breakdown: {
        formula: string;
        yield_kg: number;
        price_per_kg: number;
        result: number;
    };
}

export interface HistoricalIncomePoint {
    harvest_date: string;
    income: number;
    income_per_hectare: number;
    yield: number;
    hectares: number;
}
